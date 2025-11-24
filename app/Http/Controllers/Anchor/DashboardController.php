<?php

namespace App\Http\Controllers\Anchor;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Models\PaymentRequestAccount;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Notifications\DatabaseNotification;

class DashboardController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    $vendors = [];

    $pending_invoices = [];

    $approved_invoices_data = [];
    $pending_invoices_data = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    $programs = [];

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

    // Format months
    $months_formatted = [];
    foreach ($months as $key => $month) {
      array_push($months_formatted, Carbon::parse($month)->format('M'));
    }

    $vendors = OdAccountsResource::collection(
      ProgramVendorConfiguration::whereHas('program', function ($query) use ($company) {
        $query
          ->whereHas('programCode', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
          })
          ->whereHas('anchor', function ($query) use ($company) {
            $query->where('companies.id', $company->id);
          });
      })
        ->join('companies', 'companies.id', '=', 'program_vendor_configurations.company_id')
        ->orderBy('companies.name', 'ASC')
        ->get()
    );

    return view('content.anchor.reverse-factoring.dashboard', compact('vendors', 'months_formatted'));
  }

  public function invoicesData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
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

    $vendors = [];
    $invoices = [];
    $program_ids = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];
    $closed_payment_requests_data = [];
    $total_program_limit = 0;
    $total_utilized_amount = 0;
    $total_pipeline_amount = 0;
    $total_overdue_amount = 0;

    // Get Vendors in all programs
    $vendors = ProgramVendorConfiguration::whereHas('program', function ($query) use ($company) {
      $query
        ->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        })
        ->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        });
    })->pluck('company_id');

    foreach ($months as $month) {
      array_push(
        $pending_payment_requests_data,
        round(
          Invoice::vendorFinancing()
            ->whereIn('company_id', $vendors)
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
          Invoice::vendorFinancing()
            ->whereIn('company_id', $vendors)
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
          Invoice::vendorFinancing()
            ->whereIn('company_id', $vendors)
            ->whereIn('financing_status', ['closed'])
            ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
            ->sum('calculated_total_amount'),
          2
        )
      );
    }

    $total_received = 0;
    $total_outstanding = 0;

    $total_outstanding = Invoice::vendorFinancing()
      ->whereIn('company_id', $vendors)
      ->whereHas('paymentRequests')
      ->whereDate('due_date', '>', now())
      ->whereIn('financing_status', ['financed'])
      ->sum('calculated_total_amount');

    $total_received = Invoice::vendorFinancing()
      ->whereIn('company_id', $vendors)
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->sum('calculated_total_amount');

    $overdue_invoices = InvoiceResource::collection(
      Invoice::vendorFinancing()
        ->whereIn('company_id', $vendors)
        ->where('financing_status', 'disbursed')
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->get()
    );

    foreach ($overdue_invoices as $overdue_invoice) {
      $total_overdue_amount += (int) $overdue_invoice->overdue_amount;
    }

    $programs = Program::whereHas('programCode', function ($query) {
      $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
    })
      ->whereHas('anchor', function ($query) use ($company) {
        $query->where('companies.id', $company->id);
      })
      ->get();

    foreach ($programs as $program) {
      $total_program_limit += $program->program_limit;
      $total_utilized_amount += $program->utilized_amount;
      $total_pipeline_amount += $program->pipeline_amount;
      array_push($program_ids, $program->id);
    }

    $total_available_amount = $total_program_limit - ($total_utilized_amount + $total_pipeline_amount);

    $invoices['closed'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->where('financing_status', 'closed')
      ->count();

    $invoices['approved'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->count();

    $invoices['pending'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'submitted'])
      ->count();

    $invoices['financed'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->count();

    $invoices['disbursed'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'disbursed')
      ->count();

    $invoices['rejected'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->where(function ($query) {
        $query
          ->where('status', 'denied')
          ->orWhereIn('stage', ['internal_reject', 'rejected'])
          ->orWhere('financing_status', 'denied');
      })
      ->count();

    $invoices['past_due'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    $invoices['expired'] = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
      ->where('status', 'expired')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    return response()->json(
      [
        'paid_payment_requests_data' => $paid_payment_requests_data,
        'pending_payment_requests_data' => $pending_payment_requests_data,
        'closed_payment_requests_data' => $closed_payment_requests_data,
        'total_received' => $total_received,
        'total_outstanding' => $total_outstanding,
        'total_program_limit' => $total_program_limit,
        'total_utilized_amount' => $total_utilized_amount,
        'total_pipeline_amount' => $total_pipeline_amount,
        'total_overdue_amount' => $total_overdue_amount,
        'total_available_limit' => $total_available_amount,
        'invoices' => $invoices,
      ],
      200
    );
  }

  public function invoiceStatusData()
  {
    $vendors = [];
    $invoices = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    // Get Vendors in all programs
    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
      ) {
        foreach ($program->getVendors() as $vendor) {
          array_push($vendors, $vendor);
        }
      }
    }

    foreach ($vendors as $company) {
      $all_invoices = $company->invoices()->vendorFinancing();
      $invoices['paid'] = $all_invoices
        ->whereHas('paymentRequests', function ($query) {
          $query->where('status', 'paid');
        })
        ->count();

      $invoices['approved'] = $all_invoices->where('status', 'approved')->count();
      $invoices['pending'] = $all_invoices->where('status', 'pending')->count();
    }

    return $invoices;
  }

  public function factoring()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendors = 0;
    $total_program_limit = 0;
    $total_vendor_limit = 0;
    $total_utilized_amount = 0;
    $total_pipeline_amount = 0;
    $total_overdue_amount = 0;

    $dealers = 0;
    $total_dealers_program_limit = 0;
    $total_dealers_limit = 0;
    $total_dealers_utilized_amount = 0;
    $total_dealers_pipeline_amount = 0;
    $total_dealers_overdue_amount = 0;

    $pending_invoices = [];

    $approved_invoices_data = [];
    $pending_invoices_data = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];
    $latest_payment_requests = [];

    $invoices = [];
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

    // Format months
    $months_formatted = [];
    foreach ($months as $key => $month) {
      array_push($months_formatted, Carbon::parse($month)->format('M'));
    }

    $program_ids = [];

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $factoring_programs = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
        });
      })
      ->get();

    foreach ($factoring_programs as $factoring_program) {
      $vendors += 1;
    }

    $programs = Program::whereHas('programCode', function ($query) {
      $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
    })
      ->whereHas('anchor', function ($query) use ($company) {
        $query->where('companies.id', $company->id);
      })
      ->get();

    foreach ($programs as $program) {
      $total_program_limit += $program->program_limit;
      $total_utilized_amount += $program->utilized_amount;
      $total_pipeline_amount += $program->pipeline_amount;
      $total_overdue_amount += $program->overdue_amount;
      array_push($program_ids, $program->id);
    }

    $invoices['closed'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->where('financing_status', 'closed')
      ->count();

    $invoices['approved'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->count();

    $invoices['pending'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'submitted'])
      ->count();

    $invoices['financed'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->count();

    $invoices['disbursed'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'disbursed')
      ->count();

    $invoices['rejected'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->where(function ($query) {
        $query
          ->where('status', 'denied')
          ->orWhereIn('stage', ['internal_reject', 'rejected'])
          ->orWhere('financing_status', 'denied');
      })
      ->count();

    $invoices['past_due'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    $invoices['expired'] = Invoice::factoring()
      ->where(function ($query) use ($company) {
        $query->where('company_id', $company->id);
      })
      ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
      ->whereIn('status', ['expired'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    return view(
      'content.anchor.factoring.dashboard',
      compact(
        'company',
        'vendors',
        'total_program_limit',
        'total_utilized_amount',
        'total_pipeline_amount',
        'total_overdue_amount',
        'invoices',
        'months_formatted'
      )
    );
  }

  public function factoringInvoicesData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

    $vendors = [];
    $invoices = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    // Get Vendors in all programs
    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
          $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
      ) {
        array_push($vendors, $company->id);
      } elseif ($program->programType->name == Program::DEALER_FINANCING) {
        foreach ($program->getDealers() as $vendor) {
          array_push($vendors, $vendor->id);
        }
      }
    }

    foreach ($months as $month) {
      array_push(
        $pending_payment_requests_data,
        Invoice::factoring()
          ->where('company_id', $company->id)
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );
    }

    foreach ($months as $month) {
      array_push(
        $paid_payment_requests_data,
        Invoice::factoring()
          ->where('company_id', $company->id)
          ->whereIn('financing_status', ['disbursed', 'closed'])
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );
    }

    $total_received = Invoice::factoring()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->sum('disbursed_amount');

    $total_outstanding = PaymentRequestAccount::where('type', 'vendor_account')
      ->whereHas('paymentRequest', function ($query) use ($company) {
        $query->where('status', 'approved')->whereHas('invoice', function ($query) use ($company) {
          $query
            ->whereDate('due_date', '>', now()->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->whereHas('program', function ($query) {
              $query->whereHas('programCode', function ($query) {
                $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]);
              });
            });
        });
      })
      ->sum('amount');

    return response()->json(
      [
        'paid_payment_requests_data' => $paid_payment_requests_data,
        'pending_payment_requests_data' => $pending_payment_requests_data,
        'total_received' => $total_received,
        'total_outstanding' => $total_outstanding,
      ],
      200
    );
  }

  public function notifications()
  {
    return view('content.anchor.reverse-factoring.notifications');
  }

  public function notificationsData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    return response()->json($company->unreadNotifications->toArray());
  }

  public function notificationsFactoring()
  {
    return view('content.anchor.factoring.notifications');
  }

  public function notificationsDataFactoring()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    return response()->json($company->unreadNotifications->toArray());
  }

  public function notificationsDealer()
  {
    return view('content.anchor.dealer.notifications');
  }

  public function notificationsDealerFactoring()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    return response()->json($company->unreadNotifications->toArray());
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
      ->activeAnchorCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
    $company->unreadNotifications->markAsRead();
  }

  public function factoringNotificationReadAll()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
    $company->unreadNotifications->markAsRead();
  }

  // Dealer Financing
  public function dealerFinancing()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendors = 0;
    $total_program_limit = 0;
    $total_vendor_limit = 0;
    $total_utilized_amount = 0;
    $total_pipeline_amount = 0;
    $total_overdue_amount = 0;

    $dealers = 0;
    $total_dealers_program_limit = 0;
    $total_dealers_limit = 0;
    $total_dealers_utilized_amount = 0;
    $total_dealers_pipeline_amount = 0;
    $total_dealers_overdue_amount = 0;

    $pending_invoices = [];

    $approved_invoices_data = [];
    $pending_invoices_data = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];
    $latest_payment_requests = [];

    $invoices = [];
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

    // Format months
    $months_formatted = [];
    foreach ($months as $key => $month) {
      array_push($months_formatted, Carbon::parse($month)->format('M'));
    }

    $program_ids = [];

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $dealer_financing_programs = ProgramVendorConfiguration::whereHas('program', function ($query) use ($company) {
      $query->whereHas('anchor', function ($query) use ($company) {
        $query->where('companies.id', $company->id);
      });
    })
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->get();

    foreach ($dealer_financing_programs as $dealer_financing_program) {
      $total_dealers_program_limit += $dealer_financing_program->sanctioned_limit;
      $total_dealers_utilized_amount += $dealer_financing_program->utilized_amount;
      $total_dealers_pipeline_amount += $dealer_financing_program->pipeline_amount;
      $total_dealers_overdue_amount += $dealer_financing_program->overdue_amount;
      $dealers += 1;
    }

    $dealer_programs_ids = ProgramVendorConfiguration::whereHas('program', function ($query) use ($company) {
      $query
        ->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        })
        ->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
    })->pluck('program_id');

    $invoices['closed'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->where('financing_status', 'closed')
      ->count();

    $invoices['approved'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->count();

    $invoices['pending'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'submitted'])
      ->count();

    $invoices['financed'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->count();

    $invoices['disbursed'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'disbursed')
      ->count();

    $invoices['rejected'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->where(function ($query) {
        $query
          ->where('status', 'denied')
          ->orWhereIn('stage', ['internal_reject', 'rejected'])
          ->orWhere('financing_status', 'denied');
      })
      ->count();

    $invoices['past_due'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    $invoices['expired'] = Invoice::factoringDealer()
      ->where(function ($query) use ($dealer_programs_ids, $company) {
        $query->whereIn('program_id', $dealer_programs_ids)->orWhere('company_id', $company->id);
      })
      ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
      ->whereIn('status', ['expired'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    return view(
      'content.anchor.dealer.dashboard',
      compact(
        'company',
        'total_program_limit',
        'total_utilized_amount',
        'total_pipeline_amount',
        'total_overdue_amount',
        'invoices',
        'months_formatted',
        'dealers',
        'total_dealers_program_limit',
        'total_dealers_limit',
        'total_dealers_utilized_amount',
        'total_dealers_pipeline_amount',
        'total_dealers_overdue_amount'
      )
    );
  }

  public function dealerInvoicesData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
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

    $vendors = [];
    $invoices = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];
    $closed_payment_requests_data = [];

    // Get Vendors in all programs
    foreach ($company->programs as $program) {
      if ($program->programType->name === Program::DEALER_FINANCING) {
        foreach ($program->getDealers() as $vendor) {
          array_push($vendors, $vendor->id);
        }
      }
    }

    foreach ($months as $month) {
      array_push(
        $pending_payment_requests_data,
        Invoice::dealerFinancing()
          ->wherehas('program', function ($query) use ($company) {
            $query->whereHas('anchor', function ($query) use ($company) {
              $query->where('companies.id', $company->id);
            });
          })
          ->whereIn('status', ['approved'])
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );
    }

    foreach ($months as $month) {
      array_push(
        $paid_payment_requests_data,
        Invoice::dealerFinancing()
          ->wherehas('program', function ($query) use ($company) {
            $query->whereHas('anchor', function ($query) use ($company) {
              $query->where('companies.id', $company->id);
            });
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereBetween('due_date', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );
    }

    foreach ($months as $month) {
      array_push(
        $closed_payment_requests_data,
        Invoice::dealerFinancing()
          ->wherehas('program', function ($query) use ($company) {
            $query->whereHas('anchor', function ($query) use ($company) {
              $query->where('companies.id', $company->id);
            });
          })
          ->whereIn('financing_status', ['closed'])
          ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );
    }

    $total_received = 0;
    $total_outstanding = 0;

    $total_received = Invoice::dealerFinancing()
      ->wherehas('program', function ($query) use ($company) {
        $query->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        });
      })
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->sum('calculated_total_amount');

    // Get invoices that have payments requests that aren't repayments
    $total_outstanding = Invoice::dealerFinancing()
      ->wherehas('program', function ($query) use ($company) {
        $query->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        });
      })
      ->whereHas('paymentRequests')
      ->whereDate('due_date', '>', now())
      ->whereIn('financing_status', ['financed'])
      ->sum('calculated_total_amount');

    return response()->json(
      [
        'paid_payment_requests_data' => $paid_payment_requests_data,
        'pending_payment_requests_data' => $pending_payment_requests_data,
        'closed_payment_requests_data' => $closed_payment_requests_data,
        'total_received' => $total_received,
        'total_outstanding' => $total_outstanding,
      ],
      200
    );
  }

  public function dealerNotifications()
  {
    return view('content.anchor.dealer.notifications');
  }

  public function notificationsDataDealer()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    return response()->json($company->unreadNotifications->toArray());
  }

  public function dealerNotificationRead($notification)
  {
    $notification = DatabaseNotification::findOrFail($notification);

    $notification->markAsRead();
  }

  public function dealerNotificationReadAll()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
    $company->unreadNotifications->markAsRead();
  }
}
