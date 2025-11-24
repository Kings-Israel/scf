<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceTax;
use App\Models\Payment;
use App\Models\ProgramVendorConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MigrationInvoicesImport implements ToCollection, WithHeadingRow, WithMapping
{
  public function map($row): array
  {
    return [
      'invoice_number' => $row['invoices_invoice_number'],
      'vendor_name' => $row['seller'],
      'anchor_name' => $row['buyer'],
      'invoice_status' => $row['invoice_status'],
      'financing_status' => $row['finance_status'],
      'total_amount' => $row['invoices_invoice_amount'],
      'tax_amount' => $row['invoices_invoice_tax_amount'],
      'calculated_total_amount' => $row['invoices_final_amount'],
      'currency' => $row['invoices_currency'],
      'created_at' => $row['invoices_creation_date'],
      'updated_at' => $row['invoices_updation_date'],
      'invoice_date' => $row['invoices_creation_date'],
      'old_due_date' => $row['invoices_original_due_date'],
      'due_date' => $row['invoices_due_date'],
      'remarks' => $row['invoices_remarks'],
      'pi_number' => $row['pi_pi_number'],
      'pi_amount' => $row['pi_net_amount'],
      'eligibility' => $row['discountings_eligi_percent'],
      'discount_amount' => $row['discountings_expected_interest'],
      'repaid_date' => $row['discountings_loan_closed_date'],
    ];
  }

  /**
  * @param Collection $collection
  */
  public function collection(Collection $collection)
  {
    foreach ($collection as $row) {
      $anchor = Company::where('name', Str::upper($row['anchor_name']).'.')->first();
      $vendor = Company::where('name', $row['vendor_name'])->first();
      // Check if invoice already exists
      $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();

      if ($vendor && !$invoice) {
        // Find the vendors configuration
        $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $vendor->id)->first();

        if ($program_vendor_configuration) {
          $invoice_date = gettype($row['invoice_date']) == 'integer' || gettype($row['invoice_date']) == 'double'
            ? Date::excelToDateTimeObject($row['invoice_date'])->format('Y-m-d H:i:s')
            : ($row['invoice_date'] != null
              ? Carbon::parse($row['invoice_date'])->format('Y-m-d H:i:s')
              : null);

          $due_date = gettype($row['due_date']) == 'integer' || gettype($row['due_date']) == 'double'
            ? Date::excelToDateTimeObject($row['due_date'])->format('Y-m-d H:i:s')
            : ($row['due_date'] != null
              ? Carbon::parse($row['due_date'])->format('Y-m-d H:i:s')
              : null);

          $created_at = gettype($row['created_at']) == 'integer' || gettype($row['created_at']) == 'double'
            ? Date::excelToDateTimeObject($row['created_at'])->format('Y-m-d H:i:s')
            : ($row['created_at'] != null
              ? Carbon::parse($row['created_at'])->format('Y-m-d H:i:s')
              : null);

          $updated_at = gettype($row['updated_at']) == 'integer' || gettype($row['updated_at']) == 'double'
            ? Date::excelToDateTimeObject($row['updated_at'])->format('Y-m-d H:i:s')
            : ($row['updated_at'] != null
              ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i:s')
              : null);

          $old_due_date = gettype($row['old_due_date']) == 'integer' || gettype($row['old_due_date']) == 'double'
            ? Date::excelToDateTimeObject($row['old_due_date'])->format('Y-m-d H:i:s')
            : ($row['old_due_date'] != null
              ? Carbon::parse($row['old_due_date'])->format('Y-m-d H:i:s')
              : null);

          $status = 'submitted';
          $financing_status = 'pending';
          $stage = 'pending';

          if ($row['invoice_status'] == 'Paid' && $row['financing_status'] == 'Closed') {
            $status = 'disbursed';
            $financing_status = 'closed';
            $stage = 'closed';
          } elseif ($row['invoice_status'] == 'Approved' && $row['financing_status'] == 'Disbursed') {
            $status = 'disbursed';
            $financing_status = 'financed';
            $stage = 'disbursed';
          } elseif ($row['invoice_status'] == 'Rejected' && $row['financing_status'] == 'Not Requested') {
            $status = 'denied';
            $financing_status = 'pending';
            $stage = 'rejected';
          } elseif ($row['invoice_status'] == 'Approved' && $row['financing_status'] == 'Rejected') {
            $status = 'approved';
            $financing_status = 'denied';
            $stage = 'approved';
          }

          // Create Invoice
          $invoice = Invoice::create([
            'program_id' => $program_vendor_configuration->program_id,
            'company_id' => $vendor->id,
            'invoice_number' => $row['invoice_number'],
            'invoice_date' => $invoice_date,
            'due_date' => $due_date,
            'old_due_date' => !empty($row['old_due_date']) ? $old_due_date : null,
            'total_amount' => str_replace(',', '', str_replace('Ksh', '', $row['total_amount'])),
            'currency' => in_array('currency', $row->toArray()) && !empty($row['currency'])
              ? $row['currency']
              : $program_vendor_configuration->program->bank->default_currency,
            'status' => $status,
            'financing_status' => $financing_status,
            'stage' => $stage,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'pi_number' => $row['pi_number'] != 'NULL' && $row['pi_number'] != null ? $row['pi_number'] : null,
            'disbursed_amount' => $financing_status == 'financed' || $financing_status == 'closed' ? $row['pi_amount'] : null,
            'eligibility' => $row['eligibility'] != 'NULL' && $row['eligibility'] != null ? $row['eligibility'] : NULL,
          ]);

          // if (
          //   array_key_exists('tax_amount', $row->toArray()) &&
          //   $row['tax_amount'] != null &&
          //   $row['tax_amount'] != '' &&
          //   $row['tax_amount'] > 0
          // ) {
          //   InvoiceTax::create([
          //     'invoice_id' => $invoice->id,
          //     'name' => 'Tax Amount',
          //     'value' => $row['tax_amount'],
          //   ]);
          // }

          // if ($financing_status == 'closed') {
          //   Payment::create([
          //     'invoice_id' => $invoice->id,
          //     'amount' => $invoice->invoice_total_amount,
          //     'created_at' => gettype($row['repaid_date']) == 'integer' || gettype($row['repaid_date']) == 'double'
          //       ? Date::excelToDateTimeObject($row['repaid_date'])->format('Y-m-d H:i:s')
          //       : ($row['repaid_date'] != null
          //         ? Carbon::parse($row['repaid_date'])->format('Y-m-d H:i:s')
          //         : null),
          //     'updated_at' => gettype($row['repaid_date']) == 'integer' || gettype($row['repaid_date']) == 'double'
          //       ? Date::excelToDateTimeObject($row['repaid_date'])->format('Y-m-d H:i:s')
          //       : ($row['repaid_date'] != null
          //         ? Carbon::parse($row['repaid_date'])->format('Y-m-d H:i:s')
          //         : null),
          //   ]);
          // }
        }
      }
    }
  }
}
