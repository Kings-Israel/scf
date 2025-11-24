<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\CronLog;
use App\Models\Invoice;
use App\Models\BankUser;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\BankProductsConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\BankGeneralProductConfiguration;
use App\Models\Program;

class LoanReminder implements ShouldQueue
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
    // If Program Type is Vendor Financing Receivable, send to anchor users
    // If Program Type is Dealer Financing, send to dealer users
    // If Program Type is Factoring With Recourse or Without Recourse, send to buyer users
    $programs = Program::whereHas('invoices', function ($query) {
      $query->where('financing_status', 'financed')->whereDate('due_date', now()->format('Y-m-d'));
    })->get();

    foreach ($programs as $program) {
      $invoices = Invoice::where('program_id', $program->id)
        ->where('financing_status', 'financed')
        ->whereDate('due_date', now()->format('Y-m-d'))
        ->get()
        ->pluck('id')
        ->toArray();

      if (count($invoices) > 0) {
        if ($program->programType->name === Program::VENDOR_FINANCING) {
          if ($program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
            // Send to anchor users
            foreach ($program->anchor->users as $user) {
              SendMail::dispatch($user->email, 'BulkPaymentReminder', [
                'invoices' => $invoices,
                'due_date' => now()->format('Y-m-d'),
              ]);
            }
          } else {
            // Send to buyer users
            foreach ($program->anchor->users as $user) {
              SendMail::dispatch($user->email, 'BulkPaymentReminder', [
                'invoices' => $invoices,
                'due_date' => now()->format('Y-m-d'),
              ]);
            }
          }
        } elseif ($program->programType->name == Program::DEALER_FINANCING) {
          // Send to dealer users
          foreach ($program->getDealers() as $dealers) {
            foreach ($dealers->users as $user) {
              SendMail::dispatch($user->email, 'BulkPaymentReminder', [
                'invoices' => $invoices,
                'due_date' => now()->format('Y-m-d'),
              ]);
            }
          }
        }
      }
    }
  }
}
