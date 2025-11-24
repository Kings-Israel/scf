<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\PaymentRequest;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class AutoRequestFinance extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   */
  public function __construct(public PaymentRequest $payment_request)
  {
    $this->template = EmailTemplate::where('name', 'Auto Finance Request')->first();
    $this->logo = $payment_request->invoice->program->bank->adminConfiguration()->exists() ? $payment_request->invoice->program->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    $subject = '';
    $data = array();
    $data['seller_company'] = $this->payment_request->invoice->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Auto Finance Request created by ' . $this->payment_request->invoice->company->name;
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
    $vendor_configuration = $this->payment_request->invoice->program->vendorConfigurations->where('company_id', $this->payment_request->invoice->company_id)->first();

    $data = array();
    $data['{invoice_number}'] = $this->payment_request->invoice->invoice_number;
    $data['{seller_company}'] = $this->payment_request->invoice->company->name;
    $data['{pi_number}'] = $this->payment_request->invoice->pi_number;
    $data['{buyer_company}'] = $this->payment_request->invoice->program->anchor->name;
    $data['{currency}'] = $this->payment_request->invoice->currency;
    $data['{pi_amount}'] = $this->payment_request->invoice->eligible_for_finance;
    $data['{loan_amount}'] = $this->payment_request->amount;
    $data['{eligibility}'] = $vendor_configuration->eligibility;
    $data['{loan_date}'] = now()->format('d M Y');
    $data['Processing Fees: {currency}{processing_fees}</p>'] = '';
    $data['{maturity_date}'] = Carbon::parse($this->payment_request->invoice->due_date)->format('d M Y');
    $data['{days_to_payment}'] = Carbon::parse($this->payment_request->invoice->invoice_date)->diffInDays(now());

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? ' ' . number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.auto-request-finance',
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
