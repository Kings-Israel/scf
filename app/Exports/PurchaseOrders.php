<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrders implements FromCollection, WithHeadingRow, WithMapping, ShouldAutoSize
{
  /**
  * @return \Illuminate\Support\Collection
  */
  public function collection()
  {
      //
  }

  public function headings(): array
  {
    return [
      'Vendor',
      'Invoice Number',
      'Invoice Amount',
      'Invoice Date',
      'Due Date',
      'Status',
      'Financing Status',
      'Disbursement Date',
      'Disbursed Amount',
    ];
  }

  public function map($invoice): array
  {
    return [
      $invoice->company->name,
      $invoice->invoice_number,
      $invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_fees - $invoice->total_invoice_discount,
      $invoice->invoice_date,
      $invoice->due_date,
      Str::title($invoice->approval_stage),
      Str::title($invoice->financing_status),
      $invoice->disbursement_date,
      number_format($invoice->disbursed_amount),
    ];
  }
}
