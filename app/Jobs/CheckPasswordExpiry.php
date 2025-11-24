<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CheckPasswordExpiry implements ShouldQueue
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
      $users = User::whereDate('password_expiry', now()->addWeek())->get();

      foreach ($users as $user) {
        $url = URL::temporarySignedRoute(
            'auth.reset-password', now()->addHours(24), ['id' => $user->id]
        );

        SendMail::dispatch($user->email, 'PasswordExpiresSoon', ['user' => $user->id, 'link' => $url]);
      }

      $users = User::whereDate('password_expiry', now())->get();

      foreach ($users as $user) {
        $url = URL::temporarySignedRoute(
            'auth.reset-password', now()->addHours(24), ['id' => $user->id]
        );

        $user->update([
          'password' => bcrypt(mt_rand(20000, 999999))
        ]);

        SendMail::dispatch($user->email, 'PasswordExpired', ['user' => $user->id, 'link' => $url]);
      }
    }
}
