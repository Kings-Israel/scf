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

class CbsTransactionForLoanSettlementFailed extends Mailable
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
    $this->template = EmailTemplate::where('name', 'CBS Transaction for Loan settlement failed')->first();
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
      $subject = 'IF: CBS Transaction For Loan Settlement Failed';
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
    $data['invoice_number'] = $this->cbs_transaction->paymentRequest->invoice->invoice_number;
    $data['loan_id'] = $this->cbs_transaction->paymentRequest->reference_number;
    $data['anchor_name'] = $this->cbs_transaction->paymentRequest->invoice->program->anchor->name;
    $data['seller_name'] = $this->cbs_transaction->paymentRequest->invoice->company->name;
    $data['currency'] = $this->cbs_transaction->paymentRequest->invoice->currency;
    $data['pi_amount'] = $this->cbs_transaction->paymentRequest->invoice->invoice_total_amount;
    $data['reason'] = ''; // TODO Add Failed Reason to cbs transactions

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.cbs-transaction-for-loan-settlement-failed',
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
