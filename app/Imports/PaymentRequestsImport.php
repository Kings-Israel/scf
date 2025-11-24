<?php

namespace App\Imports;

use App\Helpers\Helpers;
use Carbon\Carbon;
use App\Models\Bank;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\ProgramCode;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use Illuminate\Validation\Rule;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use App\Models\ProgramVendorBankDetail;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\BankProductsConfiguration;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\PaymentRequestUploadReport;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PaymentRequestsImport implements ToModel, WithHeadingRow, SkipsOnFailure, SkipsEmptyRows, WithValidation
{
  use Importable, SkipsFailures;

  public $bank;

  public function __construct(Bank $bank, public array $companies)
  {
    $this->bank = $bank;
  }

  public function rules(): array
  {
    return [
      'payment_reference_no' => ['required', 'unique:payment_requests,reference_number'],
      'invoice_no' => ['required', 'unique:invoices,invoice_number'],
      'vendor' => ['required', Rule::in($this->companies)],
      'anchor' => ['required', Rule::in($this->companies)],
      'pi_amount_ksh' => ['required'],
      'eligibility' => ['required'],
      'requested_payment_amount_ksh' => ['required'],
      'request_date' => ['required'],
      'requested_disbursement_date' => ['required'],
      'due_date' => ['required'],
      'discount_rate' => ['required'],
      'status' => ['required', 'in:created,paid,pending,approved,rejected'],
      'created_at' => ['required'],
      'last_updated_at' => ['required'],
    ];
  }

  public function customValidationMessages()
  {
    return [
      'payment_reference_no.required' => 'Enter the payment reference number',
      'invoice_no.required' => 'Enter the invoice number',
      'invoice_no.unique' => 'The invoice number is already in use',
      'vendor.required' => 'Enter the vendor',
      'vendor.in' => 'The vendor must be one of companies in the bank',
      'anchor.required' => 'Enter the anchor',
      'anchor.in' => 'The anchor must be one of companies in the bank',
      'eligibility.required' => 'Enter the eligibility',
      'pi_amount_ksh.required' => 'Enter the PI Amount',
      'requested_payment_amount_ksh.required' => 'Enter the requested payment amount',
      'request_date.required' => 'Enter the request date',
      'requested_disbursement_date.required' => 'Enter the requested disbursement date',
      'due_date.required' => 'Enter the invoice due date',
      'discount_rate.required' => 'Enter the discount rate',
      'status.required' => 'Enter the payment request status',
      'status.in' => 'The status can be one of the following: created, paid, pending, approved, rejected',
      'created_at.required' => 'Enter the payment request created date',
      'last_updated_at.required' => 'Enter the payment request last updated date',
    ];
  }

  public function model(array $payment_request)
  {
    $anchor = Company::where('name', $payment_request['anchor'])
      ->where('bank_id', $this->bank->id)
      ->first();
    $vendor = Company::where('name', $payment_request['vendor'])
      ->where('bank_id', $this->bank->id)
      ->first();
    $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
    // $factoring_with_recourse = ProgramCode::where('name', 'Factoring With Recourse')->first();
    // $factoring_without_recourse = ProgramCode::where('name', 'Factoring Without Recourse')->first();
    // $dealer_financing = ProgramCode::where('name', 'Dealer Financing')->first();

    $invoice = Invoice::where('invoice_number', $payment_request['invoice_no'])->first();

    if ($anchor && $vendor && !$invoice) {
      // Find the program
      $anchor_role = ProgramRole::where('name', 'anchor')->first();
      $vendor_role = ProgramRole::where('name', 'vendor')->first();

      $anchors_programs = ProgramCompanyRole::where(['role_id' => $anchor_role->id, 'company_id' => $anchor->id])
        ->whereHas('program', function ($query) {
          $query->whereHas('programCode', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
          });
        })
        ->pluck('program_id');

      $filtered_programs = ProgramCompanyRole::whereIn('program_id', $anchors_programs)
        ->where('role_id', $vendor_role->id)
        ->where('company_id', $vendor->id)
        ->pluck('program_id');

      $program = Program::whereIn('id', $filtered_programs)->first();

      if ($program) {
        $request_date =
          $payment_request['request_date'] != null && $payment_request['request_date'] != ''
            ? Helpers::importParseDate($payment_request['request_date'])
            : null;
        // $request_date = Carbon::parse($payment_request['request_date'])->format('Y-m-d');
        $due_date =
          $payment_request['due_date'] != null && $payment_request['due_date'] != ''
            ? Helpers::importParseDate($payment_request['due_date'])
            : null;
        // $due_date = Carbon::parse($payment_request['due_date'])->format('Y-m-d');
        $disbursement_date =
          $payment_request['requested_disbursement_date'] != null &&
          $payment_request['requested_disbursement_date'] != ''
            ? Helpers::importParseDate($payment_request['requested_disbursement_date'])
            : null;
        // $disbursement_date = Carbon::parse($payment_request['requested_disbursement_date'])->format('Y-m-d');

        try {
          DB::beginTransaction();

          $invoice = Invoice::where('invoice_number', $payment_request['invoice_no'])->first();

          if (!$invoice) {
            // Create the invoice
            $invoice = Invoice::create([
              'program_id' => $program->id,
              'company_id' => $vendor->id,
              'invoice_number' => $payment_request['invoice_no'],
              'invoice_date' => $request_date,
              'due_date' => $due_date,
              'total_amount' => str_replace(',', '', $payment_request['pi_amount_ksh']),
              'currency' => $this->bank->default_currency,
              'financing_status' => 'submitted',
              'status' => 'approved',
              'stage' => 'approved',
            ]);
          }

          if ($invoice && $invoice->status == 'approved') {
            $invoice->update([
              'calculated_total_amount' => str_replace(',', '', $payment_request['pi_amount_ksh']),
            ]);
          }

          $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company->id)
            ->where('program_id', $invoice->program->id)
            ->first();

          $p_request = PaymentRequest::create([
            'reference_number' => $payment_request['payment_reference_no'],
            'invoice_id' => $invoice->id,
            'amount' => round(str_replace(',', '', $payment_request['requested_payment_amount_ksh']), 2),
            'processing_fee' => 0,
            'payment_request_date' => $request_date,
            'status' => $payment_request['status'],
            'approval_status' => $payment_request['status'],
            'rejected_reason' =>
              array_key_exists('rejection_remark', $payment_request) && $payment_request['rejection_remark'] != ''
                ? $payment_request['rejection_remark']
                : null,
          ]);

          // Credit to vendor's account
          $p_request->paymentAccounts()->create([
            'account' => $vendor_bank_details->account_number,
            'account_name' => $vendor_bank_details->name_as_per_bank,
            'amount' => round(str_replace(',', '', $payment_request['requested_payment_amount_ksh']), 2),
            'type' => 'vendor_account',
          ]);

          // Credit discount to discount account
          $bank_account = BankProductsConfiguration::where('bank_id', $p_request->invoice->program->bank->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Discount Income Account')
            ->first();
          if ($bank_account && $bank_account->value) {
            $p_request->paymentAccounts()->create([
              'account' => $bank_account->account_number,
              'account_name' => $bank_account->account_name,
              'amount' => round(
                str_replace(',', '', $payment_request['pi_amount_ksh']) -
                  str_replace(',', '', $payment_request['requested_payment_amount_ksh']),
                2
              ),
              'type' => 'discount',
              'description' => 'vendor account',
            ]);
          } else {
            $p_request->paymentAccounts()->create([
              'account' => 'Disc_Inc_Acc',
              'account_name' => $bank_account->account_name,
              'amount' => round(
                str_replace(',', '', $payment_request['pi_amount_ksh']) -
                  str_replace(',', '', $payment_request['requested_payment_amount_ksh']),
                2
              ),
              'type' => 'discount',
              'description' => 'vendor account',
            ]);
          }

          switch ($payment_request['status']) {
            case 'rejected':
              $p_request->invoice->update([
                'financing_status' => 'denied',
              ]);
              break;
            case 'approved':
              $p_request->invoice->update([
                'pi_number' => 'PI_' . $p_request->invoice->id,
              ]);

              $payment_request_accounts = $p_request->paymentAccounts;
              $program_bank_details = $p_request->invoice->program->bankDetails->first();
              foreach ($payment_request_accounts as $request_account) {
                switch ($request_account->type) {
                  case 'vendor_account':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Created',
                      'transaction_type' => CbsTransaction::PAYMENT_DISBURSEMENT,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'discount':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Created',
                      'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'program_fees':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Created',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'program_fees_taxes':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Created',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'tax_on_discount':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Created',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                }
              }
              break;
            case 'paid':
              $p_request->invoice->update([
                'pi_number' => 'PI_' . $p_request->invoice->id,
                'status' => 'disbursed',
                'financing_status' => 'financed',
                'disbursement_date' => $disbursement_date,
                'disbursed_amount' => str_replace(' ', '', $payment_request['requested_payment_amount_ksh']),
              ]);

              $payment_request_accounts = $p_request->paymentAccounts;
              $program_bank_details = $p_request->invoice->program->bankDetails->first();
              foreach ($payment_request_accounts as $request_account) {
                switch ($request_account->type) {
                  case 'vendor_account':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Successful',
                      'transaction_type' => CbsTransaction::PAYMENT_DISBURSEMENT,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'discount':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Successful',
                      'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'program_fees':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Successful',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'program_fees_taxes':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Sucessful',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                  case 'tax_on_discount':
                    CbsTransaction::create([
                      'bank_id' => $p_request->invoice?->program?->bank?->id,
                      'payment_request_id' => $p_request->id,
                      'debit_from_account' => $program_bank_details->account_number,
                      'debit_from_account_name' => $program_bank_details->name_as_per_bank,
                      'credit_to_account' => $request_account->account,
                      'credit_to_account_name' => $request_account->account_name,
                      'amount' => $request_account->amount,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'status' => 'Successful',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' => $p_request->invoice->program->programType->name,
                    ]);
                    break;
                }
              }
              break;
            default:
              # code...
              break;
          }

          // Send mail to company concerning status uppdate
          $company_users = $p_request->invoice->company->users;
          foreach ($company_users as $company_user) {
            if ($p_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
              if ($p_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
                  'financing_request' => $p_request->id,
                  'url' => config('app.url'),
                  'name' => $company_user->name,
                  'type' => 'vendor_financing',
                ]);
              } else {
                SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
                  'financing_request' => $p_request->id,
                  'url' => config('app.url'),
                  'name' => $company_user->name,
                  'type' => 'factoring',
                ]);
              }
            } else {
              SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
                'financing_request' => $p_request->id,
                'url' => config('app.url'),
                'name' => $company_user->name,
                'type' => 'dealer_financing',
              ]);
            }
          }

          // Send mail to anchor company users
          $anchor_company_users = $p_request->invoice->program->anchor->users;
          foreach ($anchor_company_users as $company_user) {
            if (
              $p_request->invoice->program->programCode &&
              ($p_request->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
                $p_request->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
            ) {
              SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
                'financing_request' => $p_request->id,
                'url' => config('app.url'),
                'name' => $company_user->name,
                'type' => 'factoring',
              ]);
            } else {
              SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
                'financing_request' => $p_request->id,
                'url' => config('app.url'),
                'name' => $company_user->name,
                'type' => 'factoring',
              ]);
            }
          }

          activity($this->bank->id)
            ->causedBy(auth()->user())
            ->performedOn($p_request)
            ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
            ->log('uploaded payment request');

          $last_upload_report_batch = PaymentRequestUploadReport::where('bank_id', $this->bank->id)
            ->latest()
            ->first();

          PaymentRequestUploadReport::create([
            'bank_id' => $this->bank->id,
            'upload_status' => 'Successful',
            'description' => 'Payment request uploaded successfully',
            'invoice_number' => $payment_request['invoice_no'],
            'vendor' => $payment_request['vendor'],
            'anchor' => $payment_request['anchor'],
            'status' => $payment_request['status'],
            'pi_amount' => $payment_request['pi_amount_ksh'],
            'eligibility' => $payment_request['eligibility'],
            'eligibility_payment_amount' => $payment_request['eligibility_payment_amount_ksh'],
            'requested_payment_amount' => $payment_request['requested_payment_amount_ksh'],
            'request_date' => $payment_request['request_date'],
            'requested_disbursement_date' => $payment_request['requested_disbursement_date'],
            'requested_disbursement_date' => $payment_request['requested_disbursement_date'],
            'due_date' => $payment_request['due_date'],
            'discount_rate' => $payment_request['discount_rate'],
            'approved_by_rejected_by' => $payment_request['approved_by_rejected_by'],
            'rejection_remark' => $payment_request['rejection_remark'],
            'product_code' => $payment_request['product_code'],
            'created_by' => $payment_request['created_by'],
            'last_updated_by' => $payment_request['last_updated_by'],
            'last_updated_at' => $payment_request['last_updated_at'],
            'batch_id' => $last_upload_report_batch->batch_id + 1,
          ]);

          DB::commit();
        } catch (\Throwable $e) {
          info($e);

          DB::rollback();

          $last_upload_report_batch = PaymentRequestUploadReport::where('bank_id', $this->bank->id)
            ->latest()
            ->first();

          PaymentRequestUploadReport::create([
            'bank_id' => $this->bank->id,
            'upload_status' => 'Failed',
            'description' => 'Payment request failed to upload',
            'invoice_number' => $payment_request['invoice_no'],
            'vendor' => $payment_request['vendor'],
            'anchor' => $payment_request['anchor'],
            'status' => $payment_request['status'],
            'pi_amount' => $payment_request['pi_amount_ksh'],
            'eligibility' => $payment_request['eligibility'],
            'eligibility_payment_amount' => $payment_request['eligibility_payment_amount_ksh'],
            'requested_payment_amount' => $payment_request['requested_payment_amount_ksh'],
            'request_date' => $payment_request['request_date'],
            'requested_disbursement_date' => $payment_request['requested_disbursement_date'],
            'requested_disbursement_date' => $payment_request['requested_disbursement_date'],
            'due_date' => $payment_request['due_date'],
            'discount_rate' => $payment_request['discount_rate'],
            'approved_by_rejected_by' => $payment_request['approved_by_rejected_by'],
            'rejection_remark' => $payment_request['rejection_remark'],
            'product_code' => $payment_request['product_code'],
            'created_by' => $payment_request['created_by'],
            'last_updated_by' => $payment_request['last_updated_by'],
            'last_updated_at' => $payment_request['last_updated_at'],
            'batch_id' => $last_upload_report_batch->batch_id + 1,
          ]);
        }
      }
    }
  }
}
