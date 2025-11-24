<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceFee;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInvoicesTaxes implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public ProgramVendorConfiguration $program_vendor_configuration)
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
    $invoices = Invoice::where('company_id', $this->program_vendor_configuration->company_id)
      ->whereHas('program', function ($query) {
        $query->whereHas('anchor', function ($query) {
          $query->where('companies.id', $this->program_vendor_configuration->program->anchor->id);
        })
        ->whereHas('programType', function ($query) {
          $query->where('name', $this->program_vendor_configuration->program->programType->name);
        });
      })
      ->where('status', 'submitted')
      ->get();

    foreach ($invoices as $invoice) {
      if ($this->program_vendor_configuration->withholding_tax) {
        InvoiceFee::updateOrCreate([
          'invoice_id' => $invoice->id,
          'name' => 'Withholding Tax',
        ], [
          'amount' => (float) (($this->program_vendor_configuration->withholding_tax / 100) * ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount)),
        ]);
      }

      if ($this->program_vendor_configuration->withholding_vat) {
        InvoiceFee::updateOrCreate([
          'invoice_id' => $invoice->id,
          'name' => 'Withholding VAT',
        ], [
          'amount' => (float) (($this->program_vendor_configuration->withholding_vat / 100) * ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount)),
        ]);
      }
    }
  }
}
