<?php

namespace App\Jobs;

use App\Models\CronLog;
use App\Models\PaymentRequest;
use App\Models\ProgramVendorConfiguration;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckInvoiceStalePeriod implements ShouldQueue
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
        // Get invoices with pending payment requests
        $payment_requests = PaymentRequest::where('status', 'created')
        ->whereHas('invoice', function ($q) {
          $q->whereIn('financing_status', ['pending', 'submitted'])
            ->whereHas('program', function ($q) {
            $q->where('stale_invoice_period', '>', 0);
          });
        })
        ->get();

        foreach ($payment_requests as $payment_request) {
          // Stale Invoice Period
          $stale_period = $payment_request->invoice->program->stale_invoice_period;

          if ($stale_period && $stale_period > 0) {
            if ($payment_request->created_at->diffInDays(now()) > $stale_period) {
              $payment_request->update([
                'status' => 'rejected',
                'approval_status' => 'rejected',
                'rejected_reason' => 'Payment Request Surpassed days for approval (Stale invoice period of ' . $stale_period . ' days as set in program)'
              ]);

              $payment_request->invoice->update([
                'financing_status' => 'denied',
                'rejected_reason' => 'Payment Request Surpassed days for approval (Stale invoice period of ' . $stale_period . ' days as set in program)'
              ]);

              $amount = ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount;

              $payment_request->invoice->program->decrement('pipeline_amount', $amount);
              $payment_request->invoice->company->decrement('pipeline_amount', $amount);

              // Replenish the amounts into available limit
              $program_vendor_configuration = ProgramVendorConfiguration::when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                $query->where('buyer_id', $payment_request->invoice->buyer_id);
              })
              ->where('company_id', $payment_request->invoice->company_id)
              ->where('program_id', $payment_request->invoice->program_id)
              ->first();

              $program_vendor_configuration->decrement('pipeline_amount', $amount);
            }

            CronLog::create([
              'bank_id' => $payment_request->invoice->company->bank_id,
              'name' => 'Auto Loan Reject Cron',
              'start_time' => now(),
              'end_time' => now(),
              'status' => 'completed',
            ]);
          }
        }
    }
}
