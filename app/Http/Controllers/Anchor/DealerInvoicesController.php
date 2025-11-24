<?php

namespace App\Http\Controllers\Anchor;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Models\Currency;
use App\Models\InvoiceFee;
use App\Models\InvoiceTax;
use App\Models\BankHoliday;
use App\Models\BankTaxRate;
use App\Models\CompanyUser;
use App\Models\ImportError;
use App\Models\InvoiceItem;
use App\Models\NoaTemplate;
use App\Models\ProgramCode;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Exports\InvoicesExport;
use App\Imports\InvoicesImport;
use App\Models\InvoiceApproval;
use App\Models\InvoiceDiscount;
use App\Models\ProgramDiscount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Models\ProgramVendorFee;
use App\Exports\UploadedInvoices;
use App\Models\BankPaymentAccount;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceUploadReport;
use App\Exports\CashPlannerInvoices;
use App\Exports\InvoicesErrorReport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DealerInvoicesImport;
use App\Imports\VendorInvoicesImport;
use App\Models\CompanyInvoiceSetting;
use App\Models\ProgramVendorDiscount;
use App\Models\TermsConditionsConfig;
use App\Notifications\InvoiceCreated;
use App\Notifications\InvoiceUpdated;
use App\Http\Resources\InvoiceResource;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\InvoiceCollection;
use App\Models\BankProductsConfiguration;
use Illuminate\Support\Facades\Validator;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Exports\FactoringInvoicesErrorReport;
use App\Http\Resources\CbsTransactionResource;
use App\Http\Resources\InvoiceApprovalResource;
use App\Http\Resources\InvoiceDetailsResource;
use App\Http\Resources\OdAccountsResource;
use App\Http\Resources\PaymentRequestResource;
use App\Jobs\AutoRequestFinancing;
use App\Jobs\BulkRequestFinancing;
use App\Jobs\InvoiceUpdateNotification;
use App\Mail\AutoRequestFinance;
use App\Models\AuthorizationMatrixRule;
use App\Models\BankConvertionRate;
use App\Models\BankGeneralProductConfiguration;
use App\Models\CbsTransaction;
use App\Models\CompanyAuthorizationGroup;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\CompanyUserAuthorizationGroup;
use App\Models\FinanceRequestApproval;
use App\Models\InvoiceProcessing;
use App\Notifications\PaymentRequestNotification;
use App\Models\ProgramBankDetails;

