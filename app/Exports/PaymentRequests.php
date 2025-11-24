<?php

namespace App\Exports;

use App\Http\Resources\CbsTransactionResource;
use App\Http\Resources\PaymentRequestAccountResource;
use Carbon\Carbon;
use App\Models\Bank;
use Illuminate\Support\Str;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PaymentRequests implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
  public $debit_from = '';
  public $company_name = '';
  public $from_date = '';
  public $to_date = '';
  public $status = [];
  public $sort_by = '';
  public $invoice_number = '';
  public $pi_number = '';
  public $pay_date = '';
  public $product_type = '';
  public $selected_transactions = [];

  public function __construct(
    public Bank $bank,
    $debit_from = '',
    $company_name = '',
    $from_date = '',
    $to_date = '',
    $status = [],
    $sort_by = '',
    $invoice_number = '',
    $pi_number = '',
    $pay_date = '',
    $product_type = '',
    $selected_transactions = []
  ) {
    $this->debit_from = $debit_from;
    $this->company_name = $company_name;
    $this->from_date = $from_date;
    $this->to_date = $to_date;
    $this->status = $status;
    $this->sort_by = $sort_by;
    $this->invoice_number = $invoice_number;
    $this->pi_number = $pi_number;
    $this->pay_date = $pay_date;
    $this->product_type = $product_type;
    $this->selected_transactions = $selected_transactions;
  }

  public function headings(): array
  {
    return [
      'PR ID',
      'Debit From',
      'Credit To',
      'Amount',
      'Buyer',
      'Vendor',
      'Invoice/Unique Ref Number',
      'PI No.',
      'Pay Date',
      'Paid Date',
      'Status',
      'Transaction Reference',
      'Created By',
      'Created At',
      'Last Updated By',
      'Last Updated At',
    ];
  }

  public function map($row): array
  {
    if ($row->paymentRequest->invoice->program->programType->name === Program::DEALER_FINANCING) {
      $anchor = $row->paymentRequest->invoice->program->anchor->name;
      $vendor = $row->paymentRequest->invoice->company->name;
    } else {
      if ($row->paymentRequest->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $anchor = $row->paymentRequest->invoice->program->anchor->name;
        $vendor = $row->paymentRequest->invoice->company->name;
      } else {
        $vendor = $row->paymentRequest->invoice->buyer->name;
        $anchor = $row->paymentRequest->invoice->company->name;
      }
    }

    return [
      $row->paymentRequest->id,
      $row->debit_from_account_description ? $row->debit_from_account_description : $row->debit_from_account,
      $row->credit_to_account_description ? $row->credit_to_account_description : $row->credit_to_account,
      number_format($row->amount, 2),
      $anchor,
      $vendor,
      $row->paymentRequest->invoice->invoice_number,
      $row->paymentRequest->invoice->pi_number,
      Carbon::parse($row->paymentRequest->payment_request_date)->format('d/m/Y'),
      $row->pay_date ? Carbon::parse($row->pay_date)->format('d/m/Y') : '',
      $row->paymentRequest->status == 'approved'
        ? Str::headline('created')
        : Str::headline($row->paymentRequest->status),
      $row->transaction_reference ? $row->transaction_reference : '-',
      $row->paymentRequest->created_by ? $row->paymentRequest->createdBy->name : '-',
      $row->created_at->format('d/m/Y H:i A'),
      $row->paymentRequest->updated_by ? $row->paymentRequest->updatedBy->name : '-',
      $row->updated_at->format('d/m/Y H:i A'),
    ];
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    return CbsTransactionResource::collection(
      CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query
            ->whereHas('program', function ($query) {
              $query
                ->where('bank_id', $this->bank->id)
                ->when($this->product_type && $this->product_type != '', function ($query) {
                  switch ($this->product_type) {
                    case 'vendor_financing_receivable':
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
                      });
                      break;
                    case 'factoring_with_recourse':
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::FACTORING_WITH_RECOURSE);
                      });
                      break;
                    case 'factoring_without_recourse':
                      $query->whereHas('programCode', function ($query) {
                        $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
                      });
                      break;
                    case 'dealer_financing':
                      $query->whereHas('programType', function ($query) {
                        $query->where('name', Program::DEALER_FINANCING);
                      });
                      break;
                    default:
                      break;
                  }
                  // $query->whereHas('programType', function ($query) {
                  //   $query->where('name', $this->product_type);
                  // });
                });
            })
            ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
              $query->where('invoice_number', 'LIKE', '%' . addcslashes($this->invoice_number, '\\') . '%');
            })
            ->when($this->pi_number && $this->pi_number != '', function ($query) {
              $query->where('pi_number', 'LIKE', '%' . $this->pi_number . '%');
            });
        });
      })
        ->when($this->debit_from && $this->debit_from != '', function ($query) {
          $query->where('account', 'LIKE', '%' . $this->debit_from . '%');
        })
        ->when($this->company_name && $this->company_name != '', function ($query) {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('company', function ($query) {
                $query->where('name', 'LIKE', '%' . $this->company_name . '%');
              });
            });
          });
        })
        ->when($this->from_date && $this->from_date != '', function ($query) {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereDate('payment_request_date', '>=', $this->from_date);
          });
        })
        ->when($this->to_date && $this->to_date != '', function ($query) {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereDate('payment_request_date', '<=', $this->to_date);
          });
        })
        ->when($this->status && count($this->status) > 0, function ($query) {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereIn('status', $this->status);
          });
        })
        ->when(!$this->status || count($this->status) == 0, function ($query) {
          $query->whereHas('paymentRequest', function ($query) {
            $query->whereIn('status', ['approved', 'paid']);
          });
        })
        ->when($this->pay_date && $this->pay_date != '', function ($query) {
          $query->where('payment_request_date', $this->pay_date);
        })
        ->when($this->sort_by && $this->sort_by != '', function ($query) {
          if ($this->sort_by == 'pi_no_asc') {
            $query->whereHas('paymentRequest', function ($query) {
              $query->whereHas('invoice', function ($query) {
                $query->orderBy('pi_number', 'ASC');
              });
            });
          }
          if ($this->sort_by == 'pi_no_desc') {
            $query->whereHas('paymentRequest', function ($query) {
              $query->whereHas('invoice', function ($query) {
                $query->orderBy('pi_number', 'DESC');
              });
            });
          }
          if ($this->sort_by == 'amount_asc') {
            $query->whereHas('paymentRequest', function ($query) {
              $query->orderBy('amount', 'ASC');
            });
          }
          if ($this->sort_by == 'amount_desc') {
            $query->whereHas('paymentRequest', function ($query) {
              $query->orderBy('amount', 'DESC');
            });
          }
          if ($this->sort_by == 'debit_from_asc') {
            $query->orderBy('debit_from_account', 'ASC');
          }
          if ($this->sort_by == 'debit_from_desc') {
            $query->orderBy('debit_from_account', 'DESC');
          }
          if ($this->sort_by == 'id_asc') {
            $query->orderBy('id', 'ASC');
          }
          if ($this->sort_by == 'id_desc') {
            $query->orderBy('id', 'DESC');
          }
        })
        ->when(!$this->sort_by || ($this->sort_by && $this->sort_by == ''), function ($query) {
          $query->orderBy('cbs_id', 'DESC');
        })
        ->get()
    );
  }
}
