<?php

namespace App\Imports;

use App\Jobs\BulkRequestFinancing;
use App\Jobs\SendMail;
use App\Models\BankProductsConfiguration;
use App\Models\BankTaxRate;
use App\Models\Company;
use App\Models\FinanceRequestApproval;
use App\Models\Invoice;
use App\Models\InvoiceFee;
use App\Models\InvoiceProcessing;
use App\Models\InvoiceUploadReport;
use App\Models\NoaTemplate;
use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramDiscount;
use App\Models\ProgramType;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use App\Models\User;
use App\Notifications\InvoiceCreated;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\BeforeImport;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Barryvdh\DomPDF\Facade\Pdf;

class DealerFinancing implements ToCollection, WithMapping, WithHeadingRow, SkipsEmptyRows, WithEvents
{
  use Importable, SkipsFailures;

  public $data = 0;
  public $total_rows = 0;

  public $latest_batch_id = null;

  public $leigble_invoices = [];

  public function __construct(public Company $company)
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
      !array_key_exists('payment_date_ddmmyyyy', $row) ||
      !array_key_exists('invoice_due_date_ddmmyyyy', $row) ||
      !array_key_exists('invoice_amount', $row) ||
      !array_key_exists('drawdown_amount', $row) ||
      !array_key_exists('dealer_code', $row) ||
      !array_key_exists('credit_to', $row)
    ) {
      throw ValidationException::withMessages([
        'Invalid headers or missing column. Download and use the sample template.',
      ]);
    }

    $currencies = ['KES', 'Ksh'];
    $replacements = ['', ''];

    return [
      'invoice_number' => $row['invoice_unique_ref_no'],
      'invoice_date' =>
        gettype($row['invoice_date_ddmmyyyy']) == 'integer'
          ? Date::excelToDateTimeObject($row['invoice_date_ddmmyyyy'])->format('Y-d-m')
          : ($row['invoice_date_ddmmyyyy'] != null
            ? Carbon::createFromFormat('d/m/Y', $row['invoice_date_ddmmyyyy'])->format('Y-m-d')
            : null),
      'payment_date' =>
        gettype($row['payment_date_ddmmyyyy']) == 'integer'
          ? Date::excelToDateTimeObject($row['payment_date_ddmmyyyy'])->format('Y-d-m')
          : ($row['payment_date_ddmmyyyy'] != null
            ? Carbon::createFromFormat('d/m/Y', $row['payment_date_ddmmyyyy'])->format('Y-m-d')
            : null),
      'due_date' =>
        gettype($row['invoice_due_date_ddmmyyyy']) == 'integer'
          ? Date::excelToDateTimeObject($row['invoice_due_date_ddmmyyyy'])->format('Y-d-m')
          : ($row['invoice_due_date_ddmmyyyy'] != null
            ? Carbon::createFromFormat('d/m/Y', $row['invoice_due_date_ddmmyyyy'])->format('Y-m-d')
            : null),
      'payment_od_account' => $row['paymentod_account_no'],
      'invoice_amount' => $row['invoice_amount'] === null || $row['invoice_amount'] === '' ? null : str_replace($currencies, $replacements, str_replace(',', '', $row['invoice_amount'])),
      'drawdown_amount' => $row['drawdown_amount'] === null || $row['drawdown_amount'] === '' ? null : str_replace($currencies, $replacements, str_replace(',', '', $row['drawdown_amount'])),
      'credit_to' => $row['credit_to'] === null || $row['credit_to'] === '' ? null : $row['credit_to'],
    ];
  }

  public function rules(): array
  {
    $account_numbers = ProgramVendorConfiguration::where('company_id', $this->company->id)->pluck(
      'payment_account_number'
    );

    return [
      'invoice_number' => [
        'required',
        Rule::unique('invoices')->where(function ($query) {
          return $query->where('company_id', $this->company->id);
        }),
      ],
      'invoice_date' => ['required'],
      'payment_date' => ['required'],
      'due_date' => ['required'],
      'payment_od_account' => ['required', 'in:' . $account_numbers],
      'invoice_amount' => ['required'],
      'drawdown_amount' => ['required'],
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

  public function collection(Collection $collection)
  {
    foreach ($collection->chunk(50) as $collect) {
      foreach ($collect as $row) {
        $vendor_configuration = ProgramVendorConfiguration::where(
          'payment_account_number',
          trim($row['payment_od_account'])
        )
          ->whereHas('company', function ($query) {
            $query->where('id', $this->company->id);
          })
          ->first();

        $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();

        $can_request = true;
        // Check if company has overdue invoices
        if ($vendor_configuration && $this->company->hasOverdueInvoices()) {
          // Get limit block overdue days configuration from program
          $limit_block_overdue_days = $vendor_configuration->program->vendorDiscountDetails
            ->where('company_id', $this->company->id)
            ->first()->limit_block_overdue_days;

          // Get invoices that are past block overdue days
          $invoices = Invoice::dealerFinancing()
            ->where('company_id', $this->company->id)
            ->whereDate('due_date', '<', now()->format('Y-m-d'))
            ->where('financing_status', 'financed')
            ->get()
            ->filter(function ($value) use ($limit_block_overdue_days) {
              return Carbon::parse($value->due_date)->diffInDays(now()->format('Y-m-d')) > $limit_block_overdue_days;
            })
            ->count();

          if ($invoices > 0) {
            $can_request = false;
          }
        }

        $legible_amount = $row['invoice_amount'];

        // Get legible amount
        if ($vendor_configuration) {
          $eligibility = $vendor_configuration->eligibility;

          $legible_amount = ($eligibility / 100) * $row['invoice_amount'];
        }

        if (
          $can_request &&
          $row['invoice_number'] &&
          !$invoice &&
          $vendor_configuration &&
          $row['invoice_date'] &&
          $row['payment_date'] &&
          $row['due_date'] &&
          $row['invoice_amount'] &&
          $row['drawdown_amount'] &&
          (float) $row['drawdown_amount'] <= (float) $legible_amount &&
          Carbon::parse($row['due_date'])->greaterThanOrEqualTo(now()->format('Y-m-d')) &&
          Carbon::parse($row['due_date'])->greaterThan(Carbon::parse($row['invoice_date'])) &&
          Carbon::parse($row['payment_date'])->lessThan(Carbon::parse($row['due_date']))
        ) {
          $company_id = $this->company->id;

          try {
            $drawdown_amount = $row['drawdown_amount'];

            DB::beginTransaction();
            $this->data++;
            $invoice = Invoice::create([
              'company_id' => $company_id,
              'program_id' => $vendor_configuration->program_id,
              'invoice_number' => $row['invoice_number'],
              'invoice_date' => $row['invoice_date'],
              'due_date' => $row['due_date'],
              'payment_date' => $row['payment_date'],
              'total_amount' => $row['invoice_amount'],
              'drawdown_amount' =>
                $vendor_configuration->program->programType->name == Program::DEALER_FINANCING
                  ? $drawdown_amount
                  : null,
              'calculated_total_amount' => $drawdown_amount,
              'currency' => $vendor_configuration->company->bank->default_currency,
              'financing_status' => 'pending',
              'eligibility' => $vendor_configuration->eligibility,
              'discount_charge_type' => $vendor_configuration->program->discountDetails->first()->discount_type,
              'fee_charge_type' => $vendor_configuration->program->discountDetails->first()->fee_type,
            ]);

            if ($vendor_configuration->withholding_tax > 0) {
              // Add Withholding Tax
              InvoiceFee::create([
                'invoice_id' => $invoice->id,
                'name' => 'Withholding Tax',
                'amount' =>
                  ($vendor_configuration->withholding_tax / 100) *
                  ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount),
              ]);
            }

            if ($vendor_configuration->withholding_vat > 0) {
              // Add Withholding VAT
              InvoiceFee::create([
                'invoice_id' => $invoice->id,
                'name' => 'Withholding VAT',
                'amount' =>
                  ($vendor_configuration->withholding_vat / 100) *
                  ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount),
              ]);
            }

            $invoice->update([
              'status' => 'pending',
              'stage' => 'pending',
              'eligible_for_financing' => false,
              'credit_to' => $row['credit_to'],
            ]);

            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Successful';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Invoice Uploaded successfully';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();

            $invoice_setting = $this->company->invoiceSetting;

            if ($invoice_setting && !$invoice_setting->maker_checker_creating_updating) {
              $invoice->update([
                'status' => 'submitted',
                'stage' => 'pending_maker',
              ]);

              $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

              foreach ($users as $user) {
                SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
                  'id' => $invoice->id,
                  'type' => 'dealer_financing',
                ]);
              }

              $invoice->program->anchor->notify(new InvoiceCreated($invoice));
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

              $users = User::whereIn('id', $invoice->company->users->pluck('id'))->get();

              foreach ($users as $user) {
                SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
                  'id' => $invoice->id,
                  'type' => 'dealer_financing',
                ]);
              }
            }

            if ($invoice && $invoice->status === 'approved') {
              $invoice->update([
                'eligible_for_financing' => true,
                'calculated_total_amount' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? $invoice->invoice_total_amount
                    : $invoice->drawdown_amount,
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

            DB::commit();
          } catch (\Throwable $th) {
            info($th);
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Invalid Due Date/Pay Date Format';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
        } else {
          if ($invoice) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Invoice Number already exists';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (!$can_request) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'You have overdue invoices in this program that require repayment.';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (!$vendor_configuration) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Invalid Loan/OD Account';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (!$row['invoice_date']) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Invoice Date is required';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (!$row['due_date']) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Due Date is required';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (!$row['payment_date']) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Pay Date is required';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if ((float) $row['drawdown_amount'] > (float) $legible_amount) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Max drawdown amount is ' . $eligibility . '% of invoice amount';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (Carbon::parse($row['due_date'])->lessThan(now()->format('Y-m-d'))) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Invalid Pay Date. Must be greater than today.';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (Carbon::parse($row['due_date'])->lessThan(Carbon::parse($row['payment_date']))) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Due date cannot be before the Payment Date';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
          if (Carbon::parse($row['due_date'])->lessThan(Carbon::parse($row['invoice_date']))) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'];
            $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
            $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
            $invoice_upload_report->due_date = $row['due_date'];
            $invoice_upload_report->pay_date = $row['payment_date'];
            $invoice_upload_report->type = 'Invoice';
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->description = 'Due date cannot be before the Invoice Date';
            $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
            $invoice_upload_report->product_type = Program::DEALER_FINANCING;
            $invoice_upload_report->save();
          }
        }

        if (collect($this->leigble_invoices)->count() > 0) {
          Invoice::whereIn('id', $this->leigble_invoices)->chunk(50, function ($invoices) {
            foreach ($invoices as $invoice) {
              if ($invoice->canRequestFinancing()) {
                InvoiceProcessing::create([
                  'company_id' => $this->company->id,
                  'invoice_id' => $invoice->id,
                  'action' => 'requesting financing',
                  'status' => 'pending',
                  'data' => [
                    'drawdown_amount' => $invoice->drawdown_amount
                      ? $invoice->drawdown_amount
                      : $invoice->invoice_total_amount,
                    'payment_request_date' => $invoice->payment_date,
                    'due_date' => $invoice->due_date,
                  ],
                ]);
              }
            }
          });

          BulkRequestFinancing::dispatchAfterResponse($this->company);
        }
      }
    }
  }

  // public function model(array $row)
  // {
  //   $vendor_configuration = ProgramVendorConfiguration::where(
  //     'payment_account_number',
  //     trim($row['payment_od_account'])
  //   )
  //     ->whereHas('company', function ($query) {
  //       $query->where('id', $this->company->id);
  //     })
  //     ->first();

  //   $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();

  //   $can_request = true;
  //   // Check if company has overdue invoices
  //   if ($vendor_configuration && $this->company->hasOverdueInvoices()) {
  //     // Get limit block overdue days configuration from program
  //     $limit_block_overdue_days = $vendor_configuration->program->vendorDiscountDetails->where('company_id', $this->company->id)->first()
  //       ->limit_block_overdue_days;

  //     // Get invoices that are past block overdue days
  //     $invoices = Invoice::dealerFinancing()
  //       ->where('company_id', $this->company->id)
  //       ->whereDate('due_date', '<', now()->format('Y-m-d'))
  //       ->where('financing_status', 'financed')
  //       ->get()
  //       ->filter(function ($value) use ($limit_block_overdue_days) {
  //         return Carbon::parse($value->due_date)->diffInDays(now()->format('Y-m-d')) > $limit_block_overdue_days;
  //       })
  //       ->count();

  //     if ($invoices > 0) {
  //       $can_request = false;
  //     }
  //   }

  //   $legible_amount = $row['invoice_amount'];

  //   // Get legible amount
  //   if ($vendor_configuration) {
  //     $eligibility = $vendor_configuration->eligibility;

  //     $legible_amount = ($eligibility / 100) * $row['invoice_amount'];
  //   }

  //   if (
  //     $can_request &&
  //     $row['invoice_number'] &&
  //     !$invoice &&
  //     $vendor_configuration &&
  //     $row['invoice_date'] &&
  //     $row['payment_date'] &&
  //     $row['due_date'] &&
  //     $row['invoice_amount'] &&
  //     $row['drawdown_amount'] &&
  //     (double) $row['drawdown_amount'] <= (double) $legible_amount &&
  //     Carbon::parse($row['due_date'])->greaterThanOrEqualTo(now()->format('Y-m-d')) &&
  //     Carbon::parse($row['due_date'])->greaterThan(Carbon::parse($row['invoice_date'])) &&
  //     Carbon::parse($row['payment_date'])->lessThan(Carbon::parse($row['due_date']))
  //   ) {
  //     $company_id = $this->company->id;

  //     try {
  //       $drawdown_amount = $row['drawdown_amount'];

  //       DB::beginTransaction();
  //       $this->data++;
  //       $invoice = Invoice::create([
  //         'company_id' => $company_id,
  //         'program_id' => $vendor_configuration->program_id,
  //         'invoice_number' => $row['invoice_number'],
  //         'invoice_date' => $row['invoice_date'],
  //         'due_date' => $row['due_date'],
  //         'payment_date' => $row['payment_date'],
  //         'total_amount' => $row['invoice_amount'],
  //         'drawdown_amount' => $vendor_configuration->program->programType->name == 'Dealer Financing' ? $drawdown_amount : NULL,
  //         'calculated_total_amount' => $drawdown_amount,
  //         'currency' => array_key_exists('currency', $row) ? $row['currency'] : $vendor_configuration->company->default_currency,
  //         'financing_status' => 'pending',
  //         'eligibility' => $vendor_configuration->eligibility,
  //         'discount_charge_type' => $vendor_configuration->program->discountDetails->first()->discount_type,
  //         'fee_charge_type' => $vendor_configuration->program->discountDetails->first()->fee_type,
  //       ]);

  //       if ($vendor_configuration->program->programType->name == 'Vendor Financing' && ($vendor_configuration->program->programCode->name == 'Factoring With Recourse' || $vendor_configuration->program->programCode->name == 'Factoring Without Recourse')) {
  //         $invoice->update([
  //           'status' => 'submitted',
  //           'financing_status' => 'pending',
  //           'stage' => 'pending_maker',
  //           'buyer_id' => $vendor_configuration->buyer_id,
  //           'eligible_for_financing' => false,
  //         ]);
  //       } else {
  //         $invoice->update([
  //           'status' => 'approved',
  //           'stage' => 'approved',
  //           'eligible_for_financing' => true,
  //           'credit_to' => $row['credit_to'],
  //         ]);
  //       }

  //       if ($vendor_configuration->withholding_tax > 0) {
  //         // Add Withholding Tax
  //         InvoiceFee::create([
  //           'invoice_id' => $invoice->id,
  //           'name' => 'Withholding Tax',
  //           'amount' => ($vendor_configuration->withholding_tax / 100) * $invoice->invoice_total_amount,
  //         ]);
  //       }

  //       if ($vendor_configuration->withholding_vat > 0) {
  //         // Add Withholding VAT
  //         InvoiceFee::create([
  //           'invoice_id' => $invoice->id,
  //           'name' => 'Withholding VAT',
  //           'amount' => ($vendor_configuration->withholding_vat / 100) * $invoice->invoice_total_amount,
  //         ]);
  //       }

  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Successful';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Invoice Uploaded successfully';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();

  //       $invoice_setting = $this->company->invoiceSetting;

  //       if ($vendor_configuration->program->programType->name == 'Vendor Financing') {
  //         if (
  //           auth()
  //             ->user()
  //             ->hasAllPermissions(['Manage Invoices', 'Invoice Checker'])
  //         ) {
  //           $invoice->approvals()->create([
  //             'user_id' => auth()->id(),
  //           ]);

  //           $invoice->update([
  //             'status' => 'submitted',
  //             'stage' => 'pending_checker',
  //           ]);

  //           $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

  //           foreach ($users as $user) {
  //             SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
  //               'id' => $invoice->id,
  //               'type' => 'vendor_financing',
  //             ]);
  //           }

  //           $invoice->program->anchor->notify(new InvoiceCreated($invoice));
  //         } else {
  //           if ($invoice_setting && !$invoice_setting->maker_checker_creating_updating) {
  //             $invoice->approvals()->create([
  //               'user_id' => auth()->id(),
  //             ]);

  //             $invoice->update([
  //               'status' => 'submitted',
  //               'stage' => 'pending_checker',
  //             ]);

  //             $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

  //             foreach ($users as $user) {
  //               SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
  //                 'id' => $invoice->id,
  //                 'type' => 'vendor_financing',
  //               ]);
  //             }

  //             $invoice->program->anchor->notify(new InvoiceCreated($invoice));
  //           } else {
  //             // requires maker checker approval
  //             // add maker approval
  //             $invoice->approvals()->create([
  //               'user_id' => auth()->id(),
  //             ]);

  //             $invoice->update([
  //               'status' => 'pending',
  //               'stage' => 'pending',
  //             ]);
  //           }
  //         }
  //       } else {
  //         $invoice->update([
  //           'status' => 'approved',
  //           'stage' => 'approved',
  //           'pi_number' => 'PI_' . $invoice->id,
  //         ]);
  //       }

  //       activity($invoice->program->bank->id)
  //         ->causedBy(auth()->user())
  //         ->performedOn($invoice)
  //         ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Vendor'])
  //         ->log('created invoice');

  //       $vendor_bank_details = ProgramBankDetails::where('account_number', $invoice->credit_to)->first();

  //       if (!$vendor_bank_details) {
  //         $vendor_bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
  //       }

  //       // Get difference in days between anchor payment and repayment date
  //       $diff = Carbon::parse($invoice->payment_date)->diffInDays(Carbon::parse($invoice->due_date));

  //       // Get vendor discount details
  //       $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company_id)
  //         ->where('program_id', $invoice->program_id)
  //         ->where('from_day', '<=', $diff)
  //         ->where('to_day', '>=', $diff)
  //         ->latest()
  //         ->first();

  //       if (!$vendor_discount_details) {
  //         $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company_id)
  //           ->where('program_id', $invoice->program_id)
  //           ->latest()
  //           ->first();
  //       }

  //       // Get fees for vendor
  //       $vendor_fees = ProgramVendorFee::where('company_id', $invoice->company_id)
  //         ->where('program_id', $invoice->program_id)
  //         ->get();
  //       // Get Tax on Discount Value
  //       $tax_on_discount = ProgramDiscount::where('program_id', $invoice->program_id)->first()?->tax_on_discount;

  //       $eligibility = $vendor_configuration->eligibility;
  //       $total_amount = $invoice->drawdown_amount;

  //       $total_roi = $vendor_discount_details ? $vendor_discount_details->total_roi : 0;
  //       $legible_amount = $total_amount;

  //       // Fee charges
  //       $fees_amount = 0;
  //       $fees_tax_amount = 0;
  //       if ($vendor_fees->count() > 0) {
  //         foreach ($vendor_fees as $fee) {
  //           if ($fee->type == 'amount') {
  //             $fees_amount += $fee->value;
  //           }
  //           if ($fee->type == 'percentage') {
  //             $fees_amount += ($fee->value / 100) * $legible_amount;
  //           }
  //           if ($fee->type == 'per amount') {
  //             $amounts = floor($legible_amount / $fee->per_amount);
  //             $fees_amount += $amounts * $fee->value;
  //           }
  //           if ($fee->taxes) {
  //             $fees_tax_amount += ($fee->taxes / 100) * $fees_amount;
  //           }
  //         }
  //       }

  //       $discount =
  //         // ($eligibility / 100) *
  //         Str::replace(',', '', $invoice->drawdown_amount) *
  //         ($total_roi / 100) *
  //         (Carbon::parse($invoice->payment_date)->diffInDays(Carbon::parse($invoice->due_date)) / 365);

  //       // Tax on discount
  //       $discount_tax_amount = 0;
  //       if ($discount > 0 && $tax_on_discount && $tax_on_discount > 0) {
  //         $discount_tax_amount = ($tax_on_discount / 100) * $discount;
  //       }

  //       // Check if program is front ended or rear ended
  //       if ($invoice->program->discountDetails->first()->discount_type == 'Front Ended') {
  //         if ($invoice->drawdown_amount) {
  //           $amount = $invoice->drawdown_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
  //         } else {
  //           $amount = $invoice->total_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
  //         }
  //       } else {
  //         if ($invoice->drawdown_amount) {
  //           $amount = $invoice->drawdown_amount + $discount + $fees_amount + $fees_tax_amount + $discount_tax_amount;
  //         } else {
  //           $amount = $invoice->total_amount + $discount + $fees_amount + $fees_tax_amount + $discount_tax_amount;
  //         }
  //       }

  //       $reference_number = '';

  //       $words = explode(' ', $invoice->company->name);
  //       $acronym = '';

  //       foreach ($words as $w) {
  //         $acronym .= mb_substr($w, 0, 1);
  //       }

  //       if ($invoice->program->programType->name == 'Vendor Financing') {
  //         if ($invoice->program->programCode->name == 'Vendor Financing Receivable') {
  //           $reference_number = 'VFR' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
  //         } else {
  //           $reference_number = 'FR' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
  //         }
  //       } else {
  //         $reference_number = 'DF' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
  //       }

  //       $payment_request = PaymentRequest::create([
  //         'reference_number' => $reference_number,
  //         'invoice_id' => $invoice->id,
  //         'amount' => round($amount, 2),
  //         'processing_fee' => round($fees_amount, 2),
  //         'payment_request_date' => Carbon::parse($invoice->payment_date)->format('Y-m-d'),
  //       ]);

  //       $dealer_financing = ProgramType::where('name', 'Dealer Financing')->first();

  //       $discount_income_bank_account = null;
  //       $fees_income_bank_account = null;
  //       $tax_income_bank_account = null;

  //       // Get Bank Configured Receivable Accounts
  //       $discount_income_bank_account = BankProductsConfiguration::where(
  //         'bank_id',
  //         $payment_request->invoice->program->bank->id
  //       )
  //         ->where('product_type_id', $dealer_financing->id)
  //         ->where('product_code_id', null)
  //         ->where('name', 'Discount Income Account')
  //         ->first();
  //       $fees_income_bank_account = BankProductsConfiguration::where(
  //         'bank_id',
  //         $payment_request->invoice->program->bank->id
  //       )
  //         ->where('product_type_id', $dealer_financing->id)
  //         ->where('product_code_id', null)
  //         ->where('name', 'Fee Income Account')
  //         ->first();
  //       $tax_income_bank_account = BankProductsConfiguration::where('bank_id', $payment_request->invoice->program->bank->id)
  //         ->where('product_type_id', $dealer_financing->id)
  //         ->where('product_code_id', null)
  //         ->where('name', 'Tax Account Number')
  //         ->first();

  //       // Credit to vendor's account
  //       $payment_request->paymentAccounts()->create([
  //         'account' => $vendor_bank_details->account_number,
  //         'account_name' => $vendor_bank_details->name_as_per_bank,
  //         'amount' => round($amount, 2),
  //         'type' => 'vendor_account',
  //         'description' => 'Vendor Account',
  //       ]);

  //       // Credit discount to discount account
  //       $payment_request->paymentAccounts()->create([
  //         'account' => $discount_income_bank_account ? $discount_income_bank_account->value : 'Disc_Inc_Acc',
  //         'account_name' => $discount_income_bank_account ? $discount_income_bank_account->name : 'Discount Income Account',
  //         'amount' => round($discount, 2),
  //         'type' => 'discount',
  //         'description' => 'Discount',
  //       ]);

  //       // Credit Fees to Fees Income Account
  //       if ($vendor_fees->count() > 0) {
  //         foreach ($vendor_fees as $fee) {
  //           if ($fee->type == 'amount') {
  //             $fees_amount = $fee->value;
  //             if ($fees_amount > 0) {
  //               $payment_request->paymentAccounts()->create([
  //                 'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
  //                 'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
  //                 'amount' => round($fees_amount, 2),
  //                 'type' => 'program_fees',
  //                 'title' => $fee->fee_name,
  //                 'description' => 'Fees for ' . $fee->fee_name,
  //               ]);
  //             }
  //           }
  //           if ($fee->type == 'percentage') {
  //             $fees_amount = ($fee->value / 100) * $legible_amount;
  //             if ($fees_amount > 0) {
  //               $payment_request->paymentAccounts()->create([
  //                 'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
  //                 'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
  //                 'amount' => round($fees_amount, 2),
  //                 'type' => 'program_fees',
  //                 'title' => $fee->fee_name,
  //                 'description' => 'Fees for ' . $fee->fee_name,
  //               ]);
  //             }
  //           }
  //           if ($fee->type == 'per amount') {
  //             $amounts = floor($legible_amount / $fee->per_amount);
  //             $fees_amount = $amounts * $fee->value;
  //             if ($fees_amount > 0) {
  //               $payment_request->paymentAccounts()->create([
  //                 'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
  //                 'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
  //                 'amount' => round($fees_amount, 2),
  //                 'type' => 'program_fees',
  //                 'title' => $fee->fee_name,
  //                 'description' => 'Fees for ' . $fee->fee_name,
  //               ]);
  //             }
  //           }
  //         }
  //       }

  //       // Credit Fee Taxes to Fees taxes Account
  //       $fees_amount = 0;
  //       $fees_tax_amount = 0;
  //       if ($vendor_fees->count() > 0) {
  //         foreach ($vendor_fees as $fee) {
  //           if ($fee->type == 'amount') {
  //             $fees_amount += $fee->value;
  //           }
  //           if ($fee->type == 'percentage') {
  //             $fees_amount += ($fee->value / 100) * $legible_amount;
  //           }
  //           if ($fee->type == 'per amount') {
  //             $amounts = floor($legible_amount / $fee->per_amount);
  //             $fees_amount += $amounts * $fee->value;
  //           }

  //           if ($fee->taxes) {
  //             $fees_tax_amount += ($fee->taxes / 100) * $fees_amount;

  //             $tax_income_account = BankTaxRate::where('bank_id', $invoice->program->bank_id)
  //               ->where('value', $fee->taxes)
  //               ->where('status', 'active')
  //               ->first();

  //             if ($tax_income_account) {
  //               $payment_request->paymentAccounts()->create([
  //                 'account' => $tax_income_account->account_no,
  //                 'account_name' => 'Tax Income Bank Account',
  //                 'amount' => round($fees_tax_amount, 2),
  //                 'type' => 'tax_on_fees',
  //                 'description' => 'Tax on Fees for ' . $fee->fee_name,
  //               ]);
  //             } else {
  //               $payment_request->paymentAccounts()->create([
  //                 'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
  //                 'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
  //                 'amount' => round($fees_tax_amount, 2),
  //                 'type' => 'tax_on_fees',
  //                 'description' => 'Tax on Fees for ' . $fee->fee_name,
  //               ]);
  //             }
  //           }
  //         }
  //       }

  //       // Credit to tax on discount account
  //       if ($tax_on_discount > 0) {
  //         if ($discount_tax_amount > 0) {
  //           if ($tax_income_bank_account) {
  //             $payment_request->paymentAccounts()->create([
  //               'account' => $tax_income_bank_account->value,
  //               'account_name' => $tax_income_bank_account->name,
  //               'amount' => round($discount_tax_amount, 2),
  //               'type' => 'tax_on_discount',
  //               'description' => 'Tax on Discount',
  //             ]);
  //           } else {
  //             $payment_request->paymentAccounts()->create([
  //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fees Income Bank Account',
  //               'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Bank Account',
  //               'amount' => round($discount_tax_amount, 2),
  //               'type' => 'tax_on_discount',
  //               'description' => 'Tax on Discount',
  //             ]);
  //           }
  //         }
  //       }

  //       // Check if requires checker approval
  //       $purchase_order_setting = $this->company->purchaseOrderSetting;

  //       $invoice->update([
  //         'eligible_for_financing' => false,
  //         'financing_status' => 'submitted',
  //         'calculated_total_amount' => $invoice->drawdown_amount,
  //       ]);

  //       // Update Program and Company Pipeline and Utilized Amounts
  //       $invoice->company->update([
  //         'pipeline_amount' => $invoice->company->pipeline_amount + $invoice->calculated_total_amount,
  //       ]);

  //       $invoice->program->update([
  //         'pipeline_amount' => $invoice->program->pipeline_amount + $invoice->calculated_total_amount,
  //       ]);

  //       $vendor_configuration->update([
  //         'pipeline_amount' => $vendor_configuration->pipeline_amount + $invoice->calculated_total_amount,
  //       ]);

  //       if ($purchase_order_setting->request_finance_add_repayment) {
  //         FinanceRequestApproval::create([
  //           'payment_request_id' => $payment_request->id,
  //           'user_id' => auth()->id(),
  //         ]);
  //       } else {
  //         // Check if auto approve finance requests is enabled
  //         if ($vendor_configuration->auto_approve_finance) {
  //           $payment_request->update([
  //             'status' => 'approved',
  //           ]);

  //           // Create CBS Transactions for the payment request
  //           $payment_request->createCbsTransactions();
  //         }

  //         // $invoice->program->bank->notify(new PaymentRequestNotification($payment_request));

  //         activity($invoice->program->bank->id)
  //           ->causedBy(auth()->user())
  //           ->performedOn($payment_request)
  //           ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Dealer'])
  //           ->log('initiated drawdown');

  //         if ($invoice->status == 'approved') {
  //           $invoice->update([
  //             'calculated_total_amount' => $invoice->drawdown_amount,
  //           ]);
  //         }

  //         $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
  //           ->where('status', 'active')
  //           ->where('bank_id', $invoice->program->bank_id)
  //           ->first();

  //         if (!$noa_text) {
  //           $noa_text = NoaTemplate::where('product_type', 'generic')
  //             ->where('status', 'active')
  //             ->first();
  //         }

  //         // Send NOA
  //         $data = [];
  //         $data['{date}'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
  //         $data['{buyerName}'] = $invoice->company->name;
  //         $data['{anchorName}'] = $invoice->program->anchor->name;
  //         $data['{company}'] = $invoice->company->name;
  //         $data['{anchorCompanyUniqueID}'] = $invoice->program->anchor->unique_identification_number;
  //         $data['{time}'] = now()->format('d M Y');
  //         $data['{agreementDate}'] = now()->format('d M Y');
  //         $data['{contract}'] = '';
  //         $data['{anchorAccountName}'] = $invoice->program->bankDetails->first()->account_name;
  //         $data['{anchorAccountNumber}'] = $invoice->program->bankDetails->first()->account_number;
  //         $data['{anchorCustomerId}'] = '';
  //         $data['{anchorBranch}'] = $invoice->program->anchor->branch_code;
  //         $data['{anchorIFSCCode}'] = '';
  //         $data['{anchorAddress}'] =
  //           $invoice->program->anchor->postal_code .
  //           ' ' .
  //           $invoice->program->anchor->address .
  //           ' ' .
  //           $invoice->program->anchor->city .
  //           ' ';
  //         $data['{penalnterestRate}'] = $vendor_discount_details?->penal_discount_on_principle;
  //         $data['{sellerName}'] = $invoice->company->name;

  //         $noa = '';

  //         // Notify Bank of new payment request
  //         foreach ($payment_request->invoice->program->bank->users as $bank_user) {
  //           if ($noa_text != null) {
  //             $noa = $noa_text->body;
  //             foreach ($data as $key => $val) {
  //               $noa = str_replace($key, $val, $noa);
  //             }

  //             $pdf = Pdf::loadView('pdf.noa', [
  //               'data' => $noa,
  //             ])->setPaper('a4', 'landscape');
  //           }

  //           SendMail::dispatchAfterResponse($bank_user->email, 'PaymentRequested', [
  //             'payment_request_id' => $payment_request->id,
  //             'link' => config('app.url') . '/' . $payment_request->invoice->program->bank->url,
  //             'type' => 'dealer_financing',
  //             'noa' => $noa_text != null ? $pdf->output() : null,
  //           ]);
  //         }
  //       }

  //       DB::commit();

  //       if ($invoice && $invoice->status == 'approved') {
  //         $invoice->update([
  //           'calculated_total_amount' => $invoice->program->programType->name == 'Vendor Financing' ? $invoice->invoice_total_amount : $invoice->drawdown_amount,
  //         ]);
  //       }
  //     } catch (\Throwable $th) {
  //       info($th);
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Invalid Due Date/Pay Date Format';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //   } else {
  //     if ($invoice) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Invoice Number already exists';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (!$can_request) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'You have overdue invoices in this program that require repayment.';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (!$vendor_configuration) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Invalid Loan/OD Account';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (!$row['invoice_date']) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Invoice Date is required';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (!$row['due_date']) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Due Date is required';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (!$row['payment_date']) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Pay Date is required';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if ((double) $row['drawdown_amount'] > (double) $legible_amount) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Max drawdown amount is ' . $eligibility .'% of invoice amount';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (Carbon::parse($row['due_date'])->lessThan(now()->format('Y-m-d'))) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Invalid Pay Date. Must be greater than today.';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (Carbon::parse($row['due_date'])->lessThan(Carbon::parse($row['payment_date']))) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Due date cannot be before the Payment Date';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //     if (Carbon::parse($row['due_date'])->lessThan(Carbon::parse($row['invoice_date']))) {
  //       $invoice_upload_report = new InvoiceUploadReport();
  //       $invoice_upload_report->company_id = $this->company->id;
  //       $invoice_upload_report->invoice_number = $row['invoice_number'];
  //       $invoice_upload_report->status = 'Failed';
  //       $invoice_upload_report->invoice_date = $row['invoice_date'];
  //       $invoice_upload_report->net_invoice_amount = $row['invoice_amount'];
  //       $invoice_upload_report->drawdown_amount = $row['drawdown_amount'];
  //       $invoice_upload_report->due_date = $row['due_date'];
  //       $invoice_upload_report->pay_date = $row['payment_date'];
  //       $invoice_upload_report->type = 'Invoice';
  //       $invoice_upload_report->loan_od_account = $row['payment_od_account'];
  //       $invoice_upload_report->description = 'Due date cannot be before the Invoice Date';
  //       $invoice_upload_report->batch_id = $this->latest_batch_id ? $this->latest_batch_id->batch_id + 1 : 1;
  //       $invoice_upload_report->save();
  //     }
  //   }
  // }
}
