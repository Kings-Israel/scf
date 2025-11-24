<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Models\BankHoliday;
use App\Models\NoaTemplate;
use App\Models\ProgramCode;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramDiscount;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProgramVendorFee;
use App\Models\BankPaymentAccount;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use App\Exports\CashPlannerInvoices;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Http\Resources\ProgramResource;
use App\Jobs\BulkRequestFinancing;
use App\Mail\BulkRequestFinance;
use App\Models\BankGeneralProductConfiguration;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProgramVendorDiscount;
use App\Models\TermsConditionsConfig;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\BankProductsConfiguration;
use App\Models\InvoiceProcessing;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Notifications\PaymentRequestNotification;
use Database\Factories\ProgramVendorFeeFactory;

class CashPlannerController extends Controller
{
  public function index()
  {
    $anchors = [];
    $programs = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $program_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::whereIn('program_id', $program_ids)
      ->where('company_id', $company->id)
      ->get();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $anchors_ids = ProgramCompanyRole::whereIn('program_id', $program_ids)
      ->where('role_id', $anchor_role->id)
      ->pluck('company_id');

    $anchors = Company::whereIn('id', $anchors_ids)->get();

    $off_days = $company->bank->adminConfiguration->offdays
      ? explode('-', str_replace(' ', '', $company->bank->adminConfiguration->offdays))
      : null;
    if ($off_days) {
      foreach ($off_days as $key => $off_day) {
        switch ($off_day) {
          case 'Monday':
            $off_days[$key] = 1;
            break;
          case 'Tuesday':
            $off_days[$key] = 2;
            break;
          case 'Wednesday':
            $off_days[$key] = 3;
            break;
          case 'Thursday':
            $off_days[$key] = 4;
            break;
          case 'Friday':
            $off_days[$key] = 5;
            break;
          case 'Saturday':
            $off_days[$key] = 6;
            break;
          case 'Sunday':
            $off_days[$key] = 0;
            break;
        }
      }
    }

    $holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->get();

    foreach ($holidays as $holiday) {
      $holiday['date_formatted'] = Carbon::parse($holiday->date)->format('Y-m-d');
    }

    return view('content.vendor.cash-planner.index', [
      'anchors' => $anchors,
      'programs' => $programs,
      'holidays' => $holidays,
      'off_days' => $off_days,
    ]);
  }

