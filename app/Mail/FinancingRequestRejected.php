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

class FinancingRequestRejected extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $financing_request;
  public $status;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(
    string $financing_request,
    public string $url,
    public string $name,
    public string $type,
    public string $logo = ''
  ) {
    $this->financing_request = PaymentRequest::with('invoice')->find($financing_request);

    $this->template = EmailTemplate::where('name', 'Payment Request Rejection')->first();
    if ($this->financing_request->status == 'rejected') {
      $this->status = 'Rejected';
    } else {
      if ($this->financing_request->approvals->count() == 2) {
        $this->status = 'Approved';
      } else {
        $this->status = 'First Approval';
      }
    }
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
    $data['invoice_number'] = $this->financing_request->invoice->invoice_number;
    $data['bank_name'] = $this->financing_request->invoice->program->bank->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Finance Request Rejected';
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
    $data['invoice_number'] = $this->financing_request->invoice->invoice_number;
    $data['reject_remark'] = $this->financing_request->rejected_reason;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        '{' . $key . '}',
        gettype($val) == 'integer' || gettype($val) == 'double' ? ' ' . number_format($val) : ' ' . $val,
        $mail_text
      );
    }

    return new Content(
      markdown: 'content.bank.email.financing-rejected',
      with: [
        'financing_request' => $this->financing_request,
        'url' => $this->url,
        'name' => $this->name,
        'status' => $this->status,
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
