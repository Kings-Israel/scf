<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Http\Resources\InvoiceResource;
use App\Models\BankGeneralProductConfiguration;
use App\Models\BankProductRepaymentPriority;
use App\Models\BankProductsConfiguration;
use App\Models\CbsTransaction;
use App\Models\CronLog;
use App\Models\Invoice;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CheckPastDueInvoices implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $invoices = DB::table('invoices')
      ->where('financing_status', 'disbursed')
      ->where('due_date', '<', now()->format('Y-m-d'))
      ->get();

    try {
      DB::beginTransaction();

      foreach ($invoices as $invoice_details) {
        $invoice = Invoice::find($invoice_details->id);

        // Cron Log
        $cron_log = CronLog::create([
          'bank_id' => $invoice->program->bank_id,
          'name' =>
            $invoice->program->programType->name == Program::VENDOR_FINANCING
              ? 'IF Discount Charge Accrual'
              : 'DF Discount Charge Accrual',
          'start_time' => now(),
          'status' => 'in progress',
        ]);

        // Get non collection instance of invoice and update
        $invoice_details = Invoice::find($invoice->id);
        $invoice_details->update([
          'stage' => 'past_due',
        ]);

        if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
          $credit_account_name = $invoice->program->anchor->name;
        } else {
          if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
            $credit_account_name = $invoice->program->anchor->name;
          } else {
            $credit_account_name = $invoice->buyer->name;
          }
        }

        $prefix = '';

        $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
        $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
        $factoring_with_recourse = ProgramCode::where('name', Program::FACTORING_WITH_RECOURSE)->first();
        $factoring_without_recourse = ProgramCode::where('name', Program::FACTORING_WITHOUT_RECOURSE)->first();
        $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

        $penal_discount_income_bank_account = null;
        $fees_income_bank_account = null;

        // Penal Rate
        $penal_rate = $invoice->penal_rate;
        // Grace Period Days
        $grace_period = $invoice->grace_period;
        // Grace Period Discount
        $grace_period_discount = $invoice->grace_period_discount;

        if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
          if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
              ->where('company_id', $invoice->company_id)
              ->first()->payment_account_number;

            // Get Bank Configured Receivable Accounts
            $prefix = 'VFR' . $invoice->program->bank_id;
            $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
              ->where('section', 'Vendor Finance Receivable')
              ->where('product_code_id', $vendor_financing_receivable->id)
              ->where('product_type_id', $invoice->program->program_type_id)
              ->where('name', 'Fee Income Account')
              ->first();
            $penal_discount_income_bank_account = BankProductsConfiguration::where(
              'bank_id',
              $invoice->program->bank_id
            )
              ->where('section', 'Vendor Finance Receivable')
              ->where('product_code_id', $vendor_financing_receivable->id)
              ->where('product_type_id', $vendor_financing->id)
              ->where('name', 'Penal Discount Income Account')
              ->first();
            $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
              ->where('section', 'Vendor Finance Receivable')
              ->where('product_code_id', $invoice->program->program_code_id)
              ->where('product_type_id', $invoice->program->program_type_id)
              ->where('name', 'Discount Receivable Account')
              ->first();

            $latest_payment_reference_number = PaymentRequest::whereHas('invoice', function ($query) use ($invoice) {
              $query->whereHas('program', function ($query) use ($invoice) {
                $query
                  ->where('bank_id', $invoice->program->bank_id)
                  ->whereHas('programType', function ($query) {
                    $query->where('name', Program::VENDOR_FINANCING);
                  })
                  ->whereHas('programCode', function ($query) {
                    $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                  });
              });
            })->max('id');

            $reference_number =
              'VFR0' .
              $invoice->program->bank_id .
              '' .
              now()->format('y') .
              '000' .
              Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                Program::VENDOR_FINANCING_RECEIVABLE,
              ]);
          } else {
            $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
              ->where('company_id', $invoice->company_id)
              ->where('buyer_id', $invoice->buyer_id)
              ->first()->payment_account_number;

            if ($invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
              // Factoring without recourse
              $prefix = 'FWR' . $invoice->program->bank_id;
              $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                ->where('section', 'Factoring Without Recourse')
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('name', 'Fee Income Account')
                ->first();
              $penal_discount_income_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $invoice->program->bank_id
              )
                ->where('section', 'Factoring Without Recourse')
                ->where('product_type_id', $vendor_financing->id)
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('name', 'Penal Discount Income Account')
                ->first();
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $invoice->program->bank_id
              )
                ->where('section', 'Factoring Without Recourse')
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('name', 'Discount Receivable Account')
                ->first();

              $reference_number =
                'FWR0' .
                $invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                  Program::FACTORING_WITHOUT_RECOURSE,
                  Program::FACTORING_WITHOUT_RECOURSE,
                ]);
            } else {
              // Factoring with recourse
              $prefix = 'FR' . $invoice->program->bank_id;
              $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                ->where('section', 'Factoring With Recourse')
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('name', 'Fee Income Account')
                ->first();
              $penal_discount_income_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $invoice->program->bank_id
              )
                ->where('section', 'Factoring With Recourse')
                ->where('product_type_id', $vendor_financing->id)
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('name', 'Penal Discount Income Account')
                ->first();
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $invoice->program->bank_id
              )
                ->where('section', 'Factoring With Recourse')
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('name', 'Discount Receivable Account')
                ->first();

              $reference_number =
                'FR0' .
                $invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                  Program::FACTORING_WITH_RECOURSE,
                  Program::FACTORING_WITH_RECOURSE,
                ]);
            }
          }
        } else {
          $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
            ->where('company_id', $invoice->company_id)
            ->first()->payment_account_number;

          // $prefix = 'DF' . $invoice->program->bank_id;
          $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
            ->where('product_type_id', $invoice->program->programType->id)
            ->where('product_code_id', null)
            ->where('name', 'Fee Income Account')
            ->first();
          $penal_discount_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
            ->where('product_type_id', $dealer_financing->id)
            ->where('product_code_id', null)
            ->where('name', 'Penal Discount Income Account')
            ->first();
          $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
            ->where('product_code_id', null)
            ->where('product_type_id', $invoice->program->program_type_id)
            ->where('name', 'Discount Receivable from Overdraft')
            ->first();

          $reference_number =
            'DF0' .
            $invoice->program->bank_id .
            '' .
            now()->format('y') .
            '000' .
            Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::DEALER_FINANCING);
        }

        // Get the anchor/buyer
        if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
          $anchor_name = $invoice->program->anchor->name;
        } else {
          if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            $anchor_name = $invoice->program->anchor->name;
          } else {
            $buyer_details = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
              ->where('buyer_id', $invoice->buyer_id)
              ->first();
            $anchor_name = $buyer_details->buyer->name;
          }
        }

        // Check if program is with recourse ot without recourse
        $recourse = $invoice->recourse;
        $discount_type = $invoice->discount_charge_type;
        $fee_type = $invoice->fee_charge_type;

        // Days before penal
        $days_before_penal = $invoice->days_before_penal;

        $difference_in_days = 0;

        $penal_income_account = $penal_discount_income_bank_account
          ? $penal_discount_income_bank_account->value
          : ($fees_income_bank_account
            ? $fees_income_bank_account->value
            : 'Penal_Inc_Acc');
        $penal_income_account_name = $penal_discount_income_bank_account
          ? $penal_discount_income_bank_account->name
          : ($fees_income_bank_account
            ? $fees_income_bank_account->name
            : 'Penal Account');

        if ($recourse == Invoice::WITHOUT_RECOURSE) {
          // Without Recourse
          // Get Anchor's/Buyer's/Dealer's Bank Account
          if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
            // Dealer
            $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
              ->where('company_id', $invoice->company_id)
              ->first();
          } else {
            if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
              // Anchor
              $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
            } else {
              // Buyer
              $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
                ->where('company_id', $invoice->company_id)
                ->where('buyer_id', $invoice->buyer_id)
                ->first();
            }
          }

          if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
            if ($discount_type == Invoice::FRONT_ENDED) {
              $amount_repayable =
                ($invoice->eligibility / 100) * $invoice->invoice_total_amount - $invoice->paid_amount;
            } else {
              $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
            }
          } else {
            // User drawdown amount for vendor financing
            if ($discount_type == Invoice::FRONT_ENDED) {
              $amount_repayable = ($invoice->eligibility / 100) * $invoice->drawdown_amount - $invoice->paid_amount;
            } else {
              $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
            }
          }

          // Check if the repayment transaction was not created or marked as permanently failed
          $successful_repayment_cbs_transaction = CbsTransaction::whereIn('transaction_type', [
            CbsTransaction::BANK_INVOICE_PAYMENT,
            CbsTransaction::REPAYMENT,
          ])
            ->whereIn('status', ['Created', 'Successful', 'Failed'])
            ->whereHas('paymentRequest', function ($query) use ($invoice) {
              $query->where('invoice_id', $invoice->id);
            })
            ->get();

          if ($successful_repayment_cbs_transaction->count() <= 0) {
            // All Repayment Transaction were marked as permanently failed, so recreate the repayment transaction
            if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
              if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                $repayment_reference_number =
                  'VFR0' .
                  $invoice->program->bank_id .
                  '' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                    Program::VENDOR_FINANCING_RECEIVABLE,
                  ]);
              } else {
                $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
                  ->where('company_id', $invoice->company_id)
                  ->where('buyer_id', $invoice->buyer_id)
                  ->first()->payment_account_number;

                if ($invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                  // Factoring without recourse
                  $repayment_reference_number =
                    'FWR0' .
                    $invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                      Program::FACTORING_WITHOUT_RECOURSE,
                      Program::FACTORING_WITHOUT_RECOURSE,
                    ]);
                } else {
                  // Factoring with recourse
                  $repayment_reference_number =
                    'FR0' .
                    $invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                      Program::FACTORING_WITH_RECOURSE,
                      Program::FACTORING_WITH_RECOURSE,
                    ]);
                }
              }
            } else {
              $repayment_reference_number =
                'DF0' .
                $invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::DEALER_FINANCING);
            }

            $repayment_payment_request = PaymentRequest::create([
              'reference_number' => $repayment_reference_number,
              'invoice_id' => $invoice->id,
              'amount' => $amount_repayable,
              'payment_request_date' => $invoice->due_date,
              'status' => 'approved',
              'approval_status' => 'approved',
            ]);

            // Principle repayments
            $repayment_payment_request->paymentAccounts()->create([
              'account' => $od_account,
              'account_name' => $credit_account_name,
              'amount' => $amount_repayable,
              'type' => 'principle_repayment',
              'description' =>
                $invoice->program->programType->name == Program::VENDOR_FINANCING
                  ? CbsTransaction::BANK_INVOICE_PAYMENT
                  : CbsTransaction::REPAYMENT,
            ]);

            // Principle Repayment Transaction
            CbsTransaction::create([
              'bank_id' => $repayment_payment_request->invoice->program->bank->id,
              'payment_request_id' => $repayment_payment_request->id,
              'debit_from_account' => $bank_account->account_number,
              'debit_from_account_name' => $bank_account->name_as_per_bank,
              'debit_from_account_description' => $bank_account->company
                ? $bank_account->company->name
                : $bank_account->program->anchor->name .
                  ' (' .
                  $bank_account->bank_name .
                  ': ' .
                  $bank_account->account_number .
                  ')',
              'credit_to_account' => $od_account,
              'credit_to_account_name' => $credit_account_name,
              'credit_to_account_description' =>
                $invoice->program->programType->name == Program::VENDOR_FINANCING
                  ? CbsTransaction::BANK_INVOICE_PAYMENT . ' (Bank: ' . $od_account . ')'
                  : CbsTransaction::REPAYMENT . ' (Bank: ' . $od_account . ')',
              'amount' => $amount_repayable,
              'transaction_created_date' => $invoice->due_date,
              'pay_date' => $invoice->due_date,
              'status' => 'Created',
              'transaction_type' =>
                $invoice->program->programType->name == Program::VENDOR_FINANCING
                  ? CbsTransaction::BANK_INVOICE_PAYMENT
                  : CbsTransaction::REPAYMENT,
              'product' =>
                $invoice->program->programType->name == Program::VENDOR_FINANCING
                  ? Program::VENDOR_FINANCING
                  : Program::DEALER_FINANCING,
            ]);
          }

          // Check if days before penal is set, greater than 0 and if invoice's past due date is greater than days before penal
          $difference_in_days = Carbon::now()->diffInDays($invoice->due_date);
          if ($days_before_penal && $days_before_penal->value > 0 && $difference_in_days > $days_before_penal->value) {
            // Check if grace period is set, greater than 0 and if the invoice's past due days fall within the grace period
            // If past due days are less than the grace period, create a penal transaction using the grace period penal rate
            if ($grace_period && $grace_period > 0 && $difference_in_days <= $grace_period) {
              // Create penal transaction using grace period penal rate
              $penal_rate = $grace_period_discount;
              // If penal rate is set, create payment request and cbs transaction for penal transaction
              if ($penal_rate && $penal_rate > 0) {
                $principle_amount = $amount_repayable;
                // Calculate penal amount from compounded penal rate
                for ($i = 0; $i < $difference_in_days; $i++) {
                  $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                  $principle_amount += $penal_amount;
                }

                // Create penal payment request
                $payment_request = PaymentRequest::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $invoice->id,
                  'amount' => $penal_amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                // Create penal payment request account
                $payment_request->paymentAccounts()->create([
                  'account' => $penal_income_account,
                  'account_name' => $penal_income_account_name,
                  'amount' => $penal_amount,
                  'type' => 'interest',
                  'description' => 'Overdue Charges/Fees',
                ]);

                // Create penal cbs transaction
                CbsTransaction::create([
                  'bank_id' => $payment_request->invoice->program->bank->id,
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account' => $bank_account->account_number,
                  'debit_from_account_name' => $bank_account->name_as_per_bank,
                  'debit_from_account_description' =>
                    $anchor_name . ' (' . $bank_account->bank_name . ': ' . $bank_account->account_number . ')',
                  'credit_to_account' => $penal_income_account,
                  'credit_to_account_name' => $penal_income_account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                  'amount' => $penal_amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                  'product' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? Program::VENDOR_FINANCING
                      : Program::DEALER_FINANCING,
                ]);
              }
            } else {
              // No grace period, create penal transaction using penal rate
              $penal_rate = $penal_rate;
              // If penal rate is set, create payment request and cbs transaction for penal transaction
              if ($penal_rate && $penal_rate > 0) {
                $principle_amount = $amount_repayable;
                // Calculate penal amount from compounded penal rate
                for ($i = 0; $i < $difference_in_days; $i++) {
                  $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                  $principle_amount += $penal_amount;
                }

                // Create penal payment request
                $payment_request = PaymentRequest::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $invoice->id,
                  'amount' => $penal_amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                // Create penal payment request account
                $payment_request->paymentAccounts()->create([
                  'account' => $penal_income_account,
                  'account_name' => $penal_income_account_name,
                  'amount' => $penal_amount,
                  'type' => 'interest',
                  'description' => 'Overdue Charges/Fees',
                ]);

                // Create penal cbs transaction
                CbsTransaction::create([
                  'bank_id' => $payment_request->invoice->program->bank->id,
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account' => $bank_account->account_number,
                  'debit_from_account_name' => $bank_account->name_as_per_bank,
                  'debit_from_account_description' =>
                    $anchor_name . ' (' . $bank_account->bank_name . ': ' . $bank_account->account_number . ')',
                  'credit_to_account' => $penal_income_account,
                  'credit_to_account_name' => $penal_income_account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                  'amount' => $penal_amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                  'product' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? Program::VENDOR_FINANCING
                      : Program::DEALER_FINANCING,
                ]);
              }
            }
          } else {
            // Days before penal is not set or less than 0
            if ($grace_period && $grace_period > 0 && $difference_in_days <= $grace_period) {
              // Create penal transaction using grace period penal rate
              $penal_rate = $grace_period_discount;
              // If penal rate is set, create payment request and cbs transaction for penal transaction
              if ($penal_rate && $penal_rate > 0) {
                $principle_amount = $amount_repayable;
                // Calculate penal amount from compounded penal rate
                for ($i = 0; $i < $difference_in_days; $i++) {
                  $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                  $principle_amount += $penal_amount;
                }

                // Create penal payment request
                $payment_request = PaymentRequest::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $invoice->id,
                  'amount' => $penal_amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                // Create penal payment request account
                $payment_request->paymentAccounts()->create([
                  'account' => $penal_income_account,
                  'account_name' => $penal_income_account_name,
                  'amount' => $penal_amount,
                  'type' => 'interest',
                  'description' => 'Overdue Charges/Fees',
                ]);

                // Create penal cbs transaction
                CbsTransaction::create([
                  'bank_id' => $payment_request->invoice->program->bank->id,
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account' => $bank_account->account_number,
                  'debit_from_account_name' => $bank_account->name_as_per_bank,
                  'debit_from_account_description' =>
                    $anchor_name . ' (' . $bank_account->bank_name . ': ' . $bank_account->account_number . ')',
                  'credit_to_account' => $penal_income_account,
                  'credit_to_account_name' => $penal_income_account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                  'amount' => $penal_amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                  'product' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? Program::VENDOR_FINANCING
                      : Program::DEALER_FINANCING,
                ]);
              }
            } else {
              // No grace period, create penal transaction using penal rate
              $penal_rate = $penal_rate;
              // If penal rate is set, create payment request and cbs transaction for penal transaction
              if ($penal_rate && $penal_rate > 0) {
                $principle_amount = $amount_repayable;
                // Calculate penal amount from compounded penal rate
                for ($i = 0; $i < $difference_in_days; $i++) {
                  $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                  $principle_amount += $penal_amount;
                }

                // Create penal payment request
                $payment_request = PaymentRequest::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $invoice->id,
                  'amount' => $penal_amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                // Create penal payment request account
                $payment_request->paymentAccounts()->create([
                  'account' => $penal_income_account,
                  'account_name' => $penal_income_account_name,
                  'amount' => $penal_amount,
                  'type' => 'interest',
                  'description' => 'Overdue Charges/Fees',
                ]);

                // Create penal cbs transaction
                CbsTransaction::create([
                  'bank_id' => $payment_request->invoice->program->bank->id,
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account' => $bank_account->account_number,
                  'debit_from_account_name' => $bank_account->name_as_per_bank,
                  'debit_from_account_description' =>
                    $anchor_name . ' (' . $bank_account->bank_name . ': ' . $bank_account->account_number . ')',
                  'credit_to_account' => $penal_income_account,
                  'credit_to_account_name' => $penal_income_account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                  'amount' => $penal_amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                  'product' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? Program::VENDOR_FINANCING
                      : Program::DEALER_FINANCING,
                ]);
              }
            }
          }
        } else {
          // With Recourse
          if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
            if ($discount_type == Invoice::FRONT_ENDED) {
              $amount_repayable =
                ($invoice->eligibility / 100) * $invoice->invoice_total_amount - $invoice->paid_amount;
            } else {
              $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
            }
          } else {
            // User drawdown amount for dealer financing
            if ($discount_type == Invoice::FRONT_ENDED) {
              $amount_repayable = ($invoice->eligibility / 100) * $invoice->drawdown_amount - $invoice->paid_amount;
            } else {
              $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
            }
          }

          $difference_in_days = Carbon::now()->diffInDays($invoice->due_date);

          // If within the grace period, the penal is still paid by the anchor/buyer
          // If difference in days still within the grace period, create penal transaction using grace period penal rate
          if ($grace_period && $grace_period > 0 && $difference_in_days <= $grace_period) {
            // Get Anchor's/Buyer's/Dealer's Bank Account
            if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
              // Dealer
              $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
                ->where('company_id', $invoice->company_id)
                ->first();
            } else {
              if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                // Anchor
                $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
              } else {
                // Buyer
                $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
                  ->where('company_id', $invoice->company_id)
                  ->where('buyer_id', $invoice->buyer_id)
                  ->first();
              }
            }
            // Create penal transaction using grace period penal rate
            $penal_rate = $grace_period_discount;
            // If penal rate is set, create payment request and cbs transaction for penal transaction
            if ($penal_rate && $penal_rate > 0) {
              $principle_amount = $amount_repayable;
              // Calculate penal amount from compounded penal rate
              for ($i = 0; $i < $difference_in_days; $i++) {
                $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                $principle_amount += $penal_amount;
              }

              // Create penal payment request
              $payment_request = PaymentRequest::create([
                'reference_number' => $reference_number,
                'invoice_id' => $invoice->id,
                'amount' => $penal_amount,
                'payment_request_date' => now()->format('Y-m-d'),
                'status' => 'approved',
                'approval_status' => 'approved',
              ]);

              // Create penal payment request account
              $payment_request->paymentAccounts()->create([
                'account' => $penal_income_account,
                'account_name' => $penal_income_account_name,
                'amount' => $penal_amount,
                'type' => 'interest',
                'description' => 'Overdue Charges/Fees',
              ]);

              // Create penal cbs transaction
              CbsTransaction::create([
                'bank_id' => $payment_request->invoice->program->bank->id,
                'payment_request_id' => $payment_request->id,
                'debit_from_account' => $bank_account->account_number,
                'debit_from_account_name' => $bank_account->name_as_per_bank,
                'debit_from_account_description' =>
                  $anchor_name . ' (' . $bank_account->bank_name . ': ' . $bank_account->account_number . ')',
                'credit_to_account' => $penal_income_account,
                'credit_to_account_name' => $penal_income_account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                'amount' => $penal_amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                'product' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? Program::VENDOR_FINANCING
                    : Program::DEALER_FINANCING,
              ]);
            }
          } else {
            // Recourse falls to the anchor/vendor
            // Mark all previously created repayment and penal transactions as failed
            $repayment_transaction = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
              $query->where('invoice_id', $invoice->id);
            })
              ->where('status', '!=', 'Successful')
              ->whereIn('transaction_type', [
                CbsTransaction::BANK_INVOICE_PAYMENT,
                CbsTransaction::REPAYMENT,
                CbsTransaction::OVERDUE_ACCOUNT,
              ])
              ->get();

            foreach ($repayment_transaction as $transaction) {
              $transaction->update([
                'status' => 'Permanently Failed',
              ]);

              $transaction->paymentRequest->update([
                'status' => 'failed',
              ]);
            }

            // Get Anchor's/Vendor's Bank Account
            if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
              // Anchor
              $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
            } else {
              if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                // Vendor
                $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
                  ->where('company_id', $invoice->company_id)
                  ->first();
              } else {
                // Anchor
                $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
              }
            }

            // Re-create repayment transaction for the vendor to pay
            if ($discount_type == Invoice::FRONT_ENDED) {
              // Discount Front Ended Start
              $payment_request = PaymentRequest::create([
                'reference_number' => $reference_number,
                'invoice_id' => $invoice->id,
                'amount' => $amount_repayable,
                'payment_request_date' => now()->format('Y-m-d'),
                'status' => 'approved',
                'approval_status' => 'approved',
              ]);

              // Principle repayments
              $payment_request->paymentAccounts()->create([
                'account' => $od_account,
                'account_name' => $credit_account_name,
                'amount' => $amount_repayable,
                'type' => 'principle_repayment',
                'description' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? CbsTransaction::BANK_INVOICE_PAYMENT
                    : CbsTransaction::REPAYMENT,
              ]);

              CbsTransaction::create([
                'bank_id' => $payment_request->invoice->program->bank->id,
                'payment_request_id' => $payment_request->id,
                'debit_from_account' => $bank_account->account_number,
                'debit_from_account_name' => $bank_account->name_as_per_bank,
                'debit_from_account_description' =>
                  $invoice->company->name .
                  ' (' .
                  $bank_account->bank_name .
                  ': ' .
                  $bank_account->account_number .
                  ')',
                'credit_to_account' => $od_account,
                'credit_to_account_name' => $credit_account_name,
                'credit_to_account_description' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? CbsTransaction::BANK_INVOICE_PAYMENT . ' (Bank: ' . $od_account . ')'
                    : CbsTransaction::REPAYMENT . ' (Bank: ' . $od_account . ')',
                'amount' => $amount_repayable,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? CbsTransaction::BANK_INVOICE_PAYMENT
                    : CbsTransaction::REPAYMENT,
                'product' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? Program::VENDOR_FINANCING
                    : Program::DEALER_FINANCING,
              ]);
              // End Discount Front Ended
              // If Fee type is rear ended, create a fee transaction
              if ($fee_type == Invoice::REAR_ENDED) {
                // Mark all previously created fee transactions as failed
                $fee_transaction = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
                  $query->where('invoice_id', $invoice->id);
                })
                  ->where('status', '!=', 'Successful')
                  ->where('transaction_type', 'Fees/Charges')
                  ->get();

                foreach ($fee_transaction as $transaction) {
                  $transaction->update([
                    'status' => 'Permanently Failed',
                  ]);

                  $transaction->paymentRequest->update([
                    'status' => 'failed',
                  ]);

                  // Recreate new fee Payment Request and CBS transaction with the same details
                  $payment_request = PaymentRequest::create([
                    'reference_number' => $reference_number,
                    'invoice_id' => $invoice->id,
                    'amount' => $transaction->amount,
                    'payment_request_date' => now()->format('Y-m-d'),
                    'status' => 'approved',
                    'approval_status' => 'approved',
                  ]);

                  // Create fee payment request account
                  $payment_request->paymentAccounts()->create([
                    'account' => $transaction->account,
                    'account_name' => $transaction->account_name,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                  ]);

                  // Create fee cbs transaction
                  CbsTransaction::create([
                    'bank_id' => $transaction->bank_id,
                    'payment_request_id' => $payment_request->id,
                    'debit_from_account' => $bank_account->account_number,
                    'debit_from_account_name' => $bank_account->name_as_per_bank,
                    'debit_from_account_description' =>
                      $invoice->company->name .
                      ' (' .
                      $bank_account->bank_name .
                      ': ' .
                      $bank_account->account_number .
                      ')',
                    'credit_to_account' => $transaction->account,
                    'credit_to_account_name' => $transaction->account_name,
                    'credit_to_account_description' => 'Charges (Bank: ' . $transaction->account . ')',
                    'amount' => $transaction->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::FEES_CHARGES,
                    'product' =>
                      $invoice->program->programType->name == Program::VENDOR_FINANCING
                        ? Program::VENDOR_FINANCING
                        : Program::DEALER_FINANCING,
                  ]);
                }
              }
            } else {
              // Discount Rear Ended Start
              $repayment_priorities = BankProductRepaymentPriority::where('bank_id', $invoice->program->bank_id)
                ->where('product_type_id', $invoice->program->program_type_id)
                ->orderBy('premature_priority', 'DESC')
                ->get();

              $payment_request = PaymentRequest::create([
                'reference_number' => $prefix . $od_account . '000' . $invoice->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount_repayable,
                'payment_request_date' => now()->format('Y-m-d'),
                'status' => 'approved',
                'approval_status' => 'approved',
              ]);

              foreach ($repayment_priorities as $repayment_priority) {
                switch ($repayment_priority->particulars) {
                  case 'Principal':
                    // Principle repayments
                    $payment_request->paymentAccounts()->create([
                      'account' => $od_account,
                      'account_name' => $credit_account_name,
                      'amount' => $amount_repayable,
                      'type' => 'principle_repayment',
                      'description' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? CbsTransaction::BANK_INVOICE_PAYMENT
                          : CbsTransaction::REPAYMENT,
                    ]);

                    CbsTransaction::create([
                      'bank_id' => $payment_request->invoice->program->bank->id,
                      'payment_request_id' => $payment_request->id,
                      'debit_from_account' => $bank_account->account_number,
                      'debit_from_account_name' => $bank_account->name_as_per_bank,
                      'debit_from_account_description' =>
                        $invoice->company->name .
                        ' (' .
                        $bank_account->bank_name .
                        ': ' .
                        $bank_account->account_number .
                        ')',
                      'credit_to_account' => $od_account,
                      'credit_to_account_name' => $credit_account_name,
                      'credit_to_account_description' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? CbsTransaction::BANK_INVOICE_PAYMENT . ' (Bank: ' . $od_account . ')'
                          : CbsTransaction::REPAYMENT . ' (Bank: ' . $od_account . ')',
                      'amount' => $amount_repayable,
                      'transaction_created_date' => now()->format('Y-m-d'),
                      'pay_date' => now()->format('Y-m-d'),
                      'status' => 'Created',
                      'transaction_type' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? CbsTransaction::BANK_INVOICE_PAYMENT
                          : CbsTransaction::REPAYMENT,
                      'product' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? Program::VENDOR_FINANCING
                          : Program::DEALER_FINANCING,
                    ]);
                    break;
                  case 'Fees and Charges':
                    // In case Fee Charging is Rear Ended
                    if ($fee_type == Invoice::REAR_ENDED) {
                      // Mark all previously created fee transactions as failed
                      $fee_transaction = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
                        $query->where('invoice_id', $invoice->id)->whereHas('paymentAccounts', function ($query) {
                          $query->whereIn('type', ['program_fees', 'program_fees_taxes', 'tax_on_fees']);
                        });
                      })
                        ->where('status', '!=', 'Successful')
                        ->where('transaction_type', 'Fees/Charges')
                        ->get();

                      foreach ($fee_transaction as $transaction) {
                        $transaction->update([
                          'status' => 'Permanently Failed',
                        ]);

                        $transaction->paymentRequest->update([
                          'status' => 'failed',
                        ]);

                        // Recreate new Fee Payment Request and CBS transaction with the same details
                        $payment_request = PaymentRequest::create([
                          'reference_number' => $prefix . $transaction->account . '000' . $invoice->id,
                          'invoice_id' => $invoice->id,
                          'amount' => $transaction->amount,
                          'payment_request_date' => now()->format('Y-m-d'),
                          'status' => 'approved',
                          'approval_status' => 'approved',
                        ]);
                        // Create fee payment request account
                        $payment_request->paymentAccounts()->create([
                          'account' => $transaction->account,
                          'account_name' => $transaction->account_name,
                          'amount' => $transaction->amount,
                          'type' => $transaction->type,
                          'description' => $transaction->description,
                        ]);
                        // Create fee cbs transaction
                        CbsTransaction::create([
                          'bank_id' => $transaction->bank_id,
                          'payment_request_id' => $payment_request->id,
                          'debit_from_account' => $bank_account->account_number,
                          'debit_from_account_name' => $bank_account->name_as_per_bank,
                          'debit_from_account_description' =>
                            $invoice->company->name .
                            ' (' .
                            $bank_account->bank_name .
                            ': ' .
                            $bank_account->account_number .
                            ')',
                          'credit_to_account' => $transaction->account,
                          'credit_to_account_name' => $transaction->account_name,
                          'credit_to_account_description' => 'Charges (Bank: ' . $transaction->account . ')',
                          'amount' => $transaction->amount,
                          'transaction_created_date' => now()->format('Y-m-d'),
                          'status' => 'Created',
                          'transaction_type' => CbsTransaction::FEES_CHARGES,
                          'product' =>
                            $invoice->program->programType->name == Program::VENDOR_FINANCING
                              ? Program::VENDOR_FINANCING
                              : Program::DEALER_FINANCING,
                        ]);
                      }

                      $tax_on_discount_transaction = CbsTransaction::whereHas('paymentRequest', function ($query) use (
                        $invoice
                      ) {
                        $query->where('invoice_id', $invoice->id)->whereHas('paymentAccounts', function ($query) {
                          $query->whereIn('type', ['tax_on_discount']);
                        });
                      })
                        ->where('status', '!=', 'Successful')
                        ->where('transaction_type', CbsTransaction::FEES_CHARGES)
                        ->get();

                      foreach ($tax_on_discount_transaction as $transaction) {
                        $transaction->update([
                          'status' => 'Permanently Failed',
                        ]);

                        $transaction->paymentRequest->update([
                          'status' => 'failed',
                        ]);

                        // Recreate new Fee Payment Request and CBS transaction with the same details
                        $payment_request = PaymentRequest::create([
                          'reference_number' => $prefix . $transaction->account . '000' . $invoice->id,
                          'invoice_id' => $invoice->id,
                          'amount' => $transaction->amount,
                          'payment_request_date' => now()->format('Y-m-d'),
                          'status' => 'approved',
                          'approval_status' => 'approved',
                        ]);
                        // Create fee payment request account
                        $payment_request->paymentAccounts()->create([
                          'account' => $transaction->account,
                          'account_name' => $transaction->account_name,
                          'amount' => $transaction->amount,
                          'type' => $transaction->type,
                          'description' => $transaction->description,
                        ]);
                        // Create fee cbs transaction
                        CbsTransaction::create([
                          'bank_id' => $payment_request->invoice->program->bank_id,
                          'payment_request_id' => $payment_request->id,
                          'debit_from_account' => $bank_account->account_number,
                          'debit_from_account_name' => $bank_account->name_as_per_bank,
                          'debit_from_account_description' =>
                            $invoice->company->name .
                            ' (' .
                            $bank_account->bank_name .
                            ': ' .
                            $bank_account->account_number .
                            ')',
                          'credit_to_account' => $transaction->account,
                          'credit_to_account_name' => $transaction->account_name,
                          'credit_to_account_description' => 'Charges (Bank: ' . $transaction->account . ')',
                          'amount' => $transaction->amount,
                          'transaction_created_date' => now()->format('Y-m-d'),
                          'pay_date' => now()->format('Y-m-d'),
                          'status' => 'Created',
                          'transaction_type' => CbsTransaction::FEES_CHARGES,
                          'product' =>
                            $invoice->program->programType->name == Program::VENDOR_FINANCING
                              ? Program::VENDOR_FINANCING
                              : Program::DEALER_FINANCING,
                        ]);
                      }
                    }
                    break;
                  case 'Interest':
                    // Mark all previously created discount transactions as failed
                    $discount_transaction = CbsTransaction::whereHas('paymentRequest', function ($query) use (
                      $invoice
                    ) {
                      $query->where('invoice_id', $invoice->id)->whereHas('paymentAccounts', function ($query) {
                        $query->whereIn('type', ['discount']);
                      });
                    })
                      ->where('status', '!=', 'Successful')
                      ->where('transaction_type', CbsTransaction::ACCRUAL_POSTED_INTEREST)
                      ->get();

                    foreach ($discount_transaction as $transaction) {
                      $transaction->update([
                        'status' => 'Permanently Failed',
                      ]);

                      $transaction->paymentRequest->update([
                        'status' => 'failed',
                      ]);

                      // Recreate new discount Payment Request and CBS transaction with the same details
                      $payment_request = PaymentRequest::create([
                        'reference_number' => $prefix . $transaction->account . '000' . $invoice->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $transaction->amount,
                        'payment_request_date' => now()->format('Y-m-d'),
                        'status' => 'approved',
                        'approval_status' => 'approved',
                      ]);
                      // Create discount payment request account
                      $payment_request->paymentAccounts()->create([
                        'account' => $transaction->account,
                        'account_name' => $transaction->account_name,
                        'amount' => $transaction->amount,
                        'type' => $transaction->type,
                        'description' => $transaction->description,
                      ]);
                      // Create discount cbs transaction
                      CbsTransaction::create([
                        'bank_id' => $payment_request->invoice->program->bank_id,
                        'payment_request_id' => $payment_request->id,
                        'debit_from_account' => $bank_account->account_number,
                        'debit_from_account_name' => $bank_account->name_as_per_bank,
                        'debit_from_account_description' =>
                          $invoice->company->name .
                          ' (' .
                          $bank_account->bank_name .
                          ': ' .
                          $bank_account->account_number .
                          ')',
                        'credit_to_account' => $transaction->account,
                        'credit_to_account_name' => $transaction->account_name,
                        'credit_to_account_description' =>
                          CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $transaction->account . ')',
                        'amount' => $transaction->amount,
                        'transaction_created_date' => now()->format('Y-m-d'),
                        'pay_date' => now()->format('Y-m-d'),
                        'status' => 'Created',
                        'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                      ]);
                    }
                    break;
                  case 'Other Debt Entries':
                    break;
                  case 'Penal Interest':
                    // Get Anchor's/Vendor's Bank Account
                    if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                      // Anchor
                      $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
                    } else {
                      if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                        // Vendor
                        $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
                          ->where('company_id', $invoice->company_id)
                          ->first();
                      } else {
                        // Anchor
                        $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
                      }
                    }

                    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
                      if ($discount_type == Invoice::FRONT_ENDED) {
                        $amount_repayable =
                          ($invoice->eligibility / 100) * $invoice->invoice_total_amount - $invoice->paid_amount;
                      } else {
                        $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
                      }
                    } else {
                      // User drawdown amount for vendor financing
                      if ($discount_type == Invoice::FRONT_ENDED) {
                        $amount_repayable =
                          ($invoice->eligibility / 100) * $invoice->drawdown_amount - $invoice->paid_amount;
                      } else {
                        $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
                      }
                    }

                    // Check if days before penal is set, greater than 0 and if invoice's past due date is greater than days before penal
                    $difference_in_days = Carbon::now()->diffInDays($invoice->due_date);
                    if (
                      $days_before_penal &&
                      $days_before_penal->value > 0 &&
                      $difference_in_days > $days_before_penal->value
                    ) {
                      // Check if grace period is set, greater than 0 and if the invoice's past due days fall within the grace period
                      // If past due days are less than the grace period, create a penal transaction using the grace period penal rate
                      if ($grace_period && $grace_period > 0 && $difference_in_days <= $grace_period) {
                        // Create penal transaction using grace period penal rate
                        $penal_rate = $grace_period_discount;
                        // If penal rate is set, create payment request and cbs transaction for penal transaction
                        if ($penal_rate && $penal_rate > 0) {
                          $principle_amount = $amount_repayable;
                          // Calculate penal amount from compounded penal rate
                          for ($i = 0; $i < $difference_in_days; $i++) {
                            $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                            $principle_amount += $penal_amount;
                          }

                          // Create penal payment request
                          $payment_request = PaymentRequest::create([
                            'reference_number' => $prefix . $penal_income_account . '000' . $invoice->id,
                            'invoice_id' => $invoice->id,
                            'amount' => $penal_amount,
                            'payment_request_date' => now()->format('Y-m-d'),
                            'status' => 'approved',
                            'approval_status' => 'approved',
                          ]);

                          // Create penal payment request account
                          $payment_request->paymentAccounts()->create([
                            'account' => $penal_income_account,
                            'account_name' => $penal_income_account_name,
                            'amount' => $penal_amount,
                            'type' => 'interest',
                            'description' => 'Overdue Charges/Fees',
                          ]);

                          // Create penal cbs transaction
                          CbsTransaction::create([
                            'bank_id' => $payment_request->invoice->program->bank->id,
                            'payment_request_id' => $payment_request->id,
                            'debit_from_account' => $bank_account->account_number,
                            'debit_from_account_name' => $bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $invoice->company->name .
                              ' (' .
                              $bank_account->bank_name .
                              ': ' .
                              $bank_account->account_number .
                              ')',
                            'credit_to_account' => $penal_income_account,
                            'credit_to_account_name' => $penal_income_account_name,
                            'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                            'amount' => $penal_amount,
                            'transaction_created_date' => now()->format('Y-m-d'),
                            'pay_date' => now()->format('Y-m-d'),
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }
                      } else {
                        // No grace period, create penal transaction using penal rate
                        $penal_rate = $penal_rate;
                        // If penal rate is set, create payment request and cbs transaction for penal transaction
                        if ($penal_rate && $penal_rate > 0) {
                          $principle_amount = $amount_repayable;
                          // Calculate penal amount from compounded penal rate
                          for ($i = 0; $i < $difference_in_days; $i++) {
                            $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                            $principle_amount += $penal_amount;
                          }

                          // Create penal payment request
                          $payment_request = PaymentRequest::create([
                            'reference_number' => $prefix . $penal_income_account . '000' . $invoice->id,
                            'invoice_id' => $invoice->id,
                            'amount' => $penal_amount,
                            'payment_request_date' => now()->format('Y-m-d'),
                            'status' => 'approved',
                            'approval_status' => 'approved',
                          ]);

                          // Create penal payment request account
                          $payment_request->paymentAccounts()->create([
                            'account' => $penal_income_account,
                            'account_name' => $penal_income_account_name,
                            'amount' => $penal_amount,
                            'type' => 'interest',
                            'description' => 'Overdue Charges/Fees',
                          ]);

                          // Create penal cbs transaction
                          CbsTransaction::create([
                            'bank_id' => $payment_request->invoice->program->bank->id,
                            'payment_request_id' => $payment_request->id,
                            'debit_from_account' => $bank_account->account_number,
                            'debit_from_account_name' => $bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $invoice->company->name .
                              ' (' .
                              $bank_account->bank_name .
                              ': ' .
                              $bank_account->account_number .
                              ')',
                            'credit_to_account' => $penal_income_account,
                            'credit_to_account_name' => $penal_income_account_name,
                            'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                            'amount' => $penal_amount,
                            'transaction_created_date' => now()->format('Y-m-d'),
                            'pay_date' => now()->format('Y-m-d'),
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }
                      }
                    } else {
                      // Days before penal is not set or less than 0
                      if ($grace_period && $grace_period > 0 && $difference_in_days <= $grace_period) {
                        // Create penal transaction using grace period penal rate
                        $penal_rate = $grace_period_discount;
                        // If penal rate is set, create payment request and cbs transaction for penal transaction
                        if ($penal_rate && $penal_rate > 0) {
                          $principle_amount = $amount_repayable;
                          // Calculate penal amount from compounded penal rate
                          for ($i = 0; $i < $difference_in_days; $i++) {
                            $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                            $principle_amount += $penal_amount;
                          }

                          // Create penal payment request
                          $payment_request = PaymentRequest::create([
                            'reference_number' => $prefix . $penal_income_account . '000' . $invoice->id,
                            'invoice_id' => $invoice->id,
                            'amount' => $penal_amount,
                            'payment_request_date' => now()->format('Y-m-d'),
                            'status' => 'approved',
                            'approval_status' => 'approved',
                          ]);

                          // Create penal payment request account
                          $payment_request->paymentAccounts()->create([
                            'account' => $penal_income_account,
                            'account_name' => $penal_income_account_name,
                            'amount' => $penal_amount,
                            'type' => 'interest',
                            'description' => 'Overdue Charges/Fees',
                          ]);

                          // Create penal cbs transaction
                          CbsTransaction::create([
                            'bank_id' => $payment_request->invoice->program->bank->id,
                            'payment_request_id' => $payment_request->id,
                            'debit_from_account' => $bank_account->account_number,
                            'debit_from_account_name' => $bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $invoice->company->name .
                              ' (' .
                              $bank_account->bank_name .
                              ': ' .
                              $bank_account->account_number .
                              ')',
                            'credit_to_account' => $penal_income_account,
                            'credit_to_account_name' => $penal_income_account_name,
                            'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                            'amount' => $penal_amount,
                            'transaction_created_date' => now()->format('Y-m-d'),
                            'pay_date' => now()->format('Y-m-d'),
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }
                      } else {
                        // No grace period, create penal transaction using penal rate
                        $penal_rate = $penal_rate;
                        // If penal rate is set, create payment request and cbs transaction for penal transaction
                        if ($penal_rate && $penal_rate > 0) {
                          $principle_amount = $amount_repayable;
                          // Calculate penal amount from compounded penal rate
                          for ($i = 0; $i < $difference_in_days; $i++) {
                            $penal_amount = ($penal_rate / 365 / 100) * $principle_amount;
                            $principle_amount += $penal_amount;
                          }

                          // Create penal payment request
                          $payment_request = PaymentRequest::create([
                            'reference_number' => $prefix . $penal_income_account . '000' . $invoice->id,
                            'invoice_id' => $invoice->id,
                            'amount' => $penal_amount,
                            'payment_request_date' => now()->format('Y-m-d'),
                            'status' => 'approved',
                            'approval_status' => 'approved',
                          ]);

                          // Create penal payment request account
                          $payment_request->paymentAccounts()->create([
                            'account' => $penal_income_account,
                            'account_name' => $penal_income_account_name,
                            'amount' => $penal_amount,
                            'type' => 'interest',
                            'description' => 'Overdue Charges/Fees',
                          ]);

                          // Create penal cbs transaction
                          CbsTransaction::create([
                            'bank_id' => $payment_request->invoice->program->bank->id,
                            'payment_request_id' => $payment_request->id,
                            'debit_from_account' => $bank_account->account_number,
                            'debit_from_account_name' => $bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $invoice->company->name .
                              ' (' .
                              $bank_account->bank_name .
                              ': ' .
                              $bank_account->account_number .
                              ')',
                            'credit_to_account' => $penal_income_account,
                            'credit_to_account_name' => $penal_income_account_name,
                            'credit_to_account_description' => 'Charges (Bank: ' . $penal_income_account . ')',
                            'amount' => $penal_amount,
                            'transaction_created_date' => now()->format('Y-m-d'),
                            'pay_date' => now()->format('Y-m-d'),
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::OVERDUE_ACCOUNT,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }
                      }
                    }
                    break;
                }
              }
            }
          }
        }

        $cron_log->update([
          'end_time' => now(),
          'status' => 'completed',
        ]);
      }

      DB::commit();
    } catch (\Throwable $th) {
      info($th);

      DB::rollback();
    }

    $expired_invoices = DB::table('invoices')
      ->where(function ($query) {
        $query->where('financing_status', 'pending');
      })
      ->where('status', '!=', 'expired')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->get();

    foreach ($expired_invoices as $invoice_details) {
      $invoice = Invoice::find($invoice_details->id);

      $invoice->update([
        'status' => 'expired',
        'stage' => 'expired',
      ]);
    }
  }
}
