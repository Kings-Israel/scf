<?php

namespace App\Imports;

use App\Helpers\Helpers;
use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\Invoice;
use App\Models\InvoiceFee;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceUploadReport;
use App\Models\Program;
use App\Models\ProgramType;
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

class DealerInvoicesImport implements ToModel, WithMapping, WithHeadingRow, SkipsEmptyRows, WithEvents
{
  use Importable, SkipsFailures;

  public $data = 0;
  public $total_rows = 0;

  public $latest_batch_id = null;

  public function __construct(public Company $company, public string $program_type = 'Factoring')
  {
    $this->latest_batch_id = InvoiceUploadReport::where('company_id', $this->company->id)
      ->select('batch_id')
      ->latest()
      ->first();
  }

  public function map($row): array
  {
    if (
      !array_key_exists('invoice_unique_ref_no', $row) ||
      !array_key_exists('invoice_date_ddmmyyyy', $row) ||
      !array_key_exists('paymentod_account_no', $row) ||
      !array_key_exists('net_invoice_amount', $row) ||
      !array_key_exists('invoice_due_date_ddmmyyyy', $row) ||
      !array_key_exists('type_invoiceboenoa', $row) ||
      !array_key_exists('attachments_for_multiple_attachments_separate_name_with_commas_without_space', $row)
    ) {
      throw ValidationException::withMessages([
        'Invalid headers or missing column. Download and use the sample template.',
      ]);
    }

    $currencies = ['KES', 'Ksh'];
    $replacements = ['', ''];

    return [
      'invoice_number' => trim($row['invoice_unique_ref_no']),
      'invoice_date' =>
        $row['invoice_date_ddmmyyyy'] != null && $row['invoice_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['invoice_date_ddmmyyyy'])
          : null,
      'due_date' =>
        $row['invoice_due_date_ddmmyyyy'] != null && $row['invoice_due_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['invoice_due_date_ddmmyyyy'])
          : null,
      'payment_od_account' => $row['paymentod_account_no'],
      'total_amount' =>
        $row['net_invoice_amount'] === null || $row['net_invoice_amount'] === ''
          ? null
          : str_replace($currencies, $replacements, str_replace(',', '', $row['net_invoice_amount'])),
      'currency' => $this->company->bank->default_currency,
      'attachements' => $row['attachments_for_multiple_attachments_separate_name_with_commas_without_space'],
    ];
  }

  public function rules(): array
  {
    if ($this->program_type === Program::DEALER_FINANCING) {
      $account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) {
        $query
          ->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          })
          ->whereHas('anchor', function ($query) {
            $query->where('companies.id', $this->company->id);
          });
      })->pluck('payment_account_number');
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
      'currency' => ['required'],
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
    $vendor_configuration = ProgramVendorConfiguration::where(
      'payment_account_number',
      trim($row['payment_od_account'])
    )
      ->whereHas('program', function ($query) {
        $query->whereHas('anchor', function ($query) {
          $query->where('companies.id', $this->company->id);
        });
      })
      ->first();

    $invoice = Invoice::where('invoice_number', $row['invoice_number'])
      ->where('company_id', $vendor_configuration?->company_id)
      ->first();

