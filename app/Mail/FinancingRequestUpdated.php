<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\PaymentRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinancingRequestUpdated extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $financing_request;
  public $status;
  public $logo;

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
    $logo = ''
  ) {
    $this->financing_request = PaymentRequest::with('invoice')->find($financing_request);

    $this->template = EmailTemplate::where('product_type', $type)
      ->where('name', 'Payment Request Approval')
      ->first();
    if ($this->financing_request->status == 'rejected') {
      $this->status = 'Rejected';
    } else {
      if ($this->financing_request->approvals->count() == 2) {
        $this->status = 'Approved';
      } else {
        $this->status = 'First Approval';
      }
    }
    $this->logo = $logo;
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(
      subject: $this->template
        ? str_replace('{bank_name}', $this->financing_request->invoice->program->bank->name, $this->template->subject)
        : 'Financing Request Approved'
    );
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $data = [];
    $data['cur'] = $this->financing_request->invoice->program->bank->default_currency;
    $data['invoice_number'] = $this->financing_request->invoice->invoice_number;
    $data['seller_company'] = $this->financing_request->invoice->company->name;
    $data['buyer_company'] = $this->financing_request->invoice->program->anchor->name;
    $data['pi_amount'] =
      $this->financing_request->invoice->total +
      $this->financing_request->invoice->total_invoice_taxes -
      $this->financing_request->invoice->total_invoice_fees -
      $this->financing_request->invoice->total_invoice_discount;
    $data['loan_amount'] = $this->financing_request->amount;
    $data['eligibility'] = $this->financing_request->invoice->program
      ->vendorConfigurations()
      ->where('company_id', $this->financing_request->invoice->company->id)
      ->first()->eligibility;
    $data['loan_date'] = Carbon::parse($this->financing_request->payment_request_date)->format('d M Y');
    $data['maturity_date'] = Carbon::parse($this->financing_request->invoice->due_date)->format('d M Y');
    $data['days_to_payment'] = now()->diffInDays(Carbon::parse($this->financing_request->invoice->due_date));
    $data['processing_fees'] = $this->financing_request->processing_fee;
    $data['symbol'] = $this->financing_request->invoice->currency;
    $data['amount'] = $this->financing_request->amount;
    $data['date'] = Carbon::parse($this->financing_request->payment_request_date)->format('d M Y');

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        '{' . $key . '}',
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : $val,
        $mail_text
      );
    }

    $mail_text = str_replace('{table}', '', $mail_text);

    return new Content(
      markdown: 'content.bank.email.financing-request-updated',
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
