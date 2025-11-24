<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CreditAccountRequestResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'company_id' => $this->company_id,
      'company_name' => $this->company->name,
      'program_id' => $this->program_id,
      'program_name' => $this->program->name,
      'anchor_name' => $this->program->anchor->name,
      'credit_date' => $this->credit_date,
      'status' => $this->status,
      'reference_number' => $this->reference_number,
      'rejected_reason' => $this->rejected_reason,
      'amount' => $this->amount,
      'payment_accounts' => $this->whenLoaded('paymentAccounts'),
    ];
  }
}
