<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentRequestRejectionNotification implements ShouldQueue
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
        $maker = $payment_request
          ->approvals()
          ->pluck('user_id')
          ->first();

        $user = User::find($maker);
        if (auth()->check() && $user) {
          SendMail::dispatch($user->email, 'PaymentRequestRejection', [
            'payment_request_id' => $payment_request->id,
            'user_name' => auth()->user()->name,
          ]);
        }
      }
    });
  }
}
