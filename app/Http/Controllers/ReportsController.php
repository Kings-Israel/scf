<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Bank;
use App\Models\User;
use App\Exports\Report;
use App\Models\Company;
use App\Models\CronLog;
use App\Models\Invoice;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Http\Resources\ActivityLog;
use App\Http\Resources\BankPaymentAccount as ResourcesBankPaymentAccount;
use App\Http\Resources\CbsTransactionResource;
use App\Http\Resources\InvoiceDetailsResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Http\Resources\PaymentRequestResource;
use App\Http\Resources\ProgramResource;
use App\Models\BankUser;
use App\Models\CompanyUser;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProgramVendorFee;
use App\Models\BankPaymentAccount;
use App\Models\InvoiceFee;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCompanyRole;
use App\Models\ProgramDealerDiscountRate;
use App\Models\ProgramDiscount;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;

class ReportsController extends Controller
{
  private function reportTypes(Bank $bank)
  {
    $reports = [
      'All Payments Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=all-payments-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=all-payments-report',
        'name' => 'all-payments-report',
        'permission' => auth()
          ->user()
          ->canany(['View Payments Report']),
      ],
      'Cron Logs' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=cron-logs',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=cron-logs',
        'name' => 'cron-logs',
        'permission' => auth()
          ->user()
          ->canany(['View Cron Logs']),
      ],
      'Final RTR Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=final-rtr-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=final-rtr-report',
        'name' => 'final-rtr-report',
        'permission' => auth()
          ->user()
          ->canany(['View Failed CBS transaction Report']),
      ],
      'Inactive Users Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=inactive-users-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=inactive-users-report',
        'name' => 'inactive-users-report',
        'permission' => auth()
          ->user()
          ->canany(['View Inactive Users List']),
      ],
      'Payments Pending Approval Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=loans-pending-approval-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=loans-pending-approval-report',
        'name' => 'payments-pending-approval-report',
        'permission' => auth()
          ->user()
          ->canany(['View Payments Pending Approval Report']),
      ],
      'Payments Pending Disbursal Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=loans-pending-disbursal-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=loans-pending-disbursal-report',
        'name' => 'payments-pending-disbursal-report',
        'permission' => auth()
          ->user()
          ->canany(['View Payments Pending Disbursal Report']),
      ],
      'Maturing Payments Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=maturing-payments-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=maturing-payments-report',
        'name' => 'maturing-payments-report',
        'permission' => auth()
          ->user()
          ->canany(['View Maturing Payments Report']),
      ],
      'Maturity Extended Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=maturity-extended-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=maturity-extended-report',
        'name' => 'maturity-extended-report',
        'permission' => auth()
          ->user()
          ->canany(['View Maturity Extended Report']),
      ],
      'Payments Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=payments-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=payments-report',
        'name' => 'payments-report',
        'permission' => auth()
          ->user()
          ->canany(['View Payments Report', 'View Payments']),
      ],
      'Rejected Payments Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=rejected-loans-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=rejected-loans-report',
        'name' => 'rejected-payments-report',
        'permission' => auth()
          ->user()
          ->canany(['View Rejected Payments Report']),
      ],
      'User ID Maintenance Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=user-maintenance-history-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=user-maintenance-history-report',
        'name' => 'user-maintenance-history-report',
        'permission' => auth()
          ->user()
          ->canany(['View User ID Maintenance History Report']),
      ],
      'Vendor\'s Daily Outstanding Balance' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vendor-daily-outstanding-balance-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vendor-daily-outstanding-balance-report',
        'name' => 'vendor-daily-outstanding-balance',
        'permission' => auth()
          ->user()
          ->canany(['Vendor\'s Daily Outstanding Balance']),
      ],
      'Dealers Daily Outstanding Balance' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=dealers-daily-outstanding-balance-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=dealers-daily-outstanding-balance-report',
        'name' => 'dealers-daily-outstanding-balance',
        'permission' => auth()
          ->user()
          ->canany(['Vendor\'s Daily Outstanding Balance']),
      ],
      'DF - Anchorwise Dealer Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-anchorwise-dealer-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-anchorwise-dealer-report',
        'name' => 'df-anchorwise-dealer-report',
        'permission' => auth()
          ->user()
          ->canany(['View Anchorwise Distributor Report']),
      ],
      'DF - Collection Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-collection-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-collection-report',
        'name' => 'df-collection-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Collection Report']),
      ],
      'DF - Drawdown Details Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=drawdown-details-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=drawdown-details-report',
        'name' => 'drawdown-details-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Drawdown Details Report']),
      ],
      'DF - Fees/Charges & Interest Sharing Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-fees-and-interest-sharing-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-fees-and-interest-sharing-report',
        'name' => 'df-fees-and-interest-sharing-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Fees/Charges & Interest Sharing Report']),
      ],
      'DF - Funding Limit Utilization Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-funding-limit-utilization-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-funding-limit-utilization-report',
        'name' => 'df-funding-limit-utilization-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Funding Limits Utilization Report']),
      ],
      'DF - Income Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-income-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-income-report',
        'name' => 'df-income-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Income Report']),
      ],
      'DF - Monthly Utilization and Outstanding Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-monthly-utilization-and-outstanding-report',
        'pdf_link' =>
          route('reports.pdf.export', ['bank' => $bank]) . '?type=df-monthly-utilization-and-outstanding-report',
        'name' => 'df-monthly-utilization-and-outstanding-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Monthly Utilization & Outstanding Report']),
      ],
      'DF - OD Ledger Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-od-ledger-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-od-ledger-report',
        'name' => 'df-od-ledger-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - OD Ledger Report']),
      ],
      'DF - Overdue Invoices Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-overdue-invoices-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-overdue-invoices-report',
        'name' => 'df-overdue-invoices-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Overdue Invoices Report']),
      ],
      'VF - Overdue Invoices Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-overdue-invoices-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-overdue-invoices-report',
        'name' => 'vf-overdue-invoices-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Overdue Invoices Report']),
      ],
      'Reconciliation Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=reconciliation-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=reconciliation-report',
        'name' => 'reconciliation-report',
        'permission' => auth()
          ->user()
          ->canany(['Reconciliation Report']),
      ],
      'DF - Overdue Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-overdue-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-overdue-report',
        'name' => 'df-overdue-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Overdue Report']),
      ],
      'VF - Anchorwise Vendor Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-anchorwise-vendor-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-anchorwise-vendor-report',
        'name' => 'vf-anchorwise-vendor-report',
        'permission' => auth()
          ->user()
          ->canany(['View Anchorwise Vendor Report']),
      ],
      'VF - Income Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-income-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-income-report',
        'name' => 'vf-income-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Income Report']),
      ],
      'Factoring Income Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=factoring-income-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=factoring-income-report',
        'name' => 'factoring-income-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Income Report']),
      ],
      'VF - Funding Limit Utilization Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-funding-limit-utilization-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-funding-limit-utilization-report',
        'name' => 'vf-funding-limit-utilization-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Funding Limits Utilization Report']),
      ],
      'VF - Program Mapping Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-program-mapping-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-program-mapping-report',
        'name' => 'vf-program-mapping-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Program Mapping Report']),
      ],
      'DF - Program Mapping Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-program-mapping-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-program-mapping-report',
        'name' => 'df-program-mapping-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Program Mapping Report']),
      ],
      'VF - Fees/Charges & Interest Sharing Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-fees-and-interest-sharing-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-fees-and-interest-sharing-report',
        'name' => 'vf-fees-and-interest-sharing-report',
        'permission' => auth()
          ->user()
          ->canany(['IF - Fees/Charges & Interest Sharing Report']),
      ],
      'VF - Potential Financing Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-potential-financing-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-potential-financing-report',
        'name' => 'vf-potential-financing-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Potential Financing Report']),
      ],
      'DF - Potential Financing Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-potential-financing-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-potential-financing-report',
        'name' => 'df-potential-financing-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Potential Financing Report']),
      ],
      'DF - Programs Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=dealer-financing-programs-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=dealer-financing-programs-report',
        'name' => 'dealer-financing-programs-report',
        'permission' => auth()
          ->user()
          ->canany(['View DF - Programs Report']),
      ],
      'VF - Programs Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vendor-financing-programs-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vendor-financing-programs-report',
        'name' => 'vendor-financing-programs-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Programs Report']),
      ],
      'VF - Repayment Details Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=vf-repayment-details-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=vf-repayment-details-report',
        'name' => 'vf-repayment-details-report',
        'permission' => auth()
          ->user()
          ->canany(['View VF - Repayment Details Report']),
      ],
      'DF - Repayment Details Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=df-repayment-details-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=df-repayment-details-report',
        'name' => 'df-repayment-details-report',
        'permission' => auth()
          ->user()
          ->canany(['View VF - Repayment Details Report']),
      ],
      'Users and Roles Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=users-and-roles-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=users-and-roles-report',
        'name' => 'users-and-roles-report',
        'permission' => auth()
          ->user()
          ->canany(['View Users and Roles Report']),
      ],
      'IF - Payment Details Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=if-payment-details-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=if-payment-details-report',
        'name' => 'if-payment-details-report',
        'permission' => auth()
          ->user()
          ->canany(['View IF - Payment Details Report']),
      ],
      'Bank GLs Report' => [
        'link' => route('reports.export', ['bank' => $bank]) . '?type=bank-gls-report',
        'pdf_link' => route('reports.pdf.export', ['bank' => $bank]) . '?type=bank-gls-report',
        'name' => 'bank-gls-report',
        'permission' => auth()
          ->user()
          ->canany(['View Bank GLs Report']),
      ],
    ];

    return $reports;
  }

  public function index(Bank $bank, Request $request)
  {
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

    return view('content.bank.reports.index', [
      'months' => $months,
      'months_formatted' => $months_formatted,
      'bank' => $bank,
      'reports' => collect(self::reportTypes($bank))->sortKeys(),
    ]);
  }

  public function graphData(Request $request, Bank $bank)
  {
    $timeline = $request->query('timeline');
    $program_type = $request->query('program_type');

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

    // Income
    $income_data = [];
    $interest_income_data = [];
    $fees_income_data = [];
    if ($program_type == 'factoring') {
      foreach ($months as $key => $month) {
        $discount_amount_per_month = 0;
        $interest_amount_per_month = 0;
        $fees_amount_per_month = 0;

        $discount_amount_per_month = CbsTransaction::factoring()
          ->where('bank_id', $bank->id)
          ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
          ->where('status', 'Successful')
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($income_data, ceil($discount_amount_per_month));

        $interest_amount_per_month = CbsTransaction::factoring()
          ->where('bank_id', $bank->id)
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($interest_income_data, ceil($interest_amount_per_month));

        $fees_amount_per_month = CbsTransaction::factoring()
          ->where('bank_id', $bank->id)
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query
                //     ->whereHas('paymentRequest', function ($query) {
                //       $query->whereHas('invoice', function ($query) {
                //         $query->where('financing_status', 'closed');
                //       });
                //     });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query
                //     ->whereHas('paymentRequest', function ($query) {
                //       $query->whereHas('invoice', function ($query) {
                //         $query->where('financing_status', 'closed');
                //       });
                //     });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::factoring()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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
            break;
        }
      }
    } elseif ($program_type == 'dealer_financing') {
      foreach ($months as $key => $month) {
        $discount_amount_per_month = 0;
        $interest_amount_per_month = 0;
        $fees_amount_per_month = 0;

        $discount_amount_per_month = CbsTransaction::dealerFinancing()
          ->where('bank_id', $bank->id)
          ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($income_data, ceil($discount_amount_per_month));

        $interest_amount_per_month = CbsTransaction::dealerFinancing()
          ->where('bank_id', $bank->id)
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($interest_income_data, ceil($interest_amount_per_month));

        $fees_amount_per_month = CbsTransaction::dealerFinancing()
          ->where('bank_id', $bank->id)
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query
                //     ->whereHas('paymentRequest', function ($query) {
                //       $query->whereHas('invoice', function ($query) {
                //         $query->where('financing_status', 'closed');
                //       });
                //     });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::dealerFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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
            break;
        }
      }
    } else {
      foreach ($months as $key => $month) {
        $discount_amount_per_month = 0;
        $interest_amount_per_month = 0;
        $fees_amount_per_month = 0;

        $discount_amount_per_month = CbsTransaction::vendorFinancing()
          ->where('bank_id', $bank->id)
          ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
          ->where('status', 'Successful')
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($income_data, ceil($discount_amount_per_month));

        $interest_amount_per_month = CbsTransaction::vendorFinancing()
          ->where('bank_id', $bank->id)
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($interest_income_data, ceil($interest_amount_per_month));

        $fees_amount_per_month = CbsTransaction::vendorFinancing()
          ->where('bank_id', $bank->id)
          // ->where(function ($query) {
          //   $query->whereHas('paymentRequest', function ($query) {
          //     $query->whereHas('invoice', function ($query) {
          //       $query->where('financing_status', 'closed');
          //     });
          //   });
          // })
          ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount');

        array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query
                //     ->whereHas('paymentRequest', function ($query) {
                //       $query->whereHas('invoice', function ($query) {
                //         $query->where('financing_status', 'closed');
                //       });
                //     });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query
                //     ->whereHas('paymentRequest', function ($query) {
                //       $query->whereHas('invoice', function ($query) {
                //         $query->where('financing_status', 'closed');
                //       });
                //     });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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

            $income_data = [];
            $interest_income_data = [];
            $fees_income_data = [];
            foreach ($months as $key => $month) {
              $discount_amount_per_month = 0;
              $interest_amount_per_month = 0;
              $fees_amount_per_month = 0;

              $discount_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                ->where('status', 'Successful')
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($income_data, ceil($discount_amount_per_month));

              $interest_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($interest_income_data, ceil($interest_amount_per_month));

              $fees_amount_per_month = CbsTransaction::vendorFinancing()
                ->where('bank_id', $bank->id)
                // ->where(function ($query) {
                //   $query->whereHas('paymentRequest', function ($query) {
                //     $query->whereHas('invoice', function ($query) {
                //       $query->where('financing_status', 'closed');
                //     });
                //   });
                // })
                ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount');

              array_push($fees_income_data, ceil($fees_amount_per_month));
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
            break;
        }
      }
    }

    return response()->json(
      [
        'months' => $months,
        'months_formatted' => $months_formatted,
        'income_data' => $income_data,
        'interest_income_data' => $interest_income_data,
        'fees_income_data' => $fees_income_data,
      ],
      200
    );
  }

  public function invoiceStatusData(Request $request, Bank $bank)
  {
    $program_type = $request->query('program_type');

    switch ($program_type) {
      case 'factoring':
        // Factoring Closed
        $invoices['closed'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('financing_status', 'closed')
          ->count();
        // Factoring Approved
        $invoices['approved'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('status', 'approved')
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
          ->count();
        // Factoring Pending
        $invoices['pending'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('status', ['pending', 'submitted'])
          ->count();
        // Factoring Disbursed
        $invoices['disbursed'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->where('financing_status', 'disbursed')
          ->count();
        // Factoring Financed
        $invoices['financed'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->where('financing_status', 'financed')
          ->count();
        // Factoring Rejected
        $invoices['rejected'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where(function ($query) {
            $query
              ->where('status', 'denied')
              ->orWhereIn('stage', ['internal_reject', 'rejected'])
              ->orWhere('financing_status', 'denied');
          })
          ->count();
        // Factoring Past Due
        $invoices['past_due'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->count();
        // Factoring Expired
        $invoices['expired'] = Invoice::factoring()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->count();
        break;
      case 'dealer_financing':
        // Dealer Financing Closed
        $invoices['closed'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('financing_status', 'closed')
          ->count();
        // Dealer Financing Approved
        $invoices['approved'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('status', 'approved')
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('financing_status', ['pending', 'submitted'])
          ->count();
        // Dealer Financing Pending
        $invoices['pending'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('status', ['pending', 'submitted'])
          ->count();
        // Dealer Financing Financed
        $invoices['financed'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->where('financing_status', 'financed')
          ->count();
        // Dealer Financing Disbursed
        $invoices['disbursed'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->where('financing_status', 'disbursed')
          ->count();
        // Dealer Financing Rejected
        $invoices['rejected'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where(function ($query) {
            $query
              ->where('status', 'denied')
              ->orWhereIn('stage', ['internal_reject', 'rejected'])
              ->orWhere('financing_status', 'denied');
          })
          ->count();
        // Dealer Financing Past Due
        $invoices['past_due'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->count();
        // Dealer Financing Expired
        $invoices['expired'] = Invoice::dealerFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
          ->where('status', 'expired')
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->count();
        break;
      default:
        // VFR Closed
        $invoices['closed'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('financing_status', 'closed')
          ->count();
        // VFR Approved
        $invoices['approved'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('status', 'approved')
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('financing_status', ['pending', 'submitted'])
          ->count();
        // VFR Pending
        $invoices['pending'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('status', ['pending', 'submitted'])
          ->count();
        // VFR Financed
        $invoices['financed'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->where('financing_status', 'financed')
          ->count();
        // VFR Disbursed
        $invoices['disbursed'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->where('financing_status', 'disbursed')
          ->count();
        // VFR Rejected
        $invoices['rejected'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where(function ($query) {
            $query
              ->where('status', 'denied')
              ->orWhereIn('stage', ['internal_reject', 'rejected'])
              ->orWhere('financing_status', 'denied');
          })
          ->count();
        // VFR Past Due
        $invoices['past_due'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereIn('financing_status', ['disbursed'])
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->count();
        // VFR Expired
        $invoices['expired'] = Invoice::vendorFinancing()
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->whereIn('financing_status', ['pending', 'submitted', 'financed'])
          ->where('status', 'expired')
          ->whereDate('due_date', '<', now()->format('Y-m-d'))
          ->count();
        break;
    }

    return response()->json(
      [
        'invoices' => $invoices,
      ],
      200
    );
  }

  public function revenuePieGraphData(Request $request, Bank $bank)
  {
    $filter = $request->query('filter');
    $program_type = $request->query('program_type');

    if ($program_type == 'factoring') {
      switch ($filter) {
        case 'past_ten_years':
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_five_years':
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_three_years':
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_six_months':
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subMonths(4)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(4)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(4)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_three_months':
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;
        case 'past_one_month':
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );
          break;
        default:
          $data['discount'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::factoring()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );
          break;
      }
    } elseif ($program_type == 'dealer_financing') {
      switch ($filter) {
        case 'past_ten_years':
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;
        case 'past_five_years':
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_three_years':
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_six_months':
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subMonths(5)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(5)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(5)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_three_months':
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_one_month':
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );
          break;
        default:
          $data['discount'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::dealerFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );
          break;
      }
    } else {
      switch ($filter) {
        case 'past_ten_years':
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(9)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_five_years':
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(4)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_three_years':
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subYears(2)
                  ->startOfYear(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_six_months':
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subMonths(5)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(5)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(5)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_three_months':
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [
                now()
                  ->subMonths(2)
                  ->startOfMonth(),
                now(),
              ])
              ->sum('amount'),
            2
          );
          break;

        case 'past_one_month':
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfMonth(), now()])
              ->sum('amount'),
            2
          );
          break;
        default:
          $data['discount'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              ->where('status', 'Successful')
              ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST])
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );

          $data['penal'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );

          $data['fees'] = round(
            CbsTransaction::vendorFinancing()
              ->where('bank_id', $bank->id)
              // ->where(function ($query) {
              //   $query->whereHas('paymentRequest', function ($query) {
              //     $query->whereHas('invoice', function ($query) {
              //       $query->where('financing_status', 'closed');
              //     });
              //   });
              // })
              ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES])
              ->where('status', 'Successful')
              ->whereBetween('created_at', [now()->startOfYear(), now()])
              ->sum('amount'),
            2
          );
          break;
      }
    }

    return response()->json($data, 200);
  }

  public function requestsTrackerData(Bank $bank, Request $request)
  {
    $timeline = $request->query('filter');

    $requests_count = PaymentRequest::whereHas('cbsTransactions', function ($query) {
      $query->whereIn('transaction_type', [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
      ]);
    })
      ->whereHas('invoice', function ($query) use ($bank) {
        $query->whereHas('program', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id);
        });
      })
      ->when($timeline && $timeline != '', function ($query) use ($timeline) {
        switch ($timeline) {
          case 'past_ten_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(9)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_five_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(4)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_six_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(5)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_one_month':
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfMonth()
                ->format('Y-m-d'),
              now()
                ->endOfMonth()
                ->format('Y-m-d'),
            ]);
            break;
          default:
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfYear()
                ->format('Y-m-d'),
              now()
                ->endOfYear()
                ->format('Y-m-d'),
            ]);
            break;
        }
      })
      ->count();

    // Vendor Financing Requests Count
    $vendor_financing_requests_count = PaymentRequest::whereHas('cbsTransactions', function ($query) {
      $query->whereIn('transaction_type', [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
      ]);
    })
      ->whereHas('invoice', function ($query) use ($bank) {
        $query->whereHas('program', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id)->whereHas('programCode', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
          });
        });
      })
      ->when($timeline && $timeline != '', function ($query) use ($timeline) {
        switch ($timeline) {
          case 'past_ten_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(9)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_five_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(4)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_six_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(5)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_one_month':
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfMonth()
                ->format('Y-m-d'),
              now()
                ->endOfMonth()
                ->format('Y-m-d'),
            ]);
            break;
          default:
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfYear()
                ->format('Y-m-d'),
              now()
                ->endOfYear()
                ->format('Y-m-d'),
            ]);
            break;
        }
      })
      ->count();

    // Vendor Financing Factoring Requests Count
    $factoring_requests_count = PaymentRequest::whereHas('cbsTransactions', function ($query) {
      $query->whereIn('transaction_type', [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
      ]);
    })
      ->whereHas('invoice', function ($query) use ($bank) {
        $query->whereHas('program', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id)->whereHas('programCode', function ($query) {
            $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]);
          });
        });
      })
      ->when($timeline && $timeline != '', function ($query) use ($timeline) {
        switch ($timeline) {
          case 'past_ten_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(9)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_five_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(4)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_six_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(5)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_one_month':
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfMonth()
                ->format('Y-m-d'),
              now()
                ->endOfMonth()
                ->format('Y-m-d'),
            ]);
            break;
          default:
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfYear()
                ->format('Y-m-d'),
              now()
                ->endOfYear()
                ->format('Y-m-d'),
            ]);
            break;
        }
      })
      ->count();

    // Dealer Financing Requests Count
    $dealer_financing_requests_count = PaymentRequest::whereHas('cbsTransactions', function ($query) {
      $query->whereIn('transaction_type', [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
      ]);
    })
      ->whereHas('invoice', function ($query) use ($bank) {
        $query->whereHas('program', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id)->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        });
      })
      ->when($timeline && $timeline != '', function ($query) use ($timeline) {
        switch ($timeline) {
          case 'past_ten_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(9)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_five_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(4)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_six_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(5)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_one_month':
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfMonth()
                ->format('Y-m-d'),
              now()
                ->endOfMonth()
                ->format('Y-m-d'),
            ]);
            break;
          default:
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfYear()
                ->format('Y-m-d'),
              now()
                ->endOfYear()
                ->format('Y-m-d'),
            ]);
            break;
        }
      })
      ->count();

    // Closed Requests Percentage
    $closed_requests_count = PaymentRequest::whereHas('cbsTransactions', function ($query) {
      $query->whereIn('transaction_type', [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
      ]);
    })
      ->when($timeline && $timeline != '', function ($query) use ($timeline) {
        switch ($timeline) {
          case 'past_ten_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(9)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_five_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(4)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_years':
            $query->whereBetween('payment_request_date', [
              now()
                ->subYears(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_six_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(5)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_three_months':
            $query->whereBetween('payment_request_date', [
              now()
                ->subMonths(2)
                ->format('Y-m-d'),
              now()->format('Y-m-d'),
            ]);
            break;
          case 'past_one_month':
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfMonth()
                ->format('Y-m-d'),
              now()
                ->endOfMonth()
                ->format('Y-m-d'),
            ]);
            break;
          default:
            $query->whereBetween('payment_request_date', [
              now()
                ->startOfYear()
                ->format('Y-m-d'),
              now()
                ->endOfYear()
                ->format('Y-m-d'),
            ]);
            break;
        }
      })
      ->whereHas('invoice', function ($query) use ($bank) {
        $query
          ->whereHas('program', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->where('financing_status', 'closed');
      })
      ->count();

    $closed_requests_percent = 0;

    if ($requests_count > 0) {
      $closed_requests_percent = ceil(($closed_requests_count / $requests_count) * 100);
    }

    return response()->json([
      'requests_count' => $requests_count,
      'vendor_financing_requests_count' => $vendor_financing_requests_count,
      'factoring_requests_count' => $factoring_requests_count,
      'dealer_financing_requests_count' => $dealer_financing_requests_count,
      'closed_requests_count' => $closed_requests_percent,
    ]);
  }

  public function ledger(Bank $bank)
  {
    return view('content.bank.reports.ledger');
  }

  public function logs(Bank $bank)
  {
    return view('content.bank.reports.logs', compact('bank'));
  }

  public function logsData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $user_type = $request->query('user_type');
    $description = $request->query('description');
    $from_date = $request->query('start_date');
    $to_date = $request->query('end_date');

    $logs = Activity::with('subject', 'causer')
      ->inLog($bank->id)
      ->when($user_type && $user_type != '', function ($query) use ($user_type) {
        $query->where('properties->user_type', $user_type);
      })
      ->when($description && $description != '', function ($query) use ($description) {
        $query->where(function ($query) use ($description) {
          $query
            ->where('description', 'LIKE', '%' . $description . '%')
            ->orWhereHas('causer', function ($query) use ($description) {
              $query->where('name', 'LIKE', '%' . $description . '%');
            });
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('created_at', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('created_at', '<=', $to_date);
      })
      ->latest()
      ->paginate($per_page);

    $logs = ActivityLog::collection($logs)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['data' => $logs], 200);
    }
  }

  public function report(Bank $bank, $report)
  {
    $report = collect(self::reportTypes($bank))->filter(function ($type, $value) use ($report) {
      return $type['name'] == $report;
    });

    return view('content.bank.reports.report', compact('report', 'bank'));
  }

  public function exportReports(Request $request, Bank $bank)
  {
    $date = now()->format('Y-m-d');
    $report = $request->query('type');

    switch ($report) {
      case 'logs':
        $user_type = $request->query('user_type');
        $description = $request->query('description');
        $from_date = $request->query('start_date');
        $to_date = $request->query('end_date');

        $headers = ['User', 'User Type', 'Description', 'IP', 'Device Details', 'Date & Time'];
        $data = [];

        $logs = ActivityLog::collection(
          Activity::with('subject', 'causer')
            ->inLog($bank->id)
            ->when($user_type && $user_type != '', function ($query) use ($user_type) {
              $query->where('properties->user_type', $user_type);
            })
            ->when($description && $description != '', function ($query) use ($description) {
              $query->where('description', 'LIKE', '%' . $description . '%');
            })
            ->when($from_date && $from_date != '', function ($query) use ($from_date) {
              $query->whereDate('created_at', '>=', $from_date);
            })
            ->when($to_date && $to_date != '', function ($query) use ($to_date) {
              $query->whereDate('created_at', '<=', $to_date);
            })
            ->latest()
            ->get()
        );
        foreach ($logs as $key => $log) {
          $description = '';
          if ($log->subject && $log->subject_type == Company::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->name;
          } elseif ($log->subject && $log->subject_type == Invoice::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->invoice_number;
          } elseif ($log->subject && $log->subject_type == PaymentRequest::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->reference_number;
          } elseif ($log->subject && $log->subject_type == CbsTransaction::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->id;
          } elseif ($log->subject && $log->subject_type == BankPaymentAccount::class) {
            $description = $log->causer->name . ' ' . $log->description;
          }

          $data[$key]['User'] = $log->causer->name . ' (' . $log->causer->email . ')';
          $data[$key]['User Type'] =
            !empty($log->properties) && array_key_exists('user_type', $log->properties->toArray())
              ? $log->properties['user_type']
              : '-';
          $data[$key]['Description'] = $description;
          $data[$key]['IP'] =
            !empty($log->properties) && array_key_exists('ip', $log->properties->toArray())
              ? $log->properties['ip']
              : '-';
          $data[$key]['Device Details'] =
            !empty($log->properties) && array_key_exists('device_info', $log->properties->toArray())
              ? $log->properties['device_info']
              : '-';
          $data[$key]['Date & Time'] = $log->created_at->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'ActivityLogs_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('ActivityLogs_' . $date . '.csv');
        break;
      case 'all-payments-report':
        $report_data = $this->allPaymentsReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No.',
          'Company',
          'Anchor',
          'Payment Date',
          'Due Date',
          'Invoice Amount',
          'Payment Amount',
          'Credit Note Amount',
          'Eligibility',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $credit_note_amount = InvoiceFee::where('invoice_id', $report->paymentRequest?->invoice_id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['System ID'] = $report->id;
          $data[$key]['Invoice No'] = $report->paymentRequest->invoice->invoice_number;
          $data[$key]['Company'] = $report->paymentRequest->invoice->company->name;
          $data[$key]['Anchor'] = $report->paymentRequest->invoice->buyer
            ? $report->paymentRequest->invoice->buyer->name
            : $report->paymentRequest->invoice->program->anchor->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->paymentRequest?->payment_request_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->paymentRequest->invoice->due_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->paymentRequest?->invoice?->invoice_total_amount);
          $data[$key]['Payment Amount'] = number_format($report->paymentRequest?->amount);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Eligibility'] = $report->paymentRequest?->invoice?->program->eligibility;
          $data[$key]['Status'] = Str::title($report->status);
          $data[$key]['Transaction Reference'] = $report->transaction_reference;
        }

        Excel::store(new Report($headers, $data), 'All_payment_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('All_payment_report_' . $date . '.csv');
        break;
      case 'drawdown-details-report':
        $report_data = $this->drawdownDetails($bank, $request);

        $headers = [
          'Payment Reference No',
          'Invoice No',
          'Dealer',
          'Anchor',
          'Request Data',
          'Due Date',
          'Invoice Amount',
          'Payment Amount',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Payment Reference No'] = $report->reference_number;
          $data[$key]['Invoice No'] = $report->invoice->invoice_number;
          $data[$key]['Dealer'] = $report->invoice->company->name;
          $data[$key]['Anchor'] = $report->invoice->buyer
            ? $report->invoice->buyer->name
            : $report->invoice->program->anchor->name;
          $data[$key]['Request Date'] = Carbon::parse($report->payment_request_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->invoice->due_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->invoice->invoice_total_amount);
          $data[$key]['Payment Amount'] = number_format($report->amount);
          $data[$key]['Status'] = Str::title($report->status);
        }

        Excel::store(new Report($headers, $data), 'DF_drawdown_details_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_drawdown_details_report_' . $date . '.csv');

        break;
      case 'inactive-users-report':
        $report_data = $this->inactiveUsersReport($bank, $request);

        $headers = ['Name', 'Role', 'Email', 'Phone Number', 'Companies', 'Last Login'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Name'] = $report->name;
          $data[$key]['Role'] = $report->roles?->first()?->name;
          $data[$key]['Email'] = $report->email;
          $data[$key]['Phone Number'] = $report->phone_number;
          $user_companies = $report->mappedCompanies?->map(fn($company) => $company->name);
          $data[$key]['Companies'] = collect($user_companies)->join(', ', 'and ');
          $data[$key]['Last Login'] = $report->last_login ? Carbon::parse($report->last_login)->format('d M Y') : '-';
        }

        Excel::store(new Report($headers, $data), 'Inactive_users_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Inactive_users_report_' . $date . '.csv');
        break;
      case 'payments-report':
        $report_data = $this->paymentsReport($bank, $request);

        $headers = [
          'Debit From',
          'Credit To',
          'Amount',
          'Invoice No',
          'Pay Date',
          'Transaction Date',
          'Product Type',
          'Payment Service',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Debit From'] = $report->debit_from_account;
          $data[$key]['Credit To'] = $report->credit_to_account;
          $data[$key]['Amount'] = number_format($report->amount);
          $data[$key]['Invoice No'] = $report->paymentRequest?->invoice?->invoice_number;
          $data[$key]['Pay Date'] = Carbon::parse($report->pay_date)->format('d M Y');
          $data[$key]['Transaction Date'] = Carbon::parse($report->transaction_date)->format('d M Y');
          $data[$key]['Product Type'] = Str::title($report->product);
          $data[$key]['Payment Service'] = Str::title($report->transaction_type);
          $data[$key]['Status'] = Str::title($report->status);
        }

        Excel::store(new Report($headers, $data), 'Payments_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Payments_report_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
          'Content-Type' => 'text/csv',
        ]);
        break;
      case 'dealer-financing-programs-report':
        $report_data = $this->dealerFinancingPrograms($bank, $request);

        $headers = ['Program Name', 'Anchor Name', 'Status', 'Total Program Limit', 'Utilized Amount'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Program Name'] = $report->name;
          $data[$key]['Anchor Name'] = $report->anchor->name;
          $data[$key]['Status'] = Str::title($report->account_status);
          $data[$key]['Total Program Limit'] = number_format($report->program_limit);
          $data[$key]['Utilized Amount'] = $report->utilized_amount;
        }

        Excel::store(new Report($headers, $data), 'DF_programs_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_programs_report_' . $date . '.csv');
        break;
      case 'rejected-loans-report':
        $report_data = $this->rejectedLoans($bank, $request);

        $headers = [
          'Anchor',
          'Vendor',
          'Request Date',
          'Rejection Date',
          'PI No',
          'Invoice Amount',
          'Payment Amount',
          'Rejection Reason',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Anchor'] = $report->invoice->buyer
            ? $report->invoice->buyer->name
            : $report->invoice->program->anchor->name;
          $data[$key]['Vendor'] = $report->invoice->company->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->payment_request_date)->format('d M Y');
          $data[$key]['Rejection Date'] = $report->updated_at->format('d M Y');
          $data[$key]['PI No'] = $report->invoice->pi_number;
          $data[$key]['Invoice Amount'] = number_format($report->invoice->invoice_total_amount);
          $data[$key]['Payment Amount'] = number_format($report->amount);
          $data[$key]['Rejection Reason'] = $report->rejected_reason;
        }

        Excel::store(new Report($headers, $data), 'Rejected_loans_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Rejected_loans_report_' . $date . '.csv');
        break;
      case 'df-anchorwise-dealer-report':
        $report_data = $this->dfAnchorwiseDealerReport($bank, $request);

        $headers = ['Anchor', 'Total Dealers', 'Active Dealers', 'Passive Dealers', 'Percentage of Active Dealers'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Anchor'] = $report->id;
          $data[$key]['Total Dealers'] = $report->dealers;
          $data[$key]['Active Dealers'] = $report->active_dealers;
          $data[$key]['Passive Dealers'] = $report->passive_dealers;
          $data[$key]['Percentage of Active Dealers'] = $report->active_dealers_percent;
        }

        Excel::store(new Report($headers, $data), 'DF_anchorwise_dealer_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_anchorwise_dealer_report_' . $date . '.csv');
        break;
      case 'df-dealer-classification-report':
        $report_data = $this->dfDealerClassificationReport($bank, $request);

        $headers = ['Dealer', 'Branch Code', 'Sanctioned Limit', 'Limit Expiry Date', 'Limit Utilized', 'DPD Days'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Dealer'] = $report->name;
          $data[$key]['Branch Code'] = $report->branch_code;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Limit Expiry Date'] = Carbon::parse($report->limit_expiry)->format('d M Y');
          $data[$key]['Limit Utilized'] = number_format($report->utilized_amount);
          $data[$key]['DPD'] = $report->dpd_days;
        }

        Excel::store(new Report($headers, $data), 'DF_dealer_classification_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_dealer_classification_report_' . $date . '.csv');
        break;
      case 'vendor-financing-programs-report':
        $report_data = $this->vendorFinancingPrograms($bank, $request);

        $headers = [
          'Program Name',
          'Anchor Name',
          'Status',
          'Total Program Limit',
          'Utilized Limit',
          'Pipeline Requests',
          'Available Amount',
          'Base Rate Consideration',
          'Anchor Discount Bearing',
          'Eligibility',
          'Total Mapped Companies',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          if ($report->programType->name === Program::DEALER_FINANCING) {
            $program_discount_details = ProgramDealerDiscountRate::where('program_id', $report->id)->first();
          } else {
            $program_discount_details = ProgramDiscount::where('program_id', $report->id)->first();
          }

          $data[$key]['Program Name'] = $report->name;
          $data[$key]['Anchohr Name'] = $report->anchor->name;
          $data[$key]['Status'] = $report->account_status;
          $data[$key]['Total Program Limit'] = number_format($report->program_limit, 2);
          $data[$key]['Utilized Limit'] = number_format($report->utilized_amount, 2);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount, 2);
          $data[$key]['Available Amount'] = number_format(
            $report->program_limit - $report->pipeline_amount - $report->utilized_amount,
            2
          );
          $data[$key]['Base Rate Consideration'] = number_format($program_discount_details->benchmark_rate) . '%';
          $data[$key]['Anchor Discount Bearing'] =
            number_format($program_discount_details->anchor_discount_bearing) . '%';
          $data[$key]['Eligibility'] = number_format($report->eligibility) . '%';
          if ($report->programType->name === Program::DEALER_FINANCING) {
            $data[$key]['Total Mapped Companies'] = $report->getDealers()->count();
          } else {
            if ($report->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
              $data[$key]['Total Mapped Companies'] = $report->getVendors()->count();
            } else {
              $data[$key]['Total Mapped Companies'] = $report->getBuyers()->count();
            }
          }
        }

        Excel::store(new Report($headers, $data), 'VF_programs_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_programs_report_' . $date . '.csv');
        break;
      case 'vf-anchorwise-vendor-report':
        $report_data = $this->vfAnchorwiseVendorReport($bank, $request);

        $headers = ['Anchor', 'Total Vendors', 'Active Vendors', 'Passive Vendors', 'Percentage of Active Vendors'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $vendors_count = ProgramCompanyRole::where('program_id', $report->id)->count() - 1; // -1 to remove the anchor count
          $active_vendors_count = ProgramVendorConfiguration::where('program_id', $report->id)
            ->where('utilized_amount', '>', 0)
            ->count();
          $passive_vendors_count = ProgramVendorConfiguration::where('program_id', $report->id)
            ->where('utilized_amount', '<=', 0)
            ->count();
          $active_vendors_percentage = $vendors_count > 0 ? ($active_vendors_count / $vendors_count) * 100 : 0;
          $data[$key]['Anchor'] = $report->anchor->name;
          $data[$key]['Total Vendors'] = $vendors_count;
          $data[$key]['Active Vendors'] = $active_vendors_count;
          $data[$key]['Passive Vendors'] = $passive_vendors_count;
          $data[$key]['Percentage of Active Vendors'] = $active_vendors_percentage;
        }

        Excel::store(new Report($headers, $data), 'VF_anchorwise_vendor_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_anchorwise_vendor_report_' . $date . '.csv');
        break;
      case 'vf-vendor-classification-report':
        $report_data = $this->vfVendorClassificationReport($bank, $request);

        $headers = ['Vendor', 'Branch Code', 'Sanctioned Limit', 'Limit Expiry Date', 'Limit Utilized', 'DPD'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Vendor'] = $report->name;
          $data[$key]['Branch Code'] = $report->branch_code;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Limit Expiry Date'] = Carbon::parse($report->limit_expiry)->format('d M Y');
          $data[$key]['Limit Utilized'] = number_format($report->utilized_amount);
          $data[$key]['DPD'] = $report->dpd_days;
        }

        Excel::store(new Report($headers, $data), 'VF_vendor_classification_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_vendor_classification_report_' . $date . '.csv');
        break;
      case 'df-program-mapping-report':
        $report_data = $this->dfProgramMappingReport($bank, $request);

        $headers = [
          'Dealer',
          'Program',
          'Anchor',
          'OD Account',
          'Sanctioned Limit',
          'Limit Expiry Date',
          'Tenor Rates',
          'Base Rate Consideration',
          'Eligibility',
          'Auto Approve Finance',
          'Anchor Bearing',
          'Fees/Charges',
          'Anchor Bearing Fees/Charges',
          'Vendor Bearing Fees/Charges',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $program_vendor_discount = ProgramVendorDiscount::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->get();

          $vendor_fees = ProgramVendorFee::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->get();

          $fees = '';
          $anchor_bearing_fees = '';
          $vendor_bearing_fees = '';

          foreach ($vendor_fees as $vendor_fee) {
            if ($vendor_fee->type === 'percentage') {
              $fees .= $vendor_fee->fee_name . ':' . $vendor_fee->value . '%, ';
              $anchor_bearing_fees .= '0%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->dealer_bearing . '%, ';
            }
            if ($vendor_fee->type === 'amount') {
              $fees .= $vendor_fee->fee_name . ':' . number_format($vendor_fee->value) . ', ';
              $anchor_bearing_fees .= '0%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->dealer_bearing . '%, ';
            }
            if ($vendor_fee->type === 'per amount') {
              $fees .=
                $vendor_fee->fee_name .
                ':' .
                number_format($vendor_fee->value) .
                ' per ' .
                $vendor_fee->per_amount .
                ', ';
              $anchor_bearing_fees .= '0%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->dealer_bearing . '%, ';
            }
          }

          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Program'] = $report->program->name;
          $data[$key]['Anchor'] = $report->program->anchor->name;
          $data[$key]['OD Account'] = $report->payment_account_number;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Limit Expiry Date'] = Carbon::parse($report->program->limit_expiry_date)->format('d M Y');
          $tenor_rates = '';
          foreach ($program_vendor_discount as $discount) {
            $tenor_rates .= $discount->from_day . ' - ' . $discount->to_day . ' Days = ' . $discount->total_roi . '%, ';
          }
          $data[$key]['Tenor Rates'] = $tenor_rates;
          $data[$key]['Base Rate Consideration'] =
            $program_vendor_discount->count() > 0 && $program_vendor_discount->first()->benchmark_title
              ? $program_vendor_discount->first()->benchmark_title .
                ' (' .
                $program_vendor_discount->first()->benchmark_rate .
                '%)'
              : '0%';
          $data[$key]['Eligibility'] = $report->eligibility . '%';
          $data[$key]['Auto Approve Financing'] = $report->auto_approve_finance ? 'Yes' : 'No';
          $data[$key]['Anchor Bearing'] = '0%';
          $data[$key]['Fees/Charges'] = $fees;
          $data[$key]['Anchor Bearing Fees/Charges'] = $anchor_bearing_fees;
          $data[$key]['Vendor Bearing Fees/Charges'] = $vendor_bearing_fees;
        }

        Excel::store(new Report($headers, $data), 'DF_program_mapping_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_program_mapping_report_' . $date . '.csv');
        break;
      case 'vf-program-mapping-report':
        $report_data = $this->vfProgramMappingReport($bank, $request);

        $headers = [
          'Vendor',
          'Program',
          'Anchor',
          'Payment/OD Account',
          'Sanctioned Limit',
          'Available Amount',
          'Utilized Amount',
          'Pipeline Amount',
          'Limit Expiry Date',
          'Margin Rate',
          'Base Rate Consideration',
          'Eligibility',
          'Auto Approve Finance',
          'Anchor Bearing',
          'Fees/Charges',
          'Anchor Bearing Fees/Charges',
          'Vendor Bearing Fees/Charges',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $program_vendor_discount = ProgramVendorDiscount::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->when($report->buyer_id, function ($query) use ($report) {
              $query->where('buyer_id', $report->buyer_id);
            })
            ->first();

          $vendor_fees = ProgramVendorFee::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->when($report->buyer_id, function ($query) use ($report) {
              $query->where('buyer_id', $report->buyer_id);
            })
            ->get();

          $fees = '';
          $anchor_bearing_fees = '';
          $vendor_bearing_fees = '';

          foreach ($vendor_fees as $vendor_fee) {
            if ($vendor_fee->type === 'percentage') {
              $fees .= $vendor_fee->fee_name . ':' . $vendor_fee->value . '%, ';
              $anchor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->anchor_bearing_discount . '%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->vendor_bearing_discount . '%, ';
            }
            if ($vendor_fee->type === 'amount') {
              $fees .= $vendor_fee->fee_name . ':' . number_format($vendor_fee->value) . ', ';
              $anchor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->anchor_bearing_discount . '%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->vendor_bearing_discount . '%, ';
            }
            if ($vendor_fee->type === 'per amount') {
              $fees .=
                $vendor_fee->fee_name .
                ':' .
                number_format($vendor_fee->value) .
                ' per ' .
                $vendor_fee->per_amount .
                ', ';
              $anchor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->anchor_bearing_discount . '%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->vendor_bearing_discount . '%, ';
            }
          }

          $data[$key]['Vendor'] = $report->buyer ? $report->buyer->name : $report->company->name;
          $data[$key]['Program'] = $report->program->name;
          $data[$key]['Anchor'] = $report->program->anchor->name;
          $data[$key]['Payment/OD Account'] = $report->payment_account_number;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Available Amount'] = number_format($report->sanctioned_limit - $report->utilized_amount);
          $data[$key]['Utilized Amount'] = number_format($report->utilized_amount);
          $data[$key]['Pipeline Amount'] = number_format($report->pipeline_amount);
          $data[$key]['Limit Expiry Date'] = Carbon::parse($report->program->limit_expiry_date)->format('d M Y');
          $data[$key]['Margin Rate'] = $report->discount->total_roi . '%';
          $data[$key]['Base Rate Consideration'] =
            $program_vendor_discount && $program_vendor_discount->benchmark_title
              ? $program_vendor_discount->benchmark_title . ' (' . $program_vendor_discount->benchmark_rate . '%)'
              : '0%';
          $data[$key]['Eligibility'] = $report->eligibility . '%';
          $data[$key]['Auto Approve Financing'] = $report->auto_approve_finance ? 'Yes' : 'No';
          $data[$key]['Anchor Bearing'] = $program_vendor_discount->anchor_discount_bearing . '%';
          $data[$key]['Fees/Charges'] = $fees;
          $data[$key]['Anchor Bearing Fees/Charges'] = $anchor_bearing_fees;
          $data[$key]['Vendor Bearing Fees/Charges'] = $vendor_bearing_fees;
        }

        Excel::store(new Report($headers, $data), 'VF_program_mapping_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_program_mapping_report_' . $date . '.csv');
        break;
      case 'loans-pending-approval-report':
        $report_data = $this->loansPendingApprovalReport($bank, $request);

        $headers = [
          'Invoice No',
          'Vendor',
          'Anchor',
          'Request Date',
          'Requested Disbursement Date',
          'PI Amount',
          'Requested Amount',
          'Due Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Invoice No'] = $report->invoice->invoice_number;
          $data[$key]['Vendor'] = $report->invoice->company->name;
          $data[$key]['Anchor'] = $report->invoice->program->anchor->name;
          $data[$key]['Request Date'] = $report->created_at->format('d M Y');
          $data[$key]['Requested Disbursement Date'] = Carbon::parse($report->payment_request_date)->format('d M Y');
          $data[$key]['PI Amount'] = number_format($report->invoice->invoice_total_amount);
          $data[$key]['Requested Amount'] = number_format($report->amount);
          $data[$key]['Due Date'] = Carbon::parse($report->invoice->due_date)->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'Loans_pending_approval_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Loans_pending_approval_report_' . $date . '.csv');
        break;
      case 'loans-pending-disbursal-report':
        $report_data = $this->loansPendingDisbursalReport($bank, $request);

        $headers = [
          'Invoice No',
          'Vendor',
          'Anchor',
          'Request Date',
          'Requested Disbursement Date',
          'PI Amount',
          'Requested Amount',
          'Due Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Invoice No'] = $report->invoice->invoice_number;
          $data[$key]['Vendor'] = $report->invoice->company->name;
          $data[$key]['Anchor'] = $report->invoice->program->anchor->name;
          $data[$key]['Request Date'] = $report->created_at->format('d M Y');
          $data[$key]['Requested Disbursement Date'] = Carbon::parse($report->payment_request_date)->format('d M Y');
          $data[$key]['PI Amount'] = number_format($report->invoice->invoice_total_amount);
          $data[$key]['Requested Amount'] = number_format($report->amount);
          $data[$key]['Due Date'] = Carbon::parse($report->invoice->due_date)->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'Loans_pending_disbursal_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Loans_pending_disbursal_report_' . $date . '.csv');
        break;
      case 'user-maintenance-history-report':
        $report_data = $this->userMaintenanceHistoryReport($bank, $request);

        $headers = ['Type', 'Company', 'Name', 'Email', 'Phone Number', 'Status', 'Last Updated At', 'Last Login'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Type'] = $report->company ? 'Company' : 'Bank';
          $data[$key]['Company'] = $report->company ? $report->company->name : '-';
          $data[$key]['Name'] = $report->user->name;
          $data[$key]['Email'] = $report->user->email;
          $data[$key]['Phone Number'] = $report->user->phone_number;
          $data[$key]['Status'] = $report->active ? 'Active' : 'Inactive';
          $data[$key]['Last Updated At'] = $report->user->updated_at->format('d M Y');
          $data[$key]['Last Login'] = Carbon::parse($report->user->last_login)->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'User_maintenance_history_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('User_maintenance_history_report_' . $date . '.csv');
        break;
      case 'final-rtr-report':
        $report_data = $this->finalRtrReport($bank, $request);

        $headers = [
          'CBS ID',
          'Anchor',
          'Vendor',
          'Invoice No',
          'Debit From',
          'Credit To',
          'Amount',
          'Payment Date',
          'Transaction Failed Date',
          'Product Type',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['CBS ID'] = $report->cbs_id;
          $data[$key]['Anchor'] = $report->paymentRequest
            ? ($report->paymentRequest?->invoice?->buyer
              ? $report->paymentRequest?->invoice?->buyer?->name
              : $report->paymentRequest?->invoice?->program?->anchor?->name)
            : $report->creditAccountRequest?->program->anchor?->name;
          $data[$key]['Vendor'] = $report->paymentRequest
            ? $report->paymentRequest?->invoice?->company->name
            : $report->creditAccountRequest?->company?->name;
          $data[$key]['Invoice No'] = $report->paymentRequest
            ? $report->paymentRequest?->invoice?->invoice_number
            : '-';
          $data[$key]['Debit From'] = $report->debit_from_account;
          $data[$key]['Credit To'] = $report->credit_to_account;
          $data[$key]['Amount'] = number_format($report->amount);
          $data[$key]['Payment Date'] = Carbon::parse($report->transaction_created_date)->format('d M Y');
          $data[$key]['Transaction Failed Date'] = Carbon::parse($report->pay_date)->format('d M Y');
          $data[$key]['Product Type'] = Str::title($report->product);
        }

        Excel::store(new Report($headers, $data), 'Final_rtr_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Final_rtr_report_' . $date . '.csv');
        break;
      case 'maturing-payments-report':
        $report_data = $this->maturingPaymentsReport($bank, $request);

        $headers = [
          'Payment Reference No',
          'Invoice No',
          'Vendor',
          'Anchor',
          'Payment Date',
          'PI Amount',
          'Payment Amount',
          'Total Outstanding',
          'Due Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = '';
          if ($report->financing_status == 'disbursed' || $report->financing_status == 'closed') {
            $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
              $query->whereHas('invoice', function ($query) use ($report) {
                $query->where('id', $report->id);
              });
            })
              ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
              ->first()?->transaction_reference;
          }
          $data[$key]['Payment Reference No'] = $transaction_ref;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['PI Amount'] = number_format($report->invoice_total_amount, 2);
          $data[$key]['Payment Amount'] = number_format($report->disbursed_amount, 2);
          $data[$key]['Total Outstanding Amount'] = number_format(
            $report->invoice_total_amount - $report->paid_amount,
            2
          );
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'Maturing_payments_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Maturing_payments_report_' . $date . '.csv');
        break;
      case 'maturity-extended-report':
        $report_data = $this->maturityExtendedReport($bank, $request);

        $headers = [
          'Payment Account No',
          'Invoice No',
          'Vendor',
          'Anchor',
          'Original Date',
          'Changed Due Date',
          'Product Code',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Payment Account No'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Original Date'] = Carbon::parse($report->old_due_date)->format('d M Y');
          $data[$key]['Changed Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
          $data[$key]['Product Code'] =
            $report->program->programType->name == Program::VENDOR_FINANCING
              ? Program::VENDOR_FINANCING
              : Program::DEALER_FINANCING;
        }

        Excel::store(new Report($headers, $data), 'Maturity_extended_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Maturity_extended_report_' . $date . '.csv');
        break;
      case 'if-payment-details-report':
        $report_data = $this->ifPaymentDetailsReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No.',
          'Anchor',
          'Vendor',
          'Disbursement Date',
          'Due Date',
          'DPD',
          'Total Outstanding',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['System ID'] = $report->id;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Disbursement Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
          $data[$key]['DPD'] = $report->days_past_due;
          $data[$key]['Total Outstanding'] = number_format($report->balance);
          $data[$key]['Status'] = Str::title($report->status);
        }

        Excel::store(new Report($headers, $data), 'IF_payment_details_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('IF_payment_details_report_' . $date . '.csv');
        break;
      case 'distributor-limit-utilization-report':
        $report_data = $this->distributorLimitUtilizationReport($bank, $request);

        $headers = [
          'Company Name',
          'Sanctioned Limit',
          'Utilized Amount',
          'Pipeline Requests',
          'Available Limimt',
          'Limit Utilized',
          'Product',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Company Name'] = $report->name;
          $data[$key]['Sanctioned Limit'] = number_format($report->top_level_borrower_limit);
          $data[$key]['Utilized Amount'] = number_format($report->utilized_amount);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount);
          $data[$key]['Available Limit'] = number_format($report->available_amount);
          $data[$key]['Limit Utilized'] = $report->utilized_percentage;
          $data[$key]['Product'] = 'Dealer Financing';
        }

        Excel::store(new Report($headers, $data), 'Distributor_limit_utilization_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Distributor_limit_utilization_report_' . $date . '.csv');
        break;
      case 'df-potential-financing-report':
        $report_data = $this->dfPotentialFinancingReport($bank, $request);

        $headers = ['Invoice No.', 'Anchor', 'Dealer', 'Net Invoice Amount', 'Invoice Date', 'Due Date'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Net Invoice Amount'] = number_format($report->invoice_total_amount);
          $data[$key]['Invoice Date'] = Carbon::parse($report->invoice_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'DF_potential_financing_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_potential_financing_report_' . $date . '.csv');
        break;
      case 'vf-potential-financing-report':
        $report_data = $this->vfPotentialFinancingReport($bank, $request);

        $headers = ['Invoice No.', 'Anchor', 'Vendor', 'Net Invoice Amount', 'Invoice Date', 'Due Date'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Net Invoice Amount'] = number_format($report->invoice_total_amount);
          $data[$key]['Invoice Date'] = Carbon::parse($report->invoice_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'VF_potential_financing_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_potential_financing_report_' . $date . '.csv');
        break;
      case 'vf-overdue-invoices-report':
        $report_data = $this->vfOverdueInvoicesReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No',
          'Anchor',
          'Vendor',
          'Payment Date',
          'Due Date',
          'Invoice Amount',
          'Payment Amount',
          'Outstanding Amount',
          'Overdue Days',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['System ID'] = $report->id;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->invoice_total_amount, 2);
          $data[$key]['Payment Amount'] = number_format($report->disbursed_amount, 2);
          $data[$key]['Outstanding Amount'] = number_format($report->balance, 2);
          $data[$key]['Overdue Days'] = $report->days_past_due;
          $data[$key]['Status'] = Str::title($report->status);
        }

        Excel::store(new Report($headers, $data), 'VF_overdue_invoices_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_overdue_invoices_report_' . $date . '.csv');
        break;
      case 'df-overdue-invoices-report':
        $report_data = $this->dfOverdueInvoicesReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No',
          'Anchor',
          'Dealer',
          'Payment Date',
          'Due Date',
          'Payment Amount',
          'Outstanding Amount',
          'Overdue Days',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['System ID'] = $report->id;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
          $data[$key]['Payment Amount'] = number_format($report->disbursed_amount, 2);
          $data[$key]['Outstanding Amount'] = number_format($report->balance, 2);
          $data[$key]['Overdue Days'] = $report->days_past_due;
          $data[$key]['Status'] = Str::title($report->status);
        }

        Excel::store(new Report($headers, $data), 'DF_overdue_invoices_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_overdue_invoices_report_' . $date . '.csv');
        break;
      case 'df-overdue-report':
        $report_data = $this->dfOverdueReport($bank, $request);

        $headers = [
          'OD Account',
          'Dealer',
          'Anchor',
          'Invoice No',
          'Drawdown Date',
          'Overdue Principle',
          'Principle DPD',
          'Overdue Interest',
          'Interest DPD',
          'Total Penal Interest',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Drawdown Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Overdue Principle'] = number_format($report->invoice_total_amount);
          $data[$key]['Principle DPD'] = $report->days_past_due;
          $data[$key]['Overdue Interest'] = number_format($report->overdue_amount);
          $data[$key]['Interest DPD'] = $report->days_past_due;
          $data[$key]['Total Penal Interest'] = number_format($report->overdue_amount);
        }

        Excel::store(new Report($headers, $data), 'DF_overdue_invoices_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_overdue_invoices_report_' . $date . '.csv');
        break;
      case 'df-od-ledger-report':
        $report_data = $this->dfOdLedgerReport($bank, $request);

        $headers = [
          'OD Account',
          'Dealer',
          'Invoice No',
          'Date',
          'Transaction Type',
          'Debit',
          'Credit',
          'Principle Balance',
          'Discount',
          'Penal Discount',
          'Overdue Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Dealer'] = $report->paymentRequest
            ? $report->paymentRequest->invoice->company->name
            : $report->creditAccountRequest->company->name;
          $data[$key]['Invoice No'] = $report->paymentRequest ? $report->paymentRequest->invoice->invoice_number : '-';
          $data[$key]['Date'] = $report->created_at->format('d M Y');
          $data[$key]['Transaction Type'] = $report->transaction_reference;
          $data[$key]['Debit'] = $report->paymentRequest ? number_format($report->amount) : 0;
          $data[$key]['Credit'] = $report->creditAccountRequest ? number_format($report->amount) : 0;
          $data[$key]['Principle Balance'] = $report->paymentRequest
            ? number_format($report->paymentRequest->invoice->invoice_total_amount)
            : 0;
          $data[$key]['Discount'] = 0;
          $data[$key]['Penal Discount'] = 0;
          $data[$key]['Overdue Date'] = $report->paymentRequest
            ? Carbon::parse($report->paymentRequest->invoice->due_date)->format('d M Y')
            : '-';
        }

        Excel::store(new Report($headers, $data), 'DF_od_ledger_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_od_ledger_report_' . $date . '.csv');
        break;
      case 'users-and-roles-report':
        $report_data = $this->usersAndRolesReport($bank, $request);

        $headers = ['User Type', 'Company', 'User', 'Email', 'Role', 'Status', 'Last Login', 'Last Login IP'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['User Type'] = $report->mappedCompanies->count() > 0 ? 'Company' : 'Bank';
          $companies = '';
          if ($report->mappedCompanies->count() > 0) {
            foreach ($report->mappedCompanies as $company) {
              $companies .= $company->name . ', ';
            }
          }
          $data[$key]['Company'] = $companies;
          $data[$key]['User'] = $report->name;
          $data[$key]['Email'] = $report->email;
          $roles = '';
          foreach ($report->roles as $role) {
            $roles .= $role->name . ', ';
          }
          $data[$key]['Roles'] = $roles;
          $data[$key]['Status'] = Str::title($report->status);
          $data[$key]['Last Login'] = Carbon::parse($report->last_login)->format('d M Y');
          $data[$key]['Last Login IP'] = $report->activity?->properties->contains('ip')
            ? $report->activity?->properties?->ip
            : '-';
        }

        Excel::store(new Report($headers, $data), 'Users_and_roles_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Users_and_roles_report_' . $date . '.csv');
        break;
      case 'vf-funding-limit-utilization-report':
        $report_data = $this->vfFundingLimitUtilizationReport($bank, $request);

        $headers = [
          'Organization Name',
          'Sanctioned Limit',
          'Available Limit',
          'Current Exposure',
          'Pipeline Requests',
          'Utilized Limit',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Organization Name'] =
            $report->program->programCode?->name === Program::VENDOR_FINANCING_RECEIVABLE
              ? $report->company->name
              : $report->buyer->name;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Available Limit'] = number_format(
            $report->sanctioned_limit - $report->utilized_amount - $report->pipeline_amount
          );
          $data[$key]['Current Exposure'] = number_format($report->utilized_amount);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount);
          $data[$key]['Utilized Limit'] =
            round((($report->utilized_amount + $report->pipeline_amount) / $report->sanctioned_limit) * 100, 2) . '%';
        }

        Excel::store(new Report($headers, $data), 'VF_funding_limit_utilization_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_funding_limit_utilization_report_' . $date . '.csv');
        break;
      case 'df-funding-limit-utilization-report':
        $report_data = $this->dfFundingLimitUtilizationReport($bank, $request);

        $headers = [
          'Organization Name',
          'Sanctioned Limit',
          'Available Limit',
          'Current Exposure',
          'Pipeline Requests',
          'Utilized Limit',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Organization Name'] = $report->name;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Available Limit'] = number_format($report->sanctioned_limit - $report->utilized_amount);
          $data[$key]['Current Exposure'] = number_format($report->utilized_amount);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount);
          $data[$key]['Utilized Limit'] = $report->utilized_percentage;
        }

        Excel::store(new Report($headers, $data), 'DF_funding_limit_utilization_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_funding_limit_utilization_report_' . $date . '.csv');
        break;
      case 'df-income-report':
        $report_data = $this->dfIncomeReport($bank, $request);

        $headers = [
          'Dealer',
          'Anchor',
          'Invoice No',
          'Payment Date',
          'Invoice Amount',
          'Credit Note Amount',
          'Principle Balance',
          'Fees/Charges',
          'Total Posted Discount',
          'Total Posted Penal Discount',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
            $query->whereHas('invoice', function ($query) use ($report) {
              $query->where('id', $report->id);
            });
          })
            ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
            ->first()?->transaction_reference;

          $credit_note_amount = InvoiceFee::where('invoice_id', $report->id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->calculated_total_amount + $credit_note_amount, 2);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Principle Balance'] = number_format($report->balance, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Total Posted Penal Discount'] = number_format($report->overdue_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
          $data[$key]['Transaction Reference'] = $transaction_ref;
        }

        Excel::store(new Report($headers, $data), 'DF_income_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_income_report_' . $date . '.csv');
        break;
      case 'df-fees-and-interest-sharing-report':
        $report_data = $this->dfFeesAndInterestSharingReport($bank, $request);

        $headers = [
          'Dealer',
          'Invoice No',
          'Payment Date',
          'Payment Amount',
          'Total Posted Discount',
          'Anchor Discount Share',
          'Fees/Charges',
          'Anchor Fees Share',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Payment Amount'] = number_format($report->paymentRequests->first()->amount);
          $data[$key]['Total Posted Discount'] = number_format(
            $report->drawdown_amount - $report->paymentRequests->first()->amount
          );
          $data[$key]['Anchor Discount Share'] = 0;
          $data[$key]['Fees/Charges'] = number_format($report->fees);
          $data[$key]['Anchor Fees Share'] = 0;
          $data[$key]['Status'] = Str::title($report->status);
        }

        Excel::store(
          new Report($headers, $data),
          'DF_fees_charges_and_interest_sharing_report_' . $date . '.csv',
          'exports'
        );

        return Storage::disk('exports')->download('DF_fees_charges_and_interest_sharing_report_' . $date . '.csv');
        break;
      case 'vf-fees-and-interest-sharing-report':
        $report_data = $this->vfFeesAndInterestSharingReport($bank, $request);

        $headers = [
          'Vendor',
          'Invoice No',
          'Payment Date',
          'Payment Amount',
          'Total Posted Discount',
          'Anchor Discount Share',
          'Fees/Charges',
          'Anchor Fees Share',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Payment Amount'] = number_format($report->disbursed_amount, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Anchor Discount Share'] = number_format($report->anchor_discount_bearing_amount, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Anchor Fees Share'] = number_format($report->anchor_fee_bearing_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
        }

        Excel::store(
          new Report($headers, $data),
          'VF_fees_charges_and_interest_sharing_report_' . $date . '.csv',
          'exports'
        );

        return Storage::disk('exports')->download('VF_fees_charges_and_interest_sharing_report_' . $date . '.csv');
        break;
      case 'vf-income-report':
        $report_data = $this->vfIncomeReport($bank, $request);

        $headers = [
          'Vendor',
          'Anchor',
          'Invoice No',
          'Payment Date',
          'Invoice Amount',
          'Credit Note Amount',
          'Principle Balance',
          'Fees/Charges',
          'Total Posted Discount',
          'Total Posted Penal Discount',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
            $query->whereHas('invoice', function ($query) use ($report) {
              $query->where('id', $report->id);
            });
          })
            ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
            ->first()?->transaction_reference;

          $credit_note_amount = InvoiceFee::where('invoice_id', $report->id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->calculated_total_amount + $credit_note_amount, 2);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Principle Balance'] = number_format($report->balance, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Total Posted Penal Discount'] = number_format($report->overdue_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
          $data[$key]['Transaction Reference'] = $transaction_ref;
        }

        Excel::store(new Report($headers, $data), 'VF_income_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_income_report_' . $date . '.csv');
        break;
      case 'factoring-income-report':
        $report_data = $this->factoringIncomeReport($bank, $request);

        $headers = [
          'Vendor',
          'Anchor',
          'Invoice No',
          'Payment Date',
          'Invoice Amount',
          'Credit Note Amount',
          'Principle Balance',
          'Fees/Charges',
          'Total Posted Discount',
          'Total Posted Penal Discount',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
            $query->whereHas('invoice', function ($query) use ($report) {
              $query->where('id', $report->id);
            });
          })
            ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
            ->first()?->transaction_reference;

          $credit_note_amount = InvoiceFee::where('invoice_id', $report->id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->calculated_total_amount + $credit_note_amount, 2);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Principle Balance'] = number_format($report->balance, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Total Posted Penal Discount'] = number_format($report->overdue_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
          $data[$key]['Transaction Reference'] = $transaction_ref;
        }

        Excel::store(new Report($headers, $data), 'Factoring_income_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Factoring_income_report_' . $date . '.csv');
        break;
      case 'vendors-daily-outstanding-balance-report':
        $report_data = $this->vendorsDailyOutstandingBalanceReport($bank, $request);

        $headers = [
          'Date',
          'Vendor',
          'Outstanding Balance',
          'Sanctioned Limit',
          'Pipeline Requests',
          'Available Limit',
          'Limit Utilized(%)',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Date'] = now()->format('d M Y');
          $data[$key]['Vendor'] = $report->buyer
            ? $report->buyer->name . ' (' . $report->payment_account_number . ')'
            : $report->company->name . ' (' . $report->payment_account_number . ')';
          $data[$key]['Outstanding Balance'] = number_format($report->utilized_amount);
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount);
          $data[$key]['Available Limit'] = number_format(
            $report->sanctioned_limit - ($report->pipeline_amount + $report->utilized_amount)
          );
          $data[$key]['Limit Utilized(%)'] = $report->utilized_percentage_ratio . '%';
        }

        Excel::store(
          new Report($headers, $data),
          'Vendor_daily_outstanding_balance_report_' . $date . '.csv',
          'exports'
        );

        return Storage::disk('exports')->download('Vendor_daily_outstanding_balance_report_' . $date . '.csv');
        break;
      case 'dealers-daily-outstanding-balance-report':
        $report_data = $this->dealersDailyOutstandingBalanceReport($bank, $request);

        $headers = [
          'Date',
          'Dealer',
          'Outstanding Balance',
          'Sanctioned Limit',
          'Pipeline Requests',
          'Available Limit',
          'Limit Utilized(%)',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Date'] = $report->created_at->format('d M Y');
          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Outstanding Balance'] = number_format($report->utilized);
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline);
          $data[$key]['Available Limit'] = number_format(
            $report->sanctioned_limit - ($report->pipeline + $report->utilized)
          );
          $data[$key]['Limit Utilized(%)'] = $report->utilized_percentage_ratio . '%';
        }

        Excel::store(
          new Report($headers, $data),
          'Dealers_daily_outstanding_balance_report_' . $date . '.csv',
          'exports'
        );

        return Storage::disk('exports')->download('Dealers_daily_outstanding_balance_report_' . $date . '.csv');
        break;
      case 'cron-logs':
        $report_data = $this->cronLogs($bank, $request);

        $headers = ['System ID', 'Cron Name', 'Date', 'Status', 'Start Time', 'End Time', 'Time Taken'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['System ID'] = $report->id;
          $data[$key]['Cron Name'] = $report->name;
          $data[$key]['Date'] = $report->created_at->format('d M Y');
          $data[$key]['Status'] = $report->status;
          $data[$key]['Start Time'] = Carbon::parse($report->start_time)->format('H:i A');
          $data[$key]['End Time'] = Carbon::parse($report->end_time)->format('H:i A');
          $data[$key]['Time Taken'] = $report->end_time
            ? Carbon::parse($report->start_time)->diffInMinutes(Carbon::parse($report->end_time))
            : '-';
        }

        Excel::store(new Report($headers, $data), 'Cron_logs_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Cron_logs_' . $date . '.csv');
        break;
      case 'df-collection-report':
        $report_data = $this->dfCollectionReport($bank, $request);

        $headers = ['OD Account', 'Dealer', 'Invoice/Unique Ref No.', 'Date', 'Transaction Ref', 'Amount', 'Account'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Dealer'] = $report->paymentRequest->invoice->company->name;
          $data[$key]['Invoice/Unique Ref No.'] = $report->paymentRequest->invoice->invoice_number;
          $data[$key]['Date'] = Carbon::parse($report->pay_date)->format('d M Y');
          $data[$key]['Transaction Ref'] = $report->transaction_reference;
          $data[$key]['Amount'] = number_format($report->amount, 2);
          $data[$key]['Account'] = $report->credit_to_account;
        }

        Excel::store(new Report($headers, $data), 'DF_collection_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_collection_report_' . $date . '.csv');
        break;
      case 'vf-repayment-details-report':
        $report_data = $this->vfRepaymentDetailsReport($bank, $request);

        $headers = [
          'Date',
          'CBS ID',
          'Total Repayment Amount',
          'Set Off Amount',
          'Balance Amount',
          'Set Off Particulars',
          'Set Off Type',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Date'] = Carbon::parse($report->transaction_created_date)->format('d M Y');
          $data[$key]['CBS ID'] = $report->id;
          $data[$key]['Total Repayment Amount'] = number_format($report->amount, 2);
          $data[$key]['Set Off Amount'] = number_format($report->paymentRequest->invoice->invoice_total_amount, 2);
          $data[$key]['Balance Amount'] = number_format($report->paymentRequest->invoice->balance, 2);
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Particulars'] =
              'Penal Interest for ' . $report->paymentRequest->invoice->invoice_number;
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Particulars'] = 'Principle For ' . $report->paymentRequest->invoice->invoice_number;
          } else {
            $data[$key]['Set Off Particulars'] = 'Interest For ' . $report->paymentRequest->invoice->invoice_number;
          }
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Type'] = 'Penal Interest';
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Type'] = 'Principle';
          } else {
            $data[$key]['Set Off Type'] = 'Interest';
          }
        }

        Excel::store(new Report($headers, $data), 'VF_Repayment_details_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('VF_Repayment_details_report_' . $date . '.csv');
        break;
      case 'df-repayment-details-report':
        $report_data = $this->dfRepaymentDetailsReport($bank, $request);

        $headers = [
          'Date',
          'CBS ID',
          'Program Name',
          'Total Repayment Amount',
          'Set Off Amount',
          'Balance Amount',
          'Set Off Particulars',
          'Set Off Type',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Date'] = Carbon::parse($report->transaction_created_date)->format('d M Y');
          $data[$key]['CBS ID'] = $report->id;
          $data[$key]['Program Name'] = $report->paymentRequest->invoice->program->name;
          $data[$key]['Total Repayment Amount'] = number_format($report->amount, 2);
          $data[$key]['Set Off Amount'] = number_format($report->paymentRequest->invoice->invoice_total_amount, 2);
          $data[$key]['Balance Amount'] = number_format($report->paymentRequest->invoice->balance, 2);
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Particulars'] =
              'Penal Interest for ' . $report->paymentRequest->invoice->invoice_number;
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Particulars'] = 'Principle For ' . $report->paymentRequest->invoice->invoice_number;
          } else {
            $data[$key]['Set Off Particulars'] = 'Interest For ' . $report->paymentRequest->invoice->invoice_number;
          }
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Type'] = 'Penal Interest';
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Type'] = 'Principle';
          } else {
            $data[$key]['Set Off Type'] = 'Interest';
          }
        }

        Excel::store(new Report($headers, $data), 'DF_Repayment_details_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('DF_Repayment_details_report_' . $date . '.csv');
        break;
      case 'df-monthly-utilization-and-outstanding-report':
        $report_data = $this->dfMonthlyUtilizationAndOutstandingReport($bank, $request);
        $headers = [
          'OD Account',
          'Month',
          'Total OD Limit',
          'Utilized Limit',
          'Principle Outstanding',
          'Interest Outstanding',
          'Principle DPD (Days)',
          'Principle DPD Amount',
          'Interest DPD (Days)',
          'Interest DPD Amount',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Month'] = $report->created_at->format('M Y');
          $data[$key]['Total OD Limit'] = number_format($report->vendor_configurations->sanctioned_limit);
          $data[$key]['Utilized Limit'] = $report->vendor_configurations->utilized_percentage_ratio;
          $data[$key]['Principle Outstanding'] = number_format($report->paymentRequest->invoice->disbursed_amount, 2);
          $data[$key]['Interest Outstanding'] = number_format($report->paymentRequest->invoice->balance, 2);
          $data[$key]['Principle DPD (Days)'] = number_format($report->paymentRequest->invoice->days_past_due);
          $data[$key]['Principle DPD Amount'] = number_format($report->paymentRequest->invoice->overdue_amount, 2);
          $data[$key]['Interest DPD (Days)'] = number_format($report->paymentRequest->invoice->days_past_due);
          $data[$key]['Interest DPD Amount'] = number_format($report->paymentRequest->invoice->overdue_amount, 2);
        }

        Excel::store(
          new Report($headers, $data),
          'DF_Monthly_utilization_and_outstanding_report_' . $date . '.csv',
          'exports'
        );

        return Storage::disk('exports')->download('DF_Monthly_utilization_and_outstanding_report_' . $date . '.csv');
        break;
      case 'bank-gls-report':
        $report_data = $this->bankGlsReports($bank, $request);
        $headers = ['Account Name', 'Account Number', 'Balance', 'Created At'];

        $data = [];

        foreach ($report_data as $key => $report) {
          // Get Amount Credited to Account
          $credit_amount = CbsTransaction::where('bank_id', $bank->id)
            ->where('credit_to_account', $report->account_number)
            ->sum('amount');
          $debit_amount = CbsTransaction::where('bank_id', $bank->id)
            ->where('debit_from_account', $report->account_number)
            ->sum('amount');
          $data[$key]['Account Name'] = $report->account_name;
          $data[$key]['Account Number'] = $report->account_number;
          $data[$key]['Balance'] = number_format($credit_amount - $debit_amount, 2);
          $data[$key]['Created At'] = $report->created_at->format('d M Y');
        }

        Excel::store(new Report($headers, $data), 'Bank_GLs_report_' . $date . '.csv', 'exports');

        return Storage::disk('exports')->download('Bank_GLs_report_' . $date . '.csv');
        break;
      default:
        return response()->json(['message' => 'Invalid report type'], 400);
        break;
    }
  }

  public function exportPdfReports(Request $request, Bank $bank)
  {
    $date = now()->format('Y-m-d');
    $report = $request->query('type');

    switch ($report) {
      case 'logs':
        $user_type = $request->query('user_type');
        $description = $request->query('description');
        $from_date = $request->query('start_date');
        $to_date = $request->query('end_date');

        $headers = ['User', 'User Type', 'Description', 'IP', 'Device Details', 'Date & Time'];
        $data = [];

        $logs = Activity::with('subject', 'causer')
          ->inLog($bank->id)
          ->when($user_type && $user_type != '', function ($query) use ($user_type) {
            $query->where('properties->user_type', $user_type);
          })
          ->when($description && $description != '', function ($query) use ($description) {
            $query->where('description', 'LIKE', '%' . $description . '%');
          })
          ->when($from_date && $from_date != '', function ($query) use ($from_date) {
            $query->whereDate('created_at', '>=', $from_date);
          })
          ->when($to_date && $to_date != '', function ($query) use ($to_date) {
            $query->whereDate('created_at', '<=', $to_date);
          })
          ->latest()
          ->get();

        foreach ($logs as $key => $log) {
          $description = '';
          if ($log->subject && $log->subject_type == Company::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->name;
          } elseif ($log->subject && $log->subject_type == Invoice::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->invoice_number;
          } elseif ($log->subject && $log->subject_type == PaymentRequest::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->reference_number;
          } elseif ($log->subject && $log->subject_type == CbsTransaction::class) {
            $description = $log->causer->name . ' ' . $log->description . ' ' . $log->subject->id;
          } elseif ($log->subject && $log->subject_type == BankPaymentAccount::class) {
            $description = $log->causer->name . ' ' . $log->description;
          }

          $data[$key] = [
            'User' => $log->causer->name . ' (' . $log->causer->email . ')',
            'User Type' =>
              !empty($log->properties) && array_key_exists('user_type', $log->properties->toArray())
                ? $log->properties['user_type']
                : '-',
            'Description' => $description,
            'IP' => !empty($log->properties) && array_key_exists('ip', $log->properties) ? $log->properties['ip'] : '-',
            'Device Details' =>
              !empty($log->properties) && array_key_exists('device_info', $log->properties)
                ? $log->properties['device_info']
                : '-',
            'Date & Time' => $log->created_at->format('d M Y'),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Activity Logs Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('ActivityLogs_' . $date . '.pdf');
        break;

      case 'all-payments-report':
        $report_data = $this->allPaymentsReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No.',
          'Company',
          'Anchor',
          'Payment Date',
          'Due Date',
          'Invoice Amount',
          'Payment Amount',
          'Credit Note Amount',
          'Eligibility',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $credit_note_amount = InvoiceFee::where('invoice_id', $report->paymentRequest?->invoice_id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['System ID'] = $report->id;
          $data[$key]['Invoice No'] = $report->paymentRequest->invoice->invoice_number;
          $data[$key]['Company'] = $report->paymentRequest->invoice->company->name;
          $data[$key]['Anchor'] = $report->paymentRequest->invoice->buyer
            ? $report->paymentRequest->invoice->buyer->name
            : $report->paymentRequest->invoice->program->anchor->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->paymentRequest?->payment_request_date)->format('d M Y');
          $data[$key]['Due Date'] = Carbon::parse($report->paymentRequest->invoice->due_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->paymentRequest?->invoice?->invoice_total_amount);
          $data[$key]['Payment Amount'] = number_format($report->paymentRequest?->amount);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Eligibility'] = $report->paymentRequest?->invoice?->program->eligibility;
          $data[$key]['Status'] = Str::title($report->status);
          $data[$key]['Transaction Reference'] = $report->transaction_reference;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'All Payments Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('All_payment_report_' . $date . '.pdf');
        break;

      case 'drawdown-details-report':
        $report_data = $this->drawdownDetails($bank, $request);

        $headers = [
          'Payment Reference No',
          'Invoice No',
          'Dealer',
          'Anchor',
          'Request Date',
          'Due Date',
          'Invoice Amount',
          'Payment Amount',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Payment Reference No' => $report->reference_number,
            'Invoice No' => $report->invoice->invoice_number,
            'Dealer' => $report->invoice->company->name,
            'Anchor' => $report->invoice->buyer
              ? $report->invoice->buyer->name
              : $report->invoice->program->anchor->name,
            'Request Date' => Carbon::parse($report->payment_request_date)->format('d M Y'),
            'Due Date' => Carbon::parse($report->invoice->due_date)->format('d M Y'),
            'Invoice Amount' => number_format($report->invoice->invoice_total_amount),
            'Payment Amount' => number_format($report->amount),
            'Status' => Str::title($report->status),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'DF - Drawdown Details Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Drawdown_Details_Report_' . $date . '.pdf');
        break;

      case 'inactive-users-report':
        $report_data = $this->inactiveUsersReport($bank, $request);

        $headers = ['Name', 'Role', 'Email', 'Phone Number', 'Companies', 'Last Login'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Name'] = $report->name;
          $data[$key]['Role'] = $report->roles?->first()?->name;
          $data[$key]['Email'] = $report->email;
          $data[$key]['Phone Number'] = $report->phone_number;
          $user_companies = $report->mappedCompanies?->map(fn($company) => $company->name);
          $data[$key]['Companies'] = collect($user_companies)->join(', ', 'and ');
          $data[$key]['Last Login'] = $report->last_login ? Carbon::parse($report->last_login)->format('d M Y') : '-';
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Inactive Users Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Inactive_Users_Report_' . $date . '.pdf');
        break;

      case 'rejected-loans-report':
        $report_data = $this->rejectedLoans($bank, $request);

        $headers = [
          'Anchor',
          'Vendor',
          'Request Date',
          'Rejection Date',
          'PI No',
          'Invoice Amount',
          'Payment Amount',
          'Rejection Reason',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Anchor' => $report->invoice->buyer
              ? $report->invoice->buyer->name
              : $report->invoice->program->anchor->name,
            'Vendor' => $report->invoice->company->name,
            'Request Date' => Carbon::parse($report->payment_request_date)->format('d M Y'),
            'Rejection Date' => $report->updated_at->format('d M Y'),
            'PI No' => $report->invoice->pi_number,
            'Invoice Amount' => number_format($report->invoice->invoice_total_amount),
            'Payment Amount' => number_format($report->amount),
            'Rejection Reason' => $report->rejected_reason,
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Rejected Loans Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Rejected_Loans_Report_' . $date . '.pdf');
        break;

      case 'df-anchorwise-dealer-report':
        $report_data = $this->dfAnchorwiseDealerReport($bank, $request);

        $headers = ['Anchor', 'Total Dealers', 'Active Dealers', 'Passive Dealers', 'Percentage of Active Dealers'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Anchor' => $report->id,
            'Total Dealers' => $report->dealers,
            'Active Dealers' => $report->active_dealers,
            'Passive Dealers' => $report->passive_dealers,
            'Percentage of Active Dealers' => $report->active_dealers_percent,
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'DF - Anchorwise Dealer Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('DF_Anchorwise_Dealer_Report_' . $date . '.pdf');
        break;

      case 'df-dealer-classification-report':
        $report_data = $this->dfDealerClassificationReport($bank, $request);

        $headers = ['Dealer', 'Branch Code', 'Sanctioned Limit', 'Limit Expiry Date', 'Limit Utilized', 'DPD Days'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Dealer' => $report->name,
            'Branch Code' => $report->branch_code,
            'Sanctioned Limit' => number_format($report->sanctioned_limit),
            'Limit Expiry Date' => Carbon::parse($report->limit_expiry)->format('d M Y'),
            'Limit Utilized' => number_format($report->utilized_amount),
            'DPD Days' => $report->dpd_days,
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'DF - Dealer Classification Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('DF_Dealer_Classification_Report_' . $date . '.pdf');
        break;

      case 'vendor-financing-programs-report':
        $report_data = $this->vendorFinancingPrograms($bank, $request);

        $headers = [
          'Program Name',
          'Anchor Name',
          'Status',
          'Total Program Limit',
          'Utilized Limit',
          'Pipeline Requests',
          'Available Amount',
          'Base Rate Consideration',
          'Anchor Discount Bearing',
          'Eligibility',
          'Total Mapped Companies',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          if ($report->programType->name === Program::DEALER_FINANCING) {
            $program_discount_details = ProgramDealerDiscountRate::where('program_id', $report->id)->first();
          } else {
            $program_discount_details = ProgramDiscount::where('program_id', $report->id)->first();
          }

          $data[$key]['Program Name'] = $report->name;
          $data[$key]['Anchohr Name'] = $report->anchor->name;
          $data[$key]['Status'] = $report->account_status;
          $data[$key]['Total Program Limit'] = number_format($report->program_limit, 2);
          $data[$key]['Utilized Limit'] = number_format($report->utilized_amount, 2);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount, 2);
          $data[$key]['Available Amount'] = number_format(
            $report->program_limit - $report->pipeline_amount - $report->utilized_amount,
            2
          );
          $data[$key]['Base Rate Consideration'] = number_format($program_discount_details->benchmark_rate) . '%';
          $data[$key]['Anchor Discount Bearing'] =
            number_format($program_discount_details->anchor_discount_bearing) . '%';
          $data[$key]['Eligibility'] = number_format($report->eligibility) . '%';
          if ($report->programType->name === Program::DEALER_FINANCING) {
            $data[$key]['Total Mapped Companies'] = $report->getDealers()->count();
          } else {
            if ($report->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
              $data[$key]['Total Mapped Companies'] = $report->getVendors()->count();
            } else {
              $data[$key]['Total Mapped Companies'] = $report->getBuyers()->count();
            }
          }
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Vender Financing Programs Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('VF_Programs_Report_' . $date . '.pdf');
        break;

      case 'vf-anchorwise-vendor-report':
        $report_data = $this->vfAnchorwiseVendorReport($bank, $request);

        $headers = ['Anchor', 'Total Vendors', 'Active Vendors', 'Passive Vendors', 'Percentage of Active Vendors'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $vendors_count = ProgramCompanyRole::where('program_id', $report->id)->count() - 1; // -1 to remove the anchor count

          $active_vendors_count = ProgramVendorConfiguration::where('program_id', $report->id)
            ->where('utilized_amount', '>', 0)
            ->count();

          $passive_vendors_count = ProgramVendorConfiguration::where('program_id', $report->id)
            ->where('utilized_amount', '<=', 0)
            ->count();

          $active_vendors_percentage = $vendors_count > 0 ? ($active_vendors_count / $vendors_count) * 100 : 0;

          $data[$key]['Anchor'] = $report->anchor->name;
          $data[$key]['Total Vendors'] = $vendors_count;
          $data[$key]['Active Vendors'] = $active_vendors_count;
          $data[$key]['Passive Vendors'] = $passive_vendors_count;
          $data[$key]['Percentage of Active Vendors'] = $active_vendors_percentage;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'VF Anchorwise-Vendor Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('VF_Anchorwise_Vendor_Report_' . $date . '.pdf');
        break;

      case 'vf-vendor-classification-report':
        $report_data = $this->vfVendorClassificationReport($bank, $request);

        $headers = ['Vendor', 'Branch Code', 'Sanctioned Limit', 'Limit Expiry Date', 'Limit Utilized', 'DPD'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Vendor' => $report->name,
            'Branch Code' => $report->branch_code,
            'Sanctioned Limit' => number_format($report->sanctioned_limit),
            'Limit Expiry Date' => Carbon::parse($report->limit_expiry)->format('d M Y'),
            'Limit Utilized' => number_format($report->utilized_amount),
            'DPD' => $report->dpd_days,
          ];
        }
        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'VF Vendor Classification Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('VF_Vendor_Classification_Report_' . $date . '.pdf');
        break;

      case 'df-program-mapping-report':
        $report_data = $this->dfProgramMappingReport($bank, $request);

        $headers = [
          'Dealer',
          'Program',
          'Anchor',
          'OD Account',
          'Sanctioned Limit',
          'Limit Expiry Date',
          'Tenor Rates',
          'Base Rate Consideration',
          'Eligibility',
          'Auto Approve Finance',
          'Anchor Bearing',
          'Fees/Charges',
          'Anchor Bearing Fees/Charges',
          'Vendor Bearing Fees/Charges',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $program_vendor_discount = ProgramVendorDiscount::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->get();

          $vendor_fees = ProgramVendorFee::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->get();

          $fees = '';
          $anchor_bearing_fees = '';
          $vendor_bearing_fees = '';

          foreach ($vendor_fees as $vendor_fee) {
            if ($vendor_fee->type === 'percentage') {
              $fees .= $vendor_fee->fee_name . ':' . $vendor_fee->value . '%, ';
              $anchor_bearing_fees .= '0%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->dealer_bearing . '%, ';
            }
            if ($vendor_fee->type === 'amount') {
              $fees .= $vendor_fee->fee_name . ':' . number_format($vendor_fee->value) . ', ';
              $anchor_bearing_fees .= '0%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->dealer_bearing . '%, ';
            }
            if ($vendor_fee->type === 'per amount') {
              $fees .=
                $vendor_fee->fee_name .
                ':' .
                number_format($vendor_fee->value) .
                ' per ' .
                $vendor_fee->per_amount .
                ', ';
              $anchor_bearing_fees .= '0%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->dealer_bearing . '%, ';
            }
          }

          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Program'] = $report->program->name;
          $data[$key]['Anchor'] = $report->program->anchor->name;
          $data[$key]['OD Account'] = $report->payment_account_number;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Limit Expiry Date'] = Carbon::parse($report->program->limit_expiry_date)->format('d M Y');
          $tenor_rates = '';
          foreach ($program_vendor_discount as $discount) {
            $tenor_rates .= $discount->from_day . ' - ' . $discount->to_day . ' Days = ' . $discount->total_roi . '%, ';
          }
          $data[$key]['Tenor Rates'] = $tenor_rates;
          $data[$key]['Base Rate Consideration'] =
            $program_vendor_discount->count() > 0 && $program_vendor_discount->first()->benchmark_title
              ? $program_vendor_discount->first()->benchmark_title .
                ' (' .
                $program_vendor_discount->first()->benchmark_rate .
                '%)'
              : '0%';
          $data[$key]['Eligibility'] = $report->eligibility . '%';
          $data[$key]['Auto Approve Financing'] = $report->auto_approve_finance ? 'Yes' : 'No';
          $data[$key]['Anchor Bearing'] = '0%';
          $data[$key]['Fees/Charges'] = $fees;
          $data[$key]['Anchor Bearing Fees/Charges'] = $anchor_bearing_fees;
          $data[$key]['Vendor Bearing Fees/Charges'] = $vendor_bearing_fees;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'DF - Program Mapping  Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('DF_Program_Mapping_Report_' . $date . '.pdf');
        break;

      case 'vf-program-mapping-report':
        $report_data = $this->vfProgramMappingReport($bank, $request);

        $headers = [
          'Vendor',
          'Program',
          'Anchor',
          'Payment/OD Account',
          'Sanctioned Limit',
          'Available Amount',
          'Utilized Amount',
          'Pipeline Amount',
          'Limit Expiry Date',
          'Margin Rate',
          'Base Rate Consideration',
          'Eligibility',
          'Auto Approve Finance',
          'Anchor Bearing',
          'Fees/Charges',
          'Anchor Bearing Fees/Charges',
          'Vendor Bearing Fees/Charges',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $program_vendor_discount = ProgramVendorDiscount::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->when($report->buyer_id, function ($query) use ($report) {
              $query->where('buyer_id', $report->buyer_id);
            })
            ->first();

          $vendor_fees = ProgramVendorFee::where('program_id', $report->program_id)
            ->where('company_id', $report->company_id)
            ->when($report->buyer_id, function ($query) use ($report) {
              $query->where('buyer_id', $report->buyer_id);
            })
            ->get();

          $fees = '';
          $anchor_bearing_fees = '';
          $vendor_bearing_fees = '';

          foreach ($vendor_fees as $vendor_fee) {
            if ($vendor_fee->type === 'percentage') {
              $fees .= $vendor_fee->fee_name . ':' . $vendor_fee->value . '%, ';
              $anchor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->anchor_bearing_discount . '%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->vendor_bearing_discount . '%, ';
            }
            if ($vendor_fee->type === 'amount') {
              $fees .= $vendor_fee->fee_name . ':' . number_format($vendor_fee->value) . ', ';
              $anchor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->anchor_bearing_discount . '%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->vendor_bearing_discount . '%, ';
            }
            if ($vendor_fee->type === 'per amount') {
              $fees .=
                $vendor_fee->fee_name .
                ':' .
                number_format($vendor_fee->value) .
                ' per ' .
                $vendor_fee->per_amount .
                ', ';
              $anchor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->anchor_bearing_discount . '%, ';
              $vendor_bearing_fees .= $vendor_fee->fee_name . ':' . $vendor_fee->vendor_bearing_discount . '%, ';
            }
          }

          $data[$key]['Vendor'] = $report->buyer ? $report->buyer->name : $report->company->name;
          $data[$key]['Program'] = $report->program->name;
          $data[$key]['Anchor'] = $report->program->anchor->name;
          $data[$key]['Payment/OD Account'] = $report->payment_account_number;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Available Amount'] = number_format(
            $report->sanctioned_limit - $report->utilized_amount - $report->pipeline_amount
          );
          $data[$key]['Utilized Amount'] = number_format($report->utilized_amount);
          $data[$key]['Pipeline Amount'] = number_format($report->pipeline_amount);
          $data[$key]['Limit Expiry Date'] = Carbon::parse($report->program->limit_expiry_date)->format('d M Y');
          $data[$key]['Margin Rate'] = $report->discount->total_roi . '%';
          $data[$key]['Base Rate Consideration'] =
            $program_vendor_discount && $program_vendor_discount->benchmark_title
              ? $program_vendor_discount->benchmark_title . ' (' . $program_vendor_discount->benchmark_rate . '%)'
              : '0%';
          $data[$key]['Eligibility'] = $report->eligibility . '%';
          $data[$key]['Auto Approve Financing'] = $report->auto_approve_finance ? 'Yes' : 'No';
          $data[$key]['Anchor Bearing'] = $report->discount->anchor_discount_bearing . '%';
          $data[$key]['Fees/Charges'] = $fees;
          $data[$key]['Anchor Bearing Fees/Charges'] = $anchor_bearing_fees;
          $data[$key]['Vendor Bearing Fees/Charges'] = $vendor_bearing_fees;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'VF Program Mapping Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('VF_Program_Mapping_Report_' . $date . '.pdf');
        break;
      case 'loans-pending-approval-report':
        $report_data = $this->loansPendingApprovalReport($bank, $request);

        $headers = [
          'Invoice No',
          'Vendor',
          'Anchor',
          'Request Date',
          'Requested Disbursement Date',
          'PI Amount',
          'Requested Amount',
          'Due Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Invoice No' => $report->invoice->invoice_number,
            'Vendor' => $report->invoice->company->name,
            'Anchor' => $report->invoice->program->anchor->name,
            'Request Date' => $report->created_at->format('d M Y'),
            'Requested Disbursement Date' => Carbon::parse($report->payment_request_date)->format('d M Y'),
            'PI Amount' => number_format($report->invoice->invoice_total_amount),
            'Requested Amount' => number_format($report->amount),
            'Due Date' => Carbon::parse($report->invoice->due_date)->format('d M Y'),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Loans Pending Approval Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Loans_pending_approval_report_' . $date . '.pdf');
        break;

      case 'loans-pending-disbursal-report':
        $report_data = $this->loansPendingDisbursalReport($bank, $request);

        $headers = [
          'Invoice No',
          'Vendor',
          'Anchor',
          'Request Date',
          'Requested Disbursement Date',
          'PI Amount',
          'Requested Amount',
          'Due Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Invoice No' => $report->invoice->invoice_number,
            'Vendor' => $report->invoice->company->name,
            'Anchor' => $report->invoice->program->anchor->name,
            'Request Date' => $report->created_at->format('d M Y'),
            'Requested Disbursement Date' => Carbon::parse($report->payment_request_date)->format('d M Y'),
            'PI Amount' => number_format($report->invoice->invoice_total_amount),
            'Requested Amount' => number_format($report->amount),
            'Due Date' => Carbon::parse($report->invoice->due_date)->format('d M Y'),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Loan Pending Disbursal Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Loans_pending_disbursal_report_' . $date . '.pdf');
        break;

      case 'user-maintenance-history-report':
        $report_data = $this->userMaintenanceHistoryReport($bank, $request);

        $headers = ['Type', 'Company', 'Name', 'Email', 'Phone Number', 'Status', 'Last Updated At', 'Last Login'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Type' => $report->company ? 'Company' : 'Bank',
            'Company' => $report->company ? $report->company->name : '-',
            'Name' => $report->user->name,
            'Email' => $report->user->email,
            'Phone Number' => $report->user->phone_number,
            'Status' => $report->active ? 'Active' : 'Inactive',
            'Last Updated At' => $report->user->updated_at->format('d M Y'),
            'Last Login' => Carbon::parse($report->user->last_login)->format('d M Y'),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'User ID Maintenance Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('User_maintenance_history_report_' . $date . '.pdf');
        break;

      case 'final-rtr-report':
        $report_data = $this->finalRtrReport($bank, $request);

        $headers = [
          'CBS ID',
          'Anchor',
          'Vendor',
          'Invoice No',
          'Debit From',
          'Credit To',
          'Amount',
          'Payment Date',
          'Transaction Failed Date',
          'Product Type',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'CBS ID' => $report->cbs_id,
            'Anchor' => $report->paymentRequest
              ? ($report->paymentRequest?->invoice?->buyer
                ? $report->paymentRequest?->invoice?->buyer?->name
                : $report->paymentRequest?->invoice?->program?->anchor?->name)
              : $report->creditAccountRequest?->program?->anchor?->name,
            'Vendor' => $report->paymentRequest
              ? $report->paymentRequest?->invoice?->company?->name
              : $report->creditAccountRequest?->company?->name,
            'Invoice No' => $report->paymentRequest ? $report->paymentRequest?->invoice?->invoice_number : '-',
            'Debit From' => $report->debit_from_account,
            'Credit To' => $report->credit_to_account,
            'Amount' => number_format($report->amount),
            'Payment Date' => Carbon::parse($report->transaction_created_date)->format('d M Y'),
            'Transaction Failed Date' => Carbon::parse($report->pay_date)->format('d M Y'),
            'Product Type' => Str::title($report->product),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Final RTR Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Final_rtr_report_' . $date . '.pdf');
        break;
      case 'maturing-payments-report':
        $report_data = $this->maturingPaymentsReport($bank, $request);
        $headers = [
          'Payment Reference No',
          'Invoice No',
          'Vendor',
          'Anchor',
          'Payment Date',
          'PI Amount',
          'Payment Amount',
          'Total Outstanding',
          'Due Date',
        ];
        $data = [];
        foreach ($report_data as $key => $report) {
          $transaction_ref = '';
          if ($report->financing_status == 'disbursed' || $report->financing_status == 'closed') {
            $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
              $query->whereHas('invoice', function ($query) use ($report) {
                $query->where('id', $report->id);
              });
            })
              ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
              ->first()?->transaction_reference;
          }

          $data[$key]['Payment Reference No'] = $transaction_ref;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['PI Amount'] = number_format($report->invoice_total_amount, 2);
          $data[$key]['Payment Amount'] = number_format($report->disbursed_amount, 2);
          $data[$key]['Total Outstanding'] = number_format($report->invoice_total_amount - $report->paid_amount, 2);
          $data[$key]['Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
        }
        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Maturing Payments Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Maturing_payments_report_' . $date . '.pdf');
        break;
      case 'maturity-extended-report':
        $report_data = $this->maturityExtendedReport($bank, $request);

        $headers = [
          'Payment Account No',
          'Invoice No',
          'Vendor',
          'Anchor',
          'Original Date',
          'Changed Due Date',
          'Product Code',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Payment Account No'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Original Date'] = Carbon::parse($report->old_due_date)->format('d M Y');
          $data[$key]['Changed Due Date'] = Carbon::parse($report->due_date)->format('d M Y');
          $data[$key]['Product Code'] =
            $report->program->programType->name == Program::VENDOR_FINANCING
              ? Program::VENDOR_FINANCING
              : Program::DEALER_FINANCING;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Maturity Extended Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Maturity_extended_report_' . $date . '.pdf');
        break;
      case 'payments-report':
        $report_data = $this->paymentsReport($bank, $request);

        $headers = [
          'Debit From',
          'Credit To',
          'Amount',
          'Invoice No',
          'Pay Date',
          'Transaction Date',
          'Product Type',
          'Payment Service',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Debit From' => $report->debit_from_account,
            'Credit To' => $report->credit_to_account,
            'Amount' => number_format($report->amount),
            'Invoice No' => $report->paymentRequest?->invoice?->invoice_number,
            'Pay Date' => Carbon::parse($report->pay_date)->format('d M Y'),
            'Transaction Date' => Carbon::parse($report->transaction_date)->format('d M Y'),
            'Product Type' => Str::title($report->product),
            'Payment Service' => Str::title($report->transaction_type),
            'Status' => Str::title($report->status),
          ];
        }
        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Payments Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Payments_Report_' . $date . '.pdf');
        break;

      case 'if-payment-details-report':
        $report_data = $this->ifPaymentDetailsReport($bank, $request);
        $headers = [
          'System ID',
          'Invoice No.',
          'Anchor',
          'Vendor',
          'Disbursement Date',
          'Due Date',
          'DPD',
          'Total Outstanding',
          'Status',
        ];
        $data = [];
        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'System ID' => $report->id,
            'Invoice No' => $report->invoice_number,
            'Anchor' => $report->buyer ? $report->buyer->name : $report->program->anchor->name,
            'Vendor' => $report->company->name,
            'Disbursement Date' => Carbon::parse($report->disbursement_date)->format('d M Y'),
            'Due Date' => Carbon::parse($report->due_date)->format('d M Y'),
            'DPD' => $report->days_past_due,
            'Total Outstanding' => number_format($report->balance),
            'Status' => Str::title($report->status),
          ];
        }
        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Final RTR Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('IF_payment_details_report_' . $date . '.pdf');
        break;

      case 'distributor-limit-utilization-report':
        $report_data = $this->distributorLimitUtilizationReport($bank, $request);
        $headers = [
          'Company Name',
          'Sanctioned Limit',
          'Utilized Amount',
          'Pipeline Requests',
          'Available Limit',
          'Limit Utilized',
          'Product',
        ];
        $data = [];
        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Company Name' => $report->name,
            'Sanctioned Limit' => number_format($report->top_level_borrower_limit),
            'Utilized Amount' => number_format($report->utilized_amount),
            'Pipeline Requests' => number_format($report->pipeline_amount),
            'Available Limit' => number_format($report->available_amount),
            'Limit Utilized' => $report->utilized_percentage,
            'Product' => 'Dealer Financing',
          ];
        }
        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('Distributor_limit_utilization_report_' . $date . '.pdf');
        break;

      case 'df-potential-financing-report':
        $report_data = $this->dfPotentialFinancingReport($bank, $request);
        $headers = ['Invoice No.', 'Anchor', 'Dealer', 'Net Invoice Amount', 'Invoice Date', 'Due Date'];
        $data = [];
        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Invoice No' => $report->invoice_number,
            'Anchor' => $report->buyer ? $report->buyer->name : $report->program->anchor->name,
            'Dealer' => $report->company->name,
            'Net Invoice Amount' => number_format($report->invoice_total_amount),
            'Invoice Date' => Carbon::parse($report->invoice_date)->format('d M Y'),
            'Due Date' => Carbon::parse($report->due_date)->format('d M Y'),
          ];
        }
        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('DF_potential_financing_report_' . $date . '.pdf');
        break;

      case 'vf-potential-financing-report':
        $report_data = $this->vfPotentialFinancingReport($bank, $request);
        $headers = ['Invoice No.', 'Anchor', 'Vendor', 'Net Invoice Amount', 'Invoice Date', 'Due Date'];
        $data = [];
        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Invoice No' => $report->invoice_number,
            'Anchor' => $report->buyer ? $report->buyer->name : $report->program->anchor->name,
            'Vendor' => $report->company->name,
            'Net Invoice Amount' => number_format($report->invoice_total_amount),
            'Invoice Date' => Carbon::parse($report->invoice_date)->format('d M Y'),
            'Due Date' => Carbon::parse($report->due_date)->format('d M Y'),
          ];
        }
        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('VF_potential_financing_report_' . $date . '.pdf');
        break;

      case 'vf-overdue-invoices-report':
        $report_data = $this->vfOverdueInvoicesReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No',
          'Anchor',
          'Vendor',
          'Payment Date',
          'Due Date',
          'Invoice Amount',
          'Payment Amount',
          'Outstanding Amount',
          'Overdue Days',
          'Status',
        ];
        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'System ID' => $report->id,
            'Invoice No' => $report->invoice_number,
            'Anchor' => $report->buyer ? $report->buyer->name : $report->program->anchor->name,
            'Vendor' => $report->company->name,
            'Payment Date' => Carbon::parse($report->disbursement_date)->format('d M Y'),
            'Due Date' => Carbon::parse($report->due_date)->format('d M Y'),
            'Invoice Amount' => number_format($report->invoice_total_amount, 2),
            'Payment Amount' => number_format($report->disbursed_amount, 2),
            'Outstanding Amount' => number_format($report->balance, 2),
            'Overdue Days' => $report->days_past_due,
            'Status' => Str::title($report->status),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('VF_Overdue_Invoices_Report_' . $date . '.pdf');

      case 'df-overdue-invoices-report':
        $report_data = $this->dfOverdueInvoicesReport($bank, $request);

        $headers = [
          'System ID',
          'Invoice No',
          'Anchor',
          'Dealer',
          'Payment Date',
          'Due Date',
          'Payment Amount',
          'Outstanding Amount',
          'Overdue Days',
          'Status',
        ];
        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'System ID' => $report->id,
            'Invoice No' => $report->invoice_number,
            'Anchor' => $report->buyer ? $report->buyer->name : $report->program->anchor->name,
            'Dealer' => $report->company->name,
            'Payment Date' => Carbon::parse($report->disbursement_date)->format('d M Y'),
            'Due Date' => Carbon::parse($report->due_date)->format('d M Y'),
            'Payment Amount' => number_format($report->disbursed_amount, 2),
            'Outstanding Amount' => number_format($report->balance, 2),
            'Overdue Days' => $report->days_past_due,
            'Status' => Str::title($report->status),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('DF_Overdue_Invoices_Report_' . $date . '.pdf');

      case 'users-and-roles-report':
        $report_data = $this->usersAndRolesReport($bank, $request);

        $headers = ['User Type', 'Company', 'User', 'Email', 'Role', 'Status', 'Last Login', 'Last Login IP'];
        $data = [];

        foreach ($report_data as $key => $report) {
          $companies = '';
          if ($report->mappedCompanies->count() > 0) {
            foreach ($report->mappedCompanies as $company) {
              $companies .= $company->name . ', ';
            }
          }

          $roles = '';
          foreach ($report->roles as $role) {
            $roles .= $role->name . ', ';
          }

          $data[$key] = [
            'User Type' => $report->mappedCompanies->count() > 0 ? 'Company' : 'Bank',
            'Company' => rtrim($companies, ', '),
            'User' => $report->name,
            'Email' => $report->email,
            'Roles' => rtrim($roles, ', '),
            'Status' => Str::title($report->status),
            'Last Login' => Carbon::parse($report->last_login)->format('d M Y'),
            'Last Login IP' => $report->activity?->properties->contains('ip')
              ? $report->activity?->properties?->ip
              : '-',
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('Users_and_Roles_Report_' . $date . '.pdf');

      case 'vf-funding-limit-utilization-report':
        $report_data = $this->vfFundingLimitUtilizationReport($bank, $request);

        $headers = [
          'Organization Name',
          'Sanctioned Limit',
          'Available Limit',
          'Current Exposure',
          'Pipeline Requests',
          'Utilized Limit',
        ];
        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Organization Name'] =
            $report->program->programCode?->name === Program::VENDOR_FINANCING_RECEIVABLE
              ? $report->company->name
              : $report->buyer->name;
          $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit);
          $data[$key]['Available Limit'] = number_format(
            $report->sanctioned_limit - $report->utilized_amount - $report->pipeline_amount
          );
          $data[$key]['Current Exposure'] = number_format($report->utilized_amount);
          $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount);
          $data[$key]['Utilized Limit'] =
            round((($report->utilized_amount + $report->pipeline_amount) / $report->sanctioned_limit) * 100, 2) . '%';
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('VF_Funding_Limit_Utilization_Report_' . $date . '.pdf');

      case 'df-funding-limit-utilization-report':
        $report_data = $this->dfFundingLimitUtilizationReport($bank, $request);

        $headers = [
          'Organization Name',
          'Sanctioned Limit',
          'Available Limit',
          'Current Exposure',
          'Pipeline Requests',
          'Utilized Limit',
        ];
        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Organization Name' => $report->name,
            'Sanctioned Limit' => number_format($report->sanctioned_limit),
            'Available Limit' => number_format($report->sanctioned_limit - $report->utilized_amount),
            'Current Exposure' => number_format($report->utilized_amount),
            'Pipeline Requests' => number_format($report->pipeline_amount),
            'Utilized Limit' => $report->utilized_percentage,
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');
        return $pdf->download('DF_Funding_Limit_Utilization_Report_' . $date . '.pdf');
      case 'df-income-report':
        $report_data = $this->dfIncomeReport($bank, $request);

        $headers = [
          'Dealer',
          'Anchor',
          'Invoice No',
          'Payment Date',
          'Invoice Amount',
          'Credit Note Amount',
          'Principle Balance',
          'Fees/Charges',
          'Total Posted Discount',
          'Total Posted Penal Discount',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
            $query->whereHas('invoice', function ($query) use ($report) {
              $query->where('id', $report->id);
            });
          })
            ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
            ->first()?->transaction_reference;

          $credit_note_amount = InvoiceFee::where('invoice_id', $report->id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->calculated_total_amount + $credit_note_amount, 2);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Principle Balance'] = number_format($report->balance, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Total Posted Penal Discount'] = number_format($report->overdue_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
          $data[$key]['Transaction Reference'] = $transaction_ref;
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_income_report_' . $date . '.pdf');
        break;

      case 'df-fees-and-interest-sharing-report':
        $report_data = $this->dfFeesAndInterestSharingReport($bank, $request);

        $headers = [
          'Dealer',
          'Invoice No',
          'Payment Date',
          'Payment Amount',
          'Total Posted Discount',
          'Anchor Discount Share',
          'Fees/Charges',
          'Anchor Fees Share',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Dealer' => $report->company->name,
            'Invoice No' => $report->invoice_number,
            'Payment Date' => Carbon::parse($report->disbursement_date)->format('d M Y'),
            'Payment Amount' => number_format($report->paymentRequests->first()->amount),
            'Total Posted Discount' => number_format(
              $report->drawdown_amount - $report->paymentRequests->first()->amount
            ),
            'Anchor Discount Share' => 0,
            'Fees/Charges' => number_format($report->fees),
            'Anchor Fees Share' => 0,
            'Status' => Str::title($report->status),
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_fees_charges_and_interest_sharing_report_' . $date . '.pdf');
        break;

      case 'df-overdue-report':
        $report_data = $this->dfOverdueReport($bank, $request);

        $headers = [
          'OD Account',
          'Dealer',
          'Anchor',
          'Invoice No',
          'Drawdown Date',
          'Overdue Principle',
          'Principle DPD',
          'Overdue Interest',
          'Interest DPD',
          'Total Penal Interest',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Dealer'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Drawdown Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Overdue Principle'] = number_format($report->invoice_total_amount);
          $data[$key]['Principle DPD'] = $report->days_past_due;
          $data[$key]['Overdue Interest'] = number_format($report->overdue_amount);
          $data[$key]['Interest DPD'] = $report->days_past_due;
          $data[$key]['Total Penal Interest'] = number_format($report->overdue_amount);
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_overdue_report_' . $date . '.pdf');
        break;
      case 'df-od-ledger-report':
        $report_data = $this->dfOdLedgerReport($bank, $request);

        $headers = [
          'OD Account',
          'Dealer',
          'Invoice No',
          'Date',
          'Transaction Type',
          'Debit',
          'Credit',
          'Principle Balance',
          'Discount',
          'Penal Discount',
          'Overdue Date',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Dealer'] = $report->paymentRequest
            ? $report->paymentRequest->invoice->company->name
            : $report->creditAccountRequest->company->name;
          $data[$key]['Invoice No'] = $report->paymentRequest ? $report->paymentRequest->invoice->invoice_number : '-';
          $data[$key]['Date'] = $report->created_at->format('d M Y');
          $data[$key]['Transaction Type'] = $report->transaction_reference;
          $data[$key]['Debit'] = $report->paymentRequest ? number_format($report->amount) : 0;
          $data[$key]['Credit'] = $report->creditAccountRequest ? number_format($report->amount) : 0;
          $data[$key]['Principle Balance'] = $report->paymentRequest
            ? number_format($report->paymentRequest->invoice->invoice_total_amount)
            : 0;
          $data[$key]['Discount'] = 0;
          $data[$key]['Penal Discount'] = 0;
          $data[$key]['Overdue Date'] = $report->paymentRequest
            ? Carbon::parse($report->paymentRequest->invoice->due_date)->format('d M Y')
            : '-';
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_od_ledger_report_' . $date . '.pdf');
        break;

      case 'vf-fees-and-interest-sharing-report':
        $report_data = $this->vfFeesAndInterestSharingReport($bank, $request);

        $headers = [
          'Vendor',
          'Invoice No',
          'Payment Date',
          'Payment Amount',
          'Total Posted Discount',
          'Anchor Discount Share',
          'Fees/Charges',
          'Anchor Fees Share',
          'Status',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Payment Amount'] = number_format($report->disbursed_amount, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Anchor Discount Share'] = number_format($report->anchor_discount_bearing_amount, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Anchor Fees Share'] = number_format($report->anchor_fee_bearing_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('VF_fees_charges_and_interest_sharing_report_' . $date . '.pdf');
        break;

      case 'vf-income-report':
        $report_data = $this->factoringIncomeReport($bank, $request);

        $headers = [
          'Vendor',
          'Anchor',
          'Invoice No',
          'Payment Date',
          'Invoice Amount',
          'Credit Note Amount',
          'Principle Balance',
          'Fees/Charges',
          'Total Posted Discount',
          'Total Posted Penal Discount',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
            $query->whereHas('invoice', function ($query) use ($report) {
              $query->where('id', $report->id);
            });
          })
            ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
            ->first()?->transaction_reference;

          $credit_note_amount = InvoiceFee::where('invoice_id', $report->id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->calculated_total_amount + $credit_note_amount, 2);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Principle Balance'] = number_format($report->balance, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Total Posted Penal Discount'] = number_format($report->overdue_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
          $data[$key]['Transaction Reference'] = $transaction_ref;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'VF - Income Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('VF_income_report_' . $date . '.pdf');
        break;
      case 'factoring-income-report':
        $report_data = $this->factoringIncomeReport($bank, $request);

        $headers = [
          'Vendor',
          'Anchor',
          'Invoice No',
          'Payment Date',
          'Invoice Amount',
          'Credit Note Amount',
          'Principle Balance',
          'Fees/Charges',
          'Total Posted Discount',
          'Total Posted Penal Discount',
          'Status',
          'Transaction Reference',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) use ($report) {
            $query->whereHas('invoice', function ($query) use ($report) {
              $query->where('id', $report->id);
            });
          })
            ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
            ->first()?->transaction_reference;

          $credit_note_amount = InvoiceFee::where('invoice_id', $report->id)
            ->where('name', 'Credit Note Amount')
            ->sum('amount');

          $data[$key]['Vendor'] = $report->company->name;
          $data[$key]['Anchor'] = $report->buyer ? $report->buyer->name : $report->program->anchor->name;
          $data[$key]['Invoice No'] = $report->invoice_number;
          $data[$key]['Payment Date'] = Carbon::parse($report->disbursement_date)->format('d M Y');
          $data[$key]['Invoice Amount'] = number_format($report->calculated_total_amount + $credit_note_amount, 2);
          $data[$key]['Credit Note Amount'] = number_format($credit_note_amount, 2);
          $data[$key]['Principle Balance'] = number_format($report->balance, 2);
          $data[$key]['Fees/Charges'] = number_format($report->program_fees, 2);
          $data[$key]['Total Posted Discount'] = number_format($report->discount, 2);
          $data[$key]['Total Posted Penal Discount'] = number_format($report->overdue_amount, 2);
          $data[$key]['Status'] = Str::title($report->financing_status);
          $data[$key]['Transaction Reference'] = $transaction_ref;
        }

        $pdf = Pdf::loadView('pdf.report', [
          'reportTitle' => 'Factoring Income Report',
          'headers' => $headers,
          'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Factoring_income_report_' . $date . '.pdf');
        break;
      case 'vendors-daily-outstanding-balance-report':
        $report_data = $this->vendorsDailyOutstandingBalanceReport($bank, $request);

        $headers = [
          'Date',
          'Vendor',
          'Outstanding Balance',
          'Sanctioned Limit',
          'Pipeline Requests',
          'Available Limit',
          'Limit Utilized(%)',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Date' => now()->format('d M Y'),
            'Vendor' => $report->buyer
              ? $report->buyer->name . ' (' . $report->payment_account_number . ')'
              : $report->company->name . ' (' . $report->payment_account_number . ')',
            'Outstanding Balance' => number_format($report->utilized_amount),
            'Sanctioned Limit' => number_format($report->sanctioned_limit),
            'Pipeline Requests' => number_format($report->pipeline_amount),
            'Available Limit' => number_format(
              $report->sanctioned_limit - ($report->pipeline_amount + $report->utilized_amount)
            ),
            'Limit Utilized(%)' => $report->utilized_percentage_ratio . '%',
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('Vendor_daily_outstanding_balance_report_' . $date . '.pdf');
        break;
      case 'dealers-daily-outstanding-balance-report':
        $report_data = $this->dealersDailyOutstandingBalanceReport($bank, $request);

        $headers = [
          'Date',
          'Dealer',
          'Outstanding Balance',
          'Sanctioned Limit',
          'Pipeline Requests',
          'Available Limit',
          'Limit Utilized(%)',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key] = [
            'Date' => $report->created_at->format('d M Y'),
            'Dealer' => $report->company->name,
            'Outstanding Balance' => number_format($report->utilized),
            'Sanctioned Limit' => number_format($report->sanctioned_limit),
            'Pipeline Requests' => number_format($report->pipeline),
            'Available Limit' => number_format($report->sanctioned_limit - ($report->pipeline + $report->utilized)),
            'Limit Utilized(%)' => $report->utilized_percentage_ratio . '%',
          ];
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('Dealers_daily_outstanding_balance_report_' . $date . '.pdf');
        break;
      case 'cron-logs':
        $report_data = $this->cronLogs($bank, $request);

        $headers = ['System ID', 'Cron Name', 'Date', 'Status', 'Start Time', 'End Time', 'Time Taken'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['System ID'] = $report->id;
          $data[$key]['Cron Name'] = $report->name;
          $data[$key]['Date'] = $report->created_at->format('d M Y');
          $data[$key]['Status'] = $report->status;
          $data[$key]['Start Time'] = Carbon::parse($report->start_time)->format('H:i A');
          $data[$key]['End Time'] = Carbon::parse($report->end_time)->format('H:i A');
          $data[$key]['Time Taken'] = $report->end_time
            ? Carbon::parse($report->start_time)->diffInMinutes(Carbon::parse($report->end_time))
            : '-';
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('Cron_logs_' . $date . '.pdf');
        break;
      case 'df-collection-report':
        $report_data = $this->dfCollectionReport($bank, $request);

        $headers = ['OD Account', 'Dealer', 'Invoice/Unique Ref No.', 'Date', 'Transaction Ref', 'Amount', 'Account'];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Dealer'] = $report->paymentRequest->invoice->company->name;
          $data[$key]['Invoice/Unique Ref No.'] = $report->paymentRequest->invoice->invoice_number;
          $data[$key]['Date'] = Carbon::parse($report->pay_date)->format('d M Y');
          $data[$key]['Transaction Ref'] = $report->transaction_reference;
          $data[$key]['Amount'] = number_format($report->amount, 2);
          $data[$key]['Account'] = $report->credit_to_account;
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_Collection_report_' . $date . '.pdf');
        break;
      case 'vf-repayment-details-report':
        $report_data = $this->vfRepaymentDetailsReport($bank, $request);

        $headers = [
          'Date',
          'CBS ID',
          'Total Repayment Amount',
          'Set Off Amount',
          'Balance Amount',
          'Set Off Particulars',
          'Set Off Type',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Date'] = Carbon::parse($report->transaction_created_date)->format('d M Y');
          $data[$key]['CBS ID'] = $report->id;
          $data[$key]['Program Name'] = $report->paymentRequest->invoice->program->name;
          $data[$key]['Total Repayment Amount'] = number_format($report->amount, 2);
          $data[$key]['Set Off Amount'] = number_format($report->paymentRequest->invoice->invoice_total_amount, 2);
          $data[$key]['Balance Amount'] = number_format($report->paymentRequest->invoice->balance, 2);
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Particulars'] =
              'Penal Interest for ' . $report->paymentRequest->invoice->invoice_number;
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Particulars'] = 'Principle For ' . $report->paymentRequest->invoice->invoice_number;
          } else {
            $data[$key]['Set Off Particulars'] = 'Interest For ' . $report->paymentRequest->invoice->invoice_number;
          }
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Type'] = 'Penal Interest';
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Type'] = 'Principle';
          } else {
            $data[$key]['Set Off Type'] = 'Interest';
          }
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('VF_Repayment_details_report_' . $date . '.pdf');
        break;
      case 'df-repayment-details-report':
        $report_data = $this->dfRepaymentDetailsReport($bank, $request);

        $headers = [
          'Date',
          'CBS ID',
          'Total Repayment Amount',
          'Set Off Amount',
          'Balance Amount',
          'Set Off Particulars',
          'Set Off Type',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['Date'] = Carbon::parse($report->transaction_created_date)->format('d M Y');
          $data[$key]['CBS ID'] = $report->id;
          $data[$key]['Program Name'] = $report->paymentRequest->invoice->program->name;
          $data[$key]['Total Repayment Amount'] = number_format($report->amount, 2);
          $data[$key]['Set Off Amount'] = number_format($report->paymentRequest->invoice->invoice_total_amount, 2);
          $data[$key]['Balance Amount'] = number_format($report->paymentRequest->invoice->balance, 2);
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Particulars'] =
              'Penal Interest for ' . $report->paymentRequest->invoice->invoice_number;
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Particulars'] = 'Principle For ' . $report->paymentRequest->invoice->invoice_number;
          } else {
            $data[$key]['Set Off Particulars'] = 'Interest For ' . $report->paymentRequest->invoice->invoice_number;
          }
          if (
            Carbon::parse($report->transaction_created_date)->greaterThan(
              Carbon::parse($report->paymentRequest->invoice->due_date)
            )
          ) {
            $data[$key]['Set Off Type'] = 'Penal Interest';
          } elseif (
            Carbon::parse($report->transaction_created_date)->equalTo($report->paymentRequest->invoice->due_date)
          ) {
            $data[$key]['Set Off Type'] = 'Principle';
          } else {
            $data[$key]['Set Off Type'] = 'Interest';
          }
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_Repayment_details_report_' . $date . '.pdf');
        break;
      case 'df-monthly-utilization-and-outstanding-report':
        $report_data = $this->dfMonthlyUtilizationAndOutstandingReport($bank, $request);
        $headers = [
          'OD Account',
          'Month',
          'Total OD Limit',
          'Utilized Limit',
          'Principle Outstanding',
          'Interest Outstanding',
          'Principle DPD (Days)',
          'Principle DPD Amount',
          'Interest DPD (Days)',
          'Interest DPD Amount',
        ];

        $data = [];

        foreach ($report_data as $key => $report) {
          $data[$key]['OD Account'] = $report->vendor_configurations->payment_account_number;
          $data[$key]['Month'] = $report->created_at->format('M Y');
          $data[$key]['Total OD Limit'] = number_format($report->vendor_configurations->sanctioned_limit);
          $data[$key]['Utilized Limit'] = $report->vendor_configurations->utilized_percentage_ratio;
          $data[$key]['Principle Outstanding'] = number_format($report->paymentRequest->invoice->disbursed_amount, 2);
          $data[$key]['Interest Outstanding'] = number_format($report->paymentRequest->invoice->balance, 2);
          $data[$key]['Principle DPD (Days)'] = number_format($report->paymentRequest->invoice->days_past_due);
          $data[$key]['Principle DPD Amount'] = number_format($report->paymentRequest->invoice->overdue_amount, 2);
          $data[$key]['Interest DPD (Days)'] = number_format($report->paymentRequest->invoice->days_past_due);
          $data[$key]['Interest DPD Amount'] = number_format($report->paymentRequest->invoice->overdue_amount, 2);
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('DF_Monthly_utilization_and_outstanding_report_' . $date . '.pdf');
        break;
      case 'bank-gls-report':
        $report_data = $this->bankGlsReports($bank, $request);
        $headers = ['Account Name', 'Account Number', 'Balance', 'Created At'];

        $data = [];

        foreach ($report_data as $key => $report) {
          // Get Amount Credited to Account
          $credit_amount = CbsTransaction::where('bank_id', $bank->id)
            ->where('credit_to_account', $report->account_number)
            ->sum('amount');
          $debit_amount = CbsTransaction::where('bank_id', $bank->id)
            ->where('debit_from_account', $report->account_number)
            ->sum('amount');
          $data[$key]['Account Name'] = $report->account_name;
          $data[$key]['Account Number'] = $report->account_number;
          $data[$key]['Balance'] = number_format($credit_amount - $debit_amount, 2);
          $data[$key]['Created At'] = $report->created_at->format('M Y');
        }

        $pdf = Pdf::loadView('pdf.report', ['headers' => $headers, 'data' => $data])->setPaper('a4', 'landscape');

        return $pdf->download('Bank_GLs_report_' . $date . '.pdf');
        break;
      default:
        return response()->json(['message' => 'Invalid report type'], 400);
        break;
    }
  }

  public function reportData(Bank $bank, Request $request)
  {
    $type = $request->query('type');
    $data = null;

    switch ($type) {
      case 'all-payments-report':
        $data = $this->allPaymentsReport($bank, $request);
        break;
      case 'cron-logs':
        $data = $this->cronLogs($bank, $request);
        break;
      case 'drawdown-details-report':
        $data = $this->drawdownDetails($bank, $request);
        break;
      case 'inactive-users-report':
        $data = $this->inactiveUsersReport($bank, $request);
        break;
      case 'payments-report':
        $data = $this->paymentsReport($bank, $request);
        break;
      case 'dealer-financing-programs-report':
        $data = $this->dealerFinancingPrograms($bank, $request);
        break;
      case 'rejected-loans-report':
        $data = $this->rejectedLoans($bank, $request);
        break;
      case 'df-anchorwise-dealer-report':
        $data = $this->dfAnchorwiseDealerReport($bank, $request);
        break;
      case 'df-dealer-classification-report':
        $data = $this->dfDealerClassificationReport($bank, $request);
        break;
      case 'vendor-financing-programs-report':
        $data = $this->vendorFinancingPrograms($bank, $request);
        break;
      case 'vf-anchorwise-vendor-report':
        $data = $this->vfAnchorwiseVendorReport($bank, $request);
        break;
      case 'vf-vendor-classification-report':
        $data = $this->vfVendorClassificationReport($bank, $request);
        break;
      case 'df-program-mapping-report':
        $data = $this->dfProgramMappingReport($bank, $request);
        break;
      case 'vf-program-mapping-report':
        $data = $this->vfProgramMappingReport($bank, $request);
        break;
      case 'loans-pending-approval-report':
        $data = $this->loansPendingApprovalReport($bank, $request);
        break;
      case 'loans-pending-disbursal-report':
        $data = $this->loansPendingDisbursalReport($bank, $request);
        break;
      case 'user-maintenance-history-report':
        $data = $this->userMaintenanceHistoryReport($bank, $request);
        break;
      case 'final-rtr-report':
        $data = $this->finalRtrReport($bank, $request);
        break;
      case 'maturing-payments-report':
        $data = $this->maturingPaymentsReport($bank, $request);
        break;
      case 'if-payment-details-report':
        $data = $this->ifPaymentDetailsReport($bank, $request);
        break;
      case 'distributor-limit-utilization-report':
        $data = $this->distributorLimitUtilizationReport($bank, $request);
        break;
      case 'df-potential-financing-report':
        $data = $this->dfPotentialFinancingReport($bank, $request);
        break;
      case 'vf-potential-financing-report':
        $data = $this->vfPotentialFinancingReport($bank, $request);
        break;
      case 'vf-overdue-invoices-report':
        $data = $this->vfOverdueInvoicesReport($bank, $request);
        break;
      case 'df-overdue-invoices-report':
        $data = $this->dfOverdueInvoicesReport($bank, $request);
        break;
      case 'df-overdue-report':
        $data = $this->dfOverdueReport($bank, $request);
        break;
      case 'users-and-roles-report':
        $data = $this->usersAndRolesReport($bank, $request);
        break;
      case 'vf-funding-limit-utilization-report':
        $data = $this->vfFundingLimitUtilizationReport($bank, $request);
        break;
      case 'df-funding-limit-utilization-report':
        $data = $this->dfFundingLimitUtilizationReport($bank, $request);
        break;
      case 'df-income-report':
        $data = $this->dfIncomeReport($bank, $request);
        break;
      case 'df-fees-and-interest-sharing-report':
        $data = $this->dfFeesAndInterestSharingReport($bank, $request);
        break;
      case 'vf-fees-and-interest-sharing-report':
        $data = $this->vfFeesAndInterestSharingReport($bank, $request);
        break;
      case 'vf-income-report':
        $data = $this->vfIncomeReport($bank, $request);
        break;
      case 'factoring-income-report':
        $data = $this->factoringIncomeReport($bank, $request);
        break;
      case 'vendors-daily-outstanding-balance-report':
        $data = $this->vendorsDailyOutstandingBalanceReport($bank, $request);
        break;
      case 'dealers-daily-outstanding-balance-report':
        $data = $this->dealersDailyOutstandingBalanceReport($bank, $request);
        break;
      case 'vf-repayment-details-report':
        $data = $this->vfRepaymentDetailsReport($bank, $request);
        break;
      case 'df-repayment-details-report':
        $data = $this->dfRepaymentDetailsReport($bank, $request);
        break;
      case 'df-od-ledger-report':
        $data = $this->dfOdLedgerReport($bank, $request);
        break;
      case 'df-collection-report':
        $data = $this->dfCollectionReport($bank, $request);
        break;
      case 'maturity-extended-report':
        $data = $this->maturityExtendedReport($bank, $request);
        break;
      case 'df-monthly-utilization-and-outstanding-report':
        $data = $this->dfMonthlyUtilizationAndOutstandingReport($bank, $request);
        break;
      case 'bank-gls-report':
        $data = $this->bankGlsReports($bank, $request);
        break;
      default:
        # code...
        break;
    }

    return $data;
  }

  public function allPaymentsReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $invoice_no = $request->query('invoice_no');
    $pi_number = $request->query('pi_number');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $from_due_date = $request->query('from_due_date');
    $to_due_date = $request->query('to_due_date');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');
    $product_type = $request->query('product_type');

    $payments = CbsTransaction::with('paymentRequest.invoice')
      ->where('bank_id', $bank->id)
      ->when($product_type && $product_type != '', function ($query) use ($product_type) {
        $query->whereHas('paymentRequest', function ($query) use ($product_type) {
          $query->whereHas('invoice', function ($query) use ($product_type) {
            $query->whereHas('program', function ($query) use ($product_type) {
              switch ($product_type) {
                case 'vendor_financing_receivable':
                  $query->whereHas('programCode', function ($query) {
                    $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  });
                  break;
                case 'factoring_with_recourse':
                  $query->whereHas('programCode', function ($query) {
                    $query->where('name', Program::FACTORING_WITH_RECOURSE);
                  });
                  break;
                case 'factoring_without_recourse':
                  $query->whereHas('programCode', function ($query) {
                    $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                  });
                  break;
                case 'dealer_financing':
                  $query->whereHas('programType', function ($query) {
                    $query->where('name', Program::DEALER_FINANCING);
                  });
                  break;
                default:
                  break;
              }
            });
          });
        });
      })
      ->whereHas('paymentRequest', function ($query) use ($invoice_no, $pi_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_no, $pi_number) {
          $query
            ->whereIn('financing_status', ['disbursed', 'closed'])
            ->when($invoice_no && $invoice_no != '', function ($query) use ($invoice_no) {
              $query->where('invoice_number', 'LIKE', '%' . $invoice_no . '%');
            })
            ->when($pi_number && $pi_number != '', function ($query) use ($pi_number) {
              $query->where('pi_number', 'LIKE', '%' . $pi_number . '%');
            });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->where(function ($query) use ($anchor) {
          $query->whereHas('paymentRequest', function ($query) use ($anchor) {
            $query->whereHas('invoice', function ($query) use ($anchor) {
              $query
                ->whereHas('program', function ($query) use ($anchor) {
                  $query->whereHas('anchor', function ($query) use ($anchor) {
                    $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
                  });
                })
                ->orWhereHas('buyer', function ($query) use ($anchor) {
                  $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
                });
            });
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('paymentRequest', function ($query) use ($vendor) {
          $query->whereHas('invoice', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '%');
            });
          });
        });
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('pay_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('pay_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('pay_date', '<=', $to_payment_date);
      })
      ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
        $query->whereHas('paymentRequest', function ($query) use ($from_due_date) {
          $query->whereHas('invoice', function ($query) use ($from_due_date) {
            $query->whereDate('due_date', '>=', $from_due_date);
          });
        });
      })
      ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
        $query->whereHas('paymentRequest', function ($query) use ($to_due_date) {
          $query->whereHas('invoice', function ($query) use ($to_due_date) {
            $query->whereDate('due_date', '<=', $to_due_date);
          });
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->whereDate('status', $status);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        if ($sort_by == 'invoice_no_asc') {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->orderBy('invoice_number', 'ASC');
            });
          });
        }
        if ($sort_by == 'invoice_no_desc') {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->orderBy('invoice_number', 'DESC');
            });
          });
        }
        if ($sort_by == 'payment_amount_asc') {
          $query->orderBy('amount', 'ASC');
        }
        if ($sort_by == 'payment_amount_desc') {
          $query->orderBy('amount', 'DESC');
        }
        if ($sort_by == 'anchor_asc') {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('program', function ($query) {
                $query->whereHas('anchor', function ($query) {
                  $query->orderBy('name', 'ASC');
                });
              });
            });
          });
        }
        if ($sort_by == 'anchor_desc') {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('program', function ($query) {
                $query->whereHas('anchor', function ($query) {
                  $query->orderBy('name', 'DESC');
                });
              });
            });
          });
        }
        if ($sort_by == 'vendor_asc') {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('company', function ($query) {
                $query->orderBy('name', 'ASC');
              });
            });
          });
        }
        if ($sort_by == 'vendor_desc') {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('company', function ($query) {
                $query->orderBy('name', 'DESC');
              });
            });
          });
        }
      })
      ->when(!$sort_by || ($sort_by && $sort_by == ''), function ($query) {
        $query->latest();
      })
      ->where('transaction_type', 'Payment Disbursement');

    if ($per_page) {
      $payments = $payments->paginate($per_page);
      $payments = CbsTransactionResource::collection($payments)
        ->response()
        ->getData();
    } else {
      $payments = $payments->get();
    }

    return $payments;
  }

  public function cronLogs(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $date = $request->query('date');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $sort_by = $request->query('sort_by');

    $data = CronLog::where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($date && $date != '', function ($query) use ($date) {
        $query->whereDate('created_at', $date);
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('created_at', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('created_at', '<=', $to_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->latest();
      })
      ->paginate($per_page);

    return $data;
  }

  public function paymentsReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $cbs_id = $request->query('cbs_id');
    $invoice_number = $request->query('invoice_number');
    $transaction_ref = $request->query('transaction_ref');
    $account = $request->query('account');
    $product_type = $request->query('product_type');
    $sort_by = $request->query('sort_by');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $transaction_type = $request->query('transaction_type');
    $vendor = $request->query('vendor');

    $cbs_transactions = CbsTransaction::with(['paymentRequest.invoice'])
      ->where('bank_id', $bank->id)
      ->where('status', 'Successful')
      ->when(
        ($cbs_id && $cbs_id != '') || ($account && $account != '') || ($product_type && $product_type != ''),
        function ($query) use ($cbs_id, $account, $product_type) {
          $query->where(function ($query) use ($cbs_id, $account, $product_type) {
            $query
              ->when($product_type && $product_type != '', function ($query) use ($product_type) {
                $query->whereHas('paymentRequest', function ($query) use ($product_type) {
                  $query->whereHas('invoice', function ($query) use ($product_type) {
                    $query->whereHas('program', function ($query) use ($product_type) {
                      switch ($product_type) {
                        case 'vendor_financing_receivable':
                          $query->whereHas('programCode', function ($query) {
                            $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                          });
                          break;
                        case 'factoring_with_recourse':
                          $query->whereHas('programCode', function ($query) {
                            $query->where('name', Program::FACTORING_WITH_RECOURSE);
                          });
                          break;
                        case 'factoring_without_recourse':
                          $query->whereHas('programCode', function ($query) {
                            $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                          });
                          break;
                        case 'dealer_financing':
                          $query->whereHas('programType', function ($query) {
                            $query->where('name', Program::DEALER_FINANCING);
                          });
                          break;
                        default:
                          break;
                      }
                    });
                  });
                });
              })
              ->when($account && $account != '', function ($query) use ($account) {
                $query
                  ->where('debit_from_account', 'LIKE', '%' . $account . '%')
                  ->orWhere('credit_to_account', 'LIKE', '%' . $account . '%')
                  ->orWhere('debit_from_account_name', 'LIKE', '%' . $account . '%')
                  ->orWhere('credit_to_account_name', 'LIKE', '%' . $account . '%');
              })
              ->when($cbs_id && $cbs_id != '', function ($query) use ($cbs_id) {
                $query->where('id', $cbs_id);
              });
          });
        }
      )
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('paymentRequest', function ($query) use ($invoice_number) {
          $query->whereHas('invoice', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('paymentRequest', function ($query) use ($vendor) {
          $query->whereHas('invoice', function ($query) use ($vendor) {
            $query->whereHas('company', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '%');
            });
          });
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('transaction_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('transaction_date', '<=', $to_date);
      })
      ->when($transaction_type && count($transaction_type) > 0, function ($query) use ($transaction_type) {
        $query->whereIn('transaction_type', $transaction_type);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('id', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('id', 'DESC');
      });

    if ($per_page) {
      $cbs_transactions = $cbs_transactions->paginate($per_page);
      $cbs_transactions = CbsTransactionResource::collection($cbs_transactions)
        ->response()
        ->getData();
    } else {
      $cbs_transactions = $cbs_transactions->get();

      // foreach ($cbs_transactions as $cbs_transaction) {
      //   $cbs_transaction->paymentRequest['anchor'] = $cbs_transaction->paymentRequest?->invoice?->program->anchor;
      //   if ($cbs_transaction->paymentRequest && $cbs_transaction->paymentRequest?->invoice?->program->programType->name == 'Vendor Financing' && ($cbs_transaction->paymentRequest?->invoice?->program->programCode->name == 'Factoring With Recourse' || $cbs_transaction->paymentRequest?->invoice?->program->programCode->name == 'Factoring Without Recourse')) {
      //     $cbs_transaction->paymentRequest['buyer'] = $cbs_transaction->paymentRequest?->invoice->buyer;
      //   }
      // }
    }

    return $cbs_transactions;
  }

  public function inactiveUsersReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $search = $request->query('search');
    $company = $request->query('company');
    $email = $request->query('email');
    $sort_by = $request->query('sort_by');
    $from_date = $request->query('from_date');
    $to_date = $request->query('from_date');

    $users = [];

    $company_ids = $bank->companies->pluck('id');
    $users_ids = CompanyUser::whereIn('company_id', $company_ids)->pluck('user_id');

    $users = User::with('roles', 'mappedCompanies')
      ->when($search && $search != '', function ($query) use ($search) {
        $query->where(function ($query) use ($search) {
          $query->where('name', 'LIKE', '%' . $search . '%');
        });
      })
      ->when($email && $email != '', function ($query) use ($email) {
        $query->where('email', 'LIKE', '%' . $email . '%');
      })
      ->when($company && $company != '', function ($query) use ($company) {
        $query->whereHas('mappedCompanies', function ($query) use ($company) {
          $query->where('name', 'LIKE', '%' . $company . '%');
        });
      })
      ->whereIn('id', $users_ids)
      ->whereDate('last_login', '<', now()->format('Y-m-d'))
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('last_login', '>=', Carbon::parse($from_date)->format('Y-m-d'));
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('last_login', '<=', Carbon::parse($to_date)->format('Y-m-d'));
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $users = $users->paginate($per_page);
    } else {
      $users = $users->get();
    }

    return $users;
  }

  public function drawdownDetails(Bank $bank, Request $request)
  {
    $payment_request_date = $request->query('payment_request_date');
    $invoice_number = $request->query('invoice_number');
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $dpd = $request->query('dpd');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');

    $payment_requests = PaymentRequest::with('invoice', 'paymentAccounts', 'cbsTransactions')
      ->whereHas('invoice', function ($query) use ($bank) {
        $query
          ->whereHas('program', function ($query) {
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
          })
          ->whereHas('company', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          });
      })
      ->where(function ($query) {
        $query
          ->whereHas('cbsTransactions', function ($query) {
            $query->where('transaction_type', 'Payment Disbursement');
          })
          ->orWhereDoesntHave('cbsTransactions');
      })
      ->when($payment_request_date && $payment_request_date != '', function ($query) use ($payment_request_date) {
        $query->whereDate('payment_request_date', $payment_request_date);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('invoice', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->whereHas('invoice', function ($query) use ($status) {
          $query->where('status', $status)->orWhere('financing_status', $status);
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $payment_requests = $payment_requests->paginate($per_page);

      $payment_requests = PaymentRequestResource::collection($payment_requests)
        ->response()
        ->getData();
    } else {
      $payment_requests = $payment_requests->get();

      foreach ($payment_requests as $payment_request) {
        $payment_request->invoice['vendor_discount_details'] = ProgramVendorDiscount::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_configurations'] = ProgramVendorConfiguration::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_fee_details'] = ProgramVendorFee::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_contact_details'] = ProgramVendorContactDetail::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_bank_details'] = ProgramVendorBankDetail::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->get();
        $payment_request->invoice->program['bank_details'] = ProgramBankDetails::where(
          'program_id',
          $payment_request->invoice->program_id
        )->first();
        $payment_request->invoice['vendor_fees'] = $payment_request->fees;
      }
    }

    return $payment_requests;
  }

  public function dealerFinancingPrograms(Bank $bank, Request $request)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');

    $programs = Program::with('programType', 'programCode', 'discountDetails', 'dealerDiscountRates', 'bank', 'anchor')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('account_status', $status);
      })
      ->whereHas('programType', function ($query) {
        $query->where('name', Program::DEALER_FINANCING);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $programs = $programs->paginate($per_page);
    } else {
      $programs = $programs->get();
    }

    foreach ($programs as $program) {
      $program['dealers_count'] = $program->getDealers()->count();
    }

    return $programs;
  }

  public function vendorFinancingPrograms(Bank $bank, Request $request)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');

    $programs = Program::with('programType', 'programCode', 'discountDetails', 'bank', 'anchor')
      ->where('bank_id', $bank->id)
      ->whereHas('programType', function ($query) {
        $query->where('name', Program::VENDOR_FINANCING);
      })
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('account_status', $status);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $programs = $programs->paginate($per_page);
      $programs = ProgramResource::collection($programs)
        ->response()
        ->getData();
    } else {
      $programs = $programs->get();
      foreach ($programs as $program) {
        $program['vendors_count'] = $program->getVendors()->count() + $program->getBuyers()->count();
      }
    }

    return $programs;
  }

  public function rejectedLoans(Bank $bank, Request $request)
  {
    $payment_reference_number = $request->query('payment_reference_number');
    $invoice_number = $request->query('invoice_number');
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');

    $payment_requests = PaymentRequest::whereHas('invoice', function ($query) use ($bank) {
      $query->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      });
    })
      ->when($payment_reference_number && $payment_reference_number != '', function ($query) use (
        $payment_reference_number
      ) {
        $query->where('reference_number', 'LIKE', '%' . $payment_reference_number . '%');
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('invoice', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        });
      })
      ->where('status', 'rejected')
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('updated_at', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('updated_at', '<=', $to_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $payment_requests = $payment_requests->paginate($per_page);
      $payment_requests = PaymentRequestResource::collection($payment_requests)
        ->response()
        ->getData();
    } else {
      $payment_requests = $payment_requests->get();
      foreach ($payment_requests as $payment_request) {
        $payment_request->invoice['total'] = $payment_request->invoice->total;
        $payment_request->invoice['total_taxes'] = $payment_request->invoice->total_invoice_taxes;
        $payment_request->invoice['total_fees'] = $payment_request->invoice->total_invoice_fees;
        $payment_request->invoice['total_discount'] = $payment_request->invoice->total_invoice_discount;
        $payment_request->invoice['vendor_discount_details'] = ProgramVendorDiscount::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_configurations'] = ProgramVendorConfiguration::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_fee_details'] = ProgramVendorFee::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_contact_details'] = ProgramVendorContactDetail::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
        $payment_request->invoice['vendor_bank_details'] = ProgramVendorBankDetail::where(
          'company_id',
          $payment_request->invoice->company->id
        )
          ->where('program_id', $payment_request->invoice->program_id)
          ->get();
        $payment_request->invoice->program['bank_details'] = ProgramBankDetails::where(
          'program_id',
          $payment_request->invoice->program_id
        )->first();
        $payment_request->invoice['vendor_fees'] = $payment_request->fees;
        $payment_request['eligible_for_finance'] = $payment_request->eligible_for_finance;
        $payment_request->invoice['eligible_for_finance'] = $payment_request->invoice->eligible_for_finance;
        $payment_request['discount'] = $payment_request->discount;
        $payment_request['anchor'] = $payment_request->invoice->program->getAnchor();
        if (
          $payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($payment_request->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $payment_request->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $payment_request['buyer'] = $payment_request->invoice->buyer;
        }
      }
    }

    return $payment_requests;
  }

  public function dfAnchorwiseDealerReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $sort_by = $request->query('sort_by');

    $programs = Program::with('programType', 'programCode', 'anchor')
      ->where('bank_id', $bank->id)
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->whereHas('programType', function ($query) {
        $query->where('name', Program::DEALER_FINANCING);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $programs = $programs->paginate($per_page);
    } else {
      $programs = $programs->get();
    }

    foreach ($programs as $program) {
      $program['dealers'] = $program->getDealers()->count();
      $program['active_dealers'] = $program->getActiveDealers()->count();
      $program['passive_dealers'] = $program->getPassiveDealers()->count();
      $program['active_dealers_percent'] = $program->getDealers()->count()
        ? ($program->getActiveDealers()->count() / $program->getDealers()->count()) * 100
        : 0;
    }

    return $programs;
  }

  public function dfDealerClassificationReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $dealer = $request->query('dealer');
    $unique_id = $request->query('unique_id');
    $branch_code = $request->query('branch_code');
    $rm = $request->query('rm');
    $sort_by = $request->query('sort_by');

    $dealer_role = ProgramRole::where('name', 'dealer')->first();
    $dealer_program_id = ProgramType::where('name', Program::DEALER_FINANCING)->first()->id;

    $dealers_ids = ProgramCompanyRole::where('role_id', $dealer_role->id)
      ->get()
      ->pluck('company_id')
      ->unique();

    $companies = Company::with('relationshipManagers', 'bank')
      ->where('bank_id', $bank->id)
      ->whereIn('id', $dealers_ids)
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->where('name', 'LIKE', '%' . $dealer . '%');
      })
      ->when($unique_id && $unique_id != '', function ($query) use ($unique_id) {
        $query->where('unique_identification_number', 'LIKE', '%' . $unique_id . '%');
      })
      ->when($branch_code && $branch_code != '', function ($query) use ($branch_code) {
        $query->where('branch_code', 'LIKE', '%' . $branch_code . '%');
      })
      ->when($rm && $rm != '', function ($query) use ($rm) {
        $query->whereHas('relationshipManagers', function ($query) use ($rm) {
          $query->where('name', 'LIKE', '%' . $rm . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $companies = $companies->paginate($per_page);
    } else {
      $companies = $companies->get();
    }

    foreach ($companies as $company) {
      foreach ($company->programs->where('program_type_id', $dealer_program_id) as $company_program) {
        $company['sanctioned_limit'] += $company->programConfigurations
          ->where('program_id', $company_program->id)
          ->first()
          ? $company->programConfigurations->where('program_id', $company_program->id)->first()->sanctioned_limit
          : 0;
        $company['utilized_limit'] += $company->utilizedAmount($company_program);
        $company['dpd_days'] += $company->daysPastDue($company_program);
      }
    }

    return $companies;
  }

  public function vfAnchorwiseVendorReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $sort_by = $request->query('sort_by');

    $programs = Program::with('programType', 'programCode', 'anchor')
      ->where('bank_id', $bank->id)
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->whereHas('programType', function ($query) {
        $query->where('name', Program::VENDOR_FINANCING);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $programs = $programs->paginate($per_page);
      $programs = ProgramResource::collection($programs)
        ->response()
        ->getData();
    } else {
      $programs = $programs->get();
    }

    // foreach ($programs as $program) {
    //   $program['vendors'] = $program->getVendors()->count() + $program->getBuyers()->count();
    //   $program['active_vendors'] = $program->getActiveVendors()->count() + $program->getActiveBuyers()->count();
    //   $program['passive_vendors'] = $program->getPassiveVendors()->count() + $program->getPassiveBuyers()->count();
    //   $program['active_vendors_percent'] =
    //     $program->getVendors()->count() + $program->getBuyers()->count() > 0
    //       ? (($program->getActiveVendors()->count() + $program->getActiveBuyers()->count()) /
    //           ($program->getVendors()->count() + $program->getBuyers()->count())) *
    //         100
    //       : 0;
    // }

    return $programs;
  }

  public function vfVendorClassificationReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');
    $unique_id = $request->query('unique_id');
    $branch_code = $request->query('branch_code');
    $rm = $request->query('rm');
    $sort_by = $request->query('sort_by');

    $vendor_role = ProgramRole::where('name', 'vendor')->first();
    $buyer_role = ProgramRole::where('name', 'buyer')->first();
    $vendor_program_id = ProgramType::where('name', Program::VENDOR_FINANCING)->first()->id;

    $dealers_ids = ProgramCompanyRole::whereIn('role_id', [$vendor_role->id, $buyer_role->id])
      ->get()
      ->pluck('company_id')
      ->unique();

    $companies = Company::where('bank_id', $bank->id)
      ->whereIn('id', $dealers_ids)
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->where('name', 'LIKE', '%' . $vendor . '%');
      })
      ->when($unique_id && $unique_id != '', function ($query) use ($unique_id) {
        $query->where('unique_identification_number', 'LIKE', '%' . $unique_id . '%');
      })
      ->when($branch_code && $branch_code != '', function ($query) use ($branch_code) {
        $query->where('branch_code', 'LIKE', '%' . $branch_code . '%');
      })
      ->when($rm && $rm != '', function ($query) use ($rm) {
        $query->whereHas('relationshipManagers', function ($query) use ($rm) {
          $query->where('name', 'LIKE', '%' . $rm . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $companies = $companies->paginate($per_page);
    } else {
      $companies = $companies->get();
    }

    foreach ($companies as $company) {
      if ($company->programs->count() > 0) {
        foreach ($company->programs->where('program_type_id', $vendor_program_id) as $company_program) {
          $company['sanctioned_limit'] += $company->programConfigurations
            ->where('program_id', $company_program->id)
            ->first()
            ? $company->programConfigurations->where('program_id', $company_program->id)->first()->sanctioned_limit
            : 0;
          $company['utilized_limit'] += $company->utilizedAmount($company_program);
          $company['dpd_days'] += $company->daysPastDue($company_program);
        }
      } else {
        $company['sanctioned_limit'] = 0;
        $company['utilized_limit'] = 0;
        $company['dpd_days'] = 0;
      }
    }

    return $companies;
  }

  public function dfProgramMappingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $dealer_search = $request->query('dealer');
    $anchor = $request->query('anchor');
    $program = $request->query('program_name');
    $sort_by = $request->query('sort_by');
    $od_expiry_date_from = $request->query('od_expiry_date_from');
    $od_expiry_date_to = $request->query('od_expiry_date_to');
    $limit_expiry_date_from = $request->query('limit_expiry_date_from');
    $limit_expiry_date_to = $request->query('limit_expiry_date_to');

    $dealers = ProgramVendorConfiguration::with('program', 'company')
      ->whereHas('program', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($program && $program != '', function ($query) use ($program) {
        $query->whereHas('program', function ($query) use ($program) {
          $query->where('name', 'LIKE', '%' . $program . '%');
        });
      })
      ->when($dealer_search && $dealer_search != '', function ($query) use ($dealer_search) {
        $query->whereHas('company', function ($query) use ($dealer_search) {
          $query->where('name', 'LIKE', '%' . $dealer_search . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      })
      ->when($od_expiry_date_from && $od_expiry_date_from != '', function ($query) use ($od_expiry_date_from) {
        $query->whereDate('limit_expiry_date', '>=', $od_expiry_date_from);
      })
      ->when($od_expiry_date_to && $od_expiry_date_to != '', function ($query) use ($od_expiry_date_to) {
        $query->whereDate('limit_expiry_date', '<=', $od_expiry_date_to);
      })
      ->when($limit_expiry_date_from && $limit_expiry_date_from != '', function ($query) use ($limit_expiry_date_from) {
        $query->whereHas('program', function ($query) use ($limit_expiry_date_from) {
          $query->whereDate('limit_expiry_date', '>=', $limit_expiry_date_from);
        });
      })
      ->when($limit_expiry_date_to && $limit_expiry_date_to != '', function ($query) use ($limit_expiry_date_to) {
        $query->whereHas('program', function ($query) use ($limit_expiry_date_to) {
          $query->whereDate('limit_expiry_date', '<=', $limit_expiry_date_to);
        });
      });

    if ($per_page) {
      $dealers = $dealers->paginate($per_page);
    } else {
      $dealers = $dealers->get();
    }

    foreach ($dealers as $dealer) {
      $dealer['discount'] = ProgramVendorDiscount::where('company_id', $dealer->company_id)
        ->where('program_id', $dealer->program_id)
        ->get();
      $dealer['bank_details'] = ProgramVendorBankDetail::where('company_id', $dealer->company_id)
        ->where('program_id', $dealer->program_id)
        ->first();
    }

    return $dealers;
  }

  public function vfProgramMappingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor_search = $request->query('vendor');
    $anchor = $request->query('anchor');
    $program = $request->query('program_name');
    $sort_by = $request->query('sort_by');
    $od_expiry_date_from = $request->query('od_expiry_date_from');
    $od_expiry_date_to = $request->query('od_expiry_date_to');
    $limit_expiry_date_from = $request->query('limit_expiry_date_from');
    $limit_expiry_date_to = $request->query('limit_expiry_date_to');
    $program_type = $request->query('program_type');

    $vendors = ProgramVendorConfiguration::with('program', 'company')
      ->whereHas('program', function ($query) use ($bank, $program_type) {
        $query
          ->where('bank_id', $bank->id)
          ->whereHas('programType', function ($query) use ($program_type) {
            $query->where('name', Program::VENDOR_FINANCING);
          })
          ->when($program_type && $program_type != '', function ($query) use ($program_type) {
            $query->whereHas('programCode', function ($query) use ($program_type) {
              switch ($program_type) {
                case 'vendor_financing_receivable':
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  break;
                case 'factoring_with_recourse':
                  $query->where('name', Program::FACTORING_WITH_RECOURSE);
                  break;
                case 'factoring_without_recourse':
                  $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                  break;

                default:
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  break;
              }
            });
          });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($program && $program != '', function ($query) use ($program) {
        $query->whereHas('program', function ($query) use ($program) {
          $query->where('name', 'LIKE', '%' . $program . '%');
        });
      })
      ->when($vendor_search && $vendor_search != '', function ($query) use ($vendor_search) {
        $query->whereHas('company', function ($query) use ($vendor_search) {
          $query->where('name', 'LIKE', '%' . $vendor_search . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      })
      ->when($od_expiry_date_from && $od_expiry_date_from != '', function ($query) use ($od_expiry_date_from) {
        $query->whereDate('limit_expiry_date', '>=', $od_expiry_date_from);
      })
      ->when($od_expiry_date_to && $od_expiry_date_to != '', function ($query) use ($od_expiry_date_to) {
        $query->whereDate('limit_expiry_date', '<=', $od_expiry_date_to);
      })
      ->when($limit_expiry_date_from && $limit_expiry_date_from != '', function ($query) use ($limit_expiry_date_from) {
        $query->whereHas('program', function ($query) use ($limit_expiry_date_from) {
          $query->whereDate('limit_expiry_date', '>=', $limit_expiry_date_from);
        });
      })
      ->when($limit_expiry_date_to && $limit_expiry_date_to != '', function ($query) use ($limit_expiry_date_to) {
        $query->whereHas('program', function ($query) use ($limit_expiry_date_to) {
          $query->whereDate('limit_expiry_date', '<=', $limit_expiry_date_to);
        });
      });

    if ($per_page) {
      $vendors = OdAccountsResource::collection($vendors->paginate($per_page))
        ->response()
        ->getData();
    } else {
      $vendors = $vendors->get();
      foreach ($vendors as $vendor) {
        $vendor['utilized_amount'] = $vendor->utilized_amount;
        $vendor['pipeline_amount'] = $vendor->pipeline_amount;
        if ($vendor->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $vendor['discount'] = ProgramVendorDiscount::where('company_id', $vendor->company_id)
            ->where('program_id', $vendor->program_id)
            ->first();
          $vendor['bank_details'] = ProgramVendorBankDetail::where('company_id', $vendor->company_id)
            ->where('program_id', $vendor->program_id)
            ->first();
        } elseif (
          $vendor->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
          $vendor->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE
        ) {
          $vendor['discount'] = ProgramVendorDiscount::where('buyer_id', $vendor->buyer_id)
            ->where('program_id', $vendor->program_id)
            ->first();
          $vendor['bank_details'] = ProgramVendorBankDetail::where('buyer_id', $vendor->buyer_id)
            ->where('program_id', $vendor->program_id)
            ->first();
        }
      }
    }

    return $vendors;
  }

  public function loansPendingApprovalReport(Bank $bank, Request $request)
  {
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $requested_payment_date = $request->query('requested_payment_date');
    $from_requested_payment_date = $request->query('from_requested_disbursement_date');
    $to_requested_payment_date = $request->query('to_requested_disbursement_date');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');
    $program_type = $request->query('program_type');

    $payment_requests = PaymentRequest::whereHas('invoice', function ($query) use ($bank, $invoice_number) {
      $query
        ->whereDate('due_date', '>', now()->format('Y-m-d'))
        ->whereHas('company', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id);
        })
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
    })
      ->where('status', 'created')
      ->when($requested_payment_date && $requested_payment_date != '', function ($query) use ($requested_payment_date) {
        $query->whereDate('payment_request_date', $requested_payment_date);
      })
      ->when($from_requested_payment_date && $from_requested_payment_date != '', function ($query) use (
        $from_requested_payment_date
      ) {
        $query->whereDate('payment_request_date', '>=', $from_requested_payment_date);
      })
      ->when($to_requested_payment_date && $to_requested_payment_date != '', function ($query) use (
        $to_requested_payment_date
      ) {
        $query->whereDate('payment_request_date', '<=', $to_requested_payment_date);
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('invoice', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        });
      })
      ->when($program_type && $program_type != '', function ($query) use ($program_type) {
        $query->whereHas('invoice', function ($query) use ($program_type) {
          $query->whereHas('program', function ($query) use ($program_type) {
            switch ($program_type) {
              case 'dealer_financing':
                $query->whereHas('programType', function ($query) {
                  $query->where('name', Program::DEALER_FINANCING);
                });
                break;
              case 'vendor_financing_receivable':
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                });
                break;
              case 'factoring_with_recourse':
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::FACTORING_WITH_RECOURSE);
                });
                break;
              case 'factoring_without_recourse':
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                });
                break;
              default:
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                });
                break;
            }
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('pr_id', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('pr_id', 'DESC');
      });

    if ($per_page) {
      $payment_requests = $payment_requests->paginate($per_page);
      $payment_requests = PaymentRequestResource::collection($payment_requests)
        ->response()
        ->getData();
    } else {
      $payment_requests = $payment_requests->get();
    }

    return $payment_requests;
  }

  public function loansPendingDisbursalReport(Bank $bank, Request $request)
  {
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $requested_disbursement_date = $request->query('requested_disbursement_date');
    $from_requested_disbursement_date = $request->query('from_requested_disbursement_date');
    $to_requested_disbursement_date = $request->query('to_requested_disbursement_date');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');
    $program_type = $request->query('program_type');

    $payment_requests = PaymentRequest::with('cbsTransactions', 'paymentAccounts')
      ->whereHas('invoice', function ($query) use ($bank, $anchor, $invoice_number) {
        $query
          ->whereDate('due_date', '>', now()->format('Y-m-d'))
          ->whereHas('company', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('name', 'LIKE', '%' . $anchor . '%');
              });
            });
          });
      })
      ->where('status', 'approved')
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($requested_disbursement_date && $requested_disbursement_date != '', function ($query) use (
        $requested_disbursement_date
      ) {
        $query->whereDate('payment_request_date', $requested_disbursement_date);
      })
      ->when($from_requested_disbursement_date && $from_requested_disbursement_date != '', function ($query) use (
        $from_requested_disbursement_date
      ) {
        $query->whereDate('payment_request_date', '>=', $from_requested_disbursement_date);
      })
      ->when($to_requested_disbursement_date && $to_requested_disbursement_date != '', function ($query) use (
        $to_requested_disbursement_date
      ) {
        $query->whereDate('payment_request_date', '<=', $to_requested_disbursement_date);
      })
      ->when($program_type && $program_type != '', function ($query) use ($program_type) {
        $query->whereHas('invoice', function ($query) use ($program_type) {
          $query->whereHas('program', function ($query) use ($program_type) {
            switch ($program_type) {
              case 'dealer_financing':
                $query->whereHas('programType', function ($query) {
                  $query->where('name', Program::DEALER_FINANCING);
                });
                break;
              case 'vendor_financing_receivable':
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                });
                break;
              case 'factoring_with_recourse':
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::FACTORING_WITH_RECOURSE);
                });
                break;
              case 'factoring_without_recourse':
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                });
                break;
              default:
                $query->whereHas('programCode', function ($query) {
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                });
                break;
            }
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('pr_id', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderby('pr_id');
      });

    if ($per_page) {
      $payment_requests = $payment_requests->paginate($per_page);
      $payment_requests = PaymentRequestResource::collection($payment_requests)
        ->response()
        ->getData();
    } else {
      $payment_requests = $payment_requests->get();
    }

    return $payment_requests;
  }

  public function userMaintenanceHistoryReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $user_type = $request->query('user_type');
    $last_login = $request->query('last_login');
    $status = $request->query('status');
    $company = $request->query('company');
    $sort_by = $request->query('sort_by');

    if ($user_type && $user_type != '') {
      if ($user_type == 'bank') {
        $users = BankUser::with('bank', 'user')
          ->where('bank_id', $bank->id)
          ->when(($last_login && $last_login != '') || ($status && $status != ''), function ($query) use (
            $last_login,
            $status
          ) {
            $query->whereHas('user', function ($query) use ($last_login, $status) {
              $query
                ->whereDate('last_login', $last_login)
                ->when($status && $status != '', function ($query) use ($status) {
                  if ($status == 'active') {
                    $query->where('is_active', true);
                  } else {
                    $query->where('is_active', false);
                  }
                });
            });
          });

        if ($per_page) {
          $users = $users
            ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
              $query->orderBy('created_at', $sort_by);
            })
            ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
              $query->latest();
            })
            ->paginate($per_page);
        } else {
          $users = $users->get();
        }

        return $users;
      } else {
        $companies = Company::where('bank_id', $bank->id)
          ->get()
          ->pluck('id');

        $users = CompanyUser::with('company', 'user')
          ->whereIn('company_id', $companies)
          ->when(($last_login && $last_login != '') || ($status && $status != ''), function ($query) use (
            $last_login,
            $status
          ) {
            $query->whereHas('user', function ($query) use ($last_login, $status) {
              $query
                ->whereDate('last_login', $last_login)
                ->when($status && $status != '', function ($query) use ($status) {
                  if ($status == 'active') {
                    $query->where('is_active', true);
                  } else {
                    $query->where('is_active', false);
                  }
                });
            });
          })
          ->when($company && $company != '', function ($query) use ($company) {
            $query->whereHas('company', function ($query) use ($company) {
              $query->where('name', 'LIKE', '%' . $company . '%');
            });
          });

        if ($per_page) {
          $users = $users
            ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
              $query->orderBy('created_at', $sort_by);
            })
            ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
              $query->latest();
            })
            ->paginate($per_page);
        } else {
          $users = $users->get();
        }

        return $users;
      }
    }
  }

  public function finalRtrReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $product_type = $request->query('product_type');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice_number');
    $failed_date = $request->query('failed_date');
    $sort_by = $request->query('sort_by');

    $data = CbsTransaction::with('paymentRequest')
      ->where('bank_id', $bank->id)
      ->when($product_type && $product_type != '', function ($query) use ($product_type) {
        $query->where('product', $product_type);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('paymentRequest', function ($query) use ($anchor) {
          $query->whereHas('invoice', function ($query) use ($anchor) {
            $query->whereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('name', 'LIKE', '%' . $anchor . '%');
              });
            });
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('paymentRequest', function ($query) use ($vendor) {
          $query->whereHas('invoice', function ($query) use ($vendor) {
            $query
              ->whereHas('company', function ($query) use ($vendor) {
                $query->where('name', 'LIKE', '%' . $vendor . '%');
              })
              ->orWhereHas('buyer', function ($query) use ($vendor) {
                $query->where('name', 'LIKE', '%' . $vendor . '%');
              });
          });
        });
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('paymentRequest', function ($query) use ($invoice_number) {
          $query->whereHas('invoice', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          });
        });
      })
      ->when($failed_date && $failed_date != '', function ($query) use ($failed_date) {
        $query->whereDate('updated_at', $failed_date);
      })
      ->whereIn('status', ['Permanently Failed', 'Failed'])
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = CbsTransactionResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function maturingPaymentsReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $maturing_in = $request->query('maturing_in');
    $product_type = $request->query('product_type');
    $sort_by = $request->query('sort_by');

    $data = Invoice::whereHas('company', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->where('financing_status', 'disbursed')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when(
        ($anchor && $anchor != '') ||
          ($vendor && $vendor != '') ||
          ($payment_date && $payment_date != '') ||
          ($product_type && $product_type != '') ||
          ($from_date && $from_date != '') ||
          ($to_date && $to_date != '') ||
          ($maturing_in && $maturing_in != ''),
        function ($query) use ($anchor, $vendor, $payment_date, $product_type, $from_date, $to_date, $maturing_in) {
          $query->where(function ($query) use (
            $anchor,
            $vendor,
            $payment_date,
            $product_type,
            $from_date,
            $to_date,
            $maturing_in
          ) {
            $query
              ->when($product_type && $product_type != '', function ($query) use ($product_type) {
                $query->whereHas('program', function ($query) use ($product_type) {
                  $query->whereHas('programType', function ($query) use ($product_type) {
                    $query->where('id', $product_type);
                  });
                });
              })
              ->when($vendor && $vendor != '', function ($query) use ($vendor) {
                $query->whereHas('company', function ($query) use ($vendor) {
                  $query->where('name', 'LIKE', '%' . $vendor . '%');
                });
              })
              ->when($anchor && $anchor != '', function ($query) use ($anchor) {
                $query->whereHas('program', function ($query) use ($anchor) {
                  $query->whereHas('anchor', function ($query) use ($anchor) {
                    $query->where('name', 'LIKE', '%' . $anchor . '%');
                  });
                });
              })
              ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
                $query->whereDate('disbursement_date', $payment_date);
              })
              ->when($from_date && $from_date != '', function ($query) use ($from_date) {
                $query->whereDate('due_date', '>=', $from_date);
              })
              ->when($to_date && $to_date != '', function ($query) use ($to_date) {
                $query->whereDate('due_date', '<=', $to_date);
              })
              ->when($maturing_in && $maturing_in != '', function ($query) use ($maturing_in) {
                switch ($maturing_in) {
                  case '2':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(2)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case '5':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(5)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case '7':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(7)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case '15':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(15)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case '30':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(30)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case '60':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(60)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case '90':
                    $query->whereBetween('due_date', [
                      now()->format('Y-m-d'),
                      now()
                        ->addDays(90)
                        ->format('Y-m-d'),
                    ]);
                    break;
                  case 'more than 90':
                    $query->whereDate(
                      'due_date',
                      '>',
                      now()
                        ->addDays(90)
                        ->format('Y-m-d')
                    );
                    break;
                }
              })
              ->when($product_type && $product_type != '', function ($query) use ($product_type) {
                $query->whereHas('program', function ($query) use ($product_type) {
                  $query->whereHas('programType', function ($query) use ($product_type) {
                    $query->where('id', $product_type);
                  });
                });
              });
          });
        }
      )
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('due_date', $sort_by);
      })
      ->when(!$sort_by || $sort_by === '', function ($query) {
        $query->orderBy('due_date', 'ASC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = InvoiceResource::collection($data->get());
    }

    return $data;
  }

  public function ifPaymentDetailsReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $vendor = $request->query('vendor');
    $status = $request->query('status');
    $product_type = $request->query('product');
    $overdue_days = $request->query('dpd');
    $sort_by = $request->query('sort_by');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');

    $data = Invoice::whereIn('financing_status', ['closed', 'disbursed'])
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->when(
        ($anchor && $anchor != '') ||
          ($vendor && $vendor != '') ||
          ($status && $status != '') ||
          ($product_type && $product_type != ''),
        function ($query) use ($anchor, $vendor, $status, $product_type) {
          $query->where(function ($query) use ($anchor, $vendor, $status, $product_type) {
            $query
              ->when($vendor && $vendor != '', function ($query) use ($vendor) {
                $query->whereHas('company', function ($query) use ($vendor) {
                  $query->where('name', 'LIKE', '%' . $vendor . '%');
                });
              })
              ->when($anchor && $anchor != '', function ($query) use ($anchor) {
                $query->whereHas('program', function ($query) use ($anchor) {
                  $query->whereHas('anchor', function ($query) use ($anchor) {
                    $query->where('name', 'LIKE', '%' . $anchor . '%');
                  });
                });
              })
              ->when($status && $status != '', function ($query) use ($status) {
                $query->where('status', $status);
              })
              ->when($product_type && $product_type != '', function ($query) use ($product_type) {
                // $query->whereHas('program', function ($query) use ($product_type) {
                //   $query->whereHas('programType', function ($query) use ($product_type) {
                //     $query->where('name', $product_type);
                //   });
                // });
                $query->whereHas('program', function ($query) use ($product_type) {
                  switch ($product_type) {
                    case 'vendor_financing_receivable':
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                      });
                      break;
                    case 'factoring_with_recourse':
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::FACTORING_WITH_RECOURSE);
                      });
                      break;
                    case 'factoring_without_recourse':
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                      });
                      break;
                    case 'dealer_financing':
                      $query->whereHas('programType', function ($query) {
                        $query->where('name', Program::DEALER_FINANCING);
                      });
                      break;
                    default:
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                      });
                      break;
                  }
                });
              });
          });
        }
      )
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($overdue_days && $overdue_days != '', function ($query) use ($overdue_days) {
        if ($overdue_days == '0') {
          $query->whereDate('due_date', '<=', now()->format('Y-m-d'));
        }
        if ($overdue_days == '1-5') {
          $query->whereBetween('due_date', [
            now()->format('Y-m-d'),
            now()
              ->subDays(5)
              ->format('Y-m-d'),
          ]);
        }
        if ($overdue_days == '6-10') {
          $query->whereBetween('due_date', [
            now()
              ->subDays(6)
              ->format('Y-m-d'),
            now()
              ->subDays(10)
              ->format('Y-m-d'),
          ]);
        }
        if ($overdue_days == '10') {
          $query->whereDate(
            'due_date',
            '>=',
            now()
              ->subDays(10)
              ->format('Y-m-d')
          );
        }
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->where('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->where('disbursement_date', '<=', $to_payment_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function distributorLimitUtilizationReport(Bank $bank, Request $request)
  {
    $name = $request->query('name');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');

    $dealer_role = ProgramRole::where('name', 'dealer')->first();
    $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

    $dealers = ProgramCompanyRole::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id)->whereHas('programType', function ($query) {
        $query->where('name', Program::DEALER_FINANCING);
      });
    })
      ->where('role_id', $dealer_role->id)
      ->pluck('company_id');

    $data = Company::with('programs.programType')
      ->where('bank_id', $bank->id)
      ->whereIn('id', $dealers)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
    } else {
      $data = $data->get();
    }

    foreach ($data as $company) {
      $dealer_financing_programs = $company->programs->where('program_type_id', $dealer_financing->id);
      foreach ($dealer_financing_programs as $program) {
        $company['total_sanctioned_limit'] += $company->programConfigurations
          ->where('program_id', $program->id)
          ->first()?->sanctioned_limit;
        $company['utilized_percentage'] += $company->utilizedPercentage($program); // TODO Check on this value
        $company['pipeline_amount'] += $company->pipelineAmount($program);
        $company['utilized_amount'] += $company->utilizedAmount($program);
        $company['available_amount'] += $company->top_level_borrower_limit - $company->utilizedAmount($program);
      }
    }

    return $data;
  }

  public function vfRepaymentDetailsReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $set_off_type = $request->query('set_off_type');
    $date = $request->query('date');
    $from_transaction_date = $request->query('from_transaction_date');
    $to_transaction_date = $request->query('to_transaction_date');
    $cbs_id = $request->query('cbs_id');
    $program_name = $request->query('program_name');
    $sort_by = $request->query('sort_by');

    $data = CbsTransaction::with('paymentRequest.invoice.program', 'paymentRequest.invoice.invoiceMedia')
      ->where('bank_id', $bank->id)
      ->whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING);
            });
          });
        });
      })
      ->when($set_off_type && $set_off_type != '', function ($query) use ($set_off_type) {
        switch ($set_off_type) {
          case 'Bank Invoice Payment':
            $query->whereIn('transaction_type', [CbsTransaction::BANK_INVOICE_PAYMENT]);
            break;
          case 'Penal Interest':
            $query->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT]);
            break;
          case 'Discount Charge':
            $query->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST]);
            break;
          case 'Fees/Charges':
            $query->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES]);
            break;
          default:
            # code...
            break;
        }
      })
      ->when(!$set_off_type || $set_off_type == '', function ($query) {
        $query->whereIn('transaction_type', [
          CbsTransaction::FEES_CHARGES,
          CbsTransaction::ACCRUAL_POSTED_INTEREST,
          CbsTransaction::OVERDUE_ACCOUNT,
          CbsTransaction::BANK_INVOICE_PAYMENT,
        ]);
      })
      ->where('cbs_transactions.status', 'Successful')
      ->when($date && $date != '', function ($query) use ($date) {
        $query->whereDate('transaction_date', $date);
      })
      ->when($from_transaction_date && $from_transaction_date != '', function ($query) use ($from_transaction_date) {
        $query->whereDate('transaction_date', '>=', $from_transaction_date);
      })
      ->when($to_transaction_date && $to_transaction_date != '', function ($query) use ($to_transaction_date) {
        $query->whereDate('transaction_date', '<=', $to_transaction_date);
      })
      ->when($cbs_id && $cbs_id != '', function ($query) use ($cbs_id) {
        $query->where('id', $cbs_id);
      })
      ->when($program_name && $program_name != '', function ($query) use ($program_name) {
        $query->whereHas('paymentRequest', function ($query) use ($program_name) {
          $query->whereHas('invoice', function ($query) use ($program_name) {
            $query->whereHas('program', function ($query) use ($program_name) {
              $query->where('name', 'LIKE', '%' . $program_name . '%');
            });
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('id', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('id', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = CbsTransactionResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function dfRepaymentDetailsReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $set_off_type = $request->query('set_off_type');
    $date = $request->query('date');
    $cbs_id = $request->query('cbs_id');
    $program_name = $request->query('program_name');
    $sort_by = $request->query('sort_by');

    $data = CbsTransaction::with('paymentRequest.invoice.program', 'paymentRequest.invoice.invoiceMedia')
      ->where('bank_id', $bank->id)
      ->whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
          });
        });
      })
      ->when($set_off_type && $set_off_type != '', function ($query) use ($set_off_type) {
        switch ($set_off_type) {
          case 'Principal':
            $query->whereIn('transaction_type', [CbsTransaction::REPAYMENT]);
            break;
          case 'Penal Interest':
            $query->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT]);
            break;
          case 'Interest':
            $query->whereIn('transaction_type', [
              CbsTransaction::ACCRUAL_POSTED_INTEREST,
              CbsTransaction::FEES_CHARGES,
            ]);
            break;
          default:
            # code...
            break;
        }
      })
      ->when(!$set_off_type || $set_off_type == '', function ($query) {
        $query->whereIn('transaction_type', [
          CbsTransaction::FEES_CHARGES,
          CbsTransaction::ACCRUAL_POSTED_INTEREST,
          CbsTransaction::OVERDUE_ACCOUNT,
          CbsTransaction::REPAYMENT,
        ]);
      })
      ->where('status', 'Successful')
      ->when($date && $date != '', function ($query) use ($date) {
        $query->whereDate('transaction_date', $date);
      })
      ->when($cbs_id && $cbs_id != '', function ($query) use ($cbs_id) {
        $query->where('id', $cbs_id);
      })
      ->when($program_name && $program_name != '', function ($query) use ($program_name) {
        $query->whereHas('paymentRequest', function ($query) use ($program_name) {
          $query->whereHas('invoice', function ($query) use ($program_name) {
            $query->whereHas('program', function ($query) use ($program_name) {
              $query->where('name', 'LIKE', '%' . $program_name . '%');
            });
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = CbsTransactionResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function dfPotentialFinancingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $dealer = $request->query('vendor');
    $invoice_date = $request->query('invoice_date');
    $due_date = $request->query('due_date');
    $invoice_number = $request->query('invoice_number');
    $sort_by = $request->query('sort_by');

    $data = Invoice::with(
      'program.anchor',
      'invoiceMedia',
      'invoiceItems',
      'invoiceDiscounts',
      'invoiceFees',
      'invoiceTaxes'
    )
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->whereHas('program', function ($query) use ($bank) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->where('status', 'approved')
      ->where('financing_status', 'pending')
      ->whereDoesntHave('paymentRequests')
      ->whereDate('due_date', '>', now()->format('Y-m-d'))
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($invoice_date && $invoice_date != '', function ($query) use ($invoice_date) {
        $query->whereDate('invoice_date', $invoice_date);
      })
      ->when($due_date && $due_date != '', function ($query) use ($due_date) {
        $query->whereDate('due_date', $due_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function vfPotentialFinancingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $dealer = $request->query('dealer');
    $invoice_date = $request->query('invoice_date');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');
    $due_date = $request->query('due_date');
    $from_due_date = $request->query('from_due_date');
    $to_due_date = $request->query('to_due_date');
    $invoice_number = $request->query('invoice_number');
    $sort_by = $request->query('sort_by');
    $program_type = $request->query('program_type');

    $data = Invoice::with('program.anchor', 'buyer', 'invoiceMedia')
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->whereHas('program', function ($query) use ($program_type) {
        $query
          ->whereHas('programType', function ($query) use ($program_type) {
            $query->where('name', Program::VENDOR_FINANCING);
          })
          ->when($program_type && $program_type != '', function ($query) use ($program_type) {
            $query->whereHas('programCode', function ($query) use ($program_type) {
              switch ($program_type) {
                case 'vendor_financing_receivable':
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  break;
                case 'factoring_with_recourse':
                  $query->where('name', Program::FACTORING_WITH_RECOURSE);
                  break;
                case 'factoring_without_recourse':
                  $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                  break;
                default:
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  break;
              }
            });
          });
      })
      ->where('status', 'approved')
      ->whereIn('financing_status', ['pending'])
      ->whereDate('due_date', '>', now()->format('Y-m-d'))
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($invoice_date && $invoice_date != '', function ($query) use ($invoice_date) {
        $query->whereDate('invoice_date', $invoice_date);
      })
      ->when($from_invoice_date && $from_invoice_date != '', function ($query) use ($from_invoice_date) {
        $query->whereDate('invoice_date', '>=', $from_invoice_date);
      })
      ->when($to_invoice_date && $to_invoice_date != '', function ($query) use ($to_invoice_date) {
        $query->whereDate('invoice_date', '<=', $to_invoice_date);
      })
      ->when($due_date && $due_date != '', function ($query) use ($due_date) {
        $query->whereDate('due_date', $due_date);
      })
      ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
        $query->whereDate('due_date', '>=', $from_due_date);
      })
      ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
        $query->whereDate('due_date', '<=', $to_due_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function vfOverdueInvoicesReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $dealer = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $from_due_date = $request->query('from_due_date');
    $to_due_date = $request->query('to_due_date');
    $overdue_days = $request->query('overdue_days');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');

    $data = Invoice::with(
      'program.anchor',
      'paymentRequests.paymentAccounts',
      'invoiceMedia',
      'invoiceItems',
      'invoiceTaxes',
      'invoiceFees',
      'invoiceDiscounts'
    )
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->whereIn('name', [
            Program::VENDOR_FINANCING_RECEIVABLE,
            Program::FACTORING_WITH_RECOURSE,
            Program::FACTORING_WITHOUT_RECOURSE,
          ]);
        });
      })
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->where('financing_status', 'disbursed')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
        $query->whereDate('due_date', '>=', $from_due_date);
      })
      ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
        $query->whereDate('due_date', '<=', $to_due_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->where(function ($query) use ($anchor) {
          $query
            ->whereHas('buyer', function ($query) use ($anchor) {
              $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
            })
            ->orWhereHas('program', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('companies.name', 'LIKE', '%' . $anchor . '%');
              });
            });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($overdue_days && $overdue_days != '', function ($query) use ($overdue_days) {
        if ($overdue_days == '0') {
          $query->whereDate(
            'due_date',
            now()
              ->subDay()
              ->format('Y-m-d')
          );
        }
        if ($overdue_days == '1-5') {
          $query->whereBetween('due_date', [
            now()->format('Y-m-d'),
            now()
              ->subDays(4)
              ->format('Y-m-d'),
          ]);
        }
        if ($overdue_days == '6-10') {
          $query->whereBetween('due_date', [
            now()
              ->subDays(5)
              ->format('Y-m-d'),
            now()
              ->subDays(9)
              ->format('Y-m-d'),
          ]);
        }
        if ($overdue_days == '10') {
          $query->whereDate(
            'due_date',
            '>=',
            now()
              ->subDays(9)
              ->format('Y-m-d')
          );
        }
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function dfOverdueInvoicesReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $dealer = $request->query('dealer');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $from_due_date = $request->query('from_due_date');
    $to_due_date = $request->query('to_due_date');
    $overdue_days = $request->query('overdue_days');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');

    $data = Invoice::with('program.anchor', 'paymentRequests.paymentAccounts', 'invoiceMedia')
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->whereHas('program', function ($query) use ($bank) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($from_due_date && $from_due_date != '', function ($query) use ($from_due_date) {
        $query->whereDate('due_date', '>=', $from_due_date);
      })
      ->when($to_due_date && $to_due_date != '', function ($query) use ($to_due_date) {
        $query->whereDate('due_date', '<=', $to_due_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($overdue_days && $overdue_days != '', function ($query) use ($overdue_days) {
        if ($overdue_days == '0') {
          $query->whereDate(
            'due_date',
            now()
              ->subDay()
              ->format('Y-m-d')
          );
        }
        if ($overdue_days == '1-5') {
          $query->whereBetween('due_date', [
            now()->format('Y-m-d'),
            now()
              ->subDays(4)
              ->format('Y-m-d'),
          ]);
        }
        if ($overdue_days == '6-10') {
          $query->whereBetween('due_date', [
            now()
              ->subDays(5)
              ->format('Y-m-d'),
            now()
              ->subDays(9)
              ->format('Y-m-d'),
          ]);
        }
        if ($overdue_days == '10') {
          $query->whereDate(
            'due_date',
            '>=',
            now()
              ->subDays(9)
              ->format('Y-m-d')
          );
        }
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function usersAndRolesReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $company = $request->query('company');
    $details = $request->query('details');
    $status = $request->query('status');
    $last_login = $request->query('last_login');
    $user_type = $request->query('user_type');
    $sort_by = $request->query('sort_by');

    if ($user_type && $user_type != '') {
      if ($user_type == 'bank') {
        $bank_users = BankUser::where('bank_id', $bank->id)->pluck('user_id');
        $company_users = collect([]);
      } else {
        $companies = Company::where('bank_id', $bank->id)->pluck('id');
        $company_users = CompanyUser::whereIn('company_id', $companies)->pluck('user_id');
        $bank_users = collect([]);
      }
    } else {
      $bank_users = BankUser::where('bank_id', $bank->id)->pluck('user_id');
      $companies = Company::where('bank_id', $bank->id)->pluck('id');
      $company_users = CompanyUser::whereIn('company_id', $companies)->pluck('user_id');
    }

    $all_users = collect($company_users)->concat($bank_users);

    $data = User::with('roles', 'mappedCompanies', 'banks')
      ->when($company && $company != '', function ($query) use ($company) {
        $query->whereHas('companies', function ($query) use ($company) {
          $query->where('name', 'LIKE', '%' . $company . '%');
        });
      })
      ->when($details && $details != '', function ($query) use ($details) {
        $query->where(function ($query) use ($details) {
          $query
            ->where('name', 'LIKE', '%' . $details . '%')
            ->orWhere('email', 'LIKE', '%' . $details . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $details . '%');
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($last_login && $last_login != '', function ($query) use ($last_login) {
        $query->whereDate('last_login', $last_login);
      })
      ->whereIn('id', $all_users)
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
    } else {
      $data = $data->get();
    }

    foreach ($data as $user) {
      $activity = Activity::causedBy($user)
        ->latest()
        ->first();

      $user['activity'] = $activity;
    }

    return $data;
  }

  public function vfFundingLimitUtilizationReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $company_name = $request->query('company_name');
    $company_type = $request->query('company_type');
    $sort_by = $request->query('sort_by');

    $data = ProgramVendorConfiguration::with('company')
      ->whereHas('program', function ($query) use ($bank) {
        $query
          ->where('bank_id', $bank->id)
          ->where('account_status', 'active')
          ->whereHas('programType', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING);
          });
      })
      ->when($company_name && $company_name != '', function ($query) use ($company_name) {
        $query->whereHas('company', function ($query) use ($company_name) {
          $query->where('name', 'LIKE', '%' . $company_name . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);

      $data = OdAccountsResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function dfFundingLimitUtilizationReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $company_name = $request->query('company_name');

    $data = ProgramVendorConfiguration::with('company')
      ->whereHas('program', function ($query) use ($bank) {
        $query
          ->where('bank_id', $bank->id)
          ->where('account_status', 'active')
          ->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
      })
      ->when($company_name && $company_name != '', function ($query) use ($company_name) {
        $query->whereHas('company', function ($query) use ($company_name) {
          $query->where('name', 'LIKE', '%' . $company_name . '%');
        });
      });

    if ($per_page) {
      $data = $data->paginate($per_page);

      $data = OdAccountsResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();

      foreach ($data as $vendor_configuration) {
        $vendor_configuration['utilized_amount'] = $vendor_configuration->utilized_amount;
        $vendor_configuration['pipeline_amount'] = $vendor_configuration->pipeline_amount;
        $vendor_configuration['available_amount'] =
          $vendor_configuration->sanctioned_limit -
          $vendor_configuration->pipeline_amount -
          $vendor_configuration->utilized_amount;
        $vendor_configuration['utilized_percentage'] = round(
          (($vendor_configuration->pipeline_amount + $vendor_configuration->utilized_amount) /
            $vendor_configuration->sanctioned_limit) *
            100,
          2
        );
      }
    }

    return $data;
  }

  public function vendorsDailyOutstandingBalanceReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $date = $request->query('date');
    $vendor = $request->query('vendor');
    $sort_by = $request->query('sort_by');

    $data = ProgramVendorConfiguration::with('company')
      ->wherehas('program', function ($query) use ($bank, $vendor) {
        $query->where('bank_id', $bank->id)->whereHas('programCode', function ($query) use ($vendor) {
          $query->where(function ($query) {
            $query
              ->where('name', Program::VENDOR_FINANCING_RECEIVABLE)
              ->orWhere('name', Program::FACTORING_WITH_RECOURSE)
              ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
          });
          $query->when($vendor && $vendor != '', function ($query) use ($vendor) {
            $query->whereHas('anchor', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '&');
            });
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->where(function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_by', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->latest();
      });

    if ($per_page) {
      $data = OdAccountsResource::collection($data->paginate($per_page))
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function dealersDailyOutstandingBalanceReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $date = $request->query('date');
    $vendor = $request->query('vendor');
    $sort_by = $request->query('sort_by');

    $data = ProgramVendorConfiguration::with('company')
      ->wherehas('program', function ($query) use ($bank, $vendor) {
        $query
          ->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          })
          ->where('bank_id', $bank->id);
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->where(function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_by', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = OdAccountsResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
    }

    return $data;
  }

  public function dfIncomeReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $dealer = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');

    $data = Invoice::whereHas('company', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->whereHas('program', function ($query) use ($bank) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('stage', $status);
      })
      ->when(!$status || ($status && $status == ''), function ($query) {
        $query->whereIn('financing_status', ['closed', 'disbursed']);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $invoice) {
        $invoice['fees'] = $invoice->program_fees;
      }
    }

    return $data;
  }

  public function vfIncomeReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');

    $data = Invoice::with(
      'program.anchor',
      'paymentRequests.paymentAccounts',
      'program.anchor',
      'buyer',
      'invoiceMedia',
      'invoiceItems',
      'invoiceFees',
      'invoiceTaxes'
    )
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->whereHas('program', function ($query) use ($bank) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->when(!$status || $status == '', function ($query) {
        $query->whereIn('financing_status', ['closed', 'disbursed']);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('stage', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $invoice) {
        $invoice['fees'] = $invoice->program_fees;
      }
    }

    return $data;
  }

  public function factoringIncomeReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');

    $data = Invoice::with(
      'program.anchor',
      'paymentRequests.paymentAccounts',
      'program.anchor',
      'buyer',
      'invoiceMedia',
      'invoiceItems',
      'invoiceFees',
      'invoiceTaxes'
    )
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->whereHas('program', function ($query) use ($bank) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', 'Factoring With Recourse')->orWhere('name', 'Factoring Without Recourse');
        });
      })
      ->when(!$status || $status == '', function ($query) {
        $query->whereIn('financing_status', ['closed', 'disbursed']);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('stage', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $invoice) {
        $invoice['fees'] = $invoice->program_fees;
      }
    }

    return $data;
  }

  public function dfFeesAndInterestSharingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');

    $data = Invoice::whereHas('company', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->whereIn('financing_status', ['financed', 'disbursed', 'closed'])
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);

      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      $data = InvoiceResource::collection($data);
    }

    return $data;
  }

  public function vfFeesAndInterestSharingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $payment_date = $request->query('payment_date');
    $from_payment_date = $request->query('from_payment_date');
    $to_payment_date = $request->query('to_payment_date');
    $status = $request->query('status');
    $sort_by = $request->query('sort_by');
    $program_type = $request->query('program_type');

    $data = Invoice::whereHas('company', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->whereHas('program', function ($query) use ($program_type) {
        $query
          ->whereHas('programType', function ($query) use ($program_type) {
            $query->where('name', Program::VENDOR_FINANCING);
          })
          ->when($program_type && $program_type != '', function ($query) use ($program_type) {
            $query->whereHas('programCode', function ($query) use ($program_type) {
              switch ($program_type) {
                case 'vendor_financing_receivable':
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  break;
                case 'factoring_with_recourse':
                  $query->where('name', Program::FACTORING_WITH_RECOURSE);
                  break;
                case 'factoring_without_recourse':
                  $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                  break;
                default:
                  $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  break;
              }
            });
          });
      })
      ->when(!$status || $status === '', function ($query) {
        $query->whereIn('financing_status', ['financed', 'disbursed', 'closed']);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('financing_status', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($payment_date && $payment_date != '', function ($query) use ($payment_date) {
        $query->whereDate('disbursement_date', $payment_date);
      })
      ->when($from_payment_date && $from_payment_date != '', function ($query) use ($from_payment_date) {
        $query->whereDate('disbursement_date', '>=', $from_payment_date);
      })
      ->when($to_payment_date && $to_payment_date != '', function ($query) use ($to_payment_date) {
        $query->whereDate('disbursement_date', '<=', $to_payment_date);
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->where(function ($query) use ($vendor) {
          $query
            ->whereHas('company', function ($query) use ($vendor) {
              $query->where('name', 'LIKE', '%' . $vendor . '%');
            })
            ->orWhereHas('program', function ($query) use ($vendor) {
              $query->whereHas('anchor', function ($query) use ($vendor) {
                $query->where('companies.name', 'LIKE', '%' . $vendor . '%');
              });
            });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('updated_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('updated_at', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);

      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      $data = InvoiceResource::collection($data);
    }

    return $data;
  }

  public function dfOverdueReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $od_account = $request->query('od_account');
    $anchor = $request->query('anchor');
    $dealer = $request->query('dealer');
    $sort_by = $request->query('sort_by');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');

    $data = Invoice::with('program', 'company', 'invoiceMedia')
      ->whereHas('program', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->where('financing_status', 'disbursed')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->when($od_account && $od_account != '', function ($query) use ($od_account) {
        $query->whereHas('program', function ($query) use ($od_account) {
          $query->whereHas('vendorConfigurations', function ($query) use ($od_account) {
            $query->where('payment_account_number', 'LIKE', '%' . $od_account . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('disbursement_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('disbursement_date', '<=', $to_date);
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $invoice) {
        $invoice['vendor_configurations'] = $invoice->program->vendorConfigurations
          ->where('company_id', $invoice->company_id)
          ->first();
        $invoice['utilized_amount'] = $invoice->company->utilizedAmount($invoice->program);
        $invoice['pipeline_amount'] = $invoice->company->pipelineAmount($invoice->program);
        $invoice['days_past_due'] = $invoice->company->daysPastDue($invoice->program);
      }
    }

    return $data;
  }

  public function dfOdLedgerReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $od_account = $request->query('od_account');
    $dealer = $request->query('dealer');
    $invoice_number = $request->query('invoice_number');
    $date = $request->query('date');
    $overdue_date = $request->query('overdue_date');
    $sort_by = $request->query('sort_by');

    $data = CbsTransaction::with('paymentRequest')
      ->where('bank_id', $bank->id)
      ->whereHas('paymentRequest', function ($query) use ($invoice_number, $date, $overdue_date) {
        $query->whereHas('invoice', function ($query) use ($invoice_number, $date, $overdue_date) {
          $query
            ->whereHas('program', function ($query) {
              $query->whereHas('programType', function ($query) {
                $query->where('name', Program::DEALER_FINANCING);
              });
            })
            ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
              $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
            })
            ->when($date && $date != '', function ($query) use ($date) {
              $query->whereDate('invoice_date', $date);
            })
            ->when($overdue_date && $overdue_date != '', function ($query) use ($overdue_date) {
              $query->whereDate('due_date', $overdue_date);
            });
        });
      })
      ->when($od_account && $od_account != '', function ($query) use ($od_account) {
        $query->whereHas('paymentRequest', function ($query) use ($od_account) {
          $query->whereHas('invoice', function ($query) use ($od_account) {
            $query->whereHas('program', function ($query) use ($od_account) {
              $query->whereHas('vendorConfigurations', function ($query) use ($od_account) {
                $query->where('payment_account_number', 'LIKE', '%' . $od_account . '%');
              });
            });
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('paymentRequest', function ($query) use ($dealer) {
          $query->whereHas('invoice', function ($query) use ($dealer) {
            $query->whereHas('company', function ($query) use ($dealer) {
              $query->where('name', 'LIKE', '%' . $dealer . '%');
            });
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);

      $data = CbsTransactionResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $cbs_transaction) {
        if ($cbs_transaction->paymentRequest) {
          $cbs_transaction[
            'vendor_configurations'
          ] = $cbs_transaction->paymentRequest->invoice->program->vendorConfigurations
            ->where('company_id', $cbs_transaction->paymentRequest->invoice->company_id)
            ->first();
        } else {
          $cbs_transaction[
            'vendor_configurations'
          ] = $cbs_transaction->creditAccountRequest->program->vendorConfigurations
            ->where('company_id', $cbs_transaction->creditAccountRequest->company_id)
            ->first();
        }
      }
    }

    return $data;
  }

  public function maturityExtendedReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $vendor = $request->query('vendor');
    $product_type = $request->query('product_type');
    $sort_by = $request->query('sort_by');
    $from_original_date = $request->query('from_original_date');
    $to_original_date = $request->query('to_original_date');
    $from_changed_date = $request->query('from_changed_date');
    $to_changed_date = $request->query('to_changed_date');

    $data = Invoice::with('program.anchor', 'program.programCode', 'invoiceMedia')
      ->where('old_due_date', '!=', null)
      ->whereIn('status', ['approved', 'disbursed', 'denied'])
      ->whereHas('program', function ($query) use ($bank, $anchor, $product_type) {
        $query
          ->where('bank_id', $bank->id)
          ->when($anchor && $anchor != '', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          })
          ->when($product_type && $product_type != '', function ($query) use ($product_type) {
            $query->whereHas('programCode', function ($query) use ($product_type) {
              $query->where('abbrev', $product_type);
            });
          });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($from_original_date && $from_original_date != '', function ($query) use ($from_original_date) {
        $query->whereDate('old_due_date', '>=', $from_original_date);
      })
      ->when($to_original_date && $to_original_date != '', function ($query) use ($to_original_date) {
        $query->whereDate('old_due_date', '<=', $to_original_date);
      })
      ->when($from_changed_date && $from_changed_date != '', function ($query) use ($from_changed_date) {
        $query->whereDate('invoice_date', '>=', $from_changed_date);
      })
      ->when($to_changed_date && $to_changed_date != '', function ($query) use ($to_changed_date) {
        $query->whereDate('invoice_date', '<=', $to_changed_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = InvoiceResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $invoice) {
        $invoice['vendor_configurations'] = $invoice->program->vendorConfigurations
          ->where('company_id', $invoice->company_id)
          ->first();
      }
    }

    return $data;
  }

  public function dfCollectionReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $od_account = $request->query('od_account');
    $dealer = $request->query('dealer');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $overdue_date = $request->query('overdue_date');
    $sort_by = $request->query('sort_by');

    $data = CbsTransaction::with('paymentRequest')
      ->where('bank_id', $bank->id)
      ->where('status', 'Successful')
      ->whereIn('transaction_type', [
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::FUNDS_TRANSFER,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
        CbsTransaction::OVERDUE_ACCOUNT,
        CbsTransaction::OD_DRAWDOWN,
        CbsTransaction::REPAYMENT,
      ])
      ->whereHas('paymentRequest', function ($query) use ($invoice_number, $overdue_date) {
        $query->whereHas('invoice', function ($query) use ($invoice_number, $overdue_date) {
          $query
            ->whereHas('program', function ($query) {
              $query->whereHas('programType', function ($query) {
                $query->where('name', Program::DEALER_FINANCING);
              });
            })
            ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
              $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
            })
            ->when($overdue_date && $overdue_date != '', function ($query) use ($overdue_date) {
              $query->whereDate('due_date', $overdue_date);
            });
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('pay_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('pay_date', '<=', $to_date);
      })
      ->when($od_account && $od_account != '', function ($query) use ($od_account) {
        $query->whereHas('paymentRequest', function ($query) use ($od_account) {
          $query->whereHas('invoice', function ($query) use ($od_account) {
            $query->whereHas('program', function ($query) use ($od_account) {
              $query->whereHas('vendorConfigurations', function ($query) use ($od_account) {
                $query->where('payment_account_number', 'LIKE', '%' . $od_account . '%');
              });
            });
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('paymentRequest', function ($query) use ($dealer) {
          $query->whereHas('invoice', function ($query) use ($dealer) {
            $query->whereHas('company', function ($query) use ($dealer) {
              $query->where('name', 'LIKE', '%' . $dealer . '%');
            });
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('created_at', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->latest();
      });

    if ($per_page) {
      $data = $data->paginate($per_page);

      $data = CbsTransactionResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $cbs_transaction) {
        if ($cbs_transaction->paymentRequest) {
          $cbs_transaction[
            'vendor_configurations'
          ] = $cbs_transaction->paymentRequest->invoice->program->vendorConfigurations
            ->where('company_id', $cbs_transaction->paymentRequest->invoice->company_id)
            ->first();
        } else {
          $cbs_transaction[
            'vendor_configurations'
          ] = $cbs_transaction->creditAccountRequest->program->vendorConfigurations
            ->where('company_id', $cbs_transaction->creditAccountRequest->company_id)
            ->first();
        }
      }
    }

    return $data;
  }

  public function dfMonthlyUtilizationAndOutstandingReport(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $od_account = $request->query('od_account');
    $dealer = $request->query('dealer');
    $month = $request->query('month');
    $year = $request->query('year');
    $sort_by = $request->query('sort_by');

    $data = CbsTransaction::with('paymentRequest')
      ->where('bank_id', $bank->id)
      ->whereIn('transaction_type', [CbsTransaction::FEES_CHARGES, CbsTransaction::FUNDS_TRANSFER])
      ->whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
          });
        });
      })
      ->when($month && $month != '', function ($query) use ($month) {
        $query->whereMonth('created_at', $month);
      })
      ->when($year && $year != '', function ($query) use ($year) {
        $query->whereYear('created_at', $year);
      })
      ->when($od_account && $od_account != '', function ($query) use ($od_account) {
        $query->whereHas('paymentRequest', function ($query) use ($od_account) {
          $query->whereHas('invoice', function ($query) use ($od_account) {
            $query->whereHas('program', function ($query) use ($od_account) {
              $query->whereHas('vendorConfigurations', function ($query) use ($od_account) {
                $query->where('payment_account_number', 'LIKE', '%' . $od_account . '%');
              });
            });
          });
        });
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('paymentRequest', function ($query) use ($dealer) {
          $query->whereHas('invoice', function ($query) use ($dealer) {
            $query->whereHas('company', function ($query) use ($dealer) {
              info($dealer);
              $query->where('name', 'LIKE', '%' . $dealer . '%');
            });
          });
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        $query->orderBy('id', $sort_by);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) use ($sort_by) {
        $query->orderBy('cbs_id', 'DESC');
      });

    if ($per_page) {
      $data = $data->paginate($per_page);
      $data = CbsTransactionResource::collection($data)
        ->response()
        ->getData();
    } else {
      $data = $data->get();
      foreach ($data as $cbs_transaction) {
        $cbs_transaction['vendor_configurations'] = ProgramVendorConfiguration::select(
          'payment_account_number',
          'sanctioned_limit',
          'utilized_amount',
          'id'
        )
          ->where('company_id', $cbs_transaction->paymentRequest->invoice->company_id)
          ->where('program_id', $cbs_transaction->paymentRequest->invoice->program_id)
          ->first();
      }
    }

    return $data;
  }

  public function manageVendors(Bank $bank, Program $program)
  {
    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $type = 'vendors';
      } else {
        $type = 'buyers';
      }
    } else {
      $type = 'dealers';
    }

    $manage = true;

    return view('content.bank.reports.vendors-data', [
      'type' => $type,
      'bank' => $bank,
      'program' => $program,
      'manage' => $manage,
    ]);
  }

  public function vendorsData(Request $request, Bank $bank, Program $program)
  {
    $per_page = $request->query('per_page');
    $company_name = $request->query('name');

    $company_ids = [];

    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $company_ids = $program->getVendors()->pluck('id');
      } else {
        $company_ids = $program->getBuyers()->pluck('id');
      }
    } else {
      $company_ids = $program->getDealers()->pluck('id');
    }

    $data = Company::where('bank_id', $bank->id)
      ->whereIn('id', $company_ids)
      ->when($company_name && $company_name != '', function ($query) use ($company_name) {
        $query->where('name', 'LIKE', '%' . $company_name . '%');
      })
      ->paginate($per_page);

    foreach ($data as $company) {
      if ($program->programType->name == Program::VENDOR_FINANCING) {
        if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $vendor_configuration = new OdAccountsResource(
            ProgramVendorConfiguration::where('company_id', $company->id)
              ->where('program_id', $program->id)
              ->select(
                'id',
                'program_id',
                'company_id',
                'buyer_id',
                'sanctioned_limit',
                'payment_account_number',
                'limit_expiry_date',
                'limit_review_date',
                'request_auto_finance',
                'auto_approve_finance',
                'eligibility',
                'status',
                'utilized_amount',
                'pipeline_amount'
              )
              ->first()
          );
          $company['vendor_configuration'] = $vendor_configuration;
          $company['vendor_discount_details'] = ProgramVendorDiscount::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'benchmark_title',
              'benchmark_rate',
              'business_strategy_spread',
              'credit_spread',
              'total_spread',
              'total_roi',
              'anchor_discount_bearing',
              'vendor_discount_bearing',
              'penal_discount_on_principle',
              'grace_period',
              'grace_period_discount',
              'maturity_handling_on_holidays',
              'from_day',
              'to_day'
            )
            ->get();
          $company['vendor_bank_details'] = ProgramVendorBankDetail::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select('name_as_per_bank', 'account_number', 'bank_name', 'branch', 'swift_code', 'account_type')
            ->get();
          $company['vendor_fee_details'] = ProgramVendorFee::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'fee_name',
              'type',
              'value',
              'anchor_bearing_discount',
              'vendor_bearing_discount',
              'taxes',
              'dealer_bearing',
              'per_amount'
            )
            ->get();
        } else {
          $vendor_configuration = new OdAccountsResource(
            ProgramVendorConfiguration::where('buyer_id', $company->id)
              ->where('program_id', $program->id)
              ->select(
                'id',
                'program_id',
                'company_id',
                'buyer_id',
                'sanctioned_limit',
                'payment_account_number',
                'limit_expiry_date',
                'limit_review_date',
                'request_auto_finance',
                'auto_approve_finance',
                'eligibility',
                'status',
                'utilized_amount',
                'pipeline_amount'
              )
              ->first()
          );
          $company['vendor_configuration'] = $vendor_configuration;
          $company['vendor_discount_details'] = ProgramVendorDiscount::where('buyer_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'benchmark_title',
              'benchmark_rate',
              'business_strategy_spread',
              'credit_spread',
              'total_spread',
              'total_roi',
              'anchor_discount_bearing',
              'vendor_discount_bearing',
              'penal_discount_on_principle',
              'grace_period',
              'grace_period_discount',
              'maturity_handling_on_holidays',
              'from_day',
              'to_day'
            )
            ->get();
          $company['vendor_bank_details'] = ProgramVendorBankDetail::where('buyer_id', $company->id)
            ->where('program_id', $program->id)
            ->select('name_as_per_bank', 'account_number', 'bank_name', 'branch', 'swift_code', 'account_type')
            ->get();
          $company['vendor_fee_details'] = ProgramVendorFee::where('buyer_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'fee_name',
              'type',
              'value',
              'anchor_bearing_discount',
              'vendor_bearing_discount',
              'taxes',
              'dealer_bearing',
              'per_amount'
            )
            ->get();
        }
      } else {
        $vendor_configuration = new OdAccountsResource(
          ProgramVendorConfiguration::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'id',
              'program_id',
              'company_id',
              'buyer_id',
              'sanctioned_limit',
              'payment_account_number',
              'limit_expiry_date',
              'limit_review_date',
              'request_auto_finance',
              'auto_approve_finance',
              'eligibility',
              'status',
              'utilized_amount',
              'pipeline_amount'
            )
            ->first()
        );
        $company['vendor_configuration'] = $vendor_configuration;
        $company['vendor_discount_details'] = ProgramVendorDiscount::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->select(
            'benchmark_title',
            'benchmark_rate',
            'business_strategy_spread',
            'credit_spread',
            'total_spread',
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing',
            'penal_discount_on_principle',
            'grace_period',
            'grace_period_discount',
            'maturity_handling_on_holidays',
            'from_day',
            'to_day'
          )
          ->get();
        $company['vendor_bank_details'] = ProgramVendorBankDetail::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->select('name_as_per_bank', 'account_number', 'bank_name', 'branch', 'swift_code', 'account_type')
          ->get();
        $company['vendor_fee_details'] = ProgramVendorFee::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->select(
            'fee_name',
            'type',
            'value',
            'anchor_bearing_discount',
            'vendor_bearing_discount',
            'taxes',
            'dealer_bearing',
            'per_amount'
          )
          ->get();
      }
      $company['utilized_amount'] = $vendor_configuration->utilized_amount;
      $company['pipeline_amount'] = $vendor_configuration->pipeline_amount;
      $company['utilized_percentage_ratio'] = $vendor_configuration->utilized_percentage;
    }

    return response()->json($data);
  }

  public function paymentDetails(Bank $bank, Invoice $invoice)
  {
    $invoice = new InvoiceDetailsResource($invoice->load('program.anchor', 'paymentRequests.paymentAccounts'));

    $payment_requests = PaymentRequestResource::collection(
      PaymentRequest::with('cbsTransactions')
        ->where('invoice_id', $invoice->id)
        ->get()
    );

    return view('content.bank.reports.payment-details', [
      'bank' => $bank,
      'invoice' => $invoice,
      'payment_requests' => $payment_requests,
    ]);
  }

  public function dailyDiscount(Bank $bank, Invoice $invoice)
  {
    $cbs_transactions = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
      $query->where('invoice_id', $invoice->id);
    })
      ->whereDate('created_at', '>', Carbon::parse($invoice->due_date))
      ->get();

    return view('content.bank.reports.daily-discount', ['bank' => $bank, 'cbs_transactions' => $cbs_transactions]);
  }

  public function fundingLimitUtilization(Bank $bank, Program $program, Company $company)
  {
    if ($program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration_details = ProgramVendorConfiguration::with('program.anchor', 'company')
        ->where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->first();
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration_details = ProgramVendorConfiguration::with('program.anchor', 'company')
          ->where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->first();
      } else {
        $vendor_configuration_details = ProgramVendorConfiguration::with('program.anchor', 'buyer')
          ->where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->first();
      }
    }

    $pipeline_requests = $company->pipelineAmount($program);
    $utilized_amount = $company->utilizedAmount($program);
    $available_amount = $vendor_configuration_details->sanctioned_limit - ($utilized_amount + $pipeline_requests);

    return view('content.bank.reports.funding-limit-details', [
      'program' => $program,
      'company' => $company,
      'program_vendor_configuration' => $vendor_configuration_details,
      'pipeline_amount' => $pipeline_requests,
      'utilized_amount' => $utilized_amount,
      'available_amount' => $available_amount,
    ]);
  }

  public function bankGlsReports(Bank $bank, Request $request)
  {
    $account_name = $request->query('account_name');
    $account_number = $request->query('account_number');
    $per_page = $request->query('per_page');

    $data = BankPaymentAccount::where('bank_id', $bank->id)
      ->when($account_number && $account_number != '', function ($query) use ($account_number) {
        $query->where('account_number', 'LIKE', '%' . $account_number . '%');
      })
      ->when($account_name && $account_name != '', function ($query) use ($account_name) {
        $query->where('account_name', 'LIKE', '%' . $account_name . '%');
      });

    if ($per_page) {
      return ResourcesBankPaymentAccount::collection($data->paginate($per_page))
        ->response()
        ->getData();
    }

    return $data->get();
  }
}
