<?php

use App\Helpers\Helpers;
use App\Http\Controllers\ProgramsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompaniesController;
use App\Http\Controllers\RequestsController;
use App\Http\Resources\InvoiceDetailsResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Imports\CBSCorrections;
use App\Imports\MigrationCBSImport;
use App\Imports\MigrationCBSImportV2;
use App\Imports\MigrationFactoringCBSImport;
use App\Imports\MigrationFactoringInvoicesImport;
use App\Imports\MigrationInvoicesImport;
use App\Jobs\BulkRequestFinancing;
use App\Models\Bank;
use App\Models\BankDocument;
use App\Models\BankHoliday;
use App\Models\BankProductsConfiguration;
use App\Models\CbsTransaction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceApproval;
use App\Models\InvoiceProcessing;
use App\Models\InvoiceTax;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use App\Models\ProposedConfigurationChange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/banks', function () {
  $banks = Bank::with('requiredDocuments')->get();

  return response()->json(['data' => $banks], 200);
});

Route::get('/banks/{id}/documents', function ($id) {
  $documents = BankDocument::where('bank_id', $id)->get();

  return response()->json(['data' => $documents], 200);
});

// Migration Import for VFR Invoices
Route::post('/migration/invoices', function (Request $request) {
  ini_set('max_execution_time', 10000);
  try {
    $import = new MigrationInvoicesImport();

    Excel::import($import, $request->file('invoices')->store('public'));

    return response()->json(['Invoices Imported'], 200);
  } catch (\Throwable $th) {
    //throw $th;
    info($th);
    return response()->json(['error' => $th], 500);
  }
});

// Migration Import for VFR CBS Transactions
Route::post('/migration/cbs-transactions', function (Request $request) {
  ini_set('max_execution_time', 10000);
  try {
    $import = new MigrationCBSImport();

    Excel::import($import, $request->file('cbs')->store('public'));

    return response()->json(['CBS Transactions Imported'], 200);
  } catch (\Throwable $th) {
    //throw $th;
    info($th);
    return response()->json(['error' => $th], 500);
  }
});

// Migration Import for Factoring Invoices
Route::post('/migration/factoring/invoices', function (Request $request) {
  ini_set('max_execution_time', 10000);
  try {
    $import = new MigrationFactoringInvoicesImport();

    Excel::import($import, $request->file('invoices')->store('public'));

    return response()->json(['Invoices Imported']);
  } catch (\Throwable $th) {
    //throw $th;
    info($th);
    return response()->json(['error' => $th], 500);
  }
});

// CBS Transactions Import for Factoring
Route::post('/migration/factoring/cbs-transactions', function (Request $request) {
  ini_set('max_execution_time', 10000);
  try {
    $import = new MigrationFactoringCBSImport();

    Excel::import($import, $request->file('cbs')->store('public'));

    return response()->json(['CBS Transactions Import successfully']);
  } catch (\Throwable $th) {
    //throw $th;
    info($th);
    return response()->json(['error' => $th], 500);
  }
});

// CBS Transactions Import for Factoring
Route::post('/migration/cbs-transactions-v2', function (Request $request) {
  ini_set('max_execution_time', 10000);
  try {
    $import = new MigrationCBSImportV2();

    Excel::import($import, $request->file('cbs')->store('public'));

    return response()->json(['CBS Transactions Import successfully']);
  } catch (\Throwable $th) {
    //throw $th;
    info($th);
    return response()->json(['error' => $th], 500);
  }
});

Route::post('/migration/cbs-transactions-correct', function (Request $request) {
  ini_set('max_execution_time', 10000);
  try {
    $import = new CBSCorrections();

    Excel::import($import, $request->file('cbs')->store('public'));

    return response()->json(['CBS Transactions Import successfully']);
  } catch (\Throwable $th) {
    //throw $th;
    info($th);
    return response()->json(['error' => $th], 500);
  }
});

