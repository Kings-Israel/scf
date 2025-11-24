<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Models\ProgramVendorFee;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\PaymentRequest;
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
    $approved_invoices_data = [];
    $pending_invoices_data = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    $invoices = [];
    $all_invoices = [];
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

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices['closed'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->where('financing_status', 'closed')
      ->count();

    $invoices['approved'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->count();

    $invoices['pending'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['created', 'pending', 'submitted'])
      ->count();

    $invoices['disbursed'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'disbursed')
      ->count();

    $invoices['financed'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->count();

    $invoices['rejected'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->where(function ($query) {
        $query
          ->where('status', 'denied')
          ->orWhereIn('stage', ['internal_reject', 'rejected'])
          ->orWhere('financing_status', 'denied');
      })
      ->count();

    $invoices['past_due'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['disbursed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    $invoices['expired'] = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
      ->whereIn('status', ['expired'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    return view('content.vendor.dashboard.index', compact('invoices', 'months_formatted'));
  }

  public function invoicesData()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
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

    $invoices = [];
    $paid_payment_requests_data = [];
    $pending_payment_requests_data = [];

    $total_received = 0;
    $total_outstanding = 0;

    foreach ($months as $month) {
      array_push(
        $pending_payment_requests_data,
        round(
          Invoice::vendorFinancing()
            ->where('company_id', $company->id)
            ->where('status', 'approved')
            ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
            ->sum('calculated_total_amount'),
          2
        )
      );

      array_push(
        $paid_payment_requests_data,
        round(
          Invoice::vendorFinancing()
            ->where('company_id', $company->id)
            ->whereIn('financing_status', ['disbursed'])
            ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
            ->sum('calculated_total_amount'),
          2
        )
      );
    }

    $total_program_limit = 0;
    $total_utilized_limit = 0;
    $total_closed = 0;
    $total_available_limit = 0;
    $total_overdue_amount = 0;
    $total_pipeline_amount = 0;

    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $total_program_limit = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereIn('program_id', $programs_ids)
      ->sum('sanctioned_limit');
    $total_utilized_limit = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereIn('program_id', $programs_ids)
      ->sum('utilized_amount');
    $total_pipeline_amount = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereIn('program_id', $programs_ids)
      ->sum('pipeline_amount');

    $total_outstanding = PaymentRequestAccount::where('type', 'vendor_account')
      ->whereHas('paymentRequest', function ($query) use ($company) {
        $query->where('status', 'approved')->whereHas('invoice', function ($query) use ($company) {
          $query
            ->whereDate('due_date', '>', now()->format('Y-m-d'))
            ->where('company_id', $company->id)
            ->whereHas('program', function ($query) {
              $query->whereHas('programCode', function ($query) {
                $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
              });
            });
        });
      })
      ->sum('amount');

    $total_received = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->sum('disbursed_amount');

    foreach (
      Invoice::vendorFinancing()
        ->where('company_id', $company->id)
        ->where('financing_status', 'disbursed')
        ->whereDate('due_date', '<', now())
        ->get()
      as $invoice
    ) {
      $total_overdue_amount += $invoice->overdue_amount;
    }

    $total_available_limit = $total_program_limit - $total_utilized_limit - $total_pipeline_amount;

    return response()->json(
      [
        'paid_payment_requests_data' => $paid_payment_requests_data,
        'pending_payment_requests_data' => $pending_payment_requests_data,
        'total_program_limit' => $total_program_limit,
        'total_available_limit' => $total_available_limit,
        'total_utilized_limit' => $total_utilized_limit,
        'total_pipeline_amount' => $total_pipeline_amount,
        'total_closed' => $total_closed,
        'total_overdue_amount' => $total_overdue_amount,
        'total_received' => $total_received,
        'total_outstanding' => $total_outstanding,
      ],
      200
    );
  }

  public function invoiceStatusData()
  {
    $invoices = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices['paid'] = $company
      ->invoices()
      ->vendorFinancing()
      ->whereHas('paymentRequests', function ($query) {
        $query->where('status', 'paid');
      })
      ->count();

    $invoices['approved'] = $company
      ->invoices()
      ->vendorFinancing()
      ->where('program.programCode', Program::VENDOR_FINANCING_RECEIVABLE)
      ->where('status', 'approved')
      ->count();
    $invoices['pending'] = $company
      ->invoices()
      ->vendorFinancing()
      ->where('program.programCode', Program::VENDOR_FINANCING_RECEIVABLE)
      ->where('status', 'pending')
      ->count();

    return $invoices;
  }

  public function notifications()
  {
    return view('content.vendor.dashboard.notifications');
  }

  public function notificationsData()
  {
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
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
      ->activeVendorCompany()
      ->first();
    $company = Company::find($current_company->company_id);
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
    $company->unreadNotifications->markAsRead();
  }
}
