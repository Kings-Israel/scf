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

class TenorEndPostingForDrawdown extends Mailable
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
    $this->template = EmailTemplate::where('name', 'Tenor End Posting Amount for Drawdown')->first();
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
      $subject = 'Tenor End Posting Amount For Drawdown ' . $this->invoice->invoice_number;
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
    $vendor_discount = $this->invoice->program->vendorDiscountDetails->where('company_id', $this->invoice->company_id)->first();

    $data = array();
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['drawdown_amount'] = $this->invoice->drawdown_amount;
    $data['drawdown_balance'] = $this->invoice->balance;
    $data['interest_balance'] = ''; // TODO Check on interest balance
    $data['due_date'] = $this->invoice->due_date;
    $data['symbol'] = $this->invoice->currency;
    $data['od_account'] = $vendor_configuration->payment_account_number;
    $data['penal_interest_on_principle'] = $vendor_discount->penal_interest_on_principle;
    $data['penal_interest_on_interest'] = '';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }
    return new Content(
      markdown: 'content.bank.email.tenor-end-posting-for-drawdown',
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
