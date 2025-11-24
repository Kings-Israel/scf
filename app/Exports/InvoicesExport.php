<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InvoicesExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
  public $vendor = '';
  public $anchor = '';
  public $buyer = '';
  public $invoice_number = '';
  public $from_date = '';
  public $to_date = '';
  public $from_invoice_date = '';
  public $to_invoice_date = '';
  public $status = [];
  public $financing_status = [];
  public $program = '';
  public $disbursement_date = '';
  public $pi_number = '';
  public $type = '';
  public $sort_by = '';

  public function __construct(
    public Company $company,
    string $type,
    $program = '',
    $anchor = '',
    $vendor = '',
    $invoice_number = '',
    $from_date = '',
    $to_date = '',
    $from_invoice_date = '',
    $to_invoice_date = '',
    $status = [],
    $financing_status = [],
    $disbursement_date = '',
    $pi_number = '',
    $sort_by = '',
    $buyer = ''
  ) {
    $this->type = $type;
    $this->program = $program;
    $this->anchor = $anchor;
    $this->vendor = $vendor;
    $this->buyer = $buyer;
    $this->invoice_number = $invoice_number;
    $this->from_date = $from_date;
    $this->to_date = $to_date;
    $this->from_invoice_date = $from_invoice_date;
    $this->to_invoice_date = $to_invoice_date;
    $this->status = $status;
    $this->financing_status = $financing_status;
    $this->disbursement_date = $disbursement_date;
    $this->pi_number = $pi_number;
    $this->sort_by = $sort_by;
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    if ($this->type == 'vendor' || $this->type == 'anchor') {
      // Get programs
      $company_programs = [];
      foreach ($this->company->programs as $program) {
        if (
          $program->programType->name === Program::VENDOR_FINANCING &&
          $program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          array_push($company_programs, $program->id);
        }
      }

      return Invoice::whereIn('program_id', $company_programs)
        ->when($this->anchor && $this->anchor != '', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->whereHas('anchor', function ($query) {
              $query->where('name', 'LIKE', '%' . $this->anchor . '%');
            });
          });
        })
        ->when($this->vendor && $this->vendor != '', function ($query) {
          $query->whereHas('company', function ($query) {
            $query->where('name', 'LIKE', '%' . $this->vendor . '%');
          });
        })
        ->when($this->program && $this->program != '', function ($query) {
          $query->where('program_id', $this->program);
        })
        ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($this->invoice_number, '\\') . '%');
        })
        ->when($this->from_date && $this->from_date != '', function ($query) {
          $query->whereDate('due_date', '>=', $this->from_date);
        })
        ->when($this->to_date && $this->to_date != '', function ($query) {
          $query->whereDate('due_date', '<=', $this->to_date);
        })
        ->when($this->from_invoice_date && $this->from_invoice_date != '', function ($query) {
          $query->whereDate('invoice_date', '>=', $this->from_invoice_date);
        })
        ->when($this->to_invoice_date && $this->to_invoice_date != '', function ($query) {
          $query->whereDate('invoice_date', '<=', $this->to_invoice_date);
        })
        ->when($this->disbursement_date && $this->disbursement_date != '', function ($query) {
          $query->whereDate('disbursement_date', $this->disbursement_date);
        })
        ->when($this->pi_number && $this->pi_number != '', function ($query) {
          $query->where('pi_number', 'LIKE', '%' . $this->pi_number . '%');
        })
        ->when($this->status && count($this->status) > 0, function ($query) {
          $query->whereIn('stage', $this->status);
        })
        ->when($this->financing_status && count($this->financing_status) > 0, function ($query) {
          $query->whereIn('financing_status', $this->financing_status);
        })
        ->when(!$this->sort_by || $this->sort_by == '', function ($query) {
          $query->orderBy('updated_at', 'DESC');
        })
        ->when($this->sort_by && $this->sort_by != '', function ($query) {
          if ($this->sort_by == 'po_asc') {
            $query->whereHas('purchaseOrder', function ($query) {
              $query->orderBy('purchase_order_number', 'ASC');
            });
          } elseif ($this->sort_by == 'po_desc') {
            $query->whereHas('purchaseOrder', function ($query) {
              $query->orderBy('purchase_order_number', 'DESC');
            });
          } elseif ($this->sort_by == 'invoice_no_asc') {
            $query->orderBy('invoice_number', 'ASC');
          } elseif ($this->sort_by == 'invoice_no_desc') {
            $query->orderBy('invoice_number', 'DESC');
          } elseif ($this->sort_by == 'invoice_amount_asc') {
            $query->orderBy('total_amount', 'ASC');
          } elseif ($this->sort_by == 'invoice_amount_desc') {
            $query->orderBy('total_amount', 'DESC');
          } elseif ($this->sort_by == 'vendor_asc') {
            $query->join('companies', 'companies.id', '=', 'invoices.company_id')->orderBy('companies.name', 'ASC');
          } elseif ($this->sort_by == 'vendor_desc') {
            $query->join('companies', 'companies.id', '=', 'invoices.company_id')->orderBy('companies.name', 'DESC');
          } elseif ($this->sort_by == 'due_date_asc') {
            $query->orderBy('due_date', 'ASC');
          } elseif ($this->sort_by == 'due_date_desc') {
            $query->orderBy('due_date', 'DESC');
          }
        })
        ->get();
    }

    if ($this->type == 'dealer') {
      return Invoice::dealerFinancing()
        ->where('company_id', $this->company->id)
        ->when($this->anchor && $this->anchor != '', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->whereHas('anchor', function ($query) {
              $query->where('name', 'LIKE', '%' . $this->anchor . '%');
            });
          });
        })
        ->when($this->vendor && $this->vendor != '', function ($query) {
          $query->whereHas('company', function ($query) {
            $query->where('name', 'LIKE', '%' . $this->vendor . '%');
          });
        })
        ->when($this->program && $this->program != '', function ($query) {
          $query->where('program_id', $this->program);
        })
        ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($this->invoice_number, '\\') . '%');
        })
        ->when($this->from_date && $this->from_date != '', function ($query) {
          $query->whereDate('invoice_date', '>=', $this->from_date);
        })
        ->when($this->to_date && $this->to_date != '', function ($query) {
          $query->whereDate('invoice_date', '<=', $this->to_date);
        })
        ->when($this->disbursement_date && $this->disbursement_date != '', function ($query) {
          $query->whereDate('disbursement_date', $this->disbursement_date);
        })
        ->when($this->pi_number && $this->pi_number != '', function ($query) {
          $query->where('pi_number', 'LIKE', '%' . $this->pi_number . '%');
        })
        ->when($this->status && count($this->status) > 0, function ($query) {
          $query->whereIn('stage', $this->status);
        })
        ->when(!$this->sort_by || $this->sort_by == '', function ($query) {
          $query->orderBy('updated_at', 'DESC');
        })
        ->when($this->financing_status && count($this->financing_status) > 0, function ($query) {
          $query->whereIn('financing_status', $this->financing_status);
        })
        ->get();
    }

    if ($this->type == 'buyer') {
      return Invoice::factoring()
        ->where('buyer_id', $this->company->id)
        ->when($this->anchor && $this->anchor != '', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->whereHas('anchor', function ($query) {
              $query->where('name', 'LIKE', '%' . $this->anchor . '%');
            });
          });
        })
        ->when($this->vendor && $this->vendor != '', function ($query) {
          $query->whereHas('company', function ($query) {
            $query->where('name', 'LIKE', '%' . $this->vendor . '%');
          });
        })
        ->when($this->program && $this->program != '', function ($query) {
          $query->where('program_id', $this->program);
        })
        ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
          $query->where('invoice_number', 'LIKE', '%' . $this->invoice_number . '%');
        })
        ->when($this->from_date && $this->from_date != '', function ($query) {
          $query->whereDate('due_date', '>=', $this->from_date);
        })
        ->when($this->to_date && $this->to_date != '', function ($query) {
          $query->whereDate('due_date', '<=', $this->to_date);
        })
        ->when($this->disbursement_date && $this->disbursement_date != '', function ($query) {
          $query->whereDate('disbursement_date', $this->disbursement_date);
        })
        ->when($this->pi_number && $this->pi_number != '', function ($query) {
          $query->where('pi_number', 'LIKE', '%' . $this->pi_number . '%');
        })
        ->when($this->status && count($this->status) > 0, function ($query) {
          $query->whereIn('stage', $this->status);
        })
        ->when($this->financing_status && count($this->financing_status) > 0, function ($query) {
          $query->whereIn('financing_status', $this->financing_status);
        })
        ->when(!$this->sort_by || $this->sort_by == '', function ($query) {
          $query->orderBy('updated_at', 'DESC');
        })
        ->when($this->sort_by && $this->sort_by != '', function ($query) {
          if ($this->sort_by == 'po_asc') {
            $query->whereHas('purchaseOrder', function ($query) {
              $query->orderBy('purchase_order_number', 'ASC');
            });
          } elseif ($this->sort_by == 'po_desc') {
            $query->whereHas('purchaseOrder', function ($query) {
              $query->orderBy('purchase_order_number', 'DESC');
            });
          } elseif ($this->sort_by == 'invoice_no_asc') {
            $query->orderBy('invoice_number', 'ASC');
          } elseif ($this->sort_by == 'invoice_no_desc') {
            $query->orderBy('invoice_number', 'DESC');
          } elseif ($this->sort_by == 'invoice_amount_asc') {
            $query->orderBy('total_amount', 'ASC');
          } elseif ($this->sort_by == 'invoice_amount_desc') {
            $query->orderBy('total_amount', 'DESC');
          } elseif ($this->sort_by == 'vendor_asc') {
            $query->join('companies', 'companies.id', '=', 'invoices.company_id')->orderBy('companies.name', 'ASC');
          } elseif ($this->sort_by == 'vendor_desc') {
            $query->join('companies', 'companies.id', '=', 'invoices.company_id')->orderBy('companies.name', 'DESC');
          } elseif ($this->sort_by == 'due_date_asc') {
            $query->orderBy('due_date', 'ASC');
          } elseif ($this->sort_by == 'due_date_desc') {
            $query->orderBy('due_date', 'DESC');
          }
        })
        ->get();
    }
  }

  public function headings(): array
  {
    if ($this->type === 'vendor' || $this->type === 'dealer') {
      return [
        'Anchor',
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
    if ($this->type === 'vendor' || $this->type === 'dealer' || $this->type === 'buyer') {
      return [
        $invoice->program->anchor->name,
        $invoice->invoice_number,
        number_format(
          $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount,
          2
        ),
        Carbon::parse($invoice->invoice_date)->format('d/m/Y'),
        Carbon::parse($invoice->due_date)->format('d/m/Y'),
        Str::title($invoice->approval_stage),
        Str::title($invoice->financing_status),
        $invoice->disbursement_date ? Carbon::parse($invoice->disbursement_date)->format('d/m/Y') : '-',
        number_format($invoice->disbursed_amount, 2),
      ];
    }

    return [
      $invoice->company->name,
      $invoice->invoice_number,
      number_format(
        $invoice->total +
          $invoice->total_invoice_taxes -
          $invoice->total_invoice_fees -
          $invoice->total_invoice_discount,
        2
      ),
      Carbon::parse($invoice->invoice_date)->format('d/m/Y'),
      Carbon::parse($invoice->due_date)->format('d/m/Y'),
      Str::title($invoice->approval_stage),
      Str::title($invoice->financing_status),
      $invoice->disbursement_date ? Carbon::parse($invoice->disbursement_date)->format('d/m/Y') : '-',
      number_format($invoice->disbursed_amount, 2),
    ];
  }
}
