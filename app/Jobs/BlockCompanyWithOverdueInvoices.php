<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\CronLog;
use App\Models\Invoice;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BlockCompanyWithOverdueInvoices implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct()
  {
    $invoices = Invoice::dealerFinancing()
      ->where('financing_status', 'financed')
      ->whereDate('due_date', '<', now())
      ->whereHas('company', function ($query) {
        $query->where('is_blocked', false);
      })
      ->get();

    foreach ($invoices as $invoice) {
      $limit_block_overdue_days = ProgramVendorDiscount::where('company_id', $invoice->company_id)
        ->where('program_id', $invoice->program_id)
        ->select('limit_block_overdue_days')
        ->first()->limit_block_overdue_days;

      if (Carbon::parse($invoice->due_date)->diffInDays(now()) > $limit_block_overdue_days) {
        $invoice->company->update(['is_blocked' => true]);

        CronLog::create([
          'bank_id' => $invoice->company->bank_id,
          'name' => 'DF: Block Company as per "Limit Overdue Date"',
          'start_time' => now(),
          'end_time' => now(),
          'status' => 'completed',
        ]);
      }
    }
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    //
  }
}