Route::post('/invoice/delete/payment-request', function (Request $request) {
  $invoice_number = $request->input('invoice_number');

  $invoice = Invoice::where('invoice_number', $invoice_number)->first();

  $invoice->update([
    // 'due_date' => '2025-09-27',
    'eligible_for_financing' => true,
    'financing_status' => 'pending',
  ]);

  PaymentRequest::where('invoice_id', $invoice->id)->delete();
  // $payment_request->approvals()->delete();
  // CbsTransaction::where('payment_request_id', $payment_request->id)->delete();
  InvoiceProcessing::where('invoice_id', $invoice->id)->delete();

  // $invoice->program->decrement(
  //   'utilized_amount',
  //   $invoice->program->programType->name == Program::DEALER_FINANCING
  //     ? $invoice->drawdown_amount
  //     : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
  // );

  // $invoice->program->increment(
  //   'pipeline_amount',
  //   $invoice->program->programType->name == Program::DEALER_FINANCING
  //     ? $invoice->drawdown_amount
  //     : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
  // );

  // $invoice->company->decrement(
  //   'utilized_amount',
  //   $invoice->program->programType->name == Program::DEALER_FINANCING
  //     ? $invoice->drawdown_amount
  //     : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
  // );

  // $invoice->company->increment(
  //   'pipeline_amount',
  //   $invoice->program->programType->name == Program::DEALER_FINANCING
  //     ? $invoice->drawdown_amount
  //     : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
  // );

  // $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
  //   ->when($invoice->buyer_id, function ($query) use ($invoice) {
  //     $query->where('buyer_id', $invoice->buyer_id);
  //   })
  //   ->where('program_id', $invoice->program_id)
  //   ->first();

  // $program_vendor_configuration->decrement(
  //   'utilized_amount',
  //   $invoice->program->programType->name == Program::DEALER_FINANCING
  //     ? $invoice->drawdown_amount
  //     : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
  // );

  // $program_vendor_configuration->increment(
  //   'pipeline_amount',
  //   $invoice->program->programType->name == Program::DEALER_FINANCING
  //     ? $invoice->drawdown_amount
  //     : ($invoice->eligibility / 100) * $invoice->invoice_total_amount
  // );

  // $invoice->update([
  //   'financing_status' => 'submitted',
  // ]);

  // $payment_request->update([
  //   'status' => 'created',
  //   'approval_status' => 'pending_maker',
  // ]);

  return response()->json(['deleted']);
});

Route::get('/cbs-transactions/type/update', function () {
  $cbs_transactions = CbsTransaction::where('transaction_type', 'Accrual/Posted Interest')->get();

  foreach ($cbs_transactions as $cbs_transaction) {
    $cbs_transaction->update([
      'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
    ]);
  }
});

Route::get('/invoices/status/update', function () {
  // $invoices = Invoice::where('status', 'submitted')
  //   ->where('stage', 'pending_maker')
  //   ->whereDate('due_date', '>=', now()->format('Y-m-d'))
  //   ->get();

  // foreach ($invoices as $invoice) {
  //   $company_user = $invoice->company->users->first();

  //   if ($company_user) {
  //     $invoice->approvals()->create([
  //       'user_id' => $company_user->id,
  //     ]);

  //     $invoice->update([
  //       'stage' => 'pending_checker',
  //     ]);
  //   }
  // }

  // $invoices = Invoice::where('status', 'submitted')
  //   ->where('stage', 'pending_checker')
  //   ->whereDate('due_date', '>=', now()->format('Y-m-d'))
  //   ->get();

  // foreach ($invoices as $invoice) {
  //   if ($invoice->program->programCode?->name === Program::FACTORING_WITHOUT_RECOURSE) {
  //     $company_user = $invoice->company->users->first();
  //   }
  // }

  return response()->json('Approval Added');
});

