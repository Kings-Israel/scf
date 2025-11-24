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

class DfInterestPosting extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Invoice $invoice, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'DF Interest Posting')->first();
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
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Interst Posting on transaction for invoice ' . $this->invoice->invoice_number;
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
    $vendor_configuration = $this->invoice->program->vendorConfigurations->where('company_id', $this->invoice->company_id)->first();

    $data = array();
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['symbol'] = $this->invoice->currency;
    $data['drawdown_amount'] = $this->invoice->disbursed_amount;
    $data['drawdown_balance'] = $this->invoice->balance;
    $data['od_account'] = $vendor_configuration->payment_account_number;
    $data['interest_posted'] = $this->invoice->overdue_amount;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? ' ' . number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.df-interest-posting',
      with: [
        'invoice' => $this->invoice,
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
