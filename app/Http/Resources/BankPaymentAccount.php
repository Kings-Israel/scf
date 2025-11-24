<?php

namespace App\Http\Resources;

use App\Models\CbsTransaction;
use Illuminate\Http\Resources\Json\JsonResource;

class BankPaymentAccount extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    // Get Amount Credited to Account
    $credit_amount = CbsTransaction::where('bank_id', $this->bank_id)
      ->where('credit_to_account', $this->account_number)
      ->sum('amount');
    $debit_amount = CbsTransaction::where('bank_id', $this->bank_id)
      ->where('debit_from_account', $this->account_number)
      ->sum('amount');

    return [
      'id' => $this->id,
      'bank_id' => $this->bank_id,
      'bank' => $this->whenLoaded('bank'),
      'account_name' => $this->account_name,
      'account_number' => $this->account_number,
      'credited_amount' => $credit_amount,
      'debited_amount' => $debit_amount,
      'balance' => $credit_amount - $debit_amount,
    ];
  }
}