Route::get('/invoice/disbursement_date', function () {
  $invoices = Invoice::whereIn('buyer_id', [95, 98])
    ->whereIn('financing_status', ['financed', 'closed'])
    ->get();

  foreach ($invoices as $invoice) {
    $invoice->update([
      'disbursement_date' => $invoice->invoice_date,
    ]);

    $created_at = explode(' ', $invoice->created_at)[1];

    $invoice->update([
      'created_at' => $invoice->invoice_date . ' ' . $created_at,
    ]);
  }
  // // Disbursement Date
  // $invoices = Invoice::whereIn('financing_status', ['financed', 'closed'])
  //   ->whereDate('disbursement_date', '>', now()->format('Y-m-d'))
  //   ->get();

  // foreach ($invoices as $invoice) {
  //   $year = explode('-', $invoice->disbursement_date)[0];
  //   $month = explode('-', $invoice->disbursement_date)[1];
  //   $day = explode('-', $invoice->disbursement_date)[2];

  //   $invoice->update([
  //     'disbursement_date' => $year . '-' . $day . '-' . $month,
  //   ]);
  // }

  // // Created Date
  // $invoices = Invoice::whereDate('created_at', '>', now())->get();

  // foreach ($invoices as $invoice) {
  //   $time = explode(' ', $invoice->created_at)[1];
  //   $year = explode('-', $invoice->created_at)[0];
  //   $month = explode('-', $invoice->created_at)[1];
  //   $day = explode(' ', explode('-', $invoice->created_at)[2])[0];

  //   $invoice->update([
  //     'created_at' => $year . '-' . $day . '-' . $month . ' ' . $time,
  //   ]);
  // }

  return response()->json(['Updated']);
});

Route::get('/invoice/{invoice_number}/overdue-amount', function ($invoice_number) {
  $invoice = Invoice::where('invoice_number', $invoice_number)->first();

  return response()->json([
    'Total Amount' => $invoice->invoice_total_amount,
    'Balance' => $invoice->balance,
    'Paid Amount' => $invoice->paid_amount,
    'Overdue Amount' => $invoice->overdue_amount,
    'Overdue' => $invoice->overdue,
  ]);
});

// Close Invoices that have payments
Route::get('/invoices/overdue/paid', function () {
  $invoices = Invoice::where('financing_status', 'financed')
    ->whereHas('payments')
    ->get();

  foreach ($invoices as $invoice) {
    $invoice->update([
      'financing_status' => 'closed',
      'stage' => 'closed',
    ]);
  }

  return response()->json($invoices);
});

// Updated Calculated Total Amount to be the same as invoice total amount
Route::get('/invoices/calculated_amount', function () {
  $invoices = Invoice::where('financing_status', ['financed', 'closed'])->get();

  foreach ($invoices as $invoice) {
    $invoice->update([
      'calculated_total_amount' => $invoice->total_amount,
    ]);
  }

  return response()->json(['message' => 'Updated']);
});

