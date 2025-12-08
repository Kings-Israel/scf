<?php

namespace App\Http\Controllers;

use App\Enums\PaymentRequestStatus;
use App\Exceptions\LimitExceeded;
use App\Exceptions\NegativePipelineAmount;
use Carbon\Carbon;
use App\Models\Bank;
use App\Jobs\SendMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\BankUser;
use App\Models\ImportError;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use Illuminate\Http\Request;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Exports\CbsErrorReport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PaymentRequests;
use App\Models\ProgramVendorFee;
use App\Models\ProgramBankDetails;
use Illuminate\Support\Facades\DB;
use App\Models\CreditAccountRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProgramVendorDiscount;
use App\Exports\CbsTransactionsExport;
use App\Imports\CbsTransactionsImport;
use App\Imports\PaymentRequestsImport;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\BankProductsConfiguration;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentRequestUploadReport;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Exports\PaymentRequestsErrorReport;
use App\Exports\PortfolioExport;
use App\Notifications\FinanceRequestUpdated;
use App\Notifications\ProgramLimitDepletion;
use App\Http\Resources\CbsTransactionResource;
use App\Http\Resources\InvoiceDetailsResource;
use App\Http\Resources\PaymentRequestResource;
use App\Imports\CbsTransactionsImportV2;
use App\Jobs\CreateCbsTransactions;
use App\Jobs\LoanClosingNotification;
use App\Jobs\LoanDisbursalNotification;
use App\Jobs\PaymentRequestRejectionNotification;
use App\Models\BankGeneralProductConfiguration;
use App\Models\BankProductRepaymentPriority;
use App\Models\Company;
use App\Models\CronLog;
use App\Models\PaymentRequestAccount;
use App\Models\Program;
use App\Models\ProgramDiscount;
use App\Models\PurchaseOrder;
use App\Services\InvoiceApprovalService;
use Illuminate\Support\Facades\Log;

class RequestsController extends Controller
{
  protected InvoiceApprovalService $invoiceApprovalService;

  public function __construct(InvoiceApprovalService $invoiceApprovalService)
  {
    $this->invoiceApprovalService = $invoiceApprovalService;
  }

  public function reverseFactoring(Bank $bank, Request $request)
  {
    $status = $request->query('status_search');
    $period = $request->query('period');

    return view('content.bank.requests.vendor-financing', [
      'params' => [
        'status' => $status,
        'period' => $period,
      ],
    ]);
  }

  public function factoring(Bank $bank, Request $request)
  {
    $status = $request->query('status_search');
    $period = $request->query('period');

    return view('content.bank.requests.factoring', [
      'params' => [
        'status' => $status,
        'period' => $period,
      ],
    ]);
  }

  public function dealerFinancing(Bank $bank, Request $request)
  {
    $status = $request->query('status_search');
    $period = $request->query('period');

    return view('content.bank.requests.dealer-financing', [
      'params' => [
        'status' => $status,
        'period' => $period,
      ],
    ]);
  }

