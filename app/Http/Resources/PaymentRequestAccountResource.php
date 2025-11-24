<?php

namespace App\Http\Resources;

use App\Models\ProgramVendorConfiguration;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PaymentRequestAccountResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $debit_from = '';
    $transaction_reference = '';
    if ($this->paymentRequest->invoice->program->programType->name == 'Vendor Financing') {
      if ($this->paymentRequest->invoice->program->programCode->name == 'Vendor Financing Receivable') {
        $debit_from = ProgramVendorConfiguration::where('company_id', $this->paymentRequest->invoice->company_id)
          ->where('program_id', $this->paymentRequest->invoice->program_id)
          ->first()->payment_account_number;
      } else {
        $debit_from = ProgramVendorConfiguration::where('buyer_id', $this->paymentRequest->invoice->buyer_id)
          ->where('program_id', $this->paymentRequest->invoice->program_id)
          ->first()->payment_account_number;
      }
    } else {
      $debit_from = ProgramVendorConfiguration::where('company_id', $this->paymentRequest->invoice->company_id)
        ->where('program_id', $this->paymentRequest->invoice->program_id)
        ->first()->payment_account_number;
    }

    $transaction_reference =
      $this->paymentRequest?->cbsTransactions->count() > 0
        ? $this->paymentRequest?->cbsTransactions?->first()->transaction_reference
        : null;

    return [
      'id' => $this->id,
      'pr_id' => $this->paymentRequest->id,
      'debit_from' => $debit_from,
      'credit_to' => $this->account,
      'account' => $this->account,
      'amount' => round($this->amount, 2),
      'buyer' => $this->paymentRequest->invoice->company->name,
      'vendor' => $this->paymentRequest->invoice->program->anchor->name,
      'invoice_number' => $this->paymentRequest->invoice->invoice_number,
      'pi_number' => $this->paymentRequest->invoice->pi_number,
      'pay_date' => $this->paymentRequest->payment_request_date,
      'paid_date' => $this->pay_date,
      'type' => Str::title(str_replace('_', ' ', $this->type)),
      'status' => $this->paymentRequest->status == 'approved' ? 'created' : $this->paymentRequest->status,
      'transaction_reference' => $transaction_reference,
      'title' => $this->title,
      'description' => $this->description,
      'created_by' => '',
      'created_at' => $this->created_at,
      'updated_by' => '',
      'updated_at' => $this->updated_at,
      'can_show' => $this->can_show
    ];
  }
}
