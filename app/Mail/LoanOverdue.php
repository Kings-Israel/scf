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

class LoanOverdue extends Mailable
{
  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Invoice $invoice, public string $logo = '')
  {
    $this->template = EmailTemplate::where('name', 'Loan Overdue')->first();
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
    $data['invoice_number'] = $this->invoice->invoice_number;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{'.$key.'}', $val, $subject);
      }
    } else {
      $subject = 'Loan Closing';
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
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['symbol'] = $this->invoice->currency;
    $data['loan_amount'] = $this->invoice->total + $this->invoice->total_invoice_taxes - $this->invoice->total_invoice_fees - $this->invoice->total_invoice_discount;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{'.$key.'}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.loan-overdue',
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
