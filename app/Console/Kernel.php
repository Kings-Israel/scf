<?php

namespace App\Console;

use App\Http\Resources\InvoiceResource;
use App\Jobs\BlockCompanyWithOverdueInvoices;
use App\Jobs\BulkInvoiceProcessing;
use App\Jobs\BulkRequestFinancing;
use App\Jobs\CheckDrafts;
use App\Jobs\CheckEligibleForFinancing;
use App\Jobs\LoanReminder;
use App\Jobs\CheckProgramExpiry;
use App\Jobs\LoanPastDueReminer;
use App\Jobs\CheckInvoiceDueDate;
use App\Jobs\CheckInvoiceStalePeriod;
use App\Jobs\CheckPasswordExpiry;
use App\Jobs\CheckPastDueInvoices;
use App\Jobs\CheckProgramReview;
use App\Jobs\SendMail;
use App\Jobs\SendOverdueRemainder;
use App\Jobs\UpdateInvoicesCalculatedAmount;
use App\Jobs\UpdatePaymentRequestAmount;
use App\Mail\BulkPaymentReminder;
use App\Models\BankGeneralProductConfiguration;
use App\Models\CbsTransaction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceProcessing;
use App\Models\Otp;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\PaymentRequestAccount;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\BankProductsConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\BankProductRepaymentPriority;
use App\Models\CompanyUser;
use App\Models\CronLog;
use App\Models\InvoiceApproval;
use App\Models\InvoiceUploadReport;
use App\Models\ProgramDiscount;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class Kernel extends ConsoleKernel
{
  /**
   * Get the timezone that should be used by default for scheduled events.
   */
  protected function scheduleTimezone(): DateTimeZone|string|null
  {
    return 'Africa/Nairobi';
  }
  /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
   */
  protected function schedule(Schedule $schedule)
  {
    $schedule->job(new CheckInvoiceDueDate())->dailyAt('00:00');
    $schedule->job(new CheckPastDueInvoices())->dailyAt('00:00');
    $schedule->job(new CheckEligibleForFinancing())->dailyAt('00:00');

    // $schedule->job(new SendOverdueRemainder())->dailyAt('07:00');
    $schedule->job(LoanReminder::class)->dailyAt('10:00');
    // $schedule->job(LoanReminder::class)->dailyAt('14:30');

    // $schedule->job(LoanPastDueReminer::class)->dailyAt('10:00');

    $schedule->job(CheckInvoiceStalePeriod::class)->dailyAt('00:00');

    $schedule->job(CheckProgramExpiry::class)->dailyAt('11:00');

    $schedule->job(CheckDrafts::class)->dailyAt('00:00');

    $schedule->job(CheckPasswordExpiry::class)->dailyAt('08:00');

    $schedule->job(CheckProgramReview::class)->dailyAt('09:00');

    $schedule->job(BlockCompanyWithOverdueInvoices::class)->dailyAt('00:00');

    $schedule->job(UpdatePaymentRequestAmount::class)->dailyAt('00:00');

    // $schedule->job(UpdateInvoicesCalculatedAmount::class)->dailyAt('20:00');

    $schedule
      ->call(function () {
        Otp::where('expires_at', '<', now())->delete();
      })
      ->everyMinute();

    $schedule->job(new BulkInvoiceProcessing())->everyMinute();

    $schedule
      ->call(function () {
        $invoices = Invoice::whereIn('status', ['approved', 'disbursed'])
          ->where('pi_number', null)
          ->get();

        foreach ($invoices as $invoice) {
          $invoice->update([
            'pi_number' => 'PI_' . $invoice->id,
          ]);

          if (!$invoice->calculated_total_amount) {
            $invoice->update([
              'calculated_total_amount' => $invoice->invoice_total_amount,
            ]);
          }
        }
      })
      ->dailyAt('17:00');

    $schedule
      ->call(function () {
        $uploaded_invoices = InvoiceUploadReport::where('product_type', null)->get();
        foreach ($uploaded_invoices as $uploaded_invoice) {
          $invoice = Invoice::where('invoice_number', $uploaded_invoice->invoice_number)->first();
          if ($invoice) {
            $uploaded_invoice->update([
              'product_type' => $invoice->program->programType->name,
              'product_code' =>
                $invoice->program->programCode->name === Program::FACTORING_WITH_RECOURSE ||
                $invoice->program->programCode->name === Program::FACTORING_WITHOUT_RECOURSE
                  ? 'Factoring'
                  : $invoice->program->programCode->name,
            ]);
          }
        }
      })
      ->dailyAt('23:00');

    // // Remove Payment Requests for discount and fees transactions
    // $schedule
    //   ->call(function () {
    //     // Get Discount Charge CBS Transactions
    //     $cbs_transactions = CbsTransaction::whereIn('transaction_type', [
    //       CbsTransaction::ACCRUAL_POSTED_INTEREST,
    //       CbsTransaction::FEES_CHARGES,
    //     ])
    //       // ->whereHas('paymentRequest', function ($query) {
    //       //   $query->whereHas('invoice', function ($query) {
    //       //     $query->where('invoice_number', '6/SI/010145/20231100-RE-5100014912');
    //       //   });
    //       // })
    //       ->get();
    //     foreach ($cbs_transactions as $cbs_transaction) {
    //       if ($cbs_transaction->paymentRequest && $cbs_transaction->paymentRequest->invoice) {
    //         $discount_payment_request_id = PaymentRequest::find($cbs_transaction->payment_request_id)?->id;

    //         $disbursement_cbs_transaction = CbsTransaction::whereHas('paymentRequest', function ($query) use (
    //           $cbs_transaction
    //         ) {
    //           $query->where('invoice_id', $cbs_transaction->paymentRequest->invoice_id);
    //         })
    //           ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
    //           ->first();

    //         $disbursement_payment_request_id = PaymentRequest::find($disbursement_cbs_transaction->payment_request_id)
    //           ?->id;

    //         // Update CBS Transaction to Disbursement Payment Request
    //         if (
    //           $discount_payment_request_id &&
    //           $disbursement_payment_request_id &&
    //           $discount_payment_request_id !== $disbursement_payment_request_id
    //         ) {
    //           $cbs_transaction->update([
    //             'payment_request_id' => $disbursement_payment_request_id,
    //           ]);

    //           // Create Payment Request Account for Discount
    //           PaymentRequestAccount::create([
    //             'payment_request_id' => $disbursement_payment_request_id,
    //             'account' => $cbs_transaction->credit_to_account,
    //             'account_name' => $cbs_transaction->credit_to_account_name,
    //             'amount' => round($cbs_transaction->amount, 2),
    //             'type' => 'discount',
    //             'description' =>
    //               $cbs_transaction->transaction_type === CbsTransaction::ACCRUAL_POSTED_INTEREST
    //                 ? Invoice::VENDOR_DISCOUNT_BEARING
    //                 : Invoice::VENDOR_FEE_BEARING,
    //             'created_at' => $cbs_transaction->created_at,
    //             'updated_at' => $cbs_transaction->updated_at,
    //           ]);

    //           // Delete the Discount Payment Request
    //           PaymentRequest::where('id', $discount_payment_request_id)->delete();
    //         }
    //       } else {
    //         info($cbs_transaction->id);
    //       }
    //     }
    //   })
    //   ->dailyAt('23:00');

    // // Remove @yopmail users
    // $schedule
    //   ->call(function () {
    //     // Vendors
    //     $emails = ['vendor@yopmail.com', 'vendor1@yopmail.com', 'vendor0@yopmail.com'];

    //     foreach ($emails as $email) {
    //       $user = User::where('email', $email)->first();
    //       if ($user) {
    //         // Update Payment Requests to any company user
    //         $payment_requests = PaymentRequest::where('created_by', $user->id)->get();

    //         foreach ($payment_requests as $payment_request) {
    //           $company_user = CompanyUser::where('user_id', '!=', $user->id)
    //             ->where('company_id', $payment_request->invoice->company_id)
    //             ->where('active', true)
    //             ->first();
    //           $updated_at = $payment_request->updated_at;
    //           $payment_request->created_by = $company_user->user_id;
    //           $payment_request->updated_at = $updated_at;
    //           $payment_request->save();
    //         }

    //         // Delete all logs
    //         Activity::where('causer_id', $user->id)->delete();

    //         $user->delete();
    //       }
    //     }

    //     // Anchors
    //     $emails = ['anchor@yopmail.com', 'anchor1@yopmail.com', 'anchor0@yopmail.com'];

    //     foreach ($emails as $email) {
    //       $user = User::where('email', $email)->first();
    //       if ($user) {
    //         // Update Payment Requests to any company user
    //         $payment_requests = PaymentRequest::where('created_by', $user->id)->get();

    //         foreach ($payment_requests as $payment_request) {
    //           $company_user = CompanyUser::whereHas('user', function ($query) use ($emails) {
    //             $query->whereNotIn('email', $emails);
    //           })
    //             ->where('company_id', $payment_request->invoice->company_id)
    //             // ->where('active', true)
    //             ->first();
    //           $updated_at = $payment_request->updated_at;
    //           $payment_request->created_by = $company_user->user_id;
    //           $payment_request->updated_at = $updated_at;
    //           $payment_request->save();
    //         }

    //         // Invoie Approval
    //         $approvals = InvoiceApproval::where('user_id', $user->id)->get();
    //         foreach ($approvals as $approval) {
    //           $company_user = CompanyUser::whereHas('user', function ($query) use ($emails) {
    //             $query->whereNotIn('email', $emails);
    //           })
    //             ->where('company_id', $approval->invoice->company_id)
    //             // ->where('active', true)
    //             ->first();

    //           $updated_at = $approval->updated_at;
    //           $approval->user_id = $company_user->user_id;
    //           $approval->updated_at = $updated_at;
    //           $approval->save();
    //         }

    //         // Delete all logs
    //         Activity::where('causer_id', $user->id)->delete();

    //         $user->delete();
    //       }
    //     }
    //   })
    //   ->dailyAt('23:00');
    $schedule
      ->call(function () {
        // Change Financed to disbursed
        $invoices = Invoice::where('financing_status', 'financed')->get();

        foreach ($invoices as $invoice) {
          // Make sure the updated at value remains as it was
          $updated_at = $invoice->updated_at;
          if (Carbon::parse($invoice->due_date)->greaterThan(now()->format('Y-m-d'))) {
            $invoice->update([
              'financing_status' => 'disbursed',
              'status' => 'approved',
              'stage' => 'approved',
            ]);
          } else {
            $invoice->update([
              'financing_status' => 'disbursed',
              'status' => 'approved',
            ]);
          }

          $invoice->updated_at = $updated_at;
          $invoice->save();
        }

        // Change Submitted and approved to financed
        $approved_invoices = Invoice::where('financing_status', 'submitted')
          ->whereDate('due_date', '>', now()->format('Y-m-d'))
          ->whereHas('paymentRequests', function ($query) {
            $query->where('status', 'approved');
          })
          ->get();

        foreach ($approved_invoices as $invoice) {
          $invoice->update([
            'financing_status' => 'financed',
          ]);
        }

        // Change Financed to disbursed
        $closed_invoices = Invoice::where('financing_status', 'closed')->where('status', 'disbursed')->get();

        foreach ($closed_invoices as $invoice) {
          $updated_at = $invoice->updated_at;
          $invoice->update([
            'status' => 'approved'
          ]);

          $invoice->updated_at = $updated_at;
          $invoice->save();
        }
      })
      ->dailyAt('23:05');

    // Fix for incocrrectly calculated payment requests
    // $schedule
    //   ->call(function () {
    //     // Update past requests with updated rates for vendors
    //     $program_vendor_configurations = ProgramVendorConfiguration::whereIn('company_id', [5, 9, 24, 27, 34])
    //       ->whereHas('program', function ($query) {
    //         $query->whereHas('programCode', function ($query) {
    //           $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
    //         });
    //       })
    //       ->get();

    //     foreach ($program_vendor_configurations as $program_vendor_configuration) {
    //       $program_vendor_discounts = ProgramVendorDiscount::where(
    //         'program_id',
    //         $program_vendor_configuration->program_id
    //       )
    //         ->where('company_id', $program_vendor_configuration->company_id)
    //         ->first();

    //       $vendor_discount_details = ProgramVendorDiscount::where(
    //         'program_id',
    //         $program_vendor_configuration->program_id
    //       )
    //         ->where('company_id', $program_vendor_configuration->company_id)
    //         ->first();
    //       $vendor_fees = ProgramVendorFee::where('program_id', $program_vendor_configuration->program_id)
    //         ->where('company_id', $program_vendor_configuration->company_id)
    //         ->get();

    //       $invoices = Invoice::where('program_id', $program_vendor_configuration->program_id)
    //         ->where('company_id', $program_vendor_configuration->company_id)
    //         ->whereHas('paymentRequests', function ($query) {
    //           $query->whereDate('payment_request_date', '>', '2025-09-16');
    //         })
    //         ->whereIn('financing_status', ['financed', 'submitted'])
    //         ->get();

    //       foreach ($invoices as $invoice) {
    //         $fees_amount = 0;
    //         $fees_tax_amount = 0;
    //         $anchor_bearing_fees = 0;
    //         $vendor_bearing_fees = 0;
    //         $legible_amount = ($invoice->eligibility / 100) * $invoice->invoice_total_amount;
    //         $payment_date = PaymentRequest::where('invoice_id', $invoice->id)->first()->payment_request_date;
    //         $eligibility = $invoice->eligibility;
    //         $total_amount = $invoice->invoice_total_amount;
    //         $total_roi = $program_vendor_discounts->total_roi;
    //         $daily_charge = 0;
    //         $fixed_fee = 0;

    //         if ($vendor_fees->count() > 0) {
    //           foreach ($vendor_fees as $fee) {
    //             if ($fee->type === 'percentage') {
    //               if ($fee->charge_type === 'daily') {
    //                 $fees_amount +=
    //                   ($fee->value / 100) *
    //                   $legible_amount *
    //                   Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date));

    //                 if ($fee->taxes) {
    //                   $fees_tax_amount += round(
    //                     ($fee->taxes / 100) *
    //                       (($fee->value / 100) *
    //                         $legible_amount *
    //                         Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date))),
    //                     2
    //                   );
    //                 }

    //                 if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
    //                   $anchor_bearing_fees += 0;
    //                   $vendor_bearing_fees += round(
    //                     ($fee->value / 100) *
    //                       $legible_amount *
    //                       Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date)),
    //                     2
    //                   );
    //                 } else {
    //                   $anchor_bearing_fees += round(
    //                     ($fee->anchor_bearing_discount / 100) *
    //                       (($fee->value / 100) *
    //                         $legible_amount *
    //                         Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date))),
    //                     2
    //                   );
    //                   $vendor_bearing_fees += round(
    //                     ($fee->vendor_bearing_discount / 100) *
    //                       (($fee->value / 100) *
    //                         $legible_amount *
    //                         Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date))),
    //                     2
    //                   );
    //                 }
    //                 $daily_charge = $vendor_bearing_fees;
    //               } else {
    //                 $fees_amount += ($fee->value / 100) * $legible_amount;

    //                 if ($fee->taxes) {
    //                   $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
    //                 }

    //                 if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
    //                   $anchor_bearing_fees += 0;
    //                   $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
    //                 } else {
    //                   $anchor_bearing_fees += round(
    //                     ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
    //                     2
    //                   );
    //                   $vendor_bearing_fees += round(
    //                     ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
    //                     2
    //                   );
    //                 }

    //                 $fixed_fee = $vendor_bearing_fees;
    //               }
    //             }
    //           }
    //         }

    //         // Get Tax on Discount Value
    //         $tax_on_discount = ProgramDiscount::select('tax_on_discount')
    //           ->where('program_id', $invoice->program_id)
    //           ->first()?->tax_on_discount;

    //         $anchor_bearing_discount_value = 0;

    //         $original_discount =
    //           ($eligibility / 100) *
    //           $total_amount *
    //           ($total_roi / 100) *
    //           (Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date)) / 365);

    //         // Tax on discount
    //         $discount_tax_amount = 0;
    //         if ($tax_on_discount && $tax_on_discount > 0) {
    //           $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
    //         }

    //         $discount = $original_discount;
    //         if ($total_roi > 0) {
    //           if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
    //             $discount = $original_discount;
    //           } else {
    //             if ($vendor_discount_details->anchor_discount_bearing > 0) {
    //               $discount =
    //                 ($vendor_discount_details->anchor_discount_bearing / $total_roi) *
    //                 ($eligibility / 100) *
    //                 $total_amount *
    //                 ($total_roi / 100) *
    //                 (Carbon::parse($payment_date)->diffInDays(Carbon::parse($invoice->due_date)) / 365);
    //             } else {
    //               $discount = $original_discount;
    //             }
    //           }
    //         }

    //         $anchor_bearing_discount_value = round($original_discount - $discount, 2);

    //         if ($invoice->program->programType->name === Program::VENDOR_FINANCING) {
    //           $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount - $vendor_bearing_fees;
    //         }

    //         $vendor_amount = $amount;
    //         $discount_type = $invoice->discount_type;
    //         $fee_type = $invoice->fee_type;

    //         // Calculate amount to disburse to vendor based on discount type and fee type
    //         if ($discount_type === Invoice::REAR_ENDED) {
    //           $vendor_amount = $amount + $discount + $discount_tax_amount;
    //         }

    //         if ($fee_type === Invoice::REAR_ENDED) {
    //           if ($invoice->program->programType->name === Program::VENDOR_FINANCING) {
    //             $vendor_amount = $vendor_amount + $fees_tax_amount + $vendor_bearing_fees;
    //           }
    //         }

    //         $vendor_account_payment_request = PaymentRequest::where('invoice_id', $invoice->id)->first();

    //         $vendor_account_payment_request->update([
    //           'amount' => $vendor_amount,
    //           'processing_fee' => round($fees_amount, 2),
    //           'anchor_discount_bearing' => $anchor_bearing_discount_value,
    //           'vendor_discount_bearing' => $original_discount - $anchor_bearing_discount_value,
    //           'anchor_fee_bearing' => $anchor_bearing_fees,
    //           'vendor_fee_bearing' => $vendor_bearing_fees,
    //         ]);

    //         $vendor_payment_account = PaymentRequestAccount::where(
    //           'payment_request_id',
    //           $vendor_account_payment_request->id
    //         )
    //           ->where('type', 'vendor_account')
    //           ->first();

    //         $vendor_payment_account->update([
    //           'amount' => $amount,
    //         ]);

    //         if ($vendor_bearing_fees > 0) {
    //           $vendor_bearing_fees_payment_accounts = PaymentRequestAccount::where(
    //             'payment_request_id',
    //             $vendor_account_payment_request->id
    //           )
    //             ->where('description', Invoice::VENDOR_FEE_BEARING)
    //             ->get();

    //           if ($vendor_bearing_fees_payment_accounts->count() > 0) {
    //             foreach ($vendor_bearing_fees_payment_accounts as $key => $vendor_bearing_fees_payment_account) {
    //               if ($key === 0) {
    //                 $vendor_bearing_fees_payment_account->update([
    //                   'amount' => $daily_charge - $fixed_fee,
    //                 ]);
    //               } else {
    //                 $vendor_bearing_fees_payment_account->update([
    //                   'amount' => $fixed_fee,
    //                 ]);
    //               }
    //             }
    //           }
    //         }

    //         // Update the CBS Transactions
    //         $cbs_transactions = CbsTransaction::where('payment_request_id', $vendor_account_payment_request->id)->get();

    //         foreach ($cbs_transactions as $cbs_transaction) {
    //           if ($cbs_transaction->transaction_type === CbsTransaction::PAYMENT_DISBURSEMENT) {
    //             $cbs_transaction->update([
    //               'amount' => $vendor_amount,
    //             ]);
    //           }

    //           if ($cbs_transaction->transaction_type === CbsTransaction::FEES_CHARGES) {
    //             // Daily Charge
    //             if ($cbs_transaction->credit_to_account === '0000001') {
    //               $cbs_transaction->update([
    //                 'amount' => $daily_charge - $fixed_fee,
    //               ]);
    //             } else {
    //               // Fixed Fee
    //               $cbs_transaction->update([
    //                 'amount' => $fixed_fee,
    //               ]);
    //             }
    //           }

    //           if ($cbs_transaction->transaction_type === CbsTransaction::ACCRUAL_POSTED_INTEREST) {
    //             $cbs_transaction->update([
    //               'amount' => $discount,
    //             ]);
    //           }
    //         }

    //         // Updated Disbursed Amount if invoice is disbrsed
    //         if ($invoice->financing_status === 'financed') {
    //           $invoice->update([
    //             'disbursed_amount' => $vendor_amount,
    //           ]);
    //         }
    //       }
    //     }
    //   })
    //   ->dailyAt('13:52');
  }

  /**
   * Register the commands for the application.
   *
   * @return void
   */
  protected function commands()
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}