    if (
      $row['invoice_number'] &&
      !$invoice &&
      $vendor_configuration &&
      $row['invoice_date'] &&
      $row['due_date'] &&
      Carbon::parse($row['due_date'])->greaterThanOrEqualTo(now()->format('Y-m-d'))
    ) {
      if ($this->program_type === Program::DEALER_FINANCING) {
        $company_id = $vendor_configuration->company_id;
      } else {
        $company_id = $this->company->id;
      }

      try {
        $drawdown_amount = 0;
        if ($vendor_configuration->withholding_tax || $vendor_configuration->withholding_vat) {
          $drawdown_amount =
            ($vendor_configuration->eligibility / 100) * $row['total_amount'] -
            ($vendor_configuration->withholding_tax / 100) * $row['total_amount'] -
            ($vendor_configuration->withholding_vat / 100) * $row['total_amount'];
        } else {
          $drawdown_amount = ($vendor_configuration->eligibility / 100) * $row['total_amount'];
        }

        DB::beginTransaction();
        $this->data++;
        $invoice = Invoice::create([
          'company_id' => $company_id,
          'program_id' => $vendor_configuration->program_id,
          'invoice_number' => $row['invoice_number'],
          'invoice_date' => $row['invoice_date'],
          'due_date' => $row['due_date'],
          'total_amount' => $row['total_amount'],
          'drawdown_amount' =>
            $vendor_configuration->program->programType->name == Program::DEALER_FINANCING ? $drawdown_amount : null,
          'calculated_total_amount' => $drawdown_amount,
          'currency' => array_key_exists('currency', $row)
            ? $row['currency']
            : $vendor_configuration->company->bank->default_currency,
          'financing_status' => 'pending',
        ]);

        if (
          $vendor_configuration->program->programType->name == Program::VENDOR_FINANCING &&
          ($vendor_configuration->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $vendor_configuration->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $invoice->update([
            'status' => 'submitted',
            'financing_status' => 'pending',
            'stage' => 'pending_maker',
            'buyer_id' => $vendor_configuration->buyer_id,
            'eligible_for_financing' => false,
          ]);
        }

        if ($vendor_configuration->withholding_tax > 0) {
          // Add Withholding Tax
          InvoiceFee::create([
            'invoice_id' => $invoice->id,
            'name' => 'Withholding Tax',
            'amount' =>
              ($vendor_configuration->withholding_tax / 100) *
              ($invoice->total + $invoice->total_invoice_taxes + $invoice->total_invoice_discount),
          ]);
        }

        if ($vendor_configuration->withholding_vat > 0) {
          // Add Withholding VAT
          InvoiceFee::create([
            'invoice_id' => $invoice->id,
            'name' => 'Withholding VAT',
            'amount' =>
              ($vendor_configuration->withholding_vat / 100) *
              ($invoice->total + $invoice->total_invoice_taxes + $invoice->total_invoice_discount),
          ]);
        }

        $invoice_setting = $this->company->invoiceSetting;

        if ($vendor_configuration->program->programType->name === Program::VENDOR_FINANCING) {
          $invoice_upload_report = new InvoiceUploadReport();
          $invoice_upload_report->company_id = $this->company->id;
          $invoice_upload_report->invoice_number = $invoice->invoice_number;
          $invoice_upload_report->status = 'Successful';
          $invoice_upload_report->invoice_date = $row['invoice_date'];
          $invoice_upload_report->net_invoice_amount = $row['total_amount'];
          $invoice_upload_report->currency = $row['currency'];
          $invoice_upload_report->due_date = $row['due_date'];
          $invoice_upload_report->type = 'Invoice';
          $invoice_upload_report->loan_od_account = $vendor_configuration->payment_account_number;
          $invoice_upload_report->pay_date = $row['due_date'];
          $invoice_upload_report->description = 'Invoice Uploaded successfully';
          $invoice_upload_report->product_type = Program::VENDOR_FINANCING;
          $invoice_upload_report->product_code = 'Factoring';
          $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
          $invoice_upload_report->save();

          if (
            auth()
              ->user()
              ->hasAllPermissions(['Approve Invoices - Level 1', 'Approve Invoices - Level 2'])
          ) {
            $invoice->update([
              'status' => 'submitted',
              'stage' => 'pending_maker',
            ]);

            $users = User::whereIn('id', $invoice->buyer->users->pluck('id'))->get();

            foreach ($users as $user) {
              if ($user->id !== auth()->id()) {
                SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
                  'id' => $invoice->id,
                  'type' => 'vendor_financing',
                ]);
              }
            }

            $invoice->buyer->notify(new InvoiceCreated($invoice));
          } else {
            if ($invoice_setting && !$invoice_setting->maker_checker_creating_updating) {
              $invoice->update([
                'status' => 'submitted',
                'stage' => 'pending_maker',
              ]);

              $users = User::whereIn('id', $invoice->buyer->users->pluck('id'))->get();

              foreach ($users as $user) {
                if ($user->id !== auth()->id()) {
                  SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
                    'id' => $invoice->id,
                    'type' => 'vendor_financing',
                  ]);
                }
              }

              $invoice->buyer->notify(new InvoiceCreated($invoice));
            } else {
              // requires maker checker approval
              // add maker approval
              $invoice->approvals()->create([
                'user_id' => auth()->id(),
              ]);

              $invoice->update([
                'status' => 'pending',
                'stage' => 'pending',
              ]);
            }
          }

          if ($invoice && $invoice->status == 'approved') {
            $invoice->update([
              'calculated_total_amount' => $invoice->invoice_total_amount,
            ]);
          }

