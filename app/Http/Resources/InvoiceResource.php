<?php

namespace App\Http\Resources;

use App\Models\CbsTransaction;
use App\Models\CompanyAuthorizationGroup;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $transaction_ref = '';
    if ($this->financing_status == 'disbursed' || $this->financing_status == 'closed') {
      $transaction_ref = CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query->where('id', $this->id);
        });
      })
        ->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT)
        ->first()?->transaction_reference;
    }

    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_fee_details = ProgramVendorFee::select(
          'vendor_bearing_discount',
          'anchor_bearing_discount',
          'dealer_bearing'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_configuration = ProgramVendorConfiguration::select('payment_account_number')
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_fee_details = ProgramVendorFee::select(
          'vendor_bearing_discount',
          'anchor_bearing_discount',
          'dealer_bearing'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_configuration = ProgramVendorConfiguration::select('payment_account_number')
          ->where('company_id', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
      }
    } else {
      $vendor_discount_details = ProgramVendorDiscount::select(
        'total_roi',
        'vendor_discount_bearing',
        'anchor_discount_bearing'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
      $vendor_fee_details = ProgramVendorFee::select(
        'dealer_bearing',
        'vendor_bearing_discount',
        'anchor_bearing_discount'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
      $vendor_configuration = ProgramVendorConfiguration::select('payment_account_number')
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
    }

    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $authorization_group = CompanyAuthorizationGroup::where('company_id', $this->program->anchor->id)
          ->with('authorizationUsers.user')
          ->whereHas('rules', function ($query) {
            $query->whereHas('authorizationMatrix', function ($query) {
              $query
                ->where('min_pi_amount', '<=', $this->total + $this->total_invoice_taxes)
                ->where('max_pi_amount', '>=', $this->total + $this->total_invoice_taxes)
                ->where('program_type_id', $this->program->program_type_id);
            });
          })
          ->exists();
      } else {
        $authorization_group = CompanyAuthorizationGroup::where('company_id', $this->buyer_id)
          ->with('authorizationUsers.user')
          ->whereHas('rules', function ($query) {
            $query->whereHas('authorizationMatrix', function ($query) {
              $query
                ->where('min_pi_amount', '<=', $this->total + $this->total_invoice_taxes)
                ->where('max_pi_amount', '>=', $this->total + $this->total_invoice_taxes)
                ->where('program_type_id', $this->program->program_type_id);
            });
          })
          ->exists();
      }
    } else {
      $authorization_group = CompanyAuthorizationGroup::where('company_id', $this->program->anchor->id)
        ->with('authorizationUsers.user')
        ->whereHas('rules', function ($query) {
          $query->whereHas('authorizationMatrix', function ($query) {
            $query
              ->where('min_pi_amount', '<=', $this->total + $this->total_invoice_taxes)
              ->where('max_pi_amount', '>=', $this->total + $this->total_invoice_taxes)
              ->where('program_type_id', $this->program->program_type_id);
          });
        })
        ->exists();
    }

    return [
      'id' => $this->id,
      'program_id' => $this->program_id,
      'program_name' => $this->program->name,
      'program_type_id' => $this->program->programType->id,
      'program_type_name' => $this->program->programType->name,
      'program_code_id' => $this->program->programCode?->id,
      'program_code_name' => $this->program->programCode?->name,
      'bank_id' => $this->program->bank_id,
      'currency' => $this->currency,
      'invoice_number' => $this->invoice_number,
      'invoice_total_amount' => $this->invoice_total_amount,
      'invoice_date' => $this->invoice_date,
      'due_date' => $this->due_date,
      'old_due_date' => $this->old_due_date,
      'paid_amount' => $this->paid_amount,
      'balance' => $this->balance,
      'total' => $this->total,
      'total_invoice_discount' => $this->total_invoice_discount,
      'total_invoice_taxes' => $this->total_invoice_taxes,
      'total_invoice_fees' => $this->total_invoice_fees,
      'eligibility' => $this->eligibility,
      'recourse' => $this->recourse,
      'eligible_for_finance' => $this->eligible_for_finance,
      'eligible_for_financing' => $this->eligible_for_financing,
      'days_past_due' => $this->days_past_due,
      // Used in calculation of balance of invoice
      'overdue_amount' => $this->overdue_amount,
      // For Financed and Closed Invoices
      'overdue' => $this->overdue,
      'can_edit' => $this->can_edit,
      'can_delete' => $this->can_delete,
      'can_delete_attachment' => $this->can_delete_attachment,
      'approval_stage' => $this->approval_stage,
      'user_can_approve' => $this->user_can_approve,
      'user_has_approved' => $this->user_has_approved,
      'can_edit' => $this->can_edit,
      'can_edit_fees' => $this->can_edit_fees,
      'can_request_today' => $this->can_request_today,
      'financing_request_window' => $this->financing_request_window,
      'discount' => $this->discount,
      'discount_rate' => $vendor_discount_details?->total_roi,
      'anchor_discount_bearing' => $vendor_discount_details?->anchor_discount_bearing,
      'vendor_discount_bearing' => $vendor_discount_details?->vendor_discount_bearing,
      'anchor_discount_bearing_amount' => $this->anchor_discount_bearing_amount,
      'vendor_discount_bearing_amount' => $this->vendor_discount_bearing_amount,
      'vendor_fee_bearing_amount' => $this->vendor_fee_bearing_amount,
      'anchor_fee_bearing_amount' => $this->anchor_fee_bearing_amount,
      'anchor_fee_bearing' => $vendor_fee_details?->anchor_bearing_discount,
      'vendor_fee_bearing' => $vendor_fee_details?->vendor_bearing_discount,
      'dealer_fee_bearing' => $vendor_fee_details?->dealer_bearing,
      'status' => $this->status,
      'financing_status' => $this->financing_status,
      'pi_number' => $this->pi_number,
      'anchor' => $this->program->anchor->name,
      'anchor_id' => $this->program->anchor->id,
      'total_amount' => $this->total_amount,
      'disbursed_amount' => $this->disbursed_amount,
      'disbursement_date' => $this->disbursement_date,
      'dealer' => $this->dealer?->name,
      'buyer' => $this->buyer?->name,
      'buyer_id' => $this->buyer?->id,
      'company' => $this->company->name,
      'company_id' => $this->company->id,
      'purchase_order_id' => $this->purchaseOrder?->id,
      'purchase_order_number' => $this->purchaseOrder?->purchase_order_number,
      'payment_requests' => $this->paymentRequests->count(),
      'actual_remittance_amount' => $this->actual_remittance_amount,
      'approvals' => $this->whenLoaded('approvals'),
      'min_financing_days' => $this->program->min_financing_days,
      'program_type' => $this->program->programType->name,
      'transaction_ref' => $transaction_ref,
      'program_fees' => $this->program_fees,
      'payment_account_number' => $vendor_configuration->payment_account_number,
      'updated_at' => $this->updated_at,
      'drawdown_amount' => $this->drawdown_amount,
      'discount_type' => $this->discount_type,
      'discount_charge_type' => $this->discount_charge_type,
      'fee_charge_type' => $this->fee_charge_type,
      // 'program' => new ProgramResource($this->whenLoaded('program')),
      // 'company' => new CompanyResource($this->whenLoaded('company')),
      'authorization_group' => $authorization_group,
      'payment_request_date' =>
        $this->paymentRequests->count() > 0 ? $this->paymentRequests->first()->payment_request_date : null,
      'closure_date' => $this->financing_status === 'closed' ? $this->closure_date : null,
      'closure_transaction_reference' =>
        $this->financing_status === 'closed' ? $this->closure_transaction_reference : null,
    ];
  }
}
