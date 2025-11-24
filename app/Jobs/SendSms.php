<?php

namespace App\Jobs;

use App\Models\BankUser;
use App\Models\CompanyUser;
use App\Models\ProposedConfigurationChange;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSms implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public string $to, public string $text)
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
    if (config('app.env') == 'local' || config('app.env') == 'uat') {
      return true;
    }

    // Check if user is active
    $user = User::where('phone_number', $this->to)
      ->where('is_active', true)
      ->first();

    if (!$user) {
      return true;
    } else {
      // Bank User
      $bank_user = BankUser::where('user_id', $user->id)->first();
      // Company User
      $company_user = CompanyUser::where('user_id', $user->id)->first();
      // Don't send mail if user is inactive
      if ($bank_user && (!$bank_user->active || $user->receive_notifications == 'email')) {
        return;
      }

      if ($company_user && (!$company_user->active || $user->receive_notifications == 'email')) {
        return;
      }
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => config('services.infobip.base_url'),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>
        '{"messages":[{"destinations":[{"to":"' . $this->to . '"}],"from":"YoFinvoice","text":"' . $this->text . '"}]}',
      CURLOPT_HTTPHEADER => [
        'Authorization: App ' . config('services.infobip.api-Key'),
        'Content-Type: application/json',
        'Accept: application/json',
      ],
    ]);

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
  }
}
