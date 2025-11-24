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

class LoanDisbursalNotification implements ShouldQueue
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

    $vendor_invoices = $all_invoices->groupBy('company_id');

    foreach ($vendor_invoices as $company_id => $vendor_invoice) {
      info($company_id);
      $company = Company::find($company_id);

      foreach ($company->users as $user) {
        SendMail::dispatch($user->email, 'BulkLoanDisbursal', [
          'invoices' => collect($vendor_invoice)
            ->pluck('id')
            ->toArray(),
        ]);
      }
    }

    $anchor_invoices = $all_invoices->groupBy('program_id');

    foreach ($anchor_invoices as $program_id => $anchor_invoice) {
      info($program_id);
      $program = Program::find($program_id);

      if ($program->programType->name === Program::VENDOR_FINANCING) {
        if ($program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
          $users = $program->anchor->users;

          foreach ($users as $user) {
            SendMail::dispatch($user->email, 'BulkInvoicePaymentReceivedBySeller', [
              'invoices' => collect($anchor_invoice)
                ->pluck('id')
                ->toArray(),
            ]);
          }
        }
      } else {
        $users = $program->anchor->users;

        foreach ($users as $user) {
          SendMail::dispatch($user->email, 'BulkInvoicePaymentReceivedBySeller', [
            'invoices' => collect($anchor_invoice)
              ->pluck('id')
              ->toArray(),
          ]);
        }
      }
    }

    $buyer_invoices = $all_invoices->where('buyer_id', '!=', null)->groupBy('buyer_id');

    foreach ($buyer_invoices as $company_id => $buyer_invoice) {
      info($company_id);
      $company = Company::find($company_id);

      foreach ($company->users as $user) {
        SendMail::dispatch($user->email, 'BulkInvoicePaymentReceivedBySeller', [
          'invoices' => collect($buyer_invoice)
            ->pluck('id')
            ->toArray(),
        ]);
      }
    }
  }
}
