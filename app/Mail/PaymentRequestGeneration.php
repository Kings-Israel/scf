<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRequestGeneration extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public PaymentRequest $payment_request, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Payment Request Generation')->first();
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
    $data['payment_request_id'] = $this->payment_request->reference_number;
    $data['pay_date'] = now()->format('d M Y');

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Payment Request generated for ' . $this->payment_request->reference_number;
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
    $vendor_configuration = $this->payment_request->invoice->program->vendorConfigurations->where('company_id', $this->payment_request->invoice->company_id)->first();

    $data = array();
    $data['{invoice_number}'] = $this->payment_request->invoice->invoice_number;
    $data['{seller_name}'] = $this->payment_request->invoice->company->name;
    $data['{payment_request_id}'] = $this->payment_request->reference_number;
    $data['{buyer_company}'] = $this->payment_request->invoice->program->anchor->name;
    $data['{debit_from}'] = $vendor_configuration->payment_account_number;
    $data['{currency}'] = $this->payment_request->invoice->currency;
    $data['{pr_amount}'] = $this->payment_request->amount;
    $data['{pi_amount}'] = $this->payment_request->invoice->eligible_for_finance;
    $data['{pi_number}'] = $this->payment_request->invoice->pi_number;
    $data['{pay_date}'] = now()->format('d M Y');
    $data['{table}'] = '';

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? ' ' . number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.payment-request-generation',
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
