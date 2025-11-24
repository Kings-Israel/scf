<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Models\Currency;
use App\Models\InvoiceTax;
use App\Models\BankTaxRate;
use App\Models\CompanyUser;
use App\Models\ImportError;
use App\Models\InvoiceItem;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Exports\InvoicesExport;
use App\Imports\InvoicesImport;
use App\Models\InvoiceApproval;
use App\Models\InvoiceDiscount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Models\ProgramVendorFee;
use App\Exports\UploadedInvoices;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceUploadReport;
use App\Exports\InvoicesErrorReport;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceDetailsResource;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\VendorInvoicesImport;
use App\Models\ProgramVendorDiscount;
use App\Notifications\InvoiceCreated;
use App\Http\Resources\InvoiceResource;
use App\Models\BankConvertionRate;
use App\Models\InvoiceFee;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
  public function index()
  {
    $invoices = [];
    $pending_invoices = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $invoices = Invoice::vendorFinancing()
      ->where('company_id', $current_company->company_id)
      ->count();
    $pending_invoices = Invoice::vendorFinancing()
      ->where('company_id', $current_company->company_id)
      ->whereIn('status', ['pending', 'created', 'submitted'])
      ->count();

    return view('content.vendor.invoices.index', [
      'invoices' => $invoices,
      'pending_invoices' => $pending_invoices,
    ]);
  }

  public function invoices(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $per_page = $request->query('per_page');
    $sort_by = $request->query('sort_by');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $invoices = Invoice::vendorFinancing()
      ->with([
        'program.anchor',
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'invoiceDiscounts',
        'paymentRequests.paymentAccounts',
        'purchaseOrder.purchaseOrderItems',
      ])
      ->where('company_id', $current_company->company_id)
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
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
      ->when($financing_status && count($financing_status) > 0, function ($query) use ($financing_status) {
        $query->whereIn('financing_status', $financing_status);
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

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function payments()
  {
    return view('content.vendor.invoices.payments');
  }

  public function paymentsData(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $pi_number = $request->query('pi_number');
    $paid_date = $request->query('paid_date');
    $per_page = $request->query('per_page');

    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $invoices = InvoiceResource::collection(
      Invoice::vendorFinancing()
        ->with(['program.anchor', 'invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts'])
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
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
        ->where('company_id', $current_company->company_id)
        ->orderBy('updated_at', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json($invoices);
  }

  public function pendingInvoices(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $sort_by = $request->query('sort_by');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $invoices = Invoice::vendorFinancing()
      ->with(['program.anchor', 'invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts'])
      ->where('company_id', $current_company->company_id)
      ->whereIn('status', ['created', 'submitted', 'pending'])
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
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
      })
      ->when($financing_status && count($financing_status) > 0, function ($query) use ($financing_status) {
        $query->whereIn('financing_status', $financing_status);
      })
      ->when(!$sort_by || $sort_by == '', function ($query) {
        $query->orderBy('due_date', 'DESC');
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

    if (request()->wantsJson()) {
      return response()->json(['pending_invoices' => $invoices], 200);
    }
  }

  public function create(Invoice $invoice = null)
  {
    if (
      !auth()
        ->user()
        ->hasAnyPermission(['Manage Invoices', 'Edit Invoice', 'Flip PO to Invoice'])
    ) {
      toastr()->error('', 'You don\'t have permissions to perform this action');
      return back();
    }

    $anchors = [];
    $purchase_orders = [];
    $attachment_required = false;
    $vendor_configuration = null;
    $vendor_bank_accounts = [];

    if ($invoice) {
      $invoice->load('invoiceTaxes', 'invoiceFees', 'invoiceDiscounts');
      $attachment_required = $invoice->program->mandatory_invoice_attachment;
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $invoice->program->id)
        ->where('company_id', $invoice->company_id)
        ->first();
      $vendor_bank_accounts = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
        ->where('company_id', $invoice->company_id)
        ->get();
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get Purchase Orders
    foreach ($company->purchaseOrders->where('status', 'accepted') as $purchase_order) {
      array_push($purchase_orders, $purchase_order->load('purchaseOrderItems', 'anchor'));
    }

    // Get Anchors
    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->where('is_blocked', false)
      ->get();

    foreach ($vendor_configurations as $vendor_configuration) {
      array_push($anchors, $vendor_configuration->program->anchor);
    }

    $anchors = collect($anchors)->unique();

    $taxes = [];

    $company_taxes = $company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    $pending_invoices = [];

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    $bank_default_currency = Currency::find($company->bank->adminConfiguration?->defaultCurrency);

    // Get Currencies that have a conversion rate set
    $bank_converstion_rates = BankConvertionRate::where('bank_id', $company->bank_id)
      ->where('rate', '!=', 0)
      ->pluck('from_currency');
    if ($bank_converstion_rates->count() > 0) {
      $currencies = Currency::whereIn('code', [$bank_converstion_rates, $bank_default_currency->code])->get();
    } else {
      $currencies = Currency::whereIn('code', [$bank_default_currency->code])->get();
    }

    return view(
      'content.vendor.invoices.create',
      compact(
        'anchors',
        'purchase_orders',
        'taxes',
        'invoice',
        'pending_invoices',
        'currencies',
        'attachment_required',
        'vendor_configuration',
        'vendor_bank_accounts'
      )
    );
  }

  public function accounts()
  {
    return view('content.vendor.invoices.programs');
  }

  public function loanAccounts(Request $request)
  {
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $accounts = ProgramVendorConfiguration::with('program.anchor')
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->where('company_id', $company->id)
      ->paginate($per_page);

    return response()->json(['accounts' => $accounts]);
  }

  public function downloadSample()
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('invoices-sample.xlsx'), 'vendor-invoices.xlsx');
    }

    return response()->download(public_path('invoices-sample.xlsx'), 'vendor-invoices.xlsx');
  }

  public function programs(Company $company)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $vendor_programs = ProgramCompanyRole::where([
      'role_id' => $vendor_role->id,
      'company_id' => $current_company->company_id,
    ])
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->pluck('program_id');

    $filtered_programs = ProgramCompanyRole::whereIn('program_id', $vendor_programs)
      ->where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::query()
      ->with([
        'program' => function ($query) {
          $query->select('id', 'name', 'program_type_id', 'program_code_id');
        },
      ])
      ->select('id', 'program_id', 'company_id', 'payment_account_number')
      ->whereIn('program_id', $filtered_programs)
      ->where('company_id', $current_company->company_id)
      ->get();

    return response()->json(['programs' => $programs]);
  }

  public function program(Program $program, Company $company)
  {
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $bank_accounts = ProgramVendorBankDetail::select('account_number')
      ->where('program_id', $program->id)
      ->where('company_id', $current_company->company_id)
      ->get();

    $program_vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
      ->where('company_id', $current_company->company_id)
      ->select('program_id', 'payment_terms')
      ->first();

    $program = Program::select('mandatory_invoice_attachment')->find($program->id);

    return response()->json(
      [
        'program' => $program,
        'bank_accounts' => $bank_accounts,
        'payment_terms' => $program_vendor_configuration->payment_terms
          ? $program_vendor_configuration->payment_terms
          : $program_vendor_configuration->program->default_payment_terms,
      ],
      200
    );
  }

  public function checkInvoiceNumber($number)
  {
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoice = Invoice::where('invoice_number', $number)
      ->where('company_id', $company->id)
      ->first();

    if ($invoice) {
      return response()->json(['exists' => true], 400);
    }

    return response()->json(['exists' => false], 200);
  }

  public function store(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $request->validate(
      [
        'invoice_number' => [
          'required',
          Rule::unique('invoices')->where(function ($query) use ($company) {
            return $query->where('company_id', $company->id);
          }),
        ],
        'item' => ['required', 'array', 'min:1'],
        'item.*' => ['required', 'string'],
        'quantity' => ['required', 'array', 'min:1'],
        'quantity.*' => ['required', 'string'],
        'unit' => ['required', 'array', 'min:1'],
        'unit.*' => ['required', 'string'],
        'price_per_quantity' => ['required', 'array', 'min:1'],
        'price_per_quantity.*' => ['required', 'string'],
        'program_id' => ['required'],
      ],
      [
        'program_id.required' => 'Select a Program',
        'invoice_number.required' => 'Enter invoice number',
        'invoice_number.unique' => 'The invoice number is already in use',
      ]
    );

    $program = Program::find($request->program_id);

    if ($program && $program->mandatory_invoice_attachment) {
      $request->validate([
        'invoice' => ['required'],
      ]);
    }

    // Check number of company users and permissions
    $invoice_setting = $company->invoiceSetting;
    $company_users = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Invoice Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    if ($invoice_setting->maker_checker_creating_updating && $company_users <= 0) {
      toastr()->error('', 'No company user present for checker approval');
      return back();
    }

    try {
      DB::beginTransaction();

      $invoice = Invoice::create([
        'program_id' => $request->program_id,
        'company_id' => $current_company->company_id,
        'invoice_number' => $request->invoice_number,
        'invoice_date' => Carbon::parse($request->invoice_date)->format('Y-m-d'),
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'currency' => $request->currency,
        'remarks' => $request->remarks,
        'purchase_order_id' =>
          $request->has('purchase_order') &&
          !empty($request->purchase_order) &&
          $request->purchase_order != '' &&
          $request->purchase_order != 'Select Purchase Order'
            ? $request->purchase_order
            : null,
        'created_by' => auth()->id(),
        'credit_to' => $request->credit_to,
        'status' => 'pending',
        'financing_status' => 'pending',
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
              $invoice_item_discount = str_replace(',', '', $request->discount_value[$key]);
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

      $invoice->update([
        'total_amount' => $invoice->total,
      ]);

      if ($invoice->purchaseOrder) {
        $invoice->purchaseOrder->update([
          'status' => 'invoiced',
        ]);
      }

      if ($request->has('invoice') && !empty($request->invoice)) {
        foreach ($request->input('invoice', []) as $file) {
          $invoice
            ->addMedia(storage_path('app/public/tmp/uploads/' . $file))
            ->withCustomProperties(['user_type' => Company::class, 'user_name' => auth()->user()->name])
            ->toMediaCollection('invoice');
        }
      }

      $invoice->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      if ($invoice_setting && !$invoice_setting->maker_checker_creating_updating) {
        $invoice->update([
          'status' => 'submitted',
          'stage' => 'pending_maker',
        ]);

        // Apply Invoice Fees if set by anchor
        $vendor_settings = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $invoice->program_id)
          ->select('withholding_tax', 'withholding_vat')
          ->first();
        if ($vendor_settings) {
          if ($vendor_settings->withholding_tax) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding Tax';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_tax / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }

          if ($vendor_settings->withholding_vat) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding VAT';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_vat / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }
        }

        $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
            'id' => $invoice->id,
            'company_id' => $invoice->company->id,
            'type' => 'vendor_financing',
          ]);
        }

        $invoice->program->anchor->notify(new InvoiceCreated($invoice));
      } else {
        // Check no of users in the company

        // requires maker checker approval
        $invoice->update([
          'status' => 'pending',
        ]);
      }

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($invoice)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Vendor'])
        ->log('created invoice');

      DB::commit();

      toastr()->success('', 'Invoice created successfully');

      return redirect()->route('vendor.invoice-index');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
    }
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

  public function edit(Invoice $invoice)
  {
    // Check if user has permission to perform the update
    if (!$invoice->can_edit) {
      toastr()->error('', 'You don\'t have permission to edit this invoice');
      return redirect()->route('vendor.invoice-index');
    }

    $anchors = [];
    $purchase_orders = [];
    $attachment_required = false;
    $vendor_configuration = null;

    if ($invoice) {
      $invoice->load('invoiceTaxes', 'invoiceFees', 'invoiceDiscounts');
      $attachment_required = $invoice->program->mandatory_invoice_attachment;
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $invoice->program->id)
        ->where('company_id', $invoice->company_id)
        ->first();
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get Purchase Orders
    foreach ($company->purchaseOrders->where('status', 'accepted') as $purchase_order) {
      array_push($purchase_orders, $purchase_order->load('purchaseOrderItems', 'anchor'));
    }

    // Get Anchors
    foreach ($company->programs as $program) {
      $vendors = $program->getVendors()->pluck('id');
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE &&
        $vendors->contains($company->id)
      ) {
        array_push($anchors, $program->anchor);
      }
    }

    $anchors = collect($anchors)->unique();

    $taxes = [];

    $company_taxes = $company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    $pending_invoices = [];

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    if ($company->bank->adminConfiguration) {
      if ($company->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = explode(',', str_replace("\"", '', $company->bank->adminConfiguration->selectedCurrencyIds));
      } elseif ($company->bank->adminConfiguration->defaultCurrency) {
        $currency = [$company->bank->adminConfiguration->defaultCurrency];
      }
    }

    $currencies = Currency::whereIn('id', $currency)->get();

    $vendor_bank_details = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
      ->where('company_id', $invoice->company_id)
      ->get();

    return view(
      'content.vendor.invoices.edit',
      compact(
        'anchors',
        'purchase_orders',
        'taxes',
        'invoice',
        'pending_invoices',
        'currencies',
        'attachment_required',
        'vendor_configuration',
        'vendor_bank_details'
      )
    );
  }

  public function update(Request $request, Invoice $invoice)
  {
    // Check if user has permission to perform the update
    if (!$invoice->can_edit) {
      toastr()->error('', 'You don\'t have permission to edit this invoice');
      return redirect()->route('vendor.invoice-index');
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $request->validate([
      'invoice_number' => [
        'required',
        Rule::unique('invoices', 'invoice_number')
          ->ignore($invoice->id, 'id')
          ->where(function ($query) use ($company) {
            return $query->where('company_id', $company->id);
          }),
      ],
      'program_id' => ['required'],
      'anchor' => ['required'],
      'currency' => ['required'],
      'invoice_date' => ['required', 'date'],
      'due_date' => ['required', 'date'],
    ]);

    // Check if invoice already has a financing request
    if ($invoice->paymentRequests->count() > 0) {
      toastr()->error('', 'Cannot update. Invoice already has a finance request.');
      return redirect()->route('vendor.invoice-index');
    }

    // Check number of company users and permissions
    $invoice_setting = $company->invoiceSetting;
    $company_users = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Invoice Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    if ($invoice_setting->maker_checker_creating_updating && $company_users <= 0) {
      toastr()->error('', 'No company user present for checker approval');
      return back();
    }

    $program = Program::find($request->program_id);

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();
    try {
      DB::beginTransaction();

      $invoice->update([
        'program_id' => $program->id,
        'company_id' => $current_company->company_id,
        'invoice_number' => $request->invoice_number,
        'invoice_date' => Carbon::parse($request->invoice_date)->format('Y-m-d'),
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'currency' => $request->currency,
        'remarks' => $request->remarks,
        'buyer_id' => $request->buyer,
        'purchase_order_id' =>
          $request->has('purchase_order') &&
          !empty($request->purchase_order) &&
          $request->purchase_order != 'Select Purchase Order'
            ? $request->purchase_order
            : null,
        'status' => 'pending',
      ]);

      if (
        $request->has('item') &&
        !empty($request->item) &&
        count($request->item) > 0 &&
        $request->has('quantity') &&
        !empty($request->quantity) &&
        count($request->quantity) > 0 &&
        $request->has('unit') &&
        !empty($request->unit) &&
        count($request->unit) > 0 &&
        $request->has('price_per_quantity') &&
        !empty($request->price_per_quantity) &&
        count($request->price_per_quantity) > 0
      ) {
        // Delete current invoice items, taxes and discounts
        InvoiceItem::where('invoice_id', $invoice->id)->delete();
        InvoiceTax::where('invoice_id', $invoice->id)->delete();
        InvoiceDiscount::where('invoice_id', $invoice->id)->delete();

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
              ($request->has('discount_value') &&
                !empty($request->discount_value) &&
                count($request->discount_value) > 0)
            ) {
              if (
                array_key_exists($key, $request->discount_value) &&
                (float) str_replace(',', '', $request->discount_value[$key]) > 0
              ) {
                $invoice_item_discount = str_replace(',', '', $request->discount_value[$key]);
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
      }

      if ($request->has('invoice') && !empty($request->invoice)) {
        foreach ($request->input('invoice', []) as $file) {
          $invoice
            ->addMedia(storage_path('app/public/tmp/uploads/' . $file))
            ->withCustomProperties(['user_type' => Company::class, 'user_name' => auth()->user()->name])
            ->toMediaCollection('invoice');
        }
      }

      $invoice->update([
        'total_amount' => $invoice->total,
      ]);

      $min_financing_days = Program::find($invoice->program_id);
      if (Carbon::parse($invoice->due_date)->subDays($min_financing_days) < now()) {
        $invoice = Invoice::find($invoice->id);
        $invoice->update([
          'eligible_for_financing' => false,
        ]);
      } else {
        $invoice->update([
          'eligible_for_financing' => true,
        ]);
      }

      // Remove all present approvals
      InvoiceApproval::where('invoice_id', $invoice->id)->delete();

      $invoice->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      if ($invoice_setting && !$invoice_setting->maker_checker_creating_updating) {
        $invoice->update([
          'status' => 'submitted',
          'stage' => 'pending_maker',
        ]);

        // Apply Invoice Fees if set by anchor
        $vendor_settings = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $invoice->program_id)
          ->select('withholding_tax', 'withholding_vat')
          ->first();
        if ($vendor_settings) {
          if ($vendor_settings->withholding_tax) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding Tax';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_tax / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }

          if ($vendor_settings->withholding_vat) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding VAT';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_vat / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }
        }

        $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
            'id' => $invoice->id,
            'company_id' => $invoice->company->id,
            'type' => 'vendor_financing',
          ]);
        }

        $invoice->program->anchor->notify(new InvoiceCreated($invoice));
      } else {
        // requires maker checker approval
        $invoice->update([
          'status' => 'pending',
          'stage' => 'pending',
        ]);
      }

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($invoice)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
        ->log('updated invoice');

      DB::commit();

      toastr()->success('', 'Invoice updated successfully');

      return redirect()->route('vendor.invoice-index');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
    }
  }

  public function show(Invoice $invoice)
  {
    sleep(1); // Allows for the loading animation to show and close
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

  public function calculateRemittance(Invoice $invoice, $date)
  {
    if (request()->wantsJson()) {
      return [
        'invoice_total_amount' => $invoice->invoice_total_amount,
        'actual_remittance' => $invoice->calculateActualRemittanceAmount($date)['actual_remittance'],
      ];
    }

    return [
      'invoice_total_amount' => $invoice->invoice_total_amount,
      'actual_remittance' => $invoice->calculateActualRemittanceAmount($date)['actual_remittance'],
    ];
  }

  public function import(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $import = new VendorInvoicesImport($company);
    $import->import($request->file('invoices')->store('public'));

    if ($request->wantsJson()) {
      if ($import->data > 0) {
        return response()->json(
          [
            'message' => 'Invoices uploaded successfully',
            'uploaded' => $import->data,
            'total_rows' => collect($import->total_rows)->first() - 1,
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
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $date = now()->format('Y-m-d');

    Excel::store(
      new InvoicesErrorReport($company, Program::VENDOR_FINANCING_RECEIVABLE, 'vendor'),
      'Invoices_error_report_' . $date . '.xlsx',
      'exports'
    );

    return Storage::disk('exports')->download('Invoices_error_report_' . $date . '.xlsx');
  }

  public function uploaded()
  {
    return view('content.vendor.invoices.uploaded');
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
      ->activeVendorCompany()
      ->first();

    $invoices = InvoiceUploadReport::where('product_code', Program::VENDOR_FINANCING_RECEIVABLE)
      ->where('company_id', $current_company->company_id)
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($upload_date && $upload_date != '', function ($query) use ($upload_date) {
        $query->whereDate('created_date', $upload_date);
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
    $upload_date = $request->query('uploaded_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $date = now()->format('Y-m-d');

    Excel::store(
      new UploadedInvoices($current_company, $invoice_number, $status, $upload_date, 'Vendor Financing Receivable'),
      'Uploaded_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Uploaded_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function export(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $pi_number = $request->query('pi_number');
    $paid_date = $request->query('paid_date');
    $sort_by = $request->query('sort_by');

    $date = now()->format('Y-m-d');

    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    Excel::store(
      new InvoicesExport(
        $company,
        'vendor',
        '',
        $anchor,
        '',
        $invoice_number,
        $from_date,
        $to_date,
        $from_invoice_date,
        $to_invoice_date,
        $status,
        $financing_status,
        $paid_date,
        $pi_number,
        $sort_by
      ),
      'Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function sendInvoiceForApproval(Request $request, Invoice $invoice)
  {
    $request->validate([
      'status' => ['required'],
    ]);

    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if ($request->status == 'approve') {
      $invoice->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      $approvals = InvoiceApproval::where('invoice_id', $invoice->id)->count();

      if (
        $company
          ->users()
          ->where('is_active', true)
          ->count() == 1 ||
        $approvals >= 2
      ) {
        $invoice->update([
          'status' => 'submitted',
          'stage' => 'pending_maker',
        ]);

        // Apply Invoice Fees if set by anchor
        $vendor_settings = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $invoice->program_id)
          ->select('withholding_tax', 'withholding_vat')
          ->first();
        if ($vendor_settings) {
          if ($vendor_settings->withholding_tax) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding Tax';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_tax / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }

          if ($vendor_settings->withholding_vat) {
            $invoice_fees = new InvoiceFee();
            $invoice_fees->invoice_id = $invoice->id;
            $invoice_fees->name = 'Withholding VAT';
            $invoice_fees->amount =
              (float) (($vendor_settings->withholding_vat / 100) *
                ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount));
            $invoice_fees->save();
          }
        }

        $users = User::whereIn('id', $invoice->program->anchor->users->pluck('id'))->get();

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InvoiceCreated', [
            'id' => $invoice->id,
            'company_id' => $invoice->company->id,
            'type' => 'vendor_financing',
          ]);
        }

        $invoice->program->anchor->notify(new InvoiceCreated($invoice));
      }
    } else {
      $invoice->update([
        'status' => 'denied',
        'stage' => 'internal_reject',
        'rejected_reason' => $request->rejection_reason,
      ]);

      $invoice->approvals()->delete();

      foreach ($invoice->company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'InvoiceRejection', [
          'invoice_id' => $invoice->id,
          'type' => 'vendor_financing',
        ]);
      }
    }

    if (!request()->wantsJson()) {
      toastr()->success('', 'Invoice updated successfully');

      return back();
    }

    return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice], 200);
  }

  public function delete(Invoice $invoice)
  {
    return response()->json(['message' => 'Invoice deleted successfully']);
    // Check if not approved
    if ($invoice->status == 'created' || $invoice->status == 'submitted' || $invoice->status == 'pending') {
      $invoice->delete();

      return response()->json(['message' => 'Invoice deleted successfully']);
    }

    return response()->json(['message' => 'Cannot delete approved invoice'], 403);
  }

  public function deleteAttachment(Invoice $invoice)
  {
    return response()->json(['message' => 'Invoice deleted successfully']);
    // Check if not approved
    if ($invoice->status == 'created' || $invoice->status == 'submitted' || $invoice->status == 'pending') {
      $invoice->update([
        'attachment' => null,
      ]);

      // Delete from storage
      Storage::disk('invoices')->delete($invoice->attachment);

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Invoice attachment deleted successfully']);
      }

      toastr()->success('', 'Invoice attachment deleted successfully');

      return back();
    }

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Cannot delete attachment from approved invoice'], 403);
    }

    toastr()->error('', 'Cannot delete attachment from approved invoice');

    return back();
  }

  public function download(Invoice $invoice)
  {
    $pdf = Pdf::loadView('pdf.invoice', [
      'invoice' => $invoice,
      'items' => $invoice->invoiceItems,
      'taxes' => $invoice->invoiceTaxes,
      'fees' => $invoice->invoiceFees,
      'discount' => $invoice->total_invoice_discount,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Invoice_' . $invoice->invoice_number . '.pdf');
  }

  public function downloadPaymentInstruction(Invoice $invoice)
  {
    $pdf = Pdf::loadView('pdf.payment-instruction', [
      'invoice' => $invoice,
      'items' => $invoice->invoiceItems,
      'taxes' => $invoice->invoiceTaxes,
      'fees' => $invoice->invoiceFees,
      'discount' => $invoice->total_invoice_discount,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Payment_Instruction_' . $invoice->invoice_number . '.pdf');
  }
}
