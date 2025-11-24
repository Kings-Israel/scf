<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use Carbon\Carbon;
use App\Models\Bank;
use App\Models\Invoice;
use App\Models\Program;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramVendorFee;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Http\Resources\PaymentRequestResource;
use App\Models\CbsTransaction;
use Illuminate\Notifications\DatabaseNotification;

class DashboardController extends Controller
{
  public function index(Bank $bank)
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

    return view('content.bank.dashboard', compact('months', 'months_formatted', 'bank'));
  }

  public function dashboardData(Bank $bank)
  {
    $total_anchors_count = 0;
    $vendor_financing_anchors_count = 0;
    $factoring_anchors_count = 0;
    $dealer_financing_anchors_count = 0;

    $buyers_count = 0;
    $vendors_count = 0;
    $dealers_count = 0;
    $payments_pending_approval_count = 0;

    // Vendor Financing Data
    $vendor_financing_maturing_payments_count = 0;
    $vendor_financing_payments_pending_approval_count = 0;
    $vendor_financing_disbursed_payments_count = 0;

    // Factoring Data
    $factoring_maturing_payments_count = 0;
    $factoring_payments_pending_approval_count = 0;
    $factoring_disbursed_payments_count = 0;

    // Dealer Financing Data
    $dealer_financing_maturing_payments_count = 0;
    $dealer_financing_payments_pending_approval_count = 0;
    $dealer_financing_disbursed_payments_count = 0;

    $programs = Program::where('bank_id', $bank->id)
      ->where('account_status', 'active')
      ->get();

    if ($programs->count() > 0) {
      foreach ($programs as $program) {
        $total_anchors_count += $program->getAnchor() ? 1 : 0;
        if ($program->programType->name == Program::DEALER_FINANCING) {
          $dealer_financing_anchors_count += $program->getAnchor() ? 1 : 0;
          $dealers_count += $program->getDealers()->count();
        } else {
          if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            $vendor_financing_anchors_count += $program->getAnchor() ? 1 : 0;
            $vendors_count += $program->getVendors()->count();
          } else {
            $factoring_anchors_count += $program->getAnchor() ? 1 : 0;
            $buyers_count += $program->getBuyers()->count();
          }
        }
      }

      $program_ids = $programs->pluck('id');

      // Vendor Financing Data
      // Get Vendor Financing Maturing Payments
      // Get Vendor Financing Pending Payment Requests
      $vendor_financing_maturing_payments_count = Invoice::whereHas('program', function ($q) use ($program_ids) {
        $q->whereIn('id', $program_ids)->whereHas('programCode', function ($q) {
          $q->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()->format('Y-m-d'),
          now()
            ->addDays(7)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $vendor_financing_payments_pending_approval_count = PaymentRequest::select(
        'id',
        'reference_number',
        'payment_request_date',
        'status',
        'amount',
        'invoice_id',
        'created_at'
      )
        ->where('status', 'created')
        ->whereHas('invoice', function ($query) use ($program_ids) {
          $query
            ->whereIn('program_id', $program_ids)
            ->whereHas('program', function ($query) {
              $query->whereHas('programCode', function ($query) {
                $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
              });
            })
            ->whereDate('due_date', '>', now()->format('Y-m-d'));
        })
        ->where(function ($query) {
          $query
            ->whereHas('cbsTransactions', function ($query) {
              $query->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT);
            })
            ->orWhereDoesntHave('cbsTransactions');
        })
        ->sum('amount');

      // Get Vendor Financing Disbursed Payments
      $vendor_financing_disbursed_payments_count = CbsTransaction::vendorFinancing()
        ->whereHas('paymentRequest', function ($query) use ($program_ids) {
          $query->whereHas('invoice', function ($query) use ($program_ids) {
            $query->whereHas('program', function ($query) use ($program_ids) {
              $query->whereIn('id', $program_ids);
            });
          });
        })
        ->whereIn('status', ['Successful'])
        ->whereIn('transaction_type', [
          CbsTransaction::PAYMENT_DISBURSEMENT,
          CbsTransaction::FEES_CHARGES,
          CbsTransaction::ACCRUAL_POSTED_INTEREST,
        ])
        ->whereBetween('created_at', [now()->startOfMonth(), now()])
        ->sum('amount');
      // End Vendor Financing Data

      // Factoring Data
      // Get Factoring Maturing Payments
      $factoring_maturing_payments_count = Invoice::whereHas('program', function ($q) use ($program_ids) {
        $q->whereIn('id', $program_ids)->whereHas('programCode', function ($q) {
          $q->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]);
        });
      })
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()->format('Y-m-d'),
          now()
            ->addDays(7)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      // Get Factoring Pending Payment Requests
      $factoring_payments_pending_approval_count = PaymentRequest::select(
        'id',
        'reference_number',
        'payment_request_date',
        'status',
        'amount',
        'invoice_id',
        'created_at'
      )
        ->where('status', 'created')
        ->whereHas('invoice', function ($query) use ($program_ids) {
          $query
            ->whereIn('program_id', $program_ids)
            ->whereHas('program', function ($query) {
              $query->whereHas('programCode', function ($query) {
                $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]);
              });
            })
            ->whereDate('due_date', '>', now()->format('Y-m-d'));
        })
        ->where(function ($query) {
          $query
            ->whereHas('cbsTransactions', function ($query) {
              $query->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT);
            })
            ->orWhereDoesntHave('cbsTransactions');
        })
        ->sum('amount');

      // Get Factoring Disbursed Payments
      $factoring_disbursed_payments_count = CbsTransaction::factoring()
        ->whereHas('paymentRequest', function ($query) use ($program_ids) {
          $query->whereHas('invoice', function ($query) use ($program_ids) {
            $query->whereHas('program', function ($query) use ($program_ids) {
              $query->whereIn('id', $program_ids);
            });
          });
        })
        ->whereIn('status', ['Successful'])
        ->whereIn('transaction_type', [
          CbsTransaction::PAYMENT_DISBURSEMENT,
          CbsTransaction::FEES_CHARGES,
          CbsTransaction::ACCRUAL_POSTED_INTEREST,
        ])
        ->whereBetween('created_at', [now()->startOfMonth(), now()])
        ->sum('amount');
      // End Factoring Data

      // Dealer Financing Data
      // Get Dealer Financing Maturing Payments
      $dealer_financing_maturing_payments_count = Invoice::whereHas('program', function ($q) use ($program_ids) {
        $q->whereIn('id', $program_ids)->whereHas('programType', function ($q) {
          $q->where('name', Program::DEALER_FINANCING);
        });
      })
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()->format('Y-m-d'),
          now()
            ->addDays(7)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      // Get Dealer Financing Pending Payment Requests
      $dealer_financing_payments_pending_approval_count = PaymentRequest::select(
        'id',
        'reference_number',
        'payment_request_date',
        'status',
        'amount',
        'invoice_id',
        'created_at'
      )
        ->where('status', 'created')
        ->whereHas('invoice', function ($query) use ($program_ids) {
          $query
            ->whereIn('program_id', $program_ids)
            ->whereHas('program', function ($query) {
              $query->whereHas('programType', function ($query) {
                $query->where('name', Program::DEALER_FINANCING);
              });
            })
            ->whereDate('due_date', '>', now()->format('Y-m-d'));
        })
        ->where(function ($query) {
          $query
            ->whereHas('cbsTransactions', function ($query) {
              $query->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT);
            })
            ->orWhereDoesntHave('cbsTransactions');
        })
        ->sum('amount');

      // Get Dealer Financing Disbursed Payments
      $dealer_financing_disbursed_payments_count = CbsTransaction::dealerFinancing()
        ->whereHas('paymentRequest', function ($query) use ($program_ids) {
          $query->whereHas('invoice', function ($query) use ($program_ids) {
            $query->whereHas('program', function ($query) use ($program_ids) {
              $query->whereIn('id', $program_ids);
            });
          });
        })
        ->whereIn('status', ['Successful'])
        ->whereIn('transaction_type', [
          CbsTransaction::PAYMENT_DISBURSEMENT,
          CbsTransaction::FEES_CHARGES,
          CbsTransaction::ACCRUAL_POSTED_INTEREST,
        ])
        ->whereBetween('created_at', [now()->startOfMonth(), now()])
        ->sum('amount');
      // End Dealer Financing Data

      if (request()->wantsJson()) {
        return response()->json([
          'has_data' => true,
          'programs' => $programs,
          'vendor_financing_anchors_count' => $vendor_financing_anchors_count,
          'factoring_anchors_count' => $factoring_anchors_count,
          'dealer_financing_anchors_count' => $dealer_financing_anchors_count,
          'vendors_count' => $vendors_count,
          'buyers_count' => $buyers_count,
          'dealers_count' => $dealers_count,
          // Vendor Financing Data
          'vendor_financing_maturing_payments_count' => round($vendor_financing_maturing_payments_count, 2),
          'vendor_financing_payments_pending_approval_count' => round(
            $vendor_financing_payments_pending_approval_count,
            2
          ),
          'vendor_financing_disbursed_payments_count' => round($vendor_financing_disbursed_payments_count, 2),

          // Factoring Data
          'factoring_maturing_payments_count' => round($factoring_maturing_payments_count, 2),
          'factoring_payments_pending_approval_count' => round($factoring_payments_pending_approval_count, 2),
          'factoring_disbursed_payments_count' => round($factoring_disbursed_payments_count, 2),

          // Dealer Financing Data
          'dealer_financing_maturing_payments_count' => round($dealer_financing_maturing_payments_count, 2),
          'dealer_financing_payments_pending_approval_count' => round(
            $dealer_financing_payments_pending_approval_count,
            2
          ),
          'dealer_financing_disbursed_payments_count' => round($dealer_financing_disbursed_payments_count, 2),
        ]);
      }
    }
  }

  public function graphData(Request $request, Bank $bank)
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

    // Disbursed Amount
    $disbursed_amount_data = [];
    foreach ($months as $key => $month) {
      array_push(
        $disbursed_amount_data,
        CbsTransaction::where('bank_id', $bank->id)
          ->whereIn('transaction_type', [
            CbsTransaction::PAYMENT_DISBURSEMENT,
            CbsTransaction::FEES_CHARGES,
            CbsTransaction::ACCRUAL_POSTED_INTEREST,
          ])
          ->where('status', 'Successful')
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount')
      );
    }

    // Income
    $income_data = [];
    foreach ($months as $key => $month) {
      array_push(
        $income_data,
        CbsTransaction::where('bank_id', $bank->id)
          ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST, CbsTransaction::FEES_CHARGES])
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('amount')
      );
    }

    // PI Amount
    $pi_amount_data = [];
    foreach ($months as $key => $month) {
      array_push(
        $pi_amount_data,
        Invoice::whereHas('program', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id);
        })
          ->whereIn('status', ['approved'])
          ->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
          ->sum('calculated_total_amount')
      );
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

          // Disbursed Amount
          $disbursed_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $disbursed_amount_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [
                  CbsTransaction::PAYMENT_DISBURSEMENT,
                  CbsTransaction::FEES_CHARGES,
                  CbsTransaction::ACCRUAL_POSTED_INTEREST,
                ])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount')
            );
          }

          // Income
          $income_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $income_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST, CbsTransaction::FEES_CHARGES])
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount')
            );
          }

          // PI Amount
          $pi_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $pi_amount_data,
              Invoice::whereHas('program', function ($query) use ($bank) {
                $query->where('bank_id', $bank->id);
              })
                ->whereIn('status', ['approved'])
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('calculated_total_amount')
            );
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

          // Disbursed Amount
          $disbursed_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $disbursed_amount_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [
                  CbsTransaction::PAYMENT_DISBURSEMENT,
                  CbsTransaction::FEES_CHARGES,
                  CbsTransaction::ACCRUAL_POSTED_INTEREST,
                ])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount')
            );
          }

          // Income
          $income_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $income_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST, CbsTransaction::FEES_CHARGES])
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount')
            );
          }

          // PI Amount
          $pi_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $pi_amount_data,
              Invoice::whereHas('program', function ($query) use ($bank) {
                $query->where('bank_id', $bank->id);
              })
                ->whereIn('status', ['approved'])
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('calculated_total_amount')
            );
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

          // Disbursed Amount
          $disbursed_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $disbursed_amount_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [
                  CbsTransaction::PAYMENT_DISBURSEMENT,
                  CbsTransaction::FEES_CHARGES,
                  CbsTransaction::ACCRUAL_POSTED_INTEREST,
                ])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount')
            );
          }

          // Income
          $income_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $income_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST, CbsTransaction::FEES_CHARGES])
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('amount')
            );
          }

          // PI Amount
          $pi_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $pi_amount_data,
              Invoice::whereHas('program', function ($query) use ($bank) {
                $query->where('bank_id', $bank->id);
              })
                ->whereIn('status', ['approved'])
                ->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])
                ->sum('calculated_total_amount')
            );
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

          // Disbursed Amount
          $disbursed_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $disbursed_amount_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [
                  CbsTransaction::PAYMENT_DISBURSEMENT,
                  CbsTransaction::FEES_CHARGES,
                  CbsTransaction::ACCRUAL_POSTED_INTEREST,
                ])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount')
            );
          }

          // Income
          $income_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $income_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST, CbsTransaction::FEES_CHARGES])
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount')
            );
          }

          // PI Amount
          $pi_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $pi_amount_data,
              Invoice::whereHas('program', function ($query) use ($bank) {
                $query->where('bank_id', $bank->id);
              })
                ->whereIn('status', ['approved'])
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('calculated_total_amount')
            );
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

          // Disbursed Amount
          $disbursed_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $disbursed_amount_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [
                  CbsTransaction::PAYMENT_DISBURSEMENT,
                  CbsTransaction::FEES_CHARGES,
                  CbsTransaction::ACCRUAL_POSTED_INTEREST,
                ])
                ->where('status', 'Successful')
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount')
            );
          }

          // Income
          $income_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $income_data,
              CbsTransaction::where('bank_id', $bank->id)
                ->whereIn('transaction_type', [CbsTransaction::ACCRUAL_POSTED_INTEREST, CbsTransaction::FEES_CHARGES])
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('amount')
            );
          }

          // PI Amount
          $pi_amount_data = [];
          foreach ($months as $key => $month) {
            array_push(
              $pi_amount_data,
              Invoice::whereHas('program', function ($query) use ($bank) {
                $query->where('bank_id', $bank->id);
              })
                ->whereIn('status', ['approved'])
                ->whereBetween('created_at', [
                  Carbon::parse($month)->startOfMonth(),
                  Carbon::parse($month)->endOfMonth(),
                ])
                ->sum('calculated_total_amount')
            );
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

    return response()->json(
      [
        'months' => $months,
        'months_formatted' => $months_formatted,
        'disbursed_amount_data' => $disbursed_amount_data,
        'income_data' => $income_data,
        'pi_amount_data' => $pi_amount_data,
      ],
      200
    );
  }

  public function notifications(Bank $bank)
  {
    return view('content.bank.notifications', compact('bank'));
  }

  public function notificationsData(Bank $bank)
  {
    $notifications = $bank->unreadNotifications->toArray();
    $notifications = array_merge(
      $notifications,
      auth()
        ->user()
        ->unreadNotifications->toArray()
    );
    return response()->json(collect($notifications)->toArray());
  }

  public function notificationRead(Bank $bank, $notification)
  {
    $notification = DatabaseNotification::findOrFail($notification);

    $notification->markAsRead();
  }

  public function notificationReadAll(Bank $bank)
  {
    $bank->unreadNotifications->markAsRead();
    auth()
      ->user()
      ->unreadNotifications->markAsRead();
  }
}
