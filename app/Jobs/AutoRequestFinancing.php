<?php

namespace App\Jobs;

use App\Models\BankGeneralProductConfiguration;
use App\Models\Invoice;
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

class AutoRequestFinancing implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public array $invoice_ids)
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
    $can_request = true;

    // Get total amount of the requested invoices
    $sum_amount = 0;

    Invoice::whereIn('id', $this->invoice_ids)->chunk(100, function ($invoices) use (&$sum_amount) {
      foreach ($invoices as $invoice) {
        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->select('eligibility')
          ->first();

        $sum_amount += ($vendor_configurations->eligibility / 100) * $invoice->calculated_total_amount;
      }
    });

    // & used in can_request to pass by reference
    // Check if request will exceed vendor program limit
    // Check if request will exceed program limit
    // Check if request will exceed company top level borrower limit
    Invoice::whereIn('id', $this->invoice_ids)->chunk(100, function ($invoices) use (&$can_request, $sum_amount) {
      foreach ($invoices as $invoice) {
        $program = Program::find($invoice->program_id);

        // Check if program is active
        if ($program->account_status == 'suspended') {
          $can_request = false;
        }

        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->first();

        // Check if company can make the request on the program
        if (!$vendor_configurations->is_approved || $vendor_configurations->status == 'inactive') {
          // Notify bank of request to unblock
          foreach ($invoice->company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToUnblock', ['company_id' => $invoice->company->id]);
          }

          $can_request = false;
        }

        $invoice_total_amount = ($vendor_configurations->eligibility / 100) * $invoice->invoice_total_amount;

        // Check limits at OD Level
        $sanctioned_limit = $vendor_configurations->sanctioned_limit;
        $utilized_amount = $vendor_configurations->utilized_amount;
        $pipeline_amount = $vendor_configurations->pipeline_amount;

        $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($invoice->company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $invoice->company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
            ]);
          }
          $can_request = false;
        }

        // Get Retain Limit as set in Bank Configuration
        $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
          ->where('product_type_id', $invoice->program->program_type_id)
          ->where('name', 'retain limit')
          ->first();

        if ($bank_configurations->value > 0) {
          $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
          $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
          $potential_utilization_amount = $utilized_amount + $pipeline_amount + $invoice_total_amount;
          if ($potential_utilization_amount > $remainder) {
            $can_request = false;
          }
        }

        $program = Program::find($invoice->program_id);

        // Check at program level
        $program_limit = $program->program_limit;
        $utilized_amount = $program->utilized_amount;
        $pipeline_amount = $program->pipeline_amount;
        $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($invoice->company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $invoice->company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
            ]);
          }

          $can_request = false;
          return false;
        }
        // Check if request will exceed drawing power
        if ($vendor_configurations->drawing_power > 0) {
          if ($invoice_total_amount > $vendor_configurations->drawing_power) {
            // Notify bank of request to unblock
            foreach ($invoice->company->bank->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
                'company_id' => $invoice->company->id,
                'approved_limit' => $vendor_configurations->sanctioned_limit,
                'current_exposure' => $utilized_amount,
                'pipeline_requests' => $pipeline_amount,
                'available_limit' => $available_limit,
              ]);
            }

            $can_request = false;
          }
        }

        // Check if request exceeds company top level borrower limit
        $top_level_borrower_limit = $invoice->company->top_level_borrower_limit;
        $utilized_amount = $invoice->company->utilized_amount;
        $pipeline_amount = $invoice->company->pipeline_amount;
        $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $sum_amount;

        if ($available_limit <= 0) {
          $can_request = false;
        }
      }
    });

    if (!$can_request) {
      return true; // Exit if any of the checks fail
    }

    $payment_requests = [];
    Invoice::without('company')->whereIn('id', $this->invoice_ids)
      ->chunk(100, function ($processing_invoices) use (&$payment_requests) {
        foreach ($processing_invoices as $invoice) {
          if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
            $bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();

            $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
              ->where('program_id', $invoice->program_id)
              ->first();

            $response = $invoice->requestDealerFinance(
              $vendor_configurations,
              $invoice->drawdown_amount,
              $bank_details,
              now()->format('Y-m-d'),
              $invoice->due_date
            );
          } else {
            if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
              $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)
                ->where('program_id', $invoice->program_id)
                ->first();

              $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                ->where('program_id', $invoice->program_id)
                ->first();

              $response = $invoice->requestFinance(
                $vendor_configurations,
                $bank_details->id,
                now()->format('Y-m-d')
              );
            } else {
              $bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();

              $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                ->where('program_id', $invoice->program_id)
                ->where('buyer_id', $invoice->buyer_id)
                ->first();

              $response = $invoice->requestFinance(
                $vendor_configurations,
                $bank_details->id,
                now()->format('Y-m-d')
              );
            }
          }

          if (isset($response['status']) && $response['status'] == 'success') {
            array_push($payment_requests, $response['payment_request_id']);
          }
        }
      });
  }
}
