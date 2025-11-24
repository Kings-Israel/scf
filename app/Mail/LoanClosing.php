<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoanClosing extends Mailable
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
    $this->template = EmailTemplate::where('name', 'Payment Closing Mail')
      ->where('status', 'active')
      ->first();
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
    $data['seller_name'] = $this->invoice->buyer_id
      ? $this->invoice->program->anchor->name
      : $this->invoice->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Payment for Seller Closing Mail';
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
    $data = [];
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['seller_name'] = $this->invoice->buyer_id
      ? $this->invoice->program->anchor->name
      : $this->invoice->company->name;
    $data['ac_number'] = $this->invoice->pi_number;
    $data['currency'] = $this->invoice->currency;
    $data['invoice_due_date'] = Carbon::parse($this->invoice->due_date)->format('d M Y');
    $data['loan_date'] = Carbon::parse($this->invoice->disbursement_date)->format('d M Y');
    $data['loan_closure_date'] = now()->format('d M Y');
    if ($this->invoice->program->programType->name === Program::VENDOR_FINANCING) {
      $data['loan_amount'] = ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount;
    } else {
      $data['loan_amount'] = $this->invoice->drawdown_amount;
    }

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        '{' . $key . '}',
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : $val,
        $mail_text
      );
    }

    return new Content(
      markdown: 'content.bank.email.loan-closing',
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
