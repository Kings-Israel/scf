<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceApprovalResource extends JsonResource
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
          'currency' => $this->currency,
          'invoice_number' => $this->invoice_number,
          'invoice_total_amount' => $this->invoice_total_amount,
          'invoice_date' => $this->invoice_date,
          'due_date' => $this->due_date,
          'paid_amount' => $this->paid_amount,
          'balance' => $this->balance,
          'total' => $this->total,
          'total_invoice_discount' => $this->total_invoice_discount,
          'total_invoice_taxes' => $this->total_invoice_taxes,
          'total_invoice_fees' => $this->total_invoice_fees,
          'program' => new ProgramResource($this->whenLoaded('program')),
          'program.anchor' => new CompanyResource($this->whenLoaded('program.anchor')),
          'dealer' => $this->dealer_id ? new CompanyResource($this->dealer) : NULL,
          'buyer' => $this->buyer_id ? new CompanyResource($this->buyer) : NULL,
          'company' => new CompanyResource($this->whenLoaded('company')),
          'purchase_order' => $this->whenLoaded('purchaseOrder'),
          'invoice_fees' => $this->whenLoaded('invoiceFees'),
          'invoice_taxes' => $this->whenLoaded('invoiceTaxes')
        ];
    }
}
