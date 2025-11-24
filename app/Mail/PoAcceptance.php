<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\EmailTemplate;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class PoAcceptance extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   */
  public function __construct(public PurchaseOrder $purchase_order)
  {
    $this->template = EmailTemplate::where('name', 'PO Acceptance')->first();
    $this->logo = $purchase_order->company->bank->adminConfiguration()->exists() ? $purchase_order->company->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    $subject = '';
    $data = array();
    $data['po_number'] = $this->purchase_order->purchase_order_number;
    $data['seller_company'] = $this->purchase_order->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'PO Acepted by ' . $this->purchase_order->company->name;
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
    $data['seller_company'] = $this->purchase_order->company->name;
    $data['po_number'] = $this->purchase_order->purchase_order_number;
    $data['start_date'] = Carbon::parse($this->purchase_order->duration_from)->format('d M Y');
    $data['end_date'] = Carbon::parse($this->purchase_order->duration_to)->format('d M Y');
    $data['currency'] = $this->purchase_order->currency;
    $data['po_amount'] = $this->purchase_order->total_amount;
    $data['table'] = '';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.po-acceptance',
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
