<?php

namespace App\Notifications;

use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgramReview extends Notification
{
  use Queueable;

  /**
   * Create a new notification instance.
   *
   * @return void
   */
  public function __construct(public int $vendor_configuration)
  {
  }

  /**
   * Get the notification's delivery channels.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function via($notifiable)
  {
    return ['database'];
  }

  /**
   * Get the mail representation of the notification.
   *
   * @param  mixed  $notifiable
   * @return \Illuminate\Notifications\Messages\MailMessage
   */
  public function toMail($notifiable)
  {
    return (new MailMessage())
      ->line('The introduction to the notification.')
      ->action('Notification Action', url('/'))
      ->line('Thank you for using our application!');
  }

  /**
   * Get the array representation of the notification.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function toArray($notifiable)
  {
    $program_vendor_configuration = ProgramVendorConfiguration::with('buyer', 'company')->find(
      $this->vendor_configuration
    );

    if ($program_vendor_configuration->program->programType->name == 'Dealer Financing') {
      $company = $program_vendor_configuration->company->name;
    } else {
      if ($program_vendor_configuration->program->programCode->name == 'Vendor Financing Receivable') {
        $company = $program_vendor_configuration->company->name;
      } else {
        $company = $program_vendor_configuration->buyer->name;
      }
    }

    return [
      'notification' => 'Prorgam Mapping for vendor, ' . $company . ', is due for review.',
      'vendor_configuration' => $program_vendor_configuration,
    ];
  }
}
