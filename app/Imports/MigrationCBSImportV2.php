<?php

namespace App\Imports;

use App\Helpers\Helpers;
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

class MigrationCBSImportV2 implements ToCollection, WithHeadingRow, WithMapping
{
  public function map($row): array
  {
    return [
      'cbs_id' => $row['cbs_id'],
      'pr_id' => $row['pr_id'],
      'debit_from' => $row['debit_from'],
      'credit_to' => $row['credit_to'],
      // 'ifsc_code' => $row['ifsc_code'],
      'amount' => (float) str_replace(
        ',',
        '',
        str_replace('-', '', str_replace('(', '', str_replace(')', '', $row['amount_ksh'])))
      ),
      'invoice_number' => $row['invoice_unique_reference_no'],
      // 'vendor' => $row['seller'],
      'pay_date' =>
        $row['pay_date'] != null && $row['pay_date'] != '' ? Helpers::importParseDate($row['pay_date']) : null,
      'transaction_date' =>
        $row['transaction_date'] != null && $row['transaction_date'] != ''
          ? Helpers::importParseDate($row['transaction_date'])
          : null,
      'transaction_reference_number' => $row['reference_no'],
      'transaction_type' => $row['transaction_type'],
      'product' => $row['product_type'],
      'payment_service' => $row['payment_service'],
      'status' => $row['status'],
      'created_by' => $row['created_by'],
      'created_at' => Helpers::importParseDate($row['created_at'], 'Y-m-d H:i:s'),
      'updated_at' => Helpers::importParseDate($row['last_updated_at'], 'Y-m-d H:i:s'),
    ];
  }

