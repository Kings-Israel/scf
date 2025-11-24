<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\ProgramRole;
use App\Models\ProposedConfigurationChange;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $utilized = 0;

    $limit = $this->top_level_borrower_limit;

    if ($limit > 0) {
      $utilized_amount = $this->utilized_amount;
      $pipeline_amount = $this->pipelein_amount;

      $utilized = round((($utilized_amount + $pipeline_amount) / $limit) * 100);
    }

    $roles = ProgramRole::whereHas('programs', function ($query) {
      $query->where('programs.published_at', '!=', null);
    })
      ->whereHas('companies', function ($query) {
        $query->where('companies.id', $this->id);
      })
      ->get()
      ->unique('id');

    // Check if company users have any proposed updates
    $user_changes =
      CompanyUser::where('company_id', $this->id)
        ->whereHas('user', function ($query) {
          $query->whereHas('changes');
        })
        ->count() +
      ProposedConfigurationChange::where('modeable_type', Company::class)
        ->where('modeable_id', $this->id)
        ->count();

    return [
      'id' => $this->id,
      'company_bank_id' => $this->company_bank_id,
      'name' => $this->name,
      'branch_code' => $this->branch_code,
      'kra_pin' => $this->kra_pin,
      'status' => $this->status,
      'approval_status' => $this->approval_status,
      'organization_type' => $this->organization_type,
      'is_blocked' => $this->is_blocked,
      'can_edit' => $this->can_edit,
      'can_edit_after_rejection' => $this->can_edit_after_rejection,
      'can_view' => $this->can_view,
      'can_activate' => $this->can_activate,
      'can_approve' => $this->can_approve,
      'can_block' => $this->can_block,
      'default_currency' => $this->default_currency,
      'proposed_update' => $this->proposedUpdate ? true : false,
      'update' => $this->proposedUpdate,
      'roles' => $roles,
      'pipeline' => $this->whenLoaded('pipeline'),
      'utilized' => $utilized,
      'deleted_at' => $this->deleted_at,
      'bank' => $this->whenLoaded('bank'),
      'unique_identification_number' => $this->unique_identification_number,
      'city' => $this->city,
      'postal_code' => $this->postal_code,
      'address' => $this->address,
      'user_changes' => $user_changes > 0 ? true : false,
    ];
  }
}
