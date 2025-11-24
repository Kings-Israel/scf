<?php

namespace App\Exports;

use App\Models\ImportError;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PaymentRequestsErrorReport implements FromView, ShouldAutoSize
{
  public function view(): View
  {
    $errors = ImportError::where('user_id', auth()->id())->where('module', 'PaymentRequests')->get()->groupBy('row');

    foreach ($errors as $error) {
      $error_details = '';
      foreach ($error as $details) {
        foreach ($details->errors as $detail) {
          $error_details .= ', ' . $detail;
        }
        $details->delete();
      }
      $error->details = $error_details;
    }

    return view('content.bank.exports.payment-requests-error-report', [
      'errors' => $errors
    ]);
  }
}
