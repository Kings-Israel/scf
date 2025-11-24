<?php

namespace App\Mail;

use App\Models\Program;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductConfigurationApproval extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public string $user_name, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Product Configuration Approval')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = '';

    if ($this->template) {
      $subject = $this->template->subject;
    } else {
      $subject = 'Change in Product Configuration';
    }

    return new Envelope(
      subject: $subject,
    );
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $data = array();
    $data['{user_name}'] = $this->user_name;

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.program-configuration-approval',
      with: [
        'template' => $this->template,
        'mail_text' => $mail_text,
        'logo' => $this->logo,
      ]
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array
   */
  public function attachments()
  {
    return [];
  }
}
