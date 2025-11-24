<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoiceRejection extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Invoice $invoice, public string $type)
  {
    $this->template = EmailTemplate::where('name', 'Invoice Rejection')->first();
    $this->logo = $invoice->program->bank->adminConfiguration()->exists() ? $invoice->program->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
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
    $data['buyer_company'] = $this->invoice->program->anchor->name;
    $data['invoice_number'] = $this->invoice->invoice_number;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Invoice Rejected by ' . $this->invoice->program->anchor->name;
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
    $data['buyer_company'] = $this->invoice->program->anchor->name;
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['rejection_reason'] = $this->invoice->rejected_reason;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? ': ' . number_format($val) : ': ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.invoice-rejection',
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
