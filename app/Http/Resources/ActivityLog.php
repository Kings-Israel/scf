<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLog extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $subject = null;

    switch ($this->subject_type) {
      case 'App\\Models\\Invoice':
        $subject = $this->subject?->invoice_number;
        break;
      case 'App\\Models\\Company':
        $subject = $this->subject->name;
        break;
      case 'App\\Models\\CbsTransaction':
        $subject = $this->subject_id;
        break;
      case 'App\\Models\\PaymentRequest':
        $subject = $this->subject?->reference_number;
        break;
      default:
        $subject = '';
        break;
    }

    return [
      'user' => $this->causer->name,
      'description' => $this->description,
      'properties' => $this->properties,
      'subject' => $subject,
      'subject_type' => $this->subject_type,
      'causer_type' => $this->causer_type,
      'causer' => $this->causer,
      'created_at' => $this->created_at,
    ];
  }
}
