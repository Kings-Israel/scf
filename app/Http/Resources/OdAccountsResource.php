<?php

namespace App\Http\Resources;

use App\Models\Program;
use App\Models\ProgramBankDetails;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use Illuminate\Http\Resources\Json\JsonResource;

class OdAccountsResource extends JsonResource
{
  /**
   * The resource that this resource collects.
   *
   * @var string
   */
  public $collects = ProgramVendorConfiguration::class;

  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->get();
        $vendor_discount = ProgramVendorDiscount::select('total_roi', 'benchmark_title', 'benchmark_rate')
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->get();
        $vendor_discount = ProgramVendorDiscount::select('total_roi', 'benchmark_title', 'benchmark_rate')
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
      }
    } else {
      $vendor_bank_details = ProgramBankDetails::where('program_id', $this->program_id)->get();
      $vendor_discount = ProgramVendorDiscount::select('total_roi', 'benchmark_title', 'benchmark_rate')
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
    }

    $utilized_percentage = 0;
    if ($this->sanctioned_limit > 0) {
      $utilized_percentage = round(
        (($this->utilized_amount + $this->pipeline_amount) / $this->sanctioned_limit) * 100,
        2
      );
    }

    return [
      'id' => $this->id,
      'payment_account_number' => $this->payment_account_number,
      'program_name' => $this->program->name,
      'program_id' => $this->program->id,
      'company_name' => $this->company->name,
      'company_id' => $this->company->id,
      'company_unique_identification_number' => $this->company->unique_identification_number,
      'anchor_name' => $this->program->anchor->name,
      'anchor_unique_identification_number' => $this->program->anchor->unique_identification_number,
      'anchor_id' => $this->program->anchor->id,
      'buyer_name' => $this->buyer?->name,
      'buyer_id' => $this->buyer?->id,
      'buyer_unique_identification_number' => $this->buyer?->unique_identification_number,
      'sanctioned_limit' => $this->sanctioned_limit,
      'drawing_power' => $this->drawing_power,
      'limit_approved_date' => $this->limit_approved_date,
      'expiry_date' => $this->limit_expiry_date,
      'limit_review_date' => $this->limit_review_date,
      'eligibility' => $this->eligibility,
      'status' => $this->status,
      'pipeline_amount' => $this->pipeline_amount,
      'utilized_amount' => $this->utilized_amount,
      'available_amount' => $this->sanctioned_limit - ($this->pipeline_amount + $this->utilized_amount),
      'overdue_days' => $this->days_past_due,
      'overdue_amount' => $this->overdue_amount,
      'paid_amount' => $this->paidAmount(),
      'vendor_bank_details' => $vendor_bank_details,
      'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
      'deleted_at' => $this->deleted_at,
      'deleted_by' => $this->deleted_by,
      'is_approved' => $this->is_approved,
      'user' => $this->user,
      'can_approve' => $this->can_approve,
      'utilized_percentage' => $utilized_percentage,
      'auto_request_finance' => $this->auto_request_finance,
      'is_rejected' => $this->rejected_by ? true : false,
      'rejected' => $this->rejected_by == auth()->id() ? true : false,
      'rejection_reason' => $this->rejection_reason,
      'total_roi' => $vendor_discount->total_roi,
      'benchmark_title' => $vendor_discount->benchmark_title,
      'benchmark_rate' => $vendor_discount->benchmark_rate,
      'can_delete' => $this->can_delete,
      'total_requested_amount' => $this->total_requested_amount,
      'is_blocked' => $this->is_blocked
    ];
  }
}
