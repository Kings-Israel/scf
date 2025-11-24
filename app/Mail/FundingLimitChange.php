<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class FundingLimitChange extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Company $company, public float $old_value, public float $new_value, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Funding Limit Change')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = '';
    $data = array();
    $data['{company_name}'] = $this->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace($key, $val, $subject);
      }
    } else {
      $subject = 'Funding Limit Changed';
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
    $data['msg'] = 'Funding Limit was changed for the company ' . $this->company->name . ' from ' . $this->company->bank->default_currency . ' ' . number_format($this->old_value) . ' to ' . $this->company->bank->default_currency . ' ' . number_format($this->new_value);

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.funding-limit-change',
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
