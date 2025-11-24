<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;
use App\Exports\Report;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\ProgramRole;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramVendorFee;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Http\Resources\PaymentRequestResource;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->where('company_id', $company->id)
      ->get()
      ->pluck('program_id');

    $programs = Program::whereIn('id', $programs_ids)->get();

    $total_program_limit = 0;
    $utilized_amount = 0;
    $pipeline_amount = 0;
    $available_amount = 0;

    foreach ($programs as $program) {
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
        ->where('program_id', $program->id)
        ->first();
      $total_program_limit += $vendor_configuration->sanctioned_limit;
      $utilized_amount += $program->utilized_amount;
      $pipeline_amount += $program->pipeline_amount;
    }

    $available_amount = $total_program_limit - $utilized_amount - $pipeline_amount;

    return view('content.vendor.reports.index', [
      'total_program_limit' => $total_program_limit,
      'available_amount' => $available_amount,
      'utilized_amount' => $utilized_amount,
    ]);
  }

  public function allInvoicesReportView()
  {
    return view('content.vendor.reports.all-invoices');
  }

  public function programsReportView()
  {
    return view('content.vendor.reports.programs');
  }

  public function paymentsReportView()
  {
    return view('content.vendor.reports.payments');
  }

  public function data()
  {
    // Get past 12 months
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = Carbon::today()
        ->startOfMonth()
        ->subMonth($i);
      array_push($months, $month);
    }

    // Format months
    $months_formatted = [];
    foreach ($months as $key => $month) {
      array_push($months_formatted, Carbon::parse($month)->format('M'));
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->get()
      ->pluck('id');

    // Get Borrowing Levels
    $borrowing_levels = [];
    // All Invoices
    $all_invoices = [];
    // Invoices Pending Approval
    $pending_invoices = [];
    // Discount amounts
    $discount_amounts = [];

    foreach ($months as $month) {
      $payment_requests = PaymentRequest::whereIn('invoice_id', $invoices)
        ->where('status', 'paid')
        ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
        ->get();

      array_push($borrowing_levels, round($payment_requests->sum('amount')));
      $discount_amount = 0;
      foreach ($payment_requests as $payment_request) {
        $discount_amount += round(
          $payment_request->invoice->total +
            $payment_request->invoice->total_invoice_taxes -
            $payment_request->invoice->total_invoice_fees -
            $payment_request->invoice->total_invoice_discount -
            $payment_request->amount
        );
      }
      array_push($discount_amounts, $discount_amount);

      $invoice_data = Invoice::vendorFinancing()
        ->where('company_id', $company->id)
        ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
        ->get();
      $all_invoices_total = 0;
      $pending_invoices_total = 0;
      foreach ($invoice_data as $invoice) {
        $all_invoices_total +=
          $invoice->total +
          $invoice->total_invoice_taxes -
          $invoice->total_invoice_fees -
          $invoice->total_invoice_discount;
        if ($invoice->status == 'submitted') {
          $pending_invoices_total +=
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;
        }
      }
      array_push($all_invoices, $all_invoices_total);
      array_push($pending_invoices, $pending_invoices_total);
    }

    return response()->json([
      'months' => $months_formatted,
      'borrowing_levels' => [
        'data' => $borrowing_levels,
        'min' => min($borrowing_levels),
        'max' => max($borrowing_levels),
      ],
      'all_invoices' => $all_invoices,
      'pending_invoices' => $pending_invoices,
      'discount_amounts' => $discount_amounts,
    ]);
  }

  public function allInvoicesReport(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $invoices = InvoiceResource::collection(
      Invoice::vendorFinancing()
        ->with([
          'program.anchor',
          'invoiceItems',
          'invoiceFees',
          'invoiceTaxes',
          'invoiceDiscounts',
          'paymentRequests.paymentAccounts',
        ])
        ->where('company_id', $current_company->company_id)
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        })
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->when($from_date && $from_date != '', function ($query) use ($from_date) {
          $query->whereDate('due_date', '>=', $from_date);
        })
        ->when($to_date && $to_date != '', function ($query) use ($to_date) {
          $query->whereDate('due_date', '<=', $to_date);
        })
        ->when($status && $status != '', function ($query) use ($status) {
          $query->where('stage', $status);
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          $query->where('financing_status', $financing_status);
        })
        ->orderBy('due_date', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function programsReport(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $payment_account_number = $request->query('program_code');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::where('company_id', $current_company->company_id)
        ->when($payment_account_number && $payment_account_number != '', function ($query) use (
          $payment_account_number
        ) {
          $query->where('payment_account_number', 'LIKE', '%' . $payment_account_number . '%');
        })
        ->whereHas('program', function ($query) use ($anchor) {
          $query
            ->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            })
            ->when($anchor && $anchor != '', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
              });
            });
        })
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['programs' => $programs]);
    }
  }

  public function paymentsReport(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $po = $request->query('po');
    $invoice_status = $request->query('invoice_status');
    $financing_status = $request->query('financing_status');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');
    $from_due_date = $request->query('from_due_date');
    $to_due_date = $request->query('to_due_date');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $payments = InvoiceResource::collection(
      Invoice::where('company_id', $current_company->company_id)
        ->whereIn('financing_status', ['disbursed', 'closed'])
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
          $query->where('status', $invoice_status);
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          $query->where('financing_status', $financing_status);
        })
        ->when($po && $po != '', function ($query) use ($po) {
          $query->whereHas('purchae_order', function ($query) use ($po) {
            $query->where('purchase_order_number', '%' . $po . '%');
          });
        })
        ->when($from_invoice_date && $from_invoice_date != '', function ($query) use ($from_invoice_date) {
          $query->whereDate('invoice_date', '>=', $from_invoice_date);
        })
        ->when($to_invoice_date && $to_invoice_date != '', function ($query) use ($to_invoice_date) {
          $query->whereDate('invoice_date', '<=', $to_invoice_date);
        })
        ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
          $query->whereDate('due_date', '>=', $from_due_date);
        })
        ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
          $query->whereDate('due_date', '<=', $to_due_date);
        })
        ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
          $from_disbursement_date
        ) {
          $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
        })
        ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
          $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
        })
        ->orderBy('due_date', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['payments' => $payments]);
    }
  }

  public function export(Request $request, $type)
  {
    $date = now()->format('Y-m-d');

    switch ($type) {
      case 'all-invoices-report':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        // Get active company
        $current_company = auth()
          ->user()
          ->activeVendorCompany()
          ->first();

        $invoices = Invoice::vendorFinancing()
          ->with([
            'program.anchor',
            'invoiceItems',
            'invoiceFees',
            'invoiceTaxes',
            'invoiceDiscounts',
            'paymentRequests.paymentAccounts',
          ])
          ->where('company_id', $current_company->company_id)
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('name', 'LIKE', '%' . $anchor . '%');
              });
            });
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('due_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('due_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('stage', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->orderBy('due_date', 'DESC')
          ->get();

        $headers = [
          'Invoice Number',
          'Anchor',
          'Invoice Amount',
          'Invoice Date',
          'Due Date',
          'Disbursement Date',
          'Disbursed Amount',
          'Financing Status',
          'Discount Value',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Anchor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Amount'] =
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount ? $invoice->disbursed_amount : 0;
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount Value'] =
            $invoice->discount > 0 && ($invoice->status == 'disbursed' || $invoice->status == 'closed')
              ? $invoice->discount
              : '-';
        }

        Excel::store(new Report($headers, $data), 'Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'programs-report':
        $current_company = auth()
          ->user()
          ->activeVendorCompany()
          ->first();

        $anchor = $request->query('anchor');
        $payment_account_number = $request->query('program_code');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::where('company_id', $current_company->company_id)
            ->when($payment_account_number && $payment_account_number != '', function ($query) use (
              $payment_account_number
            ) {
              $query->where('payment_account_number', 'LIKE', '%' . $payment_account_number . '%');
            })
            ->whereHas('program', function ($query) use ($anchor) {
              $query
                ->whereHas('programCode', function ($query) {
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                })
                ->when($anchor && $anchor != '', function ($query) use ($anchor) {
                  $query->whereHas('anchor', function ($query) use ($anchor) {
                    $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
                  });
                });
            })
            ->get()
        );

        $headers = [
          'Loan/OD Account',
          'Anchor',
          'Financing Limit',
          'Utilized Limit',
          'Pipeline Amount',
          'Available Limit',
          'Limit Review Date',
        ];
        $data = [];

        foreach ($programs as $key => $program) {
          $data[$key]['Loan/OD Account'] = $program->payment_account_number;
          $data[$key]['Anchor'] = $program->anchor_name;
          $data[$key]['Financing Limit'] = number_format($program->sanctioned_limit, 2);
          $data[$key]['Utilized Limit'] = number_format($program->utilized, 2);
          $data[$key]['Pipeline Amount'] = number_format($program->pipeline, 2);
          $data[$key]['Available Limit'] = number_format(
            $program->sanctioned_limit - $program->utilized - $program->pipeline,
            2
          );
          $data[$key]['Limit Review Date'] = $program->limit_review_date
            ? Carbon::parse($program->limit_review_date)->format('d M Y')
            : '-';
        }

        Excel::store(new Report($headers, $data), 'Programs_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Programs_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'payments-report':
        $invoice_number = $request->query('invoice_number');
        $po = $request->query('po');
        $invoice_status = $request->query('invoice_status');
        $financing_status = $request->query('financing_status');
        $from_invoice_date = $request->query('from_invoice_date');
        $to_invoice_date = $request->query('to_invoice_date');
        $from_due_date = $request->query('from_due_date');
        $to_due_date = $request->query('to_due_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $current_company = auth()
          ->user()
          ->activeVendorCompany()
          ->first();

        $payments = Invoice::where('company_id', $current_company->company_id)
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
            $query->where('status', $invoice_status);
          })
          ->when(!$financing_status || $financing_status === '', function ($query) {
            $query->whereIn('financing_status', ['disbursed', 'closed']);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->when($po && $po != '', function ($query) use ($po) {
            $query->whereHas('purchae_order', function ($query) use ($po) {
              $query->where('purchase_order_number', '%' . $po . '%');
            });
          })
          ->when($from_invoice_date && $from_invoice_date != '', function ($query) use ($from_invoice_date) {
            $query->whereDate('invoice_date', '>=', $from_invoice_date);
          })
          ->when($to_invoice_date && $to_invoice_date != '', function ($query) use ($to_invoice_date) {
            $query->whereDate('invoice_date', '<=', $to_invoice_date);
          })
          ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
            $query->whereDate('due_date', '>=', $from_due_date);
          })
          ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
            $query->whereDate('due_date', '<=', $to_due_date);
          })
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->orderBy('due_date', 'DESC')
          ->get();

        $headers = [
          'System ID',
          'Invoice Number',
          'Anchor',
          'Invoice Amount',
          'P.I. Amount',
          'Invoice Date',
          'Due Date',
          'Payment Tenor',
          'Discount Charge(%)',
          'Discount Amount',
          'Fees/Charges',
          'Total Charges',
          'Disbursed Amount',
          'Disbursement Date',
          'Status',
          'Payment Closure Date',
          'Principal Balance',
          'Penal Charges',
          'Total Outstanding',
        ];
        $data = [];

        foreach ($payments as $key => $invoice) {
          $vendor_configuration = ProgramVendorDiscount::where('company_id', $invoice->company_id)
            ->where('program_id', $invoice->program_id)
            ->first();
          $data[$key]['System ID'] = $invoice->id;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Anchor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['P.I. Amount'] = number_format(($invoice->eligibility / 100) * $invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Payment Tenor'] = Carbon::parse($invoice->due_date)->diffInDays(
            Carbon::parse($invoice->invoice_date)
          );
          $data[$key]['Discount Rate(%)'] = $vendor_configuration->total_roi;
          $data[$key]['Discount Amount'] = number_format($invoice->discount, 2);
          $data[$key]['Fees/Charges'] = number_format($invoice->program_fees, 2);
          $data[$key]['Total Charges'] = number_format($invoice->discount + $invoice->program_fees, 2);
          $data[$key]['Disbursed Amount'] = number_format($invoice->disbursed_amount, 2);
          $data[$key]['Disbursement Date'] = Carbon::parse($invoice->disbursement_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->financing_status);
          $data[$key]['Payment Closure Date'] =
            $invoice->financing_status === 'closed' ? $invoice->payments->last()->created_at->format('d M Y') : '-';
          $data[$key]['Principal Balance'] =
            $invoice->financing_status === 'closed'
              ? 0
              : number_format($invoice->invoice_total_amount - $invoice->overdue_amount, 2);
          $data[$key]['Penal Charges'] = number_format($invoice->overdue_amount, 2);
          $data[$key]['Total Outstanding'] =
            $invoice->financing_status === 'closed'
              ? 0
              : number_format($invoice->invoice_total_amount + $invoice->overdue_amount, 2);
        }

        Excel::store(new Report($headers, $data), 'Payments_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Payments_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      default:
        break;
    }
  }

  public function exportPdf(Request $request, $type)
  {
    $date = now()->format('Y-m-d');

    switch ($type) {
      case 'all-invoices-report':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        // Get active company
        $current_company = auth()
          ->user()
          ->activeVendorCompany()
          ->first();

        $invoices = Invoice::vendorFinancing()
          ->with([
            'program.anchor',
            'invoiceItems',
            'invoiceFees',
            'invoiceTaxes',
            'invoiceDiscounts',
            'paymentRequests.paymentAccounts',
          ])
          ->where('company_id', $current_company->company_id)
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('name', 'LIKE', '%' . $anchor . '%');
              });
            });
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('due_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('due_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('stage', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->orderBy('due_date', 'DESC')
          ->get();

        $headers = [
          'Invoice Number',
          'Anchor',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Disbursement Date',
          'Disbursed Amount',
          'Financing Status',
          'Discount Value',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Anchor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Amount'] =
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount
            ? number_format($invoice->disbursed_amount, 2)
            : 0;
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount Value'] =
            $invoice->discount > 0 && ($invoice->status == 'disbursed' || $invoice->status == 'closed')
              ? number_format($invoice->discount, 2)
              : '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'All Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('All_Invoices_Report_' . $date . '.pdf');
        break;
      case 'programs-report':
        $current_company = auth()
          ->user()
          ->activeVendorCompany()
          ->first();

        $anchor = $request->query('anchor');
        $payment_account_number = $request->query('program_code');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::where('company_id', $current_company->company_id)
            ->when($payment_account_number && $payment_account_number != '', function ($query) use (
              $payment_account_number
            ) {
              $query->where('payment_account_number', 'LIKE', '%' . $payment_account_number . '%');
            })
            ->whereHas('program', function ($query) use ($anchor) {
              $query
                ->whereHas('programCode', function ($query) {
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                })
                ->when($anchor && $anchor != '', function ($query) use ($anchor) {
                  $query->whereHas('anchor', function ($query) use ($anchor) {
                    $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
                  });
                });
            })
            ->get()
        );

        $headers = [
          'Program Code',
          'Anchor',
          'Financing Limit',
          'Utilized Limit',
          'Pipeline Amount',
          'Available Limit',
          'Limit Review Date',
        ];
        $data = [];

        foreach ($programs as $key => $program) {
          $data[$key]['Loan/OD Account'] = $program->payment_account_number;
          $data[$key]['Anchor'] = $program->anchor_name;
          $data[$key]['Financing Limit'] = number_format($program->sanctioned_limit, 2);
          $data[$key]['Utilized Limit'] = number_format($program->utilized, 2);
          $data[$key]['Pipeline Amount'] = number_format($program->pipeline, 2);
          $data[$key]['Available Limit'] = number_format(
            $program->sanctioned_limit - $program->utilized - $program->pipeline,
            2
          );
          $data[$key]['Limit Review Date'] = $program->limit_review_date
            ? Carbon::parse($program->limit_review_date)->format('d M Y')
            : '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Programs Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Programs_Report_' . $date . '.pdf');
        break;
      case 'payments-report':
        $invoice_number = $request->query('invoice_number');
        $po = $request->query('po');
        $invoice_status = $request->query('invoice_status');
        $financing_status = $request->query('financing_status');
        $from_invoice_date = $request->query('from_invoice_date');
        $to_invoice_date = $request->query('to_invoice_date');
        $from_due_date = $request->query('from_due_date');
        $to_due_date = $request->query('to_due_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $current_company = auth()
          ->user()
          ->activeVendorCompany()
          ->first();

        $payments = Invoice::where('company_id', $current_company->company_id)
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
            $query->where('status', $invoice_status);
          })
          ->when(!$financing_status || $financing_status === '', function ($query) {
            $query->whereIn('financing_status', ['disbursed', 'closed']);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->when($po && $po != '', function ($query) use ($po) {
            $query->whereHas('purchae_order', function ($query) use ($po) {
              $query->where('purchase_order_number', '%' . $po . '%');
            });
          })
          ->when($from_invoice_date && $from_invoice_date != '', function ($query) use ($from_invoice_date) {
            $query->whereDate('invoice_date', '>=', $from_invoice_date);
          })
          ->when($to_invoice_date && $to_invoice_date != '', function ($query) use ($to_invoice_date) {
            $query->whereDate('invoice_date', '<=', $to_invoice_date);
          })
          ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
            $query->whereDate('due_date', '>=', $from_due_date);
          })
          ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
            $query->whereDate('due_date', '<=', $to_due_date);
          })
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->orderBy('due_date', 'DESC')
          ->get();

        $headers = [
          'System ID',
          'Invoice Number',
          'Anchor',
          'Invoice Amount',
          'P.I. Amount',
          'Invoice Date',
          'Due Date',
          'Payment Tenor',
          'Discount Charge(%)',
          'Discount Amount',
          'Fees/Charges',
          'Total Discount Amount',
          'Disbursed Amount',
          'Disbursement Date',
          'Status',
          'Payment Closure Date',
          'Principal Balance',
          'Penal Charges',
          'Total Outstanding',
        ];
        $data = [];

        foreach ($payments as $key => $invoice) {
          $vendor_configuration = ProgramVendorDiscount::where('company_id', $invoice->company_id)
            ->where('program_id', $invoice->program_id)
            ->first();
          $data[$key]['System ID'] = $invoice->id;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Anchor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['P.I. Amount'] = number_format(($invoice->eligibility / 100) * $invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Payment Tenor'] = Carbon::parse($invoice->due_date)->diffInDays(
            Carbon::parse($invoice->invoice_date)
          );
          $data[$key]['Discount Rate(%)'] = $vendor_configuration->total_roi;
          $data[$key]['Discount Amount'] = number_format($invoice->discount, 2);
          $data[$key]['Fees/Charges'] = number_format($invoice->program_fees, 2);
          $data[$key]['Total Discount Amount'] = number_format($invoice->discount + $invoice->program_fees, 2);
          $data[$key]['Disbursed Amount'] = number_format($invoice->disbursed_amount, 2);
          $data[$key]['Disbursement Date'] = Carbon::parse($invoice->disbursement_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->financing_status);
          $data[$key]['Payment Closure Date'] =
            $invoice->financing_status === 'closed' ? $invoice->payments->last()->created_at->format('d M Y') : '-';
          $data[$key]['Principal Balance'] =
            $invoice->financing_status === 'closed'
              ? 0
              : number_format($invoice->invoice_total_amount - $invoice->overdue_amount, 2);
          $data[$key]['Penal Charges'] = number_format($invoice->overdue_amount, 2);
          $data[$key]['Total Outstanding'] =
            $invoice->financing_status === 'closed'
              ? 0
              : number_format($invoice->invoice_total_amount + $invoice->overdue_amount, 2);
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Payments Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Payments_Report_' . $date . '.pdf');
        break;
      default:
        break;
    }
  }
}
