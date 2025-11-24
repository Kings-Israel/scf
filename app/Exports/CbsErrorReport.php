<?php

namespace App\Exports;

use App\Models\ImportError;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CbsErrorReport implements FromView, ShouldAutoSize
{
  /**
  * @return \Illuminate\Support\Collection
  */
  public function view(): View
  {
    // Get the latest batch
    $latest_batch_id = ImportError::where('user_id', auth()->id())->where('module', 'CbsTransaction')->latest()->first()?->batch_id;

    $errors = ImportError::where('user_id', auth()->id())->where('module', 'CbsTransaction')->where('batch_id', $latest_batch_id)->get();

    return view('content.bank.exports.cbs-error-report', [
      'errors' => $errors
    ]);
  }
}
