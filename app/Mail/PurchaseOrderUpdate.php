<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class PurchaseOrderUpdate extends Mailable
{
  use Queueable, SerializesModels;

  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public string $purchase_order_number, public string $url, public string $name)
  {
    $purchase_order = PurchaseOrder::where('purchase_order_number', $purchase_order_number)->first();
    if (!$purchase_order) {
      $this->logo = config('app.url') . '/assets/img/branding/logo-name.png';
    } else {
      $this->logo = $purchase_order->company->bank->adminConfiguration()->exists() ? $purchase_order->company->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
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
      subject: 'Purchase Order Update',
    );
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    return new Content(
      markdown: 'content.email.purchase-order-update',
      with: [
        'purchase' => $this->purchase_order_number,
        'url' => $this->url,
        'name' => $this->name,
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
