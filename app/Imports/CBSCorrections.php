<?php

namespace App\Imports;

use App\Models\CbsTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CBSCorrections implements ToCollection, WithMapping, WithHeadingRow
{
  public function map($row): array
  {
    return [
      'action' => $row['action'],
      'cbs_id' => $row['cbs_id'],
      'pr_id' => $row['pr_id'],
      'debit_from' => $row['debit_from'],
      'credit_to' => $row['credit_to'],
      'ifsc_code' => $row['ifsc_code'],
      'amount' => (double) str_replace(',', '', str_replace('-', '', str_replace('(', '', str_replace(')', '', $row['amount_ksh'])))),
      'invoice_number' => $row['invoice_number'],
      // 'vendor' => $row['seller'],
      'pay_date' => $row['pay_date'],
      'transaction_date' => $row['transaction_date'],
      'transaction_reference_number' => $row['reference_no'],
      'transaction_type' => $row['transaction_type'],
      'product' => $row['product'],
      'payment_service' => $row['payment_service'],
      'status' => $row['status'],
      'created_by' => $row['created_by'],
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
      if ($row['action'] == 'REMOVE') {
        $cbs_transaction = CbsTransaction::where('transaction_reference', $row['transaction_reference_number'])->first();
        if ($cbs_transaction) {
          $cbs_transaction->paymentRequest?->delete();
          $cbs_transaction->delete();
        }
      } else {
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
          $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)->where('program_id', $invoice->program_id)->first();

          $vendor_bank_account = ProgramVendorBankDetail::where('company_id', $invoice->company_id)->where('program_id', $invoice->program_id)->first();
          $anchor_bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
          $transaction_date = !empty($row['transaction_date']) && $row['transaction_date'] != '-' && $row['transaction_date'] != '' ? gettype($row['transaction_date']) == 'integer' || gettype($row['transaction_date']) == 'double'
                ? Date::excelToDateTimeObject($row['transaction_date'])->format('Y-m-d')
                : ($row['transaction_date'] != null
                  ? Carbon::createFromFormat('d-M-y', $row['transaction_date'])->format('Y-m-d')
                  : null) : null;
          $pay_date = !empty($row['pay_date']) && $row['pay_date'] != '-' && $row['pay_date'] != '' ?
                gettype($row['pay_date']) == 'integer' || gettype($row['pay_date']) == 'double'
                ? Date::excelToDateTimeObject($row['pay_date'])->format('Y-m-d')
                : ($row['pay_date'] != null
                  ? Carbon::createFromFormat('d-M-y', $row['pay_date'])->format('Y-m-d')
                  : null) : null;

          if ($program && $program_vendor_configuration && $vendor_bank_account && $anchor_bank_account) {
            $cbs_transaction = CbsTransaction::create([
              'cbs_id' => $latest_cbs_id + 1,
              'bank_id' => $invoice->program->bank_id,
              'debit_from_account' => $row['debit_from'],
              'credit_to_account' => $row['credit_to'],
              'amount' => (double) $row['amount'],
              'transaction_created_date' => gettype($row['created_at']) == 'integer' || gettype($row['created_at']) == 'double'
                ? Date::excelToDateTimeObject($row['created_at'])->format('Y-m-d')
                : ($row['created_at'] != null
                  ? Carbon::parse($row['created_at'])->format('Y-m-d')
                  : null),
              'transaction_date' => $transaction_date,
              'pay_date' => $pay_date,
              'transaction_reference' => $row['transaction_reference_number'],
              'status' => $row['status'],
              'transaction_type' => $row['transaction_type'],
              'product' => $row['product'],
              'created_at' => !empty($row['created_at']) && $row['created_at'] != '-' && $row['created_at'] != '' ? gettype($row['created_at']) == 'integer' || gettype($row['created_at']) == 'double'
                ? Date::excelToDateTimeObject($row['created_at'])->format('Y-m-d H:i')
                : ($row['created_at'] != null
                  ? Carbon::parse($row['created_at'])->format('Y-m-d H:i')
                  : null) : null,
              'updated_at' => !empty($row['updated_at']) && $row['updated_at'] != '-' && $row['updated_at'] != '' ? gettype($row['updated_at']) == 'integer' || gettype($row['updated_at']) == 'double'
                ? Date::excelToDateTimeObject($row['updated_at'])->format('Y-m-d H:i')
                : ($row['updated_at'] != null
                  ? Carbon::parse($row['updated_at'])->format('Y-m-d H:i')
                  : null) : null,
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
                'updated_at' => $pay_date
              ]);
            }

            switch ($row['transaction_type']) {
              case 'Payment Disbursement':
                $cbs_transaction->update([
                  // 'transaction_type' => 'Payment Disbursement',
                  'payment_request_id' => $payment_request->id,
                  'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
                  'credit_to_account_description' => $invoice->company->name . '(' . $row['credit_to'] . ')',
                ]);

                // Create Payment Account
                $payment_request->paymentAccounts()->create([
                  'account' => $row['credit_to'],
                  'amount' => $row['amount'],
                  'type' => 'vendor_account',
                  'description' => 'vendor account',
                ]);

                if ($row['status'] == 'Successful') {
                  $payment_request->invoice->update([
                    'disbursed_amount' => $row['amount'],
                    'disbursement_date' => $transaction_date,
                    'payment_date' => $transaction_date,
                    'status' => 'disbursed',
                    'financing_status' => 'financed'
                  ]);
                }

                if ($row['status'] == 'Created') {
                  $payment_request->invoice->update([
                    'disbursed_amount' => 0,
                    'disbursement_date' => NULL,
                    'payment_date' => NULL,
                    'status' => 'approved',
                    'stage' => 'approved',
                    'financing_status' => 'submitted'
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
                  'updated_at' => $transaction_date
                ]);
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
                  'updated_at' => $transaction_date
                ]);
                break;
              case 'Bank Invoice Payment':
                $account_description = '';
                if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                  $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] .')';
                } else {
                  if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                    $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] .')';
                  } else {
                    $account_description = $invoice->buyer->name . ' (' . $row['debit_from'] .')';
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
                  'description' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? 'Bank Invoice Payment'
                      : 'Repayment',
                  'created_at' => $transaction_date,
                  'updated_at' => $transaction_date
                ]);

                if ($row['status'] == 'Successful') {
                  Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $row['amount'],
                    'created_at' => $transaction_date,
                    'updated_at' => $transaction_date
                  ]);
                }
                break;
              case 'Bank invoice payment':
                $account_description = '';
                if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
                  $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] .')';
                } else {
                  if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                    $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] .')';
                  } else {
                    $account_description = $invoice->buyer->name . ' (' . $row['debit_from'] .')';
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
                  'description' =>
                    $invoice->program->programType->name == Program::VENDOR_FINANCING
                      ? 'Bank Invoice Payment'
                      : 'Repayment',
                  'created_at' => $transaction_date,
                  'updated_at' => $transaction_date
                ]);

                if ($row['status'] == 'Successful') {
                  Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $row['amount'],
                    'created_at' => $transaction_date,
                    'updated_at' => $transaction_date
                  ]);
                }
                break;
              default:
                # code...
                break;
            }

            $sum = CbsTransaction::whereHas('paymentRequest', function ($q) use ($invoice) {
              $q->where('invoice_id', $invoice->id);
            })
            ->whereIn('transaction_type', ['Payment Disbursement', 'Accrual/Posted Interest', 'Fees/Charges'])
            ->sum('amount');

            $invoice->update([
              'total_amount' => $sum,
              'eligibility' => 100
            ]);

            // $od_account = ProgramVendorConfiguration::where('payment_account_number', $row['debit_from'])->orWhere('payment_account_number', $row['credit_to'])->first();

            // if ($od_account) {
            //   if ($od_account->payment_account_number == $row['debit_from'] && $cbs_transaction->status == 'Created') {
            //     // Update Program and Company Pipeline and Utilized Amounts
            //     $payment_request->invoice->company->update([
            //       'pipeline_amount' =>
            //         $payment_request->invoice->company->pipeline_amount + $row['amount'],
            //     ]);

            //     $payment_request->invoice->program->update([
            //       'pipeline_amount' =>
            //         $payment_request->invoice->program->pipeline_amount + $row['amount'],
            //     ]);

            //     $program_vendor_configuration = ProgramVendorConfiguration::where(
            //       'company_id',
            //       $payment_request->invoice->company_id
            //     )
            //       ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
            //         $query->where('buyer_id', $payment_request->invoice->buyer_id);
            //       })
            //       ->where('program_id', $payment_request->invoice->program_id)
            //       ->first();

            //     $program_vendor_configuration->update([
            //       'pipeline_amount' =>
            //         $program_vendor_configuration->pipeline_amount + $row['amount'],
            //     ]);
            //   }

            //   if ($od_account->payment_account_number == $row['debit_from'] && $cbs_transaction->status == 'Successful') {
            //     // Update Program and Company Pipeline and Utilized Amounts
            //     $payment_request->invoice->company->update([
            //       'utilized_amount' =>
            //         $payment_request->invoice->company->utilized_amount + $row['amount'],
            //       'pipeline_amount' =>
            //         $payment_request->invoice->company->pipeline_amount - $row['amount'],
            //     ]);

            //     $payment_request->invoice->program->update([
            //       'utilized_amount' =>
            //         $payment_request->invoice->program->utilized_amount + $row['amount'],
            //       'pipeline_amount' =>
            //         $payment_request->invoice->program->pipeline_amount - $row['amount'],
            //     ]);

            //     $program_vendor_configuration = ProgramVendorConfiguration::where(
            //       'company_id',
            //       $payment_request->invoice->company_id
            //     )
            //       ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
            //         $query->where('buyer_id', $payment_request->invoice->buyer_id);
            //       })
            //       ->where('program_id', $payment_request->invoice->program_id)
            //       ->first();

            //     $program_vendor_configuration->update([
            //       'utilized_amount' =>
            //         $program_vendor_configuration->utilized_amount + $row['amount'],
            //       'pipeline_amount' =>
            //         $program_vendor_configuration->pipeline_amount - $row['amount'],
            //     ]);
            //   }

            //   if ($od_account->payment_account_number == $row['credit_to'] && $cbs_transaction->status == 'Successful') {
            //     // Update Program and Company Pipeline and Utilized Amounts
            //     $payment_request->invoice->company->update([
            //       'utilized_amount' =>
            //         $payment_request->invoice->company->utilized_amount - $row['amount'],
            //     ]);

            //     $payment_request->invoice->program->update([
            //       'utilized_amount' =>
            //         $payment_request->invoice->program->utilized_amount - $row['amount'],
            //     ]);

            //     $program_vendor_configuration = ProgramVendorConfiguration::where(
            //       'company_id',
            //       $payment_request->invoice->company_id
            //     )
            //       ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
            //         $query->where('buyer_id', $payment_request->invoice->buyer_id);
            //       })
            //       ->where('program_id', $payment_request->invoice->program_id)
            //       ->first();

            //     $program_vendor_configuration->update([
            //       'utilized_amount' =>
            //         $program_vendor_configuration->utilized_amount - $row['amount'],
            //     ]);
            //   }
            // }

            $previous_pr_id = $row['pr_id'];
            $latest_pr_id = PaymentRequest::orderBy('pr_id', 'DESC')->first()?->pr_id;
          }
        }
      }
    }
  }
}
