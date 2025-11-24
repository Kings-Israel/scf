<?php

namespace App\Http\Resources;

use App\Models\Currency;
use App\Models\FinanceRequestApproval;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    if ($this->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configurations = ProgramVendorConfiguration::select(
          'id',
          'payment_account_number',
          'sanctioned_limit',
          'eligibility',
          'status',
          'withholding_tax',
          'withholding_vat'
        )
          ->where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();
      } else {
        $vendor_configurations = ProgramVendorConfiguration::select(
          'id',
          'payment_account_number',
          'sanctioned_limit',
          'eligibility',
          'status',
          'withholding_tax',
          'withholding_vat'
        )
          ->where('buyer_id', $this->invoice->buyer_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('buyer_id', $this->invoice->buyer_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();
      }
    } else {
      $vendor_configurations = ProgramVendorConfiguration::select(
        'id',
        'payment_account_number',
        'sanctioned_limit',
        'eligibility',
        'status',
        'withholding_tax',
        'withholding_vat'
      )
        ->where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->first();
      $vendor_discount_details = ProgramVendorDiscount::select(
        'total_roi',
        'vendor_discount_bearing',
        'anchor_discount_bearing'
      )
        ->where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->first();
    }

    $company_user_can_approve = false;
    $finance_request_approvals = null;

    if (auth()->check()) {
      $finance_request_approvals = FinanceRequestApproval::where('payment_request_id', $this->id)
        ->where('status', 'pending')
        ->get();
      if ($finance_request_approvals->count() > 0 && $finance_request_approvals->first()->user_id != auth()->id()) {
        $company_user_can_approve = true;
      }
    }

    $payment_request_amount = 0;
    $total_accounts = 0;

    $vendor_account = PaymentRequestAccount::where('payment_request_id', $this->id)
      ->where('type', 'vendor_account')
      ->first();

    if ($vendor_account) {
      $total_accounts = 1;
    }

    $total_accounts += PaymentRequestAccount::where('payment_request_id', $this->id)
      ->get()
      ->filter(fn($account) => $account->can_show)
      ->count();

    $payment_request_amount += PaymentRequestAccount::where('payment_request_id', $this->id)
      ->get()
      ->filter(fn($account) => $account->can_show)
      ->sum('amount');

    $payment_request_amount += PaymentRequestAccount::where('payment_request_id', $this->id)
      ->where('type', 'vendor_account')
      ->sum('amount');

    return [
      'id' => $this->id,
      'pr_id' => $this->pr_id,
      'pi_number' => $this->invoice->pi_number,
      'invoice_number' => $this->invoice->invoice_number,
      'invoice_currency' => $this->invoice->currency,
      'invoice_id' => $this->invoice->id,
      'invoice_amount' => $this->invoice->invoice_total_amount,
      'total' => $this->invoice->total,
      'invoice_date' => $this->invoice->invoice_date,
      'invoice_due_date' => $this->invoice->due_date,
      'invoice_disbursement_date' => $this->invoice->disbursement_date,
      'invoice_disbursed_amount' => $this->invoice->disbursed_amount,
      'invoice_balance' => $this->invoice->balance,
      'invoice_paid_amount' => $this->invoice->paid_amount,
      'invoice_overdue_amount' => $this->invoice->overdue_amount,
      'invoice_overdue' => $this->invoice->overdue,
      'invoice_days_past_due' => $this->invoice->days_past_due,
      'invoice_financing_status' => $this->invoice->financing_status,
      'invoice_status' => $this->invoice->status,
      'purchase_order_number' => $this->invoice->purchaseOrder?->purchase_order_number,
      'purchase_order_id' => $this->invoice->purchaseOrder?->id,
      'program_name' => $this->invoice->program->name,
      'program_id' => $this->invoice->program->id,
      'company_name' => $this->invoice->company->name,
      'company_id' => $this->invoice->company->id,
      'program_suspended' => $this->invoice->program->account_status == 'active' ? false : true,
      'mapping_suspended' => $vendor_configurations->status == 'active' ? false : true,
      'company_id' => $this->invoice->company->id,
      'anchor_name' => $this->invoice->program->anchor->name,
      'anchor_id' => $this->invoice->program->anchor->id,
      'buyer_name' => $this->invoice->buyer?->name,
      'buyer_id' => $this->invoice->buyer?->id,
      'program_type' => $this->invoice->program->programType->name,
      'processing_fee' => $this->processing_fee,
      'payment_request_date' => $this->payment_request_date,
      'status' => $this->status,
      'reference_number' => $this->reference_number,
      'rejected_reason' => $this->rejected_reason,
      'amount' => round($this->amount, 2),
      'anchor_discount_bearing' => $this->anchor_discount_bearing,
      'vendor_discount_bearing' => $this->vendor_discount_bearing,
      'anchor_fee_bearing' => $this->anchor_fee_bearing,
      'vendor_fee_bearing' => $this->vendor_fee_bearing,
      'payment_accounts' => PaymentRequestAccountResource::collection($this->whenLoaded('paymentAccounts')),
      // Get the first company approval to determine if the logged in user can approve the request
      'company_approvals' => FinanceRequestApprovalResource::collection($this->whenLoaded('companyApprovals')),
      'cbs_transactions' => CbsTransactionResource::collection($this->whenLoaded('cbsTransactions')),
      'debit_from_account' => $this->cbsTransactions->first()?->debit_from_account,
      'pay_date' => $this->cbsTransactions->count() > 0 ? $this->cbsTransactions->first()->pay_date : null,
      'payment_reference_number' => $this->payment_reference_number,
      'due_date' => $this->invoice->due_date,
      'currency' => $this->invoice->currency,
      'pi_amount' => $this->eligible_for_financing,
      'user_can_approve' => $this->user_can_approve,
      'user_has_approved' => $this->user_has_approved,
      'approval_stage' => $this->approval_stage,
      'can_view_company' => $this->invoice->company->can_view,
      'can_view_anchor' => $this->invoice->program->anchor->can_view,
      'can_view_buyer' => $this->invoice->buyer?->can_view,
      'invoice_total_amount' => $this->invoice->invoice_total_amount,
      'approvals' => $this->approvals,
      'eligible_for_finance' => $this->eligible_for_finance,
      'discount' => $this->discount,
      'drawdown_amount' => $this->invoice->drawdown_amount,
      // 'utilized_percentage_ratio' => $this->invoice->company->utilizedPercentage($this->invoice->program),
      'utilized_percentage_ratio' => 0,
      'discount_rate' => $this->discount_rate,
      'payment_request_amount' => round($payment_request_amount, 2),
      'total_accounts' => $total_accounts,
      'requires_company_approval' =>
        $finance_request_approvals && $finance_request_approvals->count() > 0 ? true : false,
      'company_user_can_approve' => $company_user_can_approve,
      'eligibility' => $this->invoice->eligibility,
      'sanctioned_limit' => $vendor_configurations->sanctioned_limit,
      'program_eligibility' => $this->invoice->eligibility,
      'total_roi' => $vendor_discount_details->total_roi,
      'payment_account_number' => $vendor_configurations->payment_account_number,
      'program_vendor_configuration_id' => $vendor_configurations->id,
      'discount_charge_type' => $this->invoice->discount_charge_type,
      'fee_charge_type' => $this->invoice->fee_charge_type,
      'rejected_by' => $this->rejected_by ? User::find($this->rejected_by) : null,
      'createdBy' => $this->created_by ? User::find($this->created_by) : null,
      'updatedBy' => $this->updated_by ? User::find($this->updated_by) : null,
      'created_at' => $this->created_at,
      'invoice_created_at' => $this->invoice->created_at,
      'updated_at' => $this->updated_at,
      'invoice_updated_at' => $this->invoice->updated_at,
      'discount_transactions' => ['Discount', 'Tax On Discount'],
      'fees_transactions' => ['Program Fees', 'Tax On Fees'],
    ];
  }
}
