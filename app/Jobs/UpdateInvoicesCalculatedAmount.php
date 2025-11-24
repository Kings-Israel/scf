<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\PaymentRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInvoicesCalculatedAmount implements ShouldQueue
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
    $invoices = Invoice::where('calculated_total_amount', null)->orWhere('calculated_total_amount', 0)->get();
    foreach ($invoices as $invoice) {
      $invoice->calculated_total_amount = $invoice->invoice_total_amount;
      $invoice->save();
    }

    $invoices = Invoice::whereDate('invoice_date', '>', now()->format('Y-m-d'))->whereIn('financing_status', ['financed', 'closed'])->get();

    foreach ($invoices as $invoice) {
      // Correct the invoice, switch month and day
      $year = explode('-', $invoice->invoice_date)[0];
      $month = explode('-', $invoice->invoice_date)[1];
      $day = explode('-', $invoice->invoice_date)[2];

      $invoice->disbursement_date = $year . '-' . $month . '-' . $day;
      $invoice->invoice_date = $year . '-' . $month . '-' . $day;
      $invoice->save();
    }

    $payment_requests = PaymentRequest::whereDate('payment_request_date', '>', now()->format('Y-m-d'))->where('status', 'paid')->get();
    foreach ($payment_requests as $payment_request) {
      // Correct the payment request, switch month and day
      $year = explode('-', $payment_request->invoice->invoice_date)[0];
      $month = explode('-', $payment_request->invoice->invoice_date)[1];
      $day = explode('-', $payment_request->invoice->invoice_date)[2];
      $payment_request->payment_request_date = $year . '-' . $month . '-' . $day;
      $payment_request->save();
    }
  }
}
