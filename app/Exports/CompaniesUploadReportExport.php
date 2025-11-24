<?php

namespace App\Exports;

use App\Models\Bank;
use App\Models\CompanyUploadReport;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CompaniesUploadReportExport implements FromView, ShouldAutoSize
{
  public function __construct(public Bank $bank, public string $search)
  {
  }

  /**
   * @return \Illuminate\Support\View
   */
  public function view(): View
  {
    $latest_batch_id = CompanyUploadReport::where('bank_id', $this->bank->id)
      ->select('batch_id')
      ->latest()
      ->first();

    $errors = CompanyUploadReport::where('status', 'failed')
      ->where('bank_id', $this->bank->id)
      ->where('batch_id', $latest_batch_id->batch_id)
      ->get()
      ->groupBy('name');

    foreach ($errors as $error) {
      $error_details = '';
      foreach ($error as $details) {
        $error_details .= $details->description . ', ';
      }
      $error->details = $error_details;
    }

    return view('content.bank.exports.companies-error-report', [
      'errors' => $errors,
    ]);
  }
}