class DealerInvoicesController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $can_create_invoice = false;

    // Get programs
    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->pluck('program_id');

    $invoices = Invoice::dealerFinancing()
      ->whereHas('program', function ($query) {
        $query->where(function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        });
      })
      ->whereIn('program_id', $programs)
      ->count();

    $pending_invoices = Invoice::dealerFinancing()
      ->whereHas('program', function ($query) {
        $query->where(function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        });
      })
      ->whereIn('program_id', $programs)
      ->whereIn('status', ['submitted'])
      ->count();

    if (session()->has('uploaded-invoices') && session()->get('uploaded-invoices') > 0) {
      $uploaded_invoices = session()->get('uploaded-invoices');
      if ($uploaded_invoices > 0) {
        toastr()->success('', session()->get('uploaded-invoices') . ' invoice(s) uploaded successfully');
      }
      session()->forget('uploaded-invoices');
    }

    $failed_to_upload = 0;

    if (session()->has('total-invoices') && session()->get('total-invoices') > 0) {
      $failed_to_upload = session()->get('total-invoices') - $uploaded_invoices;
      if ($failed_to_upload > 0) {
        toastr()->error('', $failed_to_upload . ' invoice(s) failed to upload.');
      }
      session()->forget('total-invoices');
    }

    return view(
      'content.anchor.dealer.invoice.invoices',
      compact('invoices', 'pending_invoices', 'can_create_invoice')
    );
  }

  public function dealers()
  {
    return view('content.anchor.dealer.dealers');
  }

  public function dealersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendors_search = $request->query('vendors');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = Program::whereHas('anchor', function ($query) use ($company) {
      $query->where('companies.id', $company->id);
    })
      ->where(function ($query) {
        $query->whereHas('programType', fn($query) => $query->where('name', Program::DEALER_FINANCING));
      })
      ->pluck('id');

    $vendors = OdAccountsResource::collection(
      ProgramVendorConfiguration::whereIn('program_id', $programs)
        ->when($vendors_search && $vendors_search != '', function ($query) use ($vendors_search) {
          $query
            ->whereHas('company', function ($query) use ($vendors_search) {
              $query->where('name', 'LIKE', '%' . $vendors_search . '%');
            })
            ->orWhereHas('buyer', function ($query) use ($vendors_search) {
              $query->where('name', 'LIKE', '%' . $vendors_search . '%');
            });
        })
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json(['data' => $vendors]);
  }

  public function downloadSample()
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('anchor-factoring-invoices.xlsx'), 'anchor-df-invoices.xlsx');
    }

    return response()->download(public_path('anchor-factoring-invoices.xlsx'), 'anchor-df-invoices.xlsx');
  }

  public function import(Request $request)
  {
    $request->validate([
      'invoices' => ['required', 'mimes:xlsx'],
    ]);

    $programs = [];
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $import = new DealerInvoicesImport($company, Program::DEALER_FINANCING);
    Excel::import($import, $request->file('invoices')->store('public'));

    if ($request->wantsJson()) {
      if ($import->data > 0) {
        return response()->json(
          [
            'message' => 'Some invoices uploaded successfully.',
            'uploaded' => $import->data,
            'total_rows' => $import->total_rows,
          ],
          400
        );
      }

      return response()->json(
        [
          'message' => 'No Invoices were uploaded successfully',
          'uploaded' => $import->data,
          'total_rows' => collect($import->total_rows)->first() - 1,
        ],
        400
      );
    }

    toastr()->success('', 'Invoices uploaded successfully');

    return back();
  }

  public function downloadErrorReport()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $date = now()->format('Y-m-d');

    Excel::store(new FactoringInvoicesErrorReport($company), 'Invoices_error_report_' . $date . '.xlsx', 'exports');

    return Storage::disk('exports')->download('Invoices_error_report_' . $date . '.xlsx');
  }

  public function invoicesData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $buyer = $request->query('buyer');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $finance_status = $request->query('financing_status');
    $per_page = $request->query('per_page');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $sort_by = $request->query('sort_by');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->pluck('program_id');

    $invoices = Invoice::dealerFinancing()
      ->with('approvals')
      ->whereIn('program_id', $programs)
      ->when($buyer && $buyer != '', function ($query) use ($buyer) {
        $query->where(function ($query) use ($buyer) {
          $query->whereHas('company', function ($query) use ($buyer) {
            $query->where('name', 'LIKE', '%' . $buyer . '%');
          });
        });
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('due_date', '>=', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('due_date', '<=', $to_date);
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        $query->whereIn('stage', $status);
        // if (collect($status)->contains('past_due')) {
        // } else {
        //   $query->whereDate('due_date', '>=', now()->format('Y-m-d'))->whereIn('stage', $status);
        // }
      })
      ->when($finance_status && count($finance_status) > 0, function ($query) use ($finance_status) {
        $query->whereIn('financing_status', $finance_status);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('updated_at', 'DESC');
      })
      ->when($sort_by && $sort_by != '', function ($query) use ($sort_by) {
        if ($sort_by == 'po_asc') {
          $query->whereHas('purchaseOrder', function ($query) {
            $query->orderBy('purchase_order_number', 'ASC');
          });
        } elseif ($sort_by == 'po_desc') {
          $query->whereHas('purchaseOrder', function ($query) {
            $query->orderBy('purchase_order_number', 'DESC');
          });
        } elseif ($sort_by == 'invoice_no_asc') {
          $query->orderBy('invoice_number', 'ASC');
        } elseif ($sort_by == 'invoice_no_desc') {
          $query->orderBy('invoice_number', 'DESC');
        } elseif ($sort_by == 'invoice_amount_asc') {
          $query->orderBy('total_amount', 'ASC');
        } elseif ($sort_by == 'invoice_amount_desc') {
          $query->orderBy('total_amount', 'DESC');
        } elseif ($sort_by == 'buyer_asc') {
          $query->join('companies', 'companies.id', '=', 'invoices.buyer_id')->orderBy('companies.name', 'ASC');
        } elseif ($sort_by == 'buyer_desc') {
          $query->join('companies', 'companies.id', '=', 'invoices.buyer_id')->orderBy('companies.name', 'DESC');
        } elseif ($sort_by == 'due_date_asc') {
          $query->orderBy('due_date', 'ASC');
        } elseif ($sort_by == 'due_date_desc') {
          $query->orderBy('due_date', 'DESC');
        }
      })
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    $taxes = ['Withholding VAT' => 'percentage', 'Withholding Tax' => 'percentage', 'Credit Note Amount' => 'amount'];

    $anchor_users = CompanyUser::where('company_id', $company->id)
      ->where('active', true)
      ->pluck('user_id');

    $users = User::whereIn('id', $anchor_users)
      ->where('is_active', true)
      ->get();

    if (request()->wantsJson()) {
      return response()->json([
        'invoices' => $invoices,
        'taxes' => $taxes,
        'users' => $users,
      ]);
    }
  }

  public function expiredInvoicesData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $buyer = $request->query('buyer');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $finance_status = $request->query('finance_status');
    $per_page = $request->query('per_page');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $current_company->company_id)
      ->whereHas('program', function ($query) {
        $query->where(function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        });
      })
      ->pluck('program_id');

    $invoices = InvoiceResource::collection(
      Invoice::dealerFinancing()
        ->with(['program', 'paymentRequests.paymentAccounts'])
        ->whereIn('program_id', $programs)
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->when($buyer && $buyer != '', function ($query) use ($buyer) {
          $query->whereHas('company', function ($query) use ($buyer) {
            $query->where('name', 'LIKE', '%' . $buyer . '%');
          });
        })
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->when($status && $status != '', function ($query) use ($status) {
          $query->where('status', $status);
        })
        ->when($finance_status && $finance_status != '', function ($query) use ($finance_status) {
          $query->where('financing_status', $finance_status);
        })
        ->orderBy('updated_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices]);
    }
  }

  public function nonEligibleInvoices(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::with(
      'program',
      'invoiceItems',
      'invoiceFees',
      'invoiceTaxes',
      'invoiceDiscounts',
      'paymentRequests.paymentAccounts'
    )
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereDate('due_date', '>=', now())
      ->orderBy('updated_at', 'DESC')
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) < now();
      });

    foreach ($invoices as $invoice) {
      $invoice['total'] = $invoice->total;
      $invoice['total_taxes'] = $invoice->total_invoice_taxes;
      $invoice['total_fees'] = $invoice->total_invoice_fees;
      $invoice['total_discount'] = $invoice->total_invoice_discount;
      $invoice['eligible_for_finance'] = $invoice->eligible_for_finance;
      $invoice['vendor_discount_details'] = ProgramVendorDiscount::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->first();
      $invoice['vendor_configurations'] = ProgramVendorConfiguration::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->first();
      $invoice['vendor_fee_details'] = ProgramVendorFee::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->first();
      $invoice['vendor_contact_details'] = ProgramVendorContactDetail::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->first();
      $invoice['vendor_bank_details'] = ProgramVendorBankDetail::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->get();
      $invoice['anchor'] = $invoice->program->getAnchor();
    }

    if (request()->wantsJson()) {
      return response()->json(['invoices' => Helpers::paginate($invoices->toArray())], 200);
    }
  }

  public function expired()
  {
    return view('content.anchor.dealer.invoice.expired');
  }

  public function uploaded()
  {
    return view('content.anchor.dealer.invoice.uploaded');
  }

  public function uploadedInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('uploaded_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $invoices = InvoiceUploadReport::where(function ($query) {
      $query->where('product_type', Program::DEALER_FINANCING);
    })
      ->where('company_id', $current_company->company_id)
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
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

  public function exportUploadedInvoices(Request $request)
  {
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('upload_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $date = now()->format('Y-m-d');

    Excel::store(
      new UploadedInvoices($current_company, $invoice_number, $status, $upload_date, 'Anchor Factoring'),
      'Uploaded_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Uploaded_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function drawdownCreate(Invoice $invoice = null)
  {
    $purchase_orders = [];
    $attachment_required = false;
    $vendor_configuration = null;

    if ($invoice) {
      $invoice = new InvoiceDetailsResource(
        $invoice->load('invoiceItems', 'invoiceTaxes', 'invoiceFees', 'invoiceDiscounts')
      );
      $attachment_required = $invoice->program->mandatory_invoice_attachment;
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $invoice->program->id)
        ->where('company_id', $invoice->company->id)
        ->first();
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get companies in Factoring programs
    $dealers = [];
    $dealer_role = ProgramRole::where('name', 'dealer')->first();
    foreach ($company->programs as $program) {
      $role = ProgramCompanyRole::where([
        'program_id' => $program->id,
        'role_id' => $dealer_role->id,
      ])->first();
      if ($role && $program->programType->name == 'Dealer Financing') {
        foreach ($program->getDealers() as $dealer) {
          array_push($dealers, $dealer);
        }
      }
    }

    $dealers = collect($dealers)->unique();

    $taxes = [];

    $company_taxes = $company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    $bank_default_currency = Currency::find($company->bank->adminConfiguration?->defaultCurrency);

    // Get Currencies that have a conversion rate set
    $bank_converstion_rates = BankConvertionRate::where('bank_id', $company->bank_id)
      ->where('rate', '!=', 0)
      ->pluck('from_currency');
    if ($bank_converstion_rates->count() > 0) {
      $currencies = Currency::whereIn('code', [$bank_converstion_rates, $bank_default_currency->code])->get();
    } else {
      $currencies = Currency::whereIn('code', [$bank_default_currency?->code])->get();
    }

    if ($currencies->count() <= 0) {
      $currencies = Currency::where('name', 'Kenyan Shilling')->get();
    }

    return view('content.anchor.dealer.invoice.drawdown', [
      'dealers' => $dealers,
      'purchase_orders' => $purchase_orders,
      'invoice' => $invoice,
      'taxes' => $taxes,
      'currencies' => $currencies,
      'attachment_required' => $attachment_required,
      'vendor_configuration' => $vendor_configuration,
    ]);
  }

  public function storeAttachments(Request $request)
  {
    $path = storage_path('app/public/tmp/uploads');

    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }

    $file = $request->file('file');

    $name = uniqid() . '_' . trim($file->getClientOriginalName());

    $file->move($path, $name);

    return response()->json([
      'name' => $name,
      'original_name' => $file->getClientOriginalName(),
    ]);
  }

  public function drawdownStore(Request $request)
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $request->validate([
      'item' => ['required', 'array', 'min:1'],
      'item.*' => ['required', 'string'],
      'quantity' => ['required', 'array', 'min:1'],
      'quantity.*' => ['required', 'string'],
      'unit' => ['required', 'array', 'min:1'],
      'unit.*' => ['required', 'string'],
      'price_per_quantity' => ['required', 'array', 'min:1'],
      'price_per_quantity.*' => ['required', 'string'],
      'program_id' => ['required'],
      'dealer' => ['required'],
      'currency' => ['required'],
      'invoice_date' => ['required', 'date'],
      'due_date' => ['required', 'date'],
      'program_id' => ['required'],
    ]);

    $dealer_company = Company::find($request->dealer);

    if ($dealer_company) {
      $request->validate([
        'invoice_number' => [
          'required',
          Rule::unique('invoices')->where(function ($query) use ($dealer_company) {
            return $query->where('company_id', $dealer_company->id);
          }),
        ],
      ]);
    }

    $program = Program::find($request->program_id);

    if ($program && $program->mandatory_invoice_attachment) {
      $request->validate([
        'invoice' => ['required', 'mimes:pdf'],
      ]);
    }

    $program_role = ProgramRole::where('name', 'dealer')->first();

    $program = ProgramCompanyRole::where('role_id', $program_role->id)
      ->where('program_id', $request->program_id)
      ->where('company_id', $request->dealer)
      ->first();

    $dealer_financing_id = ProgramType::where('name', Program::DEALER_FINANCING)->first();

    try {
      $invoice = Invoice::create([
        'program_id' => $program->program_id,
        'company_id' => $request->dealer,
        'invoice_number' => $request->invoice_number,
        'invoice_date' => Carbon::parse($request->invoice_date)->format('Y-m-d'),
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'currency' => $request->currency,
        'remarks' => $request->remarks,
        'status' => 'approved',
        'stage' => 'approved',
        'financing_status' => 'pending',
        'eligible_for_financing' => true,
        'purchase_order_id' =>
          $request->has('purchase_order') &&
          !empty($request->purchase_order) &&
          $request->purchase_order != 'Select Purchase Order' &&
          $request->purchase_order != ''
            ? $request->purchase_order
            : null,
      ]);

      foreach ($request->item as $key => $item) {
        if (
          array_key_exists($key, $request->quantity) &&
          (float) str_replace(',', '', $request->quantity[$key]) > 0 &&
          array_key_exists($key, $request->unit) &&
          array_key_exists($key, $request->price_per_quantity) &&
          (float) str_replace(',', '', $request->price_per_quantity[$key]) > 0
        ) {
          $invoice_item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item' => $item,
            'quantity' => array_key_exists($key, $request->quantity)
              ? str_replace(',', '', $request->quantity[$key])
              : null,
            'unit' => array_key_exists($key, $request->unit) ? $request->unit[$key] : null,
            'price_per_quantity' => array_key_exists($key, $request->price_per_quantity)
              ? str_replace(',', '', $request->price_per_quantity[$key])
              : null,
          ]);

          $invoice_item_discount = 0;

          if (
            $request->has('discount_type') &&
            !empty($request->discount_type) &&
            count($request->discount_type) > 0 &&
            ($request->has('discount_value') && !empty($request->discount_value) && count($request->discount_value) > 0)
          ) {
            if (
              array_key_exists($key, $request->discount_value) &&
              (float) str_replace(',', '', $request->discount_value[$key]) > 0
            ) {
              $invoice_item_discount =
                (str_replace(',', '', $request->discount_value[$key]) / 100) *
                ($invoice_item->price_per_quantity * $invoice_item->quantity);
              $invoice_item->invoiceDiscount()->create([
                'invoice_id' => $invoice->id,
                'type' => $request->discount_type[$key],
                'value' => str_replace(',', '', $request->discount_value[$key]),
              ]);
            }
          }

          if (
            $request->has('tax_name') &&
            !empty($request->tax_name) &&
            count($request->tax_name) > 0 &&
            array_key_exists($key, $request->tax_name) &&
            array_key_exists($key, $request->tax_value) &&
            $request->tax_value[$key] > 0
          ) {
            if (array_key_exists($key, $request->tax_value) && array_key_exists($key, $request->tax_name)) {
              foreach ($request->tax_value[$key] as $tax_key => $tax_value) {
                InvoiceTax::create([
                  'invoice_id' => $invoice->id,
                  'invoice_item_id' => $invoice_item->id,
                  'name' => $request->tax_name[$key][$tax_key],
                  'value' =>
                    ($tax_value / 100) *
                    ($invoice_item->quantity * $invoice_item->price_per_quantity - $invoice_item_discount),
                ]);
              }
            }
          }
        }
      }

      // Get Dealer's Configuration
      $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $request->dealer)
        ->where('program_id', $program->program_id)
        ->select('withholding_tax', 'withholding_vat')
        ->first();

      if ($program_vendor_configuration) {
        if ($program_vendor_configuration->withholding_tax) {
          $invoice_fees = new InvoiceFee();
          $invoice_fees->invoice_id = $invoice->id;
          $invoice_fees->name = 'Withholding Tax';
          $invoice_fees->amount =
            (float) (($program_vendor_configuration->withholding_tax / 100) *
              ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
          $invoice_fees->save();
        }

        if ($program_vendor_configuration->withholding_vat) {
          $invoice_fees = new InvoiceFee();
          $invoice_fees->invoice_id = $invoice->id;
          $invoice_fees->name = 'Withholding VAT';
          $invoice_fees->amount =
            (float) (($program_vendor_configuration->withholding_vat / 100) *
              ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
          $invoice_fees->save();
        }
      }

      $invoice->update([
        'total_amount' => $invoice->total,
        'calculated_total_amount' => $invoice->invoice_total_amount,
      ]);

      if ($request->has('invoice') && !empty($request->invoice)) {
        foreach ($request->input('invoice', []) as $file) {
          $invoice
            ->addMedia(storage_path('app/public/tmp/uploads/' . $file))
            ->withCustomProperties(['user_type' => Company::class, 'user_name' => auth()->user()->name])
            ->toMediaCollection('invoice');
        }
      }

      if ($invoice->purchaseOrder) {
        $invoice->purchaseOrder->update([
          'status' => 'invoiced',
        ]);
      }

      $matrix = CompanyAuthorizationMatrix::where('company_id', $company->id)
        ->where('min_pi_amount', '<=', $invoice->invoice_total_amount)
        ->where('max_pi_amount', '>=', $invoice->invoice_total_amount)
        ->where('program_type_id', $dealer_financing_id->id)
        ->where('status', 'active')
        ->first();

      if (!$matrix) {
        toastr()->error('', 'Authorization Matrix for the invoice amount is not available');

        return back()->withInput();
      } else {
        $company_authorization_group = CompanyAuthorizationGroup::where('company_id', $company->id)
          ->where('program_type_id', $dealer_financing_id->id)
          ->first();
        $user_matrix = CompanyUserAuthorizationGroup::where('user_id', auth()->id())
          ->where('company_id', $company->id)
          ->where('program_type_id', $dealer_financing_id->id)
          ->where('group_id', $company_authorization_group?->id)
          ->first();

        $invoice->update([
          'status' => 'submitted',
          'stage' => $user_matrix ? 'pending_checker' : 'pending_maker',
          'eligible_for_financing' => false,
        ]);

        if ($user_matrix) {
          // If user is in matrix, then add approval
          $invoice->approvals()->create([
            'user_id' => auth()->id(),
          ]);

          // $invoice->company->notify(new InvoiceCreated($invoice));
        }

        activity($invoice->program->bank->id)
          ->causedBy(auth()->user())
          ->performedOn($invoice)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
          ->log('created invoice');

        DB::commit();

        // Send Email to Invoice Checker
        $company_users = CompanyUser::where('company_id', $company->id)
          // ->where('user_id', '!=', auth()->id())
          ->whereHas('user', function ($query) {
            $query->whereHas('roles', function ($query) {
              $query->whereHas('permissions', function ($query) {
                $query->where('name', 'Invoice Checker');
              });
            });
          })
          ->get();

        foreach ($company_users as $company_user) {
          SendMail::dispatchAfterResponse($company_user->user->email, 'InvoiceCreated', [
            'id' => $invoice->id,
            'company_id' => $company->id,
            'type' => 'dealer_financing',
          ]);
        }

        toastr()->success('', 'Invoice created successfully');

        return redirect()->route('anchor.dealer-invoices-index');
        DB::beginTransaction();
      }
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
    }
  }

  public function drawdownPrograms(Company $company)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $anchors_programs = ProgramCompanyRole::where([
      'role_id' => $anchor_role->id,
      'company_id' => $current_company->company_id,
    ])
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->pluck('program_id');

    $filtered_programs = ProgramCompanyRole::whereIn('program_id', $anchors_programs)
      ->where('role_id', $dealer_role->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::whereIn('program_id', $filtered_programs)
      ->where('company_id', $company->id)
      ->get();

    return response()->json(['programs' => $programs]);
  }

  public function approveFees(Invoice $invoice)
  {
    if (!$invoice->user_can_approve) {
      if (!request()->wantsJson()) {
        toastr()->error('', 'You cannot perform this action in this invoice');

        return back();
      }

      return response()->json('You cannot perform this action on the invoice', 403);
    }

    ini_set('max_execution_time', 600);
    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $invoice->load('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts', 'company', 'program.programType');

    // Add user approval to invoice approvals
    $invoice->approvals()->create([
      'user_id' => auth()->id(),
    ]);

    // Maker/Checker is required
    $approvals = InvoiceApproval::where('invoice_id', $invoice->id)
      ->whereIn('user_id', $company->users->pluck('id'))
      ->get();

    // Get authorization matrix
    $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->program->anchor->id)
      ->where('min_pi_amount', '<=', $invoice->total + $invoice->total_invoice_taxes)
      ->where('max_pi_amount', '>=', $invoice->total + $invoice->total_invoice_taxes)
      ->where('status', 'active')
      ->where('program_type_id', $invoice->program->program_type_id)
      ->first();

    $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)->get();

    // Calculate number of required approvals based on rules
    $required_approvals = 0;
    foreach ($rules as $rule) {
      if (!$rule->operator) {
        $required_approvals += $rule->min_approval;
      } elseif ($rule->operator == 'and') {
        $required_approvals += $rule->min_approval;
      } else {
        $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->program->anchor->id)
          ->where('group_id', $rule->group_id)
          ->where('user_id', auth()->id())
          ->first();
        if ($user_authorization_group) {
          $user_rule = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)
            ->where('group_id', $user_authorization_group->group_id)
            ->first();
          $required_approvals = $user_rule->min_approval;
        }
      }
    }

    if ($required_approvals > 0) {
      if (count($approvals) < $required_approvals) {
        $invoice->update([
          'stage' => 'pending_checker',
        ]);

        // Company has approvers
        $users = User::whereIn('id', $company->users->pluck('id'))
          ->where('id', '!=', auth()->id())
          ->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Dealer Invoice Checker');
            });
          })
          ->get();

        if ($users->count() > 1) {
          foreach ($users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoiceUpdated', [
              'id' => $invoice->id,
              'type' => 'dealer_financing',
            ]);
          }
        }
      } else {
        // Check if all approvals are done and change to approved
        $invoice->update([
          'status' => 'approved',
          'stage' => 'approved',
          'pi_number' => 'PI_' . $invoice->id,
          'eligible_for_financing' => true,
          'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
          'calculated_total_amount' => $invoice->invoice_total_amount,
        ]);

        // $invoice->company->notify(new InvoiceUpdated($invoice));
        foreach ($invoice->company->users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InvoiceApproval', [
            'id' => $invoice->id,
            'type' =>
              $invoice->program->programType->name == Program::VENDOR_FINANCING
                ? 'vendor_financing'
                : 'dealer_financing',
          ]);
        }
      }

      if (!request()->wantsJson()) {
        toastr()->success('', 'Invoice successfully approved');

        return back();
      }

      return response()->json('Invoice updated successfully', 200);
    } else {
      if (!request()->wantsJson()) {
        toastr()->error('', 'Authorization matrix not set for invoice amount');

        return back();
      }

      return response()->json('Authorization matrix not set for invoice amount', 404);
    }
  }

  public function approve(Request $request, Invoice $invoice)
  {
    // Check if user has permission to perform the update
    if (!$invoice->user_can_approve) {
      toastr()->error('', 'You don\'t have permission to edit this invoice');
      return redirect()->route('buyer.invoices.index');
    }

    // $validator = Validator::make($request->all(), [
    //   'taxes' => ['required', 'array'],
    // ]);

    // if ($validator->fails()) {
    //   return response()->json($validator->messages(), 422);
    // }

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $anchor_users = $company->users->pluck('id');

    $current_anchor_approvals = InvoiceApproval::where('invoice_id', $invoice->id)
      ->whereIn('user_id', $anchor_users)
      ->count();

    if ($request->has('taxes') && !empty($request->taxes)) {
      foreach ($request->taxes as $tax => $value) {
        if ($value > 0) {
          InvoiceFee::updateOrCreate(
            [
              'invoice_id' => $invoice->id,
              'name' => explode('-', $tax)[0],
            ],
            [
              'amount' => $value,
            ]
          );
        }
      }

      if ($current_anchor_approvals >= 1) {
        // Remove all approvals, to go back to maker for approval
        InvoiceApproval::whereIn('user_id', $anchor_users)
          ->where('invoice_id', $invoice->id)
          ->delete();
      }
    }

    $invoice->load('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts', 'company', 'program.programType');

    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->first();

    // Add user approval to invoice approvals
    $invoice->approvals()->create([
      'user_id' => auth()->id(),
    ]);

    // Dealer Financing
    $approvals = InvoiceApproval::where('invoice_id', $invoice->id)
      ->whereIn('user_id', $company->users->pluck('id'))
      ->count();

    // Get authorization matrix
    $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->program->anchor->id)
      ->where('min_pi_amount', '<=', $invoice->total + $invoice->total_invoice_taxes)
      ->where('max_pi_amount', '>=', $invoice->total + $invoice->total_invoice_taxes)
      ->where('status', 'active')
      ->where('program_type_id', $invoice->program->program_type_id)
      ->first();

    $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)->get();

    // Calculate number of required approvals based on rules
    $required_approvals = 0;
    foreach ($rules as $rule) {
      if (!$rule->operator) {
        $required_approvals += $rule->min_approval;
      } elseif ($rule->operator == 'and') {
        $required_approvals += $rule->min_approval;
      } else {
        $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->program->anchor->id)
          ->where('group_id', $rule->group_id)
          ->where('user_id', auth()->id())
          ->first();
        if ($user_authorization_group) {
          $user_rule = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)
            ->where('group_id', $user_authorization_group->group_id)
            ->first();
          $required_approvals = $user_rule->min_approval;
        }
      }
    }

    if ($required_approvals > 0) {
      if ($approvals < $required_approvals) {
        $invoice->update([
          'stage' => 'pending_checker',
        ]);
        if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
          // Company has approvers
          $users = User::whereIn('id', $company->users->pluck('id'))
            ->where('id', '!=', auth()->id())
            ->whereHas('roles', function ($query) {
              $query->whereHas('permissions', function ($query) {
                $query->where('name', 'Approve Invoices - Level 2');
              });
            })
            ->get();
        } else {
          $users = User::whereIn('id', $company->users->pluck('id'))
            ->where('id', '!=', auth()->id())
            ->whereHas('roles', function ($query) {
              $query->whereHas('permissions', function ($query) {
                $query->where('name', 'Dealer Invoice Checker');
              });
            })
            ->get();
        }

        if ($users->count() > 1) {
          foreach ($users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoiceUpdated', [
              'id' => $invoice->id,
              'type' =>
                $invoice->program->programType->name == Program::VENDOR_FINANCING
                  ? 'vendor_financing'
                  : 'dealer_financing',
            ]);
          }
        }
      } else {
        // Check if all approvals are done and change to approved
        $invoice->update([
          'status' => 'approved',
          'stage' => 'approved',
          'pi_number' => 'PI_' . $invoice->id,
          'eligible_for_financing' =>
            Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now() ? true : false,
          // 'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
          'calculated_total_amount' => $invoice->invoice_total_amount,
        ]);

        // $invoice->company->notify(new InvoiceUpdated($invoice));
        foreach ($invoice->company->users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InvoiceApproval', [
            'id' => $invoice->id,
            'type' =>
              $invoice->program->programType->name == Program::VENDOR_FINANCING
                ? 'vendor_financing'
                : 'dealer_financing',
          ]);
        }

        if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
          // Auto request finance
          if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
            if ($invoice->canRequestFinancing()) {
              $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)->first();

              $invoice->requestFinance($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
            }
          }
        }
      }

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($invoice)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
        ->log('approved invoice');

      if (!request()->wantsJson()) {
        toastr()->success('', 'Invoice successfully approved');

        return back();
      }

      return response()->json('Invoice updated successfully', 200);
    } else {
      if (!request()->wantsJson()) {
        toastr()->error('', 'Failed to approved invoice. Authorization Rule for Invoice Amount Not Found');

        return back();
      }

      return response()->json('Failed to approve invoice. Authorization Rule for invoice amount not found', 400);
    }
  }

  public function bulkApprove(Request $request)
  {
    ini_set('max_execution_time', 800);

    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $anchor_users = $company->users->pluck('id');

    $updated_invoice_fees = json_decode($request->invoice_taxes, true);

    $further_approval_required = false;
    $bulk_request_finance_invoices = [];
    $updated_invoices = [];

    try {
      DB::beginTransaction();

      Invoice::whereIn('id', $request->invoice)->chunk(50, function ($invoices) use (
        $updated_invoice_fees,
        $anchor_users,
        $company,
        &$further_approval_required,
        &$bulk_request_finance_invoices,
        &$updated_invoices
      ) {
        foreach ($invoices as $invoice) {
          if ($invoice->user_can_approve) {
            $current_anchor_approvals = InvoiceApproval::where('invoice_id', $invoice->id)
              ->whereIn('user_id', $anchor_users)
              ->count();

            if (count($updated_invoice_fees) > 0) {
              foreach ($updated_invoice_fees as $invoice_fee_key => $invoice_fee) {
                foreach ($invoice_fee as $data_id => $fee) {
                  if ($data_id == $invoice->id) {
                    foreach ($fee as $fee_name => $fee_data) {
                      $current_invoice_fee = InvoiceFee::where('invoice_id', $invoice->id)
                        ->where('name', explode('-', $fee_name)[0])
                        ->first();

                      if ($current_invoice_fee) {
                        $current_invoice_fee->amount =
                          explode('-', $fee_name)[0] != 'Credit Note Amount'
                            ? (float) round(
                              (((float) $fee_data) / 100) *
                                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount),
                              2
                            )
                            : (float) $fee_data;

                        if (count($current_invoice_fee->getDirty()) > 0) {
                          if ($current_anchor_approvals >= 1) {
                            // Remove all approvals, to go back to maker for approval
                            InvoiceApproval::where('invoice_id', $invoice->id)
                              ->whereIn('user_id', $anchor_users)
                              ->delete();
                          }
                        }
                        $current_invoice_fee->save();
                      } else {
                        if ($fee_data > 0) {
                          // Create new invoice fee
                          $invoice_fee = new InvoiceFee();
                          $invoice_fee->invoice_id = $invoice->id;
                          $invoice_fee->name = explode('-', $fee_name)[0];
                          $invoice_fee->amount =
                            explode('-', $fee_name)[0] != 'Credit Note Amount'
                              ? (float) ((((float) $fee_data) / 100) *
                                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount))
                              : (float) $fee_data;
                          $invoice_fee->save();

                          if ($current_anchor_approvals >= 1) {
                            // Remove all approvals, to go back to maker for approval
                            InvoiceApproval::where('invoice_id', $invoice->id)
                              ->whereIn('user_id', $anchor_users)
                              ->delete();
                          }
                        }
                      }
                    }
                  }
                }
              }
            }

            $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
              ->where('program_id', $invoice->program_id)
              ->first();

            $invoice->approvals()->create([
              'user_id' => auth()->id(),
            ]);

            // Check if approvals require to go through maker/checker
            $invoice_setting = $company->invoiceSetting;

            $approvals = InvoiceApproval::where('invoice_id', $invoice->id)
              ->whereIn('user_id', $anchor_users)
              ->count();

            // Get authorization matrix
            $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->program->anchor->id)
              ->where('min_pi_amount', '<=', $invoice->total + $invoice->total_invoice_taxes)
              ->where('max_pi_amount', '>=', $invoice->total + $invoice->total_invoice_taxes)
              ->where('status', 'active')
              ->where('program_type_id', $invoice->program->program_type_id)
              ->first();

            $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)->get();

            // Calculate number of required approvals based on rules
            $required_approvals = 0;
            foreach ($rules as $rule) {
              if (!$rule->operator) {
                $required_approvals += $rule->min_approval;
              } elseif ($rule->operator == 'and') {
                $required_approvals += $rule->min_approval;
              } else {
                $user_authorization_group = CompanyUserAuthorizationGroup::where(
                  'company_id',
                  $invoice->program->anchor->id
                )
                  ->where('group_id', $rule->group_id)
                  ->where('user_id', auth()->id())
                  ->first();
                if ($user_authorization_group) {
                  $user_rule = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)
                    ->where('group_id', $user_authorization_group->group_id)
                    ->first();
                  $required_approvals = $user_rule->min_approval;
                }
              }
            }

            if ($required_approvals > 0) {
              if ($approvals < $required_approvals) {
                $invoice->update([
                  'stage' => 'pending_checker',
                ]);

                // Company has approvers
                $users = User::whereIn('id', $company->users->pluck('id'))
                  ->where('id', '!=', auth()->id())
                  ->whereHas('roles', function ($query) {
                    $query->whereHas('permissions', function ($query) {
                      $query->where('name', 'Invoice Checker');
                    });
                  })
                  ->get();

                if ($users->count() > 1) {
                  foreach ($users as $user) {
                    SendMail::dispatch($user->email, 'InvoiceUpdated', [
                      'id' => $invoice->id,
                      'type' => 'dealer_financing',
                    ])->afterCommit();
                  }
                }
                $further_approval_required = true;
              } else {
                // Check if all approvals are done and change to approved
                $invoice->update([
                  'status' => 'approved',
                  'stage' => 'approved',
                  'pi_number' => 'PI_' . $invoice->id,
                  'eligible_for_financing' => true,
                  'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
                  'calculated_total_amount' => $invoice->invoice_total_amount,
                ]);

                // $invoice->company->notify(new InvoiceUpdated($invoice));
                array_push($updated_invoices, $invoice->id);

                $further_approval_required = false;
              }

              activity($invoice->program->bank->id)
                ->causedBy(auth()->user())
                ->performedOn($invoice)
                ->withProperties([
                  'ip' => request()->ip(),
                  'device_info' => request()->userAgent(),
                  'user_type' => 'Anchor',
                ])
                ->log('approved invoice');
            }
          }
        }
      });

      if (count($bulk_request_finance_invoices) > 0) {
        AutoRequestFinancing::dispatch($bulk_request_finance_invoices)->afterCommit();
      }

      // Notify users of updated invoices
      if (count($updated_invoices) > 0) {
        InvoiceUpdateNotification::dispatch($company, $updated_invoices)->afterCommit();
      }

      DB::commit();
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Failed to approve. Contact System admin for assistance'], 500);
      }
    }

    if (!request()->wantsJson()) {
      toastr()->success(
        '',
        $further_approval_required
          ? 'Invoices approved successfully and sent for further approval'
          : 'Invoices approved successfully'
      );

      return back();
    }

    return response()->json(
      [
        'message' => $further_approval_required
          ? 'Invoices approved successfully and sent for further approval'
          : 'Invoices approved successfully',
      ],
      200
    );
  }

  public function bulkDetails(Request $request)
  {
    $type = $request->query('type') ?? Program::VENDOR_FINANCING;
    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $invoices_details = [];

    foreach (explode(',', $request->invoices) as $inv) {
      $details = new InvoiceApprovalResource(Invoice::with('invoiceFees')->find($inv));

      array_push($invoices_details, $details);
    }

    return response()->json(['data' => $invoices_details]);
  }

  public function reject(Invoice $invoice, Request $request)
  {
    $invoice->update([
      'status' => 'denied',
      'stage' => 'rejected',
      'rejected_reason' => $request->rejected_reason,
    ]);

    $invoice->company->notify(new InvoiceUpdated($invoice));

    // Send Denied Invoice Email
    foreach ($invoice->company->users as $user) {
      SendMail::dispatchAfterResponse($user->email, 'InvoiceRejection', [
        'invoice_id' => $invoice->id,
        'type' => 'vendor_financing',
      ]);
    }

    activity($invoice->program->bank->id)
      ->causedBy(auth()->user())
      ->performedOn($invoice)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
      ->log('rejected invoice');

    toastr()->success('', 'Invoice status updated');

    return back();
  }

  public function bulkReject(Request $request)
  {
    foreach ($request->invoice as $invoice_id) {
      $invoice = Invoice::find($invoice_id);

      $invoice->update([
        'status' => 'denied',
        'stage' => 'rejected',
        'rejected_reason' => $request->rejected_reason,
      ]);

      $invoice->company->notify(new InvoiceUpdated($invoice));

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($invoice)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
        ->log('rejected invoice');

      // Send Denied Invoice Email
      foreach ($invoice->company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'InvoiceRejection', ['invoice_id' => $invoice->id]);
      }
    }

    if (!request()->wantsJson()) {
      toastr()->success('', 'Invoices successfully updated');

      return back();
    }

    return response()->json('Invoices updated successfully', 200);
  }

  public function payments()
  {
    return view('content.anchor.dealer.invoice.payments');
  }

  public function paymentsData(Request $request)
  {
    $buyer = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $pi_number = $request->query('pi_number');
    $paid_date = $request->query('paid_date');
    $per_page = $request->query('per_page');

    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $current_company->company_id)
      ->whereHas('program', function ($query) {
        $query->where(function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        });
      })
      ->pluck('program_id');

    $invoices = InvoiceResource::collection(
      Invoice::dealerFinancing()
        ->with(['program', 'buyer', 'invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts'])
        ->when($buyer && $buyer != '', function ($query) use ($buyer) {
          $query->whereHas('company', function ($query) use ($buyer) {
            $query->where('name', 'LIKE', '%' . $buyer . '%');
          });
        })
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->when($pi_number && $pi_number != '', function ($query) use ($pi_number) {
          $query->where('pi_number', 'LIKE', '%' . $pi_number . '%');
        })
        ->when($paid_date && $paid_date != '', function ($query) use ($paid_date) {
          $query->whereDate('disbursement_date', $paid_date);
        })
        ->whereHas('paymentRequests', function ($query) {
          $query->where('status', 'paid');
        })
        ->whereIn('program_id', $programs)
        ->latest()
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json($invoices);
  }

  public function checkInvoiceNumber($number, $dealer = null)
  {
    if ($dealer == null) {
      $current_company = auth()
        ->user()
        ->activeAnchorDealerCompany()
        ->first();

      $invoice = Invoice::where('invoice_number', $number)
        ->where('company_id', $current_company->company_id)
        ->first();

      if ($invoice) {
        return response()->json(['exists' => true], 400);
      }

      return response()->json(['exists' => false], 200);
    }

    $invoice = Invoice::where('invoice_number', $number)
      ->where('company_id', $dealer)
      ->first();

    if ($invoice) {
      return response()->json(['exists' => true], 400);
    }

    return response()->json(['exists' => false], 200);
  }

  public function odAccounts()
  {
    return view('content.anchor.dealer.invoice.od-accounts');
  }

  public function odAccountsData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $payment_account_number = $request->query('payment_account_number');
    $dealer = $request->query('dealer');
    $per_page = $request->query('per_page');

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with([
        'invoices' => fn($invoice) => $invoice
          ->where('financing_status', 'disbursed')
          ->whereDate('due_date', '>', now()),
      ])
        ->whereHas('program', function ($query) use ($current_company) {
          $query
            ->whereHas('anchor', function ($query) use ($current_company) {
              $query->where('companies.id', $current_company->company_id);
            })
            ->whereHas('programType', function ($query) {
              $query->where('name', 'Dealer Financing');
            });
        })
        ->when($payment_account_number && $payment_account_number != '', function ($query) use (
          $payment_account_number
        ) {
          $query->where('payment_account_number', 'LIKE', '%' . $payment_account_number . '%');
        })
        ->when($dealer && $dealer != '', function ($query) use ($dealer) {
          $query->whereHas('company', function ($query) use ($dealer) {
            $query->where('name', 'LIKE', '%' . $dealer . '%');
          });
        })
        ->latest()
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json([
      'data' => $programs,
    ]);
  }

  public function odAccountDetails(ProgramVendorConfiguration $program_vendor_configuration)
  {
    $program_vendor_configuration->load('program.anchor', 'company');

    $program = $program_vendor_configuration->program;

    $cbs_transactions = CbsTransactionResource::collection(
      CbsTransaction::whereHas('paymentRequest', function ($query) use ($program) {
        $query->whereHas('invoice', function ($query) use ($program) {
          $query->where('program_id', $program->id);
        });
      })
        ->orWhereHas('creditAccountRequest', function ($query) use ($program) {
          $query->where('program_id', $program->id);
        })
        ->latest()
        ->take(10)
        ->get()
    );

    return view(
      'content.anchor.dealer.invoice.od-account-details',
      compact('program_vendor_configuration', 'cbs_transactions')
    );
  }

  public function odAccountCbsTransactions(Request $request, ProgramVendorConfiguration $program_vendor_configuration)
  {
    $per_page = $request->query('per_page');

    $cbs_transactions = CbsTransactionResource::collection(
      CbsTransaction::with('paymentRequest')
        ->whereHas('paymentRequest', function ($query) use ($program_vendor_configuration) {
          $query->whereHas('invoice', function ($query) use ($program_vendor_configuration) {
            $query->where('program_id', $program_vendor_configuration->program_id);
          });
        })
        ->latest()
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json($cbs_transactions);
  }

  public function paymentInstructions()
  {
    return view('content.anchor.dealer.invoice.dealer-payment-requests');
  }

  public function paymentinstructionsData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $company_programs = [];
    foreach ($company->programs as $program) {
      if ($program->programType->name == Program::DEALER_FINANCING) {
        array_push($company_programs, $program->id);
      }
    }

    $dealer = $request->query('dealer');
    $status = $request->query('status');
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $disbursement_date = $request->query('disbursement_date');
    $financing_status = $request->query('financing_status');

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $invoices = Invoice::dealerFinancing()
      ->whereIn('program_id', $programs_ids)
      ->pluck('id');

    $payment_requests = PaymentRequest::with('invoice', 'paymentAccounts')
      ->whereIn('invoice_id', $invoices)
      ->where(function ($q) {
        $q->whereHas('cbsTransactions', function ($q) {
          $q->whereIn('transaction_type', ['Payment Disbursement']);
        })->orWhereDoesntHave('cbsTransactions');
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('invoice', function ($query) use ($dealer) {
          $query->whereHas('company', function ($query) use ($dealer) {
            $query->where('name', 'LIKE', '%' . $dealer . '%');
          });
        });
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereHas('invoice', function ($query) use ($from_date) {
          $query->whereDate('due_date', '>=', Carbon::parse($from_date));
        });
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereHas('invoice', function ($query) use ($to_date) {
          $query->whereDate('due_date', '<=', Carbon::parse($to_date));
        });
      })
      ->when($disbursement_date && $disbursement_date != '', function ($query) use ($disbursement_date) {
        $query->whereHas('invoice', function ($query) use ($disbursement_date) {
          $query->whereDate('disbursement_date', Carbon::parse($disbursement_date));
        });
      })
      ->when($financing_status && $financing_status != '', function ($query) use ($financing_status) {
        $query->whereHas('invoice', function ($query) use ($financing_status) {
          $query->where('financing_status', $financing_status);
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('approval_status', $status);
      })
      ->latest()
      ->paginate($per_page);

    $payment_requests = PaymentRequestResource::collection($payment_requests)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['finance_requests' => $payment_requests]);
    }
  }

  public function dealerDpdInvoices()
  {
    return view('content.anchor.dealer.invoice.dpd');
  }

  public function dealerDpdInvoicesData(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $dealer = $request->query('dealer');
    $range = $request->query('range');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $company_programs = [];
    foreach ($company->programs as $program) {
      if ($program->programType->name == Program::DEALER_FINANCING) {
        array_push($company_programs, $program->id);
      }
    }

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $invoices = Invoice::dealerFinancing()
      ->with(['invoiceItems', 'invoiceFees', 'invoiceTaxes', 'paymentRequests.paymentAccounts', 'program.anchor'])
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($range && $range != '', function ($query) use ($range) {
        switch ($range) {
          case '1-30':
            $query->whereBetween('due_date', [
              now()->format('Y-m-d'),
              now()
                ->subDays(30)
                ->format('Y-m-d'),
            ]);
            break;
          case '31-60':
            $query->whereBetween('due_date', [
              now()
                ->subDays(31)
                ->format('Y-m-d'),
              now()
                ->subDays(60)
                ->format('Y-m-d'),
            ]);
            break;
          case '61-90':
            $query->whereBetween('due_date', [
              now()
                ->subDays(61)
                ->format('Y-m-d'),
              now()
                ->subDays(90)
                ->format('Y-m-d'),
            ]);
            break;
          case 'more than 90':
            $query->whereDate('due_date', '<', now()->subDays(91));
            break;
        }
      })
      ->whereIn('program_id', $programs_ids)
      ->whereDate('due_date', '<', now())
      ->where('financing_status', 'disbursed')
      ->latest()
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function rejectedInvoices()
  {
    return view('content.anchor.dealer.invoice.rejected');
  }

  public function rejectedInvoicesData(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $dealer = $request->query('dealer');
    $range = $request->query('range');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $company_programs = [];
    foreach ($company->programs as $program) {
      if ($program->programType->name == Program::DEALER_FINANCING) {
        array_push($company_programs, $program->id);
      }
    }

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $invoices = Invoice::dealerFinancing()
      ->with(['paymentRequests', 'program'])
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($dealer && $dealer != '', function ($query) use ($dealer) {
        $query->whereHas('company', function ($query) use ($dealer) {
          $query->where('name', 'LIKE', '%' . $dealer . '%');
        });
      })
      ->when($range && $range != '', function ($query) use ($range) {
        switch ($range) {
          case '1-30':
            $query->whereBetween('due_date', [
              now()->format('Y-m-d'),
              now()
                ->addDays(30)
                ->format('Y-m-d'),
            ]);
            break;
          case '31-60':
            $query->whereBetween('due_date', [
              now()
                ->addDays(31)
                ->format('Y-m-d'),
              now()
                ->addDays(60)
                ->format('Y-m-d'),
            ]);
            break;
          case '61-90':
            $query->whereBetween('due_date', [
              now()
                ->addDays(61)
                ->format('Y-m-d'),
              now()
                ->addDays(90)
                ->format('Y-m-d'),
            ]);
            break;
          case 'more than 90':
            $query->whereDate('due_date', '>', now()->addDays(91));
            break;
        }
      })
      ->whereIn('program_id', $programs_ids)
      ->where('financing_status', 'denied')
      ->latest()
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function download(Invoice $invoice)
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

  public function downloadPaymentInstruction(Invoice $invoice)
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
}
