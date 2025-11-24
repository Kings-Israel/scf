<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\CbsTransaction;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class CbsTransactionForOdCreditFailed extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public CbsTransaction $cbs_transaction, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'CBS Transaction for OD credit failed')->first();
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
      $subject = 'DF: CBS Transaction for OD credit failed';
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
    $vendor_configuration = $this->cbs_transaction->payment_request_id->invoice->program->vendorConfigurations->where('company_id', $this->cbs_transaction->paymentRequest->invoice->company_id)->first();

    $data = array();
    $data['invoice_number'] = $this->cbs_transaction->paymentRequest->invoice->invoice_number;
    $data['cbs_id'] = $this->cbs_transaction->id;
    $data['anchor_name'] = $this->cbs_transaction->paymentRequest->invoice->program->anchor->name;
    $data['distributor_name'] = $this->cbs_transaction->paymentRequest->invoice->company->name;
    $data['currency'] = $this->cbs_transaction->paymentRequest->invoice->currency;
    $data['amount'] = $this->cbs_transaction->paymentRequest->invoice->invoice_total_amount;
    $data['od_ac'] = $vendor_configuration->payment_account_number;
    $data['reason'] = ''; // TODO Add Failed Reason to cbs transactions

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.cbs-transaction-for-od-credit-failed',
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
