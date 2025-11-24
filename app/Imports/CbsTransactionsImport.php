<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Jobs\LoanClosingNotification;
use App\Jobs\LoanDisbursalNotification;
use Carbon\Carbon;
use App\Models\Bank;
use App\Jobs\SendMail;
use App\Models\BankProductsConfiguration;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Models\ProgramBankDetails;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\CreditAccountRequest;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramType;
use App\Models\ProgramVendorBankDetail;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\ProgramVendorConfiguration;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Notifications\ProgramLimitDepletion;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;

class CbsTransactionsImport implements
  ToCollection,
  WithHeadingRow,
  SkipsOnFailure,
  SkipsEmptyRows,
  WithValidation,
  WithMapping,
  WithEvents
{
  use Importable, SkipsFailures;

  public $bank;
  public $disbursed_invoices = [];
  public $closed_invoices = [];
  public $data = 0;
  public $total_rows = 0;
  public $successful_rows = 0;
  // public $bank_accounts = [];

  public function registerEvents(): array
  {
    return [
      BeforeImport::class => function (BeforeImport $event) {
        $this->total_rows = $event->getReader()->getTotalRows();
      },
    ];
  }

  public function __construct(Bank $bank)
  {
    $this->bank = $bank;

    // $program_bank_accounts = ProgramBankDetails::whereHas('program', function ($query) {
    //     $query->where('bank_id', $this->bank->id);
    //   })
    //   ->pluck('account_number');
    // $vendor_bank_account = ProgramVendorBankDetail::whereHas('program', function ($query) {
    //     $query->where('bank_id', $this->bank->id);
    //   })
    //   ->pluck('account_number');
    // $od_accounts = ProgramVendorConfiguration::whereHas('program', function ($query) {
    //     $query->where('bank_id', $this->bank->id);
    //   })
    //   ->pluck('payment_account_number');

    // $this->bank_accounts = $program_bank_accounts->merge($vendor_bank_account)->merge($od_accounts)->unique()->toArray();
  }

  public function rules(): array
  {
    return [
      'debit_from_ac_no' => ['required'],
      'credit_to_ac_no' => ['required'],
      'amount_ksh' => ['required'],
      'status_createdsuccessfulfailedpermanently_failed' => [
        'required',
        'in:Created,Successful,Failed,Permanently Failed',
      ],
      'transaction_type' => ['required'],
      'transaction_date_ddmmyyyy' => ['required_if:status_createdsuccessfulfailedpermanently_failed,Successful'],
      'transaction_reference_no' => ['required_if:status_createdsuccessfulfailedpermanently_failed,Successful'],
      'product' => ['required'],
    ];
  }

  public function customValidationMessages()
  {
    return [
      'debit_from_ac_no.required' => 'Enter the account to debit from',
      // 'debit_from_ac_no.in' => 'Enter a valid debit from account number. Must be one of the bank accounts configured for this bank.',
      'credit_to_ac_no.required' => 'Enter the account to credit to',
      // 'credit_to_ac_no.in' => 'Enter a valid credit to account number. Must be one of the bank accounts configured for this bank.',
      'amount_ksh.required' => 'Enter amount',
      'status_createdsuccessfulfailedpermanently_failed.required' => 'Enter the status',
      'transaction_type.required' => 'Enter the transaction type',
      'transaction_reference_no.required_if' =>
        'The transaction reference number is required when the status is Successful',
      'product.required' => 'Enter the product',
    ];
  }

  public function map($row): array
  {
    if (
      !array_key_exists('cbs_id', $row) ||
      !array_key_exists('invoice_unique_ref_no', $row) ||
      !array_key_exists('debit_from_ac_no', $row) ||
      !array_key_exists('debit_from_ac_name', $row) ||
      !array_key_exists('credit_to_ac_no', $row) ||
      !array_key_exists('credit_to_ac_name', $row) ||
      !array_key_exists('amount_ksh', $row) ||
      !array_key_exists('transaction_created_date_ddmmyyyy', $row) ||
      !array_key_exists('pay_date_ddmmyyyy', $row) ||
      !array_key_exists('transaction_date_ddmmyyyy', $row) ||
      !array_key_exists('transaction_reference_no', $row) ||
      !array_key_exists('status_createdsuccessfulfailedpermanently_failed', $row) ||
      !array_key_exists('transaction_type', $row) ||
      !array_key_exists('product', $row)
    ) {
      throw ValidationException::withMessages([
        'Invalid headers or missing column. Download and use the CBS Transactions template to import.',
      ]);
    }

    return [
      'cbs_id' => $row['cbs_id'],
      'invoice_unique_ref_no' => $row['invoice_unique_ref_no'],
      'debit_from_ac_no' => $row['debit_from_ac_no'],
      'debit_from_ac_name' => $row['debit_from_ac_name'],
      'credit_to_ac_no' => $row['credit_to_ac_no'],
      'credit_to_ac_name' => $row['credit_to_ac_name'],
      'amount_ksh' => $row['amount_ksh'],
      'transaction_created_date_ddmmyyyy' =>
        $row['transaction_created_date_ddmmyyyy'] != null && $row['transaction_created_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['transaction_created_date_ddmmyyyy'])
          : null,
      'transaction_date_ddmmyyyy' =>
        $row['transaction_date_ddmmyyyy'] != null && $row['transaction_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['transaction_date_ddmmyyyy'])
          : null,
      'pay_date_ddmmyyyy' =>
        $row['pay_date_ddmmyyyy'] != null && $row['pay_date_ddmmyyyy'] != ''
          ? Helpers::importParseDate($row['pay_date_ddmmyyyy'])
          : null,
      'transaction_reference_no' => $row['transaction_reference_no'],
      'status_createdsuccessfulfailedpermanently_failed' => $row['status_createdsuccessfulfailedpermanently_failed'],
      'transaction_type' => $row['transaction_type'],
      'product' => $row['product'],
    ];
  }

  public function collection(Collection $collection)
  {
    foreach ($collection as $transaction) {
      // TODO: Validate transaction date. Shouldn't be before the invoice created date
      $transaction = $transaction->toArray();
      $credit_date = $transaction['transaction_date_ddmmyyyy'];

      DB::transaction(function () use ($transaction, $credit_date) {
        if ($transaction['cbs_id'] != null || $transaction['cbs_id'] != '') {
          $invoice = null;
          if (array_key_exists('invoice_unique_ref_no', $transaction) && $transaction['invoice_unique_ref_no'] != '') {
            $invoice = Invoice::where('invoice_number', $transaction['invoice_unique_ref_no'])->first();
          }

          $cbs_transaction = CbsTransaction::where('id', $transaction['cbs_id'])
            ->where('bank_id', $this->bank->id)
            ->first();

          // If transaction has not already been completed
          if ($cbs_transaction && $cbs_transaction->status != 'Successful') {
            $cbs_transaction->update([
              // 'debit_from_account' => $transaction['debit_from_ac_no'],
              // 'debit_from_account_name' => $transaction['debit_from_ac_name'],
              // 'credit_to_account' => $transaction['credit_to_ac_no'],
              // 'credit_to_account_name' => $transaction['credit_to_ac_name'],
              // 'amount' => $transaction['amount_ksh'],
              'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
              'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
              'pay_date' => $transaction['pay_date_ddmmyyyy'],
              'transaction_reference' =>
                $transaction['transaction_reference_no'] != '' ? $transaction['transaction_reference_no'] : null,
              'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
              // 'transaction_type' => $transaction['transaction_type'],
              // 'product' => $transaction['product'],
            ]);

            // Get the payment request
            $payment_request = $cbs_transaction->paymentRequest;

            // If transaction is for payment request
            if ($payment_request) {
              if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                $payment_request->update([
                  'status' => 'paid',
                  'approval_status' => 'paid',
                ]);
              }

              // If transaction is done on due date or after, then it's loan repayment
              if (
                $transaction['transaction_type'] === CbsTransaction::OVERDUE_ACCOUNT &&
                $payment_request->invoice->financing_status === 'disbursed'
              ) {
                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  Payment::create([
                    'invoice_id' => $cbs_transaction->paymentRequest->invoice->id,
                    'amount' => $cbs_transaction->amount,
                    'credit_date' => $credit_date,
                  ]);

                  $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

                  // $payment_request->notifyUsers('InvoicePaymentProcessed');

                  if (round($payment_request->invoice->balance) <= 0) {
                    $payment_request->invoice->update([
                      'financing_status' => 'closed',
                    ]);

                    array_push($this->closed_invoices, $payment_request->invoice->id);

                    $payment_request->invoice->company->decrement(
                      'utilized_amount',
                      $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                        ? $payment_request->invoice->drawdown_amount
                        : ($payment_request->invoice->eligibility / 100) *
                          $payment_request->invoice->invoice_total_amount
                    );

                    $payment_request->invoice->program->decrement(
                      'utilized_amount',
                      $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                        ? $payment_request->invoice->drawdown_amount
                        : ($payment_request->invoice->eligibility / 100) *
                          $payment_request->invoice->invoice_total_amount
                    );

                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'company_id',
                      $payment_request->invoice->company_id
                    )
                      ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                        $query->where('buyer_id', $payment_request->invoice->buyer_id);
                      })
                      ->where('program_id', $payment_request->invoice->program_id)
                      ->first();

                    $program_vendor_configuration->decrement(
                      'utilized_amount',
                      $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                        ? $payment_request->invoice->drawdown_amount
                        : ($payment_request->invoice->eligibility / 100) *
                          $payment_request->invoice->invoice_total_amount
                    );

                    // // Notify anchor
                    // $payment_request->notifyUsers('LoanClosing');
                    // $payment_request->notifyUsers('FullRepayment');

                    // if ($cbs_transaction->amount != $payment_request->invoice->balance) {
                    //   $payment_request->notifyUsers('BalanceInvoicePayment');
                    // }

                    // // If loan was overdue, send overdue repayment mail
                    // if (Carbon::parse($payment_request->invoice->due_date)->lessThan($credit_date)) {
                    //   $payment_request->notifyUsers('OverdueFullRepayment');
                    // }
                  } else {
                    // $payment_request->notifyUsers('PartialRepayment');
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  foreach ($payment_request->invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForLoanSettlementFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              // Invoice Repayment
              if (
                ($transaction['transaction_type'] === CbsTransaction::REPAYMENT ||
                  $transaction['transaction_type'] === CbsTransaction::BANK_INVOICE_PAYMENT) &&
                $payment_request->invoice->financing_status === 'disbursed'
              ) {
                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  Payment::create([
                    'invoice_id' => $cbs_transaction->paymentRequest->invoice->id,
                    'amount' => $cbs_transaction->amount,
                    'credit_date' => $credit_date,
                  ]);

                  $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

                  // $payment_request->notifyUsers('InvoicePaymentProcessed');

                  if (round($payment_request->invoice->balance) <= 0) {
                    $payment_request->invoice->update([
                      'financing_status' => 'closed',
                    ]);

                    array_push($this->closed_invoices, $payment_request->invoice->id);

                    // Update Program and Company Pipeline and Utilized Amounts if repayment is made to OD Account
                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'payment_account_number',
                      $cbs_transaction->credit_to_account
                    )->first();

                    if ($program_vendor_configuration) {
                      $payment_request->invoice->company->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      $payment_request->invoice->program->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      // $program_vendor_configuration = ProgramVendorConfiguration::where(
                      //   'company_id',
                      //   $payment_request->invoice->company_id
                      // )
                      //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                      //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                      //   })
                      //   ->where('program_id', $payment_request->invoice->program_id)
                      //   ->first();

                      $program_vendor_configuration->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );
                    }

                    // // Notify anchor
                    // $payment_request->notifyUsers('LoanClosing');
                    // $payment_request->notifyUsers('FullRepayment');

                    // if (round($cbs_transaction->amount) != round($payment_request->invoice->balance)) {
                    //   $payment_request->notifyUsers('BalanceInvoicePayment');
                    // }

                    // // If loan was overdue, send overdue repayment mail
                    // if (Carbon::parse($payment_request->invoice->due_date)->lessThan($credit_date)) {
                    //   $payment_request->notifyUsers('OverdueFullRepayment');
                    // }
                  } else {
                    // $payment_request->notifyUsers('PartialRepayment');
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  foreach ($payment_request->invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForLoanSettlementFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              // Repayment of Accrued Interest
              if (
                $payment_request->invoice->financing_status === 'disbursed' &&
                $payment_request->invoice->discount_charge_type === Invoice::REAR_ENDED &&
                $transaction['transaction_type'] == CbsTransaction::ACCRUAL_POSTED_INTEREST
              ) {
                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  Payment::create([
                    'invoice_id' => $cbs_transaction->paymentRequest->invoice->id,
                    'amount' => $cbs_transaction->amount,
                    'credit_date' => $credit_date,
                  ]);

                  $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

                  // $payment_request->notifyUsers('InvoicePaymentProcessed');

                  if (round($payment_request->invoice->balance) <= 0) {
                    $payment_request->invoice->update([
                      'financing_status' => 'closed',
                      'stage' => 'closed',
                    ]);

                    array_push($this->closed_invoices, $payment_request->invoice->id);

                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'payment_account_number',
                      $cbs_transaction->credit_to_account
                    )->first();

                    // Update Program and Company Pipeline and Utilized Amounts if invoice affects OD
                    if ($program_vendor_configuration) {
                      $payment_request->invoice->company->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      $payment_request->invoice->program->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      // $program_vendor_configuration = ProgramVendorConfiguration::where(
                      //   'company_id',
                      //   $payment_request->invoice->company_id
                      // )
                      //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                      //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                      //   })
                      //   ->where('program_id', $payment_request->invoice->program_id)
                      //   ->first();

                      $program_vendor_configuration->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );
                    }

                    // // Notify anchor
                    // $payment_request->notifyUsers('LoanClosing');
                    // $payment_request->notifyUsers('FullRepayment');

                    // if ($cbs_transaction->amount != $payment_request->invoice->balance) {
                    //   $payment_request->notifyUsers('BalanceInvoicePayment');
                    // }

                    // // If loan was overdue, send overdue repayment mail
                    // if (Carbon::parse($payment_request->invoice->due_date)->lessThan($credit_date)) {
                    //   $payment_request->notifyUsers('OverdueFullRepayment');
                    // }
                  } else {
                    // $payment_request->notifyUsers('PartialRepayment');
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] === 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] === 'Permanently Failed'
                ) {
                  foreach ($payment_request->invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForLoanSettlementFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              // Repayment of Fees/Charges
              if (
                $payment_request->invoice->financing_status === 'disbursed' &&
                $payment_request->invoice->fee_charge_type === Invoice::REAR_ENDED &&
                $transaction['transaction_type'] == CbsTransaction::FEES_CHARGES
              ) {
                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  Payment::create([
                    'invoice_id' => $cbs_transaction->paymentRequest->invoice->id,
                    'amount' => $cbs_transaction->amount,
                    'credit_date' => $credit_date,
                  ]);

                  $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

                  // $payment_request->notifyUsers('InvoicePaymentProcessed');

                  if (round($payment_request->invoice->balance) <= 0) {
                    $payment_request->invoice->update([
                      'financing_status' => 'closed',
                    ]);

                    array_push($this->closed_invoices, $payment_request->invoice->id);

                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'payment_account_number',
                      $cbs_transaction->credit_to_account
                    )->first();
                    // Update Program and Company Pipeline and Utilized Amounts if invoice affects OD
                    if ($program_vendor_configuration) {
                      $payment_request->invoice->company->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      $payment_request->invoice->program->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      // $program_vendor_configuration = ProgramVendorConfiguration::where(
                      //   'company_id',
                      //   $payment_request->invoice->company_id
                      // )
                      //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                      //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                      //   })
                      //   ->where('program_id', $payment_request->invoice->program_id)
                      //   ->first();

                      $program_vendor_configuration->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );
                    }

                    // // Notify anchor
                    // $payment_request->notifyUsers('LoanClosing');
                    // $payment_request->notifyUsers('FullRepayment');

                    // if ($cbs_transaction->amount != $payment_request->invoice->balance) {
                    //   $payment_request->notifyUsers('BalanceInvoicePayment');
                    // }

                    // // If loan was overdue, send overdue repayment mail
                    // if (Carbon::parse($payment_request->invoice->due_date)->lessThan($credit_date)) {
                    //   $payment_request->notifyUsers('OverdueFullRepayment');
                    // }
                  } else {
                    // $payment_request->notifyUsers('PartialRepayment');
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] === 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] === 'Permanently Failed'
                ) {
                  foreach ($payment_request->invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForLoanSettlementFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              // Must be last to make sure all transactions are processed
              // Payment disbursement
              if (
                ($transaction['transaction_type'] == CbsTransaction::PAYMENT_DISBURSEMENT ||
                  $transaction['transaction_type'] == CbsTransaction::OD_DRAWDOWN ||
                  $transaction['transaction_type'] == CbsTransaction::ACCRUAL_POSTED_INTEREST ||
                  $transaction['transaction_type'] == CbsTransaction::FEES_CHARGES) &&
                $payment_request->invoice->financing_status == 'financed'
              ) {
                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  // Check if entered amount is equal to requested amount
                  if ($cbs_transaction->paymentRequest->invoice->all_transactions_successful) {
                    // $requested_amount = $cbs_transaction->paymentRequest->amount;
                    $requested_amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) use (
                      $cbs_transaction
                    ) {
                      $q->where('invoice_id', $cbs_transaction->paymentRequest->invoice_id);
                    })
                      ->where('type', 'vendor_account')
                      ->first()->amount;

                    $cbs_transaction->paymentRequest->invoice->update([
                      'disbursement_date' => $transaction['pay_date_ddmmyyyy'],
                      'disbursed_amount' => round($requested_amount, 2),
                      'financing_status' => 'disbursed',
                      'eligible_for_financing' => false,
                    ]);

                    array_push($this->disbursed_invoices, $payment_request->invoice->id);

                    // // Notify vendor users
                    // foreach ($payment_request->invoice->company->users as $user) {
                    //   SendMail::dispatch($user->email, 'LoanDisbursal', [
                    //     'invoice_id' => $payment_request->invoice->id,
                    //   ])->afterCommit();
                    // }

                    // // Notify anchor users
                    // if ($payment_request->invoice?->program->programType->name === Program::DEALER_FINANCING) {
                    //   foreach ($payment_request->invoice?->program->anchor->users as $user) {
                    //     $payment_request->notifyUsers('InvoicePaymentReceivedBySeller');
                    //   }
                    // } else {
                    //   if (
                    //     $payment_request->invoice?->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
                    //   ) {
                    //     foreach ($payment_request->invoice?->program->anchor->users as $user) {
                    //       $payment_request->notifyUsers('InvoicePaymentReceivedBySeller');
                    //     }
                    //   } else {
                    //     foreach ($payment_request->invoice?->buyer->users as $user) {
                    //       $payment_request->notifyUsers('InvoicePaymentReceivedBySeller');
                    //     }
                    //   }
                    // }

                    // // Notify relationship managers
                    // foreach ($payment_request->invoice->company->relationshipManagers as $user) {
                    //   SendMail::dispatch($user->email, 'LoanDisbursal', [
                    //     'invoice_id' => $payment_request->invoice->id,
                    //   ])->afterCommit();
                    // }

                    // Check program limit usage
                    if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                      $sanctioned_limit = ProgramVendorConfiguration::where(
                        'program_id',
                        $payment_request->invoice->program_id
                      )
                        ->where('company_id', $payment_request->invoice->company_id)
                        ->first()->sanctioned_limit;
                      $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                        $payment_request->invoice->program
                      );
                      if ($utilized_amount >= $sanctioned_limit) {
                        // Notify company users
                        $payment_request->invoice->company->notify(
                          new ProgramLimitDepletion($payment_request->invoice->program)
                        );
                      }
                    } else {
                      if (
                        $payment_request->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
                      ) {
                        $sanctioned_limit = ProgramVendorConfiguration::where(
                          'program_id',
                          $payment_request->invoice->program_id
                        )
                          ->where('company_id', $payment_request->invoice->company_id)
                          ->first()->sanctioned_limit;
                        $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                          $payment_request->invoice->program
                        );
                        if ($utilized_amount >= $sanctioned_limit) {
                          // Notify company users
                          $payment_request->invoice->company->notify(
                            new ProgramLimitDepletion($payment_request->invoice->program)
                          );
                        }
                      } else {
                        $sanctioned_limit = ProgramVendorConfiguration::where(
                          'program_id',
                          $payment_request->invoice->program_id
                        )
                          ->where('company_id', $payment_request->invoice->company_id)
                          ->where('buyer_id', $payment_request->invoice->buyer_id)
                          ->first()->sanctioned_limit;
                        $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                          $payment_request->invoice->program
                        );
                        if ($utilized_amount >= $sanctioned_limit) {
                          // Notify company users
                          $payment_request->invoice->company->notify(
                            new ProgramLimitDepletion($payment_request->invoice->program)
                          );
                        }
                      }
                    }
                  }
                }

                // TODO: Case where it was Failed and Changed to successful
                // FIX: Update utilized amounts
                // FIX: Change invoice status to financed or closed depending on current status

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  $cbs_transaction->paymentRequest->update([
                    'status' => 'failed',
                    'approval_status' => 'rejected',
                  ]);

                  if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed') {
                    $cbs_transaction->paymentRequest->invoice->update([
                      'financing_status' => 'denied',
                      'stage' => 'rejected',
                    ]);

                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'payment_account_number',
                      $cbs_transaction->debit_from_account
                    )->first();

                    // Update Program and Company Pipeline and Utilized Amounts: Replenish the Available Limit if was debited from OD
                    if ($program_vendor_configuration) {
                      $cbs_transaction->paymentRequest->invoice->company->decrement(
                        'utilized_amount',
                        $cbs_transaction->amount
                      );

                      $cbs_transaction->paymentRequest->invoice->program->decrement(
                        'utilized_amount',
                        $cbs_transaction->amount
                      );

                      // $program_vendor_configuration = ProgramVendorConfiguration::where(
                      //   'company_id',
                      //   $cbs_transaction->paymentRequest->invoice->company_id
                      // )
                      //   ->where('program_id', $cbs_transaction->paymentRequest->invoice->program_id)
                      //   ->first();

                      $program_vendor_configuration->decrement('utilized_amount', $cbs_transaction->amount);
                    }
                  }

                  // Notify bank users
                  foreach ($cbs_transaction->paymentRequest->invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'DisbursementFailed', [
                      'id' => $cbs_transaction->paymentRequest->invoice->id,
                    ])->afterCommit();
                  }
                }
              }

              activity($cbs_transaction->bank_id)
                ->causedBy(auth()->user())
                ->performedOn($cbs_transaction)
                ->withProperties([
                  'ip' => request()->ip(),
                  'device_info' => request()->userAgent(),
                  'user_type' => 'Bank',
                ])
                ->log('updated status to ' . $transaction['status_createdsuccessfulfailedpermanently_failed']);
            }

            if (!$payment_request && $invoice) {
              // Payment disbursement
              if (
                ($transaction['transaction_type'] === CbsTransaction::PAYMENT_DISBURSEMENT ||
                  $transaction['transaction_type'] === CbsTransaction::OD_DRAWDOWN) &&
                $invoice->financing_status === 'financed'
              ) {
                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  $cbs_transaction->paymentRequest->update([
                    'status' => 'paid',
                    'approval_status' => 'paid',
                  ]);

                  $invoice->update([
                    'disbursement_date' => $transaction['pay_date_ddmmyyyy'],
                    'disbursed_amount' => $cbs_transaction->amount,
                    'financing_status' => 'disbursed',
                    'eligible_for_financing' => false,
                  ]);

                  array_push($this->disbursed_invoices, $invoice->id);

                  // // Notify vendor users
                  // foreach ($invoice->company->users as $user) {
                  //   SendMail::dispatch($user->email, 'LoanDisbursal', ['invoice_id' => $invoice->id])->afterCommit();
                  // }

                  // // Notify anchor users
                  // foreach ($invoice->program->anchor->users as $user) {
                  //   SendMail::dispatch($user->email, 'LoanDisbursal', ['invoice_id' => $invoice->id])->afterCommit();
                  //   if ($invoice->paymentRequests->count() > 0) {
                  //     $invoice->notifyUsers('InvoicePaymentReceivedBySeller');
                  //   }
                  // }

                  // Check program limit usage
                  if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
                    $sanctioned_limit = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
                      ->where('company_id', $invoice->company_id)
                      ->first()->sanctioned_limit;
                    $utilized_amount = $invoice->company->utilizedAmount($invoice->program);
                    if ($utilized_amount >= $sanctioned_limit) {
                      // Notify company users
                      $invoice->company->notify(new ProgramLimitDepletion($invoice->program));
                    }
                  } else {
                    if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
                      $sanctioned_limit = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
                        ->where('company_id', $invoice->company_id)
                        ->first()->sanctioned_limit;
                      $utilized_amount = $invoice->company->utilizedAmount($invoice->program);
                      if ($utilized_amount >= $sanctioned_limit) {
                        // Notify company users
                        $invoice->company->notify(new ProgramLimitDepletion($invoice->program));
                      }
                    } else {
                      $sanctioned_limit = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
                        ->where('company_id', $invoice->company_id)
                        ->where('buyer_id', $invoice->buyer_id)
                        ->first()->sanctioned_limit;
                      $utilized_amount = $invoice->company->utilizedAmount($invoice->program);
                      if ($utilized_amount >= $sanctioned_limit) {
                        // Notify company users
                        $invoice->company->notify(new ProgramLimitDepletion($invoice->program));
                      }
                    }
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  if ($cbs_transaction->paymentRequest) {
                    $cbs_transaction->paymentRequest->update([
                      'status' => 'failed',
                    ]);

                    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed') {
                      $cbs_transaction->paymentRequest->invoice->update([
                        'financing_status' => 'denied',
                      ]);
                    }

                    // Notify bank users
                    foreach ($cbs_transaction->paymentRequest->invoice->program->bank->users as $user) {
                      SendMail::dispatch($user->email, 'DisbursementFailed', [
                        'id' => $cbs_transaction->paymentRequest->invoice->id,
                      ])->afterCommit();
                    }
                  }
                }
              }

              // If transaction is done on due date or after, then it's loan repayment
              if (
                ($transaction['transaction_type'] == CbsTransaction::FEES_CHARGES ||
                  $transaction['transaction_type'] == CbsTransaction::OVERDUE_ACCOUNT) &&
                $invoice->financing_status == 'disbursed' &&
                Carbon::parse($invoice->due_date)->lessThanOrEqualTo(now()->format('Y-m-d'))
              ) {
                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] === 'Successful' &&
                  (float) $cbs_transaction->amount <= (float) $invoice->balance
                ) {
                  Payment::create([
                    'invoice_id' => $cbs_transaction->paymentRequest->invoice->id,
                    'amount' => $cbs_transaction->amount,
                    'credit_date' => $credit_date,
                  ]);

                  $invoice->increment('calculated_paid_amount', $transaction['amount_ksh']);

                  if ($invoice->paymentRequests->count() > 0) {
                    // $invoice->notifyUsers('InvoicePaymentProcessed');
                  }

                  if (round($invoice->balance) <= 0) {
                    $invoice->update([
                      'financing_status' => 'closed',
                    ]);

                    array_push($this->closed_invoices, $invoice->id);

                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'payment_account_number',
                      $cbs_transaction->credit_to_account
                    )->first();

                    // Update Program and Company Pipeline and Utilized Amounts if affects OD
                    if ($program_vendor_configuration) {
                      $payment_request->invoice->company->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      $payment_request->invoice->program->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );

                      $program_vendor_configuration->decrement(
                        'utilized_amount',
                        $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                          ? $payment_request->invoice->drawdown_amount
                          : ($payment_request->invoice->eligibility / 100) *
                            $payment_request->invoice->invoice_total_amount
                      );
                    }

                    if ($invoice->paymentRequests->count() > 0) {
                      // // Notify anchor
                      // $invoice->notifyUsers('LoanClosing');
                      // $invoice->notifyUsers('FullRepayment');

                      // if (round($cbs_transaction->amount) != round($invoice->balance)) {
                      //   $invoice->notifyUsers('BalanceInvoicePayment');
                      // }

                      // // If loan was overdue, send overdue repayment mail
                      // if (Carbon::parse($invoice->due_date)->lessThan($credit_date)) {
                      //   $invoice->notifyUsers('OverdueFullRepayment');
                      // }
                    }
                  } else {
                    // $payment_request->notifyUsers('PartialRepayment');
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  foreach ($invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForLoanSettlementFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              if (
                ($transaction['transaction_type'] == CbsTransaction::REPAYMENT ||
                  $transaction['transaction_type'] == CbsTransaction::BANK_INVOICE_PAYMENT) &&
                $invoice->financing_status == 'disbursed' &&
                Carbon::parse($invoice->due_date)->lessThanOrEqualTo(now()->format('Y-m-d'))
              ) {
                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] === 'Successful' &&
                  round($cbs_transaction->amount) <= round($invoice->balance)
                ) {
                  Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $cbs_transaction->amount,
                    'credit_date' => $credit_date,
                  ]);

                  $invoice->increment('calculated_paid_amount', $transaction['amount_ksh']);

                  // $invoice->paymentRequests->first()->notifyUsers('InvoicePaymentProcessed');

                  if (round($invoice->balance) <= 0) {
                    $invoice->update([
                      'financing_status' => 'closed',
                    ]);

                    $program_vendor_configuration = ProgramVendorConfiguration::where(
                      'payment_account_number',
                      $cbs_transaction->credit_to_account
                    )->first();

                    // Update Program and Company Pipeline and Utilized Amounts if affects OD
                    if ($program_vendor_configuration) {
                      $invoice->company->decrement(
                        'utilized_amount',
                        $invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $invoice->drawdown_amount
                          : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                      );

                      $invoice->program->decrement(
                        'utilized_amount',
                        $invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $invoice->drawdown_amount
                          : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                      );

                      // $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                      //   ->when($invoice->buyer_id, function ($query) use ($invoice) {
                      //     $query->where('buyer_id', $invoice->buyer_id);
                      //   })
                      //   ->where('program_id', $invoice->program_id)
                      //   ->first();

                      $program_vendor_configuration->decrement(
                        'utilized_amount',
                        $invoice->program->programType->name == Program::DEALER_FINANCING
                          ? $invoice->drawdown_amount
                          : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                      );
                    }

                    array_push($this->closed_invoices, $invoice->id);

                    // // Notify anchor
                    // $invoice->notifyUsers('LoanClosing');
                    // $invoice->paymentRequests->first()->notifyUsers('FullRepayment');

                    // if ($cbs_transaction->amount != $invoice->balance) {
                    //   $invoice->paymentRequests->first()->notifyUsers('BalanceInvoicePayment');
                    // }

                    // // If loan was overdue, send overdue repayment mail
                    // if (Carbon::parse($invoice->due_date)->lessThan($credit_date)) {
                    //   $invoice->paymentRequests->first()->notifyUsers('OverdueFullRepayment');
                    // }
                  } else {
                    // $payment_request->notifyUsers('PartialRepayment');
                  }
                }

                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  foreach ($invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForLoanSettlementFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              // Crediting OD Account/Repayment
              if ($transaction['transaction_type'] == CbsTransaction::FUNDS_TRANSFER) {
                if (
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                  $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                ) {
                  foreach ($cbs_transaction->paymentRequest?->invoice->program->bank->users as $user) {
                    SendMail::dispatch($user->email, 'CbsTransactionForOdCreditFailed', [
                      'cbs_transaction_id' => $cbs_transaction->id,
                    ])->afterCommit();
                  }
                }
              }

              activity($cbs_transaction->bank_id)
                ->causedBy(auth()->user())
                ->performedOn($cbs_transaction)
                ->withProperties([
                  'ip' => request()->ip(),
                  'device_info' => request()->userAgent(),
                  'user_type' => 'Bank',
                ])
                ->log('updated status to ' . $transaction['status_createdsuccessfulfailedpermanently_failed']);
            }

            // Debiting OD Account
            // Reduce amount in payments made on OD Account
            if (!$payment_request && !$invoice) {
              if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                // Get OD Account Details
                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'payment_account_number',
                  $transaction['debit_from_ac_no']
                )->first();

                if ($program_vendor_configuration) {
                  // Get invoices made for payment on the program
                  $payment = Payment::whereHas('invoice', function ($query) use ($program_vendor_configuration) {
                    $query->where('program_id', $program_vendor_configuration->program_id);
                  })
                    ->latest()
                    ->first();

                  $remainder = $cbs_transaction->amount;

                  do {
                    $payment = Payment::whereHas('invoice', function ($query) use ($program_vendor_configuration) {
                      $query->where('program_id', $program_vendor_configuration->program_id);
                    })
                      ->latest()
                      ->first();

                    if ($payment) {
                      // Subtract payment amount
                      $payment->update([
                        'amount' => $cbs_transaction->amount - $payment->amount,
                      ]);

                      $remainder = $remainder - $payment->amount;

                      // If payment remainder is less than zero, delete payment
                      if ($payment->amount <= 0) {
                        $payment->delete();
                      }
                    }
                  } while ($payment && $remainder > 0);
                }
              }

              activity($cbs_transaction->bank_id)
                ->causedBy(auth()->user())
                ->performedOn($cbs_transaction)
                ->withProperties([
                  'ip' => request()->ip(),
                  'device_info' => request()->userAgent(),
                  'user_type' => 'Bank',
                ])
                ->log('updated status to ' . $transaction['status_createdsuccessfulfailedpermanently_failed']);
            }

            $this->data++;
          } else {
            $this->successful_rows++;
          }
        } else {
          if (
            $transaction['transaction_type'] == CbsTransaction::PAYMENT_DISBURSEMENT ||
            $transaction['transaction_type'] == CbsTransaction::OD_DRAWDOWN ||
            $transaction['transaction_type'] == CbsTransaction::FEES_CHARGES
          ) {
            // Get the program from the loan od account
            $vendor_configuration = ProgramVendorConfiguration::where(
              'payment_account_number',
              $transaction['debit_from_ac_no']
            )->first();

            if ($vendor_configuration) {
              // Get the receivers account number (FWR or FR)
              $vendor_bank_details = ProgramBankDetails::where('program_id', $vendor_configuration->program_id)
                ->where('account_number', $transaction['credit_to_ac_no'])
                ->first();

              if (!$vendor_bank_details) {
                // VFR or DF
                $vendor_bank_details = ProgramVendorBankDetail::where('program_id', $vendor_configuration->program_id)
                  ->where('company_id', $vendor_configuration->company_id)
                  ->where('account_number', $transaction['credit_to_ac_no'])
                  ->first();
              }

              if ($vendor_bank_details) {
                $payment_request = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company')
                  ->whereHas('invoice', function ($query) use ($vendor_bank_details) {
                    $query
                      ->where('program_id', $vendor_bank_details->program_id)
                      ->where('eligible_for_financing', true);
                  })
                  ->first();

                if ($payment_request) {
                  CbsTransaction::create([
                    'bank_id' => $vendor_configuration->program->bank->id,
                    'payment_request_id' => $payment_request->id,
                    'debit_from_account' => $transaction['debit_from_ac_no'],
                    'credit_to_account' => $transaction['credit_to_ac_no'],
                    'amount' => $transaction['amount_ksh'],
                    'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
                    'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
                    'pay_date' => $transaction['pay_date_ddmmyyyy'],
                    'transaction_reference' => $transaction['transaction_reference_no'],
                    'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
                    'transaction_type' => $transaction['transaction_type'],
                    'product' => $transaction['product'],
                  ]);

                  if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                    $payment_request->update([
                      'status' => 'paid',
                      'approval_status' => 'paid',
                    ]);

                    if ($payment_request->invoice->all_transactions_successful) {
                      $requested_amount = $payment_request->amount;
                      $payment_request->invoice->update([
                        'disbursement_date' => $transaction['pay_date_ddmmyyyy'],
                        'disbursed_amount' => round($requested_amount, 2),
                        'financing_status' => 'disbursed',
                        'eligible_for_financing' => false,
                      ]);

                      array_push($this->disbursed_invoices, $payment_request->invoice->id);

                      // // Notify vendor
                      // foreach ($payment_request->invoice?->company->users as $user) {
                      //   $payment_request->notifyUsers('LoanDisbursal');
                      // }

                      // if ($payment_request->invoice?->program->programType->name === Program::DEALER_FINANCING) {
                      //   foreach ($payment_request->invoice?->program->anchor->users as $user) {
                      //     $payment_request->notifyUsers('InvoicePaymentReceivedBySeller');
                      //   }
                      // } else {
                      //   if (
                      //     $payment_request->invoice?->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
                      //   ) {
                      //     foreach ($payment_request->invoice?->program->anchor->users as $user) {
                      //       $payment_request->notifyUsers('InvoicePaymentReceivedBySeller');
                      //     }
                      //   } else {
                      //     foreach ($payment_request->invoice?->buyer->users as $user) {
                      //       $payment_request->notifyUsers('InvoicePaymentReceivedBySeller');
                      //     }
                      //   }
                      // }

                      // // Notify relationship managers
                      // foreach ($payment_request->invoice->company->relationshipManagers as $user) {
                      //   SendMail::dispatch($user->email, 'LoanDisbursal', [
                      //     'invoice_id' => $payment_request->invoice->id,
                      //   ])->afterCommit();
                      // }

                      // Check program limit usage
                      if ($payment_request->invoice->program->programType->name == Program::DEALER_FINANCING) {
                        $sanctioned_limit = ProgramVendorConfiguration::where(
                          'program_id',
                          $payment_request->invoice->program_id
                        )
                          ->where('company_id', $payment_request->invoice->company_id)
                          ->first()->sanctioned_limit;
                        $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                          $payment_request->invoice->program
                        );
                        if ($utilized_amount >= $sanctioned_limit) {
                          // Notify company users
                          $payment_request->invoice->company->notify(
                            new ProgramLimitDepletion($payment_request->invoice->program)
                          );
                        }
                      } else {
                        if (
                          $payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                        ) {
                          $sanctioned_limit = ProgramVendorConfiguration::where(
                            'program_id',
                            $payment_request->invoice->program_id
                          )
                            ->where('company_id', $payment_request->invoice->company_id)
                            ->first()->sanctioned_limit;
                          $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                            $payment_request->invoice->program
                          );
                          if ($utilized_amount >= $sanctioned_limit) {
                            // Notify company users
                            $payment_request->invoice->company->notify(
                              new ProgramLimitDepletion($payment_request->invoice->program)
                            );
                          }
                        } else {
                          $sanctioned_limit = ProgramVendorConfiguration::where(
                            'program_id',
                            $payment_request->invoice->program_id
                          )
                            ->where('company_id', $payment_request->invoice->company_id)
                            ->where('buyer_id', $payment_request->invoice->buyer_id)
                            ->first()->sanctioned_limit;
                          $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                            $payment_request->invoice->program
                          );
                          if ($utilized_amount >= $sanctioned_limit) {
                            // Notify company users
                            $payment_request->invoice->company->notify(
                              new ProgramLimitDepletion($payment_request->invoice->program)
                            );
                          }
                        }
                      }
                    }
                  }

                  if (
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                  ) {
                    $payment_request->update([
                      'status' => 'failed',
                    ]);

                    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed') {
                      $payment_request->invoice->update([
                        'financing_status' => 'denied',
                      ]);

                      $program_vendor_configuration = ProgramVendorConfiguration::where(
                        'payment_account_number',
                        $transaction['debit_from_ac_no']
                      )->first();

                      // Update Program and Company Pipeline and Utilized Amounts if affects OD
                      if ($program_vendor_configuration) {
                        $payment_request->invoice->company->decrement(
                          'utilized_amount',
                          $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                            ? $payment_request->invoice->drawdown_amount
                            : ($payment_request->invoice->eligibility / 100) *
                              $payment_request->invoice->invoice_total_amount
                        );

                        $payment_request->invoice->program->decrement(
                          'utilized_amount',
                          $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                            ? $payment_request->invoice->drawdown_amount
                            : ($payment_request->invoice->eligibility / 100) *
                              $payment_request->invoice->invoice_total_amount
                        );

                        // $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $payment_request->invoice->company_id)
                        //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request->invoice) {
                        //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                        //   })
                        //   ->where('program_id', $payment_request->invoice->program_id)
                        //   ->first();

                        $program_vendor_configuration->decrement(
                          'utilized_amount',
                          $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
                            ? $payment_request->invoice->drawdown_amount
                            : ($payment_request->invoice->eligibility / 100) *
                              $payment_request->invoice->invoice_total_amount
                        );
                      }
                    }

                    foreach ($payment_request->invoice->program->bank->users as $user) {
                      SendMail::dispatchAfterResponse($user->email, 'DisbursementFailed', [
                        'id' => $payment_request->invoice->id,
                      ]);
                    }
                  }
                }
              }
            }
          }

          // Transaction is for repayment
          if (
            $transaction['transaction_type'] == CbsTransaction::REPAYMENT ||
            $transaction['transaction_type'] == CbsTransaction::BANK_INVOICE_PAYMENT ||
            $transaction['transaction_type'] == CbsTransaction::OVERDUE_ACCOUNT
          ) {
            $invoice = null;
            $vendor_configuration = null;
            if ($transaction['invoice_unique_ref_no'] != '') {
              $invoice = Invoice::where('invoice_number', $transaction['invoice_unique_ref_no'])->first();

              if ($invoice) {
                if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
                  if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                    $vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                      ->where('program_id', $invoice->program_id)
                      ->first();
                  } else {
                    $vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                      ->where('program_id', $invoice->program_id)
                      ->where('buyer_id', $invoice->buyer_id)
                      ->first();
                  }
                } else {
                  $vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                    ->where('program_id', $invoice->program_id)
                    ->first();
                }
              }
            }

            if (!$vendor_configuration) {
              $vendor_configuration = ProgramVendorConfiguration::where(
                'payment_account_number',
                $transaction['credit_to_ac_no']
              )->first();
            }

            if ($vendor_configuration) {
              $payment_request = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company')
                ->whereIn('status', ['created', 'approved'])
                ->whereHas('invoice', function ($query) use ($vendor_configuration) {
                  $query->where('program_id', $vendor_configuration->program_id)->where('financing_status', 'financed');
                })
                ->first();

              if ($payment_request) {
                $cbs_transaction = CbsTransaction::create([
                  'bank_id' => $vendor_configuration->program->bank->id,
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account' => $transaction['debit_from_ac_no'],
                  'credit_to_account' => $transaction['credit_to_ac_no'],
                  'amount' => $transaction['amount_ksh'],
                  'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
                  'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
                  'pay_date' => $transaction['pay_date_ddmmyyyy'],
                  'transaction_reference' => $transaction['transaction_reference_no'],
                  'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
                  'transaction_type' => $transaction['transaction_type'],
                  'product' => $transaction['product'],
                ]);

                if ($payment_request->invoice->financing_status == 'financed') {
                  if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                    if (round($transaction['amount_ksh']) <= round($payment_request->invoice->balance)) {
                      Payment::create([
                        'invoice_id' => $payment_request->invoice->id,
                        'amount' => $transaction['amount_ksh'],
                        'credit_date' => $credit_date,
                      ]);

                      $payment_request->invoice->increment('calculated_paid_amount', $transaction['amount_ksh']);

                      if (round($payment_request->invoice->balance) <= 0) {
                        $payment_request->invoice->update([
                          'financing_status' => 'closed',
                        ]);

                        $program_vendor_configuration = ProgramVendorConfiguration::where(
                          'payment_account_number',
                          $transaction['credit_to_ac_no']
                        )->first();

                        if ($program_vendor_configuration) {
                          $invoice->company->decrement(
                            'utilized_amount',
                            $invoice->program->programType->name == Program::DEALER_FINANCING
                              ? $invoice->drawdown_amount
                              : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                          );

                          $invoice->program->decrement(
                            'utilized_amount',
                            $invoice->program->programType->name == Program::DEALER_FINANCING
                              ? $invoice->drawdown_amount
                              : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                          );

                          // $program_vendor_configuration = ProgramVendorConfiguration::where(
                          //   'company_id',
                          //   $invoice->company_id
                          // )
                          //   ->when($invoice->buyer_id, function ($query) use ($invoice) {
                          //     $query->where('buyer_id', $invoice->buyer_id);
                          //   })
                          //   ->where('program_id', $invoice->program_id)
                          //   ->first();

                          $program_vendor_configuration->decrement(
                            'utilized_amount',
                            $invoice->program->programType->name == Program::DEALER_FINANCING
                              ? $invoice->drawdown_amount
                              : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                          );
                        }

                        array_push($this->closed_invoices, $payment_request->invoice->id);

                        // // Notify vendor
                        // $payment_request->notifyUsers('LoanClosing');
                        // $payment_request->notifyUsers('FullRepayment');

                        // if (round($transaction['amount_ksh']) != round($payment_request->invoice->balance)) {
                        //   $payment_request->notifyUsers('BalanceInvoicePayment');
                        // }

                        // // If loan was overdue, send overdue repayment mail
                        // if (Carbon::parse($payment_request->invoice->due_date)->lessThan($credit_date)) {
                        //   $payment_request->notifyUsers('OverdueFullRepayment');
                        // }
                      } else {
                        // $payment_request->notifyUsers('PartialRepayment');
                      }

                      // $payment_request->notifyUsers('InvoicePaymentProcessed');
                    }
                  }

                  if (
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                  ) {
                    foreach ($payment_request->invoice->program->bank->users as $user) {
                      SendMail::dispatchAfterResponse($user->email, 'CbsTransactionForLoanSettlementFailed', [
                        'cbs_transaction_id' => $cbs_transaction->id,
                      ]);
                    }
                  }
                }
              }

              if (!$payment_request && $invoice) {
                $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
                $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

                $prefix = '';
                $penal_discount_income_bank_account = null;
                $fees_income_bank_account = null;
                $tax_income_bank_account = null;

                if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
                  if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                    $prefix = 'VFR' . $invoice->program->bank_id;
                    $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank->id)
                      ->where('section', 'Vendor Finance Receivable')
                      ->where('product_code_id', null)
                      ->where('product_type_id', $invoice->program->program_type_id)
                      ->where('name', 'Fee Income Account')
                      ->first();
                    $penal_discount_income_bank_account = BankProductsConfiguration::where(
                      'bank_id',
                      $invoice->program->bank->id
                    )
                      ->where('section', 'Vendor Finance Receivable')
                      ->where('product_code_id', null)
                      ->where('product_type_id', $vendor_financing->id)
                      ->where('name', 'Penal Discount Income Account')
                      ->first();
                  } else {
                    $prefix = 'FR' . $invoice->program->bank_id;
                    if ($invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                      // Factoring without recourse
                      $fees_income_bank_account = BankProductsConfiguration::where(
                        'bank_id',
                        $invoice->program->bank->id
                      )
                        ->where('section', 'Factoring Without Recourse')
                        ->where('product_type_id', $invoice->program->program_type_id)
                        ->where('product_code_id', $invoice->program->program_code_id)
                        ->where('name', 'Fee Income Account')
                        ->first();
                      $penal_discount_income_bank_account = BankProductsConfiguration::where(
                        'bank_id',
                        $invoice->program->bank->id
                      )
                        ->where('section', 'Factoring Without Recourse')
                        ->where('product_type_id', $vendor_financing->id)
                        ->where('product_code_id', $invoice->program->program_code_id)
                        ->where('name', 'Penal Discount Income Account')
                        ->first();
                    } else {
                      // Factoring with recourse
                      $fees_income_bank_account = BankProductsConfiguration::where(
                        'bank_id',
                        $invoice->program->bank->id
                      )
                        ->where('section', 'Factoring With Recourse')
                        ->where('product_type_id', $invoice->program->program_type_id)
                        ->where('product_code_id', $invoice->program->program_code_id)
                        ->where('name', 'Fee Income Account')
                        ->first();
                      $penal_discount_income_bank_account = BankProductsConfiguration::where(
                        'bank_id',
                        $invoice->program->bank->id
                      )
                        ->where('section', 'Factoring With Recourse')
                        ->where('product_type_id', $vendor_financing->id)
                        ->where('product_code_id', $invoice->program->program_code_id)
                        ->where('name', 'Penal Discount Income Account')
                        ->first();
                    }
                  }
                } else {
                  $prefix = 'DF' . $invoice->program->bank_id;
                  $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank->id)
                    ->where('product_type_id', $invoice->program->programType->id)
                    ->where('product_code_id', null)
                    ->where('name', 'Fee Income Account')
                    ->first();
                  $penal_discount_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $invoice->program->bank->id
                  )
                    ->where('product_type_id', $dealer_financing->id)
                    ->where('product_code_id', null)
                    ->where('name', 'Penal Discount Income Account')
                    ->first();
                }

                $reference_number = '';

                if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
                  if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                    $reference_number = 'VFR' . $transaction['credit_to_ac_no'] . '000' . $invoice->id;
                  } else {
                    $reference_number = 'FR' . $transaction['credit_to_ac_no'] . '000' . $invoice->id;
                  }
                } else {
                  $reference_number = 'DF' . $transaction['credit_to_ac_no'] . '000' . $invoice->id;
                }

                // Create payment request
                $payment_request = PaymentRequest::create([
                  'invoice_id' => $invoice->id,
                  'amount' => $transaction['amount_ksh'],
                  'payment_request_date' => $credit_date,
                  'status' => 'created',
                  'reference_number' => $reference_number,
                ]);

                $payment_request->paymentAccounts()->create([
                  'account' => $transaction['credit_to_ac_no'],
                  'account_name' => $transaction['credit_to_ac_name'],
                  'amount' => $transaction['amount_ksh'],
                  'type' => Carbon::parse($invoice->due_date)->lessThan(now()) ? 'interest' : 'repayment',
                  'description' => Carbon::parse($invoice->due_date)->greaterThanOrEqualTo(now())
                    ? ($invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? CbsTransaction::BANK_INVOICE_PAYMENT
                      : CbsTransaction::REPAYMENT)
                    : CbsTransaction::OVERDUE_ACCOUNT,
                ]);

                $cbs_transaction = CbsTransaction::create([
                  'bank_id' => $payment_request->invoice->program->bank->id,
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account' => $transaction['debit_from_ac_no'],
                  'credit_to_account' => $transaction['credit_to_ac_no'],
                  'amount' => $transaction['amount_ksh'],
                  'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
                  'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
                  'pay_date' => $transaction['pay_date_ddmmyyyy'],
                  'transaction_reference' => $transaction['transaction_reference_no'],
                  'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
                  'transaction_type' => $transaction['transaction_type'],
                  'product' => $transaction['product'],
                ]);

                if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
                  $payment_request->update([
                    'status' => 'paid',
                    'approval_status' => 'paid',
                  ]);
                }

                if ($payment_request->invoice->financing_status === 'financed') {
                  if (
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful' &&
                    round($transaction['amount_ksh']) <= round($payment_request->invoice->balance)
                  ) {
                    Payment::create([
                      'invoice_id' => $payment_request->invoice->id,
                      'amount' => $transaction['amount_ksh'],
                      'credit_date' => $credit_date,
                    ]);

                    $payment_request->invoice->increment('calculated_paid_amount', $transaction['amount_ksh']);

                    // $payment_request->notifyUsers('InvoicePaymentProcessed');

                    if (round($payment_request->invoice->balance) <= 0) {
                      $payment_request->invoice->update([
                        'financing_status' => 'closed',
                      ]);

                      array_push($this->closed_invoices, $payment_request->invoice->id);

                      $program_vendor_configuration = ProgramVendorConfiguration::where(
                        'payment_account_number',
                        $transaction['credit_to_ac_no']
                      )->first();

                      // Update Program and Company Pipeline and Utilized Amounts if affects OD
                      if ($program_vendor_configuration) {
                        $invoice->company->decrement(
                          'utilized_amount',
                          $invoice->program->programType->name == Program::DEALER_FINANCING
                            ? $invoice->drawdown_amount
                            : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                        );

                        $invoice->program->decrement(
                          'utilized_amount',
                          $invoice->program->programType->name == Program::DEALER_FINANCING
                            ? $invoice->drawdown_amount
                            : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                        );

                        $program_vendor_configuration->decrement(
                          'utilized_amount',
                          $invoice->program->programType->name == Program::DEALER_FINANCING
                            ? $invoice->drawdown_amount
                            : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
                        );
                      }

                      // // Notify vendor
                      // $payment_request->notifyUsers('LoanClosing');
                      // $payment_request->notifyUsers('FullRepayment');

                      // if ($transaction['amount_ksh'] != $payment_request->invoice->balance) {
                      //   $payment_request->notifyUsers('BalanceInvoicePayment');
                      // }

                      // // If loan was overdue, send overdue repayment mail
                      // if (Carbon::parse($payment_request->invoice->due_date)->lessThan($credit_date)) {
                      //   $payment_request->notifyUsers('OverdueFullRepayment');
                      // }
                    } else {
                      // $payment_request->notifyUsers('PartialRepayment');
                    }
                  }

                  if (
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                  ) {
                    foreach ($payment_request->invoice->program->bank->users as $user) {
                      SendMail::dispatchAfterResponse($user->email, 'CbsTransactionForLoanSettlementFailed', [
                        'cbs_transaction_id' => $cbs_transaction->id,
                      ]);
                    }
                  }
                }
              }

              // Debiting OD Account
              // Reduce amount in payments made on OD Account
              // if (!$payment_request && !$invoice) {
              //   if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
              //     // Get OD Account Details
              //     $program_vendor_configuration = ProgramVendorConfiguration::where(
              //       'payment_account_number',
              //       $transaction['debit_from_ac_no']
              //     )->first();

              //     if ($program_vendor_configuration) {
              //       // Get invoices made for payment on the program
              //       $payment = Payment::whereHas('invoice', function ($query) use ($program_vendor_configuration) {
              //         $query->where('program_id', $program_vendor_configuration->program_id);
              //       })
              //         ->latest()
              //         ->first();

              //       $remainder = $transaction['amount_ksh'];

              //       do {
              //         $payment = Payment::whereHas('invoice', function ($query) use ($program_vendor_configuration) {
              //           $query->where('program_id', $program_vendor_configuration->program_id);
              //         })
              //           ->latest()
              //           ->first();

              //         if ($payment) {
              //           // Subtract payment amount
              //           $payment->update([
              //             'amount' => $transaction['amount_ksh'] - $payment->amount,
              //           ]);

              //           $remainder = $remainder - $payment->amount;

              //           // If payment remainder is less than zero, delete payment
              //           if ($payment->amount <= 0) {
              //             $payment->delete();
              //           }
              //         }
              //       } while ($payment && $remainder > 0);
              //     }
              //   }
              // }
            }
          }

          // Crediting OD Account/Repayment
          if ($transaction['transaction_type'] == CbsTransaction::FUNDS_TRANSFER) {
            $vendor_configuration = ProgramVendorConfiguration::where(
              'payment_account_number',
              $transaction['credit_to_ac_no']
            )->first();

            if ($vendor_configuration) {
              $company = ProgramBankDetails::whereHas('program', function ($query) use ($vendor_configuration) {
                $query->where('id', $vendor_configuration->program_id);
              })
                ->where('account_number', $transaction['debit_from_ac_no'])
                ->first();

              if ($company) {
                // Get first financed invoiced
                $invoice = Invoice::where('company_id', $vendor_configuration->company_id)
                  ->where('program_id', $vendor_configuration->program_id)
                  ->where('financing_status', 'disbursed')
                  ->orderBy('created_at', 'ASC')
                  ->first();

                $remainder = $transaction['amount_ksh'];

                do {
                  $reference_number = '';

                  $words = explode(' ', $invoice->company->name);
                  $acronym = '';

                  foreach ($words as $w) {
                    $acronym .= mb_substr($w, 0, 1);
                  }

                  if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
                    if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                      $reference_number = 'VFR' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
                    } else {
                      $reference_number = 'FR' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
                    }
                  } else {
                    $reference_number = 'DF' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
                  }

                  $payment_amount = $remainder > $invoice->balance ? $invoice->balance : $remainder;

                  // Create payment request
                  $payment_request = PaymentRequest::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $payment_amount,
                    'payment_request_date' => $credit_date,
                    'status' => 'created',
                    'reference_number' => $reference_number,
                  ]);

                  $payment_request->paymentAccounts()->create([
                    'account' => $transaction['credit_to_ac_no'],
                    'account_name' => $transaction['credit_to_ac_name'],
                    'amount' => $$payment_amount,
                    'type' => Carbon::parse($invoice->due_date)->lessThan(now()) ? 'interest' : 'repayment',
                    'description' => Carbon::parse($invoice->due_date)->greaterThanOrEqualTo(now())
                      ? ($invoice->program->programType->name == Program::VENDOR_FINANCING
                        ? CbsTransaction::BANK_INVOICE_PAYMENT
                        : CbsTransaction::REPAYMENT)
                      : CbsTransaction::OVERDUE_ACCOUNT,
                  ]);

                  $cbs_transaction = CbsTransaction::create([
                    'bank_id' => $vendor_configuration->program->bank->id,
                    'payment_request_id' => $payment_request->id,
                    'debit_from_account' => $transaction['debit_from_ac_no'],
                    'credit_to_account' => $transaction['credit_to_ac_no'],
                    'amount' => $payment_amount,
                    'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
                    'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
                    'pay_date' => $transaction['pay_date_ddmmyyyy'],
                    'transaction_reference' => $transaction['transaction_reference_no'],
                    'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
                    'transaction_type' => $transaction['transaction_type'],
                    'product' => $transaction['product'],
                  ]);

                  if (
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Failed' ||
                    $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed'
                  ) {
                    $payment_request->update([
                      'status' => 'failed',
                    ]);

                    foreach ($payment_request->program->bank->users as $user) {
                      SendMail::dispatchAfterResponse($user->email, 'CbsTransactionForOdCreditFailed', [
                        'cbs_transaction_id' => $cbs_transaction->id,
                      ]);
                    }
                  }

                  $remainder -= $invoice->balance;

                  if ($remainder > 0) {
                    $invoice = Invoice::where('company_id', $company->id)
                      ->where('financing_status', 'financed')
                      ->orderBy('created_at', 'ASC')
                      ->first();
                  } else {
                    $invoice = null;
                  }
                } while ($invoice && $remainder > 0);
              }
            }
          }
        }
      });
    }
  }
}
