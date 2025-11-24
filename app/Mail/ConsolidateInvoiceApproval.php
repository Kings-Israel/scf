<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConsolidateInvoiceApproval extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Company $company, public array $invoices)
  {
    $this->template = EmailTemplate::where('name', 'Consolidate Invoice Approval')->first();
    $this->logo = $company->bank->adminConfiguration()->exists()
      ? $company->bank->adminConfiguration->logo
      : config('app.url') . '/assets/img/branding/logo-name.png';
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
    $data['buyer_company'] = $this->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Invoice(s) Approved by ' . $this->company->name;
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
    $requests = '';
    $data['seller_company'] = $this->company->name;
    $data['buyer_company'] = $this->company->name;

    foreach ($this->invoices as $invoice_id) {
      $invoice = Invoice::find($invoice_id);
      $requests .=
        '<tr><td style="white-space: nowrap; color: #014b9d">' .
        $invoice->invoice_number .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format($invoice->invoice_total_amount, 2) .
        '</td>
        <td>|</td>
        <td>' .
        Carbon::parse($invoice->invoice_date)->format('d M Y') .
        '</td>
        <td>|</td>
        <td>' .
        Carbon::parse($invoice->due_date)->format('d M Y') .
        '</td></tr>';
    }

    $data['table'] =
      '<table>
    <thead>
    <tr>
    <th>Invoice No</th>
    <th>|</th>
    <th>Invoice Amount</th>
    <th>|</th>
    <th>Invoice Date</th>
    <th>|</th>
    <th>Due Date</th>
    </tr>
    </thead>
    <tbody>
    ' .
      $requests .
      '
    </tbody>
    </table>
    <br>';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        '{' . $key . '}',
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : ' ' . $val,
        $mail_text
      );
    }

    return new Content(
      markdown: 'content.email.consolidate-invoice-approval',
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
