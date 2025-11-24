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

class InterestPosted extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $invoice;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(string $invoice, public int $principle_amount, public int $interest_amount, public string $type, public string $logo = '')
  {
    $this->invoice = Invoice::find($invoice);

    $this->template = EmailTemplate::where('name', 'IF Interest Posting')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(
      subject: $this->template ? $this->template->subject : 'Interest Posted for Payment on Invoice ' . $this->invoice->invoice_number,
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
    $data['symbol'] = $this->invoice->program->bank->default_currency;
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['loan_amount'] = $this->principle_amount;
    $data['amount'] = $this->interest_amount;
    $data['date'] = now()->format('d M Y');
    $data['due_date'] = now()->format('d M Y');

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.interest-posted',
      with: [
        'invoice' => $this->invoice,
        'data' => collect($data),
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
