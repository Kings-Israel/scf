<?php

namespace App\Imports;

use App\Models\CbsTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;

class MigrationFactoringCBSImport implements ToCollection, WithHeadingRow, WithMapping
{
  public function map($row): array
  {
    return [
      'cbs_id' => $row['cbs_id'],
      'pr_id' => $row['pr_id'],
      'credit_to' => $row['credit_to'],
      'debit_from' => $row['debit_from'],
      'amount' => str_replace(
        ',',
        '',
        str_replace('-', '', str_replace('(', '', str_replace(')', '', $row['amount_ksh'])))
      ),
      'invoice_number' => $row['invoice_unique_reference_no'],
      'pay_date' => $row['pay_date'],
      'transaction_date' => $row['transaction_date'],
      'reference_no' => $row['reference_no'],
      'transaction_type' => $row['transaction_type'],
      'product' => $row['product_type'],
      'status' => $row['status'],
      'created_at' => $row['created_at'],
      'updated_at' => $row['last_updated_at'],
    ];
  }

  /**
   * @param Collection $collection
   */
  public function collection(Collection $collection)
  {
    $previous_pr_id = $collection->first()['pr_id'];
    $incremented_pr_id = PaymentRequest::orderBy('pr_id', 'DESC')->first()?->pr_id + 1;
    $latest_pr_id = PaymentRequest::orderBy('pr_id', 'DESC')->first()?->pr_id + 1;
    foreach ($collection as $row) {
      if ($previous_pr_id !== $row['pr_id']) {
        $incremented_pr_id = $latest_pr_id + 1;
      } else {
        $incremented_pr_id = $latest_pr_id;
      }

      $latest_cbs_id = CbsTransaction::orderBy('cbs_id', 'DESC')->first()?->cbs_id;

      // Find the respective invoice
      $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();
      if ($invoice) {
        $program = $invoice->program;
        $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
          ->where('program_id', $invoice->program_id)
          ->first();

        $vendor_bank_account = ProgramVendorBankDetail::where('company_id', $invoice->company_id)
          ->where('buyer_id', $invoice->buyer_id)
          ->where('program_id', $invoice->program_id)
          ->first();
        $anchor_bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
        $transaction_date =
          !empty($row['transaction_date']) && $row['transaction_date'] != '-' && $row['transaction_date'] != ''
            ? (gettype($row['transaction_date']) == 'integer' || gettype($row['transaction_date']) == 'double'
              ? Date::excelToDateTimeObject($row['transaction_date'])->format('Y-m-d')
              : Carbon::createFromFormat('d/m/Y', $row['transaction_date'])->format('Y-m-d'))
            : null;
        $pay_date =
          !empty($row['pay_date']) && $row['pay_date'] != '-' && $row['pay_date'] != ''
            ? (gettype($row['pay_date']) == 'integer' || gettype($row['pay_date']) == 'double'
              ? Date::excelToDateTimeObject($row['pay_date'])->format('Y-m-d')
              : Carbon::createFromFormat('d/m/Y', $row['pay_date'])->format('Y-m-d'))
            : null;

        $transaction_created_date =
          gettype($row['created_at']) == 'integer' || gettype($row['created_at']) == 'double'
            ? Date::excelToDateTimeObject($row['created_at'])->format('Y-m-d')
            : ($row['created_at'] != null
              ? Carbon::createFromFormat('d/m/Y', explode(' ', $row['created_at'])[0])->format('Y-m-d')
              : null);

        if ($program && $program_vendor_configuration && $vendor_bank_account && $anchor_bank_account) {
          $cbs_transaction = CbsTransaction::create([
            'cbs_id' => $latest_cbs_id + 1,
            'bank_id' => $invoice->program->bank_id,
            'debit_from_account' => $row['debit_from'],
            'credit_to_account' => $row['credit_to'],
            'amount' => (float) $row['amount'],
            'transaction_created_date' => $transaction_created_date,
            'transaction_date' => $transaction_date,
            'pay_date' => $pay_date,
            'transaction_reference' => $row['reference_no'],
            'status' => $row['status'],
            'transaction_type' =>
              $row['transaction_type'] == 'Funds transfer' ? 'Funds Transfer' : $row['transaction_type'],
            'product' => $row['product'],
            'created_at' =>
              !empty($row['created_at']) && $row['created_at'] != '-' && $row['created_at'] != ''
                ? (gettype($row['created_at']) == 'integer' || gettype($row['created_at']) == 'double'
                  ? Date::excelToDateTimeObject($row['created_at'])->format('Y-m-d H:i')
                  : ($row['created_at'] != null
                    ? (Str::endsWith($row['created_at'], 'PM') || Str::endsWith($row['created_at'], 'AM')
                      ? Carbon::createFromFormat('d/m/Y H:i:s A', str_replace('  ', ' ', $row['created_at']))->format(
                        'Y-m-d H:i:s'
                      )
                      : Carbon::createFromFormat('d/m/Y H:i:s', str_replace('  ', ' ', $row['created_at']))->format(
                        'Y-m-d H:i:s'
                      ))
                    : null))
                : null,
            'updated_at' =>
              !empty($row['updated_at']) && $row['updated_at'] != '-' && $row['updated_at'] != ''
                ? (gettype($row['updated_at']) == 'integer' || gettype($row['updated_at']) == 'double'
                  ? Date::excelToDateTimeObject($row['updated_at'])->format('Y-m-d H:i')
                  : ($row['updated_at'] != null
                    ? (Str::endsWith($row['updated_at'], 'PM') || Str::endsWith($row['updated_at'], 'AM')
                      ? Carbon::createFromFormat('d/m/Y H:i:s A', str_replace('  ', ' ', $row['updated_at']))->format(
                        'Y-m-d H:i:s'
                      )
                      : Carbon::createFromFormat('d/m/Y H:i:s', str_replace('  ', ' ', $row['updated_at']))->format(
                        'Y-m-d H:i:s'
                      ))
                    : null))
                : null,
          ]);

          $payment_request_status = 'created';
          $payment_request_approval_status = 'pending_maker';

          if ($row['status'] == 'Successful') {
            $payment_request_status = 'paid';
            $payment_request_approval_status = 'paid';
          } elseif ($row['status'] == 'Created') {
            $payment_request_status = 'approved';
            $payment_request_approval_status = 'approved';
          } elseif ($row['status'] == 'Failed' || $row['status'] == 'Permanently Failed') {
            $payment_request_status = 'denied';
            $payment_request_approval_status = 'denied';
          }

          $reference_number = '';

          $words = explode(' ', $invoice->company->name);
          $acronym = '';

          foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
          }

          // Get Bank Configured Receivable Accounts
          if ($program->programType->name == Program::VENDOR_FINANCING) {
            if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
              // Vendor Financing Receivable
              $reference_number = 'VFR' . $program->bank_id . '' . $acronym . '000' . $invoice->id;
            } else {
              if ($program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                // Factoring without recourse
                $reference_number = 'FWR' . $program->bank_id . '' . $acronym . '000' . $invoice->id;
              } else {
                // Factoring with recourse
                $reference_number = 'FR' . $program->bank_id . '' . $acronym . '000' . $invoice->id;
              }
            }
          } else {
            // Dealer Financing
            $reference_number = 'DF' . $program->bank_id . '' . $acronym . '000' . $invoice->id;
          }

          $payment_request = PaymentRequest::where('pr_id', $incremented_pr_id)->first();

          if (!$payment_request) {
            // Create the disbursement payment request
            $payment_request = PaymentRequest::create([
              'pr_id' => $incremented_pr_id,
              'reference_number' => $reference_number,
              'invoice_id' => $invoice->id,
              'amount' => $row['amount'],
              // 'processing_fee' => round($fees_amount, 2),
              'payment_request_date' => $pay_date,
              'status' => $payment_request_status,
              'approval_status' => $payment_request_approval_status,
              // 'anchor_discount_bearing' => $discount,
              // 'vendor_discount_bearing' => $anchor_bearing_discount_value,
              // 'created_by' => auth()->id(),
              'created_at' => $pay_date,
              'updated_at' => $pay_date,
            ]);
          }

          switch ($row['transaction_type']) {
            case 'Loan Disbursement':
              if (Carbon::parse($invoice->due_date)->greaterThan($pay_date)) {
                $latest_pi = Invoice::where('buyer_id', $invoice->buyer_id)->where('pi_number', '!=', NULL)->whereIn('financing_status', ['financed', 'closed'])->orderBy('id', 'DESC')->first();

                $invoice->update([
                  'financing_status' => 'financed',
                  'status' => 'disbursed',
                  'stage' => 'disbursed',
                  'disbursement_date' => $transaction_date,
                  'disbursed_amount' => $row['amount'],
                  'pi_number' => 'PI_' . explode('_', $latest_pi->pi_number)[1] + 1
                ]);
              }

              $cbs_transaction->update([
                'transaction_type' => Carbon::parse($invoice->due_date)->equalTo($pay_date)
                  ? 'Funds Transfer'
                  : 'Payment Disbursement',
                'payment_request_id' => $payment_request->id,
                'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
                'credit_to_account_description' => $invoice->company->name . '(' . $row['credit_to'] . ')',
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);

              // Create Payment Account
              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => Carbon::parse($invoice->due_date)->equalTo($pay_date) ? 'vendor_payment' : 'vendor_account',
                'description' => Carbon::parse($invoice->due_date)->equalTo($pay_date)
                  ? 'Vendor Invoice Payment'
                  : 'vendor account',
              ]);

              if ($row['status'] == 'Successful') {
                $payment_request->invoice->update([
                  'disbursed_amount' => $row['amount'],
                  'disbursement_date' => $transaction_date,
                  'payment_date' => $transaction_date,
                  'created_at' => $transaction_date,
                  'updated_at' => $transaction_date,
                ]);
              }
              break;
            case 'Accrual/Posted Interest':
              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
                'credit_to_account_description' => 'Accrual/Posted Interest (Bank: ' . $row['credit_to'] . ')',
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'discount',
                'description' => Invoice::VENDOR_DISCOUNT_BEARING,
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);
              break;
            case 'Funds Transfer':
              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'debit_from_account_description' => $invoice->buyer->name . ' (' . $row['debit_from'] . ')',
                'credit_to_account_description' => $invoice->program->anchor->name . ' (' . $row['credit_to'] . ')',
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'vendor_payment',
                'description' => 'Vendor Invoice Payment',
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);
              break;
            case 'Funds transfer':
              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'debit_from_account_description' => $invoice->buyer->name . ' (' . $row['debit_from'] . ')',
                'credit_to_account_description' => $invoice->program->anchor->name . ' (' . $row['credit_to'] . ')',
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'vendor_payment',
                'description' => 'Vendor Invoice Payment',
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);
              break;
            case 'Repayment':
              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'debit_from_account_description' => $invoice->buyer->name . ' (' . $row['debit_from'] . ')',
                'credit_to_account_description' => $invoice->program->anchor->name . ' (' . $row['credit_to'] . ')',
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'vendor_payment',
                'description' => 'Vendor Invoice Payment',
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);
              break;
            case 'Bank Invoice Payment':
              $account_description = '';
              if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
              } else {
                if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                  $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
                } else {
                  $account_description = $invoice->buyer->name . ' (' . $row['debit_from'] . ')';
                }
              }

              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'credit_to_account_description' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? 'Bank Invoice Payment (Bank: ' . $row['credit_to'] . ')'
                    : 'Repayment (Bank: ' . $row['credit_to'] . ')',
                'debit_from_account_description' => $account_description,
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'principle_repayment',
                'description' => 'Bank Invoice Payment',
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);

              if ($row['status'] == 'Successful') {
                Payment::create([
                  'invoice_id' => $invoice->id,
                  'amount' => $row['amount'],
                  'created_at' => $transaction_date,
                  'updated_at' => $transaction_date,
                ]);
              }
              break;
            case 'Bank invoice payment':
              $account_description = '';
              if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
              } else {
                if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                  $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
                } else {
                  $account_description = $invoice->buyer->name . ' (' . $row['debit_from'] . ')';
                }
              }

              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'credit_to_account_description' =>
                  $invoice->program->programType->name == Program::VENDOR_FINANCING
                    ? 'Bank Invoice Payment (Bank: ' . $row['credit_to'] . ')'
                    : 'Repayment (Bank: ' . $row['credit_to'] . ')',
                'debit_from_account_description' => $account_description,
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'principle_repayment',
                'description' => 'Bank Invoice Payment',
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);

              if ($row['status'] == 'Successful') {
                Payment::create([
                  'invoice_id' => $invoice->id,
                  'amount' => $row['amount'],
                  'created_at' => $transaction_date,
                  'updated_at' => $transaction_date,
                ]);
              }
              break;
            case 'Fees / Charges':
              $cbs_transaction->update([
                'payment_request_id' => $payment_request->id,
                'transaction_type' => 'Fees/Charges',
                'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
                'credit_to_account_description' => 'Charges (Bank: ' . $row['credit_to'] . ')',
              ]);

              $payment_request->paymentAccounts()->create([
                'account' => $row['credit_to'],
                'amount' => $row['amount'],
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'created_at' => $transaction_date,
                'updated_at' => $transaction_date,
              ]);
              break;
            default:
              # code...
              break;
          }

          $od_account = ProgramVendorConfiguration::where('payment_account_number', $row['debit_from'])->first();

          if ($od_account) {
            if ($od_account->payment_account_number == $row['debit_from'] && $row['status'] == 'Created') {
              if ($row['transaction_type'] != 'Bank Invoice Payment' && $row['transaction_type'] != 'Repayment') {
                // Update Program and Company Pipeline and Utilized Amounts
                $payment_request->invoice->company->increment('utilized_amount', $row['amount']);

                $payment_request->invoice->program->increment('utilized_amount', $row['amount']);

                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'company_id',
                  $payment_request->invoice->company_id
                )
                  ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                    $query->where('buyer_id', $payment_request->invoice->buyer_id);
                  })
                  ->where('program_id', $payment_request->invoice->program_id)
                  ->first();

                $program_vendor_configuration->increment('utilized_amount', $row['amount']);
              }
            }

            if ($od_account->payment_account_number == $row['debit_from'] && $row['status'] == 'Successful') {
              if ($row['transaction_type'] != 'Bank Invoice Payment' && $row['transaction_type'] != 'Repayment') {
                // Update Program and Company Pipeline and Utilized Amounts
                $payment_request->invoice->company->increment('utilized_amount', $row['amount']);

                $payment_request->invoice->program->increment('utilized_amount', $row['amount']);

                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'company_id',
                  $payment_request->invoice->company_id
                )
                  ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                    $query->where('buyer_id', $payment_request->invoice->buyer_id);
                  })
                  ->where('program_id', $payment_request->invoice->program_id)
                  ->first();

                $program_vendor_configuration->increment('utilized_amount', $row['amount']);
              }
            }
          }

          $od_account = ProgramVendorConfiguration::where('payment_account_number', $row['credit_to'])->first();

          if (
            $od_account &&
            $od_account->payment_account_number == $row['credit_to'] &&
            $row['status'] == 'Successful'
          ) {
            if ($row['transaction_type'] === 'Bank Invoice Payment' && $row['transaction_type'] === 'Repayment') {
              // Update Program and Company Pipeline and Utilized Amounts
              $payment_request->invoice->company->decrement('utilized_amount', $row['amount']);

              $payment_request->invoice->program->decrement('utilized_amount', $row['amount']);

              $program_vendor_configuration = ProgramVendorConfiguration::where(
                'company_id',
                $payment_request->invoice->company_id
              )
                ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                  $query->where('buyer_id', $payment_request->invoice->buyer_id);
                })
                ->where('program_id', $payment_request->invoice->program_id)
                ->first();

              $program_vendor_configuration->decrement('utilized_amount', $row['amount']);
            }
          }

          $previous_pr_id = $row['pr_id'];
          $latest_pr_id = PaymentRequest::orderBy('pr_id', 'DESC')->first()?->pr_id;
        }
      }
    }
  }
}
