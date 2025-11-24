<?php

namespace App\Http\Resources;

use App\Models\Program;
use App\Models\ProgramCompanyRole;
use App\Models\ProgramMappingChange;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $details_changes = [];
    $fees_details_changes = [];
    $discount_details_changes = [];
    $dealer_discount_details_rates = [];
    $bank_details_changes = [];
    $vendor_configurations = [];
    $vendors_count = 0;
    if ($this->proposedUpdate) {
      $details_changes = array_key_exists('Program Details', $this->proposedUpdate->changes)
        ? $this->proposedUpdate->changes['Program Details']
        : null;
      $fees_details_changes = array_key_exists('Program Fees', $this->proposedUpdate->changes)
        ? $this->proposedUpdate->changes['Program Fees']
        : null;
      $discount_details_changes = array_key_exists('Program Discount Details', $this->proposedUpdate->changes)
        ? $this->proposedUpdate->changes['Program Discount Details']
        : null;
      $dealer_discount_details_rates = array_key_exists('Program Dealer Discount Rates', $this->proposedUpdate->changes)
        ? $this->proposedUpdate->changes['Program Dealer Discount Rates']
        : null;
      $bank_details_changes = array_key_exists('Program Bank Details', $this->proposedUpdate->changes)
        ? $this->proposedUpdate->changes['Program Bank Details']
        : null;
      if (array_key_exists('Program Vendor Configurations', $this->proposedUpdate->changes)) {
        foreach ($this->proposedUpdate->changes['Program Vendor Configurations'] as $program_vendor_configuration) {
          array_push(
            $vendor_configurations,
            ProgramVendorConfiguration::with('company')
              ->select('id', 'company_id', 'payment_account_number')
              ->find($program_vendor_configuration)
          );
        }
      }
    }

    $vendors_count = ProgramCompanyRole::where('program_id', $this->id)->count() - 1; // -1 to remove the anchor count
    $active_vendors_count = ProgramVendorConfiguration::where('program_id', $this->id)
      ->where('utilized_amount', '>', 0)
      ->count();
    $passive_vendors_count = ProgramVendorConfiguration::where('program_id', $this->id)
      ->where('utilized_amount', '<=', 0)
      ->count();

    $active_vendors_percentage = $vendors_count > 0 ? ($active_vendors_count / $vendors_count) * 100 : 0;

    return [
      'id' => $this->id,
      'name' => $this->name,
      'account_status' => $this->account_status,
      'bank_id' => $this->bank_id,
      'program_limit' => $this->program_limit,
      'utilized_amount' => $this->utilized_amount,
      'utilized' => $this->utilized_amount,
      'pipeline_amount' => $this->pipeline_amount,
      'pipeline' => $this->pipeline_amount,
      'utilized_percentage_ratio' => $this->utilized_percentage_ratio,
      'min_financing_days' => $this->min_financing_days,
      'status' => $this->status,
      'account_status' => $this->account_status,
      'bank' => new BankResource($this->whenLoaded('bank')),
      'program_type' => $this->whenLoaded('programType'),
      'program_code' => $this->whenLoaded('programCode'),
      'anchor' => new CompanyResource($this->whenLoaded('anchor')),
      // 'proposed_update' => $this->whenLoaded('proposedUpdate'),
      'discount_details' => $this->discountDetails,
      'can_edit' => $this->can_edit,
      'can_activate' => $this->can_activate,
      'can_view' => $this->can_view,
      'can_approve' => $this->can_approve,
      'can_delete' => $this->can_delete,
      'proposed_update' => $this->proposedUpdate,
      'details_changes' => $details_changes,
      'fees_details_changes' => $fees_details_changes,
      'discount_details_changes' => $discount_details_changes,
      'dealer_discount_details_rates' => $dealer_discount_details_rates,
      'bank_details_changes' => $bank_details_changes,
      'vendor_configurations' => $vendor_configurations,
      'can_approve_changes' =>
        auth()->check() &&
        auth()
          ->user()
          ->hasPermissionTo('Program Changes Checker') &&
        $this->proposedUpdate?->user_id != auth()->id()
          ? true
          : false,
      'eligibility' => $this->eligibility,
      'vendors_count' => $vendors_count,
      'active_vendors' => $active_vendors_count,
      'passive_vendors' => $passive_vendors_count,
      'active_vendors_percentage' => $active_vendors_percentage,
      'limit_expiry_date' => $this->limit_expiry_date,
      'deleted_at' => $this->deleted_at,
      'mapping_changes_count' =>
        ProgramMappingChange::where('program_id', $this->id)->count() +
        ProgramVendorConfiguration::where('program_id', $this->id)
          ->where('status', 'inactive')
          ->where('is_approved', false)
          ->where('deleted_at', null)
          ->count(),
    ];
  }
}
