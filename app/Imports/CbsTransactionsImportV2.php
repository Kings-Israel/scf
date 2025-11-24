<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Jobs\LoanClosingNotification;
use App\Jobs\LoanDisbursalNotification;
use App\Jobs\SendMail;
use App\Models\Bank;
use App\Models\CbsTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentRequestAccount;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;

class CbsTransactionsImportV2 implements
  ToModel,
  WithHeadingRow,
  SkipsOnFailure,
  SkipsEmptyRows,
  WithValidation,
  WithMapping,
  WithEvents
{
  use Importable, SkipsFailures;

  protected $disbursed_invoices = [];
  protected $closed_invoices = [];
  public $data = 0;
  public $total_rows = 0;
  protected $bank;
  protected $notifiedUsers = []; // Track notified users for deduplication
  protected $notifications = []; // Collect notifications to dispatch
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
      'transaction_reference_no.required_if' => 'Enter the transaction reference no.',
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

  public function model(array $transaction)
  {
    $credit_date = $transaction['transaction_date_ddmmyyyy'];

    DB::transaction(function () use ($transaction, $credit_date) {
      if (!empty($transaction['cbs_id'])) {
        $this->processCbsTransaction($transaction, $credit_date);
      } else {
        $this->processNonCbsTransaction($transaction, $credit_date);
      }

      $this->data++;
    });

    // Dispatch batched notifications for disbursed and closed invoices
    if (!empty($this->disbursed_invoices)) {
      LoanDisbursalNotification::dispatch(array_unique($this->disbursed_invoices))->afterCommit();
    }

    if (!empty($this->closed_invoices)) {
      LoanClosingNotification::dispatch(array_unique($this->closed_invoices))->afterCommit();
    }

    // Dispatch collected notifications with idempotency
    $this->dispatchNotifications();
  }

  protected function processCbsTransaction($transaction, $credit_date)
  {
    $invoice = $this->getInvoice($transaction);
    $cbs_transaction = CbsTransaction::where('id', $transaction['cbs_id'])
      ->where('bank_id', $this->bank->id)
      ->first();

    if ($cbs_transaction && $cbs_transaction->status != 'Successful') {
      $this->updateCbsTransaction($cbs_transaction, $transaction);
      $payment_request = $cbs_transaction->paymentRequest;

      if ($payment_request) {
        $this->handlePaymentRequest($payment_request, $transaction, $cbs_transaction, $credit_date);
      }

      if (!$payment_request && $invoice) {
        $this->handleInvoiceWithoutPaymentRequest($invoice, $transaction, $cbs_transaction, $credit_date);
      }

      if (!$payment_request && !$invoice) {
        $this->handleNoPaymentRequestOrInvoice($transaction, $cbs_transaction);
      }

      $this->logActivity($cbs_transaction, $transaction);
    }
  }

  protected function processNonCbsTransaction($transaction, $credit_date)
  {
    if (
      in_array($transaction['transaction_type'], [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::OD_DRAWDOWN,
        CbsTransaction::FEES_CHARGES,
      ])
    ) {
      $this->handleDisbursement($transaction, $credit_date);
    }

    if (
      in_array($transaction['transaction_type'], [
        CbsTransaction::REPAYMENT,
        CbsTransaction::BANK_INVOICE_PAYMENT,
        CbsTransaction::OVERDUE_ACCOUNT,
      ])
    ) {
      $this->handleRepayment($transaction, $credit_date);
    }

    if ($transaction['transaction_type'] == CbsTransaction::FUNDS_TRANSFER) {
      $this->handleFundsTransfer($transaction, $credit_date);
    }
  }

  protected function getInvoice($transaction)
  {
    return !empty($transaction['invoice_unique_ref_no'])
      ? Invoice::where('invoice_number', $transaction['invoice_unique_ref_no'])->first()
      : null;
  }

  protected function updateCbsTransaction($cbs_transaction, $transaction)
  {
    $cbs_transaction->update([
      'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
      'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
      'pay_date' => $transaction['pay_date_ddmmyyyy'],
      'transaction_reference' => $transaction['transaction_reference_no'] ?: null,
      'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
    ]);
  }

  protected function handlePaymentRequest($payment_request, $transaction, $cbs_transaction, $credit_date)
  {
    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
      $payment_request->update(['status' => 'paid', 'approval_status' => 'paid']);
      $this->processSuccessfulPayment($payment_request, $transaction, $cbs_transaction, $credit_date);
    } elseif (
      in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
    ) {
      $this->processFailedPayment($payment_request, $transaction, $cbs_transaction);
    }
  }

  protected function processSuccessfulPayment($payment_request, $transaction, $cbs_transaction, $credit_date)
  {
    $invoice = $payment_request->invoice;
    $transaction_type = $transaction['transaction_type'];

    if (
      in_array($transaction_type, [
        CbsTransaction::OVERDUE_ACCOUNT,
        CbsTransaction::REPAYMENT,
        CbsTransaction::BANK_INVOICE_PAYMENT,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
        CbsTransaction::FEES_CHARGES,
      ]) &&
      $invoice->financing_status == 'financed'
    ) {
      if (
        ($transaction_type == CbsTransaction::OVERDUE_ACCOUNT && $invoice->financing_status == 'financed') ||
        ($transaction_type == CbsTransaction::REPAYMENT || $transaction_type == CbsTransaction::BANK_INVOICE_PAYMENT) ||
        ($transaction_type == CbsTransaction::ACCRUAL_POSTED_INTEREST &&
          $invoice->discount_charge_type == Invoice::REAR_ENDED) ||
        ($transaction_type == CbsTransaction::FEES_CHARGES && $invoice->fee_charge_type == Invoice::REAR_ENDED)
      ) {
        Payment::create([
          'invoice_id' => $invoice->id,
          'amount' => $cbs_transaction->amount,
          'credit_date' => $credit_date,
        ]);

        $invoice->increment('calculated_paid_amount', $cbs_transaction->amount);
      }
    }

    if (
      in_array($transaction_type, [
        CbsTransaction::PAYMENT_DISBURSEMENT,
        CbsTransaction::OD_DRAWDOWN,
        CbsTransaction::ACCRUAL_POSTED_INTEREST,
        CbsTransaction::FEES_CHARGES,
      ]) &&
      in_array($invoice->financing_status, ['pending', 'submitted'])
    ) {
      if ($cbs_transaction->paymentRequest->invoice->all_transactions_successful) {
        $this->disburseInvoice($payment_request, $transaction, $cbs_transaction);
      }
    }
  }

  protected function processFailedPayment($payment_request, $transaction, $cbs_transaction)
  {
    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed') {
      $payment_request->update(['status' => 'failed', 'approval_status' => 'rejected']);
      $payment_request->invoice->update(['financing_status' => 'denied', 'stage' => 'rejected']);
      $this->updateUtilizedAmounts($payment_request->invoice, $cbs_transaction->amount, true);
    }

    $this->addNotification(
      'CbsTransactionForLoanSettlementFailed',
      [
        'cbs_transaction_id' => $cbs_transaction->id,
        'users' => $payment_request->invoice->program->bank->users->pluck('email')->toArray(),
      ],
      "cbs_transaction_{$cbs_transaction->id}_failed"
    );
  }

  protected function handleInvoiceWithoutPaymentRequest($invoice, $transaction, $cbs_transaction, $credit_date)
  {
    if (
      in_array($transaction['transaction_type'], [CbsTransaction::PAYMENT_DISBURSEMENT, CbsTransaction::OD_DRAWDOWN]) &&
      in_array($invoice->financing_status, ['pending', 'submitted'])
    ) {
      if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
        $cbs_transaction->paymentRequest->update(['status' => 'paid', 'approval_status' => 'paid']);
        $invoice->update([
          'disbursement_date' => $transaction['pay_date_ddmmyyyy'],
          'disbursed_amount' => $cbs_transaction->amount,
          'financing_status' => 'financed',
          'status' => 'disbursed',
          'stage' => 'disbursed',
        ]);

        array_push($this->disbursed_invoices, $invoice->id);
        $this->checkProgramLimit($invoice);
      } elseif (
        in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
      ) {
        $this->addNotification(
          'DisbursementFailed',
          [
            'invoice_id' => $invoice->id,
            'users' => $invoice->program->bank->users->pluck('email')->toArray(),
          ],
          "disbursement_{$invoice->id}_failed"
        );
      }
    }

    if (
      in_array($transaction['transaction_type'], [
        CbsTransaction::FEES_CHARGES,
        CbsTransaction::OVERDUE_ACCOUNT,
        CbsTransaction::REPAYMENT,
        CbsTransaction::BANK_INVOICE_PAYMENT,
      ]) &&
      $invoice->financing_status == 'financed' &&
      Carbon::parse($invoice->due_date)->lessThanOrEqualTo(now()->format('Y-m-d'))
    ) {
      if (
        $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful' &&
        round($cbs_transaction->amount) <= round($invoice->balance)
      ) {
        Payment::create([
          'invoice_id' => $invoice->id,
          'amount' => $cbs_transaction->amount,
          'credit_date' => $credit_date,
        ]);

        $invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

        $this->addNotification('InvoicePaymentProcessed', [
          'invoice_id' => $invoice->id,
          'users' => $invoice->company->users->pluck('email')->toArray(),
        ]);

        if (round($invoice->balance) <= 0) {
          $this->closeInvoice($invoice, $cbs_transaction, $transaction);
        } else {
          $this->addNotification('PartialRepayment', [
            'invoice_id' => $invoice->id,
            'users' => $invoice->company->users->pluck('email')->toArray(),
          ]);
        }
      } elseif (
        in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
      ) {
        $this->addNotification(
          'CbsTransactionForLoanSettlementFailed',
          [
            'cbs_transaction_id' => $cbs_transaction->id,
            'users' => $invoice->program->bank->users->pluck('email')->toArray(),
          ],
          "cbs_transaction_{$cbs_transaction->id}_failed"
        );
      }
    }

    if (
      $transaction['transaction_type'] == CbsTransaction::FUNDS_TRANSFER &&
      in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
    ) {
      $this->addNotification(
        'CbsTransactionForOdCreditFailed',
        [
          'cbs_transaction_id' => $cbs_transaction->id,
          'users' => $cbs_transaction->paymentRequest?->invoice->program->bank->users->pluck('email')->toArray() ?? [],
        ],
        "cbs_transaction_{$cbs_transaction->id}_od_failed"
      );
    }
  }

  protected function handleNoPaymentRequestOrInvoice($transaction, $cbs_transaction)
  {
    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
      $program_vendor_configuration = ProgramVendorConfiguration::where(
        'payment_account_number',
        $transaction['debit_from_ac_no']
      )->first();

      if ($program_vendor_configuration) {
        $remainder = $cbs_transaction->amount;
        do {
          $payment = Payment::whereHas('invoice', function ($query) use ($program_vendor_configuration) {
            $query->where('program_id', $program_vendor_configuration->program_id);
          })
            ->latest()
            ->first();

          if ($payment) {
            $new_amount = max(0, $payment->amount - $remainder);
            $remainder -= $payment->amount;
            $payment->update(['amount' => $new_amount]);
            if ($new_amount <= 0) {
              $payment->delete();
            }
          }
        } while ($payment && $remainder > 0);
      }
    }
  }

  protected function handleDisbursement($transaction, $credit_date)
  {
    $vendor_configuration = ProgramVendorConfiguration::where(
      'payment_account_number',
      $transaction['debit_from_ac_no']
    )->first();

    if ($vendor_configuration) {
      $vendor_bank_details = $this->getVendorBankDetails($vendor_configuration, $transaction);
      if ($vendor_bank_details) {
        $payment_request = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company')
          ->whereHas('invoice', function ($query) use ($vendor_bank_details) {
            $query->where('program_id', $vendor_bank_details->program_id)->where('eligible_for_financing', true);
          })
          ->first();

        if ($payment_request) {
          $cbs_transaction = $this->createCbsTransaction($payment_request, $transaction);
          if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
            $this->disburseInvoice($payment_request, $transaction, $cbs_transaction);
          } elseif (
            in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
          ) {
            $this->processFailedDisbursement($payment_request, $transaction, $cbs_transaction);
          }
        }
      }
    }
  }

  protected function handleRepayment($transaction, $credit_date)
  {
    $invoice = $this->getInvoice($transaction);
    $vendor_configuration = $this->getVendorConfiguration($transaction, $invoice);

    if ($vendor_configuration) {
      $payment_request = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company')
        ->whereIn('status', ['created', 'approved'])
        ->whereHas('invoice', function ($query) use ($vendor_configuration) {
          $query->where('program_id', $vendor_configuration->program_id)->where('financing_status', 'financed');
        })
        ->first();

      if ($payment_request) {
        $cbs_transaction = $this->createCbsTransaction($payment_request, $transaction);
        if (
          $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful' &&
          round($transaction['amount_ksh']) <= round($payment_request->invoice->balance)
        ) {
          $this->processSuccessfulRepayment($payment_request, $transaction, $cbs_transaction, $credit_date);
        } elseif (
          in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
        ) {
          $this->addNotification(
            'CbsTransactionForLoanSettlementFailed',
            [
              'cbs_transaction_id' => $cbs_transaction->id,
              'users' => $payment_request->invoice->program->bank->users->pluck('email')->toArray(),
            ],
            "cbs_transaction_{$cbs_transaction->id}_failed"
          );
        }
      } elseif ($invoice) {
        $this->createPaymentRequestForInvoice($invoice, $transaction, $credit_date);
      }
    }
  }

  protected function handleFundsTransfer($transaction, $credit_date)
  {
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
        $remainder = $transaction['amount_ksh'];
        $invoice = Invoice::where('company_id', $vendor_configuration->company_id)
          ->where('program_id', $vendor_configuration->program_id)
          ->where('financing_status', 'financed')
          ->orderBy('created_at', 'ASC')
          ->first();

        while ($invoice && $remainder > 0) {
          $payment_request = $this->createPaymentRequest($invoice, $transaction, $credit_date, $remainder);
          $cbs_transaction = $this->createCbsTransaction(
            $payment_request,
            $transaction,
            min($remainder, $invoice->balance)
          );
          if (
            in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
          ) {
            $this->addNotification(
              'CbsTransactionForOdCreditFailed',
              [
                'cbs_transaction_id' => $cbs_transaction->id,
                'users' => $payment_request->program->bank->users->pluck('email')->toArray(),
              ],
              "cbs_transaction_{$cbs_transaction->id}_od_failed"
            );
          }
          $remainder -= $invoice->balance;
          $invoice =
            $remainder > 0
              ? Invoice::where('company_id', $company->id)
                ->where('financing_status', 'financed')
                ->orderBy('created_at', 'ASC')
                ->first()
              : null;
        }
      }
    }
  }

  protected function closeInvoice($invoice, $cbs_transaction, $transaction)
  {
    $invoice->update(['financing_status' => 'closed', 'stage' => 'closed']);
    array_push($this->closed_invoices, $invoice->id);

    $program_vendor_configuration = ProgramVendorConfiguration::where(
      'payment_account_number',
      $cbs_transaction->credit_to_account
    )->first();

    if ($program_vendor_configuration) {
      $this->updateUtilizedAmounts(
        $invoice,
        $invoice->program->programType->name == Program::DEALER_FINANCING
          ? $invoice->drawdown_amount
          : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
      );
    }

    $this->addNotification(
      'LoanClosing',
      [
        'invoice_id' => $invoice->id,
        'users' => $invoice->company->users->pluck('email')->toArray(),
      ],
      "loan_closing_{$invoice->id}"
    );
  }

  protected function disburseInvoice($payment_request, $transaction, $cbs_transaction)
  {
    $requested_amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) use ($cbs_transaction) {
      $q->where('invoice_id', $cbs_transaction->paymentRequest->invoice_id);
    })
      ->where('type', 'vendor_account')
      ->first()->amount;

    $payment_request->invoice->update([
      'disbursement_date' => $transaction['pay_date_ddmmyyyy'],
      'disbursed_amount' => round($requested_amount, 2),
      'financing_status' => 'financed',
      'status' => 'disbursed',
      'stage' => 'disbursed',
      'eligible_for_financing' => false,
    ]);

    array_push($this->disbursed_invoices, $payment_request->invoice->id);

    $this->addNotification(
      'LoanDisbursal',
      [
        'invoice_id' => $payment_request->invoice->id,
        'users' => array_merge(
          $payment_request->invoice->company->users->pluck('email')->toArray(),
          $payment_request->invoice->company->relationshipManagers->pluck('email')->toArray()
        ),
      ],
      "loan_disbursal_{$payment_request->invoice->id}"
    );

    $this->checkProgramLimit($payment_request->invoice);
  }

  protected function processFailedDisbursement($payment_request, $transaction, $cbs_transaction)
  {
    $payment_request->update(['status' => 'failed']);
    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Permanently Failed') {
      $payment_request->invoice->update(['financing_status' => 'denied']);
      $this->updateUtilizedAmounts($payment_request->invoice, $cbs_transaction->amount, true);
    }

    $this->addNotification(
      'DisbursementFailed',
      [
        'invoice_id' => $payment_request->invoice->id,
        'users' => $payment_request->invoice->program->bank->users->pluck('email')->toArray(),
      ],
      "disbursement_{$payment_request->invoice->id}_failed"
    );
  }

  protected function processSuccessfulRepayment($payment_request, $transaction, $cbs_transaction, $credit_date)
  {
    Payment::create([
      'invoice_id' => $payment_request->invoice->id,
      'amount' => $cbs_transaction->amount,
      'credit_date' => $credit_date,
    ]);

    $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);
  }

  protected function updateUtilizedAmounts($invoice, $amount, $increment = false)
  {
    $method = $increment ? 'increment' : 'decrement';
    $invoice->company->$method('utilized_amount', $amount);
    $invoice->program->$method('utilized_amount', $amount);
    ProgramVendorConfiguration::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->when($invoice->buyer_id, function ($query) use ($invoice) {
        $query->where('buyer_id', $invoice->buyer_id);
      })
      ->$method('utilized_amount', $amount);
  }

  protected function checkProgramLimit($invoice)
  {
    $program = $invoice->program;
    $config = ProgramVendorConfiguration::where('program_id', $program->id)
      ->where('company_id', $invoice->company_id)
      ->when($invoice->buyer_id, function ($query) use ($invoice) {
        $query->where('buyer_id', $invoice->buyer_id);
      })
      ->first();

    if ($config && $invoice->company->utilizedAmount($program) >= $config->sanctioned_limit) {
      $this->addNotification(
        'ProgramLimitDepletion',
        [
          'program_id' => $program->id,
          'users' => $invoice->company->users->pluck('email')->toArray(),
        ],
        "program_limit_{$program->id}"
      );
    }
  }

  protected function getVendorBankDetails($vendor_configuration, $transaction)
  {
    $details = ProgramBankDetails::where('program_id', $vendor_configuration->program_id)
      ->where('account_number', $transaction['credit_to_ac_no'])
      ->first();

    return $details ?:
      ProgramVendorBankDetail::where('program_id', $vendor_configuration->program_id)
        ->where('company_id', $vendor_configuration->company_id)
        ->where('account_number', $transaction['credit_to_ac_no'])
        ->first();
  }

  protected function getVendorConfiguration($transaction, $invoice)
  {
    if ($invoice) {
      return ProgramVendorConfiguration::where('company_id', $invoice->company_id)
        ->where('program_id', $invoice->program_id)
        ->when($invoice->buyer_id && $invoice->program->programType->name == Program::VENDOR_FINANCING, function (
          $query
        ) use ($invoice) {
          $query->where('buyer_id', $invoice->buyer_id);
        })
        ->first();
    }
    return ProgramVendorConfiguration::where('payment_account_number', $transaction['credit_to_ac_no'])->first();
  }

  protected function createCbsTransaction($payment_request, $transaction, $amount = null)
  {
    return CbsTransaction::create([
      'bank_id' => $payment_request->invoice->program->bank->id,
      'payment_request_id' => $payment_request->id,
      'debit_from_account' => $transaction['debit_from_ac_no'],
      'credit_to_account' => $transaction['credit_to_ac_no'],
      'amount' => $amount ?? $transaction['amount_ksh'],
      'transaction_created_date' => $transaction['transaction_created_date_ddmmyyyy'],
      'transaction_date' => $transaction['transaction_date_ddmmyyyy'],
      'pay_date' => $transaction['pay_date_ddmmyyyy'],
      'transaction_reference' => $transaction['transaction_reference_no'],
      'status' => $transaction['status_createdsuccessfulfailedpermanently_failed'],
      'transaction_type' => $transaction['transaction_type'],
      'product' => $transaction['product'],
    ]);
  }

  protected function createPaymentRequest($invoice, $transaction, $credit_date, $amount)
  {
    $acronym = collect(explode(' ', $invoice->company->name))
      ->map(fn($w) => mb_substr($w, 0, 1))
      ->join('');
    $prefix =
      $invoice->program->programType->name == Program::VENDOR_FINANCING
        ? ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
          ? 'VFR'
          : 'FR')
        : 'DF';
    $reference_number = $prefix . $invoice->program->bank_id . $acronym . '000' . $invoice->id;

    $payment_request = PaymentRequest::create([
      'invoice_id' => $invoice->id,
      'amount' => $amount,
      'payment_request_date' => $credit_date,
      'status' => 'created',
      'reference_number' => $reference_number,
    ]);

    $payment_request->paymentAccounts()->create([
      'account' => $transaction['credit_to_ac_no'],
      'account_name' => $transaction['credit_to_ac_name'],
      'amount' => $amount,
      'type' => Carbon::parse($invoice->due_date)->lessThan(now()) ? 'interest' : 'repayment',
      'description' => Carbon::parse($invoice->due_date)->greaterThanOrEqualTo(now())
        ? ($invoice->program->programType->name == Program::VENDOR_FINANCING
          ? CbsTransaction::BANK_INVOICE_PAYMENT
          : CbsTransaction::REPAYMENT)
        : CbsTransaction::OVERDUE_ACCOUNT,
    ]);

    if ($transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful') {
      $payment_request->update(['status' => 'paid', 'approval_status' => 'paid']);
    }

    return $payment_request;
  }

  protected function createPaymentRequestForInvoice($invoice, $transaction, $credit_date)
  {
    $payment_request = $this->createPaymentRequest($invoice, $transaction, $credit_date, $transaction['amount_ksh']);
    $cbs_transaction = $this->createCbsTransaction($payment_request, $transaction);

    if (
      $transaction['status_createdsuccessfulfailedpermanently_failed'] == 'Successful' &&
      round($transaction['amount_ksh']) <= round($payment_request->invoice->balance)
    ) {
      $this->processSuccessfulRepayment($payment_request, $transaction, $cbs_transaction, $credit_date);
    } elseif (
      in_array($transaction['status_createdsuccessfulfailedpermanently_failed'], ['Failed', 'Permanently Failed'])
    ) {
      $this->addNotification(
        'CbsTransactionForLoanSettlementFailed',
        [
          'cbs_transaction_id' => $cbs_transaction->id,
          'users' => $payment_request->invoice->program->bank->users->pluck('email')->toArray(),
        ],
        "cbs_transaction_{$cbs_transaction->id}_failed"
      );
    }
  }

  protected function addNotification($type, $data, $idempotencyKey = null)
  {
    $idempotencyKey =
      $idempotencyKey ?? $type . '_' . ($data['invoice_id'] ?? ($data['cbs_transaction_id'] ?? uniqid()));
    $this->notifications[] = [
      'type' => $type,
      'data' => $data,
      'idempotency_key' => $idempotencyKey,
    ];
  }

  protected function dispatchNotifications()
  {
    $processedKeys = [];
    foreach ($this->notifications as $notification) {
      $key = $notification['idempotency_key'];
      if (in_array($key, $processedKeys)) {
        Log::warning('Duplicate notification prevented', [
          'type' => $notification['type'],
          'idempotency_key' => $key,
        ]);
        continue;
      }

      foreach ($notification['data']['users'] as $email) {
        if (!in_array($email, $this->notifiedUsers)) {
          Log::info('Dispatching notification', [
            'type' => $notification['type'],
            'email' => $email,
            'idempotency_key' => $key,
            'data' => $notification['data'],
          ]);
          SendMail::dispatch($email, $notification['type'], $notification['data'])->afterCommit();
          $this->notifiedUsers[] = $email;
        }
      }
      $processedKeys[] = $key;
    }
  }

  protected function logActivity($cbs_transaction, $transaction)
  {
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
}
