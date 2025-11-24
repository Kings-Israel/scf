<?php

namespace App\Jobs;

use App\Models\BankProductsConfiguration;
use App\Models\BankTaxRate;
use App\Models\Invoice;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestAccount;
use App\Models\PaymentRequestApproval;
use App\Models\Program;
use App\Models\ProgramCode;
use App\Models\ProgramDiscount;
use App\Models\ProgramType;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePaymentRequestAmount implements ShouldQueue
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
    $payment_requests = PaymentRequest::where('status', 'created')
      ->whereIn('approval_status', ['pending_maker', 'pending_checker'])
      ->whereDate('payment_request_date', '<', now()->format('Y-m-d'))
      ->get();

    foreach ($payment_requests as $payment_request) {
      $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
      $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
      $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

      if ($payment_request->invoice->program->programType->name === Program::VENDOR_FINANCING) {
        if ($payment_request->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
          // Vendor Financing Receivable
          $vendor_discount_details = ProgramVendorDiscount::select(
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing'
          )
            ->where('company_id', $payment_request->invoice->company_id)
            ->where('program_id', $payment_request->invoice->program_id)
            ->first();

          $vendor_fees = ProgramVendorFee::where('company_id', $payment_request->invoice->company_id)
            ->where('program_id', $payment_request->invoice->program_id)
            ->get();

          $fees_income_bank_account = BankProductsConfiguration::where(
            'bank_id',
            $payment_request->invoice->program->bank_id
          )
            ->where('section', 'Vendor Finance Receivable')
            ->where('product_code_id', $vendor_financing_receivable->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Fee Income Account')
            ->first();
        } else {
          $vendor_discount_details = ProgramVendorDiscount::select(
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing'
          )
            ->where('company_id', $payment_request->invoice->company_id)
            ->where('buyer_id', $payment_request->invoice->buyer_id)
            ->where('program_id', $payment_request->invoice->program_id)
            ->first();

          $vendor_fees = ProgramVendorFee::where('company_id', $payment_request->invoice->company_id)
            ->where('buyer_id', $payment_request->invoice->buyer_id)
            ->where('program_id', $payment_request->invoice->program_id)
            ->get();

          if ($payment_request->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
            $fees_income_bank_account = BankProductsConfiguration::where(
              'bank_id',
              $payment_request->invoice->program->bank->id
            )
              ->where('section', 'Factoring Without Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $payment_request->invoice->program->program_code_id)
              ->where('name', 'Fee Income Account')
              ->first();
          } else {
            // Factoring with recourse
            $fees_income_bank_account = BankProductsConfiguration::where(
              'bank_id',
              $payment_request->invoice->program->bank->id
            )
              ->where('section', 'Factoring With Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $payment_request->invoice->program->program_code_id)
              ->where('name', 'Fee Income Account')
              ->first();
          }
        }
      } else {
        // Dealer Financing
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'anchor_discount_bearing',
          'vendor_discount_bearing'
        )
          ->where('company_id', $payment_request->invoice->company_id)
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $payment_request->invoice->company_id)
          ->where('program_id', $payment_request->invoice->program_id)
          ->get();

        $fees_income_bank_account = BankProductsConfiguration::where(
          'bank_id',
          $payment_request->invoice->program->bank->id
        )
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Fee Income Account')
          ->first();
      }

      // Get Tax on Discount Value
      $tax_on_discount = ProgramDiscount::select('tax_on_discount')
        ->where('program_id', $payment_request->invoice->program_id)
        ->first()?->tax_on_discount;

      $eligibility = $payment_request->invoice->eligibility;
      $total_amount = $payment_request->invoice->calculated_total_amount;
      if ($total_amount == 0) {
        $total_amount = $payment_request->invoice->invoice_total_amount;
      }

      $total_roi = $vendor_discount_details ? $vendor_discount_details->total_roi : 0;
      $legible_amount = ($eligibility / 100) * $total_amount;

      $vendor_bearing_fees = 0;
      $fees_amount = 0;

      PaymentRequestAccount::where('payment_request_id', $payment_request->id)
        ->whereIn('type', ['program_fees'])
        ->delete();

      PaymentRequestAccount::where('payment_request_id', $payment_request->id)
        ->whereIn('type', ['tax_on_fees'])
        ->delete();

      $payment_date = now();

      // Fee charges
      $fees_amount = 0;
      $anchor_bearing_fees = 0;
      $vendor_bearing_fees = 0;
      $fees_tax_amount = 0;
      if ($vendor_fees->count() > 0) {
        foreach ($vendor_fees as $fee) {
          if ($fee->type === 'amount') {
            if ($fee->charge_type === 'daily') {
              $fees_amount +=
                $fee->value * ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                  2
                );
              }

              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees +=
                  $fee->value * ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);
              } else {
                $anchor_bearing_fees +=
                  ($fee->anchor_bearing_discount / 100) *
                  $fee->value *
                  ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);
                $vendor_bearing_fees +=
                  ($fee->vendor_bearing_discount / 100) *
                  $fee->value *
                  ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);
              }
            } else {
              $fees_amount += $fee->value;

              if ($fee->taxes) {
                $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
              }

              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += $fee->value;
              } else {
                $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
                $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
              }
            }
          }

          if ($fee->type == 'percentage') {
            if ($fee->charge_type === 'daily') {
              $fees_amount +=
                ($fee->value / 100) *
                $legible_amount *
                ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                  2
                );
              }

              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += round(
                  ($fee->value / 100) *
                    $legible_amount *
                    ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                  2
                );
              } else {
                $anchor_bearing_fees += round(
                  ($fee->anchor_bearing_discount / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                  2
                );
                $vendor_bearing_fees += round(
                  ($fee->vendor_bearing_discount / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                  2
                );
              }
            } else {
              $fees_amount += ($fee->value / 100) * $legible_amount;

              if ($fee->taxes) {
                $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
              }

              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
              } else {
                $anchor_bearing_fees += round(
                  ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                  2
                );
                $vendor_bearing_fees += round(
                  ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                  2
                );
              }
            }
          }

          if ($fee->type === 'per amount') {
            if ($fee->charge_type === 'daily') {
              $fees_amount +=
                floor($legible_amount / $fee->per_amount) *
                $fee->value *
                ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                  2
                );
              }

              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += round(
                  floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                  2
                );
              } else {
                $anchor_bearing_fees += round(
                  ($fee->anchor_bearing_discount / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                  2
                );
                $vendor_bearing_fees += round(
                  ($fee->vendor_bearing_discount / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                  2
                );
              }
            } else {
              $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                );
              }

              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
              } else {
                $anchor_bearing_fees += round(
                  ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                );
                $vendor_bearing_fees += round(
                  ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                );
              }
            }
          }
        }
      }

      $anchor_bearing_discount_value = 0;

      $request_approval_date = now();

      $original_discount =
        $legible_amount *
        ($total_roi / 100) *
        (($request_approval_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1) / 365);

      // Tax on discount
      $discount_tax_amount = 0;
      if ($tax_on_discount && $tax_on_discount > 0) {
        $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
      }

      $discount = 0;
      if ($total_roi > 0) {
        if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
          $discount = $original_discount;
        } else {
          if ($vendor_discount_details->anchor_discount_bearing > 0) {
            $discount =
              ($vendor_discount_details->anchor_discount_bearing / $total_roi) *
              $legible_amount *
              ($total_roi / 100) *
              (($request_approval_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1) / 365);
          } else {
            $discount = $original_discount;
          }
        }
      }

      $anchor_bearing_discount_value = round($original_discount - $discount, 2);

      if ($payment_request->invoice->program->programType->name === Program::VENDOR_FINANCING) {
        $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount - $vendor_bearing_fees;
      } else {
        if ($payment_request->invoice->drawdown_amount) {
          $amount =
            $payment_request->invoice->drawdown_amount -
            $fees_amount -
            $discount -
            $fees_tax_amount -
            $discount_tax_amount;
        } else {
          $amount = $total_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
        }
      }

      $vendor_amount = $amount;

      // Calculate amount to disburse to vendor based on discount type and fee type
      if ($payment_request->invoice->discount_charge_type === Invoice::REAR_ENDED) {
        $vendor_amount = $amount + $discount + $discount_tax_amount;
      }

      if ($payment_request->invoice->fee_charge_type === Invoice::REAR_ENDED) {
        if ($payment_request->invoice->program->programType->name === Program::VENDOR_FINANCING) {
          $vendor_amount = $vendor_amount + $fees_tax_amount + $vendor_bearing_fees;
        } else {
          $vendor_amount = $vendor_amount + $fees_tax_amount + $fees_amount;
        }
      }

      $payment_request->update([
        'amount' => $vendor_amount,
        'payment_request_date' => now()->format('Y-m-d'),
        'anchor_discount_bearing' => $discount,
        'vendor_discount_bearing' => $original_discount - $anchor_bearing_discount_value,
      ]);

      $vendor_account = PaymentRequestAccount::where('payment_request_id', $payment_request->id)
        ->where('type', 'vendor_account')
        ->first();

      $vendor_account->update([
        'amount' => $vendor_amount,
      ]);

      if ($payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
        $discount_account = PaymentRequestAccount::where('payment_request_id', $payment_request->id)
          ->where('type', 'discount')
          ->where('description', Invoice::VENDOR_DISCOUNT_BEARING)
          ->first();
      } else {
        $discount_account = PaymentRequestAccount::where('payment_request_id', $payment_request->id)
          ->where('type', 'discount')
          ->where('description', Invoice::DEALER_DISCOUNT_BEARING)
          ->first();
      }

      if ($discount_account) {
        $discount_account->update([
          'amount' => $discount,
        ]);
      }

      $anchor_bearing_discount_account = PaymentRequestAccount::where('payment_request_id', $payment_request->id)
        ->where('type', 'discount')
        ->whereIn('description', [Invoice::ANCHOR_DISCOUNT_BEARING, Invoice::BUYER_DISCOUNT_BEARING])
        ->first();

      if ($anchor_bearing_discount_account) {
        $anchor_bearing_discount_account->update([
          'amount' => $anchor_bearing_discount_value,
        ]);
      }

      $discount_tax_account = PaymentRequestAccount::where('payment_request_id', $payment_request->id)
        ->where('type', 'tax_on_discount')
        ->first();

      if ($discount_tax_account) {
        $discount_tax_account->update([
          'amount' => $discount_tax_amount,
        ]);
      } else {
        if ($discount_tax_amount > 0) {
          $tax_income_bank_account = null;
          $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

          if ($payment_request->invoice->program->programType->name == Program::DEALER_FINANCING) {
            $tax_income_bank_account = BankProductsConfiguration::where(
              'bank_id',
              $payment_request->invoice->program->bank_id
            )
              ->where('product_type_id', $dealer_financing->id)
              ->where('product_code_id', null)
              ->where('name', 'Tax Account Number')
              ->first();

            $payment_request->paymentAccounts()->create([
              'account' => $tax_income_bank_account->value,
              'account_name' => $tax_income_bank_account->name,
              'amount' => round($discount_tax_amount, 2),
              'type' => 'tax_on_discount',
              'description' => 'Tax on Discount',
            ]);
          } else {
            $tax_income_account = BankTaxRate::where('bank_id', $payment_request->invoice->program->bank_id)
              ->where('value', $tax_on_discount)
              ->where('status', 'active')
              ->first();

            $payment_request->paymentAccounts()->create([
              'account' => $tax_income_account->account_no,
              'account_name' => 'Tax Income Bank Account',
              'amount' => round($discount_tax_amount, 2),
              'type' => 'tax_on_discount',
              'description' => 'tax on discount',
            ]);
          }
        }
      }

      if ($vendor_fees->count() > 0) {
        foreach ($vendor_fees as $fee) {
          $fee_account = $fee->account_number
            ? $fee->account_number
            : ($fees_income_bank_account
              ? $fees_income_bank_account->value
              : 'Fee_Inc_Acc');
          $fee_account_name = $fee->account_name
            ? $fee->account_name
            : ($fees_income_bank_account
              ? $fees_income_bank_account->name
              : 'Fee Income Account');

          if ($fee->type === 'amount') {
            // Dealer Financing
            if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? $fee->value
                  : $fee->value * ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => Invoice::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            } else {
              // Vendor Financing
              $anchor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? ($fee->anchor_bearing_discount / 100) * $fee->value
                  : ($fee->anchor_bearing_discount / 100) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);
              if ($anchor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($anchor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' =>
                    $payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING &&
                    $payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                      ? Invoice::ANCHOR_FEE_BEARING
                      : Invoice::BUYER_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? ($fee->vendor_bearing_discount / 100) * $fee->value
                  : ($fee->vendor_bearing_discount / 100) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1);
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => Invoice::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            }
          }

          if ($fee->type == 'percentage') {
            // Dealer Financing
            if ($payment_request->invoice->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(($fee->value / 100) * $legible_amount, 2)
                  : round(
                    ($fee->value / 100) *
                      $legible_amount *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                    2
                  );
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => Invoice::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            } else {
              // Vendor Financing
              $anchor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount), 2)
                  : round(
                    ($fee->anchor_bearing_discount / 100) *
                      (($fee->value / 100) *
                        $legible_amount *
                        ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                    2
                  );
              if ($anchor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($anchor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' =>
                    $payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING &&
                    $payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                      ? Invoice::ANCHOR_FEE_BEARING
                      : Invoice::BUYER_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount), 2)
                  : round(
                    ($fee->vendor_bearing_discount / 100) *
                      (($fee->value / 100) *
                        $legible_amount *
                        ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                    2
                  );
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => Invoice::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            }
          }

          if ($fee->type == 'per amount') {
            // Dealer Financing
            if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(floor($legible_amount / $fee->per_amount) * $fee->value, 2)
                  : round(
                    floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                    2
                  );
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => Invoice::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            } else {
              // Vendor Financing
              $anchor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(
                    ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                    2
                  )
                  : round(
                    ($fee->anchor_bearing_discount / 100) *
                      (floor($legible_amount / $fee->per_amount) *
                        $fee->value *
                        ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                    2
                  );

              if ($anchor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($anchor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' =>
                    $payment_request->program->programType->name == Program::VENDOR_FINANCING &&
                    $payment_request->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                      ? Invoice::ANCHOR_FEE_BEARING
                      : Invoice::BUYER_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }

              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(
                    ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                    2
                  )
                  : round(
                    ($fee->vendor_bearing_discount / 100) *
                      (floor($legible_amount / $fee->per_amount) *
                        $fee->value *
                        ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                    2
                  );

              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => Invoice::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            }
          }
        }
      }

      // Credit Fee Taxes to Fees taxes Account
      if ($fees_tax_amount > 0) {
        if ($vendor_fees->count() > 0) {
          foreach ($vendor_fees as $fee) {
            if ($fee->type == 'amount') {
              if ($fee->taxes) {
                $fees_tax_amount =
                  $fee->charge_type === 'fixed'
                    ? round(($fee->taxes / 100) * $fee->value, 2)
                    : round(
                      ($fee->taxes / 100) *
                        $fee->value *
                        ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                      2
                    );
              }
            }

            if ($fee->type == 'percentage') {
              if ($fee->taxes) {
                $fees_tax_amount =
                  $fee->charge_type === 'fixed'
                    ? round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2)
                    : round(
                      ($fee->taxes / 100) *
                        (($fee->value / 100) * $legible_amount) *
                        ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1),
                      2
                    );
              }
            }

            if ($fee->type == 'per amount') {
              if ($fee->taxes) {
                $fees_tax_amount =
                  $fee->charge_type === 'fixed'
                    ? round(($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value), 2)
                    : round(
                      ($fee->taxes / 100) *
                        (floor($legible_amount / $fee->per_amount) *
                          $fee->value *
                          ($payment_date->diffInDays(Carbon::parse($payment_request->invoice->due_date)) + 1)),
                      2
                    );
              }
            }

            $tax_income_account = BankTaxRate::where('bank_id', $payment_request->invoice->program->bank_id)
              ->where('value', $fee->taxes)
              ->where('status', 'active')
              ->first();

            if ($tax_income_account) {
              $payment_request->paymentAccounts()->create([
                'account' => $tax_income_account->account_no,
                'account_name' => 'Tax Income Bank Account',
                'amount' => round($fees_tax_amount, 2),
                'type' => 'tax_on_fees',
                'description' => 'Tax on Fees for ' . $fee->fee_name,
              ]);
            } else {
              $payment_request->paymentAccounts()->create([
                'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fees Income Bank Account',
                'account_name' => $fees_income_bank_account
                  ? $fees_income_bank_account->name
                  : 'Fees Income Bank Account',
                'amount' => round($fees_tax_amount, 2),
                'type' => 'tax_on_fees',
                'description' => 'Tax on Fees for ' . $fee->fee_name,
              ]);
            }
          }
        }
      }
    }
  }
}
