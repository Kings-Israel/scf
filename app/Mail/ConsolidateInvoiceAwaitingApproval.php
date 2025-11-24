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

class ConsolidateInvoiceAwaitingApproval extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Company $company, public array $invoices)
  {
    $this->template = EmailTemplate::where('name', 'Consolidate Invoice Awaiting Approval')->first();
    $this->logo = $company->bank->adminConfiguration()->exists() ? $company->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
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
      $subject = 'Invoice(s) Awaiting Approval';
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
    $data['table'] = '';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.consolidate-invoice-awaiting-approval',
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
