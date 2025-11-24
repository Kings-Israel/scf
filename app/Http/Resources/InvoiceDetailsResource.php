<?php

namespace App\Http\Resources;

use App\Models\CompanyAuthorizationGroup;
use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetailsResource extends JsonResource
{
  public $collects = Invoice::class;

  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $vendor_configurations = null;
    $vendor_discount_details = null;
    $vendor_fee_details = null;
    $vendor_contact_details = null;
    $vendor_bank_details = null;
    $bank_details = null;
    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configurations = new OdAccountsResource(ProgramVendorConfiguration::select(
          'program_id',
          'company_id',
          'buyer_id',
          'payment_account_number',
          'sanctioned_limit',
          'eligibility',
          'status',
          'withholding_tax',
          'withholding_vat'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first());
        $vendor_discount_details = ProgramVendorDiscount::select(
          'program_id',
          'company_id',
          'buyer_id',
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing',
          'penal_discount_on_principle',
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_fee_details = ProgramVendorFee::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->get();
        $vendor_contact_details = ProgramVendorContactDetail::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->get();
        $bank_details = ProgramBankDetails::where('program_id', $this->program_id)->get();
      } else {
        $vendor_configurations = new OdAccountsResource(ProgramVendorConfiguration::select(
          'program_id',
          'company_id',
          'buyer_id',
          'payment_account_number',
          'sanctioned_limit',
          'eligibility',
          'status',
          'withholding_tax',
          'withholding_vat'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first());
        $vendor_discount_details = ProgramVendorDiscount::select(
          'program_id',
          'company_id',
          'buyer_id',
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing',
          'penal_discount_on_principle',
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_fee_details = ProgramVendorFee::where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->get();
        $vendor_contact_details = ProgramVendorContactDetail::where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
        $vendor_bank_details = ProgramVendorBankDetail::where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->get();
        $bank_details = ProgramBankDetails::where('program_id', $this->program_id)->get();
      }
    } else {
      $vendor_configurations = new OdAccountsResource(ProgramVendorConfiguration::select(
        'program_id',
        'company_id',
        'buyer_id',
        'payment_account_number',
        'sanctioned_limit',
        'eligibility',
        'status',
        'withholding_tax',
        'withholding_vat'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first());
      $vendor_discount_details = ProgramVendorDiscount::select(
        'program_id',
        'company_id',
        'buyer_id',
        'total_roi',
        'vendor_discount_bearing',
        'anchor_discount_bearing',
        'from_day',
        'to_day',
        'penal_discount_on_principle',
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->get();
      $vendor_fee_details = ProgramVendorFee::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->get();
      $vendor_contact_details = ProgramVendorContactDetail::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
      $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->get();
      $bank_details = ProgramBankDetails::where('program_id', $this->program_id)->get();
    }

    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
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
        ->first();
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
          ->first();
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
        ->first();
    }

    return [
      'id' => $this->id,
      'currency' => $this->currency,
      'invoice_number' => $this->invoice_number,
      'invoice_total_amount' => $this->invoice_total_amount,
      'invoice_date' => $this->invoice_date,
      'due_date' => $this->due_date,
      'paid_amount' => $this->paid_amount,
      'balance' => $this->balance,
      'total' => $this->total,
      'total_invoice_discount' => $this->total_invoice_discount,
      'total_invoice_taxes' => $this->total_invoice_taxes,
      'total_invoice_fees' => $this->total_invoice_fees,
      'eligible_for_finance' => $this->eligible_for_finance,
      'days_past_due' => $this->days_past_due,
      'overdue_amount' => $this->overdue_amount,
      'paid_overdue_amount' => $this->paid_overdue_amount,
      'can_edit' => $this->can_edit,
      'can_delete' => $this->can_delete,
      'can_delete_attachment' => $this->can_delete_attachment,
      'can_request_today' => $this->can_request_today,
      'financing_request_window' => $this->financing_request_window,
      'approval_stage' => $this->approval_stage,
      'user_can_approve' => $this->user_can_approve,
      'user_has_approved' => $this->user_has_approved,
      'can_edit' => $this->can_edit,
      'can_edit_fees' => $this->can_edit_fees,
      'discount' => $this->discount,
      'status' => $this->status,
      'financing_status' => $this->financing_status,
      'pi_number' => $this->pi_number,
      'program' => new ProgramResource($this->whenLoaded('program')),
      'program.anchor' => new CompanyResource($this->whenLoaded('program.anchor')),
      'total_amount' => $this->total_amount,
      'drawdown_amount' => $this->drawdown_amount,
      'invoice_items' => $this->whenLoaded('invoiceItems'),
      'invoice_discounts' => $this->whenLoaded('invoiceDiscounts'),
      'invoice_taxes' => $this->whenLoaded('invoiceTaxes'),
      'invoice_fees' => $this->whenLoaded('invoiceFees'),
      'disbursed_amount' => $this->disbursed_amount,
      'disbursement_date' => $this->disbursement_date,
      'attachment' => $this->attachment,
      'dealer' => $this->dealer_id ? new CompanyResource($this->dealer) : null,
      'buyer' => $this->buyer_id ? new CompanyResource($this->buyer) : null,
      'company' => new CompanyResource($this->whenLoaded('company')),
      'vendor_configurations' => $vendor_configurations,
      'vendor_discount_details' => $vendor_discount_details,
      'vendor_fee_details' => $vendor_fee_details,
      'vendor_contact_details' => $vendor_contact_details,
      'vendor_bank_details' => $vendor_bank_details,
      'bank_details' => $bank_details,
      'actual_remittance_amount' => $this->actual_remittance_amount,
      'min_financing_days' => $this->program->min_financing_days,
      'max_financing_days' => $this->program->max_financing_days,
      'purchase_order' => $this->whenLoaded('purchaseOrder'),
      'media' => $this->getMedia('*')->toArray(),
      'invoice_media' => $this->getMedia('*')->toArray(),
      'payment_requests' => PaymentRequestResource::collection($this->whenLoaded('paymentRequests')),
      'cbs_transactions' => CbsTransactionResource::collection($this->whenLoaded('cbsTransactions')),
      'approvals' => $this->whenLoaded('approvals'),
      'credit_to' => $this->credit_to,
      'rejected_reason' => $this->rejected_reason,
      'discount_type' => $this->discount_type,
      'discount_charge_type' => $this->discount_charge_type,
      'fee_charge_type' => $this->fee_charge_type,
      'authorization_group' => $authorization_group,
    ];
  }
}
