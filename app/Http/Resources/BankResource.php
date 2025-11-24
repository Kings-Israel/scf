<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankResource extends JsonResource
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
      'name' => $this->name,
      'admin_configuration' => new AdminBankConfigurationResource($this->whenLoaded('adminConfiguration')),
      'default_currency' => $this->default_currency,
      'product_types' => $this->product_types,
    ];
  }
}
