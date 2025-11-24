<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordExpired extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public User $user, public string $link, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Password will expire today')->first();
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
      $subject = 'Password will expire soon';
    }

    return new Envelope(subject: $subject);
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $data = [];
    $data['{user_name}'] = $this->user->name;
    $data['Reset password link - {url}'] =
      '
      <a href="' .
      $this->link .
      '">
      Set your Password
      </a>';

    $mail_text = $this->template ? $this->template->body : null;

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        $key,
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : $val,
        $mail_text
      );
    }
    return new Content(
      markdown: 'content.bank.email.password-expired',
      with: [
        'link' => $this->link,
        'mail_text' => $mail_text,
        'template' => $this->template,
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
