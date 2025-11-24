<?php

namespace App\Jobs;

use App\Models\CbsTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCbsTransactions implements ShouldQueue
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
        $cbs_transactions = CbsTransaction::all();

        foreach ($cbs_transactions as $cbs_transaction) {
          $latest_id = CbsTransaction::where('bank_id', $cbs_transaction->bank_id)
          ->where('cbs_id', '!=', NULL)
          ->latest('cbs_id')
          ->first();

          if (!$cbs_transaction->cbs_id) {
            $cbs_transaction->update([
              'cbs_id' => $latest_id ? $latest_id->cbs_id + 1 : 1
            ]);
          }
        }
    }
}
