<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRequestRejection extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public PaymentRequest $payment_request, public string $user_name, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Payment Request Rejection')->first();
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
    $data['payment_request_number'] = $this->payment_request->reference_number;
    $data['user_name'] = $this->user_name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Payment Request ' . $this->payment_request->reference_number . ' rejected';
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
    $data['{payment_request_id}'] = $this->payment_request->reference_number;
    $data['{user_name}'] = $this->user_name;
    $data['{reject_remark}'] = $this->payment_request->rejected_reason;

    $mail_text = $this->template ? $this->template->body : null;

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        $key,
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : $val,
        $mail_text
      );
    }
    return new Content(
      markdown: 'content.bank.email.payment-request-rejection',
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
