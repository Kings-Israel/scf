<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCbsTransactions implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public array $payment_requests)
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
    PaymentRequest::whereIn('id', $this->payment_requests)->chunk(100, function ($payment_requests) {
      foreach ($payment_requests as $payment_request) {
        // If approval date is later than the requested payment date, update the discount and disbursed amounts
        $latest_approval = PaymentRequestApproval::where('payment_request_id', $payment_request->id)
          ->latest()
          ->first();

        if (
          $latest_approval &&
          $latest_approval->created_at->greaterThan(Carbon::parse($payment_request->payment_request_date))
        ) {
          $payment_request->updateRequestAmounts();
        }

        $payment_request->createCbsTransactions();
      }
    });
  }
}
