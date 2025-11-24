<?php

namespace App\Exports;

use App\Models\InvoiceUploadReport;
use App\Models\Program;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UploadedInvoices implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
  public $current_company;
  public $invoice_number;
  public $status;
  public $uploaded_date;
  public $type;

  public function __construct($current_company, $invoice_number, $status, $uploaded_date, $type)
  {
    $this->current_company = $current_company;
    $this->invoice_number = $invoice_number;
    $this->status = $status;
    $this->uploaded_date = $uploaded_date;
    $this->type = $type;
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    $invoices = InvoiceUploadReport::where('company_id', $this->current_company->company_id)
      ->when($this->type && $this->type != '', function ($query) {
        switch ($this->type) {
          case 'Vendor Financing Receivable':
            $query->where('product_code', Program::VENDOR_FINANCING_RECEIVABLE);
            break;
          case 'Anchor Vendor Financing Receivable':
            $query->where('product_code', Program::VENDOR_FINANCING_RECEIVABLE);
            break;
          case 'Anchor Factoring':
            $query->where(function ($q) {
              $q->where('product_code', 'Factoring')->orWhere('product_code', null);
            });
            break;
          case 'Buyer Factoring':
            $query->where('product_code', 'Factoring');
            break;
          case 'Dealer Financing':
            $query->where('product_type', Program::DEALER_FINANCING);
            break;
          default:
            # code...
            break;
        };
      })
      ->when($this->status && $this->status != '', function ($query) {
        $query->where('status', $this->status);
      })
      ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($this->invoice_number, '\\') . '%');
      })
      ->when($this->uploaded_date && $this->uploaded_date != '', function ($query) {
        $query->whereDate('created_date', $this->uploaded_date);
      })
      ->latest()
      ->get();

    return $invoices;
  }

  public function headings(): array
  {
    return ['Invoice Number', 'Status', 'Remarks'];
  }

  public function map($invoice): array
  {
    return [$invoice->invoice_number, $invoice->status, $invoice->description];
  }
}
