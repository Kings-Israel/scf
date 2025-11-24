<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\ProgramVendorConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MigrationFactoringInvoicesImport implements ToCollection, WithHeadingRow, WithMapping
{
  public function map($row): array
  {
    return [
      'invoice_number' => $row['invoice_no'],
      // 'vendor_name' => $row['vendor'],
      'anchor_name' => $row['anchor'],
      'invoice_status' => $row['status'],
      // 'financing_status' => $row['status'],
      'total_amount' => $row['invoice_amount'],
      'calculated_total_amount' => $row['invoice_amount'],
      'currency' => 'KES',
      'due_date' => $row['due_date'],
      // 'pi_number' => $row['pi_number'],
      // 'pi_amount' => $row['eligible_payment_amount_ksh'],
      // 'eligibility' => $row['eligibility'],
      // 'disbursement_date' => $row['requested_disbursement_date'],
      // 'created_at' => $row['created_at'],
      // 'updated_at' => $row['last_updated_at'],
      'invoice_date' => $row['invoice_date'],
    ];
  }

  /**
   * @param Collection $collection
   */
  public function collection(Collection $collection)
  {
    foreach ($collection as $row) {
      $anchor = Company::where('name', $row['anchor_name'])->first();
      // $vendor = Company::where('name', Str::upper($row['vendor_name']) . '.')->first();
      // Check if invoice already exists

      $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();

      if (
        $invoice &&
        ($invoice->financing_status != 'financed' || $invoice->financing_status != 'closed') &&
        $row['invoice_status'] === 'Rejected'
      ) {
        $invoice->update([
          'status' => 'denied',
          'stage' => 'rejected',
        ]);
      }

      if ($anchor && !$invoice) {
        // Find the vendors configuration
        $program_vendor_configuration = ProgramVendorConfiguration::where('buyer_id', $anchor->id)->first();

        if ($program_vendor_configuration) {
          $invoice_date =
            $row['invoice_date'] != null && $row['invoice_date'] != ''
              ? Helpers::importParseDate($row['invoice_date'])
              : null;

          $due_date =
            $row['due_date'] != null && $row['due_date'] != '' ? Helpers::importParseDate($row['due_date']) : null;

          $created_at =
            $row['invoice_date'] != null && $row['invoice_date'] != ''
              ? Helpers::importParseDate($row['invoice_date'], 'Y-m-d')
              : null;

          $updated_at =
            $row['invoice_date'] != null && $row['invoice_date'] != ''
              ? Helpers::importParseDate($row['invoice_date'], 'Y-m-d')
              : null;

          $status = 'submitted';
          $financing_status = 'pending';
          $stage = 'pending';

          if ($row['invoice_status'] === 'Paid') {
            $status = 'disbursed';
            $financing_status = 'closed';
            $stage = 'closed';
          } elseif ($row['invoice_status'] === 'Disbursed') {
            $status = 'disbursed';
            $financing_status = 'financed';
            $stage = 'disbursed';
          } elseif ($row['invoice_status'] === 'Approved') {
            $status = 'approved';
            $financing_status = 'pending';
            $stage = 'approved';
          } elseif ($row['invoice_status'] === 'Rejected') {
            $status = 'denied';
            $financing_status = 'pending';
            $stage = 'rejected';
          } elseif ($row['invoice_status'] === 'Submitted') {
            $status = 'submitted';
            $financing_status = 'pending';
            $stage = 'pending';
          }

          if (now()->greaterThan($due_date)) {
            $stage = 'past_due';
          }

          // $pi_number = Invoice::where('company_id', $vendor->id)
          //   ->orderBy('id', 'DESC')
          //   ->first();

          // Create Invoice
          $invoice = Invoice::create([
            'program_id' => $program_vendor_configuration->program_id,
            'company_id' => $program_vendor_configuration->company_id,
            'buyer_id' => $anchor->id,
            'invoice_number' => $row['invoice_number'],
            'invoice_date' => $invoice_date,
            'due_date' => $due_date,
            'total_amount' => str_replace(',', '', str_replace('Ksh', '', $row['total_amount'])),
            'currency' =>
              in_array('currency', $row->toArray()) && !empty($row['currency'])
                ? $row['currency']
                : $program_vendor_configuration->program->bank->default_currency,
            'status' => $status,
            'financing_status' => $financing_status,
            'stage' => $stage,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            // 'pi_number' => $pi_number ? 'PI_' . (int) explode('_', $pi_number->pi_number)[1] + 1 : 'PI_' . 1, // Increment the last PI number by 1
            // 'disbursed_amount' =>
            //   $financing_status == 'financed' || $financing_status == 'closed'
            //     ? str_replace(',', '', $row['pi_amount'])
            //     : null,
            // 'eligibility' => $row['eligibility'] != 'NULL' && $row['eligibility'] != null ? $row['eligibility'] : null,
          ]);
        }
      }

      // $new_pi_number = 'PI_' . $invoice->id;

      // $invoice->update([
      //   'pi_number' => $new_pi_number,
      // ]);
    }
  }
}
