<?php

namespace App\Observers;

use App\Models\PaymentRequest;
use App\Enums\PaymentRequestStatus;
use App\Services\InvoiceApprovalService;
use App\Exceptions\NegativePipelineAmountException;
use App\Exceptions\LimitExceededException; // Import the new exception
use Illuminate\Support\Facades\Log;

class PaymentRequestObserver
{
  protected InvoiceApprovalService $invoiceApprovalService;

  public function __construct(InvoiceApprovalService $invoiceApprovalService)
  {
      $this->invoiceApprovalService = $invoiceApprovalService;
  }

  /**
   * Handle the PaymentRequest "updated" event.
   * This method is called AFTER the model has been updated in the database.
   */
  public function updated(PaymentRequest $paymentRequest): void
  {
    // Check if the 'status' attribute has actually changed
    if ($paymentRequest->isDirty('status')) {
      $oldStatus = PaymentRequestStatus::from($paymentRequest->getOriginal('status'));
      $newStatus = $paymentRequest->status; // This will be the Enum instance

      // Trigger the service logic only when status changes from pending_checker to approved
      // Or from pending_maker to approved (if direct approval is allowed)
      // Or if it's a rejection (from any pending status)
      if (
        ($oldStatus === PaymentRequestStatus::PENDING_CHECKER && $newStatus === PaymentRequestStatus::APPROVED) ||
        ($oldStatus === PaymentRequestStatus::PENDING_MAKER && $newStatus === PaymentRequestStatus::APPROVED) ||
        ($newStatus === PaymentRequestStatus::REJECTED && ($oldStatus === PaymentRequestStatus::PENDING_MAKER || $oldStatus === PaymentRequestStatus::PENDING_CHECKER))
      ) {
        try {
          // The service handles the full logic, including transactions and notifications
          $this->invoiceApprovalService->handleApproval(
            $paymentRequest,
            $newStatus,
            $paymentRequest->rejected_reason // Pass rejection reason if available
          );
        } catch (NegativePipelineAmountException $e) {
          Log::error("Pipeline amount error during payment request update: " . $e->getMessage(), ['payment_request_id' => $paymentRequest->id]);
          // You might want to re-throw, or revert the status, or notify an admin.
          // For now, we'll just log and let the transaction rollback (if it was within the observer's transaction, which it isn't here).
          // Note: The service itself wraps its logic in a transaction, so if this exception is thrown from the service,
          // the service's transaction will roll back.
        } catch (LimitExceededException $e) {
          Log::error("Limit exceeded error during payment request update: " . $e->getMessage(), ['payment_request_id' => $paymentRequest->id]);
          // Handle specifically if a limit was exceeded
        } catch (\Exception $e) {
          Log::error("Error processing payment request update: " . $e->getMessage(), ['payment_request_id' => $paymentRequest->id]);
          // Handle other exceptions
        }
      }
    }
  }
}
