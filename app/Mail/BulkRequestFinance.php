<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\NoaTemplate;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorDiscount;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;

class BulkRequestFinance extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   */
  public function __construct(public array $finance_requests, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Bulk Payment Request')->first();
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    $subject = '';
    if ($this->template) {
      $subject = $this->template->subject;
    } else {
      $subject = 'New Payment Request(s) initiated';
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
    foreach ($this->finance_requests as $financing_request) {
      $financing_request = PaymentRequest::find($financing_request);
      $vendor = '';
      $anchor = '';
      if ($financing_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
        $vendor = $financing_request->invoice->company->name;
        $anchor = $financing_request->invoice->program->anchor->name;
      } else {
        if ($financing_request->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
          $vendor = $financing_request->invoice->company->name;
          $anchor = $financing_request->invoice->program->anchor->name;
        } else {
          $vendor = $financing_request->invoice->program->anchor->name;
          $anchor = $financing_request->invoice->buyer->name;
        }
      }

      $taxes_amount = PaymentRequestAccount::where('payment_request_id', $financing_request->id)
        ->whereIn('type', ['tax_on_fees', 'tax_on_discount'])
        ->sum('amount');

      $requests .=
        '<tr>
        <td style="white-space: nowrap">' .
        $financing_request->invoice->pi_number .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #014b9d">' .
        $financing_request->invoice->invoice_number .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap">' .
        $vendor .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap"> ' .
        $anchor .
        '
        </td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format(
          ($financing_request->invoice->eligibility / 100) * $financing_request->invoice->invoice_total_amount,
          2
        ) .
        '</td>
        <td>|</td>
        <td> ' .
        $financing_request->invoice->eligibility .
        '%' .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap; color: #57d38e"> ' .
        number_format($financing_request->amount, 2) .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap"> ' .
        Carbon::parse($financing_request->payment_request_date)->format('d M Y') .
        '</td>
        <td>|</td>
        <td style="white-space: nowrap">' .
        Carbon::parse($financing_request->invoice->due_date)->format('d M Y') .
        '</td><td>|</td>
        <td style="white-space: nowrap; color: #57d38e">' .
        number_format(
          $financing_request->vendor_fee_bearing + $financing_request->vendor_discount_bearing + $taxes_amount,
          2
        ) .
        '</td>
        </tr>';
    }

    $data['table'] =
      '<table>
    <thead>
    <tr>
    <th>PI No.</th>
    <th>|</th>
    <th>Invoice No</th>
    <th>|</th>
    <th>Vendor</th>
    <th>|</th>
    <th>Anchor</th>
    <th>|</th>
    <th>PI Amount</th>
    <th>|</th>
    <th>Eligibility</th>
    <th>|</th>
    <th>Payment Amount</th>
    <th>|</th>
    <th>Payment Date</th>
    <th>|</th>
    <th>Maturity Date</th>
    <th>|</th>
    <th>Fees/Charges</th>
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
      $mail_text = str_replace(
        '{' . $key . '}',
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : ' ' . $val,
        $mail_text
      );
    }

    return new Content(
      markdown: 'content.email.bulk-finance-request',
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
    $noas = [];

    $anchor_name = '';
    $buyer_name = '';
    $seller_name = '';
    $anchor_address = '';

    foreach ($this->finance_requests as $financing_request) {
      $finance_request = PaymentRequest::find($financing_request);

      if ($finance_request->invoice->program->programType->name == Program::DEALER_FINANCING) {
        $anchor_name = $finance_request->invoice->program->anchor->name;
        $seller_name = $finance_request->invoice->company->name;

        $anchor_address =
          $finance_request->invoice->program->anchor->postal_code .
          ' ' .
          $finance_request->invoice->program->anchor->address .
          ' ' .
          $finance_request->invoice->program->anchor->city .
          ' ';

        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'anchor_discount_bearing',
          'vendor_discount_bearing',
          'penal_discount_on_principle'
        )
          ->where('company_id', $finance_request->invoice->company_id)
          ->where('program_id', $finance_request->invoice->program_id)
          ->first();

        $bank_details = ProgramBankDetails::where('program_id', $finance_request->invoice->program_id)->first();

        $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
          ->where('status', 'active')
          ->where('bank_id', $finance_request->invoice->program->bank_id)
          ->first();

        if (!$noa_text) {
          $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
            ->where('status', 'active')
            ->where('bank_id', null)
            ->first();
        }
      } else {
        if ($finance_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $anchor_name = $finance_request->invoice->program->anchor->name;
          $seller_name = $finance_request->invoice->company->name;
          $anchor_address =
            $finance_request->invoice->program->anchor->postal_code .
            ' ' .
            $finance_request->invoice->program->anchor->address .
            ' ' .
            $finance_request->invoice->program->anchor->city .
            ' ';

          $vendor_discount_details = ProgramVendorDiscount::select(
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing',
            'penal_discount_on_principle'
          )
            ->where('company_id', $finance_request->invoice->company_id)
            ->where('program_id', $finance_request->invoice->program_id)
            ->first();

          $bank_details = ProgramBankDetails::where('program_id', $finance_request->invoice->program_id)->first();

          $noa_text = NoaTemplate::where('product_type', 'vendor_financing')
            ->where('status', 'active')
            ->where('bank_id', $finance_request->invoice->program->bank_id)
            ->first();

          if (!$noa_text) {
            $noa_text = NoaTemplate::where('product_type', 'vendor_financing')
              ->where('status', 'active')
              ->where('bank_id', null)
              ->first();
          }
        } else {
          $anchor_name = $finance_request->invoice->buyer->name;
          $buyer_name = $finance_request->invoice->program->anchor->name;
          $seller_name = $finance_request->invoice->program->anchor->name;
          $anchor_address =
            $finance_request->invoice->buyer->postal_code .
            ' ' .
            $finance_request->invoice->buyer->address .
            ' ' .
            $finance_request->invoice->buyer->city .
            ' ';

          $vendor_discount_details = ProgramVendorDiscount::select(
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing',
            'penal_discount_on_principle'
          )
            ->where('company_id', $finance_request->invoice->company_id)
            ->where('program_id', $finance_request->invoice->program_id)
            ->where('buyer_id', $finance_request->invoice->buyer_id)
            ->first();

          $bank_details = ProgramVendorBankDetail::where('program_id', $finance_request->invoice->program_id)
            ->where('buyer_id', $finance_request->invoice->buyer_id)
            ->first();

          $noa_text = NoaTemplate::where('product_type', 'factoring')
            ->where('status', 'active')
            ->where('bank_id', $finance_request->invoice->program->bank_id)
            ->first();

          if (!$noa_text) {
            $noa_text = NoaTemplate::where('product_type', 'factoring')
              ->where('status', 'active')
              ->where('bank_id', null)
              ->first();
          }
        }
      }

      if (!$noa_text) {
        $noa_text = NoaTemplate::where('product_type', 'generic')
          ->where('status', 'active')
          ->first();
      }

      // Send NOA
      $data = [];
      $data['{date}'] = Carbon::parse($finance_request->invoice->invoice_date)->format('d M Y');
      $data['{buyerName}'] = $buyer_name;
      $data['{anchorName}'] = $anchor_name;
      $data['{company}'] = $finance_request->invoice->company->name;
      $data['{anchorCompanyUniqueID}'] = $finance_request->invoice->program->anchor->unique_identification_number;
      $data['{time}'] = now()->format('d M Y H:i A');
      $data['{agreementDate}'] = now()->format('d M Y');
      $data['{contract}'] = '';
      $data['{anchorAccountName}'] = $bank_details?->name_as_per_bank;
      $data['{anchorAccountNumber}'] = $bank_details?->account_number;
      $data['{anchorCustomerId}'] = '';
      $data['{anchorBranch}'] = $bank_details?->branch;
      $data['{anchorIFSCCode}'] = '';
      $data['{anchorAddress}'] = $anchor_address;
      $data['{penalInterestRate}'] = $vendor_discount_details->penal_discount_on_principle;
      $data['{sellerName}'] = $seller_name;

      $noa = '';

      if ($noa_text != null) {
        $noa = $noa_text->body;
        foreach ($data as $key => $val) {
          $noa = str_replace($key, $val, $noa);
        }
      }

      if ($noa && $noa != '') {
        $pdf = Pdf::loadView('pdf.noa', [
          'data' => $noa,
        ])->setPaper('a4', 'landscape');

        array_push(
          $noas,
          Attachment::fromData(
            fn() => $pdf->output(),
            'NOA_' . $finance_request->invoice->invoice_number . '.pdf'
          )->withMime('application/pdf')
        );
      }
    }

    return $noas;
  }
}
