<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisbursementFailed extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $invoice;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(string $invoice, public string $url, public string $name, public string $type, public string $logo = '')
  {
    $this->invoice = Invoice::find($invoice);

    if ($type == 'vendor_financing') {
      $this->template = EmailTemplate::where('name', 'IF Disbursement Failed')->first();
    } else {
      $this->template = EmailTemplate::where('name', 'DF Disbursement Failed')->first();
    }
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(
      subject: $this->template ? $this->template->subject : 'Disbursement Failed',
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
    $data['anchor_name'] = $this->invoice->buyer ? $this->invoice->buyer->name : $this->invoice->program->anchor->name;
    $data['seller_name'] = $this->invoice->company->name;
    $data['distributor_name'] = $this->invoice->company->name;
    $data['loan_id'] = $this->invoice->pi_number;
    $data['pi_amount'] = $this->invoice->invoice_total_amount;
    $data['reason'] = '';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.disbursement-failed',
      with: [
        'invoice' => $this->invoice,
        'mail_text' => $mail_text,
        'logo' => $this->logo
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
