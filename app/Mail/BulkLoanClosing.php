<?php

namespace App\Mail;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkLoanClosing extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public array $invoices, public string $logo = '')
  {
    //
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(subject: 'Invoice(s) Closure');
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $requests = '';
    foreach ($this->invoices as $invoice_id) {
      $invoice = Invoice::find($invoice_id);
      $requests .=
        '<tr>
        <td>' .
        $invoice->company->name .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #014b9d">' .
        $invoice->invoice_number .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format($invoice->invoice_total_amount, 2) .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format($invoice->disbursed_amount, 2) .
        '</td>
        <td>|</td>
        <td>' .
        Carbon::parse($invoice->disbursement_date)->format('d M Y') .
        '</td>
        <td>|</td>
        <td>' .
        Carbon::parse($invoice->due_date)->format('d M Y') .
        '</td>
        <td>|</td>
        <td>' .
        $invoice->updated_at->format('d M Y') .
        '</td>
        </tr>';
    }

    $table_data =
      '<table>
    <thead>
    <tr>
    <th>Vendor</th>
    <th>|</th>
    <th>Invoice No.</th>
    <th>|</th>
    <th>Invoice Amount</th>
    <th>|</th>
    <th>Disbursed Amount</th>
    <th>|</th>
    <th>Disbursement Date</th>
    <th>|</th>
    <th>Due Date</th>
    <th>|</th>
    <th>Closure Date</th>
    </tr>
    </thead>
    <tbody>
    ' .
      $requests .
      '
    </tbody>
    </table>
    <br>';

    return new Content(
      markdown: 'mail.bulk-loan-closing',
      with: [
        'logo' => $this->logo,
        'table_data' => $table_data,
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
