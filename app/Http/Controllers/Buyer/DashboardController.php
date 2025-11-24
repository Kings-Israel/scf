<?php

namespace App\Http\Controllers\Buyer;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramVendorFee;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Http\Resources\ProgramResource;
use App\Models\PaymentRequestAccount;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use Illuminate\Notifications\DatabaseNotification;

class DashboardController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendors = [];
    $approved_invoices_data = [];
    $pending_invoices_data = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    $all_invoices = [];
    $invoices = [];
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

    $programs = ProgramResource::collection(
      Program::with('anchor', 'programType', 'programCode')
        ->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
        })
        ->whereHas('vendorConfigurations', function ($query) use ($company) {
          $query->where('buyer_id', $company->id);
        })
        ->get()
    );

    $program_ids = ProgramVendorConfiguration::where('buyer_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
        });
      })
      ->pluck('program_id');

    foreach ($programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
          $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
      ) {
        $vendor_configuration = ProgramVendorConfiguration::where([
          'program_id' => $program->id,
          'company_id' => $program->anchor->id,
          'buyer_id' => $company->id,
        ])->first();
        $program['payment_account_number'] = $vendor_configuration?->payment_account_number;
        $program['sanctioned_limit'] = $vendor_configuration?->sanctioned_limit;
        $program['utilized_amount'] = $vendor_configuration?->utilized_amount;
        $program['pipeline_amount'] = $vendor_configuration?->pipeline_amount;
        $program['overdue_amount'] = $vendor_configuration?->overdue_amount;
        $program['total_requested_amount'] = $vendor_configuration?->total_requested_amount;
        $program['overdue_days'] = $vendor_configuration
          ? $program->anchor->daysPastDue($vendor_configuration?->program)
          : 0;
        array_push($vendors, $program);

        foreach ($program->invoices as $invoice) {
          array_push($all_invoices, $invoice->id);
        }
      }
    }

    $invoices['closed'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->where('financing_status', 'closed')
      ->count();

    $invoices['approved'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->count();

    $invoices['pending'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'submitted'])
      ->count();

    $invoices['financed'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->count();

    $invoices['disbursed'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'disbursed')
      ->count();

    $invoices['rejected'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->where(function ($query) {
        $query
          ->where('status', 'denied')
          ->orWhereIn('stage', ['internal_reject', 'rejected'])
          ->orWhere('financing_status', 'denied');
      })
      ->count();

    $invoices['past_due'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    $invoices['expired'] = Invoice::factoring()
      ->whereIn('program_id', $program_ids)
      ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
      ->where('status', 'expired')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    // Get Latest Invoices
    $latest_invoices = InvoiceResource::collection(
      Invoice::with('program.anchor')
        ->whereIn('id', $all_invoices)
        ->latest()
        ->take(10)
        ->get()
    );

    return view('content.buyer.dashboard', [
      'vendors' => $vendors,
      'invoices' => $invoices,
      'latest_invoices' => $latest_invoices,
      'months_formatted' => $months_formatted,
    ]);
  }

  public function invoicesData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    // Get past 12 months
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = Carbon::today()
        ->startOfMonth()
        ->subMonth($i);
      $year = Carbon::today()
        ->startOfMonth()
        ->subMonth($i)
        ->format('Y');
      array_push($months, $month);
    }

    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];
    $closed_payment_requests_data = [];

    $programs = ProgramVendorConfiguration::where('buyer_id', $company->id)
      ->whereHas('program', function ($query) {
        $query
          ->whereHas('programType', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING);
          })
          ->whereHas('programCode', function ($query) {
            $query
              ->where('name', Program::FACTORING_WITH_RECOURSE)
              ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
          });
      })
      ->pluck('program_id');

    foreach ($months as $month) {
      array_push(
        $pending_payment_requests_data,
        round(
          Invoice::factoring()
            ->whereIn('program_id', $programs)
            ->whereIn('status', ['approved'])
            ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
            ->sum('calculated_total_amount'),
          2
        )
      );
    }

    foreach ($months as $month) {
      array_push(
        $paid_payment_requests_data,
        round(
          Invoice::factoring()
            ->whereIn('program_id', $programs)
            ->whereIn('financing_status', ['disbursed', 'closed'])
            ->whereBetween('disbursement_date', [
              Carbon::parse($month)->startOfMonth(),
              Carbon::parse($month)->endOfMonth(),
            ])
            ->sum('calculated_total_amount'),
          2
        )
      );
    }

    foreach ($months as $month) {
      array_push(
        $closed_payment_requests_data,
        round(
          Invoice::factoring()
            ->whereIn('program_id', $programs)
            ->whereIn('financing_status', ['closed'])
            ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
            ->sum('calculated_total_amount'),
          2
        )
      );
    }

    $total_program_limit = 0;
    $total_utilized_amount = 0;
    $total_pipeline_amount = 0;
    $total_overdue_amount = 0;

    $vendor_configurations = OdAccountsResource::collection(
      ProgramVendorConfiguration::where('buyer_id', $company->id)->get()
    );

    foreach ($vendor_configurations as $vendor_configuration) {
      $total_program_limit += (int) $vendor_configuration?->sanctioned_limit;
      $total_utilized_amount += (int) $vendor_configuration?->utilized_amount;
      $total_pipeline_amount += (int) $vendor_configuration?->pipeline_amount;
      foreach ($vendor_configuration?->program?->invoices as $invoice) {
        $total_overdue_amount += (int) $invoice->overdue_amount;
      }
    }

    $total_available_limit = $total_program_limit - $total_utilized_amount - $total_pipeline_amount;

    $total_received = Invoice::factoring()
      ->whereIn('program_id', $programs)
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->sum('calculated_total_amount');

    $total_outstanding = Invoice::factoring()
      ->whereIn('program_id', $programs)
      ->whereHas('paymentRequests')
      ->whereIn('financing_status', ['financed'])
      ->whereDate('due_date', '>', now())
      ->sum('calculated_total_amount');

    return response()->json(
      [
        'paid_payment_requests_data' => $paid_payment_requests_data,
        'pending_payment_requests_data' => $pending_payment_requests_data,
        'closed_payment_requests_data' => $closed_payment_requests_data,
        'total_program_limit' => $total_program_limit,
        'total_utilized_amount' => $total_utilized_amount,
        'total_pipeline_amount' => $total_pipeline_amount,
        'total_available_limit' => $total_available_limit,
        'total_overdue_amount' => $total_overdue_amount,
        'total_received' => $total_received,
        'total_outstanding' => $total_outstanding,
      ],
      200
    );
  }

  public function dealer()
  {
    $pending_invoices = [];

    $approved_invoices_data = [];
    $pending_invoices_data = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    $invoices = [];
    $all_invoices = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

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

    $latest_invoices = InvoiceResource::collection(
      Invoice::dealerFinancing()
        ->where('company_id', $current_company->company_id)
        ->where('status', 'approved')
        ->orderBy('due_date', 'ASC')
        ->take(10)
        ->get()
    );

    $pending_invoices = InvoiceResource::collection(
      Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->whereHas('paymentRequests', function ($query) {
          $query->where('status', 'pending');
        })
        ->latest()
        ->take(10)
        ->get()
    );

    $invoices['closed'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->where('financing_status', 'closed')
      ->count();

    $invoices['approved'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->count();

    $invoices['pending'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'submitted'])
      ->count();

    $invoices['financed'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->count();

    $invoices['disbursed'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'disbursed')
      ->count();

    $invoices['rejected'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->where(function ($query) {
        $query
          ->where('status', 'denied')
          ->orWhereIn('stage', ['internal_reject', 'rejected'])
          ->orWhere('financing_status', 'denied');
      })
      ->count();

    $invoices['past_due'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    $invoices['expired'] = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
      ->where('status', 'expired')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    foreach ($company->invoices as $invoice) {
      array_push($all_invoices, $invoice);
    }

    return view('content.dealer.dashboard', [
      'latest_invoices' => $latest_invoices,
      'invoices' => $invoices,
      'pending_invoices' => $pending_invoices,
      'months_formatted' => $months_formatted,
      'currency' => $company->default_currency,
    ]);
  }

  public function dealerInvoicesData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get past 12 months
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = Carbon::today()
        ->startOfMonth()
        ->subMonth($i);
      array_push($months, $month);
    }

    $invoices = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    foreach ($company->invoices as $invoice) {
      array_push($invoices, $invoice);
    }

    foreach ($months as $month) {
      array_push(
        $pending_payment_requests_data,
        Invoice::dealerFinancing()
          ->where('company_id', $company->id)
          ->where('status', 'approved')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );

      array_push(
        $paid_payment_requests_data,
        round(
          Invoice::dealerFinancing()
            ->where('company_id', $company->id)
            ->whereIn('financing_status', ['disbursed'])
            ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
            ->sum('drawdown_amount'),
          2
        )
      );
    }

    $total_program_limit = 0;
    $total_utilized = 0;
    $total_pipeline = 0;
    $total_available_limit = 0;
    $total_overdue_amount = 0;

    $dealer_financing_programs = ProgramVendorConfiguration::whereHas('program', function ($query) {
      $query->whereHas('programType', function ($query) {
        $query->where('name', Program::DEALER_FINANCING);
      });
    })
      ->where('company_id', $company->id)
      ->get();

    // Total program limit for all programs
    foreach ($dealer_financing_programs as $program) {
      $total_program_limit += $program->sanctioned_limit;
      $total_utilized += $program->utilized_amount;
      $total_pipeline += $program->pipeline_amount;
      $total_overdue_amount += $program->overdue_amount;
    }

    $total_available_limit = $total_program_limit - $total_utilized - $total_pipeline;

    $total_outstanding = PaymentRequestAccount::where('type', 'vendor_account')
      ->whereHas('paymentRequest', function ($query) use ($company) {
        $query->where('status', 'approved')->whereHas('invoice', function ($query) use ($company) {
          $query
            ->whereDate('due_date', '>', now()->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->whereHas('program', function ($query) {
              $query->whereHas('programType', function ($query) {
                $query->whereIn('name', [Program::DEALER_FINANCING]);
              });
            });
        });
      })
      ->sum('amount');

    $total_received = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->sum('disbursed_amount');

    return response()->json(
      [
        'paid_payment_requests_data' => $paid_payment_requests_data,
        'pending_payment_requests_data' => $pending_payment_requests_data,
        'total_program_limit' => $total_program_limit,
        'total_available_limit' => $total_available_limit,
        'total_utilized' => $total_utilized,
        'total_pipeline' => $total_pipeline,
        'total_overdue_amount' => $total_overdue_amount,
        'total_received' => $total_received,
        'total_outstanding' => $total_outstanding,
      ],
      200
    );
  }

  public function notifications()
  {
    return view('content.buyer.notifications');
  }

  public function notificationsData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    $notifications = $company->unreadNotifications->toArray();
    $notifications = array_merge(
      $notifications,
      auth()
        ->user()
        ->unreadNotifications->toArray()
    );
    return response()->json($notifications);

    return response()->json($company->unreadNotifications->toArray());
  }

  public function notificationsDealer()
  {
    return view('content.dealer.notifications');
  }

  public function notificationsDataDealer()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    $notifications = $company->unreadNotifications->toArray();
    $notifications = array_merge(
      $notifications,
      auth()
        ->user()
        ->unreadNotifications->toArray()
    );
    return response()->json($notifications);
  }

  public function notificationRead($notification)
  {
    $notification = DatabaseNotification::findOrFail($notification);

    $notification->markAsRead();
  }

  public function notificationReadAll()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
    $company->unreadNotifications->markAsRead();
  }

  public function dealerNotificationReadAll()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
    $company->unreadNotifications->markAsRead();
  }
}
