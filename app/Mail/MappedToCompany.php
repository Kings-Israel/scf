<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MappedToCompany extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public array $content = array(), public string $logo = '')
  {
    // Get email templates
    $this->template = EmailTemplate::where('name', 'New User')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(
      subject: $this->template ? $this->template->subject : 'Welcome To YofInvoice',
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
    $link = '';
    if (is_array($this->content['data']['links'])) {
      if(count($this->content['data']['links']) > 0) {
        $link = collect($this->content['data']['links'])->first();
      } else {
        $link = $this->content['data']['links'];
      }
    }

    $data['url'] = $link;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', $val, $mail_text);
    }

    $mail_text = str_replace('Set New Password', '<a href="' . $link . '">Set New Password</a>', $mail_text);

    return new Content(
      markdown: 'content.bank.email.mapped-to-company',
      with: [
        'content' => $this->content,
        'logo' => $this->logo,
        'template' => $this->template,
        'mail_text' => $mail_text,
        'link' => $link,
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
