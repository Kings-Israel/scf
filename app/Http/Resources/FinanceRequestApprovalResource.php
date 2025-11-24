<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FinanceRequestApprovalResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'payment_request_id' => $this->payment_request_id,
      'user_id' => $this->user_id,
      'status' => $this->status,
      'rejection_reason' => $this->rejection_reason,
      'can_approve' => $this->can_approve,
    ];
  }
}
