<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\FinancingPool;
use App\Enums\PaymentRequestStatus;
use App\Enums\ProgramType;
use App\Enums\ProgramCode;
use App\Exceptions\LimitExceeded; // Import the new exception
use App\Exceptions\NegativePipelineAmount;
use App\Jobs\SendFinancingRequestUpdateMail; // Custom Job for sending mail
use App\Models\ProgramType as ModelsProgramType;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceApprovalService
{
  /**
   * Handles the approval process for a payment request.
   *
   * @param PaymentRequest $paymentRequest The payment request being approved.
   * @param string $status The new status ('approved' or 'rejected').
   * @param string|null $rejectionReason The reason for rejection, if applicable.
   * @return PaymentRequest
   * @throws NegativePipelineAmountException
   * @throws LimitExceededException
   * @throws \Exception
   */
  public function handleApproval(PaymentRequest $paymentRequest, PaymentRequestStatus $newStatus, ?string $rejectionReason = null): PaymentRequest
  {
    // Ensure the payment request is not already approved or rejected
    if ($paymentRequest->status === PaymentRequestStatus::APPROVED || $paymentRequest->status === PaymentRequestStatus::REJECTED) {
      throw new \Exception('Payment request has already been processed.');
    }

    // Load necessary relationships to ensure they are available
    $paymentRequest->load('invoice.program.programType', 'invoice.program.programCode', 'invoice.company', 'invoice.program.bank.generalConfigurations', 'invoice.program.anchor');

    DB::transaction(function () use ($paymentRequest, $newStatus, $rejectionReason) {
      // $oldStatus = $paymentRequest->status; // Capture old status for conditional logic
      $oldStatus = PaymentRequestStatus::from($paymentRequest->status);

      if ($newStatus === PaymentRequestStatus::APPROVED) {
        $this->processApproval($paymentRequest, $oldStatus);
      } elseif ($newStatus === PaymentRequestStatus::REJECTED) {
        $this->processRejection($paymentRequest, $rejectionReason);
      }

      // Update payment request status and approval status
      $paymentRequest->update([
        'status' => $newStatus,
        'approval_status' => $newStatus, // Or 'pending_checker' for first approval
        'rejected_by' => ($newStatus === PaymentRequestStatus::REJECTED) ? auth()->id() : null,
        'rejected_reason' => ($newStatus === PaymentRequestStatus::REJECTED) ? $rejectionReason : null,
        'updated_by' => auth()->id(),
      ]);

      // Update invoice financing status if approved/rejected
      if ($newStatus === PaymentRequestStatus::REJECTED) {
        $paymentRequest->invoice->update(['financing_status' => 'denied', 'rejected_reason' => $rejectionReason]);
      }

      // Create PI number if not exists upon final approval
      if ($newStatus === PaymentRequestStatus::APPROVED && !$paymentRequest->invoice->pi_number) {
        $paymentRequest->invoice->update([
          'pi_number' => 'PI_' . $paymentRequest->invoice->id,
        ]);
      }
    });

    // // Dispatch mails AFTER the transaction commits
    // $this->dispatchApprovalNotifications($paymentRequest, $newStatus);

    return $paymentRequest;
  }

  /**
   * Processes the approval logic for a payment request.
   *
   * @param PaymentRequest $paymentRequest
   * @param PaymentRequestStatus $oldStatus
   * @throws NegativePipelineAmountException
   * @throws LimitExceededException
   */
  protected function processApproval(PaymentRequest $paymentRequest, PaymentRequestStatus $oldStatus): void
  {
    $invoiceAmount = $this->getCalculatedInvoiceAmount($paymentRequest->invoice);

    // Check if this is the final approval (transition from pending_checker to approved)
    // Or if the initial configuration allows direct approval
    $config = $paymentRequest->invoice->program->bank->generalConfigurations
      ->where('product_type_id', $paymentRequest->invoice->program->programType->id)
      ->where('name', 'finance request approval')
      ->first();

    $isFinalApproval = ($oldStatus === PaymentRequestStatus::PENDING_CHECKER);
    $isDirectApprovalAllowed = ($config && $config->value === 'no'); // Assuming 'no' means direct approval by maker

    if ($isFinalApproval || $isDirectApprovalAllowed) {
      // Add approval record
      $paymentRequest->approvals()->create(['user_id' => auth()->id()]);

      // Create CBS Transactions for the payment request (this method should be robust)
      $paymentRequest->createCbsTransactions();

      // Update limits for ProgramVendorConfiguration, Company, Program, and FinancingPool
      $this->updateFinancialLimits($paymentRequest, $invoiceAmount);
    } else {
      // First approval (e.g., pending_maker -> pending_checker)
      // This assumes a 2-step approval process.
      $paymentRequest->approvals()->create(['user_id' => auth()->id()]);
      $paymentRequest->update(['approval_status' => PaymentRequestStatus::PENDING_CHECKER]);
    }
  }

  /**
   * Processes the rejection logic for a payment request.
   *
   * @param PaymentRequest $paymentRequest
   * @param string|null $rejectionReason
   * @throws NegativePipelineAmount
   * @throws LimitExceeded
   */
  protected function processRejection(PaymentRequest $paymentRequest, ?string $rejectionReason): void
  {
    $invoiceAmount = $this->getCalculatedInvoiceAmount($paymentRequest->invoice);

    // When rejecting, only reduce pipeline_amount if it was previously in the pipeline
    // This is crucial to avoid double-counting or incorrect adjustments if it was already utilized
    // We assume that a rejected invoice means it's no longer 'in pipeline' for this specific request.
    $this->adjustPipelineOnRejection($paymentRequest, $invoiceAmount);
  }

  /**
   * Calculates the relevant invoice amount based on program type.
   *
   * @param \App\Models\Invoice $invoice
   * @return float
   */
  protected function getCalculatedInvoiceAmount(\App\Models\Invoice $invoice): float
  {
    if ($invoice->program->programType->name === Program::DEALER_FINANCING) {
      return (float) $invoice->drawdown_amount;
    }
    // For Vendor Financing (Receivable or Factoring)
    return (float) (($invoice->eligibility / 100) * $invoice->invoice_total_amount);
  }

  /**
   * Updates pipeline and utilized amounts for relevant entities.
   *
   * @param PaymentRequest $paymentRequest
   * @param float $amountToAdjust
   * @throws NegativePipelineAmount
   * @throws LimitExceeded
   */
  protected function updateFinancialLimits(PaymentRequest $paymentRequest, float $amountToAdjust): void
  {
    $invoice = $paymentRequest->invoice;

    // Update ProgramVendorConfiguration
    $programVendorConfig = ProgramVendorConfiguration::when($invoice->buyer_id, function ($query) use ($invoice) {
      return $query->where('buyer_id', $invoice->buyer_id);
    })
      ->where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->firstOrFail();

    // Check if the total committed amount (pipeline + utilized) exceeds the sanctioned limit
    if (($programVendorConfig->pipeline_amount + $programVendorConfig->utilized_amount) > $programVendorConfig->sanctioned_limit) {
      throw new LimitExceeded("Program Vendor Configuration total committed amount exceeds sanctioned limit for ID: {$programVendorConfig->id}");
    }
    // Check for negative pipeline amount BEFORE decrementing
    if ($programVendorConfig->pipeline_amount < $amountToAdjust) {
      throw new NegativePipelineAmount("Program Vendor Configuration pipeline amount would go negative for ID: {$programVendorConfig->id}");
    }
    $programVendorConfig->decrement('pipeline_amount', $amountToAdjust);
    $programVendorConfig->increment('utilized_amount', $amountToAdjust);

    // Update Company
    // Check if the total committed amount (pipeline + utilized) exceeds the sanctioned limit
    if (($invoice->company->pipeline_amount + $invoice->company->utilized_amount) > $invoice->company->top_level_borrower_limit) {
      throw new LimitExceeded("Company total committed amount exceeds sanctioned limit for : {$invoice->company->name}");
    }
    if ($invoice->company->pipeline_amount < $amountToAdjust) {
      throw new NegativePipelineAmount("Company pipeline amount would go negative for : {$invoice->company->name}");
    }
    $invoice->company->decrement('pipeline_amount', $amountToAdjust);
    $invoice->company->increment('utilized_amount', $amountToAdjust);

    // Update Program
    // Check if the total committed amount (pipeline + utilized) exceeds the sanctioned limit
    if (($invoice->program->pipeline_amount + $invoice->program->utilized_amount) > $invoice->program->program_limit) {
      throw new LimitExceeded("Program total committed amount exceeds sanctioned limit for: {$invoice->program->name}");
    }
    if ($invoice->program->pipeline_amount < $amountToAdjust) {
      throw new NegativePipelineAmount("Program pipeline amount would go negative for: {$invoice->program->name}");
    }
    $invoice->program->decrement('pipeline_amount', $amountToAdjust);
    $invoice->program->increment('utilized_amount', $amountToAdjust);
  }

  /**
   * Adjusts pipeline amount when a payment request is rejected.
   *
   * @param PaymentRequest $paymentRequest
   * @param float $amountToAdjust
   * @throws NegativePipelineAmount
   * @throws LimitExceeded
   */
  protected function adjustPipelineOnRejection(PaymentRequest $paymentRequest, float $amountToAdjust): void
  {
    $invoice = $paymentRequest->invoice;

    // Only decrease pipeline if the payment request was previously in a 'pending' state
    // and its amount was contributing to the pipeline.
    // This is a simplified assumption. A more robust system might track
    // which specific amount was added to pipeline for this request.
    // For rejection, we assume it was in pipeline and needs to be removed.

    // Update ProgramVendorConfiguration
    $programVendorConfig = ProgramVendorConfiguration::when($invoice->buyer_id, function ($query) use ($invoice) {
      return $query->where('buyer_id', $invoice->buyer_id);
    })
      ->where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->firstOrFail();

    // Check if the total committed amount (pipeline + utilized) exceeds the sanctioned limit
    if (($programVendorConfig->pipeline_amount + $programVendorConfig->utilized_amount) > $programVendorConfig->sanctioned_limit) {
      Log::warning("OD Account total committed amount already exceeds sanctioned limit on rejection: {$programVendorConfig->id}");
      // Decide how to handle this. For now, we'll proceed with decrementing pipeline if possible.
    }
    // Ensure we don't make pipeline negative
    if ($programVendorConfig->pipeline_amount < $amountToAdjust) {
      Log::warning("Attempted to decrement OD Account pipeline amount below zero on rejection for ID: {$programVendorConfig->id}. Setting to zero.");
      $amountToAdjust = $programVendorConfig->pipeline_amount; // Adjust to prevent negative
    }
    $programVendorConfig->decrement('pipeline_amount', $amountToAdjust);

    // Update Company
    if (($invoice->company->pipeline_amount + $invoice->company->utilized_amount) > $invoice->company->sanctioned_limit) {
      Log::warning("Company total committed amount already exceeds sanctioned limit on rejection: {$invoice->company->id}");
    }
    if ($invoice->company->pipeline_amount < $amountToAdjust) {
      Log::warning("Attempted to decrement Company pipeline amount below zero on rejection for ID: {$invoice->company->id}. Setting to zero.");
      $amountToAdjust = $invoice->company->pipeline_amount;
    }
    $invoice->company->decrement('pipeline_amount', $amountToAdjust);

    // Update Program
    if (($invoice->program->pipeline_amount + $invoice->program->utilized_amount) > $invoice->program->sanctioned_limit) {
      Log::warning("Program total committed amount already exceeds sanctioned limit on rejection: {$invoice->program->id}");
    }
    if ($invoice->program->pipeline_amount < $amountToAdjust) {
      Log::warning("Attempted to decrement Program pipeline amount below zero on rejection for ID: {$invoice->program->id}. Setting to zero.");
      $amountToAdjust = $invoice->program->pipeline_amount;
    }
    $invoice->program->decrement('pipeline_amount', $amountToAdjust);
  }

  /**
   * Dispatches email notifications after transaction commit.
   *
   * @param PaymentRequest $paymentRequest
   * @param PaymentRequestStatus $status
   */
  protected function dispatchApprovalNotifications(PaymentRequest $paymentRequest, PaymentRequestStatus $status): void
  {
    $invoice = $paymentRequest->invoice;
    $programType = $invoice->program->programType->name;
    $programCode = $invoice->program->programCode?->name; // Nullable

    $mailType = match ($programType) {
      Program::VENDOR_FINANCING => ($programCode === Program::VENDOR_FINANCING_RECEIVABLE) ? 'vendor_financing' : 'factoring',
      Program::DEALER_FINANCING => 'dealer_financing',
      default => 'general', // Fallback
    };

    // Send mail to company users
    foreach ($invoice->company->users as $companyUser) {
      // SendFinancingRequestUpdateMail::dispatch($companyUser->email, $status, [
      //   'financing_request' => $paymentRequest->id,
      //   'url' => config('app.url'),
      //   'name' => $companyUser->name,
      //   'type' => $mailType,
      // ])->afterCommit();
    }

    // Send mail to anchor company users (if applicable)
    if ($invoice->program->anchor) {
      foreach ($invoice->program->anchor->users as $anchorUser) {
        // SendFinancingRequestUpdateMail::dispatch($anchorUser->email, $status, [
        //   'financing_request' => $paymentRequest->id,
        //   'url' => config('app.url'),
        //   'name' => $anchorUser->name,
        //   'type' => $mailType, // Use the same type as company users for simplicity
        // ])->afterCommit();
      }
    }

    // Send notification to checker bank user if first approval (pending_maker -> pending_checker)
    if ($status === PaymentRequestStatus::PENDING_CHECKER) {
      foreach ($paymentRequest->invoice->program->bank->users as $bankUser) {
        $permissionToCheck = match ($programType) {
          Program::VENDOR_FINANCING => 'Approve Vendor Financing Requests Level 2',
          Program::DEALER_FINANCING => 'Approve Dealer Financing Requests Level 2',
          default => null,
        };

        if ($bankUser->id !== auth()->id() && $permissionToCheck && $bankUser->hasPermissionTo($permissionToCheck)) {
          // SendFinancingRequestUpdateMail::dispatch($bankUser->email, 'FinancingRequestApproved', [ // Specific mail for checker notification
          //   'financing_request' => $paymentRequest->id,
          //   'url' => config('app.url') . '/' . $paymentRequest->invoice->program->bank->url,
          //   'name' => $bankUser->name,
          //   'approver_name' => auth()->user()->name,
          //   'type' => $mailType,
          // ])->afterCommit();
        }
      }
    }
  }
}
