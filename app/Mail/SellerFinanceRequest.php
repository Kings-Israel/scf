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

class SellerFinanceRequest extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   */
  public function __construct(public Company $company, public array $finance_request)
  {
    $this->template = EmailTemplate::where('name', 'Seller Finance Request')->first();
    $this->logo = $company->bank->adminConfiguration()->exists() ? $company->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    $subject = '';
    $data = array();
    $data['seller_company'] = $this->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'New Finance Request(s) created by ' . $this->company->name;
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
    $data['seller_company'] = $this->company->name;
    $data['table'] = '';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? ' ' . number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.seller-finance-request',
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
