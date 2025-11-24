<?php

namespace App\Http\Controllers\Anchor;

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
use Illuminate\Support\Collection;
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
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->get()
      ->pluck('program_id');

    $programs = Program::whereHas(
      'programCode',
      fn($query) => $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE)
    )
      ->whereIn('id', $programs_ids)
      ->get();

    $total_program_limit = 0;
    $utilized_amount = 0;
    $pipeline_amount = 0;
    $available_amount = 0;

    foreach ($programs as $program) {
      $total_program_limit += (int) $program->program_limit;
      foreach ($program->getVendors() as $vendor) {
        $utilized_amount += (int) $vendor->utilizedAmount($program);
        $pipeline_amount += (int) $vendor->pipelineAmount($program);
      }
    }

    $available_amount = $total_program_limit - $utilized_amount - $pipeline_amount;

    return view('content.anchor.reverse-factoring.reports', [
      'total_program_limit' => $total_program_limit,
      'available_amount' => $available_amount,
      'utilized_amount' => $utilized_amount,
      'default_currency' => $company->default_currency,
    ]);
  }

  public function data(Request $request)
  {
    $timeline = $request->query('timeline');

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
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->get()
      ->pluck('program_id');

    $invoices = Invoice::vendorFinancing()
      ->whereIn('program_id', $programs_ids)
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
        ->whereIn('program_id', $programs_ids)
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

    if ($timeline && $timeline != '') {
      switch ($timeline) {
        case 'past_ten_years':
          $months = [];
          for ($i = 9; $i >= 0; $i--) {
            $month = Carbon::today()
              ->startOfYear()
              ->subYear($i);
            array_push($months, $month);
          }

          // Format months
          $months_formatted = [];
          foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('Y'));
          }

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
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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
              ->whereIn('program_id', $programs_ids)
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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

          break;
        case 'past_five_years':
          $months = [];
          for ($i = 4; $i >= 0; $i--) {
            $month = Carbon::today()
              ->startOfYear()
              ->subYear($i);
            array_push($months, $month);
          }

          // Format months
          $months_formatted = [];
          foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('Y'));
          }

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
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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
              ->whereIn('program_id', $programs_ids)
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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

          break;
        case 'past_three_years':
          $months = [];
          for ($i = 2; $i >= 0; $i--) {
            $month = Carbon::today()
              ->startOfYear()
              ->subYear($i);
            array_push($months, $month);
          }

          // Format months
          $months_formatted = [];
          foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('Y'));
          }

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
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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
              ->whereIn('program_id', $programs_ids)
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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

          break;
        case 'past_six_months':
          $months = [];
          for ($i = 5; $i >= 0; $i--) {
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
              ->whereIn('program_id', $programs_ids)
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

          break;
        case 'past_three_months':
          $months = [];
          for ($i = 2; $i >= 0; $i--) {
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
              ->whereIn('program_id', $programs_ids)
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

          break;
        default:
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
              ->whereIn('program_id', $programs_ids)
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
          break;
      }
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
      case 'discountings':
        return $this->discountings($request);
        break;
      default:
        # code...
        break;
    }
  }

  public function invoiceAnalysisView()
  {
    return view('content.anchor.reverse-factoring.reports.invoice-analysis');
  }

  public function maturingInvoicesView()
  {
    return view('content.anchor.reverse-factoring.reports.maturing-invoices');
  }

  public function allInvoicesView()
  {
    return view('content.anchor.reverse-factoring.reports.all-invoices');
  }

  public function paidInvoicesView()
  {
    return view('content.anchor.reverse-factoring.reports.paid-invoices');
  }

  public function overdueInvoicesView()
  {
    return view('content.anchor.reverse-factoring.reports.overdue-invoices');
  }

  public function closedInvoicesView()
  {
    return view('content.anchor.reverse-factoring.reports.closed-invoices');
  }

  public function vendorAnalysisView()
  {
    return view('content.anchor.reverse-factoring.reports.vendor-analysis');
  }

  public function discountingsView()
  {
    return view('content.anchor.reverse-factoring.reports.discountings');
  }

  private function company(): Company
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    return $company;
  }

  private function invoiceAnalysis(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $disbursement_date = $request->query('disbursement_date');
    $financing_status = $request->query('financing_status');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $invoices = InvoiceResource::collection(
      Invoice::vendorFinancing()
        ->whereIn('program_id', $programs_ids)
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
        ->when($disbursement_date && $disbursement_date != '', function ($query) use ($disbursement_date) {
          $query->whereDate('disbursement_date', Carbon::parse($disbursement_date));
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
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $disbursement_date = $request->query('disbursement_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::whereHas('program', function ($query) {
      $query->whereHas('programCode', function ($query) {
        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
      });
    })
      ->where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $invoices = Invoice::vendorFinancing()
      ->with('company', 'program', 'paymentRequests', 'invoiceItems')
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
        $query->where('financing_status', $financing_status);
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', Carbon::parse($from_date));
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', Carbon::parse($to_date));
      })
      ->when($disbursement_date && $disbursement_date != '', function ($query) use ($disbursement_date) {
        $query->whereDate('disbursement_date', Carbon::parse($disbursement_date));
      })
      ->whereIn('program_id', $programs_ids)
      ->latest()
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    return $invoices;
  }

  private function vendorAnalysis(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $per_page = $request->query('per_page');
    $vendor_search = $request->query('vendors');

    $vendors = [];

    // Get vendors
    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
      ) {
        $program_vendors = $program->getVendors();

        foreach ($program_vendors as $vendor) {
          $vendor = Company::where('id', $vendor->id)->first();
          $vendor['utilized_amount'] = $vendor->utilizedAmount($program);
          $vendor['pipeline_amount'] = $vendor->pipelineAmount($program);
          $vendor['configuration'] = ProgramVendorConfiguration::where('program_id', $program->id)
            ->where('company_id', $vendor->id)
            ->first();
          array_push($vendors, $vendor);
        }
      }
    }

    collect($vendors)
      ->when($vendor_search && $vendor_search != '', function (Collection $collection) use ($vendor_search) {
        return similar_text($collection->name, $vendor_search);
      })
      ->toArray();

    return Helpers::paginate($vendors, $per_page);
  }

  private function maturingInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $this->company()->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->pluck('program_id');

    return OdAccountMaturingInvoices::collection(
      ProgramVendorConfiguration::whereIn('program_id', $programs_ids)
        ->when($vendor && $vendor != '', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
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
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice_no');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbrsement_date = $request->query('to_disbursement_date');
    $financing_status = $request->query('financing_status');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $this->company()->id)
      ->pluck('program_id');

    $invoices = Invoice::vendorFinancing()
      ->with([
        'company' => fn($query) => $query->select('id', 'name'),
        'paymentRequests',
        'invoiceItems',
      ])
      ->whereIn('program_id', $programs_ids)
      ->when(!$financing_status || $financing_status == '', function ($query) {
        $query->whereIn('financing_status', ['disbursed', 'closed']);
      })
      ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
        if ($financing_status == 'past_due') {
          $query->whereDate('due_date', '<', now()->format('Y-m-d'))->whereIn('financing_status', ['disbursed']);
        } else {
          $query->where('financing_status', $financing_status);
        }
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', $to_date);
      })
      ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use ($from_disbursement_date) {
        $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
      })
      ->when($to_disbrsement_date && $to_disbrsement_date != '', function ($query) use ($to_disbrsement_date) {
        $query->whereDate('disbursement_date', '<=', $to_disbrsement_date);
      })
      ->orderBy('updated_at', 'DESC')
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    return $invoices;
  }

  private function overdueInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice_no');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $this->company()->id)
      ->pluck('program_id');

    $invoices = Invoice::vendorFinancing()
      ->with([
        'company' => fn($query) => $query->select('id', 'name'),
        'paymentRequests',
        'invoiceItems',
      ])
      ->whereIn('program_id', $programs_ids)
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', $to_date);
      })
      ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use ($from_disbursement_date) {
        $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
      })
      ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
        $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
      })
      ->orderBy('due_date', 'DESC')
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    return $invoices;
  }

  private function closedInvoices(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::whereHas('program', function ($query) {
      $query->whereHas('programCode', function ($query) {
        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
      });
    })
      ->where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $invoices = Invoice::vendorFinancing()
      ->where('financing_status', 'closed')
      ->with('company', 'program', 'paymentRequests', 'invoiceItems')
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
        $query->where('financing_status', $financing_status);
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', Carbon::parse($from_date));
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', Carbon::parse($to_date));
      })
      ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use ($from_disbursement_date) {
        $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
      })
      ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
        $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
      })
      ->whereIn('program_id', $programs_ids)
      ->orderBy('due_date', 'ASC')
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    return $invoices;
  }

  private function discountings(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $this->company()->id)
      ->pluck('program_id');

    $invoices = Invoice::vendorFinancing()
      ->with([
        'company' => fn($query) => $query->select('id', 'name'),
        'paymentRequests.cbsTransactions',
        'invoiceItems',
      ])
      ->whereIn('program_id', $programs_ids)
      ->whereHas('paymentRequests', function ($query) {
        $query->whereHas('cbsTransactions');
      })
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('disbursement_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('disbursement_date', '<=', $to_date);
      })
      ->latest()
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    return $invoices;
  }

  public function factoringIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->get()
      ->pluck('program_id');

    $programs = Program::whereHas(
      'programCode',
      fn($query) => $query->where('name', 'Factoring With Recourse')->orWhere('name', 'Factoring Without Recourse')
    )
      ->whereIn('id', $programs_ids)
      ->get();

    $total_program_limit = 0;
    $utilized_amount = 0;
    $available_amount = 0;

    foreach ($programs as $program) {
      $total_program_limit += $program->program_limit;
      $utilized_amount += $company->utilizedAmount($program);
    }

    $available_amount = $total_program_limit - $utilized_amount;

    return view('content.anchor.factoring.invoices-reports', [
      'total_program_limit' => $total_program_limit,
      'available_amount' => $available_amount,
      'utilized_amount' => $utilized_amount,
      'default_currency' => $company->default_currency,
    ]);
  }

  public function factoringData(Request $request)
  {
    $timeline = $request->query('timeline');

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
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::factoring()
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

      $invoice_data = Invoice::factoring()
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

    if ($timeline && $timeline != '') {
      switch ($timeline) {
        case 'past_ten_years':
          $months = [];
          for ($i = 9; $i >= 0; $i--) {
            $month = Carbon::today()
              ->startOfYear()
              ->subYear($i);
            array_push($months, $month);
          }

          // Format months
          $months_formatted = [];
          foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('Y'));
          }

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
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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
              ->where('company_id', $company->id)
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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

          break;
        case 'past_five_years':
          $months = [];
          for ($i = 4; $i >= 0; $i--) {
            $month = Carbon::today()
              ->startOfYear()
              ->subYear($i);
            array_push($months, $month);
          }

          // Format months
          $months_formatted = [];
          foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('Y'));
          }

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
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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
              ->where('company_id', $company->id)
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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

          break;
        case 'past_three_years':
          $months = [];
          for ($i = 2; $i >= 0; $i--) {
            $month = Carbon::today()
              ->startOfYear()
              ->subYear($i);
            array_push($months, $month);
          }

          // Format months
          $months_formatted = [];
          foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('Y'));
          }

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
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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
              ->where('company_id', $company->id)
              ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
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

          break;
        case 'past_six_months':
          $months = [];
          for ($i = 5; $i >= 0; $i--) {
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

          break;
        case 'past_three_months':
          $months = [];
          for ($i = 2; $i >= 0; $i--) {
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

          break;
        default:
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
          break;
      }
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

  public function factoringAllInvoicesReportView()
  {
    return view('content.anchor.factoring.reports.all-invoices');
  }

  public function factoringProgramsReportView()
  {
    return view('content.anchor.factoring.reports.programs');
  }

  public function factoringDealerProgramsReportView()
  {
    return view('content.anchor.factoring.reports.dealer-programs');
  }

  public function factoringPaymentsReportView()
  {
    return view('content.anchor.factoring.reports.payments');
  }

  public function factoringAllInvoicesReport(Request $request)
  {
    $buyer = $request->query('buyer');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $invoices = Invoice::factoring()
      ->where('company_id', $current_company->company_id)
      ->with([
        'program.anchor',
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'invoiceDiscounts',
        'paymentRequests.paymentAccounts',
        'buyer',
      ])
      ->when($buyer && $buyer != '', function ($query) use ($buyer) {
        $query->whereHas('buyer', function ($query) use ($buyer) {
          $query->where('name', 'LIKE', '%' . $buyer . '%');
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

  public function factoringProgramsReport(Request $request)
  {
    $buyer = $request->query('buyer');
    $loan_account = $request->query('loan_account');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('buyer', 'program')
        ->whereHas('program', function ($query) {
          $query->whereHas('programCode', function ($query) {
            $query
              ->where('name', Program::FACTORING_WITH_RECOURSE)
              ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
          });
        })
        ->where('company_id', $current_company->company_id)
        ->when($buyer && $buyer != '', function ($query) use ($buyer) {
          $query->whereHas('buyer', function ($query) use ($buyer) {
            $query->where('name', 'LIKE', '%' . $buyer . '%');
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

  public function factoringDealerProgramsReport(Request $request)
  {
    $dealer = $request->query('dealer');
    $loan_account = $request->query('loan_account');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('company', 'program')
        ->whereHas('program', function ($query) use ($current_company) {
          $query
            ->whereHas('anchor', function ($query) use ($current_company) {
              $query->where('companies.id', $current_company->company_id);
            })
            ->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
        })
        ->when($dealer && $dealer != '', function ($query) use ($dealer) {
          $query->whereHas('company', function ($query) use ($dealer) {
            $query->where('name', 'LIKE', '%' . $dealer . '%');
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

  public function factoringPaymentsReport(Request $request)
  {
    $invoice_number = $request->query('invoice_number');
    $buyer = $request->query('buyer');
    $po = $request->query('po');
    $invoice_status = $request->query('invoice_status');
    $financing_status = $request->query('financing_status');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_disbursement_date = $request->query('from_disbursement_date');
    $to_disbursement_date = $request->query('to_disbursement_date');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $payments = Invoice::factoring()
      ->where('company_id', $current_company->company_id)
      ->where('disbursement_date', '!=', null)
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($buyer && $buyer != '', function ($query) use ($buyer) {
        $query->whereHas('buyer', function ($query) use ($buyer) {
          $query->where('name', 'LIKE', '%' . $buyer . '%');
        });
      })
      ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
        $query->where('status', $invoice_status);
      })
      ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
        $query->where('financing_status', $financing_status);
      })
      ->when($po && $po != '', function ($query) use ($po) {
        $query->whereHas('purchae_order', function ($query) use ($po) {
          $query->where('purchase_order_number', 'LIKE', '%' . $po . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', $to_date);
      })
      ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use ($from_disbursement_date) {
        $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
      })
      ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
        $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
      })
      ->orderBy('due_date', 'DESC')
      ->paginate($per_page);

    $payments = InvoiceResource::collection($payments)
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
      case 'invoice-analysis':
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $disbursement_date = $request->query('disbursement_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = InvoiceResource::collection(
          Invoice::vendorFinancing()
            ->whereIn('program_id', $programs_ids)
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
            ->when($disbursement_date && $disbursement_date != '', function ($query) use ($disbursement_date) {
              $query->whereDate('disbursement_date', Carbon::parse($disbursement_date));
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

        foreach ($invoices as $key => $payment) {
          $data[$key]['Invoice Number'] = $payment->invoice_number;
          $data[$key]['Invoice Date'] = Carbon::parse($payment->invoice_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $payment->disbursement_date
            ? Carbon::parse($payment->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Invoice Due Date'] = Carbon::parse($payment->due_date)->format('d M Y');
          $data[$key]['Financing Status'] = Str::title($payment->financing_status);
          $data[$key]['Anchor'] = $payment->program->anchor->name;
          $data[$key]['Invoice Amount'] = $payment->invoice_total_amount;
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

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->whereIn('program_id', $programs_ids)
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
            $query
              ->when($status == 'overdue', function ($query) {
                $query->whereDate('due_date', '<', now()->format('Y-m-d'));
              })
              ->when($status != 'overdue', function ($query) use ($status) {
                $query->where('status', $status);
              });
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->latest()
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
          $data[$key]['Vendor'] = $invoice->company->name;
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
      case 'overdue-invoices':
        $vendor = $request->query('vendor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->whereIn('program_id', $programs_ids)
          ->where('financing_status', 'disbursed')
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '%');
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
          'Overdue Amount',
          'Days Past Due',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = $invoice->invoice_total_amount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Overdue Amount'] = number_format($invoice->balance, 2);
          $data[$key]['Days Past Due'] = $invoice->days_past_due;
        }

        Excel::store(new Report($headers, $data), 'Overdue_Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Overdue_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'paid-invoices':
        $vendor = $request->query('vendor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->whereIn('program_id', $programs_ids)
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
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
          $data[$key]['Vendor'] = $invoice->company->name;
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
      case 'vendor-analysis':
        $headers = ['Vendor', 'Limit', 'Utilized Limit', 'Available Limit', 'Pipeline Amount'];
        $data = [];
        $current_company = auth()
          ->user()
          ->activeAnchorCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $vendor_search = $request->query('vendors');

        $vendors = [];

        // Get vendors
        foreach ($company->programs as $program) {
          if (
            $program->programType->name == Program::VENDOR_FINANCING &&
            $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
          ) {
            $program_vendors = $program->getVendors();

            foreach ($program_vendors as $key => $vendor) {
              $vendor = Company::where('id', $vendor->id)->first();
              $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
                ->where('company_id', $vendor->id)
                ->first();
              $utilized_amount = $vendor->utilizedAmount($program);
              $data[$key]['Vendor'] = $vendor->name;
              $data[$key]['limit'] = $vendor_configuration->sanctioned_limit;
              $data[$key]['Utilized Amount'] = $utilized_amount;
              $data[$key]['Available Amount'] = $vendor_configuration->sanctioned_limit - $utilized_amount;
              $data[$key]['Pipeline Amount'] = $vendor->pipelineAmount($program);
            }
          }
        }

        collect($vendors)
          ->when($vendor_search && $vendor_search != '', function (Collection $collection) use ($vendor_search) {
            return similar_text($collection->name, $vendor_search);
          })
          ->toArray();

        Excel::store(new Report($headers, $data), 'Vendors_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Vendors_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'discountings':
        $vendor = $request->query('vendor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->with([
            'company' => fn($query) => $query->select('id', 'name'),
            'paymentRequests.cbsTransactions',
            'invoiceItems',
          ])
          ->whereIn('program_id', $programs_ids)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereHas('cbsTransactions');
          })
          ->whereIn('financing_status', ['disbursed', 'closed'])
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '%');
            });
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('disbursement_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('disbursement_date', '<=', $to_date);
          })
          ->latest()
          ->get();

        $headers = ['Vendor', 'Invoice Number', 'Amount', 'Disbursement Date', 'Transaction Ref'];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Disbursement Date'] = Carbon::parse($invoice->disbursement_date)->format('d M Y');
          $data[$key]['Transaction Ref'] = $invoice->paymentRequests
            ->first()
            ->cbsTransactions->where('transaction_type', 'Payment Disbursement')
            ->first()->transaction_reference;
        }

        Excel::store(new Report($headers, $data), 'Discountings_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Discountings_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'closed-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->where('financing_status', 'closed')
          ->whereIn('program_id', $programs_ids)
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
            $query
              ->when($status == 'overdue', function ($query) {
                $query->whereDate('due_date', '<', now()->format('Y-m-d'));
              })
              ->when($status != 'overdue', function ($query) use ($status) {
                $query->where('status', $status);
              });
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->orderBy('due_date', 'ASC')
          ->get();

        $headers = [
          'Vendor',
          'Invoice Number',
          'Invoice Amount',
          'Invoice Date',
          'Invoice Due Date',
          'Status',
          'Disbursement Date',
          'Date of Closure',
          'Transaction Reference No.',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] =
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Date of Closure'] =
            $invoice->financing_status === 'closed' ? Carbon::parse($invoice->closure_date)->format('d M Y') : '-';
          $data[$key]['Transaction Reference No.'] = $invoice->closure_transaction_reference ?? '-';
        }

        Excel::store(new Report($headers, $data), 'Closed_Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Closed_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'maturing-invoices':
        $vendor = $request->query('vendor');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->whereHas('program', function ($query) {
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
          })
          ->pluck('program_id');

        $payment_accounts = ProgramVendorConfiguration::whereIn('program_id', $programs_ids)
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('companies.name', 'LIKE', '%' . $vendor . '%');
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
          $due_today = 0;
          $due_seven_days = 0;
          $due_fourteen_days = 0;
          $due_twenty_one_days = 0;
          $due_twenty_eight_days = 0;
          $due_thirty_five_days = 0;
          $due_fourty_two_days = 0;
          $due_fourty_nine_days = 0;

          $total_due = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->sum('calculated_total_amount');

          $due_today = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereDate('due_date', '<=', now()->format('Y-m-d'))
            ->sum('calculated_total_amount');

          $due_seven_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_fourteen_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_twenty_one_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_thirty_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_fourty_five_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_sixty_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_seventy_five_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_ninety_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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
            $payment_account->company->name . ' (' . $payment_account->payment_account_number . ')';
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
      case 'factoring-all-invoices-report':
        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $buyer = $request->query('buyer');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->with([
            'program.anchor',
            'invoiceItems',
            'invoiceFees',
            'invoiceTaxes',
            'invoiceDiscounts',
            'paymentRequests.paymentAccounts',
            'buyer',
          ])
          ->where('company_id', $current_company->company_id)
          ->when($buyer && $buyer != '', function ($query) use ($buyer) {
            $query->whereHas('buyer', function ($query) use ($buyer) {
              $query->where('name', 'LIKE', '%' . $buyer . '%');
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
          ->latest()
          ->get();

        $headers = [
          'Invoice Number',
          'Buyer',
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
          $data[$key]['Buyer'] = $invoice->buyer->name;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
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
            $invoice->paymentRequests->count() > 0
              ? number_format($invoice->paymentRequests->first()->discount, 2)
              : '-';
        }

        Excel::store(new Report($headers, $data), 'Invoices_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'factoring-program-report':
        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $buyer = $request->query('buyer');
        $loan_account = $request->query('loan_account');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::with('buyer', 'program')
            ->whereHas('program', function ($query) {
              $query->whereHas('programCode', function ($query) {
                $query
                  ->where('name', Program::FACTORING_WITH_RECOURSE)
                  ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
              });
            })
            ->where('company_id', $current_company->company_id)
            ->when($buyer && $buyer != '', function ($query) use ($buyer) {
              $query->whereHas('buyer', function ($query) use ($buyer) {
                $query->where('name', 'LIKE', '%' . $buyer . '%');
              });
            })
            ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
              $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
            })
            ->get()
        );

        $headers = [
          'Loan Account No',
          'Buyer',
          'Financing Limit',
          'Utilized Limit',
          'Pipeline Amount',
          'Available Limit',
          'Limit Review Date',
        ];
        $data = [];

        foreach ($programs as $key => $program) {
          $data[$key]['Loan Account No'] = $program->payment_account_number;
          $data[$key]['Buyer'] = $program->buyer->name;
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
      case 'factoring-dealer-program-report':
        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $dealer = $request->query('dealer');
        $loan_account = $request->query('loan_account');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::with('buyer', 'program')
            ->whereHas('program', function ($query) use ($current_company) {
              $query
                ->whereHas('anchor', function ($query) use ($current_company) {
                  $query->where('companies.id', $current_company->company_id);
                })
                ->whereHas('programType', function ($query) {
                  $query->where('name', Program::DEALER_FINANCING);
                });
            })
            ->when($dealer && $dealer != '', function ($query) use ($dealer) {
              $query->whereHas('company', function ($query) use ($dealer) {
                $query->where('name', 'LIKE', '%' . $dealer . '%');
              });
            })
            ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
              $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
            })
            ->get()
        );

        $headers = [
          'Loan Account No',
          'Dealer',
          'Financing Limit',
          'Utilized Limit',
          'Pipeline Amount',
          'Available Limit',
          'Limit Review Date',
        ];
        $data = [];

        foreach ($programs as $key => $program) {
          $data[$key]['Loan Account No'] = $program->payment_account_number;
          $data[$key]['Dealer'] = $program->company_name;
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
      case 'factoring-payments-report':
        $invoice_number = $request->query('invoice_number');
        $buyer = $request->query('buyer');
        $po = $request->query('po');
        $invoice_status = $request->query('invoice_status');
        $financing_status = $request->query('financing_status');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $payments = Invoice::factoring()
          ->where('company_id', $current_company->company_id)
          ->where('disbursement_date', '!=', null)
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($buyer && $buyer != '', function ($query) use ($buyer) {
            $query->whereHas('buyer', function ($query) use ($buyer) {
              $query->where('name', 'LIKE', '%' . $buyer . '%');
            });
          })
          ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
            $query->where('status', $invoice_status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->when($po && $po != '', function ($query) use ($po) {
            $query->whereHas('purchae_order', function ($query) use ($po) {
              $query->where('purchase_order_number', 'LIKE', '%' . $po . '%');
            });
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
          ->orderBy('due_date', 'DESC')
          ->get();

        $headers = [
          'System ID',
          'Invoice Number',
          'Buyer',
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
            ->where('buyer_id', $invoice->buyer_id)
            ->first();
          $data[$key]['System ID'] = $invoice->id;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Buyer'] = $invoice->buyer->name;
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
        $disbursement_date = $request->query('disbrsement_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = InvoiceResource::collection(
          Invoice::vendorFinancing()
            ->whereIn('program_id', $programs_ids)
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
            ->when($disbursement_date && $disbursement_date != '', function ($query) use ($disbursement_date) {
              $query->whereDate('disbursement_date', Carbon::parse($disbursement_date));
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
          'Discount',
        ];
        $data = [];

        foreach ($invoices as $key => $payment) {
          $data[$key]['Invoice Number'] = $payment->invoice_number;
          $data[$key]['Invoice Date'] = Carbon::parse($payment->invoice_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $payment->disbursement_date
            ? Carbon::parse($payment->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Invoice Due Date'] = Carbon::parse($payment->due_date)->format('d M Y');
          $data[$key]['Financing Status'] = Str::title($payment->financing_status);
          $data[$key]['Anchor'] = $payment->program->anchor->name;
          $data[$key]['Invoice Amount'] = $payment->invoice_total_amount;
          $data[$key]['Discount'] =
            $payment->paymentRequests->count() > 0 ? $payment->paymentRequests->first()->discount : '-';
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

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->whereIn('program_id', $programs_ids)
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
            $query
              ->when($status == 'overdue', function ($query) {
                $query->whereDate('due_date', '<', now()->format('Y-m-d'));
              })
              ->when($status != 'overdue', function ($query) use ($status) {
                $query->where('status', $status);
              });
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->latest()
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
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount'] =
            ($invoice->financing_status == 'disbursed' || $invoice->financing_status == 'closed') &&
            $invoice->paymentRequests->count() > 0
              ? $invoice->paymentRequests->first()->discount
              : '-';
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount
            ? number_format($invoice->disbursed_amount, 2)
            : 0;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Invoices_Report_' . $date . '.pdf');
        break;
      case 'paid-invoices':
        $vendor = $request->query('vendor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->whereIn('program_id', $programs_ids)
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
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
          'Date of Closure',
        ];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount'] =
            $invoice->paymentRequests->count() > 0 ? $invoice->paymentRequests->first()->discount : '-';
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount
            ? number_format($invoice->disbursed_amount, 2)
            : 0;
          $data[$key]['Date of Closure'] =
            $invoice->financing_status === 'closed' ? Carbon::parse($invoice->closure_date)->format('d M Y') : '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Paid Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Paid_Invoices_Report_' . $date . '.pdf');
        break;
      case 'overdue-invoices':
        $vendor = $request->query('vendor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->whereIn('program_id', $programs_ids)
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('due_date', '>=', $from_date);
          })
          ->when($from_disbursement_date && $from_disbursement_date != '', function ($query) use (
            $from_disbursement_date
          ) {
            $query->whereDate('disbursement_date', '>=', $from_disbursement_date);
          })
          ->when($to_disbursement_date && $to_disbursement_date != '', function ($query) use ($to_disbursement_date) {
            $query->whereDate('disbursement_date', '<=', $to_disbursement_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('due_date', '<=', $to_date);
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
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
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Status'] = Str::title($invoice->status);
          $data[$key]['Financing Status'] = Str::title($invoice->financing_status);
          $data[$key]['Discount'] =
            $invoice->paymentRequests->count() > 0 ? $invoice->paymentRequests->first()->discount : '-';
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount
            ? number_format($invoice->disbursed_amount, 2)
            : 0;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Overdue Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Overdue_Invoices_Report_' . $date . '.pdf');
        break;
      case 'maturing-invoices':
        $vendor = $request->query('vendor');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->whereHas('program', function ($query) {
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
          })
          ->pluck('program_id');

        $payment_accounts = ProgramVendorConfiguration::whereIn('program_id', $programs_ids)
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('companies.name', 'LIKE', '%' . $vendor . '%');
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
          $due_today = 0;
          $due_seven_days = 0;
          $due_fourteen_days = 0;
          $due_twenty_one_days = 0;
          $due_twenty_eight_days = 0;
          $due_thirty_five_days = 0;
          $due_fourty_two_days = 0;
          $due_fourty_nine_days = 0;

          $total_due = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->sum('calculated_total_amount');

          $due_today = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
            ->where('program_id', $payment_account->program_id)
            ->where('financing_status', 'disbursed')
            ->whereDate('due_date', '<=', now()->format('Y-m-d'))
            ->sum('calculated_total_amount');

          $due_seven_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_fourteen_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_twenty_one_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_thirty_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_fourty_five_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_sixty_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_seventy_five_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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

          $due_ninety_days = Invoice::vendorFinancing()
            ->where('company_id', $payment_account->company_id)
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
            $payment_account->company->name . ' (' . $payment_account->payment_account_number . ')';
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
      case 'vendor-analysis':
        $headers = ['Vendor', 'Limit', 'Utilized Limit', 'Available Limit', 'Pipeline Amount'];
        $data = [];
        $current_company = auth()
          ->user()
          ->activeAnchorCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $vendor_search = $request->query('vendors');

        $vendors = [];

        // Get vendors
        foreach ($company->programs as $program) {
          if (
            $program->programType->name == Program::VENDOR_FINANCING &&
            $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
          ) {
            $program_vendors = $program->getVendors();

            foreach ($program_vendors as $key => $vendor) {
              $vendor = Company::where('id', $vendor->id)->first();
              $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
                ->where('company_id', $vendor->id)
                ->first();
              $utilized_amount = $vendor->utilizedAmount($program);
              $data[$key]['Vendor'] = $vendor->name;
              $data[$key]['limit'] = $vendor_configuration->sanctioned_limit;
              $data[$key]['Utilized Amount'] = $utilized_amount;
              $data[$key]['Available Amount'] = $vendor_configuration->sanctioned_limit - $utilized_amount;
              $data[$key]['Pipeline Amount'] = $vendor->pipelineAmount($program);
            }
          }
        }

        collect($vendors)
          ->when($vendor_search && $vendor_search != '', function (Collection $collection) use ($vendor_search) {
            return similar_text($collection->name, $vendor_search);
          })
          ->toArray();

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Vendor Analysis Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Vendor_Analysis_Report_' . $date . '.pdf');
        break;
      case 'discountings':
        $vendor = $request->query('vendor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->with([
            'company' => fn($query) => $query->select('id', 'name'),
            'paymentRequests.cbsTransactions',
            'invoiceItems',
          ])
          ->whereIn('program_id', $programs_ids)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereHas('cbsTransactions');
          })
          ->whereIn('financing_status', ['disbursed', 'closed'])
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '%');
            });
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('disbursement_date', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('disbursement_date', '<=', $to_date);
          })
          ->latest()
          ->get();

        $headers = ['Vendor', 'Invoice Number', 'Amount', 'Disbursement Date', 'Transaction Ref'];
        $data = [];

        foreach ($invoices as $key => $invoice) {
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Disbursement Date'] = Carbon::parse($invoice->disbursement_date)->format('d M Y');
          $data[$key]['Transaction Ref'] = $invoice->paymentRequests
            ->first()
            ->cbsTransactions->where('transaction_type', 'Payment Disbursement')
            ->first()->transaction_reference;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Discountings Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Discountings_Report_' . $date . '.pdf');
        break;
      case 'closed-invoices':
        $anchor = $request->query('anchor');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $anchor_role = ProgramRole::where('name', 'anchor')->first();

        $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
          ->where('company_id', $this->company()->id)
          ->pluck('program_id');

        $invoices = Invoice::vendorFinancing()
          ->where('financing_status', 'closed')
          ->whereIn('program_id', $programs_ids)
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
            $query
              ->when($status == 'overdue', function ($query) {
                $query->whereDate('due_date', '<', now()->format('Y-m-d'));
              })
              ->when($status != 'overdue', function ($query) use ($status) {
                $query->where('status', $status);
              });
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->orderBy('updated_at', 'DESC')
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
          $data[$key]['Vendor'] = $invoice->company->name;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
          $data[$key]['Invoice Date'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
          $data[$key]['Invoice Due Date'] = Carbon::parse($invoice->due_date)->format('d M Y');
          $data[$key]['Disbursement Date'] = $invoice->disbursement_date
            ? Carbon::parse($invoice->disbursement_date)->format('d M Y')
            : '-';
          $data[$key]['Disbursed Amount'] = $invoice->disbursed_amount
            ? number_format($invoice->disbursed_amount, 2)
            : 0;
          $data[$key]['Date of Closure'] =
            $invoice->financing_status === 'closed' ? Carbon::parse($invoice->closure_date)->format('d M Y') : '-';
          $data[$key]['Transaction Reference No.'] = $invoice->closure_transaction_reference ?? '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Closed Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Closed_Invoices_Report_' . $date . '.pdf');
        break;
      case 'factoring-all-invoices-report':
        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $buyer = $request->query('buyer');
        $invoice_number = $request->query('invoice_number');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $status = $request->query('status');
        $financing_status = $request->query('financing_status');

        $invoices = Invoice::factoring()
          ->with([
            'program.anchor',
            'invoiceItems',
            'invoiceFees',
            'invoiceTaxes',
            'invoiceDiscounts',
            'paymentRequests.paymentAccounts',
            'buyer',
          ])
          ->where('company_id', $current_company->company_id)
          ->when($buyer && $buyer != '', function ($query) use ($buyer) {
            $query->whereHas('buyer', function ($query) use ($buyer) {
              $query->where('name', 'LIKE', '%' . $buyer . '%');
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
          ->latest()
          ->get();

        $headers = [
          'Invoice Number',
          'Buyer',
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
          $data[$key]['Buyer'] = $invoice->buyer->name;
          $data[$key]['Invoice Amount'] = number_format($invoice->invoice_total_amount, 2);
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
            $invoice->paymentRequests->count() > 0
              ? number_format($invoice->paymentRequests->first()->discount, 2)
              : '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Seller All Invoices Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Seller_All_Invoices_Report_' . $date . '.pdf');
        break;
      case 'factoring-program-report':
        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $buyer = $request->query('buyer');
        $loan_account = $request->query('loan_account');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::with('buyer', 'program')
            ->whereHas('program', function ($query) {
              $query->whereHas('programCode', function ($query) {
                $query
                  ->where('name', Program::FACTORING_WITH_RECOURSE)
                  ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
              });
            })
            ->where('company_id', $current_company->company_id)
            ->when($buyer && $buyer != '', function ($query) use ($buyer) {
              $query->whereHas('buyer', function ($query) use ($buyer) {
                $query->where('name', 'LIKE', '%' . $buyer . '%');
              });
            })
            ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
              $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
            })
            ->get()
        );

        $headers = [
          'Loan Account No',
          'Buyer',
          'Financing Limit',
          'Utilized Limit',
          'Pipeline Amount',
          'Available Limit',
          'Limit Review Date',
        ];
        $data = [];

        foreach ($programs as $key => $program) {
          $data[$key]['Loan Account No'] = $program->payment_account_number;
          $data[$key]['Buyer'] = $program->buyer->name;
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
          'reportTitle' => 'Seller Programs Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Seller_Programs_Report_' . $date . '.pdf');

        break;
      case 'factoring-dealer-program-report':
        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $dealer = $request->query('dealer');
        $loan_account = $request->query('loan_account');

        $programs = OdAccountsResource::collection(
          ProgramVendorConfiguration::with('buyer', 'program')
            ->whereHas('program', function ($query) use ($current_company) {
              $query
                ->whereHas('anchor', function ($query) use ($current_company) {
                  $query->where('companies.id', $current_company->company_id);
                })
                ->whereHas('programType', function ($query) {
                  $query->where('name', Program::DEALER_FINANCING);
                });
            })
            ->when($dealer && $dealer != '', function ($query) use ($dealer) {
              $query->whereHas('company', function ($query) use ($dealer) {
                $query->where('name', 'LIKE', '%' . $dealer . '%');
              });
            })
            ->when($loan_account && $loan_account != '', function ($query) use ($loan_account) {
              $query->where('payment_account_number', 'LIKE', '%' . $loan_account . '%');
            })
            ->get()
        );

        $headers = [
          'Loan Account No',
          'Dealer',
          'Financing Limit',
          'Utilized Limit',
          'Pipeline Amount',
          'Available Limit',
          'Limit Review Date',
        ];
        $data = [];

        foreach ($programs as $key => $program) {
          $data[$key]['Loan Account No'] = $program->payment_account_number;
          $data[$key]['Dealer'] = $program->company_name;
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
      case 'factoring-payments-report':
        $invoice_number = $request->query('invoice_number');
        $buyer = $request->query('buyer');
        $po = $request->query('po');
        $invoice_status = $request->query('invoice_status');
        $financing_status = $request->query('financing_status');
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $from_disbursement_date = $request->query('from_disbursement_date');
        $to_disbursement_date = $request->query('to_disbursement_date');

        $current_company = auth()
          ->user()
          ->activeFactoringCompany()
          ->first();

        $company = Company::find($current_company->company_id);

        $payments = Invoice::factoring()
          ->where('company_id', $current_company->company_id)
          ->where('disbursement_date', '!=', null)
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($buyer && $buyer != '', function ($query) use ($buyer) {
            $query->whereHas('buyer', function ($query) use ($buyer) {
              $query->where('name', 'LIKE', '%' . $buyer . '%');
            });
          })
          ->when($invoice_status && $invoice_status != '', function ($query) use ($invoice_status) {
            $query->where('status', $invoice_status);
          })
          ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
            $query->where('financing_status', $financing_status);
          })
          ->when($po && $po != '', function ($query) use ($po) {
            $query->whereHas('purchae_order', function ($query) use ($po) {
              $query->where('purchase_order_number', 'LIKE', '%' . $po . '%');
            });
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
          ->orderBy('due_date', 'DESC')
          ->get();

        $headers = [
          'System ID',
          'Invoice Number',
          'Buyer',
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
            ->where('buyer_id', $invoice->buyer_id)
            ->first();
          $data[$key]['System ID'] = $invoice->id;
          $data[$key]['Invoice Number'] = $invoice->invoice_number;
          $data[$key]['Buyer'] = $invoice->buyer->name;
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
          'reportTitle' => 'Seller Payments Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Seller_Payments_Report_' . $date . '.pdf');

        break;
      default:
        break;
    }
  }
}