Route::post('/invoice/disbursement/fix', function (Request $request) {
  $invoice_number = $request->input('invoice_number');
  $disbursement_transaction_reference = $request->input('disbursement_transaction_reference');
  $disbursement_date = $request->input('disbursement_date');

  $invoice = Invoice::where('invoice_number', $invoice_number)->first();

  $cbs_transactions = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
    $query->where('invoice_id', $invoice->id);
  })->get();

  foreach ($cbs_transactions as $cbs_transaction) {
    $cbs_transaction->update([
      'transaction_reference' => $disbursement_transaction_reference,
      'status' => 'Successful',
    ]);

    $cbs_transaction->paymentRequest->update([
      'status' => 'paid',
      'approval_status' => 'paid',
    ]);

    $requested_amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) use ($cbs_transaction) {
      $q->where('invoice_id', $cbs_transaction->paymentRequest->invoice_id);
    })
      ->where('type', 'vendor_account')
      ->first()->amount;

    $cbs_transaction->paymentRequest->invoice->update([
      'disbursement_date' => $disbursement_date,
      'disbursed_amount' => round($requested_amount, 2),
      'status' => 'disbursed',
      'financing_status' => 'financed',
      'stage' => 'disbursed',
    ]);
  }

  // If invoice as past due, create the repayment transaction
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
    if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
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

  if (!$invoice->eligibility) {
    $invoice->update(['eligibility' => 100]);
  }

  if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
    if ($invoice->discount_type == Invoice::FRONT_ENDED) {
      $amount_repayable = ($invoice->eligibility / 100) * $invoice->invoice_total_amount - $invoice->paid_amount;
    } else {
      $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
    }
  } else {
    // User drawdown amount for dealer financing
    if ($invoice->discount_type == Invoice::FRONT_ENDED) {
      $amount_repayable = ($invoice->eligibility / 100) * $invoice->drawdown_amount - $invoice->paid_amount;
    } else {
      $amount_repayable = $invoice->disbursed_amount - $invoice->paid_amount;
    }
  }

  if (Carbon::parse($invoice->due_date)->equalTo(now()->format('Y-m-d'))) {
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
      'account_name' => $invoice->program_name,
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
      'credit_to_account_name' => $invoice->program_name,
      'credit_to_account_description' =>
        $invoice->program->programType->name === Program::VENDOR_FINANCING
          ? CbsTransaction::BANK_INVOICE_PAYMENT . ' (Bank: ' . $od_account . ')'
          : CbsTransaction::REPAYMENT . ' (Bank: ' . $od_account . ')',
      'amount' => $amount_repayable,
      'transaction_created_date' => $invoice->due_date,
      'pay_date' => $invoice->due_date,
      'status' => 'Created',
      'transaction_type' =>
        $invoice->program->programType->name === Program::VENDOR_FINANCING
          ? CbsTransaction::BANK_INVOICE_PAYMENT
          : CbsTransaction::REPAYMENT,
      'product' =>
        $invoice->program->programType->name === Program::VENDOR_FINANCING
          ? Program::VENDOR_FINANCING
          : Program::DEALER_FINANCING,
    ]);

    // If Eligibility is less than 100% create transaction to credit the vendors account
    if (round($invoice->eligibility) < 100) {
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
  }

  if (Carbon::parse($invoice->due_date)->lessThan(now()->format('Y-m-d'))) {
    // Update the Payment Requests IDs, add one then place the payment request for repayment in that ID
    $payment_requests = PaymentRequest::whereDate('created_at', '>=', Carbon::parse($invoice->due_date))
      ->orderBy('pr_id', 'ASC')
      ->get();

    $_pr_id = $payment_requests->first()->pr_id;
    foreach ($payment_requests as $_payment_request) {
      $_payment_request->update([
        'pr_id' => $_payment_request->pr_id + 1,
      ]);
    }

    // Discount Front Ended Start
    $payment_request = PaymentRequest::create([
      'pr_id' => $_pr_id,
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
      'account_name' => $invoice->program_name,
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
      'credit_to_account_name' => $invoice->program_name,
      'credit_to_account_description' =>
        $invoice->program->programType->name === Program::VENDOR_FINANCING
          ? CbsTransaction::BANK_INVOICE_PAYMENT . ' (Bank: ' . $od_account . ')'
          : CbsTransaction::REPAYMENT . ' (Bank: ' . $od_account . ')',
      'amount' => $amount_repayable,
      'transaction_created_date' => $invoice->due_date,
      'pay_date' => $invoice->due_date,
      'status' => 'Created',
      'transaction_type' =>
        $invoice->program->programType->name === Program::VENDOR_FINANCING
          ? CbsTransaction::BANK_INVOICE_PAYMENT
          : CbsTransaction::REPAYMENT,
      'product' =>
        $invoice->program->programType->name === Program::VENDOR_FINANCING
          ? Program::VENDOR_FINANCING
          : Program::DEALER_FINANCING,
    ]);

    // If Eligibility is less than 100% create transaction to credit the vendors account
    if (round($invoice->eligibility) < 100) {
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
  }

  return response()->json(['message' => 'Updated transactions'], 200);
});

// Route::get('/cbs-transactions/fix', function () {
//   $cbs_transactions = CbsTransaction::wherebetween('created_at', [
//     '2025-09-05 09:00:00.000000',
//     '2025-09-05 20:00:00.000000',
//   ])->get();

//   foreach ($cbs_transactions as $cbs_transaction) {
//     // Get the Invoice Details
//     $cbs_transaction->paymentRequest?->delete();

//     $cbs_transaction->delete();
//   }

//   return response()->json(['Deleted']);
// });

// // Delete Payment Requests with Repeated Reference Numbers
// Route::get('/payment-requests/fix', function () {
//   $payment_requests = PaymentRequest::select('reference_number')
//     ->groupBy('reference_number')
//     ->havingRaw('COUNT(*) > 1')
//     ->get();

