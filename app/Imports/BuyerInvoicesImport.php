<?php

namespace App\Imports;

use App\Helpers\Helpers;
use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\InvoiceFee;
use App\Models\InvoiceTax;
use App\Models\CompanyUser;
use App\Models\ImportError;
use Illuminate\Validation\Rule;
use App\Models\InvoiceImportError;
use Illuminate\Support\Collection;
use App\Models\InvoiceUploadReport;
use App\Models\ProgramType;
use App\Notifications\InvoiceUpdated;
use App\Models\ProgramVendorBankDetail;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class BuyerInvoicesImport implements
  ToCollection,
  WithHeadingRow,
  SkipsEmptyRows,
  SkipsOnFailure,
  WithEvents,
  WithMapping
{
  use Importable, SkipsFailures;

  protected $programs = [];
  protected $company = null;

  public $data = 0;
  public $total_rows = 0;

  public function __construct(array $programs, Company $company = null, public string $type)
  {
    $this->programs = $programs;
    $this->company = $company;
  }

  public function map($row): array
  {
    if (
      !array_key_exists('invoice_unique_ref_no', $row) ||
      !array_key_exists('invoice_date_ddmmyyyy', $row) ||
      !array_key_exists('paymentod_account_no', $row) ||
      !array_key_exists('type_invoiceboenoa', $row) ||
      !array_key_exists('net_invoice_amount', $row) ||
      !array_key_exists('invoice_due_date_ddmmyyyy', $row) ||
      !array_key_exists('pay_date_ddmmyyyy', $row) ||
      !array_key_exists('tax_amount', $row) ||
      !array_key_exists('total_payable', $row) ||
      !array_key_exists('credit_note_amount', $row) ||
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
      'total_amount' => str_replace($currencies, $replacements, str_replace(',', '', $row['net_invoice_amount'])),
      'due_date' =>
        $row['invoice_due_date_ddmmyyyy'] != null && $row['invoice_due_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['invoice_due_date_ddmmyyyy'])
          : null,
      'type' => $row['type_invoiceboenoa'],
      'payment_od_account' => $row['paymentod_account_no'],
      'pay_date' =>
        $row['pay_date_ddmmyyyy'] != null && $row['pay_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['pay_date_ddmmyyyy'])
          : null,
      'attachments' => $row['attachments_for_multiple_attachments_separate_name_with_commas_without_space'],
      'tax_amount' =>
        $row['tax_amount'] === null || $row['tax_amount'] === ''
          ? null
          : str_replace($currencies, $replacements, str_replace(',', '', $row['tax_amount'])),
      'total_payable' =>
        $row['total_payable'] === null || $row['total_payable'] === ''
          ? null
          : str_replace($currencies, $replacements, str_replace(',', '', $row['total_payable'])),
      'credit_note_amount' =>
        $row['credit_note_amount'] === null || $row['credit_note_amount'] === ''
          ? null
          : str_replace($currencies, $replacements, str_replace(',', '', $row['credit_note_amount'])),
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

  public function collection(Collection $rows)
  {
    $latest_batch_id = InvoiceUploadReport::where('company_id', $this->company->id)
      ->select('batch_id')
      ->latest()
      ->first();

    foreach ($rows as $row) {
      try {
        if (
          $row['invoice_number'] &&
          $row['invoice_date'] &&
          $row['total_amount'] &&
          $row['due_date'] &&
          $row['type'] &&
          $row['payment_od_account'] &&
          $row['invoice_date']
        ) {
          if ($this->type == Program::DEALER_FINANCING) {
            $vendor_configuration = ProgramVendorConfiguration::where(
              'payment_account_number',
              trim($row['payment_od_account'])
            )
              ->whereHas('program', function ($query) {
                $query
                  ->whereHas('anchor', function ($query) {
                    $query->where('companies.id', $this->company->id);
                  })
                  ->whereHas('programType', function ($query) {
                    $query->where('name', Program::DEALER_FINANCING);
                  });
              })
              ->where('status', 'active')
              ->first();
          } else {
            if ($this->type == Program::VENDOR_FINANCING_RECEIVABLE) {
              $vendor_configuration = ProgramVendorConfiguration::where(
                'payment_account_number',
                trim($row['payment_od_account'])
              )
                ->whereHas('program', function ($query) {
                  $query
                    ->whereHas('anchor', function ($query) {
                      $query->where('companies.id', $this->company->id);
                    })
                    ->whereHas('programCode', function ($query) {
                      $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                    });
                })
                ->where('status', 'active')
                ->first();
            } else {
              $vendor_configuration = ProgramVendorConfiguration::where(
                'payment_account_number',
                trim($row['payment_od_account'])
              )
                ->where('buyer_id', $this->company->id)
                ->whereHas('program', function ($query) {
                  $query->whereHas('programCode', function ($query) {
                    $query
                      ->where('name', Program::FACTORING_WITH_RECOURSE)
                      ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
                  });
                })
                ->where('status', 'active')
                ->first();
            }
          }

          $exists = Invoice::where('invoice_number', trim($row['invoice_number']))
            ->where('company_id', $vendor_configuration?->company_id)
            ->first();

          $due_date = $row['due_date'];
          $invoice_date = $row['invoice_date'];

          $matrix = null;

          if (
            !$exists &&
            $vendor_configuration &&
            $due_date &&
            $invoice_date &&
            now()->lessThanOrEqualTo(Carbon::parse($due_date)) &&
            Carbon::parse($invoice_date)->lessThanOrEqualTo(Carbon::parse($due_date))
          ) {
            $this->data++;
            if ($vendor_configuration->program->programType->name == Program::VENDOR_FINANCING) {
              if ($vendor_configuration->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                $invoice = Invoice::create([
                  'company_id' => $vendor_configuration->company_id,
                  'program_id' => $vendor_configuration->program_id,
                  'invoice_number' => $row['invoice_number'],
                  'invoice_date' => $row['invoice_date'],
                  'due_date' => $row['due_date'],
                  'status' => 'submitted',
                  'financing_status' => 'pending',
                  'stage' => 'pending_checker',
                  'total_amount' => str_replace('Ksh', '', str_replace(',', '', $row['total_amount'])),
                  'currency' => $this->company->bank->default_currency,
                ]);
              } else {
                $invoice = Invoice::create([
                  'company_id' => $vendor_configuration->program->anchor->id,
                  'program_id' => $vendor_configuration->program_id,
                  'invoice_number' => $row['invoice_number'],
                  'invoice_date' => $row['invoice_date'],
                  'due_date' => $row['due_date'],
                  'status' => 'submitted',
                  'financing_status' => 'pending',
                  'stage' => 'pending_checker',
                  'total_amount' => str_replace('Ksh', '', str_replace(',', '', $row['total_amount'])),
                  'buyer_id' => $vendor_configuration->buyer_id,
                  'currency' => $this->company->bank->default_currency,
                ]);

                if ($vendor_configuration && $vendor_configuration->auto_approve_invoices) {
                  $invoice->update([
                    'status' => 'approved',
                    'stage' => 'approved',
                    'eligible_for_financing' =>
                      Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                        ? true
                        : false,
                  ]);
                }
              }
            }

            if (
              array_key_exists('tax_amount', $row->toArray()) &&
              $row['tax_amount'] != null &&
              $row['tax_amount'] != '' &&
              $row['tax_amount'] > 0
            ) {
              InvoiceTax::create([
                'invoice_id' => $invoice->id,
                'name' => 'Tax Amount',
                'value' => $row['tax_amount'],
              ]);
            }

            if (
              array_key_exists('credit_note_amount', $row->toArray()) &&
              $row['credit_note_amount'] != null &&
              $row['credit_note_amount'] != '' &&
              $row['credit_note_amount'] > 0
            ) {
              InvoiceFee::create([
                'invoice_id' => $invoice->id,
                'name' => 'Credit Note Amount',
                'amount' => $row['credit_note_amount'],
              ]);
            }

            if ($vendor_configuration->program->programType->name == Program::VENDOR_FINANCING) {
              if (
                $vendor_configuration->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE ||
                $vendor_configuration->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
                $vendor_configuration->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE
              ) {
                if ($vendor_configuration->withholding_tax) {
                  InvoiceFee::create([
                    'invoice_id' => $invoice->id,
                    'name' => 'Withholding Tax',
                    'amount' =>
                      (float) (($vendor_configuration->withholding_tax / 100) *
                        ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount)),
                  ]);
                }

                if ($vendor_configuration->withholding_vat) {
                  InvoiceFee::create([
                    'invoice_id' => $invoice->id,
                    'name' => 'Withholding VAT',
                    'amount' =>
                      (float) (($vendor_configuration->withholding_vat / 100) *
                        ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount)),
                  ]);
                }
              }
            }

            if ($invoice && $invoice->status === 'approved') {
              $invoice->update([
                'calculated_total_amount' => $invoice->invoice_total_amount,
              ]);
            }

            // $invoice_setting = $invoice->company->invoiceSetting;

            // if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
            //   if ($invoice_setting) {
            //     if (!$invoice_setting->maker_checker_creating_updating) {
            //       // Add approval for vendor company
            //       $user = $invoice->company->users->where('is_active', true)->first();

            //       if ($user) {
            //         $invoice->approvals()->create([
            //           'user_id' => $user->id,
            //         ]);
            //       }
            //     } else {
            //       // Add two approvals for vendor company
            //       $user = $invoice->company->users->where('is_active', true)->first();

            //       if ($user) {
            //         $invoice->approvals()->create([
            //           'user_id' => $user->id,
            //         ]);

            //         $invoice->approvals()->create([
            //           'user_id' => $user->id,
            //         ]);
            //       }
            //     }
            //   } else {
            //     if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
            //       if (
            //         $invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            //         $invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE
            //       ) {
            //         // Check if user is in authorization matrix group
            //         $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->buyer_id)
            //           ->where('min_pi_amount', '<=', $invoice->total + $invoice->total_invoice_taxes)
            //           ->where('max_pi_amount', '>=', $invoice->total + $invoice->total_invoice_taxes)
            //           ->where('status', 'active')
            //           ->where('program_type_id', $invoice->program->program_type_id)
            //           ->first();

            //         $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix?->id)->pluck('group_id');

            //         $authorization_group = CompanyAuthorizationGroup::where('company_id', $invoice->buyer_id)
            //           ->whereIn('id', $rules)
            //           ->where('status', 'active')
            //           ->pluck('id');

            //         $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->buyer_id)
            //           ->whereIn('group_id', $authorization_group)
            //           ->where('user_id', auth()->id())
            //           ->first();

            //         if ($user_authorization_group) {
            //           $invoice->approvals()->create([
            //             'user_id' => auth()->id(),
            //           ]);

            //           $invoice->update([
            //             'status' => 'submitted',
            //             'stage' => 'pending_checker',
            //           ]);
            //         } else {
            //           $invoice->update([
            //             'stage' => 'pending_maker',
            //           ]);
            //         }
            //       }
            //     }
            //   }
            // }

            if ($vendor_configuration->program->programType->name === Program::VENDOR_FINANCING) {
              $vendor_financing_id = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
              // Check Authorization Matrix for invoice amount
              $matrix = CompanyAuthorizationMatrix::where('company_id', $this->company->id)
                ->where('min_pi_amount', '<=', $invoice->invoice_total_amount)
                ->where('max_pi_amount', '>=', $invoice->invoice_total_amount)
                ->where('program_type_id', $vendor_financing_id->id)
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
                $invoice_upload_report->net_invoice_amount = str_replace(
                  ',',
                  '',
                  str_replace('Ksh', '', $row['total_amount'])
                );
                $invoice_upload_report->currency = $this->company->bank->default_currency;
                $invoice_upload_report->due_date = $row['due_date'];
                $invoice_upload_report->type = $row['type'];
                $invoice_upload_report->loan_od_account = $row['payment_od_account'];
                $invoice_upload_report->pay_date = $row['pay_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->attachments = $row['attachments'];
                $invoice_upload_report->tax_amount = $row['tax_amount'];
                $invoice_upload_report->total_payable = $row['total_payable']
                  ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
                  : 0;
                $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
                $invoice_upload_report->description =
                  'Authorization Matrix rule was not found for this invoice amount.';
                $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
                $invoice_upload_report->product_type =
                  $this->type === Program::VENDOR_FINANCING_RECEIVABLE || $this->type === 'Factoring'
                    ? Program::VENDOR_FINANCING
                    : Program::DEALER_FINANCING;
                $invoice_upload_report->product_code = $this->type == Program::DEALER_FINANCING ? null : $this->type;
                $invoice_upload_report->save();

                // Delete invoice
                $invoice->delete();
              } else {
                $invoice_upload_report = new InvoiceUploadReport();
                $invoice_upload_report->company_id = $this->company->id;
                $invoice_upload_report->invoice_number = $invoice->invoice_number;
                $invoice_upload_report->invoice_date = $row['invoice_date'];
                $invoice_upload_report->net_invoice_amount = str_replace(
                  ',',
                  '',
                  str_replace('Ksh', '', $row['total_amount'])
                );
                $invoice_upload_report->currency = $this->company->bank->default_currency;
                $invoice_upload_report->due_date = $row['due_date'];
                $invoice_upload_report->type = $row['type'];
                $invoice_upload_report->loan_od_account = $vendor_configuration->payment_account_number;
                $invoice_upload_report->pay_date = $row['pay_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->attachments = $row['attachments'];
                $invoice_upload_report->status = 'Successful';
                $invoice_upload_report->description = 'Invoice Uploaded successfully';
                $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
                $invoice_upload_report->product_type =
                  $this->type === Program::VENDOR_FINANCING_RECEIVABLE || $this->type === 'Factoring'
                    ? Program::VENDOR_FINANCING
                    : Program::DEALER_FINANCING;
                $invoice_upload_report->product_code = $this->type == Program::DEALER_FINANCING ? null : $this->type;
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
              }
            } else {
              $invoice_upload_report = new InvoiceUploadReport();
              $invoice_upload_report->company_id = $this->company->id;
              $invoice_upload_report->invoice_number = $invoice->invoice_number;
              $invoice_upload_report->invoice_date = $row['invoice_date']
                ? Carbon::createFromFormat('Y-d-m', $row['invoice_date'])->format('Y-m-d')
                : null;
              $invoice_upload_report->net_invoice_amount = str_replace(
                ',',
                '',
                str_replace('Ksh', '', $row['total_amount'])
              );
              $invoice_upload_report->currency = $this->company->bank->default_currency;
              $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
              $invoice_upload_report->type = $row['type'];
              $invoice_upload_report->loan_od_account = $vendor_configuration->payment_account_number;
              $invoice_upload_report->pay_date = $row['pay_date']
                ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                : null;
              $invoice_upload_report->attachments = $row['attachments'];
              $invoice_upload_report->status = 'Successful';
              $invoice_upload_report->description = 'Invoice Uploaded successfully';
              $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
              $invoice_upload_report->product_type =
                $this->type === Program::VENDOR_FINANCING_RECEIVABLE || $this->type === 'Factoring'
                  ? Program::VENDOR_FINANCING
                  : Program::DEALER_FINANCING;
              $invoice_upload_report->product_code = $this->type == Program::DEALER_FINANCING ? null : $this->type;
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
            }
          } else {
            if ($this->company) {
              if (!$vendor_configuration) {
                $invoice_upload_report = new InvoiceUploadReport();
                $invoice_upload_report->company_id = $this->company->id;
                $invoice_upload_report->invoice_number = $row['invoice_number'];
                $invoice_upload_report->status = 'Failed';
                $invoice_upload_report->invoice_date = $row['invoice_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['invoice_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->net_invoice_amount = str_replace(
                  ',',
                  '',
                  str_replace('Ksh', '', $row['total_amount'])
                );
                $invoice_upload_report->currency = $this->company->bank->default_currency;
                $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
                $invoice_upload_report->type = $row['type'];
                $invoice_upload_report->loan_od_account = $row['payment_od_account'];
                $invoice_upload_report->pay_date = $row['pay_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->attachments = $row['attachments'];
                $invoice_upload_report->tax_amount = $row['tax_amount'];
                $invoice_upload_report->total_payable = $row['total_payable']
                  ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
                  : 0;
                $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
                $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
                $invoice_upload_report->description = 'Invalid or Inactive Loan/OD Account';
              } elseif ($exists) {
                $invoice_upload_report = new InvoiceUploadReport();
                $invoice_upload_report->company_id = $this->company->id;
                $invoice_upload_report->invoice_number = $row['invoice_number'];
                $invoice_upload_report->status = 'Failed';
                $invoice_upload_report->invoice_date = $row['invoice_date'] ? $row['invoice_date'] : null;
                $invoice_upload_report->net_invoice_amount = str_replace(
                  ',',
                  '',
                  str_replace('Ksh', '', $row['total_amount'])
                );
                $invoice_upload_report->currency = $this->company->bank->default_currency;
                $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
                $invoice_upload_report->type = $row['type'];
                $invoice_upload_report->loan_od_account = $row['payment_od_account'];
                $invoice_upload_report->pay_date = $row['pay_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->attachments = $row['attachments'];
                $invoice_upload_report->tax_amount = $row['tax_amount'];
                $invoice_upload_report->total_payable = $row['total_payable']
                  ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
                  : 0;
                $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
                $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
                $invoice_upload_report->description = 'Invalid Invoice Number. Invoice already exists';
              } elseif (now()->greaterThan(Carbon::parse($due_date))) {
                $invoice_upload_report = new InvoiceUploadReport();
                $invoice_upload_report->company_id = $this->company->id;
                $invoice_upload_report->invoice_number = $row['invoice_number'];
                $invoice_upload_report->status = 'Failed';
                $invoice_upload_report->invoice_date = $row['invoice_date'] ? $row['invoice_date'] : null;
                $invoice_upload_report->net_invoice_amount = str_replace(
                  ',',
                  '',
                  str_replace('Ksh', '', $row['total_amount'])
                );
                $invoice_upload_report->currency = $this->company->bank->default_currency;
                $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
                $invoice_upload_report->type = $row['type'];
                $invoice_upload_report->loan_od_account = $row['payment_od_account'];
                $invoice_upload_report->pay_date = $row['pay_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->attachments = $row['attachments'];
                $invoice_upload_report->tax_amount = $row['tax_amount'];
                $invoice_upload_report->total_payable = $row['total_payable']
                  ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
                  : 0;
                $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
                $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
                $invoice_upload_report->description = 'Invalid Invoice Due Date. Due date cannot be in the past.';
              } elseif (Carbon::parse($invoice_date)->greaterThan(Carbon::parse($due_date))) {
                $invoice_upload_report = new InvoiceUploadReport();
                $invoice_upload_report->company_id = $this->company->id;
                $invoice_upload_report->invoice_number = $row['invoice_number'];
                $invoice_upload_report->status = 'Failed';
                $invoice_upload_report->invoice_date = $row['invoice_date'] ? $row['invoice_date'] : null;
                $invoice_upload_report->net_invoice_amount = str_replace(
                  ',',
                  '',
                  str_replace('Ksh', '', $row['total_amount'])
                );
                $invoice_upload_report->currency = $this->company->bank->default_currency;
                $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
                $invoice_upload_report->type = $row['type'];
                $invoice_upload_report->loan_od_account = $row['payment_od_account'];
                $invoice_upload_report->pay_date = $row['pay_date']
                  ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
                  : null;
                $invoice_upload_report->attachments = $row['attachments'];
                $invoice_upload_report->tax_amount = $row['tax_amount'];
                $invoice_upload_report->total_payable = $row['total_payable']
                  ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
                  : 0;
                $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
                $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
                $invoice_upload_report->description =
                  'Invalid Invoice Date. Invoice Date cannot be greater than Due Date.';
              }

              $invoice_upload_report->product_type =
                $this->type === Program::VENDOR_FINANCING_RECEIVABLE || $this->type === 'Factoring'
                  ? Program::VENDOR_FINANCING
                  : Program::DEALER_FINANCING;
              $invoice_upload_report->product_code = $this->type === Program::DEALER_FINANCING ? null : $this->type;

              $invoice_upload_report->save();
            }
          }
        } else {
          if ($this->company) {
            $invoice_upload_report = new InvoiceUploadReport();
            $invoice_upload_report->company_id = $this->company->id;
            $invoice_upload_report->invoice_number = $row['invoice_number'];
            $invoice_upload_report->status = 'Failed';
            $invoice_upload_report->invoice_date = $row['invoice_date'] ? $row['invoice_date'] : null;
            $invoice_upload_report->net_invoice_amount = str_replace(
              ',',
              '',
              str_replace('Ksh', '', $row['total_amount'])
            );
            $invoice_upload_report->currency = $this->company->bank->default_currency;
            $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
            $invoice_upload_report->type = $row['type'];
            $invoice_upload_report->loan_od_account = $row['payment_od_account'];
            $invoice_upload_report->pay_date = $row['pay_date']
              ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
              : null;
            $invoice_upload_report->attachments = $row['attachments'];
            $invoice_upload_report->tax_amount = $row['tax_amount'];
            $invoice_upload_report->total_payable = $row['total_payable']
              ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
              : 0;
            $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
            $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;

            if (!$row['invoice_number']) {
              $invoice_upload_report->description = 'The Invoice Number is required';
            }

            if (!$row['invoice_date']) {
              $invoice_upload_report->description = 'The Invoice Date is required';
            }

            if (!$row['total_amount']) {
              $invoice_upload_report->description = 'The Invoice Total Amount is required';
            }

            if (!$row['due_date']) {
              $invoice_upload_report->description = 'The Invoice Due Date is required';
            }

            if (!$row['type']) {
              $invoice_upload_report->description = 'The Type is required';
            }

            if (!$row['payment_od_account']) {
              $invoice_upload_report->description = 'The Payment/OD Account is required';
            }

            $invoice_upload_report->product_type =
              $this->type === Program::VENDOR_FINANCING_RECEIVABLE || $this->type === 'Factoring'
                ? Program::VENDOR_FINANCING
                : Program::DEALER_FINANCING;
            $invoice_upload_report->product_code = $this->type === Program::DEALER_FINANCING ? null : $this->type;

            $invoice_upload_report->save();
          }
        }
      } catch (\Throwable $th) {
        info($th);
        $invoice_upload_report = new InvoiceUploadReport();
        $invoice_upload_report->company_id = $this->company->id;
        $invoice_upload_report->invoice_number = $row['invoice_number'];
        $invoice_upload_report->status = 'Failed';
        $invoice_upload_report->invoice_date = $row['invoice_date'] ? $row['invoice_date'] : null;
        $invoice_upload_report->net_invoice_amount = str_replace(',', '', str_replace('Ksh', '', $row['total_amount']));
        $invoice_upload_report->currency = $this->company->bank->default_currency;
        $invoice_upload_report->due_date = $row['due_date'] ? $row['due_date'] : null;
        $invoice_upload_report->type = $row['type'];
        $invoice_upload_report->loan_od_account = $row['payment_od_account'];
        $invoice_upload_report->pay_date = $row['pay_date']
          ? Carbon::createFromFormat('Y-d-m', $row['pay_date'])->format('Y-m-d')
          : null;
        $invoice_upload_report->attachments = $row['attachments'];
        $invoice_upload_report->tax_amount = $row['tax_amount'];
        $invoice_upload_report->total_payable = $row['total_payable']
          ? str_replace(',', '', str_replace('Ksh', '', $row['total_payable']))
          : 0;
        $invoice_upload_report->credit_note_amount = $row['credit_note_amount'];
        $invoice_upload_report->description = 'Invalid Due Date/Pay Date Format';
        $invoice_upload_report->batch_id = $latest_batch_id ? $latest_batch_id->batch_id + 1 : 1;
        $invoice_upload_report->product_type =
          $this->type === Program::VENDOR_FINANCING_RECEIVABLE || $this->type === 'Factoring'
            ? Program::VENDOR_FINANCING
            : Program::DEALER_FINANCING;
        $invoice_upload_report->product_code = $this->type === Program::DEALER_FINANCING ? null : $this->type;
        $invoice_upload_report->save();
      }
    }
  }

  function batchSize(): int
  {
    return 1000;
  }

  function chunkSize(): int
  {
    return 1000;
  }
}
