<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckEligibleForFinancing implements ShouldQueue
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
    $invoices = Invoice::whereDoesntHave('paymentRequests')
        ->where('eligible_for_financing', true)
        ->get();

    foreach ($invoices as $invoice) {
      if ($invoice->program->programType->name === Program::VENDOR_FINANCING) {
        if ((int) $invoice->program->min_financing_days > 0 && Carbon::parse($invoice->due_date)->subDays((int) $invoice->program->min_financing_days)->lessThan(now())) {
          $invoice->update([
            'eligible_for_financing' => false,
          ]);
        }
      } else {
        if (Carbon::parse($invoice->due_date)->lessThan(now())) {
          $invoice->update([
            'eligible_for_financing' => false,
          ]);
        }
      }
    }
  }
}