  public function programs(Request $request)
  {
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('program.anchor')
        ->whereHas('program', function ($query) {
          $query->whereHas('programCode', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
          });
        })
        ->where('company_id', $company->id)
        ->select(
          'id',
          'program_id',
          'company_id',
          'sanctioned_limit',
          'payment_account_number',
          'limit_expiry_date',
          'utilized_amount',
          'pipeline_amount'
        )
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['programs' => $programs], 200);
    }
  }

  public function eligibleForFinancing(Program $program)
  {
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $total_amount = 0;

    $minimum_financing_days = $program->min_financing_days;
    $maximum_financing_days = $program->max_financing_days;

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoices = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->where('program_id', $program->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->whereDate('due_date', '>=', now())
      ->orderBy('due_date', 'DESC')
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now();
      });

    $min_invoice_date = now();

    if ($invoices->count() > 0) {
      $max_invoice_date = Carbon::parse($invoices->first()->due_date)->subDays($minimum_financing_days);
      $min_invoice_date = Carbon::parse($invoices->first()->due_date)->subDays($maximum_financing_days);
      foreach ($invoices as $invoice) {
        $eligibility = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->first()->eligibility;
        $total_amount += ($eligibility / 100) * $invoice->invoice_total_amount;
        if (
          Carbon::parse($invoice->due_date)
            ->subDays($maximum_financing_days)
            ->lessThan(Carbon::parse($min_invoice_date))
        ) {
          $min_invoice_date = Carbon::parse($invoice->due_date)->subDays($maximum_financing_days);
        }
      }

      if (Carbon::parse($min_invoice_date)->lessThan(now())) {
        $min_invoice_date = now();
      }

      return response()->json([
        'total_amount' => $total_amount,
        'max_date' => $max_invoice_date,
        'min_date' => $min_invoice_date,
        'min_financing_days' => $minimum_financing_days,
      ]);
    }
    return response()->json(['error' => 'No valid invoices in this program']);
  }

  public function eligibleForFinancingCalculate(Program $program, $date)
  {
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $total_discount = 0;
    $discount = 0;
    $total_invoice_amount = 0;
    $total_actual_remittance = 0;

    $invoices = Invoice::where('company_id', $company->id)
      ->where('program_id', $program->id)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now();
      });

    foreach ($invoices as $invoice) {
      $total_invoice_amount += $invoice->invoice_total_amount;
      $response = $invoice->calculateActualRemittanceAmount($date);
      $total_actual_remittance += $response['actual_remittance'];
      $discount += $response['discount'];
    }

    $total_discount = $discount;

    return [round($total_discount, 2), round($total_actual_remittance, 2)];
  }

  public function storeMassFinancingRequest(Request $request)
  {
    $request->validate([
      'payment_date' => ['required'],
      'program_id' => ['required'],
    ]);

    $invoice_ids = [];

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    if ($company->status == 'inactive') {
      toastr()->error('', 'Company has been deactivated. Contact bank for assistance');

      return back();
    }

    if ($company->is_blocked) {
      toastr()->error('', 'Company has been blocked from making requests. Contact bank for assistance');

      return back();
    }

    // Check if payment request date is not a bank holiday or off day
    $bank_holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->pluck('date')
      ->toArray();

    $off_days = $company->bank->adminConfiguration->offdays;
    $off_days = $off_days ? explode('-', str_replace(' ', '', $off_days)) : [];
    $off_days_converted = [];
    foreach ($off_days as $off_day) {
      switch ($off_day) {
        case 'Monday':
          $off_days_converted[] = 1;
          break;
        case 'Tuesday':
          $off_days_converted[] = 2;
          break;
        case 'Wednesday':
          $off_days_converted[] = 3;
          break;
        case 'Thursday':
          $off_days_converted[] = 4;
          break;
        case 'Friday':
          $off_days_converted[] = 5;
          break;
        case 'Saturday':
          $off_days_converted[] = 6;
          break;
        case 'Sunday':
          $off_days_converted[] = 0;
          break;
      }
    }

    if (in_array(Carbon::parse($request->payment_date)->format('Y-m-d'), $bank_holidays)) {
      toastr()->error('', 'Payment request date falls on a bank holiday.');

      return back();
    }

    if (in_array(Carbon::parse($request->payment_date)->dayOfWeek, $off_days_converted)) {
      toastr()->error('', 'Payment request date falls on a bank off day.');

      return back();
    }

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoice_ids = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->where('program_id', $request->program_id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->orderBy('due_date', 'DESC')
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now();
      })
      ->pluck('id');

    // Get total amount of the requested invoices
    $sum_amount = 0;
    $message = 'Requested amount exceeds your limit. Contact bank for assistance.';

    Invoice::whereIn('id', $invoice_ids)->chunk(100, function ($invoices) use (&$sum_amount) {
      foreach ($invoices as $invoice) {
        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->select('eligibility')
          ->first();

        $sum_amount += ($vendor_configurations->eligibility / 100) * $invoice->calculated_total_amount;
      }
    });

    $can_request = true;
    // & used in can_request to pass by reference
    // Check if request will exceed vendor program limit
    // Check if request will exceed program limit
    // Check if request will exceed company top level borrower limit
    Invoice::whereIn('id', $invoice_ids)->chunk(50, function ($invoices) use (
      $company,
      &$can_request,
      &$sum_amount,
      &$message
    ) {
      foreach ($invoices as $invoice) {
        $invoice_total_amount = $invoice->invoice_total_amount;

        $program = Program::find($invoice->program_id);

        // Check if program is active
        if ($program->account_status == 'suspended') {
          $message = 'Program is currently unavailable to make requests. Contact bank for assistance.';
          $can_request = false;
        }

        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->first();

        // Check limits at OD Level
        $sanctioned_limit = $vendor_configurations->sanctioned_limit;
        $utilized_amount = $vendor_configurations->utilized_amount;
        $pipeline_amount = $vendor_configurations->pipeline_amount;

        $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
            ]);
          }
          $message = 'Requested amount exceeds your sanctioned limit. Contact bank for assistance.';
          $can_request = false;
        }

        // Check at program level
        $program_limit = $program->program_limit;
        $utilized_amount = $program->utilized_amount;
        $pipeline_amount = $program->pipeline_amount;
        $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
            ]);
          }

          $can_request = false;
          $message = 'Requested amount exceeds program limit. Contact bank for assistance.';
        }

        // Check if request exceeds company top level borrower limit
        $top_level_borrower_limit = $company->top_level_borrower_limit;
        $utilized_amount = $company->total_utilized_amount;
        $pipeline_amount = $company->total_pipeline_amount;
        $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $invoice_total_amount;
        if ($available_limit <= 0) {
          $message = 'Requested amount exceeds your company top level borrower limit. Contact bank for assistance.';
          $can_request = false;
        }

        // Check if request will exceed drawing power
        if ($vendor_configurations->drawing_power > 0) {
          if ($invoice_total_amount > $vendor_configurations->drawing_power) {
            // Notify bank of request to unblock
            foreach ($company->bank->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
                'company_id' => $company->id,
                'approved_limit' => $vendor_configurations->sanctioned_limit,
                'current_exposure' => $utilized_amount,
                'pipeline_requests' => $pipeline_amount,
                'available_limit' => $available_limit,
              ]);
            }
            $message = 'Requested amount exceeds your drawing power. Contact bank for assistance.';
            $can_request = false;
          }
        }
      }
    });

    if (!$can_request) {
      toastr()->error('', $message);

      return back();
    }

    $requested_payment_date = $request->payment_date;

    Invoice::whereIn('id', $invoice_ids)->chunk(50, function ($invoices) use ($requested_payment_date, $company) {
      foreach ($invoices as $invoice) {
        InvoiceProcessing::create([
          'company_id' => $company->id,
          'invoice_id' => $invoice->id,
          'action' => 'requesting financing',
          'status' => 'pending',
          'data' => [
            'payment_request_date' => $requested_payment_date,
          ],
        ]);
      }
    });

    BulkRequestFinancing::dispatchAfterResponse($company, Program::VENDOR_FINANCING_RECEIVABLE);

    toastr()->success('', 'Payment requests created successfully.');

    return back();
  }

  public function exportInvoices(Request $request)
  {
    $date = now()->format('Y-m-d');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    Excel::store(
      new CashPlannerInvoices($request->selected_program, $company, $request->selected_date),
      'Eligible_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Eligible_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function plannerCalculate(Request $request)
  {
    $program_id = $request->query('program');
    $amount = $request->query('amount');
    $invoice_date = $request->query('invoice_date');
    $due_date = $request->query('due_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendor_discount_details = ProgramVendorDiscount::where('program_id', $program_id)
      ->where('company_id', $company->id)
      ->first();
    $vendor_configurations = ProgramVendorConfiguration::where('program_id', $program_id)
      ->where('company_id', $company->id)
      ->first();
    $vendor_fees = ProgramVendorFee::where('program_id', $program_id)
      ->where('company_id', $company->id)
      ->get();
    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::where('program_id', $program_id)->first()?->tax_on_discount;

    $eligibility = $vendor_configurations->eligibility;
    $total_roi = $vendor_discount_details->total_roi;
    $legible_amount = ($eligibility / 100) * $amount;

    $fees_amount = 0;
    $anchor_bearing_fees = 0;
    $vendor_bearing_fees = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type === 'amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount += $fee->value * Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * $fee->value * Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value * Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));
            } else {
              $anchor_bearing_fees +=
                ($fee->anchor_bearing_discount / 100) *
                $fee->value *
                Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));
              $vendor_bearing_fees +=
                ($fee->vendor_bearing_discount / 100) *
                $fee->value *
                Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));
            }
          } else {
            $fees_amount += $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value;
            } else {
              $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
              $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
            }
          }
        }

        if ($fee->type === 'percentage') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              ($fee->value / 100) *
              $legible_amount *
              Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                ($fee->value / 100) *
                  $legible_amount *
                  Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }
          } else {
            $fees_amount += ($fee->value / 100) * $legible_amount;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
            }
          }
        }

        if ($fee->type === 'per amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              floor($legible_amount / $fee->per_amount) *
              $fee->value *
              Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }
          } else {
            $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }
          }
        }
      }
    }

    $total_discount = 0;
    if ($total_roi > 0) {
      if ($invoice_date && $due_date) {
        $total_discount =
          ($vendor_discount_details->vendor_discount_bearing / $total_roi) *
          ($eligibility / 100) *
          $amount *
          ($total_roi / 100) *
          (Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)) / 365);
      } else {
        $invoice = Invoice::where('company_id', $company->id)
          ->where('status', 'approved')
          ->where('program_id', $program_id)
          ->whereDoesntHave('paymentRequests')
          ->orderBy('due_date', 'DESC')
          ->where('eligible_for_financing', true)
          ->first();

        $total_discount =
          ($vendor_discount_details->vendor_discount_bearing / $total_roi) *
          ($eligibility / 100) *
          $amount *
          ($total_roi / 100) *
          (now()->diffInDays(Carbon::parse($invoice->due_date)) / 365);
      }
    }

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $total_discount;
    }

    $total_actual_remittance =
      $legible_amount - $vendor_bearing_fees - $fees_tax_amount - $total_discount - $discount_tax_amount;

    if ($vendor_discount_details->discount_type == Invoice::REAR_ENDED) {
      $total_actual_remittance = $legible_amount - $vendor_bearing_fees - $fees_tax_amount;
      $total_discount = 0;
    }

    if ($vendor_discount_details->fee_type == Invoice::REAR_ENDED) {
      $total_actual_remittance = $total_actual_remittance + $vendor_bearing_fees + $fees_tax_amount;
    }

    return [round($total_discount, 2), round($total_actual_remittance, 2)];
  }

  public function eligibleInvoices(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $per_page = $request->query('per_page');

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoices = Invoice::with(
      'program.anchor',
      'program.discountDetails',
      'invoiceItems',
      'invoiceFees',
      'invoiceTaxes',
      'invoiceDiscounts',
      'paymentRequests.paymentAccounts',
      'purchaseOrder.purchaseOrderItems'
    )
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . $invoice_number . '%');
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', $to_date);
      })
      ->where('eligible_for_financing', true)
      ->orderBy('due_date', 'ASC')
      ->paginate($per_page);

    $minimum_financing_days = 0;
    $highest_due_date = now();

    if ($invoices->count() > 0) {
      $minimum_financing_days = $invoices->first()->program->min_financing_days;
      $highest_due_date = $invoices->first()->due_date;
    }

    foreach ($invoices as $invoice) {
      // Get the program with the least minimum financing days
      if ($invoice->program->min_financing_days < $minimum_financing_days) {
        $minimum_financing_days = $invoice->program->min_financing_days;
      }

      // Get the invoice with the highest due date
      if (Carbon::parse($invoice->due_date)->greaterThan(Carbon::parse($highest_due_date))) {
        $highest_due_date = $invoice->due_date;
      }
    }

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    $terms_and_conditions = '#';
    $noa = '#';
    $noa_text = NoaTemplate::where('product_type', 'vendor_financing')
      ->where('status', 'active')
      ->where('bank_id', $company->bank_id)
      ->first();

    if (!$noa_text) {
      $noa_text = NoaTemplate::where('product_type', 'generic')
        ->where('status', 'active')
        ->first();
    }

    $terms_text = TermsConditionsConfig::where('product_type', 'vendor_financing')
      ->where('status', 'active')
      ->first();

    // get bank off days and holidays
    $off_days = $company->bank->adminConfiguration->offdays
      ? explode('-', str_replace(' ', '', $company->bank->adminConfiguration->offdays))
      : null;
    if ($off_days) {
      foreach ($off_days as $key => $off_day) {
        switch ($off_day) {
          case 'Monday':
            $off_days[$key] = 1;
            break;
          case 'Tuesday':
            $off_days[$key] = 2;
            break;
          case 'Wednesday':
            $off_days[$key] = 3;
            break;
          case 'Thursday':
            $off_days[$key] = 4;
            break;
          case 'Friday':
            $off_days[$key] = 5;
            break;
          case 'Saturday':
            $off_days[$key] = 6;
            break;
          case 'Sunday':
            $off_days[$key] = 0;
            break;
        }
      }
    }

    $holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->get();

    foreach ($holidays as $holiday) {
      $holiday['date_formatted'] = Carbon::parse($holiday->date)->format('d M Y');
    }

    if (request()->wantsJson()) {
      return response()->json(
        [
          'invoices' => $invoices,
          'terms_and_conditions' => $terms_and_conditions,
          'noa' => $noa,
          'noa_text' => $noa_text,
          'terms_text' => $terms_text,
          'min_financing_days' => $minimum_financing_days,
          'highest_due_date' => $highest_due_date,
          'off_days' => $off_days,
          'holidays' => $holidays,
        ],
        200
      );
    }
  }

  public function nonEligibleInvoices(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = InvoiceResource::collection(
      Invoice::with(
        'program',
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'invoiceDiscounts',
        'paymentRequests.paymentAccounts'
      )
        ->where('company_id', $company->id)
        ->where('status', 'approved')
        ->whereDoesntHave('paymentRequests')
        ->whereDate('due_date', '>=', now())
        ->orderBy('created_at', 'DESC')
        ->where('eligible_for_financing', false)
        ->paginate($request->per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function remittanceAmountDetails(Request $request)
  {
    $request->validate([
      'invoices' => ['required'],
      'date' => ['required'],
    ]);

    $total_amount = 0;
    $total_actual_remittance = 0;

    foreach ($request->invoices as $invoice) {
      $invoice = Invoice::find($invoice['id']);
      $total_amount += $invoice->invoice_total_amount;
      $total_actual_remittance += $invoice->calculateActualRemittanceAmount($request->date)['actual_remittance'];
    }

    return response()->json([
      'total_amount' => $total_amount,
      'total_remittance_amount' => $total_actual_remittance,
    ]);
  }

  public function requestFinance(Request $request)
  {
    $request->validate([
      'invoice_id' => ['required'],
      'payment_request_date' => ['required', 'date'],
      'credit_to' => ['required'],
    ]);

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    if ($company->status == 'inactive') {
      return response()->json(['message' => 'Company has been deactivated. Contact bank for assistance.'], 422);
    }

    if ($company->is_blocked) {
      return response()->json(
        ['message' => 'Company has been locked from making requests. Contact bank for assistance.'],
        422
      );
    }

    $invoice = Invoice::with('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts', 'company')->find(
      $request->invoice_id
    );

    $program = Program::find($invoice->program_id);

    // Check if program is active
    if ($program->account_status === 'suspended') {
      // Notify user
      return response()->json(
        ['message' => 'Program is currently unavailable to make requests. Contact bank for assistance.'],
        422
      );
    }

    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company->id)
      ->where('program_id', $invoice->program_id)
      ->first();

    // Check if company can make the request on the program
    if (
      $vendor_configurations->is_blocked ||
      !$vendor_configurations->is_approved ||
      $vendor_configurations->status == 'inactive'
    ) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'RequestToUnblock', ['company_id' => $company->id]);
      }

      // Notify user
      return response()->json(
        ['message' => 'This OD account has been blocked/deactivated on the program. Contact bank for assistance.'],
        422
      );
    }

    $utilized_amount = $program->utilized_amount;

    $vendor_utilized_amount = $company->utilized_amount;
    $pipeline_requests = $company->pipeline_amount;

    // Check against top level borrower limit
    if (
      $vendor_utilized_amount + $pipeline_requests + $invoice->invoice_total_amount >
      $company->top_level_borrower_limit
    ) {
      return response()->json(['message' => 'Amount exceeds your top level borrowing limit.'], 422);
    }

    // Get Retain Limit as set in Bank Configuration
    $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
      ->where('product_type_id', $invoice->program->program_type_id)
      ->where('name', 'retain limit')
      ->first();

    if ($bank_configurations->value > 0) {
      $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
      $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
      $potential_utilization_amount = $utilized_amount + $pipeline_requests + $invoice->invoice_total_amount;
      if ($potential_utilization_amount > $remainder) {
        return response()->json(['message' => 'Amount exceeds your borrowing limit.'], 422);
      }
    }

    // Check if request will exceed OD Account Sanctioned program limit
    $vendor_configuration_utilized_amount = $vendor_configurations->utilized_amount;
    $vendor_configuration_pipeline_amount = $vendor_configurations->pipeline_amount;
    if (
      $vendor_configuration_utilized_amount + $vendor_configuration_pipeline_amount + $invoice->invoice_total_amount >
      $vendor_configurations->sanctioned_limit
    ) {
      $vendor_configuration_available_limit =
        $vendor_configurations->sanctioned_limit -
        $vendor_configuration_utilized_amount -
        $vendor_configuration_pipeline_amount;
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $vendor_configurations->sanctioned_limit,
          'current_exposure' => $vendor_configuration_utilized_amount,
          'pipeline_requests' => $vendor_configuration_pipeline_amount,
          'available_limit' => $vendor_configuration_available_limit,
        ])->afterResponse();
      }

      return response()->json(['message' => 'Vendor Limit exceeded. Contact Bank For Assistance'], 422);
    }

    // Check if request will exceed program limit
    $program_utilized_amount = $program->utilized_amount;
    $program_pipeline_amount = $program->pipeline_amount;
    if (
      $program_utilized_amount + $program_pipeline_amount + $invoice->invoice_total_amount >
      $program->program_limit
    ) {
      $program_available_limit = $program->program_limit - $program->utilized_amount - $program->pipeline_amount;
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $program->program_limit,
          'current_exposure' => $program_utilized_amount,
          'pipeline_requests' => $program_pipeline_amount,
          'available_limit' => $program_available_limit,
        ])->afterResponse();
      }

      return response()->json(['message' => 'Program Limit exceeded. Contact Bank For Assistance'], 422);
    }

    // Check if request will exceed drawing power
    if ($vendor_configurations->drawing_power > 0) {
      if ($invoice->invoice_total_amount > $vendor_configurations->drawing_power) {
        // Notify bank of request to unblock
        foreach ($company->bank->users as $user) {
          SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
            'company_id' => $company->id,
            'approved_limit' => $vendor_configurations->sanctioned_limit,
            'current_exposure' => $vendor_configuration_utilized_amount,
            'pipeline_requests' => $vendor_configuration_pipeline_amount,
            'available_limit' =>
              $vendor_configurations->sanctioned_limit -
              $vendor_configuration_utilized_amount -
              $vendor_configuration_pipeline_amount,
          ])->afterResponse();
        }

        return response()->json(['message' => 'Drawing Power Limit exceeded. Contact Bank For Assistance'], 422);
      }
    }

    InvoiceProcessing::create([
      'company_id' => $company->id,
      'invoice_id' => $invoice->id,
      'action' => 'requesting financing',
      'status' => 'pending',
      'data' => [
        'payment_request_date' => $request->payment_request_date,
        'credit_to' => $request->credit_to,
      ],
    ]);

    BulkRequestFinancing::dispatchAfterResponse($company, Program::VENDOR_FINANCING_RECEIVABLE);

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Successfully created payment request']);
    }

    toastr()->success('', 'Payment request successfully created.');

    return back();
  }

  public function requestMultipleFinance(Request $request)
  {
    $request->validate([
      'invoices' => ['required'],
      'payment_request_date' => ['required', 'date'],
    ]);

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    if ($company->status == 'inactive') {
      return response()->json(['message' => 'Company has been deactivated. Contact bank for assistance.'], 422);
    }

    if ($company->is_blocked) {
      return response()->json(
        ['message' => 'Company has been locked from making requests. Contact bank for assistance.'],
        422
      );
    }

    // Check if payment request date is not a bank holiday or off day
    $bank_holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->pluck('date')
      ->toArray();

    $off_days = $company->bank->adminConfiguration->offdays;
    $off_days = $off_days ? explode('-', str_replace(' ', '', $off_days)) : [];
    $off_days_converted = [];
    foreach ($off_days as $off_day) {
      switch ($off_day) {
        case 'Monday':
          $off_days_converted[] = 1;
          break;
        case 'Tuesday':
          $off_days_converted[] = 2;
          break;
        case 'Wednesday':
          $off_days_converted[] = 3;
          break;
        case 'Thursday':
          $off_days_converted[] = 4;
          break;
        case 'Friday':
          $off_days_converted[] = 5;
          break;
        case 'Saturday':
          $off_days_converted[] = 6;
          break;
        case 'Sunday':
          $off_days_converted[] = 0;
          break;
      }
    }

    if (in_array(Carbon::parse($request->payment_request_date)->format('Y-m-d'), $bank_holidays)) {
      return response()->json(['message' => 'Payment request date falls on a bank holiday.'], 422);
    }

    if (in_array(Carbon::parse($request->payment_request_date)->dayOfWeek, $off_days_converted)) {
      return response()->json(['message' => 'Payment request date falls on a bank off day.'], 422);
    }

    $requested_payment_date = $request->payment_request_date;
    $can_request = true;
    $message = 'Requested amount exceeds available limit';

    // Get total amount of the requested invoices
    $sum_amount = 0;

    Invoice::whereIn('id', $request->invoices)->chunk(100, function ($invoices) use (&$sum_amount) {
      foreach ($invoices as $invoice) {
        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->select('eligibility')
          ->first();

        $sum_amount += ($vendor_configurations->eligibility / 100) * $invoice->calculated_total_amount;
      }
    });

    // & used in can_request to pass by reference
    // Check if request will exceed vendor program limit
    // Check if request will exceed program limit
    // Check if request will exceed company top level borrower limit
    Invoice::whereIn('id', $request->invoices)->chunk(100, function ($invoices) use (
      $company,
      &$can_request,
      &$message,
      $sum_amount
    ) {
      foreach ($invoices as $invoice) {
        $program = Program::find($invoice->program_id);

        // Check if program is active
        if ($program->account_status === 'suspended') {
          // Notify user
          $message = 'Program is currently unavailable to make requests. Contact bank for assistance.';
          $can_request = false;
          return false;
        }

        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->first();

        // Check if company can make the request on the program
        if (
          $vendor_configurations->is_blocked ||
          !$vendor_configurations->is_approved ||
          $vendor_configurations->status === 'inactive'
        ) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatch($user->email, 'RequestToUnblock', ['company_id' => $company->id])->afterResponse();
          }

          // Notify user
          $message = 'This OD account has been blocked/deactivated on the program. Contact bank for assistance.';
          $can_request = false;
          return false;
        }

        $invoice_total_amount = ($vendor_configurations->eligibility / 100) * $invoice->invoice_total_amount;

        // Check if request exceeds company top level borrower limit
        $top_level_borrower_limit = $company->top_level_borrower_limit;
        $utilized_amount = $company->utilized_amount;
        $pipeline_amount = $company->pipeline_amount;
        $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          $message = 'Amount exceeds your top level borrowing limit. Contact bank for assistance.';
          $can_request = false;
          return false;
        }

        // Check limits at OD Level
        $sanctioned_limit = $vendor_configurations->sanctioned_limit;
        $utilized_amount = $vendor_configurations->utilized_amount;
        $pipeline_amount = $vendor_configurations->pipeline_amount;

        $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
            ])->afterResponse();
          }
          $message = 'Vendor Limit exceeded. Contact Bank For Assistance.';
          $can_request = false;
          return false;
        }

        // Get Retain Limit as set in Bank Configuration
        $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
          ->where('product_type_id', $invoice->program->program_type_id)
          ->where('name', 'retain limit')
          ->first();

        if ($bank_configurations->value > 0) {
          $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
          $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
          $potential_utilization_amount = $utilized_amount + $pipeline_amount + $invoice_total_amount;
          if ($potential_utilization_amount > $remainder) {
            $message = 'Amount exceeds your borrowing limit. Contact bank for assistance.';
            $can_request = false;
            return false;
          }
        }

        $program = Program::find($invoice->program_id);

        // Check at program level
        $program_limit = $program->program_limit;
        $utilized_amount = $program->utilized_amount;
        $pipeline_amount = $program->pipeline_amount;
        $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
            ])->afterResponse();
          }
          $message = 'Program Limit exceeded. Contact Bank For Assistance';
          $can_request = false;
          return false;
        }

        // Check if request will exceed drawing power
        if ($vendor_configurations->drawing_power > 0) {
          if ($invoice_total_amount > $vendor_configurations->drawing_power) {
            // Notify bank of request to unblock
            foreach ($company->bank->users as $user) {
              SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
                'company_id' => $company->id,
                'approved_limit' => $vendor_configurations->sanctioned_limit,
                'current_exposure' => $utilized_amount,
                'pipeline_requests' => $pipeline_amount,
                'available_limit' => $available_limit,
              ])->afterResponse();
            }
            $message = 'Drawing Power exceeded. Contact Bank For Assistance';
            $can_request = false;
            return false;
          }
        }
      }
    });

    if (!$can_request) {
      return response()->json(['message' => $message], 422);
    }

    Invoice::whereIn('id', $request->invoices)
      ->orderBy('due_date', 'DESC')
      ->chunk(50, function ($invoices) use ($requested_payment_date, $company) {
        foreach ($invoices as $invoice) {
          InvoiceProcessing::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'action' => 'requesting financing',
            'status' => 'pending',
            'data' => [
              'payment_request_date' => $requested_payment_date,
            ],
          ]);
        }
      });

    BulkRequestFinancing::dispatchAfterResponse($company, Program::VENDOR_FINANCING_RECEIVABLE);

    if (request()->wantsJson()) {
      return response()->json('Payment Request Sent Successfully');
    }

    toastr()->success('', 'Payment request successfully created.');

    return back();
  }

  public function downloadNoa(Invoice $invoice)
  {
    $noa_text = '';

    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $noa_text = NoaTemplate::where('product_type', 'vendor_financing')
          ->where('status', 'active')
          ->where('bank_id', $invoice->program->bank_id)
          ->first();
      } else {
        $noa_text = NoaTemplate::where('product_type', 'factoring')
          ->where('status', 'active')
          ->where('bank_id', $invoice->program->bank_id)
          ->first();
      }
    } else {
      $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
        ->where('status', 'active')
        ->where('bank_id', $invoice->program->bank_id)
        ->first();
    }

    if (!$noa_text) {
      $noa_text = NoaTemplate::where('product_type', 'generic')
        ->where('status', 'active')
        ->first();
    }

    $data = [];
    $data['{date}'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
    $data['{buyerName}'] = $invoice->company->name;
    $data['{anchorName}'] = $invoice->program->anchor->name;
    $data['{company}'] = $invoice->company->name;
    $data['{anchorCompanyUniqueID}'] = $invoice->program->anchor->unique_identification_number;
    $data['{time}'] = now()->format('d M Y');
    $data['{agreementDate}'] = now()->format('d M Y');
    $data['{contract}'] = '';
    $data['{anchorAccountName}'] = '';
    $data['{anchorAccountNumber}'] = '';
    $data['{anchorCustomerId}'] = '';
    $data['{anchorBranch}'] = '';
    $data['{anchorIFSCCode}'] = '';
    $data['{anchorAddress}'] = '';
    $data['{penalnterestRate}'] = '';
    $data['{sellerName}'] = $invoice->company->name;

    $noa = '';

    if ($noa_text && $noa_text->body) {
      $noa = $noa_text->body;
      foreach ($data as $key => $val) {
        $noa = str_replace($key, $val, $noa);
      }

      $pdf = Pdf::loadView('pdf.noa', [
        'data' => $noa,
      ])->setPaper('a4', 'landscape');

      return $pdf->download('NOA_' . $invoice->invoice_number . '.pdf');
    }
  }

  public function downloadTerms($type)
  {
    $terms_and_conditions = TermsConditionsConfig::where('status', 'active')
      ->where('product_type', $type)
      ->first();

    if ($terms_and_conditions && $terms_and_conditions->terms_conditions) {
      $pdf = Pdf::loadView('pdf.terms_and_conditions', [
        'data' => $terms_and_conditions->terms_conditions,
      ])->setPaper('a4', 'landscape');

      return $pdf->download('Terms and Conditions.pdf');
    }
  }
}
