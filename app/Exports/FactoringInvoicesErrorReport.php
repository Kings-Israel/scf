<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\InvoiceUploadReport;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FactoringInvoicesErrorReport implements FromView, ShouldAutoSize
{
  public function __construct(public Company $company) {}
  /**
   * @return \Illuminate\Support\View
   */
  public function view(): View
  {
    $latest_batch_id = InvoiceUploadReport::where('company_id', $this->company->id)->select('batch_id')->latest()->first();

    $errors = InvoiceUploadReport::where('status', 'Failed')->where('company_id', $this->company->id)->where('batch_id', $latest_batch_id->batch_id)->get()->groupBy('invoice_number');

    foreach ($errors as $error) {
      $error_details = '';
      foreach ($error as $details) {
        $error_details .= $details->description . ', ';
      }
      $error->details = $error_details;
    }

    return view('content.anchor.factoring.exports.invoices-errors', [
      'errors' => $errors,
    ]);
  }
}
