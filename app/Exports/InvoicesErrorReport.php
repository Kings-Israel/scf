<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\ImportError;
use App\Models\InvoiceUploadReport;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InvoicesErrorReport implements FromView, ShouldAutoSize, WithColumnFormatting
{
  public function __construct(public Company $company, public string $type, public string $company_type) {}

  public function columnFormats(): array
  {
    if ($this->company_type == 'anchor') {
      return [
        'C' => NumberFormat::FORMAT_NUMBER_00,
        'J' => NumberFormat::FORMAT_NUMBER_00,
        'K' => NumberFormat::FORMAT_NUMBER_00,
        'L' => NumberFormat::FORMAT_NUMBER_00,
      ];
    } else {
      return [
        'C' => NumberFormat::FORMAT_NUMBER_00,
      ];
    }
  }

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

    return view('content.anchor.reverse-factoring.exports.invoices-errors', [
      'errors' => $errors,
      'type' => $this->type,
      'company_type' => $this->company_type,
    ]);
  }
}
