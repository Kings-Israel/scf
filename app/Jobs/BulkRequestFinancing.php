<?php

namespace App\Jobs;

use App\Http\Resources\InvoiceResource;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceProcessing;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkRequestFinancing implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $type;
  public $company;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Company $company = null, $type = '')
  {
    $this->company = $company;
    $this->type = $type;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $payment_requests = [];
    InvoiceProcessing::where('status', 'pending')
      ->where('action', 'requesting financing')
      ->whereDate('created_at', now())
      ->where('company_id', $this->company->id)
      ->whereHas('invoice', function ($query) {
        $query->orderBy('due_date', 'ASC');
      })
      ->chunk(50, function ($processing_invoices) use (&$payment_requests) {
        foreach ($processing_invoices as $processing_invoice) {
          $invoice = Invoice::find($processing_invoice->invoice_id);

          if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
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
            if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
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

          if (isset($response['status']) && $response['status'] === 'success') {
            array_push($payment_requests, $response['payment_request_id']);
          }
        }
      });

    if (count($payment_requests) > 0) {
      foreach ($this->company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'BulkFinanceRequest', [
          'financing_requests' => $payment_requests,
        ]);
      }

      foreach ($this->company->bank->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'BulkFinanceRequest', [
          'financing_requests' => $payment_requests,
        ]);
      }
    }
  }
}
