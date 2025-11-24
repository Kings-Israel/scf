<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoanClosingNotification implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public array $invoices)
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
    $invoices = collect($this->invoices)->unique();

    $all_invoices = Invoice::whereIn('id', $invoices)->get();

    $anchor_invoices = $all_invoices->where('buyer_id', null)->groupBy('program_id');

    foreach ($anchor_invoices as $program_id => $anchor_invoice) {
      info($program_id);
      $program = Program::find($program_id);

      $anchor_users = $program->anchor->users;
      foreach ($anchor_users as $user) {
        SendMail::dispatch($user->email, 'BulkLoanClosing', [
          'invoices' => collect($anchor_invoice)
            ->pluck('id')
            ->toArray(),
        ]);
      }
    }

    $buyer_invoices = $all_invoices->where('buyer_id', '!=', null)->groupBy('buyer_id');

    foreach ($buyer_invoices as $buyer_id => $buyer_invoice) {
      info($buyer_id);
      $company = Company::find($buyer_id);

      foreach ($company->users as $user) {
        SendMail::dispatch($user->email, 'BulkLoanClosing', [
          'invoices' => collect($buyer_invoice)
            ->pluck('id')
            ->toArray(),
        ]);
      }
    }

    // foreach ($invoices as $invoice_id) {
    //   $invoice = Invoice::find($invoice_id);
    //   info($invoice->invoice_number);
    //   // $invoice->notifyUsers('LoanClosing');
    //   // If vendor financing receivable, notify anchor
    //   if (
    //     $invoice->program->programType->name === Program::VENDOR_FINANCING &&
    //     $invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
    //   ) {
    //     $anchor_users = $invoice->program->anchor->users;
    //     foreach ($anchor_users as $user) {
    //       SendMail::dispatch($user->email, 'LoanClosing', ['invoice_id' => $invoice->id]);
    //     }
    //   }
    //   // If factoring, notify buyer
    //   if (
    //     $invoice->program->programType->name === Program::VENDOR_FINANCING &&
    //     ($invoice->program->programCode->name === Program::FACTORING_WITH_RECOURSE ||
    //       $invoice->program->programCode->name === Program::FACTORING_WITHOUT_RECOURSE)
    //   ) {
    //     $buyer_users = $invoice->buyer->users;
    //     foreach ($buyer_users as $user) {
    //       SendMail::dispatch($user->email, 'LoanClosing', ['invoice_id' => $invoice->id]);
    //     }
    //   }
    //   // If dealer financing, notify dealer users
    //   if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
    //     $dealer_users = $invoice->company->users;
    //     foreach ($dealer_users as $user) {
    //       SendMail::dispatch($user->email, 'LoanClosing', ['invoice_id' => $invoice->id]);
    //     }
    //   }
    //   // Notify bank RMs users
    //   $bank_users = $invoice->program->bankUserDetails;
    //   foreach ($bank_users as $user) {
    //     SendMail::dispatch($user->email, 'LoanClosing', ['invoice_id' => $invoice->id]);
    //   }
    // }
  }
}
