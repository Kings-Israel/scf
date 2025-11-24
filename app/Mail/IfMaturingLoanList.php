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

class IfMaturingLoanList extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public array $invoices, public $due_date, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'IF Maturing Loan List')->first();
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
    $data['due_date'] = Carbon::parse($this->due_date)->format('d M Y');

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Loans Maturing on ' . Carbon::parse($this->due_date)->format('d M Y');
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
    $invoices = '';

    foreach ($this->invoices as $invoice) {
      $invoice = Invoice::find($invoice);
      $invoices .= '<tr><td>' . $invoice->invoice_number . '</td><td>' . $invoice->company->name . '</td><td>' . $invoice->invoice_total_amount . '</td><td>' . Carbon::parse($invoice->due_date)->format('d M Y') . '</td></tr>';
    }

    $data = array();
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

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.if-maturing-loan-list',
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
