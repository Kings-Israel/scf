<?php

namespace App\Jobs;

use App\Models\Bank;
use App\Models\CronLog;
use App\Models\Program;
use Illuminate\Bus\Queueable;
use App\Notifications\ProgramExpiry;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\ProgramLimitExpiry;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class CheckProgramExpiry implements ShouldQueue
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
    $programs = Program::whereDate('limit_expiry_date', '<=', now()->format('Y-m-d'))->get();

    foreach ($programs as $program) {
      $program->update([
        'account_status' => 'suspended',
      ]);

      $program->bank->notify(new ProgramExpiry($program));

      CronLog::create([
        'bank_id' => $program->bank_id,
        'name' => 'Program Expiry Notification',
        'start_time' => now(),
        'status' => 'completed',
        'end_time' => now(),
      ]);
    }

    $mappings = ProgramVendorConfiguration::whereDate('limit_expiry_date', '<=', now()->format('Y-m-d'))->get();

    foreach ($mappings as $mapping) {
      $mapping->update([
        'status' => 'inactive',
      ]);

      $mapping->company->notify(new ProgramLimitExpiry($mapping->program));

      CronLog::create([
        'bank_id' => $mapping->program->bank_id,
        'name' => 'Program Limit Expiry Notification',
        'start_time' => now(),
        'status' => 'completed',
        'end_time' => now(),
      ]);
    }
  }
}
