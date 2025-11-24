<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDrafts implements ShouldQueue
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
    Company::where('is_published', false)
      ->whereDate('created_at', '<=', now()->subDays(5))
      ->onlyDrafts()
      ->delete();
    Program::where('is_published', false)
      ->whereDate('created_at', '<=', now()->subDays(5))
      ->onlyDrafts()
      ->delete();
  }
}
