<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class PiUploads extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public array $invoices, public Company $company)
  {
    $this->template = EmailTemplate::where('name', 'PI Uploads')->first();
    $this->logo = $company->bank->adminConfiguration()->exists() ? $company->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    if ($this->template) {
      $subject = $this->template->subject;
    } else {
      $subject = 'New PI(s) Uploaded';
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
      $invoices .= '<tr><td>' . $invoice->invoice_number . '</td><td>' . $invoice->invoice_total_amount . '</td><td>' . Carbon::parse($invoice->invoice->due_date)->format('d M Y') . '</td></tr>';
    }

    $data = array();
    $data['{buyer_name}'] = $this->company->name;
    $data['{table}'] =
      '<table>
    <thead>
    <tr>
    <th>Invoice Number</th>
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
      markdown: 'content.email.pi-uploads',
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
