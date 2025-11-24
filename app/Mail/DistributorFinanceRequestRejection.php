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

class DistributorFinanceRequestRejection extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public PaymentRequest $payment_request, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Distributor Finance Request Rejection')->first();
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
    $data['invoice_number'] = $this->payment_request->invoice->invoice_number;
    $data['bank_name'] = $this->payment_request->invoice->program->bank->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Finance Request on ' . $this->payment_request->invoice->invoice_number . ' rejected';
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
    $data['{reject_remark}'] = $this->payment_request->rejected_reason;

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.distributor-finance-request-rejection',
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
