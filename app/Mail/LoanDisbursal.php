<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\EmailTemplate;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoanDisbursal extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Invoice $invoice, public string $logo = '')
  {
    $this->template = EmailTemplate::where('name', 'Payment Disbursal Mail')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = '';
    $data = [];
    $data['invoice_number'] = $this->invoice->invoice_number;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Payment Disbursal';
    }

    return new Envelope(subject: $subject);
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $vendor_configuration = null;
    if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $this->invoice->program_id)
        ->where('company_id', $this->invoice->company->id)
        ->first();
    } else {
      if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration = ProgramVendorConfiguration::where('program_id', $this->invoice->program_id)
          ->where('company_id', $this->invoice->company->id)
          ->first();
      } else {
        $vendor_configuration = ProgramVendorConfiguration::where('program_id', $this->invoice->program_id)
          ->where('buyer_id', $this->invoice->buyer->id)
          ->first();
      }
    }

    $account_number = $this->invoice->paymentRequests
      ->first()
      ->paymentAccounts->where('type', 'vendor_account')
      ->first();

    $data = [];
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['seller_name'] = $this->invoice->company->name;
    $data['ac_number'] = $account_number ? $account_number->account : $vendor_configuration->payment_account_number;
    $data['currency'] = $this->invoice->currency;
    $data['loan_amount'] = number_format($this->invoice->disbursed_amount, 2);
    $data['vendor'] = $vendor_configuration->company->name;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.loan-disbursal',
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