  /**
   * @param Collection $collection
   */
  public function collection(Collection $collection)
  {
    $latest_id = 43519;
    $latest_cbs_id = 132707;

    // $existsing_payment_requests = PaymentRequest::where('pr_id', '>=', $latest_id)->get();
    // foreach ($existsing_payment_requests as $existsing_payment_request) {
    //   $existsing_payment_request->update([
    //     'pr_id' => $latest_id + 50,
    //   ]);
    //   $latest_id += 1;
    // }

    $existsing_cbs_transactions = CbsTransaction::where('id', '>=', $latest_cbs_id)->get();
    foreach ($existsing_cbs_transactions as $existsing_cbs_transaction) {
      $existsing_cbs_transaction->update([
        'id' => $latest_cbs_id + 50,
      ]);
      $latest_cbs_id += 1;
    }

    $latest_id = 43519;
    $latest_cbs_id = 132707;

    // foreach ($collection as $key => $row) {
    //   // Find the respective invoice
    //   $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();

    //   if ($invoice) {
    //     $program = $invoice->program;
    //     $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
    //       ->where('program_id', $invoice->program_id)
    //       ->first();

    //     $vendor_bank_account = ProgramVendorBankDetail::where('company_id', $invoice->company_id)
    //       ->where('program_id', $invoice->program_id)
    //       ->first();
    //     $anchor_bank_account = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
    //     $transaction_date = $row['transaction_date'];
    //     $pay_date = $row['pay_date'];

    //     $transaction_type = $row['transaction_type'];

    //     if ($transaction_type === 'Loan Disbursement') {
    //       $transaction_type = CbsTransaction::PAYMENT_DISBURSEMENT;
    //     }

    //     if ($program && $program_vendor_configuration && $vendor_bank_account && $anchor_bank_account) {
    //       $cbs_transaction = CbsTransaction::create([
    //         // 'id' => $latest_cbs,
    //         'id' => $latest_cbs_id,
    //         'bank_id' => $invoice->program->bank_id,
    //         'debit_from_account' => $row['debit_from'],
    //         'credit_to_account' => $row['credit_to'],
    //         'amount' => (float) $row['amount'],
    //         'transaction_created_date' => Carbon::parse($row['created_at'])->format('Y-m-d'),
    //         'transaction_date' => $transaction_date,
    //         'pay_date' => $pay_date,
    //         'transaction_reference' => $row['transaction_reference_number'],
    //         'status' => $row['status'],
    //         'transaction_type' =>
    //           $transaction_type === 'Accrual/Posted Interest' ? CbsTransaction::FEES_CHARGES : $transaction_type,
    //         'product' => $row['product'],
    //         'created_at' => $row['created_at'],
    //         'updated_at' => $row['updated_at'],
    //       ]);

    //       $payment_request_status = 'created';
    //       $payment_request_approval_status = 'pending_maker';

    //       if ($row['status'] === 'Successful') {
    //         $payment_request_status = 'paid';
    //         $payment_request_approval_status = 'paid';
    //       } elseif ($row['status'] === 'Created') {
    //         $payment_request_status = 'approved';
    //         $payment_request_approval_status = 'approved';
    //       } elseif ($row['status'] === 'Failed' || $row['status'] === 'Permanently Failed') {
    //         $payment_request_status = 'denied';
    //         $payment_request_approval_status = 'denied';
    //       }

    //       $reference_number = '';

    //       $reference_number =
    //         'FWR0' . $program->bank_id . '' . Carbon::parse($invoice->invoice_date)->format('y') . '0000' . $latest_id;

    //       $payment_request = PaymentRequest::where('invoice_id', $invoice->id)->first();

    //       if (!$payment_request) {
    //         // Create the disbursement payment request
    //         $payment_request = PaymentRequest::create([
    //           'pr_id' => $latest_id,
    //           'reference_number' => $reference_number,
    //           'invoice_id' => $invoice->id,
    //           'amount' => $row['amount'],
    //           // 'processing_fee' => round($fees_amount, 2),
    //           'payment_request_date' => $pay_date,
    //           'status' => $payment_request_status,
    //           'approval_status' => $payment_request_approval_status,
    //           // 'anchor_discount_bearing' => $discount,
    //           // 'vendor_discount_bearing' => $anchor_bearing_discount_value,
    //           // 'created_by' => auth()->id(),
    //           'created_at' => $row['created_at'],
    //           'updated_at' => $row['updated_at'],
    //         ]);
    //       }

    //       switch ($transaction_type) {
    //         case 'Payment Disbursement':
    //           $cbs_transaction->update([
    //             // 'transaction_type' => 'Payment Disbursement',
    //             'payment_request_id' => $payment_request->id,
    //             'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
    //             'credit_to_account_description' => $invoice->company->name . '(' . $row['credit_to'] . ')',
    //           ]);

    //           // Create Payment Account
    //           $payment_request->paymentAccounts()->create([
    //             'account' => $row['credit_to'],
    //             'amount' => $row['amount'],
    //             'type' => 'vendor_account',
    //             'description' => 'vendor account',
    //           ]);

    //           if ($row['status'] == 'Successful') {
    //             $payment_request->invoice->update([
    //               'disbursed_amount' => $row['amount'],
    //               'disbursement_date' => $transaction_date,
    //               'payment_date' => $transaction_date,
    //               'status' => 'disbursed',
    //               'financing_status' => 'financed',
    //             ]);
    //           }

    //           if ($row['status'] == 'Created') {
    //             $payment_request->invoice->update([
    //               'disbursed_amount' => 0,
    //               'disbursement_date' => null,
    //               'payment_date' => null,
    //               'status' => 'approved',
    //               'stage' => 'approved',
    //               'financing_status' => 'submitted',
    //             ]);
    //           }
    //           break;
    //         case 'Accrual/Posted Interest':
    //           $cbs_transaction->update([
    //             'payment_request_id' => $payment_request->id,
    //             'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
    //             'credit_to_account_description' => 'Discount Charge (Bank: ' . $row['credit_to'] . ')',
    //           ]);

    //           $payment_request->paymentAccounts()->create([
    //             'account' => $row['credit_to'],
    //             'amount' => $row['amount'],
    //             'type' => 'discount',
    //             'description' => Invoice::VENDOR_FEE_BEARING,
    //             'created_at' => $transaction_date,
    //             'updated_at' => $transaction_date,
    //           ]);
    //           break;
    //         case 'Fees / Charges':
    //           $cbs_transaction->update([
    //             'payment_request_id' => $payment_request->id,
    //             'transaction_type' => 'Fees/Charges',
    //             'debit_from_account_description' => $row['debit_from'] . ' (Bank)',
    //             'credit_to_account_description' => 'Charges (Bank: ' . $row['credit_to'] . ')',
    //           ]);

    //           $payment_request->paymentAccounts()->create([
    //             'account' => $row['credit_to'],
    //             'amount' => $row['amount'],
    //             'type' => 'program_fees',
    //             'description' => Invoice::VENDOR_FEE_BEARING,
    //             'created_at' => $transaction_date,
    //             'updated_at' => $transaction_date,
    //           ]);
    //           break;
    //         // case 'Bank Invoice Payment':
    //         //   $account_description = '';
    //         //   if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
    //         //     $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
    //         //   } else {
    //         //     if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
    //         //       $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
    //         //     } else {
    //         //       $account_description = $invoice->buyer->name . ' (' . $row['debit_from'] . ')';
    //         //     }
    //         //   }

    //         //   $cbs_transaction->update([
    //         //     'payment_request_id' => $payment_request->id,
    //         //     'credit_to_account_description' =>
    //         //       $invoice->program->programType->name == Program::VENDOR_FINANCING
    //         //         ? 'Bank Invoice Payment (Bank: ' . $row['credit_to'] . ')'
    //         //         : 'Repayment (Bank: ' . $row['credit_to'] . ')',
    //         //     'debit_from_account_description' => $account_description,
    //         //   ]);

    //         //   $payment_request->paymentAccounts()->create([
    //         //     'account' => $row['credit_to'],
    //         //     'amount' => $row['amount'],
    //         //     'type' => 'principle_repayment',
    //         //     'description' =>
    //         //       $invoice->program->programType->name == Program::VENDOR_FINANCING
    //         //         ? 'Bank Invoice Payment'
    //         //         : 'Repayment',
    //         //     'created_at' => $transaction_date,
    //         //     'updated_at' => $transaction_date,
    //         //   ]);

    //         //   if ($row['status'] == 'Successful') {
    //         //     Payment::create([
    //         //       'invoice_id' => $invoice->id,
    //         //       'amount' => $row['amount'],
    //         //       'created_at' => $transaction_date,
    //         //       'updated_at' => $transaction_date,
    //         //     ]);
    //         //   }
    //         //   break;
    //         // case 'Bank invoice payment':
    //         //   $account_description = '';
    //         //   if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
    //         //     $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
    //         //   } else {
    //         //     if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
    //         //       $account_description = $invoice->program->anchor->name . ' (' . $row['debit_from'] . ')';
    //         //     } else {
    //         //       $account_description = $invoice->buyer->name . ' (' . $row['debit_from'] . ')';
    //         //     }
    //         //   }

    //         //   $cbs_transaction->update([
    //         //     'payment_request_id' => $payment_request->id,
    //         //     'credit_to_account_description' =>
    //         //       $invoice->program->programType->name == Program::VENDOR_FINANCING
    //         //         ? 'Bank Invoice Payment (Bank: ' . $row['credit_to'] . ')'
    //         //         : 'Repayment (Bank: ' . $row['credit_to'] . ')',
    //         //     'debit_from_account_description' => $account_description,
    //         //   ]);

    //         //   $payment_request->paymentAccounts()->create([
    //         //     'account' => $row['credit_to'],
    //         //     'amount' => $row['amount'],
    //         //     'type' => 'principle_repayment',
    //         //     'description' =>
    //         //       $invoice->program->programType->name == Program::VENDOR_FINANCING
    //         //         ? 'Bank Invoice Payment'
    //         //         : 'Repayment',
    //         //     'created_at' => $transaction_date,
    //         //     'updated_at' => $transaction_date,
    //         //   ]);

    //         //   if ($row['status'] == 'Successful') {
    //         //     Payment::create([
    //         //       'invoice_id' => $invoice->id,
    //         //       'amount' => $row['amount'],
    //         //       'created_at' => $transaction_date,
    //         //       'updated_at' => $transaction_date,
    //         //     ]);
    //         //   }
    //         //   break;
    //         default:
    //           # code...
    //           break;
    //       }

    //       // $sum = CbsTransaction::whereHas('paymentRequest', function ($q) use ($invoice) {
    //       //   $q->where('invoice_id', $invoice->id);
    //       // })
    //       //   ->whereIn('transaction_type', ['Payment Disbursement', 'Accrual/Posted Interest', 'Fees/Charges'])
    //       //   ->sum('amount');

    //       // $invoice->update([
    //       //   'total_amount' => $sum,
    //       //   'eligibility' => 100,
    //       // ]);

    //       // $od_account = ProgramVendorConfiguration::where('payment_account_number', $row['debit_from'])->first();

    //       // if ($od_account) {
    //       //   if ($od_account->payment_account_number == $row['debit_from'] && $row['status'] == 'Created') {
    //       //     if ($row['transaction_type'] != 'Bank Invoice Payment' && $row['transaction_type'] != 'Repayment') {
    //       //       // Update Program and Company Pipeline and Utilized Amounts
    //       //       $payment_request->invoice->company->update([
    //       //         'utilized_amount' => ($payment_request->invoice->company->utilized_amount += $row['amount']),
    //       //       ]);

    //       //       $payment_request->invoice->program->update([
    //       //         'utilized_amount' => ($payment_request->invoice->program->utilized_amount += $row['amount']),
    //       //       ]);

    //       //       $program_vendor_configuration = ProgramVendorConfiguration::where(
    //       //         'company_id',
    //       //         $payment_request->invoice->company_id
    //       //       )
    //       //         ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
    //       //           $query->where('buyer_id', $payment_request->invoice->buyer_id);
    //       //         })
    //       //         ->where('program_id', $payment_request->invoice->program_id)
    //       //         ->first();

    //       //       $program_vendor_configuration->update([
    //       //         'utilized_amount' => ($program_vendor_configuration->utilized_amount += $row['amount']),
    //       //       ]);
    //       //     }
    //       //   }

    //       //   if ($od_account->payment_account_number == $row['debit_from'] && $row['status'] == 'Successful') {
    //       //     if ($row['transaction_type'] != 'Bank Invoice Payment' && $row['transaction_type'] != 'Repayment') {
    //       //       // Update Program and Company Pipeline and Utilized Amounts
    //       //       $payment_request->invoice->company->update([
    //       //         'utilized_amount' => ($payment_request->invoice->company->utilized_amount += $row['amount']),
    //       //       ]);

    //       //       $payment_request->invoice->program->update([
    //       //         'utilized_amount' => ($payment_request->invoice->program->utilized_amount += $row['amount']),
    //       //       ]);

    //       //       $program_vendor_configuration = ProgramVendorConfiguration::where(
    //       //         'company_id',
    //       //         $payment_request->invoice->company_id
    //       //       )
    //       //         ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
    //       //           $query->where('buyer_id', $payment_request->invoice->buyer_id);
    //       //         })
    //       //         ->where('program_id', $payment_request->invoice->program_id)
    //       //         ->first();

    //       //       $program_vendor_configuration->update([
    //       //         'utilized_amount' => ($program_vendor_configuration->utilized_amount += $row['amount']),
    //       //       ]);
    //       //     }
    //       //   }
    //       // }

    //       // $od_account = ProgramVendorConfiguration::where('payment_account_number', $row['credit_to'])->first();

    //       // if (
    //       //   $od_account &&
    //       //   $od_account->payment_account_number == $row['credit_to'] &&
    //       //   $row['status'] == 'Successful'
    //       // ) {
    //       //   if ($row['transaction_type'] != 'Bank Invoice Payment' && $row['transaction_type'] != 'Repayment') {
    //       //     // Update Program and Company Pipeline and Utilized Amounts
    //       //     $payment_request->invoice->company->update([
    //       //       'utilized_amount' => ($payment_request->invoice->company->utilized_amount -= $row['amount']),
    //       //     ]);

    //       //     $payment_request->invoice->program->update([
    //       //       'utilized_amount' => ($payment_request->invoice->program->utilized_amount -= $row['amount']),
    //       //     ]);

    //       //     $program_vendor_configuration = ProgramVendorConfiguration::where(
    //       //       'company_id',
    //       //       $payment_request->invoice->company_id
    //       //     )
    //       //       ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
    //       //         $query->where('buyer_id', $payment_request->invoice->buyer_id);
    //       //       })
    //       //       ->where('program_id', $payment_request->invoice->program_id)
    //       //       ->first();

    //       //     $program_vendor_configuration->update([
    //       //       'utilized_amount' => ($program_vendor_configuration->utilized_amount -= $row['amount']),
    //       //     ]);
    //       //   }
    //       // }

    //       $latest_id += 1;
    //       $latest_cbs_id += 1;
    //     }
    //   }
    // }
  }

  private function fixDateTime($date)
  {
    $year = explode('-', $date)[0];
    $month = explode('-', $date)[1];
    $day = explode('-', explode(' ', $date)[0])[2];

    return $year . '-' . $day . '-' . $month . ' ' . explode(' ', $date)[1];
  }
}
