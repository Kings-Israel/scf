<?php

namespace App\Jobs;

use App\Models\BankGeneralProductConfiguration;
use App\Models\BankProductsConfiguration;
use App\Models\BankTaxRate;
use App\Models\Company;
use App\Models\FinanceRequestApproval;
use App\Models\Invoice;
use App\Models\InvoiceProcessing;
use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCode;
use App\Models\ProgramDiscount;
use App\Models\ProgramType;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkInvoiceProcessing implements ShouldQueue
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
    $payment_requests = [];
    $processing_invoices = InvoiceProcessing::where('status', 'pending')
      ->where('action', 'requesting financing')
      ->whereDate('created_at', now())
      ->whereHas('invoice', function ($query) {
        $query->orderBy('due_date', 'ASC');
      })
      ->get();

    foreach ($processing_invoices as $processing_invoice) {
      // $company = Company::find($company_id);

      $invoice = Invoice::find($processing_invoice->invoice_id);

      if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
        if (isset($processing_invoice->data['credit_to'])) {
          $bank_details = ProgramBankDetails::find($processing_invoice->data['credit_to']);
        } else {
          $bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
        }

        if (!$bank_details) {
          $bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
        }

        $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
          ->where('program_id', $invoice->program_id)
          ->first();

        $response = $invoice->requestDealerFinance(
          $vendor_configurations,
          $processing_invoice->data['drawdown_amount'],
          $bank_details,
          $processing_invoice->data['payment_request_date'],
          $processing_invoice->data['due_date']
        );
      } else {
        if ($invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
          // If processing invoice has credit to set, use that
          if (isset($processing_invoice->data['credit_to'])) {
            $bank_details = ProgramVendorBankDetail::find($processing_invoice->data['credit_to']);
          } else {
            $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)
              ->where('program_id', $invoice->program_id)
              ->first();
          }

          if (!$bank_details) {
            $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)
              ->where('program_id', $invoice->program_id)
              ->first();
          }

          $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
            ->where('program_id', $invoice->program_id)
            ->first();

          $response = $invoice->requestFinance(
            $vendor_configurations,
            $bank_details->id,
            $processing_invoice->data['payment_request_date']
          );
        } else {
          if (isset($processing_invoice->data['credit_to'])) {
            $bank_details = ProgramBankDetails::find($processing_invoice->data['credit_to']);
          } else {
            $bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
          }

          if (!$bank_details) {
            $bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();
          }

          $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
            ->where('program_id', $invoice->program_id)
            ->where('buyer_id', $invoice->buyer_id)
            ->first();

          $response = $invoice->requestFinance(
            $vendor_configurations,
            $bank_details->id,
            $processing_invoice->data['payment_request_date']
          );
        }
      }

      if (
        $response &&
        isset($response['status']) &&
        $response['status'] === 'success' &&
        isset($response['payment_request_id'])
      ) {
        array_push($payment_requests, $response['payment_request_id']);
      }
      // foreach ($processings as $processing_invoice) {
      // }

      // if (count($payment_requests) > 0) {
      //   foreach ($company->users as $user) {
      //     SendMail::dispatchAfterResponse($user->email, 'BulkFinanceRequest', ['financing_requests' => $payment_requests]);
      //   }

      //   foreach ($company->bank->users as $user) {
      //     SendMail::dispatchAfterResponse($user->email, 'BulkFinanceRequest', ['financing_requests' => $payment_requests]);
      //   }
      // }
    }
  }
}
