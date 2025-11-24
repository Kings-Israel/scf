<?php

namespace App\Jobs;

use App\Models\CronLog;
use App\Models\ProgramVendorConfiguration;
use App\Notifications\ProgramReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckProgramReview implements ShouldQueue
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
    // $programs = Program::whereDate('limit_review_date', '<=', now()->format('Y-m-d'))->get();

    // foreach ($programs as $program) {
    //   $program->bank->notify(new ProgramReview($program));

    //   CronLog::create([
    //     'bank_id' => $program->bank_id,
    //     'name' => 'Program Review Notification',
    //     'start_time' => now(),
    //     'status' => 'completed',
    //     'end_time' => now(),
    //   ]);
    // }

    $mappings = ProgramVendorConfiguration::whereDate('limit_review_date', '=', now()->format('Y-m-d'))->get();

    foreach ($mappings as $mapping) {
      $mapping->company->notify(new ProgramReview($mapping->program, $mapping));

      CronLog::create([
        'bank_id' => $mapping->program->bank_id,
        'name' => 'Program Limit Review Notification',
        'start_time' => now(),
        'status' => 'completed',
        'end_time' => now(),
      ]);
    }
  }
}
