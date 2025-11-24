<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class IfPaymentReminder extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Invoice $invoice, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'IF Payment Reminder')->first();
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
      $subject = 'Payment Reminder for Invoice: ' . $this->invoice->invoice_number;
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
    $data['{pi_amount}'] = $this->invoice->paymentRequests->first()->amount;
    $data['{currency}'] = $this->invoice->currency;
    $data['{amount}'] = $this->invoice->invoice_total_amount;
    $data['{due_date}'] = Carbon::parse($this->invoice->due_date)->format('d M Y');
    $data['{po_number}'] = $this->invoice->purchaseOrder ? $this->invoice->purchaseOrder->purchase_order_number : '';
    $data['{invoice_date}'] = Carbon::parse($this->invoice->invoice_date)->format('d M Y');
    $data['{table}'] = '';

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.if-payment-reminder',
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
