<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\PaymentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentRequested extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   */
  public function __construct(public PaymentRequest $payment_request, public string $link, public string $type, public $noa = NULL)
  {
    $this->template = EmailTemplate::where('name', 'Seller Finance Request')->first();
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
      $subject = 'Seller Finance Request';
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
    $invoices = '<tr><td>' . $this->payment_request->invoice->invoice_number . '</td><td>' . $this->payment_request->invoice->company->name . '</td><td>' . number_format($this->payment_request->invoice->invoice_total_amount, 2) . '</td><td>' . Carbon::parse($this->payment_request->invoice->due_date)->format('d M Y') . '</td></tr>';

    $data = array();
    $data['{seller_company}'] = $this->payment_request->invoice->company->name;
    $data['{table}'] =
      '<table>
    <thead>
    <tr>
    <th>Invoice Number</th>
    <th>Vendor</th>
    <th>Invoice Amount</th>
    <th>Due Date</th>
    </tr>
    </thead>
    <tbody>
    ' . $invoices . '
    </tbody>
    </table>';

    $mail_text = $this->template->body;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    $mail_text = str_replace('{table}', '', $mail_text);

    return new Content(
      markdown: 'content.email.payment-requested',
      with: [
        'payment_request' => $this->payment_request,
        'link' => $this->link,
        'mail_text' => $mail_text,
        'template' => $this->template,
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
    if ($this->noa) {
      $pdf = Pdf::loadView('pdf.noa', [
        'data' => $this->noa
      ])->setPaper('a4', 'landscape');

      return [
        Attachment::fromData(fn() => $pdf->output(), 'NOA_' . $this->payment_request->invoice->invoice_number . '.pdf')->withMime('application/pdf')
      ];
    } else {
      return [];
    }
  }
}
