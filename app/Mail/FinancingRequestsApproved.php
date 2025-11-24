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

class FinancingRequestsApproved extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public array $financing_requests, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Payment Requests Approved by Maker')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = '';
    if ($this->template) {
      $subject = $this->template->subject;
    } else {
      $subject = 'Payment Requests pending checker approval';
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
    $requests = '';
    foreach ($this->financing_requests as $financing_request) {
      $financing_request = PaymentRequest::find($financing_request);
      $requests .=
        '<tr><td>' .
        $financing_request->reference_number .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #014b9d">' .
        $financing_request->invoice->invoice_number .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format($financing_request->invoice->invoice_total_amount, 2) .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format($financing_request->amount, 2) .
        '</td>
        <td>|</td>
        <td>' .
        Carbon::parse($financing_request->payment_request_date)->format('d M Y') .
        '</td>
        <td>|</td>
        <td>' .
        Carbon::parse($financing_request->invoice->due_date)->format('d M Y') .
        '</td></tr>';
    }

    $data = [];
    $data['table'] =
      '<table>
    <thead>
    <tr>
    <th>Reference Number</th>
    <th>|</th>
    <th>Invoice No</th>
    <th>|</th>
    <th>Invoice Amount</th>
    <th>|</th>
    <th>Request Amount</th>
    <th>|</th>
    <th>Requested Payment Date</th>
    <th>|</th>
    <th>Due Date</th>
    </tr>
    </thead>
    <tbody>
    ' .
      $requests .
      '
    </tbody>
    </table>';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.financing-requests-approved',
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
