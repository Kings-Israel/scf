<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Invoice;
use App\Notifications\InvoiceUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InvoiceUpdateNotification implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public Company $company, public array $updated_invoices)
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
    $invoices = Invoice::whereIn('id', $this->updated_invoices)
      ->get()
      ->groupBy('company_id');

    foreach ($invoices as $key => $invoice) {
      $company = Company::find($key);
      foreach ($company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'ConsolidateInvoiceApproval', [
          'company_id' => $this->company->id,
          'invoices' => collect($invoice)
            ->pluck('id')
            ->toArray(),
        ]);
      }
    }
  }
}
