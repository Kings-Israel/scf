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

class InvoicesController extends Controller
{
  public function index()
  {
    $invoices = [];
    $pending_invoices = [];
    $programs = [];
    $dealer_financing = 0;

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    foreach ($company->programs as $program) {
      if ($program->programType->name === Program::DEALER_FINANCING) {
        $dealer_financing += 1;
      }
    }

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $program_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->pluck('program_id');

    $pending_invoices = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'created', 'submitted'])
      ->count();

    $invoices = Invoice::vendorFinancing()
      ->whereIn('program_id', $program_ids)
      ->whereIn('status', ['submitted', 'approved', 'disbursed', 'denied'])
      ->count();

    return view(
      'content.anchor.reverse-factoring.invoices',
      compact('invoices', 'pending_invoices', 'dealer_financing')
    );
  }

  public function invoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');
    $sort_by = $request->query('sort_by');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::vendorFinancing()
      ->withCount('approvals')
      ->whereHas('program', function ($query) use ($company) {
        $query->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        });
      })
      ->whereIn('invoices.status', ['submitted', 'disbursed', 'approved', 'denied', 'expired'])
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
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
      ->when($from_invoice_date && $from_invoice_date != '', function ($query) use ($from_invoice_date) {
        $query->whereDate('invoice_date', '>=', $from_invoice_date);
      })
      ->when($to_invoice_date && $to_invoice_date != '', function ($query) use ($to_invoice_date) {
        $query->whereDate('invoice_date', '<=', $to_invoice_date);
      })
      ->when($status && count($status) > 0, function ($query) use ($status) {
        $query->whereIn('stage', $status);
        // if (collect($status)->contains('past_due')) {
        // } else {
        //   $query->whereDate('due_date', '>=', now()->format('Y-m-d'))->whereIn('stage', $status);
        // }
      })
      ->where('stage', '!=', 'internal_reject')
      ->when($financing_status && count($financing_status) > 0, function ($query) use ($financing_status) {
        $query->whereIn('financing_status', $financing_status);
      })
      ->when(!$sort_by || $sort_by === '', function ($query) {
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
        } elseif ($sort_by == 'vendor_asc') {
          $query->join('companies', 'companies.id', '=', 'invoices.company_id')->orderBy('companies.name', 'ASC');
        } elseif ($sort_by == 'vendor_desc') {
          $query->join('companies', 'companies.id', '=', 'invoices.company_id')->orderBy('companies.name', 'DESC');
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
      return response()->json(['invoices' => $invoices, 'taxes' => $taxes, 'users' => $users], 200);
    }
  }

  public function vendors()
  {
    return view('content.anchor.reverse-factoring.vendors');
  }

  public function vendorsData(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor_search = $request->query('vendor');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendors = [];

    $vendors = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('company')
        ->whereHas('program', function ($query) use ($company) {
          $query
            ->whereHas('anchor', function ($query) use ($company) {
              $query->where('companies.id', $company->id);
            })
            ->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
        })
        ->when($vendor_search && $vendor_search != '', function ($query) use ($vendor_search) {
          $query->whereHas('company', function ($query) use ($vendor_search) {
            $query->where('name', 'LIKE', '%' . $vendor_search . '%');
          });
        })
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json($vendors);
  }

  public function pendingInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');
    $status = $request->query('status');

    $companies = auth()
      ->user()
      ->companies()
      ->anchor()
      ->get();

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    // Get programs
    $company_programs = [];
    foreach ($companies as $key => $company) {
      if ($company->id == $current_company->company_id) {
        foreach ($company->programs as $program) {
          array_push($company_programs, $program->id);
        }
      }
    }

    $invoices = Invoice::vendorFinancing()
      ->with(
        'program.anchor',
        'company',
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'invoiceDiscounts',
        'purchaseOrder.purchaseOrderItems'
      )
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->whereIn('program_id', $company_programs)
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
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
      ->when($from_invoice_date && $from_invoice_date != '', function ($query) use ($from_invoice_date) {
        $query->whereDate('invoice_date', '>=', $from_invoice_date);
      })
      ->when($to_invoice_date && $to_invoice_date != '', function ($query) use ($to_invoice_date) {
        $query->whereDate('invoice_date', '<=', $to_invoice_date);
      })
      ->where('status', 'submitted')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->when($status && count($status) > 0, function ($query) use ($status) {
        $query->whereIn('stage', $status);
      })
      ->where('stage', '!=', 'internal_reject')
      ->orderBy('updated_at', 'DESC')
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
      return response()->json(['invoices' => $invoices, 'taxes' => $taxes, 'users' => $users], 200);
    }
  }

  public function paymentInstructions()
  {
    return view('content.anchor.reverse-factoring.payment-instructions');
  }

  public function paymentInstructionsData(Request $request)
  {
    $vendor = $request->query('vendor');
    $invoice_number = $request->query('invoice');
    $per_page = $request->query('per_page');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $finance_requests = InvoiceResource::collection(
      Invoice::vendorFinancing()
        ->where('pi_number', '!=', null)
        ->whereHas('program', function ($query) use ($current_company) {
          $query->whereHas('anchor', function ($query) use ($current_company) {
            $query->where('companies.id', $current_company->company_id);
          });
        })
        ->when($vendor && $vendor != '', function ($query) use ($vendor) {
          $query->whereHas('company', function ($query) use ($vendor) {
            $query->where('name', 'LIKE', '%' . $vendor . '%');
          });
        })
        ->when($status && $status != '', function ($query) use ($status) {
          $query->where('financing_status', $status);
        })
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->orderBy('due_date', 'DESC')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['finance_requests' => $finance_requests], 200);
    }
  }

  public function uploaded()
  {
    return view('content.anchor.reverse-factoring.uploaded-invoices');
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
      ->activeAnchorCompany()
      ->first();

    $invoices = InvoiceUploadReport::where('product_type', Program::VENDOR_FINANCING)
      ->where('product_code', Program::VENDOR_FINANCING_RECEIVABLE)
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
    $upload_date = $request->query('uploaded_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $date = now()->format('Y-m-d');

    Excel::store(
      new UploadedInvoices(
        $current_company,
        $invoice_number,
        $status,
        $upload_date,
        'Anchor Vendor Financing Receivable'
      ),
      'Uploaded_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Uploaded_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function edit(Invoice $invoice)
  {
    if (!$invoice->can_edit) {
      toastr()->error('', 'Invoice cannot be edited.');
      return redirect()->route('anchor.invoices');
    }

    $invoice->load('invoiceTaxes', 'invoiceFees', 'invoiceDiscounts');

    $max_days = $invoice->program->max_days_due_date_extension;

    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $anchors_programs = ProgramCompanyRole::where([
      'role_id' => $anchor_role->id,
      'company_id' => $current_company->company_id,
    ])
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->pluck('program_id');

    $filtered_programs = ProgramCompanyRole::whereIn('program_id', $anchors_programs)
      ->where('role_id', $vendor_role->id)
      ->where('company_id', $invoice->company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::whereIn('program_id', $filtered_programs)
      ->where('company_id', $invoice->company->id)
      ->first();

    $payment_account_number = $programs->payment_account_number;

    $taxes = [];

    $company_taxes = $invoice->company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    return view('content.anchor.reverse-factoring.edit-invoice', [
      'invoice' => $invoice,
      'max_days' => $max_days,
      'payment_account_number' => $payment_account_number,
      'taxes' => $taxes,
    ]);
  }

  public function update(Request $request, Invoice $invoice)
  {
    if (!$invoice->can_edit) {
      toastr()->error('', 'Invoice cannot be edited');
      return redirect()->route('anchor.invoices');
    }

    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $request->validate([
      'due_date' => ['required', 'date'],
    ]);

    // Check if invoice already has a financing request
    if ($invoice->paymentRequests->count() > 0) {
      toastr()->error('', 'Cannot update. Invoice already has a finance request.');
      return redirect()->route('anchor.invoices');
    }

    try {
      DB::beginTransaction();

      $old_date = $invoice->due_date;

      $invoice->update([
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'old_due_date' => $old_date,
      ]);

      foreach ($company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'DueDateChanged', [
          'invoice_id' => $invoice->id,
          'old_date' => $old_date,
        ]);
      }

      foreach ($invoice->company->users as $company_user) {
        SendMail::dispatchAfterResponse($company_user->email, 'DueDateChanged', [
          'invoice_id' => $invoice->id,
          'old_date' => $old_date,
        ]);
      }

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($invoice)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
        ->log('updated due date');

      DB::commit();

      toastr()->success('', 'Invoice updated successfully');

      return redirect()->route('anchor.invoices');
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
            'purchaseOrder.purchaseOrderItems',
            'approvals'
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
        'purchaseOrder.purchaseOrderItems',
        'approvals'
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
    if ($invoice->program->programType->name === Program::VENDOR_FINANCING) {
      $current_company = auth()
        ->user()
        ->activeAnchorCompany()
        ->first()?->company_id;
    } else {
      $current_company = auth()
        ->user()
        ->activeFactoringCompany()
        ->first()->company_id;
    }

    $company = Company::find($current_company);

    $invoice->load('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts', 'company', 'program.programType');

    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->first();

    // Add user approval to invoice approvals
    $invoice->approvals()->create([
      'user_id' => auth()->id(),
    ]);

    // Check if approvals require to go through maker/checker
    $invoice_setting = $company->invoiceSetting;

    if ($invoice->program->programType->name === Program::VENDOR_FINANCING) {
      if ($invoice_setting) {
        if (!$invoice_setting->maker_checker_creating_updating) {
          $invoice->update([
            'status' => 'approved',
            'stage' => 'approved',
            'pi_number' => 'PI_' . $invoice->id,
            'eligible_for_financing' =>
              Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now() ? true : false,
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

          // Auto request finance
          if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
            if ($invoice->canRequestFinancing()) {
              $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)
                ->where('program_id', $invoice->program_id)
                ->first();

              $invoice->requestFinance($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
            }
          }

          if (!request()->wantsJson()) {
            toastr()->success('', 'Invoice successfully approved');

            return back();
          }

          return response()->json('Invoice updated successfully', 200);
        } else {
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
            if (count($approvals) < $required_approvals) {
              $invoice->update([
                'stage' => 'pending_checker',
              ]);

              // Company has approvers
              $users = User::whereIn('id', $company->users->pluck('id'))
                ->where('id', '!=', auth()->id())
                ->whereHas('roles', function ($query) {
                  $query->whereHas('permissions', function ($query) {
                    $query->where('name', 'Approve Invoices - Level 2');
                  });
                })
                ->get();

              if ($users->count() > 1) {
                foreach ($users as $user) {
                  SendMail::dispatchAfterResponse($user->email, 'InvoiceUpdated', [
                    'id' => $invoice->id,
                    'type' => 'vendor_financing',
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
                  Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                    ? true
                    : false,
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

              // Auto request finance
              if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
                if ($invoice->canRequestFinancing()) {
                  $bank_details = ProgramVendorBankDetail::where('program_id', $invoice->program_id)
                    ->where('company_id', $invoice->company_id)
                    ->first();

                  $invoice->requestFinance($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
                }
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
      } else {
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
                    $query->where('name', 'Invoice Checker');
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
                Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                  ? true
                  : false,
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
            ->withProperties([
              'ip' => request()->ip(),
              'device_info' => request()->userAgent(),
              'user_type' => 'Anchor',
            ])
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
    } else {
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
                $query->where('name', 'Invoice Checker');
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
    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      $current_company = auth()
        ->user()
        ->activeAnchorCompany()
        ->first()->company_id;
    } else {
      $current_company = auth()
        ->user()
        ->activeFactoringCompany()
        ->first()->company_id;
    }

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

    // Check if approvals require to go through maker/checker
    $invoice_setting = $company->invoiceSetting;

    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($invoice_setting) {
        if (!$invoice_setting->maker_checker_creating_updating) {
          $invoice->update([
            'status' => 'approved',
            'stage' => 'approved',
            'pi_number' => 'PI_' . $invoice->id,
            'eligible_for_financing' =>
              Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now() ? true : false,
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

          // Auto request finance
          if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
            if ($invoice->canRequestFinancing()) {
              $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)->first();

              $invoice->requestFinance($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
            }
          }

          if (!request()->wantsJson()) {
            toastr()->success('', 'Invoice successfully approved');

            return back();
          }

          return response()->json('Invoice updated successfully', 200);
        } else {
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
              if ($invoice->program->programType->name === Program::VENDOR_FINANCING) {
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
                      $query->where('name', 'Invoice Checker');
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
                  Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                    ? true
                    : false,
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
              ->withProperties([
                'ip' => request()->ip(),
                'device_info' => request()->userAgent(),
                'user_type' => 'Anchor',
              ])
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
      } else {
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
                    $query->where('name', 'Invoice Checker');
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
                Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                  ? true
                  : false,
              // 'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
              'calculated_total_amount' => $invoice->invoice_total_amount,
            ]);

            // $invoice->company->notify(new InvoiceUpdated($invoice));
            foreach ($invoice->company->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'InvoiceApproval', [
                'id' => $invoice->id,
                'type' => 'vendor_financing',
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
            ->withProperties([
              'ip' => request()->ip(),
              'device_info' => request()->userAgent(),
              'user_type' => 'Anchor',
            ])
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
    } else {
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
                  $query->where('name', 'Invoice Checker');
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
  }

  public function bulkApprove(Request $request)
  {
    ini_set('max_execution_time', 800);
    // Check if company can make the request
    $type = $request->query('type');
    if ($type && $type == 'Factoring') {
      $current_company = auth()
        ->user()
        ->activeFactoringCompany()
        ->first()->company_id;
    } else {
      $current_company = auth()
        ->user()
        ->activeAnchorCompany()
        ->first()->company_id;
    }

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

            if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
              if ($invoice_setting) {
                if (!$invoice_setting->maker_checker_creating_updating) {
                  $invoice->update([
                    'status' => 'approved',
                    'stage' => 'approved',
                    'pi_number' => 'PI_' . $invoice->id,
                    'eligible_for_financing' =>
                      Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                        ? true
                        : false,
                    'calculated_total_amount' => $invoice->invoice_total_amount,
                  ]);

                  array_push($updated_invoices, $invoice->id);

                  // Auto request finance
                  if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
                    array_push($bulk_request_finance_invoices, $invoice->id);
                  }
                  $further_approval_required = false;
                } else {
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
                            $query->where('name', 'Approve Invoices - Level 2');
                          });
                        })
                        ->get();

                      if ($users->count() > 1) {
                        foreach ($users as $user) {
                          SendMail::dispatch($user->email, 'InvoiceUpdated', [
                            'id' => $invoice->id,
                            'type' => 'vendor_financing',
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
                        'eligible_for_financing' =>
                          Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                            ? true
                            : false,
                        'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
                        'calculated_total_amount' => $invoice->invoice_total_amount,
                      ]);

                      array_push($updated_invoices, $invoice->id);

                      // Auto request finance
                      if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
                        array_push($bulk_request_finance_invoices, $invoice->id);
                      }

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
              } else {
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
                          $query->where('name', 'Approve Invoices - Level 2');
                        });
                      })
                      ->get();

                    if ($users->count() > 1) {
                      foreach ($users as $user) {
                        SendMail::dispatch($user->email, 'InvoiceUpdated', [
                          'id' => $invoice->id,
                          'type' => 'vendor_financing',
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
                      'eligible_for_financing' =>
                        Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                          ? true
                          : false,
                      'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
                      'calculated_total_amount' => $invoice->invoice_total_amount,
                    ]);

                    // $invoice->company->notify(new InvoiceUpdated($invoice));
                    array_push($updated_invoices, $invoice->id);

                    // Auto request finance
                    if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
                      array_push($bulk_request_finance_invoices, $invoice->id);
                    }

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
            } else {
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
        }
      });

      DB::commit();
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Failed to approve. Contact System admin for assistance'], 500);
      }
    }

    if (count($bulk_request_finance_invoices) > 0) {
      AutoRequestFinancing::dispatch($bulk_request_finance_invoices)->afterResponse();
    }

    // Notify users of updated invoices
    if (count($updated_invoices) > 0) {
      InvoiceUpdateNotification::dispatch($company, $updated_invoices)->afterResponse();
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
    if ($type == Program::VENDOR_FINANCING) {
      $current_company = auth()
        ->user()
        ->activeAnchorCompany()
        ->first()->company_id;
    } else {
      $current_company = auth()
        ->user()
        ->activeFactoringCompany()
        ->first()->company_id;
    }

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

  public function import(Request $request)
  {
    $request->validate([
      'invoices' => ['required', 'mimes:xlsx'],
    ]);

    $programs = [];
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        $program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
      ) {
        array_push($programs, $program->id);
      }
    }

    $import = new InvoicesImport($programs, $company, Program::VENDOR_FINANCING_RECEIVABLE);

    Excel::import($import, $request->file('invoices')->store('public'));

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
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $date = now()->format('Y-m-d');

    Excel::store(
      new InvoicesErrorReport($company, Program::VENDOR_FINANCING_RECEIVABLE, 'anchor'),
      'Invoices_error_report_' . $date . '.xlsx',
      'exports'
    );

    return Storage::disk('exports')->download('Invoices_error_report_' . $date . '.xlsx');
  }

  public function export(Request $request)
  {
    $vendor = $request->query('vendor');
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
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    Excel::store(
      new InvoicesExport(
        $company,
        'anchor',
        '',
        '',
        $vendor,
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

  public function downloadSample()
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('sample-invoices.xlsx'), 'anchor-vfr-invoices.xlsx');
    }
    return response()->download(public_path('sample-invoices.xlsx'), 'anchor-vfr-invoices.xlsx');
  }

  // Factoring
  public function factoringIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $dealer_financing = 0;
    $can_create_invoice = false;
    foreach ($company->programs as $program) {
      if (
        ($program->programCode?->name == Program::FACTORING_WITH_RECOURSE ||
          $program->programCode?->name == Program::FACTORING_WITHOUT_RECOURSE) &&
        auth()
          ->user()
          ->hasAllPermissions(['Manager Seller Invoices'])
      ) {
        $can_create_invoice = true;
      }
    }

    $invoices = Invoice::factoring()
      ->where('company_id', $company->id)
      ->count();

    $pending_invoices = Invoice::factoring()
      ->where('company_id', $company->id)
      ->whereIn('status', ['pending', 'created', 'submitted'])
      ->count();

    return view(
      'content.anchor.factoring.invoice.invoices',
      compact('invoices', 'pending_invoices', 'dealer_financing', 'can_create_invoice')
    );
  }

  public function dealers()
  {
    return view('content.anchor.factoring.dealers');
  }

  public function dealersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendors_search = $request->query('vendors');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = Program::whereHas('anchor', function ($query) use ($company) {
      $query->where('companies.id', $company->id);
    })
      ->where(function ($query) {
        $query->whereHas(
          'programCode',
          fn($query) => $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE])
        );
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

  public function downloadFactoringSample()
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('anchor-factoring-invoices.xlsx'), 'anchor-factoring-df-invoices.xlsx');
    }

    return response()->download(public_path('anchor-factoring-invoices.xlsx'), 'anchor-factoring-df-invoices.xlsx');
  }

  public function factoringImport(Request $request)
  {
    $request->validate([
      'invoices' => ['required', 'mimes:xlsx'],
    ]);

    $programs = [];
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $import = new DealerInvoicesImport($company);
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

  public function factoringDownloadErrorReport()
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $date = now()->format('Y-m-d');

    Excel::store(new FactoringInvoicesErrorReport($company), 'Invoices_error_report_' . $date . '.xlsx', 'exports');

    return Storage::disk('exports')->download('Invoices_error_report_' . $date . '.xlsx');
  }

  public function factoringInvoicesData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

    $invoices = Invoice::factoring()
      ->where('company_id', $company->id)
      ->when($buyer && $buyer != '', function ($query) use ($buyer) {
        $query->where(function ($query) use ($buyer) {
          $query
            ->whereHas('buyer', function ($query) use ($buyer) {
              $query->where('name', 'LIKE', '%' . $buyer . '%');
            })
            ->orWhereHas('company', function ($query) use ($buyer) {
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

  public function factoringExpiredInvoicesData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $buyer = $request->query('buyer');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $finance_status = $request->query('finance_status');
    $per_page = $request->query('per_page');

    // // Get programs
    // $company_programs = [];
    // foreach ($company->programs as $program) {
    //   if (
    //     $program->programType->name == Program::VENDOR_FINANCING &&
    //     ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
    //       $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
    //   ) {
    //     array_push($company_programs, $program->id);
    //   }
    // }

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $current_company->company_id)
      ->whereHas('program', function ($query) {
        $query->where(function ($query) {
          $query
            ->whereHas('programCode', function ($query) {
              $query
                ->where('name', Program::FACTORING_WITH_RECOURSE)
                ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
            })
            ->orWhereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
        });
      })
      ->pluck('program_id');

    $invoices = InvoiceResource::collection(
      Invoice::factoringDealer()
        ->with(['program', 'paymentRequests.paymentAccounts'])
        ->whereIn('program_id', $programs)
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->when($buyer && $buyer != '', function ($query) use ($buyer) {
          $query->whereHas('buyer', function ($query) use ($buyer) {
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
        ->orderBy('created_at', 'DESC')
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
      ->activeFactoringCompany()
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
      ->orderBy('created_at', 'DESC')
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

  public function factoringExpired()
  {
    return view('content.anchor.factoring.invoice.expired');
  }

  public function factoringUploaded()
  {
    return view('content.anchor.factoring.invoice.uploaded');
  }

  public function factoringUploadedInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('uploaded_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $invoices = InvoiceUploadReport::where(function ($query) {
      $query->where('product_code', 'Factoring')->orWhere('product_code', null);
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

  public function factoringExportUploadedInvoices(Request $request)
  {
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('upload_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

  public function factoringCreate(Invoice $invoice = null)
  {
    $purchase_orders = [];
    $attachment_required = false;
    $vendor_configuration = null;
    $vendor_bank_accounts = [];

    if ($invoice) {
      $invoice = new InvoiceDetailsResource(
        $invoice->load('invoiceItems', 'invoiceTaxes', 'invoiceFees', 'invoiceDiscounts')
      );

      $attachment_required = $invoice->program->mandatory_invoice_attachment;
      $vendor_configuration = ProgramVendorConfiguration::select('payment_account_number')
        ->where('program_id', $invoice->program->id)
        ->where('buyer_id', $invoice->buyer->id)
        ->first();

      $vendor_bank_accounts = ProgramBankDetails::where('program_id', $invoice->program_id)->get();
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get companies in Factoring programs
    $buyers = [];

    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]);
        });
      })
      ->where('is_blocked', false)
      ->get();

    foreach ($vendor_configurations as $vendor_configuration) {
      array_push($buyers, $vendor_configuration->buyer);
    }

    $buyers = collect($buyers)->unique();

    // Get Purchase Orders
    foreach ($company->purchaseOrders as $purchase_order) {
      array_push($purchase_orders, $purchase_order);
    }

    $taxes = [];

    $company_taxes = $company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    $bank_default_currency = Currency::find($company->bank->adminConfiguration?->defaultCurrency);

    // Get Currencies that have a conversion rate set
    $currencies = Currency::whereIn('code', [$bank_default_currency?->code])->get();

    if ($currencies->count() <= 0) {
      $currencies = Currency::where('name', 'Kenyan Shilling')->get();
    }

    return view('content.anchor.factoring.invoice.create', [
      'buyers' => $buyers,
      'purchase_orders' => $purchase_orders,
      'invoice' => $invoice,
      'taxes' => $taxes,
      'currencies' => $currencies,
      'attachment_required' => $attachment_required,
      'vendor_configuration' => $vendor_configuration,
      'vendor_bank_accounts' => $vendor_bank_accounts,
    ]);
  }

  public function factoringDrawdownCreate(Invoice $invoice = null)
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
      ->activeFactoringCompany()
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

    // Get Purchase Orders
    // foreach ($company->purchaseOrders as $purchase_order) {
    //   array_push($purchase_orders, $purchase_order);
    // }

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
      $currencies = Currency::whereIn('code', [$bank_default_currency->code])->get();
    }

    return view('content.anchor.factoring.invoice.drawdown', [
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

  public function store(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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
        'buyer' => ['required'],
        'currency' => ['required'],
        'invoice_date' => ['required', 'date'],
        'due_date' => ['required', 'date'],
        'program_id' => ['required'],
      ],
      [
        'invoice_number.unique' => 'This Invoice Number is already in use',
      ]
    );

    $program = Program::find($request->program_id);

    if ($program && $program->mandatory_invoice_attachment) {
      $request->validate([
        'invoice' => ['required'],
      ]);
    }

    $program_role = ProgramRole::where('name', 'anchor')->first();

    $program = ProgramCompanyRole::where('role_id', $program_role->id)
      ->where('program_id', $request->program_id)
      ->where('company_id', $current_company->company_id)
      ->first();

    try {
      DB::beginTransaction();

      $invoice = Invoice::create([
        'program_id' => $program->program_id,
        'company_id' => $current_company->company_id,
        'invoice_number' => $request->invoice_number,
        'invoice_date' => Carbon::parse($request->invoice_date)->format('Y-m-d'),
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'currency' => $request->currency,
        'remarks' => $request->remarks,
        'status' => 'submitted',
        'financing_status' => 'pending',
        'buyer_id' => $request->buyer,
        'purchase_order_id' =>
          $request->has('purchase_order') &&
          !empty($request->purchase_order) &&
          $request->purchase_order != 'Select Purchase Order' &&
          $request->purchase_order != ''
            ? $request->purchase_order
            : null,
        'credit_to' => $request->credit_to,
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

      if ($invoice->purchaseOrder) {
        $invoice->purchaseOrder->update([
          'status' => 'invoiced',
        ]);
      }

      $invoice->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      $invoice_setting = $company->invoiceSetting;
      if ($invoice_setting && !$invoice_setting->maker_checker_creating_updating) {
        // Check if invoices are auto approved
        $vendor_settings = ProgramVendorConfiguration::where('buyer_id', $invoice->buyer_id)
          ->where('program_id', $invoice->program_id)
          ->select('withholding_tax', 'withholding_vat', 'auto_approve_invoices')
          ->first();

        if ($vendor_settings && $vendor_settings->auto_approve_invoices) {
          $invoice->update([
            'status' => 'approved',
            'stage' => 'approved',
            'eligible_for_financing' =>
              Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now() ? true : false,
          ]);
        } else {
          $invoice->update([
            'status' => 'submitted',
            'stage' => 'pending_maker',
          ]);
        }

        // Apply WHT and Withholding VAT from program vendor configuration
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

        $company_users = CompanyUser::with('user')
          ->where('company_id', $request->buyer)
          ->get();
        foreach ($company_users as $company_user) {
          SendMail::dispatchAfterResponse($company_user->user->email, 'InvoiceCreated', [
            'id' => $invoice->id,
            'type' => 'factoring',
          ]);
        }

        $invoice->buyer->notify(new InvoiceCreated($invoice));
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
        ->log('created invoice');

      DB::commit();

      toastr()->success('', 'Invoice created successfully');

      return redirect()->route('anchor.factoring-invoices-index');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
    }
  }

  public function drawdownStore(Request $request)
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

        return redirect()->route('anchor.factoring-invoices-index');
        DB::beginTransaction();
      }
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
    }
  }

  public function factoringEdit(Invoice $invoice)
  {
    $purchase_orders = [];
    $attachment_required = false;
    $vendor_configuration = null;

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    if ($invoice) {
      $invoice->load('invoiceTaxes', 'invoiceFees', 'invoiceDiscounts');
      $attachment_required = $invoice->program->mandatory_invoice_attachment;
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $invoice->program->id)
        ->where('buyer_id', $invoice->buyer->id)
        ->first();
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get companies in Factoring programs
    $buyers = [];
    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    foreach ($company->programs as $program) {
      $role = ProgramCompanyRole::where([
        'program_id' => $program->id,
        'company_id' => $company->id,
        'role_id' => $anchor_role->id,
      ])->first();
      if (
        $role &&
        ($program->programType->name == Program::VENDOR_FINANCING &&
          ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE))
      ) {
        foreach ($program->getBuyers() as $buyer) {
          array_push($buyers, $buyer);
        }
      }
    }

    // Get Purchase Orders
    foreach ($company->purchaseOrders as $purchase_order) {
      array_push($purchase_orders, $purchase_order);
    }

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $company->bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $company_taxes = $company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    if ($company->bank->adminConfiguration) {
      if ($company->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = explode(',', str_replace("\"", '', $company->bank->adminConfiguration->selectedCurrencyIds));
      } elseif ($company->bank->adminConfiguration->defaultCurrency) {
        $currency = [$company->bank->adminConfiguration->defaultCurrency];
      }
    }

    $currencies = Currency::whereIn('id', $currency)->get();

    $vendor_bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->get();

    return view('content.anchor.factoring.invoice.edit', [
      'buyers' => $buyers,
      'purchase_orders' => $purchase_orders,
      'invoice' => $invoice,
      'taxes' => $taxes,
      'currencies' => $currencies,
      'attachment_required' => $attachment_required,
      'vendor_configuration' => $vendor_configuration,
      'vendor_bank_details' => $vendor_bank_details,
    ]);
  }

  public function factoringUpdate(Request $request, Invoice $invoice)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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
      'buyer' => ['required'],
      'currency' => ['required'],
      'invoice_date' => ['required', 'date'],
      'due_date' => ['required', 'date'],
    ]);

    // Check if invoice already has a financing request
    if ($invoice->paymentRequests->count() > 0) {
      toastr()->error('', 'Cannot update. Invoice already has a finance request.');
      return redirect()->route('anchor.factoring-invoices-index');
    }

    $program = Program::find($request->program_id);

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
        'stage' => 'pending_maker',
      ]);

      // Apply WHT and Withholding VAT from program vendor configuration
      $vendor_settings = ProgramVendorConfiguration::where('buyer_id', $invoice->buyer_id)
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

      $company_users = CompanyUser::with('user')
        ->where('company_id', $request->buyer)
        ->get();
      foreach ($company_users as $company_user) {
        SendMail::dispatchAfterResponse($company_user->user->email, 'InvoiceUpdated', [
          'id' => $invoice->id,
          'type' => 'factoring',
        ]);
      }

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($invoice)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
        ->log('updated invoice');

      DB::commit();

      toastr()->success('', 'Invoice updated successfully');

      return redirect()->route('anchor.factoring-invoices-index');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
    }
  }

  public function programs(Company $company)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $buyer_role = ProgramRole::where('name', 'buyer')->first();

    $anchors_programs = ProgramCompanyRole::where([
      'role_id' => $anchor_role->id,
      'company_id' => $current_company->company_id,
    ])
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', 'Factoring With Recourse')->orWhere('name', 'Factoring Without Recourse');
        });
      })
      ->pluck('program_id');

    $filtered_programs = ProgramCompanyRole::whereIn('program_id', $anchors_programs)
      ->where('role_id', $buyer_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::query()
      ->with([
        'program' => function ($query) {
          $query->select('id', 'name', 'program_type_id', 'program_code_id');
        },
      ])
      ->select('id', 'program_id', 'company_id', 'payment_account_number', 'buyer_id')
      ->whereIn('program_id', $filtered_programs)
      ->where('buyer_id', $company->id)
      ->get();

    return response()->json(['programs' => $programs]);
  }

  public function drawdownPrograms(Company $company)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $anchors_programs = ProgramCompanyRole::where([
      'role_id' => $anchor_role->id,
      'company_id' => $current_company->company_id,
    ])
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', 'Dealer Financing');
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

  public function sendInvoiceForApproval(Request $request, Invoice $invoice)
  {
    $request->validate([
      'status' => ['required'],
    ]);

    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

        // Apply WHT and Withholding VAT from program vendor configuration
        $vendor_settings = ProgramVendorConfiguration::where('buyer_id', $invoice->buyer_id)
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

        $company_users = CompanyUser::with('user')
          ->where('company_id', $invoice->buyer_id)
          ->get();
        foreach ($company_users as $company_user) {
          SendMail::dispatchAfterResponse($company_user->user->email, 'InvoiceCreated', [
            'id' => $invoice->id,
            'type' => 'factoring',
          ]);
        }
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
          'type' => 'factoring',
        ]);
      }
    }

    if (!request()->wantsJson()) {
      toastr()->success('', 'Invoice updated successfully');

      return back();
    }

    return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice], 200);
  }

  public function factoringApproveFees(Invoice $invoice)
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
    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      $current_company = auth()
        ->user()
        ->activeAnchorCompany()
        ->first()?->company_id;
    } else {
      $current_company = auth()
        ->user()
        ->activeFactoringCompany()
        ->first()->company_id;
    }

    $company = Company::find($current_company);

    $invoice->load('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts', 'company', 'program.programType');

    $vendor_configurations = ProgramVendorConfiguration::select('auto_request_finance')
      ->where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->first();

    // Add user approval to invoice approvals
    $invoice->approvals()->create([
      'user_id' => auth()->id(),
    ]);

    // Check if approvals require to go through maker/checker
    $invoice_setting = $company->invoiceSetting;

    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($invoice_setting) {
        if (!$invoice_setting->maker_checker_creating_updating) {
          $invoice->update([
            'status' => 'approved',
            'stage' => 'approved',
            'pi_number' => 'PI_' . $invoice->id,
            'eligible_for_financing' =>
              Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now() ? true : false,
            // 'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
            'calculated_total_amount' => $invoice->invoice_total_amount,
          ]);

          $invoice->company->notify(new InvoiceUpdated($invoice));

          // Auto request finance
          if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
            if ($invoice->canRequestFinancing()) {
              $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)->first();

              $invoice->requestFinance($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
            }
          }

          if (!request()->wantsJson()) {
            toastr()->success('', 'Invoice successfully approved');

            return back();
          }

          return response()->json('Invoice updated successfully', 200);
        } else {
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
            if (count($approvals) < $required_approvals) {
              $invoice->update([
                'stage' => 'pending_checker',
              ]);

              // Company has approvers
              $users = User::whereIn('id', $company->users->pluck('id'))
                ->where('id', '!=', auth()->id())
                ->whereHas('roles', function ($query) {
                  $query->whereHas('permissions', function ($query) {
                    $query->where('name', 'Approve Invoices - Level 2');
                  });
                })
                ->get();

              if ($users->count() > 1) {
                foreach ($users as $user) {
                  SendMail::dispatchAfterResponse($user->email, 'InvoiceUpdated', [
                    'id' => $invoice->id,
                    'type' => 'vendor_financing',
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
                  Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                    ? true
                    : false,
                'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
                'calculated_total_amount' => $invoice->invoice_total_amount,
              ]);

              $invoice->company->notify(new InvoiceUpdated($invoice));

              // Auto request finance
              if ($vendor_configurations && $vendor_configurations->auto_request_finance) {
                if ($invoice->canRequestFinancing()) {
                  $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)->first();

                  $invoice->requestFinance($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
                }
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
      } else {
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
                    $query->where('name', 'Invoice Checker');
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
                Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now()
                  ? true
                  : false,
              // 'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
              'calculated_total_amount' => $invoice->invoice_total_amount,
            ]);

            $invoice->company->notify(new InvoiceUpdated($invoice));

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
            ->withProperties([
              'ip' => request()->ip(),
              'device_info' => request()->userAgent(),
              'user_type' => 'Anchor',
            ])
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
    } else {
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
                $query->where('name', 'Invoice Checker');
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

          $invoice->company->notify(new InvoiceUpdated($invoice));
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
  }

  public function factoringPayments()
  {
    return view('content.anchor.factoring.invoice.payments');
  }

  public function factoringPaymentsData(Request $request)
  {
    $buyer = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $pi_number = $request->query('pi_number');
    $paid_date = $request->query('paid_date');
    $per_page = $request->query('per_page');

    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $programs = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('company_id', $current_company->company_id)
      ->whereHas('program', function ($query) {
        $query->where(function ($query) {
          $query
            ->whereHas('programCode', function ($query) {
              $query
                ->where('name', Program::FACTORING_WITH_RECOURSE)
                ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
            })
            ->orWhereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
        });
      })
      ->pluck('program_id');

    $invoices = InvoiceResource::collection(
      Invoice::factoringDealer()
        ->with(['program', 'buyer', 'invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts'])
        ->when($buyer && $buyer != '', function ($query) use ($buyer) {
          $query->whereHas('buyer', function ($query) use ($buyer) {
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

  public function factoringPlanner()
  {
    $anchors = [];
    $programs = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $program_ids = ProgramCompanyRole::whereHas('program', function ($query) {
      $query->whereHas('programCode', function ($query) {
        $query->where('name', 'Factoring With Recourse')->orWhere('name', 'Factoring Without Recourse');
      });
    })
      ->where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::with('program.anchor')
      ->whereIn('program_id', $program_ids)
      ->where('company_id', $company->id)
      ->get();

    // Get Anchors
    foreach ($programs as $program) {
      foreach ($program->program->getBuyers() as $buyer) {
        array_push($anchors, $buyer);
      }
    }

    $anchors = collect($anchors)->unique();

    $off_days = $company->bank->adminConfiguration->offdays
      ? explode('-', str_replace(' ', '', $company->bank->adminConfiguration->offdays))
      : null;
    if ($off_days) {
      foreach ($off_days as $key => $off_day) {
        switch ($off_day) {
          case 'Monday':
            $off_days[$key] = 1;
            break;
          case 'Tuesday':
            $off_days[$key] = 2;
            break;
          case 'Wednesday':
            $off_days[$key] = 3;
            break;
          case 'Thursday':
            $off_days[$key] = 4;
            break;
          case 'Friday':
            $off_days[$key] = 5;
            break;
          case 'Saturday':
            $off_days[$key] = 6;
            break;
          case 'Sunday':
            $off_days[$key] = 0;
            break;
        }
      }
    }

    $holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->get();

    foreach ($holidays as $holiday) {
      $holiday['date_formatted'] = Carbon::parse($holiday->date)->format('Y-m-d');
    }

    return view('content.anchor.factoring.planner', compact('anchors', 'programs', 'company', 'off_days', 'holidays'));
  }

  public function plannerCalculate(Request $request)
  {
    $program_id = $request->query('program');
    $amount = $request->query('amount');
    $invoice_date = $request->query('invoice_date');
    $due_date = $request->query('due_date');

    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendor_configurations = ProgramVendorConfiguration::find($program_id);

    $program = Program::find($vendor_configurations->program_id);

    $vendor_discount_details = ProgramVendorDiscount::where('program_id', $program->id)
      ->where('buyer_id', $vendor_configurations->buyer_id)
      ->first();
    $vendor_fees = ProgramVendorFee::where('program_id', $program->id)
      ->where('buyer_id', $vendor_configurations->buyer_id)
      ->get();
    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::where('program_id', $program->id)->first()?->tax_on_discount;

    $eligibility = $vendor_configurations->eligibility;
    $total_roi = $vendor_discount_details->total_roi;
    $legible_amount = ($eligibility / 100) * $amount;

    $fees_amount = 0;
    $anchor_bearing_fees = 0;
    $vendor_bearing_fees = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type === 'amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount += $fee->value * Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * $fee->value * Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value * Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));
            } else {
              $anchor_bearing_fees +=
                ($fee->anchor_bearing_discount / 100) *
                $fee->value *
                Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));
              $vendor_bearing_fees +=
                ($fee->vendor_bearing_discount / 100) *
                $fee->value *
                Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));
            }
          } else {
            $fees_amount += $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value;
            } else {
              $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
              $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
            }
          }
        }

        if ($fee->type === 'percentage') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              ($fee->value / 100) *
              $legible_amount *
              Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                ($fee->value / 100) *
                  $legible_amount *
                  Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }
          } else {
            $fees_amount += ($fee->value / 100) * $legible_amount;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
            }
          }
        }

        if ($fee->type === 'per amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              floor($legible_amount / $fee->per_amount) *
              $fee->value *
              Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date))),
                2
              );
            }
          } else {
            $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }

            if ($vendor_configurations->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }
          }
        }
      }
    }

    $total_discount = 0;
    // Discount Calculation
    if ($total_roi > 0) {
      if ($invoice_date && $due_date) {
        $total_discount =
          ($vendor_discount_details->vendor_discount_bearing / $total_roi) *
          ($eligibility / 100) *
          $amount *
          ($total_roi / 100) *
          (Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)) / 365);
      } else {
        $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

        $invoice = Invoice::where('status', 'approved')
          ->where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->whereNotIn('id', $invoices_in_processing)
          ->orderBy('due_date', 'DESC')
          ->first();

        $total_discount =
          ($vendor_discount_details->vendor_discount_bearing / $total_roi) *
          ($eligibility / 100) *
          $amount *
          ($total_roi / 100) *
          (now()->diffInDays(Carbon::parse($invoice->due_date)) / 365);
      }
    }

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $total_discount;
    }

    $total_actual_remittance =
      $legible_amount - $vendor_bearing_fees - $fees_tax_amount - $total_discount - $discount_tax_amount;

    if ($vendor_discount_details->discount_type == Invoice::REAR_ENDED) {
      $total_actual_remittance = $legible_amount - $vendor_bearing_fees - $fees_tax_amount;
      $total_discount = 0;
    }

    if ($vendor_discount_details->fee_type == Invoice::REAR_ENDED) {
      $total_actual_remittance = $total_actual_remittance + $vendor_bearing_fees + $fees_tax_amount;
    }

    return [round($total_discount, 2), round($total_actual_remittance, 2)];
  }

  public function factoringPrograms(Request $request)
  {
    $per_page = $request->query('per_page');
    $programs = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('buyer')
        ->whereHas('program', function ($query) {
          $query->whereHas('programCode', function ($query) {
            $query
              ->where('name', Program::FACTORING_WITH_RECOURSE)
              ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
          });
        })
        ->where('company_id', $company->id)
        ->select(
          'id',
          'program_id',
          'company_id',
          'buyer_id',
          'sanctioned_limit',
          'payment_account_number',
          'limit_expiry_date',
          'utilized_amount',
          'pipeline_amount'
        )
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['programs' => $programs], 200);
    }
  }

  public function factoringEligibleInvoices(Request $request)
  {
    $buyer = $request->query('buyer');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $minimum_financing_days = 0;

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoices = Invoice::factoring()
      ->with(
        'program.discountDetails',
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'buyer',
        'paymentRequests',
        'purchaseOrder.purchaseOrderItems'
      )
      ->where('company_id', $current_company->company_id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->whereDate('due_date', '>', now())
      ->where('eligible_for_financing', true)
      ->when($buyer && $buyer != '', function ($query) use ($buyer) {
        $query->whereHas('buyer', function ($query) use ($buyer) {
          $query->where('name', 'LIKE', '%' . $buyer . '%');
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
      ->orderBy('due_date', 'DESC')
      ->paginate($per_page);

    $minimum_financing_days = '';
    $highest_due_date = '';

    if ($invoices->count() > 0) {
      $minimum_financing_days = $invoices->first()->program->min_financing_days;
      $highest_due_date = $invoices->first()->due_date;
    }

    foreach ($invoices as $invoice) {
      $invoice['anchor'] = $invoice->buyer;

      // Get the program with the least minimum financing days
      if ($invoice->program->min_financing_days < $minimum_financing_days) {
        $minimum_financing_days = $invoice->program->min_financing_days;
      }

      // Get the invoice with the highest due date
      if (Carbon::parse($invoice->due_date)->greaterThan(Carbon::parse($highest_due_date))) {
        $highest_due_date = $invoice->due_date;
      }
    }

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    $terms_and_conditions = '#';
    $noa = '#';
    $noa_text = NoaTemplate::where('product_type', 'factoring')
      ->where('status', 'active')
      ->where('bank_id', $company->bank_id)
      ->first();

    if (!$noa_text) {
      $noa_text = NoaTemplate::where('product_type', 'generic')
        ->where('status', 'active')
        ->first();
    }
    $terms_text = TermsConditionsConfig::where('product_type', 'factoring')
      ->where('status', 'active')
      ->first();

    // get bank off days and holidays
    $off_days = $company->bank->adminConfiguration->offdays
      ? explode('-', str_replace(' ', '', $company->bank->adminConfiguration->offdays))
      : null;
    if ($off_days) {
      foreach ($off_days as $key => $off_day) {
        switch ($off_day) {
          case 'Monday':
            $off_days[$key] = 1;
            break;
          case 'Tuesday':
            $off_days[$key] = 2;
            break;
          case 'Wednesday':
            $off_days[$key] = 3;
            break;
          case 'Thursday':
            $off_days[$key] = 4;
            break;
          case 'Friday':
            $off_days[$key] = 5;
            break;
          case 'Saturday':
            $off_days[$key] = 6;
            break;
          case 'Sunday':
            $off_days[$key] = 0;
            break;
        }
      }
    }

    $holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->get();

    foreach ($holidays as $holiday) {
      $holiday['date_formatted'] = Carbon::parse($holiday->date)->format('d M Y');
    }

    return response()->json(
      [
        'invoices' => $invoices,
        'terms_and_conditions' => $terms_and_conditions,
        'noa' => $noa,
        'noa_text' => $noa_text,
        'terms_text' => $terms_text,
        'min_financing_days' => $minimum_financing_days,
        'highest_due_date' => $highest_due_date,
        'off_days' => $off_days,
        'holidays' => $holidays,
      ],
      200
    );
  }

  public function remittanceAmountDetails(Request $request)
  {
    $request->validate([
      'invoices' => ['required'],
      'date' => ['required'],
    ]);

    $total_amount = 0;
    $total_actual_remittance = 0;

    foreach ($request->invoices as $invoice) {
      $invoice = Invoice::find($invoice['id']);
      $total_amount += $invoice->invoice_total_amount;
      $total_actual_remittance += $invoice->calculateActualRemittanceAmount($request->date)['actual_remittance'];
    }

    return response()->json([
      'total_amount' => $total_amount,
      'total_remittance_amount' => $total_actual_remittance,
    ]);
  }

  public function downloadNoa(Invoice $invoice)
  {
    $noa_text = '';

    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $noa_text = NoaTemplate::where('product_type', 'vendor_financing')
          ->where('status', 'active')
          ->where('bank_id', $invoice->program->bank_id)
          ->first();
      } else {
        $noa_text = NoaTemplate::where('product_type', 'factoring')
          ->where('status', 'active')
          ->where('bank_id', $invoice->program->bank_id)
          ->first();
      }
    } else {
      $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
        ->where('status', 'active')
        ->where('bank_id', $invoice->program->bank_id)
        ->first();
    }

    if (!$noa_text) {
      $noa_text = NoaTemplate::where('product_type', 'generic')
        ->where('status', 'active')
        ->first();
    }

    $data = [];
    $data['{date}'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
    $data['{buyerName}'] = $invoice->program->anchor->name;
    $data['{anchorName}'] = $invoice->buyer->name;
    $data['{company}'] = $invoice->company->name;
    $data['{anchorCompanyUniqueID}'] = $invoice->program->anchor->unique_identification_number;
    $data['{time}'] = now()->format('d M Y');
    $data['{agreementDate}'] = now()->format('d M Y');
    $data['{contract}'] = '';
    $data['{anchorAccountName}'] = '';
    $data['{anchorAccountNumber}'] = '';
    $data['{anchorCustomerId}'] = '';
    $data['{anchorBranch}'] = '';
    $data['{anchorIFSCCode}'] = '';
    $data['{anchorAddress}'] = '';
    $data['{penalnterestRate}'] = '';
    $data['{sellerName}'] = $invoice->company->name;

    $noa = '';

    if ($noa_text && $noa_text->body) {
      $noa = $noa_text->body;
      foreach ($data as $key => $val) {
        $noa = str_replace($key, $val, $noa);
      }

      $pdf = Pdf::loadView('pdf.noa', [
        'data' => $noa,
      ])->setPaper('a4', 'landscape');

      return $pdf->download('NOA_' . $invoice->invoice_number . '.pdf');
    }
  }

  public function downloadTerms($type)
  {
    $terms_and_conditions = TermsConditionsConfig::where('status', 'active')
      ->where('product_type', $type)
      ->first();

    if ($terms_and_conditions && $terms_and_conditions->terms_conditions) {
      $pdf = Pdf::loadView('pdf.terms_and_conditions', [
        'data' => $terms_and_conditions->terms_conditions,
      ])->setPaper('a4', 'landscape');

      return $pdf->download('Terms and Conditions.pdf');
    }
  }

  public function factoringRequests()
  {
    return view('content.anchor.factoring.financing-requests');
  }

  public function factoringRequestsData(Request $request)
  {
    $buyer = $request->query('buyer');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $finance_requests = PaymentRequest::where('processing_fee', '!=', null)
      ->with('paymentAccounts')
      ->whereHas('invoice', function ($query) use ($current_company, $buyer) {
        $query
          ->where('company_id', $current_company->company_id)
          ->whereHas('program', function ($query) {
            $query->whereHas('programCode', function ($query) {
              $query->where(function ($query) {
                $query
                  ->where('name', Program::FACTORING_WITH_RECOURSE)
                  ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
              });
            });
          })
          ->when($buyer && $buyer != '', function ($query) use ($buyer) {
            $query->whereHas('buyer', function ($query) use ($buyer) {
              $query->where('name', 'LIKE', '%' . $buyer . '%');
            });
          });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->orderBy('reference_number', 'DESC')
      ->paginate($per_page);

    $finance_requests = PaymentRequestResource::collection($finance_requests)
      ->response()
      ->getData();
    if (request()->wantsJson()) {
      return response()->json(['finance_requests' => $finance_requests], 200);
    }
  }

  public function updateFinanceRequest(Request $request, PaymentRequest $payment_request)
  {
    $request->validate([
      'status' => ['required', 'in:approved,rejected'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    if ($request->status == 'approved') {
      $payment_request->companyApprovals()->delete();

      // $payment_request->invoice->program->bank->notify(new PaymentRequestNotification($payment_request));

      activity($payment_request->invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($payment_request)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
        ->log('requested financing');

      $noa_text = NoaTemplate::where('product_type', 'factoring')
        ->where('status', 'active')
        ->where('bank_id', $payment_request->invoice->program->bank_id)
        ->first();

      if (!$noa_text) {
        $noa_text = NoaTemplate::where('product_type', 'generic')
          ->where('status', 'active')
          ->first();
      }

      $vendor_configurations = ProgramVendorConfiguration::where('company_id', $payment_request->invoice->company_id)
        ->where('program_id', $payment_request->invoice->program_id)
        ->where('buyer_id', $payment_request->invoice->buyer_id)
        ->first();

      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $payment_request->invoice->company_id)
        ->where('program_id', $payment_request->invoice->program_id)
        ->where('buyer_id', $payment_request->invoice->buyer_id)
        ->first();

      // Send NOA
      $data = [];
      $data['{date}'] = Carbon::parse($payment_request->invoice->invoice_date)->format('d M Y');
      $data['{buyerName}'] = $payment_request->invoice->company->name;
      $data['{anchorName}'] = $payment_request->invoice->program->anchor->name;
      $data['{company}'] = $payment_request->invoice->company->name;
      $data['{anchorCompanyUniqueID}'] = $payment_request->invoice->program->anchor->unique_identification_number;
      $data['{time}'] = now()->format('d M Y');
      $data['{agreementDate}'] = now()->format('d M Y');
      $data['{contract}'] = '';
      $data['{anchorAccountName}'] = $payment_request->invoice->program->bankDetails->first()->account_name;
      $data['{anchorAccountNumber}'] = $payment_request->invoice->program->bankDetails->first()->account_number;
      $data['{anchorCustomerId}'] = '';
      $data['{anchorBranch}'] = $payment_request->invoice->program->anchor->branch_code;
      $data['{anchorIFSCCode}'] = '';
      $data['{anchorAddress}'] =
        $payment_request->invoice->program->anchor->postal_code .
        ' ' .
        $payment_request->invoice->program->anchor->address .
        ' ' .
        $payment_request->invoice->program->anchor->city .
        ' ';
      $data['{penalnterestRate}'] = $vendor_discount_details->penal_discount_on_principle;
      $data['{sellerName}'] = $payment_request->invoice->company->name;

      $noa = '';

      // Check if auto approve finance requests is enabled
      if ($vendor_configurations->auto_approve_finance) {
        $payment_request->update([
          'status' => 'approved',
        ]);

        // Create CBS Transactions for the payment request
        $payment_request->createCbsTransactions();
      }

      // Notify Bank of new payment request
      foreach ($payment_request->invoice->program->bank->users as $bank_user) {
        if ($noa_text != null) {
          $noa = $noa_text->body;
          foreach ($data as $key => $val) {
            $noa = str_replace($key, $val, $noa);
          }

          $pdf = Pdf::loadView('pdf.noa', [
            'data' => $noa,
          ])->setPaper('a4', 'landscape');
        }

        // SendMail::dispatchAfterResponse($bank_user->email, 'PaymentRequested', [
        //   'payment_request_id' => $payment_request->id,
        //   'link' => config('app.url') . '/' . $payment_request->invoice->program->bank->url,
        //   'type' => 'factoring',
        //   'noa' => $noa_text != NULL ? $pdf->output() : NULL
        // ]);
      }

      if ($request->wantsJson()) {
        return response()->json(['payment_request' => new PaymentRequestResource($payment_request)]);
      }

      toastr()->success('', 'Financing Request updated successfully');

      return back();
    } else {
      $payment_request->update([
        'status' => 'rejected',
        'rejected_reason' => $request->rejection_reason,
      ]);

      $financing_request_approval = FinanceRequestApproval::where('payment_request_id', $payment_request->id)->first();
      $financing_request_approval->update([
        'status' => 'rejected',
        'rejection_reason' => $request->rejection_reason,
      ]);

      // Update Program and Company Pipeline and Utilized Amounts
      $payment_request->invoice->company->decrement(
        'pipeline_amount',
        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
          ? $payment_request->invoice->drawdown_amount
          : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
      );

      $payment_request->invoice->program->decrement(
        'pipeline_amount',
        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
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
        $payment_request->invoice->program->programType->name == Program::DEALER_FINANCING
          ? $payment_request->invoice->drawdown_amount
          : ($payment_request->invoice->eligibility / 100) * $payment_request->invoice->invoice_total_amount
      );

      // TODO: Notify users of rejection of financing request

      if ($request->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Financing Request updated successfully');

      return back();
    }
  }

  public function requestFinance(Request $request)
  {
    $request->validate([
      'invoice_id' => ['required'],
      'payment_request_date' => ['required', 'date'],
      'credit_to' => ['required'],
    ]);

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    if ($company->status == 'inactive') {
      return response()->json(['message' => 'Company has been deactivated. Contact bank for assistance.'], 422);
    }

    if ($company->is_blocked) {
      return response()->json(
        ['message' => 'Company has been locked from making requests. Contact bank for assistance.'],
        422
      );
    }

    $invoice = Invoice::with('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'company')->find($request->invoice_id);

    $program = Program::find($invoice->program_id);

    // Check if program is active
    if ($program->account_status == 'suspended') {
      // Notify user
      return response()->json(
        ['message' => 'Program is currently unavailable to make requests. Contact bank for assistance.'],
        422
      );
    }

    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company->id)
      ->where('program_id', $invoice->program_id)
      ->where('buyer_id', $invoice->buyer_id)
      ->first();

    // Check if company can make the request on the program
    if (
      $vendor_configurations->is_blocked ||
      !$vendor_configurations->is_approved ||
      $vendor_configurations->status == 'inactive'
    ) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToUnblock', ['company_id' => $company->id])->afterResponse();
      }

      // Notify user
      return response()->json(
        ['message' => 'Your account has been deactivated on the program. Contact bank for assistance.'],
        422
      );
    }

    $utilized_amount = $program->utilized_amount;

    $vendor_utilized_amount = $company->utilized_amount;
    $pipeline_requests = $company->pipeline_amount;

    // Check against top level borrower limit
    if (
      $vendor_utilized_amount + $pipeline_requests + $invoice->invoice_total_amount >
      $company->top_level_borrower_limit
    ) {
      return response()->json(['message' => 'Amount exceeds your top level borrowing limit.'], 422);
    }

    // Get Retain Limit as set in Bank Configuration
    $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
      ->where('product_type_id', $invoice->program->program_type_id)
      ->where('name', 'retain limit')
      ->first();

    if ($bank_configurations->value > 0) {
      $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
      $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
      $potential_utilization_amount = $utilized_amount + $pipeline_requests + $invoice->invoice_total_amount;
      if ($potential_utilization_amount > $remainder) {
        return response()->json(['message' => 'Amount exceeds your borrowing limit.'], 422);
      }
    }

    // Check if request will exceed OD Account Sanctioned program limit
    $vendor_configuration_utilized_amount = $vendor_configurations->utilized_amount;
    $vendor_configuration_pipeline_amount = $vendor_configurations->pipeline_amount;
    if (
      $vendor_configuration_utilized_amount + $vendor_configuration_pipeline_amount + $invoice->invoice_total_amount >
      $vendor_configurations->sanctioned_limit
    ) {
      $vendor_configuration_available_limit =
        $vendor_configurations->sanctioned_limit -
        $vendor_configuration_utilized_amount -
        $vendor_configuration_pipeline_amount;
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $vendor_configurations->sanctioned_limit,
          'current_exposure' => $vendor_configuration_utilized_amount,
          'pipeline_requests' => $vendor_configuration_pipeline_amount,
          'available_limit' => $vendor_configuration_available_limit,
        ])->afterResponse();
      }

      return response()->json(['message' => 'Vendor Limit exceeded. Contact Bank For Assistance'], 422);
    }

    // Check if request will exceed program limit
    $program_utilized_amount = $program->utilized_amount;
    $program_pipeline_amount = $program->pipeline_amount;
    if (
      $program_utilized_amount + $program_pipeline_amount + $invoice->invoice_total_amount >
      $program->program_limit
    ) {
      $program_available_limit = $program->program_limit - $program->utilized_amount - $program->pipeline_amount;
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $program->program_limit,
          'current_exposure' => $program_utilized_amount,
          'pipeline_requests' => $program_pipeline_amount,
          'available_limit' => $program_available_limit,
        ])->afterResponse();
      }

      return response()->json(['message' => 'Program Limit exceeded. Contact Bank For Assistance'], 422);
    }

    // Check if request will exceed drawing power
    if ($vendor_configurations->drawing_power > 0) {
      if ($invoice->invoice_total_amount > $vendor_configurations->drawing_power) {
        // Notify bank of request to unblock
        foreach ($company->bank->users as $user) {
          SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
            'company_id' => $company->id,
            'approved_limit' => $vendor_configurations->sanctioned_limit,
            'current_exposure' => $vendor_configuration_utilized_amount,
            'pipeline_requests' => $vendor_configuration_pipeline_amount,
            'available_limit' =>
              $vendor_configurations->sanctioned_limit -
              $vendor_configuration_utilized_amount -
              $vendor_configuration_pipeline_amount,
          ])->afterResponse();
        }

        return response()->json(['message' => 'Drawing Power Limit exceeded. Contact Bank For Assistance'], 422);
      }
    }

    // $response = $invoice->requestFinance($vendor_configurations, $request->credit_to, $request->payment_request_date);
    InvoiceProcessing::create([
      'company_id' => $company->id,
      'invoice_id' => $invoice->id,
      'action' => 'requesting financing',
      'status' => 'pending',
      'data' => [
        'payment_request_date' => $request->payment_request_date,
        'credit_to' => $request->credit_to,
      ],
    ]);

    BulkRequestFinancing::dispatchAfterResponse($company);

    if ($request->wantsJson()) {
      return response()->json('Payment was requested successfully');
    } else {
      toastr()->success('', 'Payment was requested successfully');

      return back();
    }
  }

  public function requestMultipleFinance(Request $request)
  {
    $request->validate([
      'invoices' => ['required'],
      'payment_request_date' => ['required', 'date'],
    ]);

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    if ($company->status === 'inactive') {
      return response()->json(['message' => 'Company has been deactivated. Contact bank for assistance.'], 422);
    }

    if ($company->is_blocked) {
      return response()->json(
        ['message' => 'Company has been locked from making requests. Contact bank for assistance.'],
        422
      );
    }

    // Check if payment request date is not a bank holiday or off day
    $bank_holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->pluck('date')
      ->toArray();

    $off_days = $company->bank->adminConfiguration->offdays;
    $off_days = $off_days ? explode('-', str_replace(' ', '', $off_days)) : [];
    $off_days_converted = [];
    foreach ($off_days as $off_day) {
      switch ($off_day) {
        case 'Monday':
          $off_days_converted[] = 1;
          break;
        case 'Tuesday':
          $off_days_converted[] = 2;
          break;
        case 'Wednesday':
          $off_days_converted[] = 3;
          break;
        case 'Thursday':
          $off_days_converted[] = 4;
          break;
        case 'Friday':
          $off_days_converted[] = 5;
          break;
        case 'Saturday':
          $off_days_converted[] = 6;
          break;
        case 'Sunday':
          $off_days_converted[] = 0;
          break;
      }
    }

    if (in_array(Carbon::parse($request->payment_request_date)->format('Y-m-d'), $bank_holidays)) {
      return response()->json(['message' => 'Payment request date falls on a bank holiday.'], 422);
    }

    if (in_array(Carbon::parse($request->payment_request_date)->dayOfWeek, $off_days_converted)) {
      return response()->json(['message' => 'Payment request date falls on a bank off day.'], 422);
    }

    $sum_amount = 0;
    $message = 'Requested amount exceeds available limit.';

    Invoice::whereIn('id', $request->invoices)->chunk(100, function ($invoices) use (&$sum_amount) {
      foreach ($invoices as $invoice) {
        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->where('buyer_id', $invoice->buyer_id)
          ->select('eligibility')
          ->first();

        $sum_amount += ($vendor_configurations->eligibility / 100) * $invoice->calculated_total_amount;
      }
    });

    $requested_payment_date = $request->payment_request_date;
    $can_request = true;
    // & used in can_request to pass by reference
    // Check if request will exceed vendor program limit
    // Check if request will exceed program limit
    // Check if request will exceed company top level borrower limit
    Invoice::whereIn('id', $request->invoices)->chunk(100, function ($invoices) use (
      $company,
      &$can_request,
      &$message,
      $sum_amount
    ) {
      foreach ($invoices as $invoice) {
        $program = Program::find($invoice->program_id);

        // Check if program is active
        if ($program->account_status == 'suspended') {
          // Notify user
          $message = 'Program is currently unavailable to make requests. Contact bank for assistance.';
          $can_request = false;
        }

        $invoice_total_amount = $invoice->invoice_total_amount;

        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->where('buyer_id', $invoice->buyer_id)
          ->first();

        // Check if company can make the request on the program
        if (
          $vendor_configurations->is_blocked ||
          !$vendor_configurations->is_approved ||
          $vendor_configurations->status == 'inactive'
        ) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToUnblock', ['company_id' => $company->id]);
          }

          // Notify user
          $message = 'This OD Account has been blocked/deactivated on the program. Contact bank for assistance.';
          $can_request = false;
        }

        // Check limits at OD Level
        $sanctioned_limit = $vendor_configurations->sanctioned_limit;
        $utilized_amount = $vendor_configurations->utilized;
        $pipeline_amount = $vendor_configurations->pipeline;

        $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
            ]);
          }
          $message = 'Amount exceeds your borrowing limit. Contact bank for assistance.';
          $can_request = false;
        }

        // Get Retain Limit as set in Bank Configuration
        $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
          ->where('product_type_id', $invoice->program->program_type_id)
          ->where('name', 'retain limit')
          ->first();

        if ($bank_configurations->value > 0) {
          $retain_amount = ($bank_configurations->value / 100) * $vendor_configurations->sanctioned_limit;
          $remainder = $vendor_configurations->sanctioned_limit - $retain_amount;
          $potential_utilization_amount = $utilized_amount + $pipeline_amount + $invoice_total_amount;
          if ($potential_utilization_amount > $remainder) {
            $message = 'Amount exceeds your borrowing limit. Contact bank for assistance.';
            $can_request = false;
          }
        }

        // Check if request exceeds company top level borrower limit
        $top_level_borrower_limit = $company->top_level_borrower_limit;
        $utilized_amount = $company->utilized_amount;
        $pipeline_amount = $company->pipeline_amount;
        $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          $message = 'Requested amount exceeds company top level borrower limit. Contact bank for assistance.';
          $can_request = false;
        }

        $program = Program::find($invoice->program_id);

        // Check at program level
        $program_limit = $program->program_limit;
        $utilized_amount = $program->utilized;
        $pipeline_amount = $program->pipeline;
        $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
            ]);
          }
          $message = 'Requested amount exceeds program limit. Contact bank for assistance.';
          $can_request = false;
        }

        // Check if request will exceed drawing power
        if ($vendor_configurations->drawing_power > 0) {
          if ($invoice_total_amount > $vendor_configurations->drawing_power) {
            // Notify bank of request to unblock
            foreach ($company->bank->users as $user) {
              SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
                'company_id' => $company->id,
                'approved_limit' => $vendor_configurations->sanctioned_limit,
                'current_exposure' => $utilized_amount,
                'pipeline_requests' => $pipeline_amount,
                'available_limit' => $available_limit,
              ]);
            }
            $message = 'Requested amount exceeds drawing power limit. Contact bank for assistance.';
            $can_request = false;
          }
        }
      }
    });

    if (!$can_request) {
      return response()->json(['message' => $message], 422);
    }

    Invoice::whereIn('id', $request->invoices)
      ->orderBy('due_date', 'DESC')
      ->chunk(50, function ($invoices) use ($company, $requested_payment_date) {
        foreach ($invoices as $invoice) {
          InvoiceProcessing::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'action' => 'requesting financing',
            'status' => 'pending',
            'data' => [
              'payment_request_date' => $requested_payment_date,
            ],
          ]);
        }
      });

    BulkRequestFinancing::dispatchAfterResponse($company);

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Payment Requests made successfully']);
    }

    toastr()->success('', 'Payment request successfully created.');

    return back();
  }

  public function eligibleForFinancing(Program $program)
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $total_amount = 0;

    $minimum_financing_days = $program->min_financing_days;
    $maximum_financing_days = $program->max_financing_days;

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoices = Invoice::factoring()
      ->where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->whereDate('due_date', '>=', now())
      ->orderBy('due_date', 'DESC')
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now();
      });

    $min_invoice_date = now();

    if ($invoices->count() > 0) {
      $max_invoice_date = Carbon::parse($invoices->first()->due_date)->subDays($minimum_financing_days);
      $min_invoice_date = Carbon::parse($invoices->first()->due_date)->subDays($maximum_financing_days);
      foreach ($invoices as $invoice) {
        $eligibility = ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('buyer_id', $invoice->buyer_id)
          ->where('company_id', $invoice->company_id)
          ->first()->eligibility;

        $total_amount += ($eligibility / 100) * $invoice->invoice_total_amount;
        if (
          Carbon::parse($invoice->due_date)
            ->subDays($maximum_financing_days)
            ->lessThan(Carbon::parse($min_invoice_date))
        ) {
          $min_invoice_date = Carbon::parse($invoice->due_date)->subDays($maximum_financing_days);
        }
      }

      if (Carbon::parse($min_invoice_date)->lessThan(now())) {
        $min_invoice_date = now();
      }
      return response()->json([
        'total_amount' => $total_amount,
        'max_date' => $max_invoice_date,
        'min_date' => $min_invoice_date,
        'min_financing_days' => $minimum_financing_days,
      ]);
    }
    return response()->json(['error' => 'No valid invoices in this program']);
  }

  public function exportInvoices(Request $request)
  {
    $date = now()->format('Y-m-d');

    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    Excel::store(
      new CashPlannerInvoices($request->selected_program, $company, $request->selected_date),
      'Eligible_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Eligible_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function eligibleForFinancingCalculate(Program $program, $date)
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $total_discount = 0;
    $discount = 0;
    $total_invoice_amount = 0;
    $total_actual_remittance = 0;

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoices = Invoice::where('company_id', $company->id)
      ->where('program_id', $program->id)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now())
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now();
      });

    foreach ($invoices as $invoice) {
      $total_invoice_amount += $invoice->invoice_total_amount;
      $response = $invoice->calculateActualRemittanceAmount($date);
      $total_actual_remittance += $response['actual_remittance'];
      $discount += $response['discount'];
    }

    $total_discount = $discount;

    return [round($total_discount, 2), round($total_actual_remittance, 2)];
  }

  public function storeMassFinancingRequest(Request $request)
  {
    $request->validate([
      'payment_date' => ['required'],
      'program_id' => ['required'],
    ]);

    $invoice_ids = [];

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    if ($company->status == 'inactive') {
      toastr()->error('', 'Company has been deactivated. Contact bank for assistance');

      return back();
    }

    if ($company->is_blocked) {
      toastr()->error('', 'Company has been blocked from making requests. Contact bank for assistance');

      return back();
    }

    // Check if payment request date is not a bank holiday or off day
    $bank_holidays = BankHoliday::active()
      ->where('bank_id', $company->bank->id)
      ->pluck('date')
      ->toArray();

    $off_days = $company->bank->adminConfiguration->offdays;
    $off_days = $off_days ? explode('-', str_replace(' ', '', $off_days)) : [];
    $off_days_converted = [];
    foreach ($off_days as $off_day) {
      switch ($off_day) {
        case 'Monday':
          $off_days_converted[] = 1;
          break;
        case 'Tuesday':
          $off_days_converted[] = 2;
          break;
        case 'Wednesday':
          $off_days_converted[] = 3;
          break;
        case 'Thursday':
          $off_days_converted[] = 4;
          break;
        case 'Friday':
          $off_days_converted[] = 5;
          break;
        case 'Saturday':
          $off_days_converted[] = 6;
          break;
        case 'Sunday':
          $off_days_converted[] = 0;
          break;
      }
    }

    if (in_array(Carbon::parse($request->payment_date)->format('Y-m-d'), $bank_holidays)) {
      toastr()->error('', 'Payment request date falls on a bank holiday.');

      return back();
    }

    if (in_array(Carbon::parse($request->payment_date)->dayOfWeek, $off_days_converted)) {
      toastr()->error('', 'Payment request date falls on a bank off day.');

      return back();
    }

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoice_ids = Invoice::factoring()
      ->where('company_id', $company->id)
      ->where('program_id', $request->program_id)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now())
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->where('eligible_for_financing', true)
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now();
      })
      ->pluck('id');

    // Get total amount of the requested invoices
    $sum_amount = 0;
    $message = 'Requested amount exceeds your limit. Contact bank for assistance.';

    Invoice::whereIn('id', $invoice_ids)->chunk(100, function ($invoices) use (&$sum_amount) {
      foreach ($invoices as $invoice) {
        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->select('eligibility')
          ->first();

        $sum_amount += ($vendor_configurations->eligibility / 100) * $invoice->calculated_total_amount;
      }
    });

    $can_request = true;
    // & used in can_request to pass by reference
    // Check if request will exceed vendor program limit
    // Check if request will exceed program limit
    // Check if request will exceed company top level borrower limit
    Invoice::whereIn('id', $invoice_ids)->chunk(50, function ($invoices) use (
      $company,
      &$can_request,
      &$sum_amount,
      &$message
    ) {
      foreach ($invoices as $invoice) {
        $invoice_total_amount = $invoice->invoice_total_amount;

        $program = Program::find($invoice->program_id);

        // Check if program is active
        if ($program->account_status === 'suspended') {
          $message = 'Program is currently unavailable to make requests. Contact bank for assistance.';
          $can_request = false;
        }

        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->where('buyer_id', $invoice->buyer_id)
          ->first();

        // Check limits at OD Level
        $sanctioned_limit = $vendor_configurations->sanctioned_limit;
        $utilized_amount = $vendor_configurations->utilized_amount;
        $pipeline_amount = $vendor_configurations->pipeline_amount;

        $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
            ]);
          }
          $message = 'Amount exceeds your sanctioned limit. Contact bank for assistance.';
          $can_request = false;
        }

        // Check at program level
        $program_limit = $program->program_limit;
        $utilized_amount = $program->utilized_amount;
        $pipeline_amount = $program->pipeline_amount;
        $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
            ]);
          }

          $message = 'Requested amount exceeds program limit. Contact bank for assistance.';
          $can_request = false;
        }

        // Check if request exceeds company top level borrower limit
        $top_level_borrower_limit = $company->top_level_borrower_limit;
        $utilized_amount = $company->total_utilized_amount;
        $pipeline_amount = $company->total_pipeline_amount;
        $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          $message = 'Requested amount exceeds company top level borrower limit. Contact bank for assistance.';
          $can_request = false;
        }
      }
    });

    if (!$can_request) {
      toastr()->error('', $message);

      return back();
    }

    $requested_payment_date = $request->payment_date;

    Invoice::whereIn('id', $invoice_ids)->chunk(50, function ($invoices_data) use ($requested_payment_date, $company) {
      foreach ($invoices_data as $invoice) {
        InvoiceProcessing::create([
          'company_id' => $company->id,
          'invoice_id' => $invoice->id,
          'action' => 'requesting financing',
          'status' => 'pending',
          'data' => [
            'payment_request_date' => $requested_payment_date,
          ],
        ]);
      }
    });

    BulkRequestFinancing::dispatchAfterResponse($company);

    toastr()->success('', 'Payment requests created successfully.');

    return back();
  }

  public function checkInvoiceNumber($number, $dealer = null)
  {
    if ($dealer == null) {
      $current_company = auth()
        ->user()
        ->activeFactoringCompany()
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

  public function factoringOdAccounts()
  {
    return view('content.anchor.factoring.invoice.od-accounts');
  }

  public function factoringOdAccountsData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

  public function factoringOdAccountDetails(ProgramVendorConfiguration $program_vendor_configuration)
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
      'content.anchor.factoring.invoice.od-account-details',
      compact('program_vendor_configuration', 'cbs_transactions')
    );
  }

  public function factoringOdAccountCbsTransactions(
    Request $request,
    ProgramVendorConfiguration $program_vendor_configuration
  ) {
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

  public function factoringPaymentInstructions()
  {
    return view('content.anchor.factoring.invoice.dealer-payment-requests');
  }

  public function factoringPaymentinstructionsData(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $company_programs = [];
    foreach ($company->programs as $program) {
      if ($program->programType->name == 'Dealer Financing') {
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
    return view('content.anchor.factoring.invoice.dpd');
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
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $company_programs = [];
    foreach ($company->programs as $program) {
      if ($program->programType->name == 'Dealer Financing') {
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

  public function dealerRejectedInvoices()
  {
    return view('content.anchor.factoring.invoice.rejected');
  }

  public function dealerRejectedInvoicesData(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $dealer = $request->query('dealer');
    $range = $request->query('range');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get programs
    $company_programs = [];
    foreach ($company->programs as $program) {
      if ($program->programType->name == 'Dealer Financing') {
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