//   foreach ($payment_requests as $payment_request) {
//     $duplicate_payment_requests = PaymentRequest::where('reference_number', $payment_request->reference_number)
//       ->orderBy('id', 'ASC')
//       ->get();

//     foreach ($duplicate_payment_requests as $duplicate_payment_request) {
//       // Set the invoice to be eligible for financing again
//       $duplicate_payment_request->invoice->update([
//         'eligible_for_financing' => true,
//         'financing_status' => 'pending',
//       ]);

//       // Delete Invoice Processing
//       InvoiceProcessing::where('invoice_id', $duplicate_payment_request->invoice_id)->delete();

//       // If it was approved, reduce the utilized amount by the invoice amount
//       if ($duplicate_payment_request->approval_status == 'approved') {
//         $duplicate_payment_request->invoice->program->decrement(
//           'utilized_amount',
//           $duplicate_payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
//             ? $duplicate_payment_request->invoice->drawdown_amount
//             : ($duplicate_payment_request->invoice->eligibility / 100) * $duplicate_payment_request->invoice->invoice_total_amount
//         );

//         $duplicate_payment_request->invoice->company->decrement(
//           'utilized_amount',
//           $duplicate_payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
//             ? $duplicate_payment_request->invoice->drawdown_amount
//             : ($duplicate_payment_request->invoice->eligibility / 100) * $duplicate_payment_request->invoice->invoice_total_amount
//         );

//         $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $duplicate_payment_request->invoice->company_id)
//           ->when($duplicate_payment_request->invoice->buyer_id, function ($query) use ($duplicate_payment_request) {
//             $query->where('buyer_id', $duplicate_payment_request->invoice->buyer_id);
//           })
//           ->where('program_id', $duplicate_payment_request->invoice->program_id)
//           ->first();

//         $program_vendor_configuration?->decrement(
//           'utilized_amount',
//           $duplicate_payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
//             ? $duplicate_payment_request->invoice->drawdown_amount
//             : ($duplicate_payment_request->invoice->eligibility / 100) * $duplicate_payment_request->invoice->invoice_total_amount
//         );
//       }

//       // If it was pending approval, reduce the pipeline amount by the invoice amount
//       if ($duplicate_payment_request->approval_status == 'pending_checker' || $duplicate_payment_request->approval_status == 'pending_maker') {
//         $duplicate_payment_request->invoice->program->decrement(
//           'pipeline_amount',
//           $duplicate_payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
//             ? $duplicate_payment_request->invoice->drawdown_amount
//             : ($duplicate_payment_request->invoice->eligibility / 100) * $duplicate_payment_request->invoice->invoice_total_amount
//         );

//         $duplicate_payment_request->invoice->company->decrement(
//           'pipeline_amount',
//           $duplicate_payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
//             ? $duplicate_payment_request->invoice->drawdown_amount
//             : ($duplicate_payment_request->invoice->eligibility / 100) * $duplicate_payment_request->invoice->invoice_total_amount
//         );

//         $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $duplicate_payment_request->invoice->company_id)
//           ->when($duplicate_payment_request->invoice->buyer_id, function ($query) use ($duplicate_payment_request) {
//             $query->where('buyer_id', $duplicate_payment_request->invoice->buyer_id);
//           })
//           ->where('program_id', $duplicate_payment_request->invoice->program_id)
//           ->first();

//         $program_vendor_configuration?->decrement(
//           'pipeline_amount',
//           $duplicate_payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
//             ? $duplicate_payment_request->invoice->drawdown_amount
//             : ($duplicate_payment_request->invoice->eligibility / 100) * $duplicate_payment_request->invoice->invoice_total_amount
//         );
//       }

//       // Delete CBS Transactions
//       CbsTransaction::where('payment_request_id', $duplicate_payment_request->id)->delete();

//       // Delete Approvals
//       $duplicate_payment_request->approvals()->delete();

//       // Delete Payment Request
//       $duplicate_payment_request->delete();
//     }
//   }

//   return response()->json(['Deleted']);
// });
