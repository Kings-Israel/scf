<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CbsTransactionResource extends JsonResource
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
      'cbs_id' => $this->cbs_id,
      'debit_from_account' => $this->debit_from_account,
      'debit_from_account_name' => $this->debit_from_account_name,
      'debit_from_account_description' => $this->debit_from_account_description,
      'credit_to_account' => $this->credit_to_account,
      'credit_to_account_name' => $this->credit_to_account_name,
      'credit_to_account_description' => $this->credit_to_account_description,
      'amount' => $this->amount,
      'transaction_created_date' => $this->transaction_created_date,
      'transaction_date' => $this->transaction_date,
      'transaction_reference' => $this->transaction_reference,
      'pay_date' => $this->pay_date,
      'status' => $this->status,
      'transaction_type' => $this->transaction_type,
      'product' => $this->product,
      'payment_request' => new PaymentRequestResource($this->whenLoaded('paymentRequest')),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
