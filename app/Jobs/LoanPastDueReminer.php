<?php

namespace App\Jobs;

use App\Models\BankGeneralProductConfiguration;
use App\Models\CronLog;
use App\Models\Invoice;
use App\Models\Program;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class LoanPastDueReminer implements ShouldQueue
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
    $invoices = Invoice::whereHas('paymentRequests', function ($query) {
      $query->whereHas('cbsTransactions', function ($query) {
        $query->where('transaction_type', 'Payment Disbursement');
      });
    })
      ->whereHas('program', function ($query) {
        $query->whereHas('bank', function ($query) {
          $query->where('status', 'active');
        });
      })
      ->where('financing_status', 'financed')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->get();

    // Send mail reminder
    foreach ($invoices as $invoice) {
      // Check if setting to remind daily is set
      $config = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
        ->where('name', 'send daily payment reminder for overdue loans')
        ->where('product_type_id', $invoice->program->program_type_id)
        ->first();

      if ($config->value == 'yes') {
        $loan_overdue_cron = CronLog::create([
          'bank_id' => $invoice->program->bank_id,
          'name' => 'Loan Overdue Reminder',
          'start_time' => now(),
          'status' => 'in progress',
        ]);

        if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
          if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            foreach ($invoice->program->anchor->users as $user) {
              SendMail::dispatch($user->email, 'LoanOverdue', ['invoice_id' => $invoice->id]);
            }
          } else {
            foreach ($invoice->buyer->users as $user) {
              SendMail::dispatch($user->email, 'LoanOverdue', ['invoice_id' => $invoice->id]);
            }
          }
        } else {
          foreach ($invoice->company->users as $user) {
            SendMail::dispatch($user->email, 'DrawdownOverdueBalance', ['invoice_id' => $invoice->id]);
          }

          // Send Stop Supply Mail to anchor if days past due is past stop supply days
          if ($invoice->days_past_due >= $invoice->program->stop_supply) {
            foreach ($invoice->program->anchor->users as $user) {
              SendMail::dispatch($user->email, 'StopSupply', [
                'company_id' => $invoice->program->anchor->id,
                'dealer_id' => $invoice->company->id,
                'stop_supply_days' => $invoice->program->stop_supply,
              ]);
            }
          }

          if ($invoice->days_past_due >= $invoice->program->fldg_days) {
            foreach ($invoice->program->anchor->users as $user) {
              SendMail::dispatch($user->email, 'FLDG', [
                'company_id' => $invoice->program->anchor->id,
                'dealers' => [$invoice->company->id],
              ]);
            }
          }
        }

        $loan_overdue_cron->update([
          'end_time' => now(),
          'status' => 'completed',
        ]);
      }
    }
  }
}
