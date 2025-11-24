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

class DueDateChange extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Invoice $invoice, public $old_date)
  {
    $this->template  = EmailTemplate::where('name', 'Due Date Change')->first();
    $this->logo = $invoice->program->bank->adminConfiguration()->exists() ? $invoice->program->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
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
    $data['buyer_company'] = $this->invoice->program->anchor->name;
    $data['invoice_number'] = $this->invoice->invoice_number;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Invoice Due Date Changed';
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
    $data['buyer_company'] = $this->invoice->program->anchor->name;
    $data['new_due_date'] = Carbon::parse($this->invoice->due_date)->format('d M Y');
    $data['old_due_date'] = Carbon::parse($this->old_date)->format('d M Y');
    $data['invoice_number'] = $this->invoice->invoice_number;
    $data['invoice_date'] = Carbon::parse($this->invoice->invoice_date)->format('d M Y');
    $data['currency'] = $this->invoice->currency;
    $data['po_number'] = $this->invoice->purchaseOrder ? $this->invoice->purchaseOrder->purchase_order_number : '';
    $data['amount'] = number_format($this->invoice->invoice_total_amount);

    $mail_text = $this->template->body;

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }

    $mail_text = str_replace('{table}', '', $mail_text);

    return new Content(
      markdown: 'content.email.due-date-change',
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
