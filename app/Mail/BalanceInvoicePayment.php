<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BalanceInvoicePayment extends Mailable
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
    $this->template = EmailTemplate::where('name', 'Balance Invoice Payment for Seller')->first();
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
      $subject = 'Balance payment for Invoice ' . $this->invoice->invoice_number . ' credited';
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
    $data['{invoice_number}'] = $this->invoice->invoice_number;
    $data['{pi_number}'] = $this->invoice->pi_number;
    $data['{buyer_company}'] = $this->invoice->program->anchor->name;
    $data['{currency}'] = $this->invoice->currency;
    $data['{date}'] = now()->format('d M Y');
    $data['{ac_number}'] = $vendor_configuration->payment_account_number;
    $data['{loan_amount}'] = $this->invoice->disbursed_amount;

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.balance-invoice-payment',
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
