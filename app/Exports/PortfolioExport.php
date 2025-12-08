<?php

namespace App\Exports;

use App\Http\Resources\PaymentRequestResource;
use App\Models\Bank;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorDiscount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PortfolioExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
  public $debit_from = '';
  public $reference_number = '';
  public $invoice_number = '';
  public $vendor = '';
  public $anchor = '';
  public $status = [];
  public $sort_by = '';
  public $request_from_date = '';
  public $request_to_date = '';
  public $selected_transactions = [];

  public function __construct(
    public Bank $bank,
    public $type,
    $reference_number = '',
    $invoice_number = '',
    $vendor = '',
    $anchor = '',
    $status = [],
    $sort_by = '',
    $request_from_date = '',
    $request_to_date = '',
    $selected_transactions = []
  ) {
    $this->debit_from = $reference_number;
    $this->invoice_number = $invoice_number;
    $this->vendor = $vendor;
    $this->anchor = $anchor;
    $this->status = $status;
    $this->sort_by = $sort_by;
    $this->request_from_date = $request_from_date;
    $this->request_to_date = $request_to_date;
    $this->selected_transactions = $selected_transactions;
  }

  public function headings(): array
  {
    return [
      'Payment Reference No.',
      'Invoice No.',
      'Vendor',
      'Anchor',
      'PI Amount',
      'Eligibility(%)',
      'Eligible Payment Amount',
      'Requested Payment',
      'Request Date',
      'Requested Disbursement Date',
      'Due Date',
      'Discount Rate',
      'Approved By / Rejected By',
      'Rejection Remark',
      'Status',
      'Product Code',
      'Created By',
      'Created At',
      'Last Updated By',
      'Last Updated At',
    ];
  }

  public function map($row): array
  {
    if ($row->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($row->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'program_id',
          'company_id',
          'buyer_id',
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('company_id', $row->invoice->company_id)
          ->where('program_id', $row->invoice->program_id)
          ->first();
      } else {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'program_id',
          'company_id',
          'buyer_id',
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('buyer_id', $row->invoice->buyer_id)
          ->where('program_id', $row->invoice->program_id)
          ->first();
      }
    } else {
      $vendor_discount_details = ProgramVendorDiscount::select(
        'program_id',
        'company_id',
        'buyer_id',
        'total_roi',
        'vendor_discount_bearing',
        'anchor_discount_bearing'
      )
        ->where('company_id', $row->invoice->company_id)
        ->where('program_id', $row->invoice->program_id)
        ->first();
    }

    $status = $row->approval_stage;

    if ($row->approval_stage === 'Paid') {
      $status = 'disbursed';
    }

    if ($row->invoice->financing_status === 'closed') {
      $status = 'closed';
    }

    return [
      $row->reference_number,
      $row->invoice->invoice_number,
      $row->invoice->buyer ? $row->invoice->program->anchor->name : $row->invoice->company->name,
      $row->invoice->buyer ? $row->invoice->buyer->name : $row->invoice->program->anchor->name,
      number_format($row->invoice->invoice_total_amount, 2),
      $row->invoice->eligibility,
      number_format($row->invoice->eligible_for_finance, 2),
      number_format($row->amount, 2),
      $row->created_at->format('d/m/Y'),
      Carbon::parse($row->payment_request_date)->format('d/m/Y'),
      Carbon::parse($row->invoice->due_date)->format('d/m/Y'),
      $vendor_discount_details->total_roi .
      '% (Anchor Bearing: ' .
      $vendor_discount_details->anchor_discount_bearing .
      '%, Vendor Bearing: ' .
      $vendor_discount_details->vendor_discount_bearing .
      '%',
      $row->approvals->count() > 0 ? $row->approvals->sortByDesc('created_at')->first()->user->name : '-',
      $row->rejected_reason,
      Str::headline($status),
      $row->invoice->program->programType->name == Program::VENDOR_FINANCING ? 'VF' : 'DF',
      $row->createdBy ? $row->createdBy->name : 'System Created',
      $row->created_at->format('d/m/Y H:i A'),
      $row->updatedBy ? $row->updatedBy->name : '-',
      $row->updated_at->format('d/m/Y H:i A'),
    ];
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    return PaymentRequestResource::collection(
      PaymentRequest::with(
        'invoice.program.programType',
        'invoice.invoiceItems',
        'invoice.invoiceFees',
        'invoice.invoiceTaxes',
        'invoice.company',
        'invoice.program.anchor',
        'invoice.buyer',
        'paymentAccounts',
        'approvals.user',
        'createdBy',
        'updatedBy'
      )
        ->whereDoesntHave('companyApprovals')
        ->whereHas('invoice', function ($query) {
          $query
            ->whereHas('program', function ($query) {
              if ($this->type == Program::DEALER_FINANCING) {
                $query->whereHas('programType', function ($query) {
                  $query->where('name', $this->type);
                });
              } else {
                if ($this->type == Program::VENDOR_FINANCING_RECEIVABLE) {
                  $query->whereHas('programCode', function ($query) {
                    $query->where('name', $this->type);
                  });
                } else {
                  $query->whereHas('programCode', function ($query) {
                    $query
                      ->where('name', Program::FACTORING_WITH_RECOURSE)
                      ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
                  });
                }
              }
            })
            ->whereHas('company', function ($query) {
              $query->where('bank_id', $this->bank->id);
            });
        })
        ->where(function ($query) {
          $query
            ->whereHas('cbsTransactions', function ($query) {
              $query->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT);
            })
            ->orWhereDoesntHave('cbsTransactions');
        })
        ->when($this->reference_number && $this->reference_number != '', function ($query) {
          $query->where('reference_number', 'LIKE', '%' . $this->reference_number . '%');
        })
        ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query->where('invoice_number', 'LIKE', '%' . $this->invoice_number . '%');
          });
        })
        ->when($this->vendor && $this->vendor != '', function ($query) {
          if ($this->type == Program::DEALER_FINANCING) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('company', function ($query) {
                $query->where('name', 'LIKE', '%' . $this->vendor . '%');
              });
            });
          } else {
            if ($this->type == Program::VENDOR_FINANCING_RECEIVABLE) {
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('company', function ($query) {
                  $query->where('name', 'LIKE', '%' . $this->vendor . '%');
                });
              });
            } else {
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('program', function ($query) {
                  $query->whereHas('anchor', function ($query) {
                    $query->where('name', 'LIKE', '%' . $this->vendor . '%');
                  });
                });
              });
            }
          }
        })
        ->when($this->anchor && $this->anchor != '', function ($query) {
          if ($this->type == Program::DEALER_FINANCING) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('program', function ($query) {
                $query->whereHas('anchor', function ($query) {
                  $query->where('name', 'LIKE', '%' . $this->anchor . '%');
                });
              });
            });
          } else {
            if ($this->type == Program::VENDOR_FINANCING_RECEIVABLE) {
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('program', function ($query) {
                  $query->whereHas('anchor', function ($query) {
                    $query->where('name', 'LIKE', '%' . $this->anchor . '%');
                  });
                });
              });
            } else {
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('buyer', function ($query) {
                  $query->where('name', 'LIKE', '%' . $this->anchor . '%');
                });
              });
            }
          }
        })
        ->when($this->sort_by && $this->sort_by != '', function ($query) {
          switch ($this->sort_by) {
            case 'invoice_no_asc':
              $query->whereHas('invoice', function ($query) {
                $query->orderBy('invoice_number', 'ASC');
              });
              break;
            case 'invoice_no_desc':
              $query->whereHas('invoice', function ($query) {
                $query->orderBy('invoice_number', 'ASC');
              });
              break;
            case 'pi_amount_asc':
              $query->orderBy('amount', 'ASC');
              break;
            case 'pi_amount_desc':
              $query->orderBy('amount', 'DESC');
              break;
            case 'vendor_asc':
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('company', function ($query) {
                  $query->orderBy('name', 'ASC');
                });
              });
              break;
            case 'vendor_desc':
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('company', function ($query) {
                  $query->orderBy('name', 'DESC');
                });
              });
              break;
            case 'anchor_asc':
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('program', function ($query) {
                  $query->whereHas('anchor', function ($query) {
                    $query->orderBy('name', 'ASC');
                  });
                });
              });
              break;
            case 'anchor_desc':
              $query->whereHas('invoice', function ($query) {
                $query->whereHas('program', function ($query) {
                  $query->whereHas('anchor', function ($query) {
                    $query->orderBy('name', 'DESC');
                  });
                });
              });
              break;
            case 'request_date_asc':
              $query->orderBy('created_at', 'ASC');
              break;
            case 'request_date_desc':
              $query->orderBy('created_at', 'DESC');
              break;
            case 'due_date_asc':
              $query->whereHas('invoice', function ($query) {
                $query->orderBy('due_date', 'DESC');
              });
              break;
            case 'due_date_desc':
              $query->whereHas('invoice', function ($query) {
                $query->orderBy('due_date', 'DESC');
              });
              break;
          }
        })
        ->when($this->request_from_date && $this->request_from_date != '', function ($query) {
          $query->whereDate('created_at', '>=', $this->request_from_date);
        })
        ->when($this->request_to_date && $this->request_to_date != '', function ($query) {
          $query->whereDate('created_at', '<=', $this->request_to_date);
        })
        // ->when($this->status && count($this->status) > 0, function ($query) {
        //   if (collect($this->status)->contains('closed')) {
        //     $query
        //       ->whereHas('invoice', function ($query) {
        //         $query->where('financing_status', 'closed');
        //       })
        //       ->whereIn('approval_status', collect($this->status)->toArray());
        //   } else {
        //     $query->whereHas('invoice', function ($query) {
        //       $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($this->status)->toArray());
        //     });
        //   }
        // })
        ->when($this->status && count($this->status) > 0, function ($query) {
          if (collect($this->status)->contains('closed')) {
            $query->whereHas('invoice', function ($query) {
              $status = collect($this->status)->filter(fn($s) => $s != 'closed');
              if (count($status) === 0) {
                $query->where('financing_status', 'closed');
              } else {
                $query->where('financing_status', 'closed')->orWhereIn('approval_status', collect($status)->toArray());
              }
            });
          }
        })
        ->when($this->status && count($this->status) > 0, function ($query) {
          if (!collect($this->status)->contains('closed')) {
            $query->whereHas('invoice', function ($query) {
              $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($this->status)->toArray());
            });
          }
        })
        ->when(!$this->sort_by || $this->sort_by == '', function ($query) {
          $query->orderBy('reference_number', 'DESC');
        })
        ->get()
    );
  }
}