  public function requests(Bank $bank)
  {
    $payment_requests = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company', 'paymentAccounts')
      ->whereHas('invoice', function ($query) use ($bank) {
        $query->whereHas('company', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id);
        });
      })
      ->whereDoesntHave('companyApprovals')
      ->whereIn('status', ['approved', 'paid', 'failed'])
      ->count();

    return view('content.bank.requests.payment-requests', [
      'payment_requests' => $payment_requests,
    ]);
  }

  public function vendorFinancingRequestsData(Bank $bank, Request $request)
  {
    $payment_reference_number = $request->query('payment_reference_number');
    $invoice_number = $request->query('invoice_number');
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $sort_by = $request->query('sort_by');
    $from_request_date = $request->query('from_request_date');
    $to_request_date = $request->query('to_request_date');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $type = $request->query('type');

    $payment_requests = PaymentRequest::with('paymentAccounts', 'approvals.user')
      // Filter to not show transactions for anchor bearing fees and discount
      ->where('processing_fee', '!=', null)
      ->whereDoesntHave('companyApprovals')
      ->whereHas('invoice', function ($query) use ($bank) {
        $query
          ->whereHas('program', function ($query) {
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
          })
          ->whereHas('company', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          });
      })
      ->when($payment_reference_number && $payment_reference_number != '', function ($query) use (
        $payment_reference_number
      ) {
        $query->where('reference_number', 'LIKE', '%' . $payment_reference_number . '%');
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('invoice', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        });
      })
      ->when($from_request_date && $from_request_date != '', function ($query) use ($from_request_date) {
        $query->whereDate('created_at', '>=', $from_request_date);
      })
      ->when($to_request_date && $to_request_date != '', function ($query) use ($to_request_date) {
        $query->whereDate('created_at', '<=', $to_request_date);
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        if (collect($status)->contains('closed')) {
          $query->whereHas('invoice', function ($query) use ($status) {
            $status = collect($status)->filter(fn($s) => $s != 'closed');
            if (count($status) === 0) {
              $query->where('financing_status', 'closed');
            } else {
              $query->where('financing_status', 'closed')->orWhereIn('approval_status', collect($status)->toArray());
            }
          });
        }
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        if (!collect($status)->contains('closed')) {
          $query->whereHas('invoice', function ($query) use ($status) {
            $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($status)->toArray());
          });
        }
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        switch ($sort_by) {
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
              $query->orderBy('due_date', 'ASC');
            });
            break;
        }
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('reference_number', 'DESC');
      })
      ->when($type && $type != '', function ($query) use ($type) {
        if ($type == 'dashboard') {
          // Load Dashboard Data
          $query->whereDoesntHave('cbsTransactions')->whereHas('invoice', function ($query) {
            $query->whereNotIn('approval_status', ['rejected'])->whereDate('due_date', '>=', now());
          });
        }
      })
      ->paginate($per_page);

    $payment_requests = PaymentRequestResource::collection($payment_requests)
      ->response()
      ->getData();

    $rejection_reasons = $bank->rejectionReasons;

    if (request()->wantsJson()) {
      return response()->json(['payment_requests' => $payment_requests, 'rejection_reasons' => $rejection_reasons]);
    }
  }

  public function factoringRequestsData(Bank $bank, Request $request)
  {
    $payment_reference_number = $request->query('payment_reference_number');
    $invoice_number = $request->query('invoice_number');
    $from_request_date = $request->query('from_request_date');
    $to_request_date = $request->query('to_request_date');
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $sort_by = $request->query('sort_by');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $type = $request->query('type');

    $payment_requests = PaymentRequest::with('paymentAccounts', 'approvals.user')
      // Filter to not show transactions for anchor bearing fees and discount
      ->where('processing_fee', '!=', null)
      ->whereDoesntHave('companyApprovals')
      ->whereHas('invoice', function ($query) use ($bank) {
        $query
          ->whereHas('program', function ($query) {
            $query->whereHas('programCode', function ($query) {
              $query
                ->where('name', Program::FACTORING_WITH_RECOURSE)
                ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
          })
          ->whereHas('company', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          });
      })
      ->when($payment_reference_number && $payment_reference_number != '', function ($query) use (
        $payment_reference_number
      ) {
        $query->where('reference_number', 'LIKE', '%' . $payment_reference_number . '%');
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('buyer', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('invoice', function ($query) use ($anchor) {
          $query->whereHas('company', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->when($from_request_date && $from_request_date != '', function ($query) use ($from_request_date) {
        $query->whereDate('created_at', '>=', $from_request_date);
      })
      ->when($to_request_date && $to_request_date != '', function ($query) use ($to_request_date) {
        $query->whereDate('created_at', '<=', $to_request_date);
      })
      // ->when($status && count($status) > 0, function ($query) use ($status) {
      //   if (collect($status)->contains('closed')) {
      //     $query
      //       ->whereHas('invoice', function ($query) use ($status) {
      //         $query->where('financing_status', 'closed');
      //       })
      //       ->whereIn('approval_status', collect($status)->toArray());
      //   } else {
      //     $query->whereHas('invoice', function ($query) use ($status) {
      //       $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($status)->toArray());
      //     });
      //   }
      // })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        if (collect($status)->contains('closed')) {
          $query->whereHas('invoice', function ($query) use ($status) {
            $status = collect($status)->filter(fn($s) => $s != 'closed');
            if (count($status) === 0) {
              $query->where('financing_status', 'closed');
            } else {
              $query->where('financing_status', 'closed')->orWhereIn('approval_status', collect($status)->toArray());
            }
          });
        }
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        if (!collect($status)->contains('closed')) {
          $query->whereHas('invoice', function ($query) use ($status) {
            $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($status)->toArray());
          });
        }
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('reference_number', 'DESC');
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        switch ($sort_by) {
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
          case 'anchor_asc':
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('buyer', function ($query) {
                $query->orderBy('name', 'ASC');
              });
            });
            break;
          case 'anchor_desc':
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('buyer', function ($query) {
                $query->orderBy('name', 'DESC');
              });
            });
            break;
          case 'vendor_asc':
            $query->whereHas('invoice', function ($query) {
              $query->whereHas('program', function ($query) {
                $query->whereHas('anchor', function ($query) {
                  $query->orderBy('name', 'ASC');
                });
              });
            });
            break;
          case 'vendor_desc':
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
      ->when($type && $type != '', function ($query) use ($type) {
        // Load dashboard data
        if ($type == 'dashboard') {
          $query->whereDoesntHave('cbsTransactions')->whereHas('invoice', function ($query) {
            $query->whereNotIn('approval_status', ['rejected'])->whereDate('due_date', '>=', now());
          });
        }
      })
      ->paginate($per_page);

    $payment_requests = PaymentRequestResource::collection($payment_requests)
      ->response()
      ->getData();

    $rejection_reasons = $bank->rejectionReasons;

    if (request()->wantsJson()) {
      return response()->json(['payment_requests' => $payment_requests, 'rejection_reasons' => $rejection_reasons]);
    }
  }

  public function dealerFinancingRequestsData(Bank $bank, Request $request)
  {
    $payment_reference_number = $request->query('payment_reference_number');
    $invoice_number = $request->query('invoice_number');
    $from_request_date = $request->query('from_request_date');
    $to_request_date = $request->query('to_request_date');
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $sort_by = $request->query('sort_by');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $type = $request->query('type');

    $payment_requests = PaymentRequest::with('invoice', 'paymentAccounts', 'approvals.user')
      // Filter to not show transactions for anchor bearing fees and discount
      ->where('processing_fee', '!=', null)
      ->whereDoesntHave('companyApprovals')
      ->whereHas('invoice', function ($query) use ($bank) {
        $query
          ->whereHas('program', function ($query) {
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
          })
          ->whereHas('company', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          });
      })
      ->when($payment_reference_number && $payment_reference_number != '', function ($query) use (
        $payment_reference_number
      ) {
        $query->where('reference_number', 'LIKE', '%' . $payment_reference_number . '%');
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('invoice', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        });
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('invoice', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        });
      })
      // ->when($status && count($status) > 0, function ($query) use ($status) {
      //   if (collect($status)->contains('closed')) {
      //     $query
      //       ->whereHas('invoice', function ($query) use ($status) {
      //         $query->where('financing_status', 'closed');
      //       })
      //       ->whereIn('approval_status', collect($status)->toArray());
      //   } else {
      //     $query->whereHas('invoice', function ($query) use ($status) {
      //       $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($status)->toArray());
      //     });
      //   }
      // })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        if (collect($status)->contains('closed')) {
          $query->whereHas('invoice', function ($query) use ($status) {
            $status = collect($status)->filter(fn($s) => $s != 'closed');
            if (count($status) === 0) {
              $query->where('financing_status', 'closed');
            } else {
              $query->where('financing_status', 'closed')->orWhereIn('approval_status', collect($status)->toArray());
            }
          });
        }
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        if (!collect($status)->contains('closed')) {
          $query->whereHas('invoice', function ($query) use ($status) {
            $query->whereDate('due_date', '>', now())->whereIn('approval_status', collect($status)->toArray());
          });
        }
      })
      ->when($from_request_date && $from_request_date != '', function ($query) use ($from_request_date) {
        $query->whereDate('created_at', '>=', $from_request_date);
      })
      ->when($to_request_date && $to_request_date != '', function ($query) use ($to_request_date) {
        $query->whereDate('created_at', '<=', $to_request_date);
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        switch ($sort_by) {
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
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('reference_number', 'DESC');
      })
      ->when($type && $type != '', function ($query) use ($type) {
        if ($type == 'dashboard') {
          // Load dashboard data
          $query->whereDoesntHave('cbsTransactions')->whereHas('invoice', function ($query) {
            $query->whereNotIn('approval_status', ['rejected'])->whereDate('due_date', '>=', now());
          });
        }
      })
      ->paginate($per_page);

    $payment_requests = PaymentRequestResource::collection($payment_requests)
      ->response()
      ->getData();

    $rejection_reasons = $bank->rejectionReasons;

    if (request()->wantsJson()) {
      return response()->json(['payment_requests' => $payment_requests, 'rejection_reasons' => $rejection_reasons]);
    }
  }

  public function exportPortfolio(Bank $bank, Request $request)
  {
    $date = now()->format('Y-m-d');
    $type = $request->query('type');
    $transaction_ref = $request->query('transaction_ref');
    $invoice_number = $request->query('invoice_number');
    $vendor = $request->query('vendor');
    $anchor = $request->query('anchor');
    $status = $request->query('status');
    $from_request_date = $request->query('from_request_date');
    $to_request_date = $request->query('to_request_date');
    $sort_by = $request->query('sort_by');
    $selected_transactions = $request->query('selected_transactions');

    Excel::store(
      new PortfolioExport(
        $bank,
        $type,
        $transaction_ref,
        $invoice_number,
        $vendor,
        $anchor,
        $status,
        $sort_by,
        $from_request_date,
        $to_request_date,
        $selected_transactions
      ),
      'Payment_Requests_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Payment_Requests_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function financingRequestsData(Bank $bank, Request $request)
  {
    $program_type = $request->query('type');
    if ($program_type) {
      switch ($program_type) {
        case 'dealer_financing':
          $payment_requests = PaymentRequest::with(
            'invoice.program.programType',
            'invoice.program.bankDetails',
            'invoice.program.vendorConfigurations',
            'invoice.program.vendorDiscountDetails',
            'invoice.invoiceItems',
            'invoice.invoiceFees',
            'invoice.invoiceTaxes',
            'invoice.company',
            'paymentAccounts.paymentRequest.invoice',
            'cbsTransactions'
          )
            ->whereHas('invoice', function ($query) use ($bank) {
              $query
                ->whereHas('program', function ($query) {
                  $query->whereHas('programType', function ($query) {
                    $query->where('name', Program::DEALER_FINANCING);
                  });
                })
                ->whereHas('company', function ($query) use ($bank) {
                  $query->where('bank_id', $bank->id);
                });
            })
            ->where(function ($query) {
              $query
                ->whereHas('cbsTransactions', function ($query) {
                  $query->where('transaction_type', CbsTransaction::PAYMENT_DISBURSEMENT);
                })
                ->orWhereDoesntHave('cbsTransactions');
            })
            ->orderBy('created_at', 'DESC')
            ->paginate();
          break;
        default:
          return response()->json(['message' => 'Program Type not found'], 200);
          break;
      }
    } else {
      $payment_requests = PaymentRequest::with(
        'invoice.program.programType',
        'invoice.program.bankDetails',
        'invoice.program.vendorConfigurations',
        'invoice.program.vendorDiscountDetails',
        'invoice.invoiceItems',
        'invoice.invoiceFees',
        'invoice.invoiceTaxes',
        'invoice.company',
        'paymentAccounts.paymentRequest.invoice',
        'cbsTransactions'
      )
        ->whereHas('invoice', function ($query) use ($bank) {
          $query->whereHas('company', function ($query) use ($bank) {
            $query->where('bank_id', $bank->id);
          });
        })
        ->orderBy('created_at', 'DESC')
        ->paginate();
    }

    foreach ($payment_requests as $payment_request) {
      $payment_request->invoice['total'] = $payment_request->invoice->total;
      $payment_request->invoice['total_taxes'] = $payment_request->invoice->total_invoice_taxes;
      $payment_request->invoice['total_fees'] = $payment_request->invoice->total_invoice_fees;
      $payment_request->invoice['total_discount'] = $payment_request->invoice->total_invoice_discount;
      $payment_request['eligible_for_finance'] = $payment_request->eligible_for_finance;
      $payment_request['anchor'] = $payment_request->invoice->program->getAnchor();
      if (
        $payment_request->invoice->program->programType->name == 'Vendor Financing' &&
        ($payment_request->invoice->program->programCode->name == 'Factoring With Recourse' ||
          $payment_request->invoice->program->programCode->name == 'Factoring Without Recourse')
      ) {
        $payment_request['buyer'] = $payment_request->invoice->buyer;
      }
    }

    if (request()->wantsJson()) {
      return response()->json(['payment_requests' => $payment_requests]);
    }
  }

  public function paymentRequestsData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $pi_number = $request->query('pi_number');
    $debit_from = $request->query('debit_from');
    $company_name = $request->query('company_name');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $sort_by = $request->query('sort_by');
    $status = $request->query('status');
    $invoice_number = $request->query('invoice_number');
    $pay_date = $request->query('pay_date');
    $transaction_type = $request->query('transaction_type');
    $product_type = $request->query('product_type');

    $payment_requests = PaymentRequest::where('pr_id', '!=', null)
      ->with(['invoice', 'paymentAccounts', 'invoice.company'])
      ->whereHas('invoice', function ($query) use ($bank, $invoice_number, $product_type, $company_name, $pi_number) {
        $query
          ->whereHas('program', function ($query) use ($bank, $product_type) {
            $query
              ->where('bank_id', $bank->id)
              ->when($product_type && $product_type != '', function ($query) use ($product_type) {
                switch ($product_type) {
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
                // $query->whereHas('programType', function ($query) use ($product_type) {
                //   $query->where('name', $product_type);
                // });
              });
          })
          ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          })
          ->when($company_name && $company_name != '', function ($query) use ($company_name) {
            $query->whereHas('company', function ($query) use ($company_name) {
              $query->where('name', 'LIKE', '%' . $company_name . '%');
            });
          })
          ->when($pi_number && $pi_number != '', function ($query) use ($pi_number) {
            $query->where('pi_number', 'LIKE', '%' . $pi_number . '%');
          });
      })
      ->when($debit_from && $debit_from != '', function ($query) use ($debit_from) {
        $query->whereHas('cbsTransactions', function ($query) use ($debit_from) {
          $query->where('debit_from_account', 'LIKE', '%' . $debit_from . '%');
        });
      })
      ->when($transaction_type && $transaction_type != '', function ($query) use ($transaction_type) {
        $query->whereHas('cbsTransactions', function ($query) use ($transaction_type) {
          $query->where('transaction_type', $transaction_type);
        });
      })
      ->when($pay_date && $pay_date != '', function ($query) use ($pay_date) {
        $query->where('payment_request_date', $pay_date);
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('payment_request_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('payment_request_date', '<=', $to_date);
      })
      ->where(function ($query) use ($status) {
        $query->when($status && count($status) > 0, function ($query) use ($status) {
          $query->whereIn('status', $status);
        });
        $query->when(!$status || count($status) == 0, function ($query) use ($status) {
          $query->whereIn('status', ['approved', 'paid']);
        });
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        if ($sort_by == 'pi_no_asc') {
          $query->whereHas('invoice', function ($query) {
            $query->orderBy('pi_number', 'ASC');
          });
        }
        if ($sort_by == 'pi_no_desc') {
          $query->whereHas('invoice', function ($query) {
            $query->orderBy('pi_number', 'DESC');
          });
        }
        if ($sort_by == 'amount_asc') {
          $query->orderBy('amount', 'ASC');
        }
        if ($sort_by == 'amount_desc') {
          $query->orderBy('amount', 'DESC');
        }
        if ($sort_by == 'debit_from_asc') {
          $query->whereHas('cbsTransactions', function ($query) {
            $query->orderBy('debit_from_account', 'ASC');
          });
        }
        if ($sort_by == 'debit_from_desc') {
          $query->whereHas('cbsTransactions', function ($query) {
            $query->orderBy('debit_from_account', 'DESC');
          });
        }
        if ($sort_by == 'id_asc') {
          $query->whereHas('cbsTransactions', function ($query) {
            $query->orderBy('id', 'ASC');
          });
        }
        if ($sort_by == 'id_desc') {
          $query->whereHas('cbsTransactions', function ($query) {
            $query->orderBy('id', 'DESC');
          });
        }
      })
      ->when(!$sort_by || ($sort_by && $sort_by == ''), function ($query) {
        $query->orderBy('pr_id', 'DESC');
      })
      ->paginate($per_page);

    $payment_requests = PaymentRequestResource::collection($payment_requests)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['payment_requests' => $payment_requests]);
    }
  }

  public function creditAccountRequestsData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $debit_from = $request->query('debit_from');
    $company_name = $request->query('company_name');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $sort_by = $request->query('sort_by');
    $status = $request->query('status');

    $payment_requests = CreditAccountRequest::with(
      'program.programType',
      'program.bankDetails',
      'company',
      'paymentAccounts',
      'cbsTransactions'
    )
      ->whereHas('company', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      })
      ->when($debit_from && $debit_from != '', function ($query) use ($debit_from) {
        $query->whereHas('program', function ($query) use ($debit_from) {
          $query->whereHas('bankDetails', function ($query) use ($debit_from) {
            $query->where('account_number', 'LIKE', '%' . $debit_from . '%');
          });
        });
      })
      ->when($company_name && $company_name != '', function ($query) use ($company_name) {
        $query->whereHas('company', function ($query) use ($company_name) {
          $query->where('name', 'LIKE', '%' . $company_name . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('credit_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('credit_date', '<=', $to_date);
      })
      ->where(function ($query) use ($status) {
        $query->when($status && $status != '', function ($query) use ($status) {
          $query->where('status', $status);
        });
      })
      ->orderBy('created_at', $sort_by)
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['payment_requests' => $payment_requests]);
    }
  }

  public function exportPaymentRequests(Request $request, Bank $bank)
  {
    $pi_number = $request->query('pi_number');
    $debit_from = $request->query('debit_from');
    $company_name = $request->query('company_name');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $sort_by = $request->query('sort_by');
    $status = $request->query('status');
    $invoice_number = $request->query('invoice_number');
    $pay_date = $request->query('pay_date');
    $product_type = $request->query('product_type');
    $date = now()->format('Y-m-d');

    // Check if the oldest payment request is more than three months old
    $oldest_payment_request = PaymentRequest::whereHas('invoice', function ($query) use ($bank) {
      $query->whereHas('program', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      });
    })
      ->orderBy('payment_request_date', 'ASC')
      ->first();
    $latest_payment_request = PaymentRequest::whereHas('invoice', function ($query) use ($bank) {
      $query->whereHas('program', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      });
    })
      ->orderBy('payment_request_date', 'DESC')
      ->first();

    // Check if data is within three months
    if ($oldest_payment_request && $latest_payment_request) {
      $diff_in_months = Carbon::parse($latest_payment_request->payment_request_date)->diffInMonths(
        Carbon::parse($oldest_payment_request->payment_request_date)
      );
      if (
        $diff_in_months > 3 &&
        (!$from_date || !$to_date || Carbon::parse($from_date)->diffInMonths(Carbon::parse($to_date)) > 3)
      ) {
        return response()->json(
          ['message' => 'You can only export 3 months of data at a time. Kindly select a date range.'],
          422
        );
      }
    }

    Excel::store(
      new PaymentRequests(
        $bank,
        $debit_from,
        $company_name,
        $from_date,
        $to_date,
        $status,
        $sort_by,
        $invoice_number,
        $pi_number,
        $pay_date,
        $product_type
      ),
      'Payment_requests_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Payment_requests_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function updateRequest(Request $request, Bank $bank)
  {
    $validator = Validator::make($request->all(), [
      'payment_request_id' => ['required'],
      'status' => ['required', 'in:approved,rejected'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $payment_request = PaymentRequest::find($request->payment_request_id);

    if ($payment_request->invoice->program->account_status != 'active') {
      return response()->json(['message' => 'Program is deactivated'], 401);
    }

    if ($payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configurations = ProgramVendorConfiguration::where('company_id', $payment_request->invoice->company_id)
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
      } else {
        $vendor_configurations = ProgramVendorConfiguration::where('buyer_id', $payment_request->invoice->buyer_id)
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();
      }
    } else {
      $vendor_configurations = ProgramVendorConfiguration::where('company_id', $payment_request->invoice->company_id)
        ->where('program_id', $payment_request->invoice->program_id)
        ->first();
    }

    $bank_configurations = BankGeneralProductConfiguration::where(
      'bank_id',
      $payment_request->invoice->program->bank_id
    )
      ->where('product_type_id', $payment_request->invoice->program->program_type_id)
      ->where('name', 'retain limit')
      ->first();

    if ($vendor_configurations->status != 'active') {
      return response()->json(['message' => 'Program is deactivated'], 401);
    }

    $company = $payment_request->invoice->company;

    // Get company utilized and pipeline amount
    $company_available_amount =
      $company->top_level_borrower_limit -
      $payment_request->invoice->company->pipeline_amount -
      $payment_request->invoice->company->utilized_amount;

    // Check if company available limit will be surpassed
    if ($company_available_amount <= 0) {
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Company Top Level Borrower Limit Exceeded'], 422);
      }

      toastr()->error('', 'Company Top Level Borrower Limit Exceeded');

      return back();
    }

    // Check if program limit is exceeded
    $program_available_amount =
      $payment_request->invoice->program->program_limit -
      $payment_request->invoice->program->pipeline_amount -
      $payment_request->invoice->program->utilized_amount;
    if ($program_available_amount <= 0) {
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Program Limit Exceeded'], 422);
      }

      toastr()->error('', 'Program Limit Exceeded');

      return back();
    }

    if ($bank_configurations->value > 0) {
      $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
      $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
      $potential_utilization_amount = $vendor_configurations->utilized_amount + $vendor_configurations->pipeline_amount;
      if ($potential_utilization_amount > $remainder) {
        if (request()->wantsJson()) {
          return response()->json(['message' => 'Invoice amount exceeds borrowing limit'], 422);
        }

        toastr()->error('', 'Invoice amount exceeds borrowing limit');

        return back();
      }
    }

    // Check if sanctioned limit is exceeded
    $mapping_available_amount =
      $vendor_configurations->sanctioned_limit -
      $vendor_configurations->pipeline_amount -
      $vendor_configurations->utilized_amount;

    if ($mapping_available_amount <= 0) {
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Company Program Limit Exceeded'], 422);
      }

      toastr()->error('', 'Company Program Limit Exceeded');
    }

    try {
      DB::beginTransaction();

      if ($request->status === 'approved') {
        $config = $payment_request->invoice->program->bank->generalConfigurations
          ->where('product_type_id', $payment_request->invoice->program->programType->id)
          ->where('name', 'finance request approval')
          ->first();
        if ($config && $config->value === 'no') {
          $payment_request->approvals()->create([
            'user_id' => auth()->id(),
          ]);

          $payment_request->update([
            'status' => $request->status,
            'approval_status' => 'approved',
            'updated_by' => auth()->id(),
          ]);

          if (!$payment_request->invoice->pi_number) {
            $payment_request->invoice->update([
              'pi_number' => 'PI_' . $payment_request->invoice->id,
            ]);
          }

          // Create CBS Transactions for the payment request
          CreateCbsTransactions::dispatchAfterResponse([$payment_request->id]);
        } else {
          // An approval has already been done
          if ($payment_request->approvals->count() === 1) {
            // Check if the user has already approved
            $has_approved = $payment_request
              ->approvals()
              ->where(['user_id' => auth()->id()])
              ->first();
            if (!$has_approved) {
              $payment_request->approvals()->create([
                'user_id' => auth()->id(),
              ]);

              $payment_request->update([
                'status' => $request->status,
                'approval_status' => 'approved',
                'updated_by' => auth()->id(),
              ]);

              if (!$payment_request->invoice->pi_number) {
                $payment_request->invoice->update([
                  'pi_number' => 'PI_' . $payment_request->invoice->id,
                ]);
              }

              // Create CBS Transactions for the payment request
              CreateCbsTransactions::dispatchAfterResponse([$payment_request->id]);
            }
          } else {
            // First Approval
            $payment_request->approvals()->create([
              'user_id' => auth()->id(),
            ]);

            $payment_request->update([
              'approval_status' => 'pending_checker',
              'updated_by' => auth()->id(),
            ]);

            if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $bank_users = BankUser::where('bank_id', $bank->id)
                ->where('user_id', '!=', auth()->id())
                ->where('active', true)
                ->whereHas('user', function ($query) {
                  $query->where('is_active', true)->whereHas('roles', function ($query) {
                    $query->whereHas('permissions', function ($query) {
                      $query->where('name', 'Approve Dealer Financing Requests Level 2');
                    });
                  });
                })
                ->get();
            } else {
              $bank_users = BankUser::where('bank_id', $bank->id)
                ->where('user_id', '!=', auth()->id())
                ->where('active', true)
                ->whereHas('user', function ($query) {
                  $query->where('is_active', true)->whereHas('roles', function ($query) {
                    $query->whereHas('permissions', function ($query) {
                      $query->where('name', 'Approve Vendor Financing Requests Level 2');
                    });
                  });
                })
                ->get();
            }
          }
        }
      }

      // Reject Payment Request
      if ($request->status === 'rejected') {
        $rejection_reason =
          $request->has('custom_rejection_reason') &&
          !empty($request->custom_rejection_reason) &&
          $request->custom_rejection_reason != ''
            ? $request->custom_rejection_reason
            : $request->rejection_reason;

        $payment_request->update([
          'rejected_by' => auth()->id(),
          'status' => $request->status,
          'rejected_reason' => $rejection_reason,
          'approval_status' => 'rejected',
          'updated_by' => auth()->id(),
        ]);

        $payment_request->invoice->update([
          'financing_status' => 'denied',
          'rejected_reason' => $rejection_reason,
        ]);

        // Update Program and Company Pipeline and Utilized Amounts
        $payment_request->invoice->company->decrement(
          'pipeline_amount',
          $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
            ? $payment_request->invoice->drawdown_amount
            : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
        );

        $payment_request->invoice->program->decrement(
          'pipeline_amount',
          $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
            ? $payment_request->invoice->drawdown_amount
            : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
        );

        $program_vendor_configuration = ProgramVendorConfiguration::where(
          'company_id',
          $payment_request->invoice->company_id
        )
          ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
            $query->where('buyer_id', $payment_request->invoice->buyer_id);
          })
          ->where('program_id', $payment_request->invoice->program_id)
          ->first();

        $program_vendor_configuration->decrement(
          'pipeline_amount',
          $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
            ? $payment_request->invoice->drawdown_amount
            : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
        );

        // Send Mail to approver bank user
        if ($payment_request->approvals->count() >= 1) {
          PaymentRequestRejectionNotification::dispatchAfterResponse([$payment_request->id]);
        }
      }
      // End Rejecte Payment Request

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn(PaymentRequest::find($request->payment_request_id))
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log($request->status);

      DB::commit();

      if ($request->status === 'approved') {
        $bank_users = $bank->users;
        // Send Notification to checker bank user
        foreach ($bank_users as $user) {
          SendMail::dispatch($user->email, 'FinancingRequestApproved', [
            'financing_request' => $payment_request->id,
            'url' => config('app.url'),
            'name' => $user->name,
            'approver_name' => auth()->user()->name,
            'type' => 'vendor_financing',
          ])->afterResponse();
        }
      }

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Payment Request updated successfully');

      return back();
    } catch (\Throwable $th) {
      //throw $th;
      DB::rollBack();

      info($th);

      if (request()->wantsJson()) {
        return response()->json(['message' => 'An error occurred']);
      }

      toastr()->error('', 'Failed to update payment request');

      return back();
    }
  }

  public function updateRequests(Request $request, Bank $bank)
  {
    $validator = Validator::make($request->all(), [
      'requests' => ['required'],
      'status' => ['required'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $requires_further_approval = false;
    $product_type = Program::VENDOR_FINANCING;

    $first_payment_request = PaymentRequest::find(collect($request->requests)->first());

    if ($first_payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
      $product_type = Program::DEALER_FINANCING;
    } else {
      if ($first_payment_request->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $product_type = Program::VENDOR_FINANCING;
      } else {
        $product_type = 'Factoring';
      }
    }

    $config = $first_payment_request->invoice->program->bank->generalConfigurations
      ->where('product_type_id', $first_payment_request->invoice->program->programType->id)
      ->where('name', 'finance request approval')
      ->first();

    $status = $request->status;
    $rejection_reason = $request->rejection_reason;

    $bank_id = $bank->id;

    $further_approval_payment_requests = [];
    $approved_payment_requests = [];
    $rejected_payment_requests = [];

    $message = '';

    try {
      DB::beginTransaction();

      PaymentRequest::whereIn('id', $request->requests)
        ->whereHas('invoice', function ($query) {
          $query->orderBy('due_date', 'ASC');
        })
        ->chunk(50, function ($payment_requests) use (
          $config,
          $status,
          $rejection_reason,
          $bank_id,
          &$requires_further_approval,
          &$further_approval_payment_requests,
          &$approved_payment_requests,
          &$rejected_payment_requests,
          &$message
        ) {
          foreach ($payment_requests as $payment_request) {
            // Rejected
            if ($status === 'rejected') {
              $payment_request->update([
                'rejected_by' => auth()->id(),
                'status' => $status,
                'rejected_reason' => $rejection_reason,
                'approval_status' => 'rejected',
                'updated_by' => auth()->id(),
              ]);

              $payment_request->invoice->update([
                'financing_status' => 'denied',
                'rejected_reason' => $rejection_reason,
              ]);

              // Update Program and Company Pipeline and Utilized Amounts
              $payment_request->invoice->company->decrement(
                'pipeline_amount',
                $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                  ? $payment_request->invoice->drawdown_amount
                  : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
              );

              $payment_request->invoice->program->decrement(
                'pipeline_amount',
                $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                  ? $payment_request->invoice->drawdown_amount
                  : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
              );

              $program_vendor_configuration = ProgramVendorConfiguration::where(
                'company_id',
                $payment_request->invoice->company_id
              )
                ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                  $query->where('buyer_id', $payment_request->invoice->buyer_id);
                })
                ->where('program_id', $payment_request->invoice->program_id)
                ->first();

              $program_vendor_configuration->decrement(
                'pipeline_amount',
                $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                  ? $payment_request->invoice->drawdown_amount
                  : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
              );

              // Add to array for response to bank and company users
              array_push($rejected_payment_requests, $payment_request->id);
            } else {
              if ($payment_request->invoice->program->account_status != 'active') {
                $message = 'Program(s) are deactivated';
              }

              if ($payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
                if ($payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                  $vendor_configurations = ProgramVendorConfiguration::where(
                    'company_id',
                    $payment_request->invoice->company_id
                  )
                    ->where('program_id', $payment_request->invoice->program_id)
                    ->first();
                } else {
                  $vendor_configurations = ProgramVendorConfiguration::where(
                    'buyer_id',
                    $payment_request->invoice->buyer_id
                  )
                    ->where('program_id', $payment_request->invoice->program_id)
                    ->first();
                }
              } else {
                $vendor_configurations = ProgramVendorConfiguration::where(
                  'company_id',
                  $payment_request->invoice->company_id
                )
                  ->where('program_id', $payment_request->invoice->program_id)
                  ->first();
              }
              $bank_configurations = BankGeneralProductConfiguration::where(
                'bank_id',
                $payment_request->invoice->program->bank_id
              )
                ->where('product_type_id', $payment_request->invoice->program->program_type_id)
                ->where('name', 'retain limit')
                ->first();

              // Check if company and program limits are exceeded
              $company = $payment_request->invoice->company;

              // Get company utilized and pipeline amount
              $company_available_amount =
                $company->top_level_borrower_limit -
                $payment_request->invoice->company->pipeline_amount -
                $payment_request->invoice->company->utilized_amount;

              // Check if company available limit will be surpassed
              if ($company_available_amount <= 0) {
                $message = 'Company/Companies Top Level Borrower Limit(s) Exceeded';
              }

              // Check if program limit is exceeded
              $program_available_amount =
                $payment_request->invoice->program->program_limit -
                $payment_request->invoice->program->pipeline_amount -
                $payment_request->invoice->program->utilized_amount;
              if ($program_available_amount <= 0) {
                $message = 'Program(s) Limits Exceeded';
              }

              if ($bank_configurations->value > 0) {
                $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
                $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
                $potential_utilization_amount =
                  $vendor_configurations->utilized_amount + $vendor_configurations->pipeline_amount;
                if ($potential_utilization_amount > $remainder) {
                  $message = 'Invoice amount(s) exceeds borrowing limits';
                }
              }

              // Check if sanctioned limit is exceeded
              $mapping_available_amount =
                $vendor_configurations->sanctioned_limit -
                $vendor_configurations->pipeline_amount -
                $vendor_configurations->utilized_amount;

              if ($mapping_available_amount <= 0) {
                $message = 'Company/Companies Program Limit(s) Exceeded';
              }

              if ($message === '') {
                // Limit's aren't exceeded
                if ($config && $config->value === 'no') {
                  $payment_request->approvals()->create([
                    'user_id' => auth()->id(),
                  ]);

                  if (!$payment_request->invoice->pi_number) {
                    $payment_request->invoice->update([
                      'pi_number' => 'PI_' . $payment_request->invoice->id,
                    ]);
                  }

                  $payment_request->update([
                    'status' => $status,
                    'approval_status' => 'approved',
                    'updated_by' => auth()->id(),
                  ]);

                  // add to array for creating cbs transactions after response
                  array_push($approved_payment_requests, $payment_request->id);
                } else {
                  // An approval has already been done
                  if ($payment_request->approvals->count() === 1) {
                    // Check if the user has already approved
                    $has_approved = $payment_request
                      ->approvals()
                      ->where(['user_id' => auth()->id()])
                      ->first();

                    if (!$has_approved) {
                      $payment_request->approvals()->create([
                        'user_id' => auth()->id(),
                      ]);

                      if (!$payment_request->invoice->pi_number) {
                        $payment_request->invoice->update([
                          'pi_number' => 'PI_' . $payment_request->invoice->id,
                        ]);
                      }

                      $payment_request->update([
                        'status' => $status,
                        'approval_status' => 'approved',
                        'updated_by' => auth()->id(),
                      ]);

                      // add to array for creating cbs transactions after response
                      array_push($approved_payment_requests, $payment_request->id);
                    }
                  } else {
                    // First Approval
                    $payment_request->approvals()->create([
                      'user_id' => auth()->id(),
                    ]);

                    $payment_request->update([
                      'approval_status' => 'pending_checker',
                      'updated_by' => auth()->id(),
                    ]);

                    array_push($further_approval_payment_requests, $payment_request->id);

                    $requires_further_approval = true;
                  }
                }
              }
            }

            if ($message === '') {
              activity($bank_id)
                ->causedBy(auth()->user())
                ->performedOn($payment_request)
                ->withProperties([
                  'ip' => request()->ip(),
                  'device_info' => request()->userAgent(),
                  'user_type' => 'Bank',
                ])
                ->log($status . ' payment request');
            }
          }
        });

      DB::commit();
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
    }

    if (count($approved_payment_requests) > 0) {
      // Create CBS Transactions for the approved payment requests
      CreateCbsTransactions::dispatchAfterResponse($approved_payment_requests);
    }

    if (count($rejected_payment_requests) > 0) {
      // Notify maker bank user for rejected payment requests
      PaymentRequestRejectionNotification::dispatchAfterResponse($rejected_payment_requests);
    }

    // Notify checker bank users if further approval is required
    if ($product_type === Program::DEALER_FINANCING) {
      $checker = BankUser::where('bank_id', $bank->id)
        ->where('user_id', '!=', auth()->id())
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Approve Dealer Financing Requests Level 2');
            });
          });
        })
        ->get();
    } else {
      $checker = BankUser::where('bank_id', $bank->id)
        ->where('user_id', '!=', auth()->id())
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Approve Vendor Financing Requests Level 2');
            });
          });
        })
        ->get();
    }

    // User doesn't have all approval permissions send mail to checker bank users
    if ($requires_further_approval && $request->status != 'rejected') {
      foreach ($checker as $bank_user) {
        SendMail::dispatchAfterResponse($bank_user->user->email, 'FinancingRequestsApproved', [
          'financing_requests' => $further_approval_payment_requests,
        ]);
      }
    }

    if ($request->status === 'rejected') {
      if (request()->wantsJson()) {
        return response()->json([
          'message' => $product_type . ' Payment Requests updated successfully',
        ]);
      }

      toastr()->success('', $product_type . ' Payment Request updated successfully');

      return back();
    }

    if ($message && $message != '') {
      // Means Limits have been exceeded, return the appropriate message
      if (request()->wantsJson()) {
        return response()->json(['message' => $message], 422);
      }

      toastr()->error('', $message);

      return back();
    }

    if (request()->wantsJson()) {
      return response()->json([
        'message' => $requires_further_approval
          ? $product_type . ' Payment Requests approved successfully and sent for further approval'
          : $product_type . ' Payment Requests approved successfully',
      ]);
    }

    toastr()->success(
      '',
      $requires_further_approval
        ? $product_type . ' Payment Requests approved successfully and sent for further approval'
        : $product_type . ' Payment Request approved successfully'
    );

    return back();
  }

  public function uploadCbsTransaction(Request $request, Bank $bank)
  {
    $request->validate([
      'transactions' => ['required', 'mimes:xlsx'],
    ]);

    $import = new CbsTransactionsImport($bank);
    $import->import($request->file('transactions')->store('public'));

    $batch = ImportError::where('user_id', auth()->id())
      ->where('module', 'CbsTransaction')
      ->max('batch_id');

    $data = [];

    // Save errors to database to extract them in Import Error
    foreach ($import->failures() as $failure) {
      $data[] = [
        'user_id' => auth()->id(),
        'batch_id' => $batch ? $batch + 1 : 1,
        'row' => $failure->row(),
        'attribute' => $failure->attribute(),
        'values' => json_encode($failure->values()),
        'errors' => json_encode($failure->errors()),
        'module' => 'CbsTransaction',
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    if (count($data) > 0) {
      ImportError::insert($data);

      // // Download excel file with errors
      // $date = now()->format('Y-m-d');
      // Excel::store(new CbsErrorReport(), 'CBS_Transactions_' . $date . '.xlsx', 'exports');

      // return Storage::disk('exports')->download('CBS_Transactions_' . $date . '.xlsx');
    }

    if (count($import->disbursed_invoices) > 0) {
      LoanDisbursalNotification::dispatch($import->disbursed_invoices)->afterResponse();
    }

    if (count($import->closed_invoices) > 0) {
      LoanClosingNotification::dispatch($import->closed_invoices)->afterResponse();
    }

    if ($import->data > 0) {
      return response()->json(
        [
          'message' => 'CBS Transactions uploaded successfully',
          'uploaded' => $import->data,
          'total_rows' => collect($import->total_rows)->first() - 1,
          'successful_rows' => $import->successful_rows,
        ],
        400
      );
    }

    return response()->json(
      [
        'message' => 'No CBS Transactions were uploaded successfully',
        'uploaded' => $import->data,
        'total_rows' => collect($import->total_rows)->first() - 1,
        'successful_rows' => $import->successful_rows,
      ],
      400
    );

    return response()->json(['message' => 'CBS Transactions uploaded successfully']);
  }

  public function downloadCbsErrorReport(Bank $bank)
  {
    $date = now()->format('Y-m-d');

    Excel::store(new CbsErrorReport(), 'CBS_Transactions_' . $date . '.xlsx', 'exports');

    return Storage::disk('exports')->download('CBS_Transactions_' . $date . '.xlsx');
  }

  public function downloadCbsTransaction(Request $request, Bank $bank)
  {
    $date = now()->format('Y-m-d');
    $cbs_id = $request->query('cbs_id');
    $invoice_number = $request->query('invoice_number');
    $transaction_ref = $request->query('transaction_ref');
    $account = $request->query('account');
    $status = $request->query('status');
    $product_type = $request->query('product_type');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_transaction_date = $request->query('from_transaction_date');
    $to_transaction_date = $request->query('to_transaction_date');
    $sort_by = $request->query('sort_by');
    $transaction_type = $request->query('transaction_type');
    $selected_transactions = $request->query('selected_transactions');

    // // Check if cbs transactions span more than 3 months
    // $first_cbs_transaction = CbsTransaction::where('bank_id', $bank->id)->first();
    // $last_cbs_transaction = CbsTransaction::where('bank_id', $bank->id)
    //   ->latest()
    //   ->first();
    // $diff_in_months = $first_cbs_transaction->created_at->diffInMonths($last_cbs_transaction->created_at);
    // if ($diff_in_months > 3 && !$from_date && !$to_date) {
    //   return response()->json(['message' => 'Select date range of transactions to download'], 400);
    // }

    // // Check if selected date filters span more than 3 months
    // if ($from_date && $to_date) {
    //   $from_date = Carbon::parse($from_date);
    //   $to_date = Carbon::parse($to_date);
    //   $diff_in_months = $from_date->diffInMonths($to_date);
    //   if ($diff_in_months > 3) {
    //     return response()->json(['message' => 'Select a maximum 3 month period of transactions to download'], 400);
    //   }
    // }

    $cbs_transactions = new CbsTransactionsExport(
      $bank,
      $cbs_id,
      $invoice_number,
      $transaction_ref,
      $account,
      $status,
      $product_type,
      $from_date,
      $to_date,
      $from_transaction_date,
      $to_transaction_date,
      $sort_by,
      $transaction_type,
      $selected_transactions
    );

    Excel::store($cbs_transactions, 'CBS_Transactions_' . $date . '.xlsx', 'exports');

    return Storage::disk('exports')->download('CBS_Transactions_' . $date . '.xlsx');
  }

  public function downloadCreatedCbsTransaction(Request $request, Bank $bank)
  {
    $date = now()->format('Y-m-d');
    $cbs_id = $request->query('cbs_id');
    $invoice_number = $request->query('invoice_number');
    $transaction_ref = $request->query('transaction_ref');
    $account = $request->query('account');
    $status = $request->query('status');
    $product_type = $request->query('product_type');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_transaction_date = $request->query('from_transaction_date');
    $to_transaction_date = $request->query('to_transaction_date');
    $sort_by = $request->query('sort_by');
    $transaction_type = $request->query('transaction_type');
    $selected_transactions = $request->query('selected_transactions');

    // // Check if cbs transactions span more than 3 months
    // $first_cbs_transaction = CbsTransaction::where('bank_id', $bank->id)->first();
    // $last_cbs_transaction = CbsTransaction::where('bank_id', $bank->id)
    //   ->latest()
    //   ->first();
    // $diff_in_months = $first_cbs_transaction->created_at->diffInMonths($last_cbs_transaction->created_at);
    // if ($diff_in_months > 3 && !$from_date && !$to_date) {
    //   return response()->json(['message' => 'Select date range of transactions to download'], 400);
    // }

    // // Check if selected date filters span more than 3 months
    // if ($from_date && $to_date) {
    //   $from_date = Carbon::parse($from_date);
    //   $to_date = Carbon::parse($to_date);
    //   $diff_in_months = $from_date->diffInMonths($to_date);
    //   if ($diff_in_months > 3) {
    //     return response()->json(['message' => 'Select a maximum 3 month period of transactions to download'], 400);
    //   }
    // }

    $cbs_transactions = new CbsTransactionsExport(
      $bank,
      $cbs_id,
      $invoice_number,
      $transaction_ref,
      $account,
      $status,
      $product_type,
      $from_date,
      $to_date,
      $from_transaction_date,
      $to_transaction_date,
      $sort_by,
      $transaction_type,
      $selected_transactions
    );

    Excel::store($cbs_transactions, 'CBS_Transactions_' . $date . '.xlsx', 'exports');

    return Storage::disk('exports')->download('CBS_Transactions_' . $date . '.xlsx');
  }

  public function cbsTransactionsData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $cbs_id = $request->query('cbs_id');
    $invoice_number = $request->query('invoice_number');
    $transaction_type = $request->query('transaction_type');
    $account = $request->query('account');
    $status = $request->query('status');
    $product_type = $request->query('product_type');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_transaction_date = $request->query('from_transaction_date');
    $to_transaction_date = $request->query('to_transaction_date');
    $sort_by = $request->query('sort_by');

    $cbs_transactions = CbsTransaction::with('paymentRequest.invoice')
      ->where('bank_id', $bank->id)
      ->when($product_type && $product_type != '', function ($query) use ($product_type) {
        $query->whereHas('paymentRequest', function ($query) use ($product_type) {
          $query->whereHas('invoice', function ($query) use ($product_type) {
            $query->whereHas('program', function ($query) use ($product_type) {
              switch ($product_type) {
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
      // ->when($product_type && $product_type != '', function ($query) use ($product_type) {
      //   $query->where('product', $product_type);
      // })
      ->when($account && $account != '', function ($query) use ($account) {
        $query->where(function ($query) use ($account) {
          $query
            ->where('debit_from_account', 'LIKE', '%' . $account . '%')
            ->orWhere('credit_to_account', 'LIKE', '%' . $account . '%')
            ->orWhere('debit_from_account_name', 'LIKE', '%' . $account . '%')
            ->orWhere('credit_to_account_name', 'LIKE', '%' . $account . '%');
        });
      })
      ->when($cbs_id && $cbs_id != '', function ($query) use ($cbs_id) {
        $query->where('id', $cbs_id);
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        $query->whereIn('status', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('paymentRequest', function ($query) use ($invoice_number) {
          $query->whereHas('invoice', function ($query) use ($invoice_number) {
            $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
          });
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereHas('paymentRequest', function ($query) use ($from_date) {
          $query->whereDate('payment_request_date', '>=', $from_date);
        });
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereHas('paymentRequest', function ($query) use ($to_date) {
          $query->whereDate('payment_request_date', '<=', $to_date);
        });
      })
      ->when($from_transaction_date && $from_transaction_date != '', function ($query) use ($from_transaction_date) {
        $query->whereDate('transaction_date', '>=', $from_transaction_date);
      })
      ->when($to_transaction_date && $to_transaction_date != '', function ($query) use ($to_transaction_date) {
        $query->whereDate('transaction_date', '<=', $to_transaction_date);
      })
      ->when(
        $transaction_type &&
          count(array_filter($transaction_type, fn($value) => !is_null($value) && $value !== '')) > 0,
        function ($query) use ($transaction_type) {
          $query->whereIn('transaction_type', $transaction_type);
        }
      )
      ->orderBy('id', $sort_by)
      ->paginate($per_page);

    $cbs_transactions = CbsTransactionResource::collection($cbs_transactions)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['transactions' => $cbs_transactions]);
    }
  }

  public function addCbsTransaction(Request $request, Bank $bank)
  {
    $cbs_transaction_types = [
      CbsTransaction::PAYMENT_DISBURSEMENT,
      CbsTransaction::ACCRUAL_POSTED_INTEREST,
      CbsTransaction::FEES_CHARGES,
      CbsTransaction::BANK_INVOICE_PAYMENT,
      CbsTransaction::REPAYMENT,
      CbsTransaction::FUNDS_TRANSFER,
    ];

    $request->validate([
      'credit_to_account' => ['required'],
      'debit_from_account' => ['required'],
      'amount' => ['required'],
      'pay_date' => ['required', 'date'],
      'status' => ['required', 'in:Successful,Created,Failed,Permanently Failed'],
      'type' => ['required', 'in:Payment Disbursement,Fees/Charges'],
      'ref' => ['required'],
    ]);

    if ($request->type == CbsTransaction::PAYMENT_DISBURSEMENT) {
      // Transaction is for vendor financing
      $payment_request = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company')
        ->whereHas('paymentAccounts', function ($query) use ($request) {
          $query->where('account', $request->credit_to_account);
        })
        ->whereHas('invoice', function ($query) use ($request) {
          $query->whereHas('program', function ($query) use ($request) {
            $query->whereHas('bankDetails', function ($query) use ($request) {
              $query->where('account_number', $request->debit_from_account);
            });
          });
        })
        ->where('amount', $request->amount)
        ->first();

      if ($payment_request) {
        $cbs_transaction = CbsTransaction::create([
          'bank_id' => $payment_request->invoice?->program?->bank?->id,
          'payment_request_id' => $payment_request->id,
          'debit_from_account' => $request->debit_from_account,
          'credit_to_account' => $request->credit_to_account,
          'amount' => $request->amount,
          'transaction_created_date' => $request->pay_date,
          'transaction_date' => $request->pay_date,
          'pay_date' => $request->pay_date,
          'transaction_reference' => $request->ref,
          'status' => $request->status,
          'transaction_type' => $request->type,
        ]);

        if ($request->status == 'Successful') {
          $payment_request->update([
            'status' => 'paid',
          ]);

          $payment_request->invoice->update([
            'disbursement_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
            'financing_status' => 'disbursed',
          ]);
        }

        if ($request->status == 'Failed' || $request->status == 'Permanently Failed') {
          $payment_request->update([
            'status' => 'failed',
          ]);

          if ($request->status == 'Permanently Failed') {
            $payment_request->invoice->update([
              'finance_status' => 'denied',
            ]);
          }
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($cbs_transaction)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('added a Cbs transaction (Payment Disbursement Transaction)');
      }
    }

    // Transaction is for vendor financing anchor repayment
    if ($request->type == CbsTransaction::FEES_CHARGES) {
      // // Get bank payment account info
      $bank_ids = Bank::whereHas('paymentAccounts', function ($query) use ($request) {
        $query->where('account_number', $request->credit_to_account);
      })
        ->get()
        ->pluck('id');

      // Get the Payment Request
      $payment_request = PaymentRequest::with('invoice.program.bankDetails', 'invoice.company')
        ->whereHas('invoice', function ($query) use ($request, $bank_ids) {
          $query->whereHas('program', function ($query) use ($request, $bank_ids) {
            $query->whereIn('bank_id', $bank_ids)->whereHas('bankDetails', function ($query) use ($request) {
              $query->where('account_number', $request->debit_from_account);
            });
          });
        })
        ->where('amount', $request->amount)
        ->first();

      if ($payment_request) {
        $fee_cbs_transaction = CbsTransaction::create([
          'bank_id' => $payment_request->invoice?->program?->bank?->id,
          'payment_request_id' => $payment_request->id,
          'debit_from_account' => $request->debit_from_account,
          'credit_to_account' => $request->credit_to_account,
          'amount' => $request->amount,
          'transaction_created_date' => $request->pay_date,
          'transaction_date' => $request->pay_date,
          'pay_date' => $request->pay_date,
          'transaction_reference' => $request->ref,
          'status' => $request->status,
          'transaction_type' => $request->type,
        ]);

        if ($request->status == 'Successful') {
          $payment_request->invoice->update([
            'financing_status' => 'closed',
            'stage' => 'closed',
          ]);
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->performedOn($fee_cbs_transaction)
          ->log('added a cbs transaction (Fees/Charges)');
      }
    }
  }

  public function updateCbsTransaction(Request $request, Bank $bank, $transaction_id)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'debit_from_account' => ['required'],
        'credit_to_account' => ['required'],
        'status' => ['required', 'in:Successful,Created,Failed,Permanently Failed'],
        'transaction_ref' => ['required_if:status,Successful'],
        'pay_date' => ['required_if:status,Successful'],
      ],
      [
        'transaction_ref.required_if' => 'The transaction reference field is required when status is Successful.',
        'pay_date.required_if' => 'Select the Paid Date when status is Successful.',
      ]
    );

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $cbs_transaction = new CbsTransactionResource(CbsTransaction::find($transaction_id));

    $cbs_transaction->update([
      'debit_from_account' => $request->debit_from_account,
      'credit_to_account' => $request->credit_to_account,
      'status' => $request->status,
      'transaction_reference' => $request->transaction_ref,
      'pay_date' => $request->pay_date,
    ]);

    $payment_request = $cbs_transaction->paymentRequest;

    if ($request->status === 'Successful') {
      if ($payment_request) {
        $cbs_transaction->update([
          'pay_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
          'transaction_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
          'transaction_reference' => $request->transaction_ref,
        ]);

        $payment_request->update([
          'status' => 'paid',
          'approval_status' => 'paid',
        ]);

        if ($payment_request->invoice) {
          if ($payment_request->invoice->financing_status === 'disbursed') {
            // Check if cbs transaction was made after invoice due date
            if (
              $cbs_transaction->transaction_type === CbsTransaction::REPAYMENT ||
              $cbs_transaction->transaction_type === CbsTransaction::OVERDUE_ACCOUNT ||
              $cbs_transaction->transaction_type === CbsTransaction::BANK_INVOICE_PAYMENT
            ) {
              // If its a repayment
              Payment::create([
                'invoice_id' => $payment_request->invoice->id,
                'amount' => $cbs_transaction->amount,
                'credit_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
              ]);

              $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

              // $payment_request->notifyUsers('InvoicePaymentProcessed');

              // Check if invoice is fully paid
              if (round($payment_request->invoice->balance) <= 0) {
                $payment_request->invoice->update([
                  'financing_status' => 'closed',
                  'stage' => 'closed',
                ]);

                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'payment_account_number',
                  $cbs_transaction->credit_to_account
                )->first();

                if ($program_vendor_configuration) {
                  // Update Program and Company Pipeline and Utilized Amounts
                  $payment_request->invoice->company->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );

                  $payment_request->invoice->program->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );

                  // $program_vendor_configuration = ProgramVendorConfiguration::where(
                  //   'company_id',
                  //   $payment_request->invoice->company_id
                  // )
                  //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                  //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                  //   })
                  //   ->where('program_id', $payment_request->invoice->program_id)
                  //   ->first();

                  $program_vendor_configuration->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );
                }

                $payment_request->notifyUsers('LoanClosing');
                // $payment_request->notifyUsers('FullRepayment');

                if (round($cbs_transaction->amount) != round($payment_request->invoice->balance)) {
                  // $payment_request->notifyUsers('BalanceInvoicePayment');
                }

                // If loan was overdue, send overdue repayment mail
                if (Carbon::parse($payment_request->invoice->due_date)->lessThan($request->pay_date)) {
                  // $payment_request->notifyUsers('OverdueFullRepayment');
                }
              }

              // Notify company users of received payment
              if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                foreach ($payment_request->invoice->company->users as $user) {
                  SendMail::dispatchAfterResponse($user->email, 'RepaymentReceivedForOdAccount', [
                    'od_account' => $request->credit_to_account,
                    'amount' => (float) $cbs_transaction->amount,
                    'distributor_name' => $payment_request->invoice->company->name,
                    'debit_from' => $request->debit_from_account,
                    'name' => auth()->user()->name,
                  ]);
                }
              }
            }

            if (
              $cbs_transaction->paymentRequest->invoice->discount_charge_type === Invoice::REAR_ENDED &&
              $cbs_transaction->transaction_type === CbsTransaction::ACCRUAL_POSTED_INTEREST
            ) {
              Payment::create([
                'invoice_id' => $payment_request->invoice->id,
                'amount' => $cbs_transaction->amount,
                'credit_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
              ]);

              $payment_request->invoice->update([
                'calculated_paid_amount' =>
                  $payment_request->invoice->calculated_paid_amount + $cbs_transaction->amount,
              ]);

              $payment_request->notifyUsers('InvoicePaymentProcessed');

              if (round($payment_request->invoice->balance) <= 0) {
                $payment_request->invoice->update([
                  'financing_status' => 'closed',
                  'stage' => 'closed',
                ]);

                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'payment_account_number',
                  $cbs_transaction->credit_to_account
                )->first();

                // Update Program and Company Pipeline and Utilized Amounts if affects OD
                if ($program_vendor_configuration) {
                  $payment_request->invoice->company->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );

                  $payment_request->invoice->program->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );

                  // $program_vendor_configuration = ProgramVendorConfiguration::where(
                  //   'company_id',
                  //   $payment_request->invoice->company_id
                  // )
                  //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                  //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                  //   })
                  //   ->where('program_id', $payment_request->invoice->program_id)
                  //   ->first();

                  $program_vendor_configuration->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );
                }

                // Notify anchor
                $payment_request->notifyUsers('LoanClosing');
                // $payment_request->notifyUsers('FullRepayment');

                if ($cbs_transaction->amount != $payment_request->invoice->balance) {
                  // $payment_request->notifyUsers('BalanceInvoicePayment');
                }

                // If loan was overdue, send overdue repayment mail
                if (Carbon::parse($payment_request->invoice->due_date)->lessThan(Carbon::parse($request->pay_date))) {
                  // $payment_request->notifyUsers('OverdueFullRepayment');
                }
              } else {
                // $payment_request->notifyUsers('PartialRepayment');
              }
            }

            if (
              $cbs_transaction->paymentRequest->invoice->fee_charge_type === Invoice::REAR_ENDED &&
              $cbs_transaction->transaction_type === CbsTransaction::FEES_CHARGES
            ) {
              Payment::create([
                'invoice_id' => $payment_request->invoice->id,
                'amount' => $cbs_transaction->amount,
                'credit_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
              ]);

              $payment_request->invoice->increment('calculated_paid_amount', $cbs_transaction->amount);

              // $payment_request->notifyUsers('InvoicePaymentProcessed');

              if (round($payment_request->invoice->balance) <= 0) {
                $payment_request->invoice->update([
                  'financing_status' => 'closed',
                  'stage' => 'closed',
                ]);

                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'payment_account_number',
                  $cbs_transaction->credit_to_account
                )->first();

                // Update Program and Company Pipeline and Utilized Amounts if affects OD
                if ($program_vendor_configuration) {
                  $payment_request->invoice->company->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );

                  $payment_request->invoice->program->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );

                  // $program_vendor_configuration = ProgramVendorConfiguration::where(
                  //   'company_id',
                  //   $payment_request->invoice->company_id
                  // )
                  //   ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                  //     $query->where('buyer_id', $payment_request->invoice->buyer_id);
                  //   })
                  //   ->where('program_id', $payment_request->invoice->program_id)
                  //   ->first();

                  $program_vendor_configuration->decrement(
                    'utilized_amount',
                    $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                      ? $payment_request->invoice->drawdown_amount
                      : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
                  );
                }

                // Notify anchor
                $payment_request->notifyUsers('LoanClosing');
                // $payment_request->notifyUsers('FullRepayment');

                if ($cbs_transaction->amount != $payment_request->invoice->balance) {
                  // $payment_request->notifyUsers('BalanceInvoicePayment');
                }

                // If loan was overdue, send overdue repayment mail
                if (Carbon::parse($payment_request->invoice->due_date)->lessThan(Carbon::parse($request->pay_date))) {
                  // $payment_request->notifyUsers('OverdueFullRepayment');
                }
              } else {
                // $payment_request->notifyUsers('PartialRepayment');
              }
            }
          } elseif (
            $payment_request->invoice->financing_status === 'pending' ||
            $payment_request->invoice->financing_status === 'submitted' ||
            $payment_request->invoice->financing_status === 'financed'
          ) {
            if (
              $cbs_transaction->transaction_type === CbsTransaction::OD_DRAWDOWN ||
              $cbs_transaction->transaction_type === CbsTransaction::PAYMENT_DISBURSEMENT ||
              $cbs_transaction->transaction_type === CbsTransaction::ACCRUAL_POSTED_INTEREST ||
              $cbs_transaction->transaction_type === CbsTransaction::FEES_CHARGES
            ) {
              if ($payment_request->invoice->all_transactions_successful) {
                // Get the amount that was requested
                // $requested_amount = $payment_request->amount;
                $requested_amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) use (
                  $cbs_transaction
                ) {
                  $q->where('invoice_id', $cbs_transaction->paymentRequest->invoice_id);
                })
                  ->where('type', 'vendor_account')
                  ->first()->amount;
                $payment_request->invoice->update([
                  'disbursement_date' => Carbon::parse($request->pay_date)->format('Y-m-d'),
                  'disbursed_amount' => round($requested_amount, 2),
                  'financing_status' => 'disbursed',
                ]);

                $program_vendor_configuration = ProgramVendorConfiguration::where(
                  'company_id',
                  $payment_request->invoice->company_id
                )
                  ->when($payment_request->invoice->buyer_id, function ($query) use ($payment_request) {
                    $query->where('buyer_id', $payment_request->invoice->buyer_id);
                  })
                  ->where('program_id', $payment_request->invoice->program_id)
                  ->first();

                LoanDisbursalNotification::dispatch([$payment_request->invoice_id])->afterResponse();

                // Check program limit usage
                if ($payment_request->invoice->program->programType->name === Program::DEALER_FINANCING) {
                  $sanctioned_limit = $payment_request->invoice->program->vendorConfigurations
                    ->where('company_id', $payment_request->invoice->company_id)
                    ->first()->sanctioned_limit;
                  $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                    $payment_request->invoice->program
                  );
                  if ($utilized_amount >= $sanctioned_limit) {
                    // Notify company users
                    $payment_request->invoice->company->notify(
                      new ProgramLimitDepletion($payment_request->invoice->program)
                    );
                  }
                } else {
                  if ($payment_request->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
                    $sanctioned_limit = $payment_request->invoice->program->vendorConfigurations
                      ->where('company_id', $payment_request->invoice->company_id)
                      ->first()->sanctioned_limit;
                    $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                      $payment_request->invoice->program
                    );
                    if ($utilized_amount >= $sanctioned_limit) {
                      // Notify company users
                      $payment_request->invoice->company->notify(
                        new ProgramLimitDepletion($payment_request->invoice->program)
                      );
                    }
                  } else {
                    $sanctioned_limit = $payment_request->invoice->program->vendorConfigurations
                      ->where('buyer_id', $payment_request->invoice->buyer_id)
                      ->first()->sanctioned_limit;
                    $utilized_amount = $payment_request->invoice->company->utilizedAmount(
                      $payment_request->invoice->program
                    );
                    if ($utilized_amount >= $sanctioned_limit) {
                      // Notify company users
                      $payment_request->invoice->company->notify(
                        new ProgramLimitDepletion($payment_request->invoice->program)
                      );
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    if ($request->status === 'Permanently Failed' || $request->status === 'Failed') {
      if ($payment_request) {
        $payment_request->update([
          'status' => 'failed',
          'approval_status' => 'rejected',
        ]);

        if (
          $payment_request->invoice->financing_status === 'pending' ||
          $payment_request->invoice->financing_status === 'submitted'
        ) {
          if ($payment_request->invoice) {
            $payment_request->invoice->update([
              'financing_status' => 'denied',
            ]);
          }

          foreach ($payment_request->invoice->program->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'DisbursementFailed', [
              'id' => $cbs_transaction->paymentRequest->invoice->id,
              'url' => config('app.url') . '/' . $bank->url,
              'name' => $user->name,
              'type' => 'vendor_financing',
            ]);
          }
        }

        if ($payment_request->invoice->financing_status === 'submitted' && $request->status === 'Permanently Failed') {
          $program_vendor_configuration = ProgramVendorConfiguration::where(
            'payment_account_number',
            $cbs_transaction->debit_from_account
          )->first();

          // Update Program and Company Pipeline and Utilized Amounts if affects OD
          if ($program_vendor_configuration) {
            $payment_request->invoice->company->decrement(
              'utilized_amount',
              $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                ? $payment_request->invoice->drawdown_amount
                : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
            );

            $payment_request->invoice->program->decrement(
              'utilized_amount',
              $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                ? $payment_request->invoice->drawdown_amount
                : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
            );

            $program_vendor_configuration->decrement(
              'utilized_amount',
              $payment_request->invoice->program->programType->name === Program::DEALER_FINANCING
                ? $payment_request->invoice->drawdown_amount
                : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->calculated_total_amount
            );
          }
        }

        if (
          $payment_request->invoice->financing_status === 'disbursed' &&
          ($cbs_transaction->transaction_type === CbsTransaction::OVERDUE_ACCOUNT ||
            $cbs_transaction->transaction_type === CbsTransaction::FEES_CHARGES ||
            $cbs_transaction->transaction_type === CbsTransaction::ACCRUAL_POSTED_INTEREST) &&
          Carbon::parse($cbs_transaction->pay_date)->greaterThan($payment_request->invoice->due_date)
        ) {
          foreach ($payment_request->invoice->program->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'CbsTransactionForLoanSettlementFailed', [
              'cbs_transaction_id' => $cbs_transaction->id,
            ]);
          }
        }
      }
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn(CbsTransaction::find($transaction_id))
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated status to ' . $request->status);

    return response()->json(['cbs_transaction' => $cbs_transaction]);
  }

  public function importPaymentRequests(Request $request, Bank $bank)
  {
    $request->validate([
      'payment_requests' => ['required'],
    ]);

    $import = new PaymentRequestsImport($bank, $bank->companies->pluck('name')->toArray());
    $import->import($request->file('payment_requests')->store('public'));

    $batch = ImportError::where('user_id', auth()->id())
      ->where('module', 'PaymentRequests')
      ->orderBy('created_at', 'DESC')
      ->first();

    $data = [];

    foreach ($import->failures() as $failure) {
      $data[] = [
        'user_id' => auth()->id(),
        'batch_id' => $batch ? $batch->batch_id + 1 : 1,
        'row' => $failure->row(),
        'attribute' => $failure->attribute(),
        'values' => json_encode($failure->values()),
        'errors' => json_encode($failure->errors()),
        'module' => 'PaymentRequests',
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    if (count($data) > 0) {
      ImportError::insert($data);

      // Download excel file with errors
      return Excel::download(new PaymentRequestsErrorReport(), 'IF_payment_requests_error_report.xlsx');
    }

    // Check if new Payment requests have been addded
    $payment_requests = PaymentRequest::whereHas('invoice', function ($query) use ($bank) {
      $query->whereHas('program', function ($query) use ($bank) {
        $query->where('bank_id', $bank->id);
      });
    })
      ->whereBetween('created_at', [now()->subMinutes(1), now()->addMinutes(1)])
      ->count();

    if ($payment_requests == 0) {
      toastr()->error('', 'Uploaded empty file');

      return back();
    }

    toastr()->success('', 'Payment Requests uploaded successfully');

    return back();
  }

  public function uploadedPaymentRequests(Bank $bank)
  {
    return view('content.bank.requests.uploaded-payment-requests');
  }

  public function uploadedPaymentRequestsData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('uploaded_date');

    $invoices = PaymentRequestUploadReport::where('bank_id', $bank->id)
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('upload_status', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . $invoice_number . '%');
      })
      ->when($upload_date && $upload_date != '', function ($query) use ($upload_date) {
        $query->whereDate('created_at', $upload_date);
      })
      ->latest()
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function downloadSample(Bank $bank)
  {
    if (request()->wantsJson()) {
      return Storage::disk('public')->download('IF_Template.csv', 'IF-Template-' . now()->format('d M Y') . '.csv');
    }
    return Storage::disk('public')->download('IF_Template.csv', 'IF-Template-' . now()->format('d M Y') . '.csv');
  }

  public function download(Bank $bank, Invoice $invoice)
  {
    $header = asset('assets/img/branding/logo-name.png');
    $description = $invoice->company->invoiceSetting->description;
    $footer = $invoice->company->invoiceSetting->footer;

    $pdf = Pdf::loadView('pdf.invoice', [
      'invoice' => $invoice,
      'header' => $header,
      'items' => $invoice->invoiceItems,
      'taxes' => $invoice->invoiceTaxes,
      'fees' => $invoice->invoiceFees,
      'discount' => $invoice->total_invoice_discount,
      'description' => $description,
      'footer' => $footer,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Invoice_' . $invoice->invoice_number . '.pdf');
  }

  public function downloadPaymentRequest(Bank $bank, PaymentRequest $paymentRequest)
  {
    $header = asset('assets/img/branding/logo-name.png');

    $paymentRequest->invoice['vendor_configurations'] = ProgramVendorConfiguration::where(
      'company_id',
      $paymentRequest->invoice->company->id
    )
      ->where('program_id', $paymentRequest->invoice->program_id)
      ->select('eligibility', 'payment_account_number')
      ->first();

    $pdf = Pdf::loadView('pdf.payment_request', ['payment_request' => $paymentRequest, 'header' => $header])->setPaper(
      'a4',
      'landscape'
    );

    return $pdf->download('Payment_Request_' . $paymentRequest->reference_number . '.pdf');
  }

  public function downloadPaymentInstruction(Bank $bank, Invoice $invoice)
  {
    $header = asset('assets/img/branding/logo-name.png');
    $description = $invoice->company->invoiceSetting->description;
    $footer = $invoice->company->invoiceSetting->footer;

    $pdf = Pdf::loadView('pdf.payment-instruction', [
      'invoice' => $invoice,
      'header' => $header,
      'items' => $invoice->invoiceItems,
      'taxes' => $invoice->invoiceTaxes,
      'fees' => $invoice->invoiceFees,
      'discount' => $invoice->total_invoice_discount,
      'description' => $description,
      'footer' => $footer,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Payment_Instruction_' . $invoice->invoice_number . '.pdf');
  }

  public function downloadPurchaseOrder(Bank $bank, PurchaseOrder $purchase_order)
  {
    $header = asset('assets/img/branding/logo-name.png');

    $pdf = Pdf::loadView('pdf.purchase-order', [
      'purchase_order' => $purchase_order,
      'header' => $header,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Purchase_Order_' . $purchase_order->po_number . '.pdf');
  }

  public function invoiceDetails(Bank $bank, Invoice $invoice)
  {
    sleep(1);
    if (request()->wantsJson()) {
      return response()->json(
        new InvoiceDetailsResource(
          $invoice->load(
            'program.anchor',
            'company',
            'invoiceItems',
            'invoiceFees',
            'invoiceTaxes',
            'invoiceDiscounts',
            'purchaseOrder.purchaseOrderItems'
          )
        )
      );
    }

    return new InvoiceDetailsResource(
      $invoice->load(
        'program.anchor',
        'company',
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'invoiceDiscounts',
        'purchaseOrder.purchaseOrderItems'
      )
    );
  }

  public function uploadInvoiceAttachment(Request $request)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'id' => 'required',
        'files' => ['required', 'array'],
        'files.*' => ['file', 'mimes:pdf', 'max:10000'],
      ],
      [
        'files.required' => 'Select a file to upload',
        'files.*.file' => 'The attachment must be a PDF file less than 10 MB',
        'files.*.mimes' => 'The file type must be a pdf',
      ]
    );

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $invoice = Invoice::find($request->id);

    if (!$invoice) {
      return response()->json(['message' => 'Invalid Invoice Number'], 404);
    }

    $bank_user = BankUser::where('user_id', auth()->id())->first();

    foreach ($request->files as $files) {
      foreach ($files as $file) {
        $invoice
          ->addMedia($file)
          ->withCustomProperties([
            'user_type' => $bank_user ? Bank::class : Company::class,
            'user_name' => auth()->user()->name,
          ])
          ->toMediaCollection('invoice');
      }
    }

    return response()->json(new InvoiceDetailsResource($invoice), 200);
  }

  public function purchaseOrderDetails(Bank $bank, PurchaseOrder $purchase_order)
  {
    if (request()->wantsJson()) {
      return response()->json($purchase_order->load('company', 'anchor', 'purchaseOrder.purchaseOrderItems'));
    }

    return $purchase_order->load('company', 'anchor', 'purchaseOrder.purchaseOrderItems');
  }
}
