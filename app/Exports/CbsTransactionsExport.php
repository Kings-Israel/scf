<?php

namespace App\Exports;

use App\Models\Bank;
use App\Models\CbsTransaction;
use App\Models\Program;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CbsTransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
  public $cbs_id = '';
  public $invoice_number = '';
  public $transaction_ref = '';
  public $account = '';
  public $from_date = '';
  public $to_date = '';
  public $from_transaction_date = '';
  public $to_transaction_date = '';
  public $status = '';
  public $sort_by = '';
  public $product_type = '';
  public $transaction_type = '';

  public $selected_transactions = [];

  public function __construct(
    public Bank $bank,
    $cbs_id = '',
    $invoice_number = '',
    $transaction_ref = '',
    $account = '',
    $status = '',
    $product_type = '',
    $from_date = '',
    $to_date = '',
    $from_transaction_date = '',
    $to_transaction_date = '',
    $sort_by = '',
    $transaction_type = '',
    $selected_transactions = []
  ) {
    $this->cbs_id = $cbs_id;
    $this->invoice_number = $invoice_number;
    $this->transaction_ref = $transaction_ref;
    $this->account = $account;
    $this->from_date = $from_date;
    $this->to_date = $to_date;
    $this->from_transaction_date = $from_transaction_date;
    $this->to_transaction_date = $to_transaction_date;
    $this->status = $status;
    $this->sort_by = $sort_by;
    $this->product_type = $product_type;
    $this->transaction_type = $transaction_type;
    $this->selected_transactions = $selected_transactions;
  }

  public function headings(): array
  {
    return [
      'CBS ID',
      'Debit From A/C No',
      'Debit From A/C Name',
      'Credit To A/C No',
      'Credit To A/C Name',
      'Amount (Ksh)',
      'Invoice / Unique Ref No',
      'Transaction Created Date (dd/mm/yyyy)',
      'Pay Date (dd/mm/yyyy)',
      'Transaction Date (dd/mm/yyyy) *',
      'Transaction Reference No. *',
      'Status (Created/Successful/Failed/Permanently Failed) *',
      'Transaction Type',
      'Product',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    // // Make sure you enable worksheet protection if you need any of the worksheet or cell protection features!
    // $sheet
    //   ->getParent()
    //   ->getActiveSheet()
    //   ->getProtection()
    //   ->setSheet(true);

    // // lock all cells then unlock the cell
    // $sheet
    //   ->getParent()
    //   ->getActiveSheet()
    //   ->getStyle('H:L')
    //   ->getProtection()
    //   ->setLocked(Protection::PROTECTION_UNPROTECTED);
  }

  public function map($row): array
  {
    return [
      $row->id,
      $row->debit_from_account,
      $row->debit_from_account_name,
      $row->credit_to_account,
      $row->credit_to_account_name,
      $row->amount,
      $row->paymentRequest?->invoice?->invoice_number,
      Carbon::parse($row->transaction_created_date)->format('d/m/Y'),
      $row->pay_date
        ? Carbon::parse($row->pay_date)->format('d/m/Y')
        : Carbon::parse($row->paymentRequest?->payment_request_date)->format('d/m/Y'),
      $row->transaction_date
        ? Carbon::parse($row->transaction_date)->format('d/m/Y')
        : Carbon::parse($row->paymentRequest?->payment_request_date)->format('d/m/Y'),
      $row->transaction_reference,
      $row->status,
      $row->transaction_type,
      $row->product,
    ];
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    $cbs_transactions = CbsTransaction::where('bank_id', $this->bank->id)
      ->when($this->cbs_id && $this->cbs_id != '', function ($query) {
        $query->where('id', $this->cbs_id);
      })
      ->when($this->product_type && $this->product_type != '', function ($query) {
        $query->whereHas('paymentRequest', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query->whereHas('program', function ($query) {
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
            });
          });
        });
      })
      // ->when($this->product_type && $this->product_type != '', function ($query) {
      //   $query->where('product', $this->product_type);
      // })
      ->when($this->account && $this->account != '', function ($query) {
        $query->where(function ($query) {
          $query
            ->where('debit_from_account', 'LIKE', '%' . $this->account . '%')
            ->orWhere('credit_to_account', 'LIKE', '%' . $this->account . '%')
            ->orWhere('debit_from_account_name', 'LIKE', '%' . $this->account . '%')
            ->orWhere('credit_to_account_name', 'LIKE', '%' . $this->account . '%');
        });
      })
      ->when($this->status && count($this->status) > 0, function ($query) {
        $query->whereIn('status', $this->status);
      })
      ->when($this->invoice_number && $this->invoice_number != '', function ($query) {
        $query->whereHas('paymentRequest', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($this->invoice_number, '\\') . '%');
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
      ->when($this->from_transaction_date && $this->from_transaction_date != '', function ($query) {
        $query->whereDate('transaction_date', '>=', $this->from_transaction_date);
      })
      ->when($this->to_transaction_date && $this->to_transaction_date != '', function ($query) {
        $query->whereDate('transaction_date', '<=', $this->to_transaction_date);
      })
      ->when(
        $this->transaction_type &&
          count(array_filter($this->transaction_type, fn($value) => !is_null($value) && $value !== '')) > 0,
        function ($query) {
          $query->whereIn('transaction_type', $this->transaction_type);
        }
      )
      ->when($this->selected_transactions && !empty(collect($this->selected_transactions)), function ($query) {
        $query->whereIn('id', $this->selected_transactions);
      })
      // ->when(
      //   $this->product_type == '' &&
      //     $this->account == '' &&
      //     $this->invoice_number == '' &&
      //     (!$this->status || count($this->status) == 0),
      //   function ($query) {
      //     $query->whereIn('status', ['Created']);
      //   }
      // )
      ->when($this->sort_by && $this->sort_by != '', function ($query) {
        $query->orderBy('id', $this->sort_by);
      })
      ->when(!$this->sort_by || $this->sort_by == '', function ($query) {
        $query->orderBy('id', 'DESC');
      });

    return $cbs_transactions->get();
  }
}
