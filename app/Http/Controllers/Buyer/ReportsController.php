<?php

namespace App\Http\Controllers\Buyer;

use Carbon\Carbon;
use App\Exports\Report;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Models\ProgramRole;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramVendorFee;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountMaturingInvoices;
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
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'buyer')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->get()
      ->pluck('program_id');

    $programs = Program::whereHas(
      'programCode',
      fn($query) => $query
        ->where('name', Program::FACTORING_WITH_RECOURSE)
        ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE)
    )
      ->whereIn('id', $programs_ids)
      ->get();

    $total_program_limit = 0;
    $utilized_amount = 0;
    $pipeline_amount = 0;
    $available_amount = 0;

    foreach ($programs as $program) {
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', $program->anchor->id)
        ->where('program_id', $program->id)
        ->first();
      if ($vendor_configuration) {
        $total_program_limit += $vendor_configuration->sanctioned_limit;
      }
      $utilized_amount += $program->utilized_amount;
      $pipeline_amount += $program->pipeline_amount;
    }

    $available_amount = $total_program_limit - $utilized_amount - $pipeline_amount;

    return view('content.buyer.reports.invoices-reports', [
      'total_program_limit' => $total_program_limit,
      'available_amount' => $available_amount,
      'utilized_amount' => $utilized_amount,
      'default_currency' => $company->default_currency,
    ]);
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
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::factoring()
      ->where('buyer_id', $company->id)
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

      $invoice_data = Invoice::factoring()
        ->where('buyer_id', $company->id)
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

  public function invoiceAnalysisView()
  {
    return view('content.buyer.reports.invoice-analysis');
  }

  public function maturingInvoicesView()
  {
    return view('content.buyer.reports.maturing-invoices');
  }

  public function allInvoicesView()
  {
    return view('content.buyer.reports.all-invoices');
  }

  public function paidInvoicesView()
  {
    return view('content.buyer.reports.paid-invoices');
  }

  public function overdueInvoicesView()
  {
    return view('content.buyer.reports.overdue-invoices');
  }

  public function closedInvoicesView()
  {
    return view('content.buyer.reports.closed-invoices');
  }

  public function vendorAnalysisView()
  {
    return view('content.buyer.reports.vendor-analysis');
  }

  public function reports(Request $request)
  {
    $type = $request->query('type');

    switch ($type) {
      case 'invoice-analysis':
        return $this->invoiceAnalysis($request);
        break;
      case 'all-invoices':
        return $this->allInvoices($request);
        break;
      case 'vendor-analysis':
        return $this->vendorAnalysis($request);
        break;
      case 'maturing-invoices':
        return $this->maturingInvoices($request);
        break;
      case 'paid-invoices':
        return $this->paidInvoices($request);
        break;
      case 'overdue-invoices':
        return $this->overdueInvoices($request);
        break;
      case 'closed-invoices':
        return $this->closedInvoices($request);
        break;
      default:
        # code...
        break;
    }
  }

  private function company(): Company
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    return $company;
  }

  private function invoiceAnalysis(Request $request)
  {
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');

    $per_page = $request->query('per_page');

    $invoices = InvoiceResource::collection(
      Invoice::factoring()
        ->where('buyer_id', $this->company()->id)
        // ->whereHas('paymentRequests')
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->when($from_date && $from_date != '', function ($query) use ($from_date) {
          $query->whereDate('due_date', '>=', Carbon::parse($from_date));
        })
        ->when($to_date && $to_date != '', function ($query) use ($to_date) {
          $query->whereDate('due_date', '<=', Carbon::parse($to_date));
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          $query->where('financing_status', $financing_status);
        })
        ->orderBy('due_date', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return $invoices;
  }

  private function allInvoices(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');

    $per_page = $request->query('per_page');

    return InvoiceResource::collection(
      Invoice::factoring()
        ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
          $query->where('status', $status);
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          $query->where('financing_status', $financing_status);
        })
        ->where('buyer_id', $this->company()->id)
        ->orderBy('created_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();
  }

  private function closedInvoices(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');

    $per_page = $request->query('per_page');

    return InvoiceResource::collection(
      Invoice::factoring()
        ->where('financing_status', 'closed')
        ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
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
        ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
          $from_disbursement_date
        ) {
          $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
        })
        ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
          $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
        })
        ->when($status && $status != '', function ($query) use ($status) {
          $query->where('status', $status);
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          $query->where('financing_status', $financing_status);
        })
        ->where('buyer_id', $this->company()->id)
        ->orderBy('updated_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();
  }

  private function vendorAnalysis(Request $request)
  {
    $per_page = $request->query('per_page');

    $vendors = [];

    // Get vendors
    foreach ($this->company()->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
          $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
      ) {
        $vendor = $program->anchor;
        $vendor['utilized_amount'] = $program->anchor->utilizedAmount($program);
        $vendor['pipeline_amount'] = $program->anchor->pipelineAmount($program);
        $vendor['configuration'] = ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('buyer_id', $this->company()->id)
          ->first();
        array_push($vendors, $vendor);
      }
    }

    return Helpers::paginate($vendors, $per_page);
  }

  private function maturingInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');

    return OdAccountMaturingInvoices::collection(
      ProgramVendorConfiguration::where('buyer_id', $this->company()->id)
        ->when($vendor && $vendor != '', function ($query) use ($vendor) {
          $query->whereHas('program', function ($query) use ($vendor) {
            $query->whereHas('anchor', function ($query) use ($vendor) {
              $query->where('companies.name', 'LIKE', '%' . $vendor . '%');
            });
          });
        })
        ->join('companies', 'companies.id', '=', 'program_vendor_configurations.company_id')
        ->orderBy('companies.name', 'ASC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();
  }

  private function paidInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');

    return InvoiceResource::collection(
      Invoice::factoring()
        ->with('company', 'program', 'paymentRequests', 'invoiceItems')
        ->where('buyer_id', $this->company()->id)
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
        ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
          $from_disbursement_date
        ) {
          $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
        })
        ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
          $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
        })
        ->when($status && $status != '', function ($query) use ($status) {
          $query->where('status', $status);
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          if ($financing_status == 'past_due') {
            $query->whereDate('due_date', '<', now()->format('Y-m-d'))->whereIn('financing_status', ['disbursed']);
          } else {
            $query->where('financing_status', $financing_status);
          }
        })
        ->when(!$financing_status || $financing_status == '', function ($query) use ($financing_status) {
          $query->whereIn('financing_status', ['disbursed', 'closed']);
        })
        ->orderBy('updated_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();
  }

  private function overdueInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');

    return InvoiceResource::collection(
      Invoice::factoring()
        ->with('company', 'program', 'paymentRequests', 'invoiceItems')
        ->where('buyer_id', $this->company()->id)
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
        ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
          $from_disbursement_date
        ) {
          $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
        })
        ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
          $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
        })
        ->whereIn('financing_status', ['disbursed'])
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->orderBy('updated_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();
  }

  public function dealerIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $dealer_role->id)
      ->where('company_id', $company->id)
      ->get()
      ->pluck('program_id');

    $programs = Program::whereHas('programType', fn($query) => $query->where('name', 'Dealer Financing'))
      ->whereIn('id', $programs_ids)
      ->get();

    $total_program_limit = 0;
    $utilized_amount = 0;
    $available_amount = 0;

    foreach ($programs as $program) {
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
        ->where('program_id', $program->id)
        ->first();
      if ($vendor_configuration) {
        $total_program_limit += $vendor_configuration->sanctioned_limit;
      }
      $utilized_amount += $program->utilized_amount;
    }

    $available_amount = $total_program_limit - $utilized_amount;

    return view('content.dealer.reports.invoices-reports', [
      'total_program_limit' => $total_program_limit,
      'available_amount' => $available_amount,
      'utilized_amount' => $utilized_amount,
      'default_currency' => $company->default_currency,
    ]);
  }

  public function dealerFinancingData()
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
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::dealerFinancing()
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

      $invoice_data = Invoice::dealerFinancing()
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

  public function dealerAllInvoicesReportView()
  {
    return view('content.dealer.reports.all-invoices');
  }

  public function dealerProgramsReportView()
  {
    return view('content.dealer.reports.programs');
  }

  public function dealerPaymentsReportView()
  {
    return view('content.dealer.reports.payments');
  }

  public function dealerFinancingAllInvoicesReport(Request $request)
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
      ->activeBuyerCompany()
      ->first();

    $invoices = Invoice::dealerFinancing()
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
        $query->whereDate('invoice_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('invoice_date', '<=', $to_date);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('stage', $status);
      })
      ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
        $query->where('financing_status', $financing_status);
      })
      ->orderBy('created_at', 'DESC')
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function dealerFinancingProgramsReport(Request $request)
  {
    $loan_account = $request->query('loan_account');
    $anchor = $request->query('anchor');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('program.anchor')
        ->whereHas('program', function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        })
        ->where('company_id', $current_company->company_id)
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        })
        ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
          $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
        })
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['programs' => $programs]);
    }
  }

  public function dealerFinancingPaymentsReport(Request $request)
  {
    $invoice_number = $request->query('invoice_number');
    $po = $request->query('po');
    $invoice_status = $request->query('invoice_status');
    $financing_status = $request->query('financing_status');

    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoice_ids = Invoice::dealerFinancing()
      ->where('company_id', $current_company->company_id)
      ->where('disbursement_date', '!=', null)
      ->pluck('id');

    $payments = PaymentRequestResource::collection(
      PaymentRequest::with('invoice.program.anchor', 'invoice.purchaseOrder')
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->whereHas('invoice', function ($query) use ($invoice_number) {
            $query->where('invoice_number', '%' . $invoice_number . '%');
          });
        })
        ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
          $query->whereHas('invoice', function ($query) use ($invoice_status) {
            $query->where('status', $invoice_status);
          });
        })
        ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
          $query->whereHas('invoice', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          });
        })
        ->when($po && $po != '', function ($query) use ($po) {
          $query->whereHas('invoice', function ($query) use ($po) {
            $query->whereHas('purchae_order', function ($query) use ($po) {
              $query->where('purchase_order_number', '%' . $po . '%');
            });
          });
        })
        ->whereIn('invoice_id', $invoice_ids)
        ->orderBy('updated_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['payments' => $payments]);
    }
  }

  public function odAccountsReport(Request $request)
  {
    return view('content.dealer.reports.od-accounts');
  }

  public function odAccountDetails(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);
  }

  public function export(Request $request, $type)
  {
    $date = now()->format('Y-m-d');

    switch ($type) {
      case 'invoice-analysis':
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->where('buyer_id', $this->company()->id)
          ->get()
          ->pluck('id');

        $payments = InvoiceResource::collection(
          Invoice::factoring()
            ->where('buyer_id', $this->company()->id)
            // ->whereHas('paymentRequests')
            ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
              $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
            })
            ->when($from_date && $from_date != '', function ($query) use ($from_date) {
              $query->whereDate('due_date', '>=', Carbon::parse($from_date));
            })
            ->when($to_date && $to_date != '', function ($query) use ($to_date) {
              $query->whereDate('due_date', '<=', Carbon::parse($to_date));
            })
            ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
              $query->where('financing_status', $financing_status);
            })
            ->orderBy('due_date', 'DESC')
            ->get()
        );

        $headers = [
          'Invoice Number',
          'Invoice Date',
          'Disbursement Date',
          'Due Date',
          'Financing Status',
          'Anchor',
          'Invoice Amount',
        ];
        $data = [];

        foreach ($payments as $key => $payment) {
          $data[$key]['Invoice Number'] = $payment->invoice_number;
          $data[$key]['Invoice Date'] = Carbon::parse($payment->invoice_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $payment->disbursement_date
            ? Carbon::parse($payment->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Invoice Due Date'] = Carbon::parse($payment->due_date)->format('d M Y');
          $data[$key]['Financing Status'] = Str::title($payment->financing_status);
          $data[$key]['Anchor'] = $payment->program->anchor->name;
          $data[$key]['Invoice Amount'] = number_format($payment->invoice_total_amount, 2);
        }

        Excel::store(new Report($headers, $data), 'Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'all-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
            $query->whereDate('invoice_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('invoice_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->where('buyer_id', $this->company()->id)
          ->orderBy('created_at', 'DESC')
          ->get();

        $headers = [
          'Anchor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Status',
          'Financing Status',
          'Disbursement Date',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Anchor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] =
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
        }

        Excel::store(new Report($headers, $data), 'Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'paid-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $invoices = Invoice::factoring()
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            if ($financing_status == 'past_due') {
              $query->whereDate('due_date', '<', now()->format('Y-m-d'))->whereIn('financing_status', ['disbursed']);
            } else {
              $query->where('financing_status', $financing_status);
            }
          })
          ->when(!$financing_status || $financing_status == '', function ($query) use ($financing_status) {
            $query->whereIn('financing_status', ['disbursed', 'closed']);
          })
          ->where('buyer_id', $this->company()->id)
          ->orderBy('updated_at', 'DESC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Status',
          'Financing Status',
          'Disbursement Date',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
        }

        Excel::store(new Report($headers, $data), 'Paid_Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Paid_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'overdue-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $invoices = Invoice::factoring()
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->where('buyer_id', $this->company()->id)
          ->orderBy('updated_at', 'DESC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Overdue Amount',
          'Days Past Due',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Overdue Amount'] = number_format($invoice->balance);
          $data[$key]['Days Past Due'] = $invoice->days_past_due;
        }

        Excel::store(new Report($headers, $data), 'Overdue_Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Overdue_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'vendor-analysis':
        $headers = ['Anchor', 'Limit', 'Utilized Limit', 'Available Limit', 'Pipeline Amount'];
        $data = [];

        foreach ($this->company()->programs as $key => $program) {
          if (
            $program->programType->name == Program::VENDOR_FINANCING &&
            ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
              $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
          ) {
            $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
              ->where('buyer_id', $this->company()->id)
              ->first();
            $utilized_amount = $program->anchor->utilizedAmount($program);
            $data[$key]['Anchor'] = $program->anchor->name;
            $data[$key]['limit'] = $vendor_configuration->sanctioned_limit;
            $data[$key]['Utilized Amount'] = $utilized_amount;
            $data[$key]['Available Amount'] = $vendor_configuration->sanctioned_limit - $utilized_amount;
            $data[$key]['Pipeline Amount'] = $program->anchor->pipelineAmount($program);
          }
        }

        Excel::store(new Report($headers, $data), 'Anchors_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Anchors_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
      case 'closed-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->where('financing_status', 'closed')
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
              });
            });
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('invoice_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('invoice_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->where('buyer_id', $this->company()->id)
          ->orderBy('due_date', 'ASC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Disbursement Date',
          'Date of Closure',
          'Transaction Reference No.',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
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
          $data[$key]['Date of Closure'] =
            $invoice->financing_status === 'closed'
              ? Carbon::parse($invoice->closure_date->created_at)->format('d M Y')
              : '-';
          $data[$key]['Transaction Reference No.'] = $invoice->closure_transaction_reference ?? '-';
        }

        Excel::store(new Report($headers, $data), 'Closed_Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Closed_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'maturing-invoices':
        $anchor = $request->query('anchor');

        $payment_accounts = ProgramVendorConfiguration::where('buyer_id', $this->company()->id)
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
              });
            });
          })
          ->join('companies', 'companies.id', '=', 'program_vendor_configurations.company_id')
          ->orderBy('companies.name', 'ASC')
          ->get();

        $headers = [
          'Vendor',
          'Total Due',
          'Due <0 Days',
          'Due 1-7 Days',
          'Due 8-14 Days',
          'Due 15-21 Days',
          'Due 22-30 Days',
          'Due 31-45 Days',
          'Due 46-60 Days',
          'Due 61-75 Days',
          'Due 76-90 Days',
        ];
        $data = [];

        foreach ($payment_accounts as $key => $payment_account) {
          $total_due = 0;
          $due_today = 0;
          $due_seven_days = 0;
          $due_fourteen_days = 0;
          $due_twenty_one_days = 0;
          $due_thirty_days = 0;
          $due_fourty_five_days = 0;
          $due_sixty_days = 0;
          $due_seventy_five_days = 0;
          $due_ninety_days = 0;

          $total_due = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->sum('calculated_total_amount');

          $due_today = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereDate('due_date', '<=', now()->format('Y-m-d'))
            ->sum('calculated_total_amount');

          $due_seven_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDay()
                ->format('Y-m-d'),
              now()
                ->addDays(7)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_fourteen_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(8)
                ->format('Y-m-d'),
              now()
                ->addDays(14)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_twenty_one_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(15)
                ->format('Y-m-d'),
              now()
                ->addDays(21)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_thirty_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(22)
                ->format('Y-m-d'),
              now()
                ->addDays(30)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_fourty_five_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(31)
                ->format('Y-m-d'),
              now()
                ->addDays(45)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_sixty_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(46)
                ->format('Y-m-d'),
              now()
                ->addDays(60)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_seventy_five_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(61)
                ->format('Y-m-d'),
              now()
                ->addDays(75)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_ninety_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(76)
                ->format('Y-m-d'),
              now()
                ->addDays(90)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $data[$key]['Vendor'] =
            $payment_account->program->anchor->name . ' (' . $payment_account->payment_account_number . ')';
          $data[$key]['Total Due'] = number_format($total_due, 2);
          $data[$key]['Due <0 Days'] = number_format($due_today, 2);
          $data[$key]['Due 1-7 Days'] = number_format($due_seven_days, 2);
          $data[$key]['Due 8-14 Days'] = number_format($due_fourteen_days, 2);
          $data[$key]['Due 15-21 Days'] = number_format($due_twenty_one_days, 2);
          $data[$key]['Due 22-30 Days'] = number_format($due_thirty_days, 2);
          $data[$key]['Due 31-45 Days'] = number_format($due_fourty_five_days, 2);
          $data[$key]['Due 46-60 Days'] = number_format($due_sixty_days, 2);
          $data[$key]['Due 61-75 Days'] = number_format($due_seventy_five_days, 2);
          $data[$key]['Due 76-90 Days'] = number_format($due_ninety_days, 2);
        }

        Excel::store(new Report($headers, $data), 'Maturing_Payments_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download(
          'Maturing_Payments_' . $date . '.csv',
          \Maatwebsite\Excel\Excel::CSV,
          [
            'Content-Type' => 'text/csv',
          ]
        );
        break;
      case 'dealer-all-invoices-report':
        $current_company = auth()
          ->user()
          ->activeBuyerCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::dealerFinancing()
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
            $query->whereDate('invoice_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('invoice_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('stage', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->orderBy('created_at', 'DESC')
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
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount ? $invoice->disbursed_amount : 0;
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount Value'] =
            $invoice->paymentRequests->count() > 0 ? $invoice->paymentRequests->first()->discount : '-';
        }

        Excel::store(new Report($headers, $data), 'Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'dealer-programs-report':
        $current_company = auth()
          ->user()
          ->activeBuyerCompany()
          ->first();

        $anchor = $request->query('anchor');
        $loan_account = $request->query('loan_account');

        $invoice_ids = Invoice::dealerFinancing()
          ->where('company_id', $current_company->company_id)
          ->get()
          ->pluck('program_id');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::whereHas('program', function ($query) {
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
          })
            ->where('company_id', $current_company->company_id)
            ->when($anchor && $anchor != '', function ($query) use ($anchor) {
              $query->whereHas('program', function ($query) use ($anchor) {
                $query->whereHas('anchor', function ($query) use ($anchor) {
                  $query->where('name', 'LIKE', '%' . $anchor . '%');
                });
              });
            })
            ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
              $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
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
      case 'dealer-payments-report':
        $invoice_number = $request->query('invoice_number');
        $po = $request->query('po');
        $invoice_status = $request->query('invoice_status');
        $financing_status = $request->query('financing_status');

        $current_company = auth()
          ->user()
          ->activeBuyerCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $invoice_ids = Invoice::dealerFinancing()
          ->where('company_id', $current_company->company_id)
          ->where('disbursement_date', '!=', null)
          ->get()
          ->pluck('id');

        $payments = PaymentRequest::with('invoice.program.anchor', 'invoice.purchaseOrder')
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->whereHas('invoice', function ($query) use ($invoice_number) {
              $query->where('invoice_number', '%' . $invoice_number . '%');
            });
          })
          ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
            $query->whereHas('invoice', function ($query) use ($invoice_status) {
              $query->where('status', $invoice_status);
            });
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->whereHas('invoice', function ($query) use ($financing_status) {
              $query->where('financing_status', $financing_status);
            });
          })
          ->when($po && $po != '', function ($query) use ($po) {
            $query->whereHas('invoice', function ($query) use ($po) {
              $query->whereHas('purchae_order', function ($query) use ($po) {
                $query->where('purchase_order_number', '%' . $po . '%');
              });
            });
          })
          ->whereIn('invoice_id', $invoice_ids)
          ->orderBy('updated_at', 'DESC')
          ->get();

        $headers = [
          'System ID',
          'Invoice Number',
          'Anchor',
          'Disbursement Date',
          'Due Date',
          'DPD (Days)',
          'Payment Tenor',
          'Discount Rate(%)',
          'Fees/Charges',
          'Principal Amount',
          'Disbursed Amount',
          'Principal Balance',
          'Total Discount Amount',
          'Balance Discount Amount',
          'Total Penal Amount',
          'Balance Penal Amount',
          'Total Outstanding',
          'Payment Closure Date',
          'Status',
        ];
        $data = [];

        foreach ($payments as $key => $payment) {
          $vendor_configuration = ProgramVendorConfiguration::where('company_id', $payment->invoice->company_id)
            ->where('program_id', $payment->invoice->program_id)
            ->first();
          $data[$key]['System ID'] = $payment->id;
          $data[$key]['Invoice Number'] = $payment->invoice->invoice_number;
          $data[$key]['Anchor'] = $payment->invoice->program->anchor->name;
          $data[$key]['Disbursement Date'] = Carbon::parse($payment->invoice->disbursement_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($payment->invoice->due_date)->format('d M Y');
          $data[$key]['DPD (Days)'] = $payment->invoice->days_past_due;
          $data[$key]['Payment Tenor'] = Carbon::parse($payment->invoice->due_date)->diffInDays(
            Carbon::parse($payment->invoice->disbursement_date)
          );
          $data[$key]['Discount Rate(%)'] = $vendor_configuration->total_roi;
          $data[$key]['Fees/Charges'] = number_format($payment->vendor_fees, 2);
          $data[$key]['Principal Amount'] = number_format($payment->invoice->invoice_total_amount, 2);
          $data[$key]['Disbursed Amount'] = number_format($payment->invoice->disbursed_amount, 2);
          $data[$key]['Principal Balance'] = number_format($payment->invoice->balance, 2);
          $data[$key]['Total Discount Amount'] = number_format($payment->discount, 2);
          $data[$key]['Balance Discount Amount'] = 0;
          $data[$key]['Total Penal'] = number_format(
            $payment->invoice->invoice_total_amount - $payment->invoice->overdue_amount,
            2
          );
          $data[$key]['Balance Penal Amount'] = number_format($payment->invoice->balance, 2);
          $data[$key]['Total Outstanding'] = number_format($payment->invoice->overdue_amount, 2);
          $data[$key]['Payment Closure Date'] =
            $payment->invoice->financing_status == 'closed'
              ? $payment->invoice->payments->last()->created_at->format('d M Y')
              : '-';
          $data[$key]['Status'] = Str::title($payment->invoice->financing_status);
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
      case 'invoice-analysis':
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $payments = InvoiceResource::collection(
          Invoice::factoring()
            ->where('buyer_id', $this->company()->id)
            // ->whereHas('paymentRequests')
            ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
              $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
            })
            ->when($from_date && $from_date != '', function ($query) use ($from_date) {
              $query->whereDate('due_date', '>=', Carbon::parse($from_date));
            })
            ->when($to_date && $to_date != '', function ($query) use ($to_date) {
              $query->whereDate('due_date', '<=', Carbon::parse($to_date));
            })
            ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
              $query->where('financing_status', $financing_status);
            })
            ->orderBy('due_date', 'DESC')
            ->get()
        );

        $headers = [
          'Invoice Number',
          'Invoice Date',
          'Disbursement Date',
          'Invoice Due Date',
          'Financing Status',
          'Anchor',
          'Invoice Amount',
          'Disbursed Amount',
          'Discount',
        ];
        $data = [];

        foreach ($payments as $key => $payment) {
          $data[$key]['Invoice Number'] = $payment->invoice_number;
          $data[$key]['Invoice Date'] = Carbon::parse($payment->invoice_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $payment->disbursement_date
            ? Carbon::parse($payment->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Invoice Due Date'] = Carbon::parse($payment->due_date)->format('d M Y');
          $data[$key]['Financing Status'] = Str::title($payment->financing_status);
          $data[$key]['Anchor'] = $payment->anchor;
          $data[$key]['Invoice Amount'] = number_format($payment->invoice_total_amount, 2);
          $data[$key]['Disbursed Amount'] = number_format($payment->disbursed_amount, 2);
          $data[$key]['Discount'] = number_format($payment->discount, 2);
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Invoice Analysis Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Invoice_Analysis_Report_' . $date . '.pdf');
        break;
      case 'all-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
            $query->whereDate('invoice_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('invoice_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->where('buyer_id', $this->company()->id)
          ->orderBy('created_at', 'DESC')
          ->get();

        $headers = [
          'Anchor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Status',
          'Financing Status',
          'Discount',
          'Disbursement Date',
          'Disbursed Amount',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Anchor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] =
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount'] =
            $invoice->paymentRequests->count() > 0 ? $invoice->paymentRequests->first()->discount : '-';
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount ? $invoice->disbursed_amount : 0;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Invoices_Report_' . $date . '.pdf');
        break;
      case 'paid-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            if ($financing_status == 'past_due') {
              $query->whereDate('due_date', '<', now()->format('Y-m-d'))->whereIn('financing_status', ['disbursed']);
            } else {
              $query->where('financing_status', $financing_status);
            }
          })
          ->when(!$financing_status || $financing_status == '', function ($query) use ($financing_status) {
            $query->whereIn('financing_status', ['disbursed', 'closed']);
          })
          ->where('buyer_id', $this->company()->id)
          ->orderBy('updated_at', 'DESC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Status',
          'Financing Status',
          'Discount',
          'Disbursement Date',
          'Disbursed Amount',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount'] =
            $invoice->paymentRequests->count() > 0
              ? number_format($invoice->paymentRequests->first()->discount, 2)
              : '-';
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount ? number_format($invoice->disbursed_amount) : 0;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Paid Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Paid_Invoices_Report_' . $date . '.pdf');
        break;
      case 'overdue-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $invoices = Invoice::factoring()
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
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
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->where('buyer_id', $this->company()->id)
          ->orderBy('updated_at', 'DESC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Disbursement Date',
          'Overdue Amount',
          'Days Past Due',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Overdue Amount'] = number_format($invoice->balance, 2);
          $data[$key]['Days Past Due'] = $invoice->days_past_due;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Overdue Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Overdue_Invoices_Report_' . $date . '.pdf');
        break;
      case 'vendor-analysis':
        $headers = ['Anchor', 'Limit', 'Utilized Limit', 'Available Limit', 'Pipeline Amount'];
        $data = [];

        foreach ($this->company()->programs as $key => $program) {
          if (
            $program->programType->name == Program::VENDOR_FINANCING &&
            ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
              $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
          ) {
            $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
              ->where('buyer_id', $this->company()->id)
              ->first();
            $utilized_amount = $program->anchor->utilizedAmount($program);
            $data[$key]['Anchor'] = $program->anchor->name;
            $data[$key]['limit'] = $vendor_configuration->sanctioned_limit;
            $data[$key]['Utilized Amount'] = $utilized_amount;
            $data[$key]['Available Amount'] = $vendor_configuration->sanctioned_limit - $utilized_amount;
            $data[$key]['Pipeline Amount'] = $program->anchor->pipelineAmount($program);
          }
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Anchor Analysis Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Vendor_Analysis_Report_' . $date . '.pdf');
      case 'closed-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $invoices = Invoice::factoring()
          ->where('financing_status', 'closed')
          ->with('company', 'program.anchor', 'paymentRequests', 'invoiceItems')
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
              });
            });
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('invoice_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('invoice_date', '<=', $to_date);
          })
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->where('buyer_id', $this->company()->id)
          ->orderBy('updated_at', 'ASC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Disbursement Date',
          'Disbursed Amount',
          'Date of Closure',
          'Transaction Reference No.',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->program->anchor->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = $invoice->invoice_total_amount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount ? $invoice->disbursed_amount : 0;
          $data[$key]['Date of Closure'] =
            $invoice->financing_status === 'closed' ? Carbon::parse($invoice->closure_date)->format('d M Y') : '-';
          $data[$key]['Transaction Reference No.'] = $invoice->closure_transaction_reference ?? '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Invoices_Report_' . $date . '.pdf');
        break;
      case 'maturing-invoices':
        $anchor = $request->query('anchor');

        $payment_accounts = ProgramVendorConfiguration::where('buyer_id', $this->company()->id)
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
              });
            });
          })
          ->join('companies', 'companies.id', '=', 'program_vendor_configurations.company_id')
          ->orderBy('companies.name', 'ASC')
          ->get();

        $headers = [
          'Vendor',
          'Total Due',
          'Due <0 Days',
          'Due 1-7 Days',
          'Due 8-14 Days',
          'Due 15-21 Days',
          'Due 22-30 Days',
          'Due 31-45 Days',
          'Due 46-60 Days',
          'Due 61-75 Days',
          'Due 76-90 Days',
        ];
        $data = [];

        foreach ($payment_accounts as $key => $payment_account) {
          $total_due = 0;
          $due_today = 0;
          $due_seven_days = 0;
          $due_fourteen_days = 0;
          $due_twenty_one_days = 0;
          $due_thirty_days = 0;
          $due_fourty_five_days = 0;
          $due_sixty_days = 0;
          $due_seventy_five_days = 0;
          $due_ninety_days = 0;

          $total_due = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->sum('calculated_total_amount');

          $due_today = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereDate('due_date', '<=', now()->format('Y-m-d'))
            ->sum('calculated_total_amount');

          $due_seven_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDay()
                ->format('Y-m-d'),
              now()
                ->addDays(7)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_fourteen_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(8)
                ->format('Y-m-d'),
              now()
                ->addDays(14)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_twenty_one_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(15)
                ->format('Y-m-d'),
              now()
                ->addDays(21)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_thirty_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(22)
                ->format('Y-m-d'),
              now()
                ->addDays(30)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_fourty_five_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(31)
                ->format('Y-m-d'),
              now()
                ->addDays(45)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_sixty_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(46)
                ->format('Y-m-d'),
              now()
                ->addDays(60)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_seventy_five_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(61)
                ->format('Y-m-d'),
              now()
                ->addDays(75)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $due_ninety_days = Invoice::factoring()
            ->where('buyer_id', $payment_account->buyer_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereBetween('due_date', [
              now()
                ->addDays(76)
                ->format('Y-m-d'),
              now()
                ->addDays(90)
                ->format('Y-m-d'),
            ])
            ->sum('calculated_total_amount');

          $data[$key]['Vendor'] =
            $payment_account->program->anchor->name . ' (' . $payment_account->payment_account_number . ')';
          $data[$key]['Total Due'] = number_format($total_due, 2);
          $data[$key]['Due <0 Days'] = number_format($due_today, 2);
          $data[$key]['Due 1-7 Days'] = number_format($due_seven_days, 2);
          $data[$key]['Due 8-14 Days'] = number_format($due_fourteen_days, 2);
          $data[$key]['Due 15-21 Days'] = number_format($due_twenty_one_days, 2);
          $data[$key]['Due 22-30 Days'] = number_format($due_thirty_days, 2);
          $data[$key]['Due 31-45 Days'] = number_format($due_fourty_five_days, 2);
          $data[$key]['Due 46-60 Days'] = number_format($due_sixty_days, 2);
          $data[$key]['Due 61-75 Days'] = number_format($due_seventy_five_days, 2);
          $data[$key]['Due 76-90 Days'] = number_format($due_ninety_days, 2);
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Maturing Payments',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Maturing_payments_' . $date . '.pdf');
        break;
      case 'dealer-all-invoices-report':
        $current_company = auth()
          ->user()
          ->activeBuyerCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::dealerFinancing()
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
            $query->whereDate('invoice_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('invoice_date', '<=', $to_date);
          })
          ->when($status && $status != '', function ($query) use ($status) {
            $query->where('stage', $status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->orderBy('created_at', 'DESC')
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
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount ? $invoice->disbursed_amount : 0;
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount Value'] =
            $invoice->paymentRequests->count() > 0 ? $invoice->paymentRequests->first()->discount : '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Dealer Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Dealer_Invoices_Report_' . $date . '.pdf');
        break;
      case 'dealer-programs-report':
        $current_company = auth()
          ->user()
          ->activeBuyerCompany()
          ->first();

        $anchor = $request->query('anchor');
        $loan_account = $request->query('loan_account');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::with('program.anchor')
            ->whereHas('program', function ($query) {
              $query->whereHas('programType', function ($query) {
                $query->where('name', Program::DEALER_FINANCING);
              });
            })
            ->where('company_id', $current_company->company_id)
            ->when($anchor && $anchor != '', function ($query) use ($anchor) {
              $query->whereHas('program', function ($query) use ($anchor) {
                $query->whereHas('anchor', function ($query) use ($anchor) {
                  $query->where('name', 'LIKE', '%' . $anchor . '%');
                });
              });
            })
            ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
              $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
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

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Dealer Programs Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Dealer_Programs_Report_' . $date . '.pdf');

        break;
      case 'dealer-payments-report':
        $invoice_number = $request->query('invoice_number');
        $po = $request->query('po');
        $invoice_status = $request->query('invoice_status');
        $financing_status = $request->query('financing_status');

        $current_company = auth()
          ->user()
          ->activeBuyerCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $invoice_ids = Invoice::dealerFinancing()
          ->where('company_id', $current_company->company_id)
          ->where('disbursement_date', '!=', null)
          ->get()
          ->pluck('id');

        $payments = PaymentRequest::with('invoice.program.anchor', 'invoice.purchaseOrder')
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->whereHas('invoice', function ($query) use ($invoice_number) {
              $query->where('invoice_number', '%' . $invoice_number . '%');
            });
          })
          ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
            $query->whereHas('invoice', function ($query) use ($invoice_status) {
              $query->where('status', $invoice_status);
            });
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->whereHas('invoice', function ($query) use ($financing_status) {
              $query->where('financing_status', $financing_status);
            });
          })
          ->when($po && $po != '', function ($query) use ($po) {
            $query->whereHas('invoice', function ($query) use ($po) {
              $query->whereHas('purchae_order', function ($query) use ($po) {
                $query->where('purchase_order_number', '%' . $po . '%');
              });
            });
          })
          ->whereIn('invoice_id', $invoice_ids)
          ->orderBy('updated_at', 'DESC')
          ->get();

        $headers = [
          'Invoice Number',
          'P.O Number',
          'Anchor',
          'Invoice Amount',
          'Discount Amount',
          'Invoice Date',
          'Due Date',
          'Invoice Status',
          'Financing Status',
          'Disbursement Date',
          'Disbursed Amount',
        ];
        $data = [];

        foreach ($payments as $key => $payment) {
          $data[$key]['Invoice Number'] = $payment->invoice->invoice_number;
          $data[$key]['P.O Number'] = $payment->invoice->purchaseOrder
            ? $payment->invoice->purchaseOrder->purchase_order_number
            : '-';
          $data[$key]['Anchor'] = $payment->invoice->program->anchor->name;
          $data[$key]['Invoice Amount'] = number_format($payment->invoice->invoice_total_amount, 2);
          $data[$key]['Discount Amount'] = number_format($payment->discount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($payment->invoice->invoice_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($payment->invoice->due_date)->format('d M Y');
          $data[$key]['Invoice Status'] = Str::title($payment->invoice->status);
          $data[$key]['Financing Status'] = Str::title($payment->invoice->financing_status);
          $data[$key]['Disbursement Date'] = Carbon::parse($payment->invoice->disbursement_date)->format('d M Y');
          $data[$key]['Dibsursed Amount'] = number_format($payment->amount, 2);
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Dealer Payments Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Dealer_Payments_Report_' . $date . '.pdf');

        break;
      default:
        break;
    }
  }
}