          activity($invoice->program->bank->id)
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->withProperties([
              'ip' => request()->ip(),
              'device_info' => request()->userAgent(),
              'user_type' => 'Vendor',
            ])
            ->log('created invoice');
        } else {
          $dealer_financing_id = ProgramType::where('name', Program::DEALER_FINANCING)->first();

          // Check Authorization Matrix for invoice amount
          $matrix = CompanyAuthorizationMatrix::where('company_id', $this->company->id)
            ->where('min_pi_amount', '<=', $invoice->invoice_total_amount)
            ->where('max_pi_amount', '>=', $invoice->invoice_total_amount)
            ->where('program_type_id', $dealer_financing_id->id)
            ->where('status', 'active')
            ->first();

          if (!$matrix) {
            // Reduce number of uploaded invoices
            $this->data--;
            // Create error report
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['total_amount'];
            $invoice_upload_report->currency = $row['currency'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $vendor_configuration->payment_account_number;
            $invoice_upload_report->pay_date = $row['due_date'];
            $invoice_upload_report->description = 'Authorization Matrix rule was not found for this invoice amount.';
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->product_code = null;
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->save();

            // Delete invoice
            $invoice->delete();
          } else {
            $invoice->update([
              'status' => 'submitted',
              'stage' => 'pending_checker',
              'eligible_for_financing' => false,
            ]);

            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $invoice->invoice_number;
            $invoice_upload_report->status = 'Successful';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['total_amount'];
            $invoice_upload_report->currency = $row['currency'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $vendor_configuration->payment_account_number;
            $invoice_upload_report->pay_date = $row['due_date'];
            $invoice_upload_report->description = 'Invoice Uploaded successfully';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type =
              $vendor_configuration->program->programType->name === Program::DEALER_FINANCING;
            $invoice_upload_report->product_code = null;
            $invoice_upload_report->save();

            activity($invoice->program->bank->id)
              ->causedBy(auth()->user())
              ->performedOn($invoice)
              ->withProperties([
                'ip' => request()->ip(),
                'device_info' => request()->userAgent(),
                'user_type' => 'Anchor',
              ])
              ->log('created invoice');

            if ($invoice && $invoice->status == 'approved') {
              $invoice->update([
                'calculated_total_amount' => $invoice->invoice_total_amount,
              ]);
            }
          }
        }
        DB::commit();
      } catch (\Throwable $th) {
        info($th);
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = $row['total_amount'];
        $invoice_upload_report->currency = $row['currency'];
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = array_key_exists('type', $row) ? $row['type'] : null;
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->description = 'Invalid Due Date/Pay Date Format';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->product_type =
          $vendor_configuration->program->programType->name === Program::VENDOR_FINANCING
            ? Program::VENDOR_FINANCING
            : Program::DEALER_FINANCING;
        $invoice_upload_report->product_code =
          $vendor_configuration->program->programType->name === Program::DEALER_FINANCING ? null : 'Factoring';
        $invoice_upload_report->save();
      }
    } else {
      if ($invoice) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = $row['total_amount'];
        $invoice_upload_report->currency = $row['currency'];
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = 'Invoice';
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->description = 'Invoice Number already exists';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->product_type =
          $invoice->program->programType->name === Program::VENDOR_FINANCING
            ? Program::VENDOR_FINANCING
            : Program::DEALER_FINANCING;
        $invoice_upload_report->product_code =
          $invoice->program->programtype->name === Program::DEALER_FINANCING ? null : 'Factoring';
        $invoice_upload_report->save();
      }
      if (!$vendor_configuration) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = $row['total_amount'];
        $invoice_upload_report->currency = $row['currency'];
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = 'Invoice';
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->description = 'Invalid Loan/OD Account';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->save();
      }
      if (!$row['invoice_date']) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = $row['total_amount'];
        $invoice_upload_report->currency = $row['currency'];
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = 'Invoice';
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->description = 'Invoice Date is required';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->save();
      }
      if (!$row['due_date']) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = $row['total_amount'];
        $invoice_upload_report->currency = $row['currency'];
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = 'Invoice';
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->description = 'Pay Date is required';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->save();
      }
      if (Carbon::parse($row['due_date'])->lessThan(now()->format('Y-m-d'))) {
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'];
        $invoice_upload_report->net_invoice_amount = $row['total_amount'];
        $invoice_upload_report->currency = $row['currency'];
        $invoice_upload_report->due_date = $row['due_date'];
        $invoice_upload_report->type = 'Invoice';
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['due_date'];
        $invoice_upload_report->description = 'Invalid Pay Date. Must be greater than today.';
        $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->save();
      }
    }
  }
}
