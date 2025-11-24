<?php

namespace App\Jobs;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\PaymentRequest;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOverdueRemainder implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    // Get invoices that are past due or due today
    $invoices = InvoiceResource::collection(
      Invoice::where('financing_status', 'financed')
        ->whereDate('due_date', '<=', now()->format('Y-m-d'))
        ->get()
    );

    foreach ($invoices as $invoice) {
      // Calculate interest for each past day and create payment request
      if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
        if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $penal_rate = $invoice->program->vendorDiscountDetails->where('company_id', $invoice->company->id)->first()
            ->penal_discount_on_principle;
          $grace_period = $invoice->program->vendorDiscountDetails->where('company_id', $invoice->company->id)->first()
            ->grace_period;
          $grace_period_discount = $invoice->program->vendorDiscountDetails
            ->where('company_id', $invoice->company->id)
            ->first()->grace_period_discount;
        } else {
          $penal_rate = $invoice->program->vendorDiscountDetails
            ->where('company_id', $invoice->company->id)
            ->where('buyer_id', $invoice->buyer->id)
            ->first()->penal_discount_on_principle;
          $grace_period = $invoice->program->vendorDiscountDetails
            ->where('company_id', $invoice->company->id)
            ->where('buyer_id', $invoice->buyer->id)
            ->first()->grace_period;
          $grace_period_discount = $invoice->program->vendorDiscountDetails
            ->where('company_id', $invoice->company->id)
            ->where('buyer_id', $invoice->buyer->id)
            ->first()->grace_period_discount;
        }
      } else {
        $penal_rate = $invoice->program->vendorDiscountDetails->where('company_id', $invoice->company->id)->first()
          ->penal_discount_on_principle;
        $grace_period = $invoice->program->vendorDiscountDetails->where('company_id', $invoice->company->id)->first()
          ->grace_period;
        $grace_period_discount = $invoice->program->vendorDiscountDetails
          ->where('company_id', $invoice->company->id)
          ->first()->grace_period_discount;
      }

      $difference_in_days = 0;

      if ($invoice->disbursed_amount < $invoice->invoice_total_amount) {
        $amount_repayable = $invoice->invoice_total_amount + $invoice->overdue_amount - $invoice->paid_amount;
      } else {
        $amount_repayable = $invoice->disbursed_amount + $invoice->overdue_amount - $invoice->paid_amount;
      }

      $principle_amount = $amount_repayable;
      $amount = 0;
      if ($grace_period && Carbon::parse($invoice->due_date)->diffInDays(now()) <= $grace_period) {
        // Check if invoice is in grace period
        for ($i = 1; $i <= Carbon::parse($invoice->due_date)->diffInDays(now()); $i++) {
          $amount = $principle_amount * ($grace_period_discount / 365);
          $principle_amount += $amount;
        }
      } else {
        for ($i = 1; $i <= $difference_in_days; $i++) {
          $amount = $principle_amount * ($penal_rate / 365);
          $principle_amount += $amount;
        }
      }

      if (Carbon::parse($invoice->due_date)->equalTo(now()->format('Y-m-d'))) {
        $payment_request = PaymentRequest::where('invoice_id', $invoice->id)
          ->whereDate('created_at', now())
          ->first();
        if ($payment_request) {
          SendMail::dispatchAfterResponse($invoice->program->bank->users->email, 'PaymentRequestGeneration', [
            'payment_request_id' => $payment_request->id,
          ]);
        }
      } else {
        // Send Mail Notification to program anchor users
        if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
          foreach ($invoice->program->anchor->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'DfInterestPosting', ['invoice' => $invoice->id]);
            SendMail::dispatchAfterResponse($user->email, 'DrawdownOverdueBalance', ['invoice_id' => $invoice->id]);
          }
        } else {
          if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            foreach ($invoice->company->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'InterestPosting', [
                'invoice' => $invoice,
                'amount' => $amount,
                'principle_amount' => $principle_amount,
                'type' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? 'vendor_financing'
                    : 'dealer_financing',
              ]);
              SendMail::dispatchAfterResponse($user->email, 'LoanOverdue', ['invoice_id' => $invoice->id]);
            }
          } else {
            foreach ($invoice->program->anchor->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'InterestPosting', [
                'invoice' => $invoice,
                'amount' => $amount,
                'principle_amount' => $principle_amount,
                'type' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? 'vendor_financing'
                    : 'dealer_financing',
              ]);
              SendMail::dispatchAfterResponse($user->email, 'LoanOverdue', ['invoice_id' => $invoice->id]);
            }
          }
        }

        // Notify Bank Users
        foreach ($invoice->program->bankUserDetails as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InterestPosting', [
            'invoice' => $invoice,
            'amount' => $amount,
            'principle_amount' => $principle_amount,
            'type' =>
              $invoice->program->programType->name == Program::VENDOR_FINANCING
                ? 'vendor_financing'
                : 'dealer_financing',
          ]);
          SendMail::dispatchAfterResponse($user->email, 'LoanOverdue', ['invoice_id' => $invoice->id]);
        }
      }
    }
  }
}
