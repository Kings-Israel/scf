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

class FinancingRequestApproved extends Mailable
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
    public string $approver_name,
    public string $type,
    public string $logo = ''
  ) {
    $this->financing_request = PaymentRequest::with('invoice')->find($financing_request);
    $this->template = EmailTemplate::where('name', 'Payment Request Approved by Maker')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(subject: $this->template ? $this->template->subject : 'Payment Request Approved by Maker');
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $data = [];
    $data['company_name'] = $this->financing_request->invoice->company->name;
    $data['maker_name'] = $this->approver_name;
    $data['invoice_number'] = $this->financing_request->invoice->invoice_number;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        '{' . $key . '}',
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : $val,
        $mail_text
      );
    }

    $invoices =
      '<tr>
      <td>' .
      $this->financing_request->reference_number .
      '</td>
      <td>|</td>
      <td style="white-space: nowrap; color: #014b9d">' .
      $this->financing_request->invoice->invoice_number .
      '</td>
      <td>|</td>
      <td>' .
      $this->financing_request->invoice->company->name .
      '</td>
      <td>|</td>
      <td style="white-space: nowrap; color: #57d38e">' .
      number_format($this->financing_request->invoice->invoice_total_amount, 2) .
      '</td>
      <td>|</td>
      <td style="white-space: nowrap; color: #57d38e">' .
      number_format($this->financing_request->amount, 2) .
      '<td>|</td>
      <td>' .
      Carbon::parse($this->financing_request->payment_request_date)->format('d M Y') .
      '</td>
      <td>|</td>
      <td>' .
      Carbon::parse($this->financing_request->invoice->due_date)->format('d M Y') .
      '</td></tr>';

    $table_data =
      '<table>
    <thead>
    <tr>
    <th>Reference Number</th>
    <th>|</th>
    <th>Invoice Number</th>
    <th>|</th>
    <th>Vendor</th>
    <th>|</th>
    <th>Invoice Amount</th>
    <th>|</th>
    <th>Requested Amount</th>
    <th>|</th>
    <th>Requested Disbursement Date</th>
    <th>|</th>
    <th>Due Date</th>
    </tr>
    </thead>
    <tbody>' .
      $invoices .
      '
    </tbody>
    </table>';

    $mail_text = str_replace('{table}', $table_data, $mail_text);

    return new Content(
      markdown: 'content.bank.email.financing-request-approved',
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
