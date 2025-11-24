<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\CronLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Helpers\Helpers;
use App\Http\Resources\InvoiceResource;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use Illuminate\Bus\Queueable;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\BankProductsConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\BankProductRepaymentPriority;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Models\BankGeneralProductConfiguration;
use App\Models\NoaTemplate;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;

class CheckInvoiceDueDate implements ShouldQueue
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
    // // Get invoices that are due today
    // $invoices = Invoice::where('financing_status', 'financed')
    //   ->where('due_date', now()->format('Y-m-d'))
    //   ->get();

    $invoices = DB::table('invoices')
      ->where('financing_status', 'disbursed')
      ->where('due_date', now()->format('Y-m-d'))
      ->get();

    try {
      DB::beginTransaction();

      foreach ($invoices as $invoice_details) {
        $use_ifrs = false;

        $invoice = Invoice::find($invoice_details->id);

        $prefix = '';

        $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
        $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
        $factoring_with_recourse = ProgramCode::where('name', Program::FACTORING_WITH_RECOURSE)->first();
        $factoring_without_recourse = ProgramCode::where('name', Program::FACTORING_WITHOUT_RECOURSE)->first();
        $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

        $penal_discount_income_bank_account = null;
        $discount_income_bank_account = null;
        $fees_income_bank_account = null;
        $tax_income_bank_account = null;

        $discount_type = $invoice->discount_charge_type;
        $fee_type = $invoice->fee_charge_type;

        if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
          if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            // Get Bank Configured Receivable Accounts
            $prefix = 'VFR' . $invoice->program->bank_id;
            $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
              ->where('section', 'Vendor Finance Receivable')
              ->where('product_code_id', $invoice->program->program_code_id)
              ->where('product_type_id', $invoice->program->program_type_id)
              ->where('name', 'Fee Income Account')
              ->first();
            $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
              ->where('section', 'Vendor Finance Receivable')
              ->where('product_code_id', $invoice->program->program_code_id)
              ->where('product_type_id', $invoice->program->program_type_id)
              ->where('name', 'Discount Income Account')
              ->first();
            $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
              ->where('section', 'Vendor Finance Receivable')
              ->where('product_code_id', $invoice->program->program_code_id)
              ->where('product_type_id', $invoice->program->program_type_id)
              ->where('name', 'Discount Receivable Account')
              ->first();
          } else {
            if ($invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
              // Factoring without recourse
              $prefix = 'FWR' . $invoice->program->bank_id;
              $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                ->where('section', 'Factoring Without Recourse')
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('name', 'Fee Income Account')
                ->first();
              $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                ->where('section', 'Factoring Without Recourse')
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('name', 'Discount Income Account')
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
            } else {
              // Factoring with recourse
              $prefix = 'FR' . $invoice->program->bank_id;
              $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                ->where('section', 'Factoring With Recourse')
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('name', 'Fee Income Account')
                ->first();
              $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                ->where('section', 'Factoring With Recourse')
                ->where('product_code_id', $invoice->program->program_code_id)
                ->where('product_type_id', $invoice->program->program_type_id)
                ->where('name', 'Discount Income Account')
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
            }
          }
        } else {
          $prefix = 'DF' . $invoice->program->bank_id;
          $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
            ->where('product_type_id', $invoice->program->programType->id)
            ->where('product_code_id', null)
            ->where('name', 'Fee Income Account')
            ->first();
          $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
            ->where('product_code_id', null)
            ->where('product_type_id', $invoice->program->program_type_id)
            ->where('name', 'Discount Income Account')
            ->first();
          $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
            ->where('product_code_id', null)
            ->where('product_type_id', $invoice->program->program_type_id)
            ->where('name', 'Discount Receivable from Overdraft')
            ->first();
        }

        $bank_account = null;
        $od_account = null;

        // Get anchors bank account
        if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
          // Dealer Pays back. Get dealer's bank details
          $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
            ->where('company_id', $invoice->company_id)
            ->first();

          $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
            ->where('company_id', $invoice->company_id)
            ->first()->payment_account_number;

          // Get dealer's bank details
          $vendor_bank_account = $bank_account;

          $reference_number =
            'DF0' .
            $invoice->program->bank_id .
            '' .
            now()->format('y') .
            '000' .
            Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING);
        } else {
          if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            // Anchor pays back. Get anchor's bank details
            $bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();

            $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
              ->where('company_id', $invoice->company_id)
              ->first()->payment_account_number;

            // Get vendors's bank details
            $vendor_bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
              ->where('company_id', $invoice->company_id)
              ->first();

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
            // Buyer pays back. Get buyer's bank details
            $bank_account = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
              ->where('buyer_id', $invoice->buyer_id)
              ->first();

            $od_account = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
              ->where('buyer_id', $invoice->buyer_id)
              ->first()->payment_account_number;

            // Get anchors's bank details
            $vendor_bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();

            if ($invoice->program->programCode->name === Program::FACTORING_WITH_RECOURSE) {
              $reference_number =
                'FR0' .
                $invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                  Program::FACTORING_WITH_RECOURSE,
                  Program::FACTORING_WITHOUT_RECOURSE,
                ]);
            } else {
              $reference_number =
                'FWR0' .
                $invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                  Program::FACTORING_WITH_RECOURSE,
                  Program::FACTORING_WITHOUT_RECOURSE,
                ]);
            }
          }
        }

        if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
          $credit_account_name = $invoice->program->anchor->name;
        } else {
          if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
            $credit_account_name = $invoice->program->anchor->name;
          } else {
            $credit_account_name = $invoice->buyer->name;
          }
        }

        // Create repayment transaction
        if (Carbon::parse($invoice->due_date)->equalTo(now()->format('Y-m-d'))) {
          // Create cron log
          $invoice_repayment_cron = CronLog::create([
            'bank_id' => $invoice->program->bank_id,
            'name' => 'Invoice Repayment',
            'start_time' => now(),
            'status' => 'in progress',
          ]);

          if (!$invoice->eligibility) {
            $invoice->update(['eligibility' => 100]);
          }

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

          if ($amount_repayable > 0) {
            if ($discount_type == Invoice::FRONT_ENDED) {
              // Discount Front Ended Start
              $payment_request = PaymentRequest::create([
                'reference_number' => $reference_number,
                'invoice_id' => $invoice->id,
                'amount' => $amount_repayable,
                'payment_request_date' => $invoice->due_date,
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

              // Principle Repayment Transaction
              CbsTransaction::create([
                'bank_id' => $payment_request->invoice->program->bank->id,
                'payment_request_id' => $payment_request->id,
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

              // If Eligibility is less than 100% create transaction to credit the vendors account
              if ($invoice->eligibility < 100) {
                // Principle repayments
                $payment_request->paymentAccounts()->create([
                  'account' => $vendor_bank_account->account_number,
                  'account_name' => $vendor_bank_account->company
                    ? $vendor_bank_account->company->name
                    : $vendor_bank_account->program->anchor->name,
                  'amount' => $invoice->invoice_total_amount - $amount_repayable,
                  'type' => 'vendor_payment',
                  'description' => 'Vendor Invoice Payment',
                ]);

                // Principle Repayment Transaction
                CbsTransaction::create([
                  'bank_id' => $payment_request->invoice->program->bank->id,
                  'payment_request_id' => $payment_request->id,
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
                  'credit_to_account' => $vendor_bank_account->account_number,
                  'credit_to_account_name' => $vendor_bank_account->company
                    ? $vendor_bank_account->company->name
                    : $vendor_bank_account->program->anchor->name,
                  'credit_to_account_description' => $vendor_bank_account->company
                    ? $vendor_bank_account->company->name
                    : $vendor_bank_account->program->anchor->name .
                      ' (' .
                      $vendor_bank_account->bank_name .
                      ': ' .
                      $vendor_bank_account->account_number .
                      ')',
                  'amount' => $invoice->invoice_total_amount - $amount_repayable,
                  'transaction_created_date' => $invoice->due_date,
                  'pay_date' => $invoice->due_date,
                  'status' => 'Created',
                  'transaction_type' => 'Funds Transfer',
                  'product' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? Program::VENDOR_FINANCING
                      : Program::DEALER_FINANCING,
                ]);
              }

              // TODO: Re-enable settlements later
              if ($use_ifrs) {
                // Create Transfer funds transactions from Discount Receivable to Discount Income
                $discount_payments = PaymentRequestAccount::whereHas('paymentRequest', function ($query) use (
                  $invoice
                ) {
                  $query->where('invoice_id', $invoice->id);
                })
                  ->where('type', 'discount')
                  ->get();

                if ($discount_payments->count() > 0) {
                  foreach ($discount_payments as $discount_payment) {
                    $discount_settlement_reference_number = '';
                    if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
                      $discount_settlement_reference_number =
                        $prefix .
                        '0' .
                        now()->format('y') .
                        '000' .
                        Helpers::generateSequentialReferenceNumber(
                          $invoice->program->bank_id,
                          Program::DEALER_FINANCING
                        );
                    } else {
                      if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
                        $discount_settlement_reference_number =
                          $prefix .
                          '0' .
                          now()->format('y') .
                          '000' .
                          Helpers::generateSequentialReferenceNumber(
                            $invoice->program->bank_id,
                            Program::VENDOR_FINANCING,
                            [Program::VENDOR_FINANCING_RECEIVABLE]
                          );
                      } else {
                        $discount_settlement_reference_number =
                          $prefix .
                          '0' .
                          now()->format('y') .
                          '000' .
                          Helpers::generateSequentialReferenceNumber(
                            $invoice->program->bank_id,
                            Program::VENDOR_FINANCING,
                            [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                          );
                      }
                    }

                    $discount_payment_request = PaymentRequest::create([
                      'reference_number' => $discount_settlement_reference_number,
                      'invoice_id' => $invoice->id,
                      'amount' => $discount_payment->amount,
                      'payment_request_date' => $invoice->due_date,
                      'status' => 'approved',
                      'approval_status' => 'approved',
                    ]);

                    $discount_payment_request->paymentAccounts()->create([
                      'account' => $discount_income_bank_account->value,
                      'account_name' => $discount_income_bank_account->name,
                      'amount' => $discount_payment->amount,
                      'type' => 'discount',
                      'description' =>
                        $discount_payment->description == Invoice::VENDOR_DISCOUNT_BEARING
                          ? Invoice::VENDOR_DISCOUNT_BEARING
                          : Invoice::ANCHOR_DISCOUNT_BEARING,
                    ]);

                    CbsTransaction::create([
                      'bank_id' => $discount_payment_request->invoice->program->bank->id,
                      'payment_request_id' => $discount_payment_request->id,
                      'debit_from_account' => $discount_payment->account,
                      'debit_from_account_name' => $discount_payment->account_name,
                      'debit_from_account_description' => $discount_payment->account . '(Bank)',
                      'credit_to_account' => $discount_income_bank_account->value,
                      'credit_to_account_name' => $discount_income_bank_account->name,
                      'credit_to_account_description' =>
                        $invoice->discount_charge_type == Invoice::FRONT_ENDED
                          ? CbsTransaction::ADVANCE_DISCOUNT_SETTLEMENT .
                            ' (Bank: ' .
                            $discount_income_bank_account->value .
                            ')'
                          : CbsTransaction::UNREALIZED_DISCOUNT_SETTLEMENT .
                            ' (Bank: ' .
                            $discount_income_bank_account->value .
                            ')',
                      'amount' => $discount_payment->amount,
                      'transaction_created_date' => $invoice->due_date,
                      'pay_date' => $invoice->due_date,
                      'status' => 'Created',
                      'transaction_type' =>
                        $invoice->discount_charge_type == Invoice::FRONT_ENDED
                          ? CbsTransaction::ADVANCE_DISCOUNT_SETTLEMENT
                          : CbsTransaction::UNREALIZED_DISCOUNT_SETTLEMENT,
                      'product' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? Program::VENDOR_FINANCING
                          : Program::DEALER_FINANCING,
                    ]);
                  }
                }
                // Discount Front Ended End
              }

              // Fee Charging
              if ($fee_type == Invoice::REAR_ENDED) {
                // Get Tax Transaction created during request
                $fees_payment_accounts = PaymentRequestAccount::whereHas('paymentRequest', function ($query) use (
                  $invoice
                ) {
                  $query->where('invoice_id', $invoice->id);
                })
                  ->where('type', 'program_fees')
                  ->get();

                if ($fees_payment_accounts->count() > 0) {
                  foreach ($fees_payment_accounts as $fees_payment_account) {
                    $fees_payment_account->update([
                      'payment_request_id' => $payment_request->id,
                    ]);

                    if (
                      $fees_payment_account->description == Invoice::ANCHOR_FEE_BEARING ||
                      $fees_payment_account->description == Invoice::BUYER_FEE_BEARING
                    ) {
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
                      CbsTransaction::create([
                        'bank_id' => $payment_request->invoice->program->bank->id,
                        'payment_request_id' => $payment_request->id,
                        'debit_from_account' => $bank_account->account_number,
                        'debit_from_account_name' => $bank_account->name_as_per_bank,
                        'debit_from_account_description' =>
                          $anchor_name . ' (' . $bank_account->bank_name . ': ' . $bank_account->account_number . ')',
                        'credit_to_account' => $fees_income_bank_account
                          ? $fees_income_bank_account->value
                          : 'Fees Income Bank Account',
                        'credit_to_account_name' => $fees_income_bank_account
                          ? $fees_income_bank_account->name
                          : 'Fees Income Bank Account',
                        'credit_to_account_description' => 'Charges (Bank: ' . $fees_income_bank_account->value . ')',
                        'amount' => $fees_payment_account->amount,
                        'transaction_created_date' => $invoice->due_date,
                        'pay_date' => $invoice->due_date,
                        'status' => 'Created',
                        'transaction_type' => CbsTransaction::FEES_CHARGES,
                        'product' =>
                          $invoice->program->programType->name == Program::VENDOR_FINANCING
                            ? Program::VENDOR_FINANCING
                            : Program::DEALER_FINANCING,
                      ]);
                    } elseif ($fees_payment_account->description == Invoice::VENDOR_FEE_BEARING) {
                      CbsTransaction::create([
                        'bank_id' => $payment_request->invoice->program->bank->id,
                        'payment_request_id' => $payment_request->id,
                        'debit_from_account' => $vendor_bank_account->account_number,
                        'debit_from_account_name' => $vendor_bank_account->name_as_per_bank,
                        'debit_from_account_description' =>
                          $payment_request->invoice->company->name .
                          ' (' .
                          $vendor_bank_account->bank_name .
                          ': ' .
                          $vendor_bank_account->account_number .
                          ')',
                        'credit_to_account' => $fees_income_bank_account
                          ? $fees_income_bank_account->value
                          : 'Fees Income Bank Account',
                        'credit_to_account_name' => $fees_income_bank_account
                          ? $fees_income_bank_account->name
                          : 'Fees Income Bank Account',
                        'credit_to_account_description' => 'Charges (Bank: ' . $fees_income_bank_account->value . ')',
                        'amount' => $fees_payment_account->amount,
                        'transaction_created_date' => $invoice->due_date,
                        'pay_date' => $invoice->due_date,
                        'status' => 'Created',
                        'transaction_type' => CbsTransaction::FEES_CHARGES,
                        'product' =>
                          $invoice->program->programType->name == Program::VENDOR_FINANCING
                            ? Program::VENDOR_FINANCING
                            : Program::DEALER_FINANCING,
                      ]);
                    }
                  }
                }

                // Get Tax on Fees Transaction created during request
                $tax_on_fee_payment_accounts = PaymentRequestAccount::whereHas('paymentRequest', function ($query) use (
                  $invoice
                ) {
                  $query->where('invoice_id', $invoice->id);
                })
                  ->where('type', 'program_fees_taxes')
                  ->get();

                if ($tax_on_fee_payment_accounts->count() > 0) {
                  foreach ($tax_on_fee_payment_accounts as $tax_on_fee_payment_account) {
                    $tax_on_fee_payment_account->update([
                      'payment_request_id' => $payment_request->id,
                    ]);

                    CbsTransaction::create([
                      'bank_id' => $payment_request->invoice->program->bank->id,
                      'payment_request_id' => $payment_request->id,
                      'debit_from_account' => $vendor_bank_account->account_number,
                      'debit_from_account_name' => $vendor_bank_account->name_as_per_bank,
                      'debit_from_account_description' =>
                        $payment_request->invoice->company->name .
                        ' (' .
                        $vendor_bank_account->bank_name .
                        ': ' .
                        $vendor_bank_account->account_number .
                        ')',
                      'credit_to_account' => $tax_income_bank_account->value,
                      'credit_to_account_name' => $tax_income_bank_account->name,
                      'credit_to_account_description' => 'Charges (Bank: ' . $tax_income_bank_account->value . ')',
                      'amount' => $tax_on_fee_payment_account->amount,
                      'transaction_created_date' => $invoice->due_date,
                      'pay_date' => $invoice->due_date,
                      'status' => 'Created',
                      'transaction_type' => CbsTransaction::FEES_CHARGES,
                      'product' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? Program::VENDOR_FINANCING
                          : Program::DEALER_FINANCING,
                    ]);
                  }
                }
              }
            } else {
              // INFO: Discount Rear Ended Start
              $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
              $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
              $factoring_with_recourse = ProgramCode::where('name', Program::FACTORING_WITH_RECOURSE)->first();
              $factoring_without_recourse = ProgramCode::where('name', Program::FACTORING_WITHOUT_RECOURSE)->first();
              $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

              $discount_income_bank_account = null;
              $fees_income_bank_account = null;
              $tax_income_bank_account = null;

              // Get Bank Configured Receivable Accounts
              if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
                if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                  $discount_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $invoice->program->bank_id
                  )
                    ->where('section', 'Vendor Finance Receivable')
                    ->where('product_code_id', $vendor_financing_receivable->id)
                    ->where('product_type_id', $vendor_financing->id)
                    ->where('name', 'Discount Income Account')
                    ->first();
                  $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                    ->where('section', 'Vendor Finance Receivable')
                    ->where('product_code_id', $vendor_financing_receivable->id)
                    ->where('product_type_id', $vendor_financing->id)
                    ->where('name', 'Fee Income Account')
                    ->first();
                } else {
                  if ($invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                    // Factoring without recourse
                    $discount_income_bank_account = BankProductsConfiguration::where(
                      'bank_id',
                      $invoice->program->bank_id
                    )
                      ->where('section', 'Factoring Without Recourse')
                      ->where('product_type_id', $vendor_financing->id)
                      ->where('product_code_id', $invoice->program->program_code_id)
                      ->where('name', 'Discount Income Account')
                      ->first();
                    $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                      ->where('section', 'Factoring Without Recourse')
                      ->where('product_type_id', $vendor_financing->id)
                      ->where('product_code_id', $invoice->program->program_code_id)
                      ->where('name', 'Fee Income Account')
                      ->first();
                  } else {
                    // Factoring with recourse
                    $discount_income_bank_account = BankProductsConfiguration::where(
                      'bank_id',
                      $invoice->program->bank_id
                    )
                      ->where('section', 'Factoring With Recourse')
                      ->where('product_type_id', $vendor_financing->id)
                      ->where('product_code_id', $invoice->program->program_code_id)
                      ->where('name', 'Discount Income Account')
                      ->first();
                    $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                      ->where('section', 'Factoring With Recourse')
                      ->where('product_type_id', $vendor_financing->id)
                      ->where('product_code_id', $invoice->program->program_code_id)
                      ->where('name', 'Fee Income Account')
                      ->first();
                  }
                }
              } else {
                $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                  ->where('product_type_id', $dealer_financing->id)
                  ->where('product_code_id', null)
                  ->where('name', 'Discount Income Account')
                  ->first();
                $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                  ->where('product_type_id', $dealer_financing->id)
                  ->where('product_code_id', null)
                  ->where('name', 'Fee Income Account')
                  ->first();
                $tax_income_bank_account = BankProductsConfiguration::where('bank_id', $invoice->program->bank_id)
                  ->where('product_type_id', $dealer_financing->id)
                  ->where('product_code_id', null)
                  ->where('name', 'Tax Account Number')
                  ->first();
              }

              $total_amount = $invoice->calculated_total_amount;
              if ($total_amount == 0) {
                $total_amount = $invoice->invoice_total_amount;
              }

              if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                $legible_amount = ($invoice->eligibility / 100) * $invoice->drawdown_amount;
              } else {
                $legible_amount = ($invoice->eligibility / 100) * $invoice->invoice_total_amount;
              }

              $amount = $legible_amount;

              $repayment_priorities = BankProductRepaymentPriority::where('bank_id', $invoice->program->bank_id)
                ->where('product_type_id', $invoice->program->program_type_id)
                ->orderBy('premature_priority', 'DESC')
                ->get();

              $rear_end_settlement_reference_number = '';
              if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
                $rear_end_settlement_reference_number =
                  $prefix .
                  '0' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::DEALER_FINANCING);
              } else {
                if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
                  $rear_end_settlement_reference_number =
                    $prefix .
                    '0' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                      Program::VENDOR_FINANCING_RECEIVABLE,
                    ]);
                } else {
                  $rear_end_settlement_reference_number =
                    $prefix .
                    '0' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber($invoice->program->bank_id, Program::VENDOR_FINANCING, [
                      Program::FACTORING_WITH_RECOURSE,
                      Program::FACTORING_WITHOUT_RECOURSE,
                    ]);
                }
              }

              $payment_request = PaymentRequest::create([
                'reference_number' => $rear_end_settlement_reference_number,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_request_date' => $invoice->due_date,
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
                      'amount' => $amount,
                      'type' => 'principle_repayment',
                      'description' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? CbsTransaction::BANK_INVOICE_PAYMENT
                          : CbsTransaction::REPAYMENT,
                    ]);

                    $account_description = '';
                    if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                      $account_description =
                        $payment_request->invoice->program->anchor->name .
                        ' (' .
                        $bank_account->bank_name .
                        ': ' .
                        $bank_account->account_number .
                        ')';
                    } else {
                      if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                        $account_description =
                          $payment_request->invoice->program->anchor->name .
                          ' (' .
                          $bank_account->bank_name .
                          ': ' .
                          $bank_account->account_number .
                          ')';
                      } else {
                        $account_description =
                          $payment_request->invoice->buyer->name .
                          ' (' .
                          $bank_account->bank_name .
                          ': ' .
                          $bank_account->account_number .
                          ')';
                      }
                    }

                    CbsTransaction::create([
                      'bank_id' => $payment_request->invoice->program->bank->id,
                      'payment_request_id' => $payment_request->id,
                      'debit_from_account' => $bank_account->account_number,
                      'debit_from_account_name' => $bank_account->name_as_per_bank,
                      'debit_from_account_description' => $account_description,
                      'credit_to_account' => $od_account,
                      'credit_to_account_name' => $credit_account_name,
                      'credit_to_account_description' =>
                        $invoice->program->programType->name == Program::VENDOR_FINANCING
                          ? CbsTransaction::BANK_INVOICE_PAYMENT . ' (Bank: ' . $od_account . ')'
                          : CbsTransaction::REPAYMENT . ' (Bank: ' . $od_account . ')',
                      'amount' => $amount,
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

                    if ($invoice->eligibility < 100) {
                      $payment_request->paymentAccounts()->create([
                        'account' => $vendor_bank_account->account_number,
                        'account_name' => $vendor_bank_account->company
                          ? $vendor_bank_account->company->name
                          : $vendor_bank_account->program->anchor->name,
                        'amount' => $invoice->invoice_total_amount - $amount,
                        'type' => 'vendor_payment',
                        'description' => 'Vendor Invoice Payment',
                      ]);

                      CbsTransaction::create([
                        'bank_id' => $payment_request->invoice->program->bank->id,
                        'payment_request_id' => $payment_request->id,
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
                        'credit_to_account' => $vendor_bank_account->account_number,
                        'credit_to_account_name' => $vendor_bank_account->company
                          ? $vendor_bank_account->company->name
                          : $vendor_bank_account->program->anchor->name,
                        'credit_to_account_description' => $vendor_bank_account->company
                          ? $vendor_bank_account->company->name
                          : $vendor_bank_account->program->anchor->name .
                            ' (' .
                            $vendor_bank_account->bank_name .
                            ': ' .
                            $vendor_bank_account->account_number .
                            ')',
                        'amount' => $invoice->invoice_total_amount - $amount,
                        'transaction_created_date' => $invoice->due_date,
                        'pay_date' => $invoice->due_date,
                        'status' => 'Created',
                        'transaction_type' => CbsTransaction::FUNDS_TRANSFER,
                        'product' =>
                          $invoice->program->programType->name == Program::VENDOR_FINANCING
                            ? Program::VENDOR_FINANCING
                            : Program::DEALER_FINANCING,
                      ]);
                    }
                    break;
                  case 'Fees and Charges':
                    // In case Fee Charging is Rear Ended
                    if ($fee_type == Invoice::REAR_ENDED) {
                      // Get Fees Transactions created during request
                      $fees_payment_accounts = PaymentRequestAccount::whereHas('paymentRequest', function ($query) use (
                        $invoice
                      ) {
                        $query->where('invoice_id', $invoice->id);
                      })
                        ->where('type', 'program_fees')
                        ->get();

                      if ($fees_payment_accounts->count() > 0) {
                        foreach ($fees_payment_accounts as $fees_payment_account) {
                          $payment_request->paymentAccounts()->create([
                            'account' => $discount_receivable_bank_account->value,
                            'account_name' => $discount_receivable_bank_account->name,
                            'amount' => $fees_payment_account->amount,
                            'type' => $fees_payment_account->type,
                            'description' => $fees_payment_account->description,
                          ]);

                          if ($fees_payment_account->description == Invoice::ANCHOR_FEE_BEARING) {
                            // Get the anchor
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
                            // Should debit anchors CASA Account and Debit Bank Discount Receivable
                            CbsTransaction::create([
                              'bank_id' => $payment_request->invoice->program->bank->id,
                              'payment_request_id' => $payment_request->id,
                              'debit_from_account' => $bank_account->account_number,
                              'debit_from_account_name' => $bank_account->name_as_per_bank,
                              'debit_from_account_description' =>
                                $anchor_name .
                                ' (' .
                                $bank_account->bank_name .
                                ': ' .
                                $bank_account->account_number .
                                ')',
                              'credit_to_account' => $discount_receivable_bank_account->value,
                              'credit_to_account_name' => $discount_receivable_bank_account->name,
                              'credit_to_account_description' =>
                                'Charges (Bank: ' . $discount_receivable_bank_account->value . ')',
                              'amount' => $fees_payment_account->amount,
                              'transaction_created_date' => $invoice->due_date,
                              'pay_date' => $invoice->due_date,
                              'status' => 'Created',
                              'transaction_type' => CbsTransaction::FEES_CHARGES,
                              'product' =>
                                $invoice->program->programType->name == Program::VENDOR_FINANCING
                                  ? Program::VENDOR_FINANCING
                                  : Program::DEALER_FINANCING,
                            ]);
                          } else {
                            // Should debit vendor's CASA Account and Debit Discount Receivable Account
                            CbsTransaction::create([
                              'bank_id' => $payment_request->invoice->program->bank->id,
                              'payment_request_id' => $payment_request->id,
                              'debit_from_account' => $vendor_bank_account->account_number,
                              'debit_from_account_name' => $vendor_bank_account->name_as_per_bank,
                              'debit_from_account_description' =>
                                $payment_request->invoice->company->name .
                                ' (' .
                                $vendor_bank_account->bank_name .
                                ': ' .
                                $vendor_bank_account->account_number .
                                ')',
                              'credit_to_account' => $discount_receivable_bank_account->value,
                              'credit_to_account_name' => $discount_receivable_bank_account->name,
                              'credit_to_account_description' =>
                                'Charges (Bank: ' . $discount_receivable_bank_account->value . ')',
                              'amount' => $fees_payment_account->amount,
                              'transaction_created_date' => $invoice->due_date,
                              'pay_date' => $invoice->due_date,
                              'status' => 'Created',
                              'transaction_type' => CbsTransaction::FEES_CHARGES,
                              'product' =>
                                $invoice->program->programType->name == Program::VENDOR_FINANCING
                                  ? Program::VENDOR_FINANCING
                                  : Program::DEALER_FINANCING,
                            ]);
                          }
                        }
                      }

                      // Get Tax on Fees Transaction created during request
                      $tax_on_fee_payment_accounts = PaymentRequestAccount::whereHas('paymentRequest', function (
                        $query
                      ) use ($invoice) {
                        $query->where('invoice_id', $invoice->id);
                      })
                        ->whereIn('type', ['program_fees_taxes', 'tax_on_fees'])
                        ->get();

                      // Vendor Should be paying tax on fees
                      if ($tax_on_fee_payment_accounts->count() > 0) {
                        foreach ($tax_on_fee_payment_accounts as $tax_on_fee_payment_account) {
                          // $tax_on_fee_payment_account->update([
                          //   'payment_request_id' => $payment_request->id,
                          // ]);
                          $payment_request->paymentAccounts()->create([
                            'account' => $discount_receivable_bank_account->value,
                            'account_name' => $discount_receivable_bank_account->name,
                            'amount' => $tax_on_fee_payment_account->amount,
                            'type' => 'program_fees_taxes',
                            'description' => $tax_on_fee_payment_account->description,
                          ]);

                          CbsTransaction::create([
                            'bank_id' => $payment_request->invoice->program->bank->id,
                            'payment_request_id' => $payment_request->id,
                            'debit_from_account' => $vendor_bank_account->account_number,
                            'debit_from_account_name' => $vendor_bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $payment_request->invoice->company->name .
                              ' (' .
                              $vendor_bank_account->bank_name .
                              ': ' .
                              $vendor_bank_account->account_number .
                              ')',
                            'credit_to_account' => $discount_receivable_bank_account->value,
                            'credit_to_account_name' => $discount_receivable_bank_account->name,
                            'credit_to_account_description' =>
                              'Charges (Bank: ' . $discount_receivable_bank_account->value . ')',
                            'amount' => $tax_on_fee_payment_account->amount,
                            'transaction_created_date' => $invoice->due_date,
                            'pay_date' => $invoice->due_date,
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::FEES_CHARGES,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }
                      }
                    }

                    // Check if there's Tax on Discount Charge
                    $tax_on_discount_payment_accounts = PaymentRequestAccount::whereHas('paymentRequest', function (
                      $query
                    ) use ($invoice) {
                      $query->where('invoice_id', $invoice->id);
                    })
                      ->where('type', 'tax_on_discount')
                      ->get();

                    // Credit to tax on discount account
                    if ($tax_on_discount_payment_accounts->count() > 0) {
                      foreach ($tax_on_discount_payment_accounts as $tax_on_discount_payment_account) {
                        $payment_request->paymentAccounts()->create([
                          'account' => $discount_receivable_bank_account->value,
                          'account_name' => $discount_receivable_bank_account->name,
                          'amount' => $tax_on_discount_payment_account->amount,
                          'type' => 'tax_on_discount',
                          'description' => $tax_on_discount_payment_account->description,
                        ]);

                        CbsTransaction::create([
                          'bank_id' => $payment_request->invoice->program->bank->id,
                          'payment_request_id' => $payment_request->id,
                          'debit_from_account' => $vendor_bank_account->account_number,
                          'debit_from_account_name' => $vendor_bank_account->name_as_per_bank,
                          'debit_from_account_description' =>
                            $payment_request->invoice->company->name .
                            ' (' .
                            $vendor_bank_account->bank_name .
                            ': ' .
                            $vendor_bank_account->account_number .
                            ')',
                          'credit_to_account' => $discount_receivable_bank_account->value,
                          'credit_to_account_name' => $discount_receivable_bank_account->name,
                          'credit_to_account_description' =>
                            'Charges (Bank: ' . $discount_receivable_bank_account->value . ')',
                          'amount' => $tax_on_discount_payment_account->amount,
                          'transaction_created_date' => $invoice->due_date,
                          'pay_date' => $invoice->due_date,
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
                    // Get Discount Transaction created during request
                    $discount_payment_accounts = PaymentRequestAccount::whereHas('paymentRequest', function (
                      $query
                    ) use ($invoice) {
                      $query->where('invoice_id', $invoice->id);
                    })
                      ->where('type', 'discount')
                      ->get();

                    if ($discount_payment_accounts->count() > 0) {
                      foreach ($discount_payment_accounts as $discount_payment_account) {
                        // Create new payment request for discount payment with the same amount and accounts
                        // as the original discount payment account
                        if (
                          $discount_payment_account->description == Invoice::ANCHOR_DISCOUNT_BEARING ||
                          $discount_payment_account->description == Invoice::BUYER_DISCOUNT_BEARING
                        ) {
                          $payment_request->paymentAccounts()->create([
                            'account' => $discount_receivable_bank_account->value,
                            'account_name' => $discount_receivable_bank_account->name,
                            'amount' => $discount_payment_account->amount,
                            'type' => 'discount',
                            'description' => $discount_payment_account->description,
                          ]);

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

                          // Debit Anchor's/Buyer's CASA account to discount receivable
                          CbsTransaction::create([
                            'bank_id' => $payment_request->invoice->program->bank->id,
                            'payment_request_id' => $payment_request->id,
                            'debit_from_account' => $bank_account->account_number,
                            'debit_from_account_name' => $bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $anchor_name .
                              ' (' .
                              $bank_account->bank_name .
                              ': ' .
                              $bank_account->account_number .
                              ')',
                            'credit_to_account' => $discount_receivable_bank_account->value,
                            'credit_to_account_name' => $discount_receivable_bank_account->name,
                            'credit_to_account_description' =>
                              'Accrual/Posted Interest (Bank: ' . $discount_receivable_bank_account->value . ')',
                            'amount' => $discount_payment_account->amount,
                            'transaction_created_date' => $invoice->due_date,
                            'pay_date' => $invoice->due_date,
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        } else {
                          $discount_receivable_reference_number = '';
                          if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
                            $discount_receivable_reference_number =
                              $prefix .
                              '0' .
                              now()->format('y') .
                              '000' .
                              Helpers::generateSequentialReferenceNumber(
                                $invoice->program->bank_id,
                                Program::DEALER_FINANCING
                              );
                          } else {
                            if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
                              $discount_receivable_reference_number =
                                $prefix .
                                '0' .
                                now()->format('y') .
                                '000' .
                                Helpers::generateSequentialReferenceNumber(
                                  $invoice->program->bank_id,
                                  Program::VENDOR_FINANCING,
                                  [Program::VENDOR_FINANCING_RECEIVABLE]
                                );
                            } else {
                              $discount_receivable_reference_number =
                                $prefix .
                                '0' .
                                now()->format('y') .
                                '000' .
                                Helpers::generateSequentialReferenceNumber(
                                  $invoice->program->bank_id,
                                  Program::VENDOR_FINANCING,
                                  [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                                );
                            }
                          }
                          // Should debit vendor's CASA Account and Debit Discount Receivable Account
                          $vendor_discount_payment_request = PaymentRequest::create([
                            'reference_number' => $discount_receivable_reference_number,
                            'invoice_id' => $invoice->id,
                            'amount' => $discount_payment_account->amount,
                            'payment_request_date' => $invoice->due_date,
                            'status' => 'approved',
                            'approval_status' => 'approved',
                          ]);

                          $vendor_discount_payment_request->paymentAccounts()->create([
                            'account' => $discount_receivable_bank_account->value,
                            'account_name' => $discount_receivable_bank_account->name,
                            'amount' => $discount_payment_account->amount,
                            'type' => 'discount',
                            'description' => $discount_payment_account->description,
                          ]);

                          CbsTransaction::create([
                            'bank_id' => $vendor_discount_payment_request->invoice->program->bank->id,
                            'payment_request_id' => $vendor_discount_payment_request->id,
                            'debit_from_account' => $vendor_bank_account->account_number,
                            'debit_from_account_name' => $vendor_bank_account->name_as_per_bank,
                            'debit_from_account_description' =>
                              $payment_request->invoice->company->name .
                              ' (' .
                              $vendor_bank_account->bank_name .
                              ': ' .
                              $vendor_bank_account->account_number .
                              ')',
                            'credit_to_account' => $discount_receivable_bank_account->value,
                            'credit_to_account_name' => $discount_receivable_bank_account->name,
                            'credit_to_account_description' =>
                              CbsTransaction::ACCRUAL_POSTED_INTEREST .
                              ' (Bank: ' .
                              $discount_receivable_bank_account->value .
                              ')',
                            'amount' => $discount_payment_account->amount,
                            'transaction_created_date' => $invoice->due_date,
                            'pay_date' => $invoice->due_date,
                            'status' => 'Created',
                            'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }

                        if ($use_ifrs) {
                          // Create payment request for unrealized discount to discount income
                          $unrealized_settlement_reference_number = '';
                          if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
                            $unrealized_settlement_reference_number =
                              $prefix .
                              '0' .
                              now()->format('y') .
                              '000' .
                              Helpers::generateSequentialReferenceNumber(
                                $invoice->program->bank_id,
                                Program::DEALER_FINANCING
                              );
                          } else {
                            if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
                              $unrealized_settlement_reference_number =
                                $prefix .
                                '0' .
                                now()->format('y') .
                                '000' .
                                Helpers::generateSequentialReferenceNumber(
                                  $invoice->program->bank_id,
                                  Program::VENDOR_FINANCING,
                                  [Program::VENDOR_FINANCING_RECEIVABLE]
                                );
                            } else {
                              $unrealized_settlement_reference_number =
                                $prefix .
                                '0' .
                                now()->format('y') .
                                '000' .
                                Helpers::generateSequentialReferenceNumber(
                                  $invoice->program->bank_id,
                                  Program::VENDOR_FINANCING,
                                  [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                                );
                            }
                          }

                          $realized_discount_payment_request = PaymentRequest::create([
                            'reference_number' => $unrealized_settlement_reference_number,
                            'invoice_id' => $invoice->id,
                            'amount' => $discount_payment_account->amount,
                            'payment_request_date' => $invoice->due_date,
                            'status' => 'approved',
                            'approval_status' => 'approved',
                          ]);

                          $realized_discount_payment_request->paymentAccounts()->create([
                            'account' => $discount_income_bank_account->value,
                            'account_name' => $discount_income_bank_account->name,
                            'amount' => $discount_payment_account->amount,
                            'type' => 'discount',
                            'description' => $discount_payment_account->description,
                          ]);

                          // Create transaction to transfer from Unrealized Discount to Discount Income
                          CbsTransaction::create([
                            'bank_id' => $realized_discount_payment_request->invoice->program->bank->id,
                            'payment_request_id' => $realized_discount_payment_request->id,
                            'debit_from_account' => $discount_payment_account->account,
                            'debit_from_account_name' => $discount_payment_account->account_name,
                            'debit_from_account_description' =>
                              $discount_payment_account->account_name .
                              ' (Bank: ' .
                              $discount_payment_account->account .
                              ')',
                            'credit_to_account' => $discount_income_bank_account
                              ? $discount_income_bank_account->value
                              : 'Discount Income Bank Account',
                            'credit_to_account_name' => $discount_income_bank_account
                              ? $discount_income_bank_account->name
                              : 'Discount Income Bank Account',
                            'credit_to_account_description' =>
                              $invoice->discount_charge_type == Invoice::FRONT_ENDED
                                ? CbsTransaction::ADVANCE_DISCOUNT_SETTLEMENT .
                                  ' (Bank: ' .
                                  ($discount_income_bank_account ? $discount_income_bank_account->value : 'N/A') .
                                  ')'
                                : CbsTransaction::UNREALIZED_DISCOUNT_SETTLEMENT .
                                  ' (Bank: ' .
                                  ($discount_income_bank_account ? $discount_income_bank_account->value : 'N/A') .
                                  ')',
                            'amount' => $discount_payment_account->amount,
                            'transaction_created_date' => $invoice->due_date,
                            'pay_date' => $invoice->due_date,
                            'status' => 'Created',
                            'transaction_type' =>
                              $invoice->discount_charge_type == Invoice::FRONT_ENDED
                                ? CbsTransaction::ADVANCE_DISCOUNT_SETTLEMENT
                                : CbsTransaction::UNREALIZED_DISCOUNT_SETTLEMENT,
                            'product' =>
                              $invoice->program->programType->name == Program::VENDOR_FINANCING
                                ? Program::VENDOR_FINANCING
                                : Program::DEALER_FINANCING,
                          ]);
                        }
                      }
                    }
                    break;
                  case 'Other Debt Entries':
                    break;
                  case 'Penal Interest':
                    break;
                  default:
                    # code...
                    break;
                }
              }
              // INFO: Discount Rear ended end
            }
          }

          $invoice_repayment_cron->update([
            'end_time' => now(),
            'status' => 'completed',
          ]);
        }
      }
      DB::commit();
    } catch (\Throwable $th) {
      //throw $th;
      info($th);
      DB::rollBack();
    }

    // Maturity handling on holidays
    $invoices = Invoice::where('financing_status', 'disbursed')
      ->whereDate('due_date', '>', now()->format('Y-m-d'))
      ->get();

    foreach ($invoices as $invoice) {
      $bank_holiday_list = $invoice->program->bank->holidays->where('status', 'active')->pluck('date');
      if ($bank_holiday_list->count() > 0) {
        if ($bank_holiday_list->contains($invoice->due_date)) {
          switch ($invoice->program->discountDetails->first()?->maturity_handling_on_holidays) {
            case 'Prepone to previous working day':
              $prev_day = Carbon::parse($invoice->due_date)
                ->subDay()
                ->format('Y-m-d');
              $i = 1;
              do {
                $prev_day = Carbon::parse($invoice->due_date)
                  ->subDays($i)
                  ->format('Y-m-d');
                $i++;
              } while ($bank_holiday_list->contains($prev_day));
              $invoice->due_date = $prev_day;
              $invoice->save();
              break;
            case 'Postpone to next working day':
              $next_day = Carbon::parse($invoice->due_date)
                ->addDay()
                ->format('Y-m-d');
              $i = 1;
              do {
                $next_day = Carbon::parse($invoice->due_date)
                  ->addDays($i)
                  ->format('Y-m-d');
                $i++;
              } while ($bank_holiday_list->contains($next_day));
              $invoice->due_date = $next_day;
              $invoice->save();
              break;
          }
        }
      }

      $bank_configuration = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
        ->where('product_type_id', $invoice->program->program_type_id)
        ->where('name', 'maturity handling on weekend')
        ->first();

      if (
        $bank_configuration &&
        Carbon::parse($invoice->due_date)->isWeekend() &&
        $bank_configuration->value != 'no effect'
      ) {
        if ($bank_configuration->value == 'prepone to previous working day') {
          $prev_day = Carbon::parse($invoice->due_date)->subDay();

          $i = 1;
          do {
            $prev_day = Carbon::parse($invoice->due_date)->subDays($i);
            $i++;
          } while ($prev_day->isWeekend());
          $invoice->due_date = $prev_day->format('Y-m-d');
          $invoice->save();
        }

        if ($bank_configuration->value == 'postpone to next working day') {
          $next_day = Carbon::parse($invoice->due_date)->addDay();
          $i = 1;
          do {
            $next_day = Carbon::parse($invoice->due_date)->addDays($i);
            $i++;
          } while ($next_day->isWeekend());
          $invoice->due_date = $next_day->format('Y-m-d');
          $invoice->save();
        }
      }
    }

    // Updated Pending Transactions to Failed
    $due_requested_cbs_transactions = CbsTransaction::where('status', 'Created')
      ->whereHas('paymentRequest', function ($q) {
        $q->whereHas('invoice', function ($q) {
          $q->whereIn('financing_status', ['submitted', 'financed'])->whereDate('due_date', now()->format('Y-m-d'));
        });
      })
      ->get();

    foreach ($due_requested_cbs_transactions as $due_requested_cbs_transaction) {
      $due_requested_cbs_transaction->update([
        'status' => 'Failed',
      ]);

      $due_requested_cbs_transaction->paymentRequest->update([
        'status' => 'rejected',
        'approval_status' => 'rejected',
        'rejected_reason' => 'Invoice due date is past',
      ]);

      $due_requested_cbs_transaction->paymentRequest->invoice->update([
        'financing_status' => 'denied',
        'rejected_reason' => 'Invoice due date is past',
      ]);

      $program_vendor_configuration = ProgramVendorConfiguration::where(
        'payment_account_number',
        $due_requested_cbs_transaction->debit_from_account
      )->first();

      if ($program_vendor_configuration) {
        $due_requested_cbs_transaction->paymentRequest->invoice->company->decrement(
          'utilized_amount',
          $due_requested_cbs_transaction->paymentRequest->invoice->program->programType->name ==
          Program::DEALER_FINANCING
            ? $due_requested_cbs_transaction->paymentRequest->invoice->drawdown_amount
            : ($due_requested_cbs_transaction->paymentRequest->invoice->eligibility / 100) *
              $payment_request->invoice->invoice_total_amount
        );

        $due_requested_cbs_transaction->paymentRequest->invoice->program->decrement(
          'utilized_amount',
          $due_requested_cbs_transaction->paymentRequest->invoice->program->programType->name ==
          Program::DEALER_FINANCING
            ? $due_requested_cbs_transaction->paymentRequest->invoice->drawdown_amount
            : ($due_requested_cbs_transaction->paymentRequest->invoice->eligibility / 100) *
              $due_requested_cbs_transaction->paymentRequest->invoice->invoice_total_amount
        );

        $program_vendor_configuration->decrement(
          'utilized_amount',
          $due_requested_cbs_transaction->paymentRequest->invoice->program->programType->name ==
          Program::DEALER_FINANCING
            ? $due_requested_cbs_transaction->paymentRequest->invoice->drawdown_amount
            : ($due_requested_cbs_transaction->paymentRequest->invoice->eligibility / 100) *
              $due_requested_cbs_transaction->paymentRequest->invoice->invoice_total_amount
        );
      }
    }
  }
}
