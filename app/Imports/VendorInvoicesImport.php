<?php

namespace App\Imports;

use App\Helpers\Helpers;
use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceFee;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceUploadReport;
use App\Models\Program;
use App\Notifications\InvoiceCreated;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VendorInvoicesImport implements ToModel, WithMapping, WithHeadingRow, SkipsEmptyRows, WithEvents
{
  use Importable, SkipsFailures;

  public $data = 0;
  public $total_rows = 0;

  protected $programs = [];

  public $latest_batch_id = null;

  public function __construct(public Company $company, array $programs = [])
  {
    $this->latest_batch_id = InvoiceUploadReport::where('company_id', $this->company->id)
      ->select('batch_id')
      ->latest()
      ->first();

    $this->programs = $programs;
  }

  public function map($row): array
  {
    if (
      !array_key_exists('invoice_number', $row) ||
      !array_key_exists('invoice_date_ddmmyyyy', $row) ||
      !array_key_exists('net_invoice_amount', $row) ||
      !array_key_exists('invoice_due_date_ddmmyyyy', $row) ||
      !array_key_exists('loanod_account', $row)
    ) {
      throw ValidationException::withMessages([
        'Invalid headers or missing column. Download and use the sample template.',
      ]);
    }

    return [
      'invoice_number' => $row['invoice_number'],
      'invoice_date' =>
        $row['invoice_date_ddmmyyyy'] != null && $row['invoice_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['invoice_date_ddmmyyyy'])
          : null,
      'due_date' =>
        $row['invoice_due_date_ddmmyyyy'] != null && $row['invoice_due_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['invoice_due_date_ddmmyyyy'])
          : null,
      'payment_od_account' => $row['loanod_account'],
      'total_amount' => $row['net_invoice_amount'],
    ];
  }

  public function rules(): array
  {
    if (count($this->programs) > 0) {
      $account_numbers = ProgramVendorConfiguration::whereIn('program_id', $this->programs)->pluck(
        'payment_account_number'
      );
    } else {
      $account_numbers = ProgramVendorConfiguration::where('company_id', $this->company->id)->pluck(
        'payment_account_number'
      );
    }

    return [
      'invoice_number' => ['required'],
      'invoice_date' => ['required'],
      'due_date' => ['required'],
      'payment_od_account' => ['required', 'in:' . $account_numbers],
      'total_amount' => ['required'],
    ];
  }

  public function registerEvents(): array
  {
    return [
      BeforeImport::class => function (BeforeImport $event) {
        $this->total_rows = $event->getReader()->getTotalRows();
      },
    ];
  }

  /**
   * @param array $row
   *
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function model(array $row)
  {
    $vendor_configuration = ProgramVendorConfiguration::where('company_id', $this->company->id)
      ->where('payment_account_number', trim($row['payment_od_account']))
      ->first();
    $invoice = Invoice::where('invoice_number', $row['invoice_number'])
      ->where('company_id', $vendor_configuration?->company_id)
      ->first();

    if (count($this->programs) > 0) {
      $company_id = $vendor_configuration->company_id;
    } else {
      $company_id = $this->company->id;
    }

    $due_date = $row['due_date'];
    $invoice_date = $row['invoice_date'];

    if (
      $row['invoice_number'] &&
      !$invoice &&
      $vendor_configuration &&
      $invoice_date &&
      $due_date &&
      // now()->lessThanOrEqualTo($due_date) &&
      Carbon::parse($invoice_date)->lessThanOrEqualTo(Carbon::parse($due_date))
    ) {
      try {
        DB::beginTransaction();
        $this->data++;
        $invoice = Invoice::create([
          'company_id' => $company_id,
          'program_id' => $vendor_configuration->program_id,
          'invoice_number' => $row['invoice_number'],
          'invoice_date' => $row['invoice_date'],
          'due_date' => $row['due_date'],
          'total_amount' => str_replace(',', '', str_replace('Ksh', '', $row['total_amount'])),
          'currency' => $vendor_configuration->program->bank->default_currency,
          'financing_status' => 'pending',
        ]);

        // Apply Invoice Fees if set by anchor
        $vendor_settings = ProgramVendorConfiguration::where('company_id', $this->company->id)
          ->where('program_id', $invoice->program_id)
          ->select('withholding_tax', 'withholding_vat')
          ->first();

        if ($vendor_settings) {
          if ($vendor_settings->withholding_tax) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding Tax';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_tax / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }

          if ($vendor_settings->withholding_vat) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding VAT';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_vat / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }
        }

        $invoice_setting = $this->company->invoiceSetting;

        if ($invoice_setting) {
          if (!$invoice_setting->maker_checker_creating_updating) {
            // Submit the invoice to the anchor
            $invoice->update([
              'status' => 'submitted',
              'stage' => 'pending_maker',
            ]);

            $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

            foreach ($users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
                'id' => $invoice->id,
                'type' => 'vendor_financing',
              ]);
            }

            $invoice->program->anchor->notify(new InvoiceCreated($invoice));
          } else {
            $invoice->approvals()->create([
              'user_id' => auth()->id(),
            ]);

            $invoice->update([
              'status' => 'pending',
              'stage' => 'pending',
            ]);

            foreach ($invoice->company->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
                'id' => $invoice->id,
                'type' => 'vendor_financing',
              ]);
            }
          }
        } else {
          // Submit to anchor
          $invoice->update([
            'status' => 'submitted',
            'stage' => 'pending_maker',
          ]);

          $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

          foreach ($users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
              'id' => $invoice->id,
              'type' => 'vendor_financing',
            ]);
          }

          $invoice->program->anchor->notify(new InvoiceCreated($invoice));
        }

        if ($invoice && $invoice->status == 'approved') {
          $invoice->update([
            'calculated_total_amount' => $invoice->invoice_total_amount,
          ]);
        }

        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $invoice->invoice_number;
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = str_replace(',', '', str_replace('Ksh', '', $row['total_amount']));
        $invoice_upload_report->currency = $vendor_configuration->program->bank->default_currency;
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->status = 'Successful';
        $invoice_upload_report->description = 'Invoice Uploaded successfully';
        $invoice_upload_report->product_type = Program::VENDOR_FINANCING;
        $invoice_upload_report->product_code = Program::VENDOR_FINANCING_RECEIVABLE;
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->save();

        activity($invoice->program->bank->id)
          ->causedBy(auth()->user())
          ->performedOn($invoice)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Vendor'])
          ->log('created invoice');

        DB::commit();

        if ($invoice && $invoice->status == 'approved') {
          $invoice->update([
            'calculated_total_amount' => $invoice->invoice_total_amount,
          ]);
        }
      } catch (\Throwable $th) {
        info($th);
        // DB::rollBack();
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = str_replace(',', '', str_replace('Ksh', '', $row['total_amount']));
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = $row['type'];
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'] ? $row['due_date'] : null;
        $invoice_upload_report->attachments = $row['attachments'];
        $invoice_upload_report->tax_amount = $row['tax_amount'];
        $invoice_upload_report->total_payable = $row['total_payable'];
        $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
        $invoice_upload_report->description = 'Invalid Due Date/Pay Date Format';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->product_type = Program::VENDOR_FINANCING;
        $invoice_upload_report->product_code = Program::VENDOR_FINANCING_RECEIVABLE;
        $invoice_upload_report->save();
      }
    } else {
      if (!$row['invoice_number']) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = str_replace(',', '', str_replace('Ksh', '', $row['total_amount']));
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = $row['type'];
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $due_date;
        $invoice_upload_report->attachments = $row['attachments'];
        $invoice_upload_report->tax_amount = $row['tax_amount'];
        $invoice_upload_report->total_payable = $row['total_payable'];
        $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
        $invoice_upload_report->description = 'Invoice Number is required';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->product_type = Program::VENDOR_FINANCING;
        $invoice_upload_report->product_code = Program::VENDOR_FINANCING_RECEIVABLE;
        $invoice_upload_report->save();
      }
      $errored_invoice = InvoiceUploadReport::where('invoice_number', $row['invoice_number'])
        ->where('company_id', $this->company->id)
        ->first();
      if (!$errored_invoice) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = str_replace(',', '', str_replace('Ksh', '', $row['total_amount']));
        $invoice_upload_report->currency = $vendor_configuration->program->bank->default_currency;
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'] ? $row['due_date'] : null;
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->status = 'Failed';
        if ($invoice) {
          $invoice_upload_report->description = 'Invalid Invoice Number. Invoice already exists';
        }
        if (!$vendor_configuration) {
          $invoice_upload_report->description = 'Invalid Loan/OD Account Number';
        }

        if (now()->greaterThan(Carbon::parse($due_date))) {
          $invoice_upload_report->description = 'Invalid Invoice Due Date. Due date cannot be in the past.';
        }

        if (
          $row['due_date'] &&
          $row['invoice_date'] &&
          Carbon::parse($invoice_date)->greaterThan(Carbon::parse($due_date))
        ) {
          $invoice_upload_report->description = 'Invalid Invoice Date. Invoice date cannot be greater than due date.';
        }

        $invoice_upload_report->product_type = Program::VENDOR_FINANCING;
        $invoice_upload_report->product_code = Program::VENDOR_FINANCING_RECEIVABLE;

        $invoice_upload_report->save();
      } else {
        $errored_invoice->invoice_date = $invoice_date;
        $errored_invoice->net_invoice_amount = $row['total_amount'];
        $errored_invoice->currency = $vendor_configuration->program->bank->default_currency;
        $invoice_upload_report->due_date = $row['due_date'];
        $errored_invoice->loan_od_account = $row['payment_od_account'];
        $errored_invoice->pay_date = $row['due_date'] ? $row['due_date'] : null;
        $errored_invoice->status = 'Failed';
        $errored_invoice->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        if ($invoice) {
          $errored_invoice->description = 'Invalid Invoice Number. Invoice already exists';
        }
        if (!$vendor_configuration) {
          $errored_invoice->description = 'Invalid Loan/OD Account Number';
        }

        if (!$row['due_date']) {
          $errored_invoice->description = 'Invoice due date is required.';
        }

        if ($row['due_date'] && now()->greaterThan($row['due_date'])) {
          $errored_invoice->description = 'Invalid Invoice Due Date. Due date cannot be in the past.';
        }

        if (
          $row['due_date'] &&
          $row['invoice_date'] &&
          Carbon::parse($invoice_date)->greaterThan(Carbon::parse($due_date))
        ) {
          $errored_invoice->description = 'Invalid Invoice Date. Invoice date cannot be greater than due date.';
        }

        $invoice_upload_report->product_type = Program::VENDOR_FINANCING;
        $invoice_upload_report->product_code = Program::VENDOR_FINANCING_RECEIVABLE;

        $errored_invoice->save();
      }
    }
  }
}
