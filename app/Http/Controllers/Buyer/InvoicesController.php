<?php

namespace App\Http\Controllers\Buyer;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Models\Currency;
use App\Models\InvoiceFee;
use App\Models\InvoiceTax;
use App\Models\CompanyUser;
use App\Models\ImportError;
use App\Models\NoaTemplate;
use App\Models\ProgramCode;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Exports\InvoicesExport;
use App\Imports\InvoicesImport;
use App\Models\InvoiceApproval;
use App\Models\ProgramDiscount;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProgramVendorFee;
use App\Exports\UploadedInvoices;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceUploadReport;
use App\Exports\InvoicesErrorReport;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceApprovalResource;
use App\Http\Resources\InvoiceDetailsResource;
use App\Mail\FinanceRequestApproval;
use App\Models\CreditAccountRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PaymentRequestAccount;
use App\Models\ProgramVendorDiscount;
use App\Models\TermsConditionsConfig;
use App\Notifications\InvoiceUpdated;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Http\Resources\PaymentRequestResource;
use App\Imports\BuyerInvoicesImport;
use App\Imports\DealerFinancing;
use App\Jobs\AutoRequestFinancing;
use App\Jobs\BulkRequestFinancing;
use App\Jobs\InvoiceUpdateNotification;
use App\Models\AuthorizationMatrixRule;
use App\Models\BankConvertionRate;
use App\Models\BankGeneralProductConfiguration;
use App\Models\BankHoliday;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\BankProductsConfiguration;
use App\Models\BankTaxRate;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\CompanyUserAuthorizationGroup;
use Illuminate\Support\Facades\Validator;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Notifications\PaymentRequestNotification;
use App\Models\FinanceRequestApproval as ModelFinanceRequestApproval;
use App\Models\InvoiceProcessing;

class InvoicesController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $invoices = Invoice::factoring()
      ->where('buyer_id', $current_company->company_id)
      ->count();
    $pending_invoices = Invoice::factoring()
      ->where('buyer_id', $current_company->company_id)
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->whereIn('status', ['pending', 'created', 'submitted'])
      ->count();

    return view('content.buyer.invoices.index', compact('invoices', 'pending_invoices'));
  }

  public function pending()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $invoices = Invoice::where('buyer_id', $current_company->company_id)->count();
    $pending_invoices = Invoice::where('buyer_id', $current_company->company_id)
      ->whereIn('status', ['pending', 'created', 'submitted'])
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->count();

    return view('content.buyer.invoices.pending', compact('invoices', 'pending_invoices'));
  }

  public function anchors()
  {
    return view('content.buyer.invoices.anchors');
  }

  public function anchorsData(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor_search = $request->query('vendor');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get vendors
    $vendors = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('company')
        ->where('buyer_id', $company->id)
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

  public function invoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
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
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::with('company', 'program.anchor', 'invoiceItems', 'invoiceFees', 'invoiceTaxes')
      ->where('buyer_id', $current_company->company_id)
      ->whereIn('status', ['submitted', 'disbursed', 'approved', 'denied', 'expired'])
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('company', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
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

  public function pendingInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');
    $financing_status = $request->query('financing_status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = Invoice::with('company', 'invoiceItems', 'invoiceFees', 'invoiceTaxes', 'program.anchor')
      ->where('buyer_id', $current_company->company_id)
      ->where('status', 'submitted')
      ->whereDate('due_date', '>=', now()->format('Y-m-d'))
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('company', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
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
      ->where('stage', '!=', 'internal_reject')
      ->when($financing_status && count($financing_status) > 0, function ($query) use ($financing_status) {
        $query->whereIn('financing_status', $financing_status);
      })
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

  public function buyerPaymentInstructions()
  {
    return view('content.buyer.invoices.payment-instructions');
  }

  public function paymentInstructionsData(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice');
    $per_page = $request->query('per_page');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $finance_requests = InvoiceResource::collection(
      Invoice::factoring()
        ->where('pi_number', '!=', null)
        ->where('buyer_id', $current_company->company_id)
        ->whereHas('paymentRequests')
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program.anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        })
        ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        })
        ->when($status && $status != '', function ($query) use ($status) {
          $query->where('financing_status', $status);
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

  public function edit(Invoice $invoice)
  {
    if (!$invoice->can_edit) {
      toastr()->error('', 'Invoice cannot be edited');
      return redirect()->route('buyer.invoices.index');
    }

    $invoice->load('invoiceTaxes', 'invoiceFees', 'invoiceDiscounts');

    $max_days = $invoice->program->max_days_due_date_extension;

    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $buyer_role = ProgramRole::where('name', 'buyer')->first();

    $anchors_programs = ProgramCompanyRole::where(['role_id' => $anchor_role->id, 'company_id' => $invoice->company_id])
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', 'Factoring With Recourse')->orWhere('name', 'Factoring Without Recourse');
        });
      })
      ->pluck('program_id');

    $filtered_programs = ProgramCompanyRole::whereIn('program_id', $anchors_programs)
      ->where('role_id', $buyer_role->id)
      ->where('company_id', $current_company->company_id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::whereIn('program_id', $filtered_programs)
      ->where('buyer_id', $current_company->company_id)
      ->first();

    $payment_account_number = $programs->payment_account_number;

    $taxes = [];

    $company_taxes = $invoice->company->taxes;
    if ($company_taxes->count() > 0) {
      foreach ($company_taxes as $company_tax) {
        $taxes[$company_tax->tax_name . '(' . $company_tax->tax_number . ')'] = $company_tax->tax_value;
      }
    }

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    if ($invoice->company->bank->adminConfiguration) {
      if ($invoice->company->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = explode(
          ',',
          str_replace("\"", '', $invoice->company->bank->adminConfiguration->selectedCurrencyIds)
        );
      } elseif ($invoice->company->bank->adminConfiguration->defaultCurrency) {
        $currency = [$invoice->company->bank->adminConfiguration->defaultCurrency];
      }
    }

    $currencies = Currency::whereIn('id', $currency)->get();

    return view('content.buyer.invoices.edit', [
      'invoice' => $invoice,
      'max_days' => $max_days,
      'payment_account_number' => $payment_account_number,
      'taxes' => $taxes,
      'currencies' => $currencies,
    ]);
  }

  public function update(Request $request, Invoice $invoice)
  {
    // Check if user has permission to perform the update
    if (!$invoice->can_edit) {
      toastr()->error('', 'You don\'t have permission to edit this invoice');
      return redirect()->route('buyer.invoices.index');
    }

    $request->validate([
      'due_date' => ['required', 'date'],
    ]);

    // Check if invoice already has a financing request
    if ($invoice->paymentRequests->count() > 0) {
      toastr()->error('', 'Cannot update. Invoice already has a finance request.');
      return redirect()->route('buyer.invoices.index');
    }

    try {
      DB::beginTransaction();

      $old_date = $invoice->due_date;

      $invoice->update([
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'old_due_date' => $old_date,
      ]);

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

      return redirect()->route('buyer.invoices.index');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      toastr()->error('', 'An error occurred');
      return back();
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
      ->activeBuyerFactoringCompany()
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
              'name' => $tax,
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

    $invoice_setting = $invoice->company->invoiceSetting;

    $invoice->approvals()->create([
      'user_id' => auth()->id(),
    ]);

    $approvals = InvoiceApproval::where('invoice_id', $invoice->id)
      ->whereIn('user_id', $company->users->pluck('id'))
      ->count();

    // Get authorization matrix
    $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->buyer_id)
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
        $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->buyer_id)
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
                $query->where('name', 'View Invoices');
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
          if ($invoice_setting && $invoice_setting->auto_request_financing) {
            if ($invoice->canRequestFinancing()) {
              $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
                ->where('buyer_id', $invoice->buyer_id)
                ->where('program_id', $invoice->program_id)
                ->first();
              $vendor_bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();

              $invoice->requestFinance($vendor_configurations, $vendor_bank_details->id, now()->format('Y-m-d'));
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

    $invoice->company->notify(new InvoiceUpdated($invoice));

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
  }

  public function approveFees(Invoice $invoice)
  {
    // Check if user has permission to perform the update
    if (!$invoice->user_can_approve) {
      toastr()->error('', 'You don\'t have permission to edit this invoice');
      return redirect()->route('buyer.invoices.index');
    }

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first()->company_id;

    $company = Company::find($current_company);

    $invoice->load('invoiceItems', 'invoiceFees', 'invoiceTaxes', 'invoiceDiscounts', 'company');

    // Add user approval to invoice approvals
    $invoice->approvals()->create([
      'user_id' => auth()->id(),
    ]);

    // Maker/Checker is required
    $approvals = InvoiceApproval::where('invoice_id', $invoice->id)
      ->whereIn('user_id', $company->users->pluck('id'))
      ->get();

    // Get authorization matrix
    $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->buyer_id)
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
        $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->buyer_id)
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
              $query->where('name', 'View Invoices');
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
          'eligible_for_financing' => true,
          'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
          'calculated_total_amount' => $invoice->invoice_total_amount,
        ]);

        // $invoice->company->notify(new InvoiceUpdated($invoice));
        foreach ($invoice->company->users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'InvoiceApproval', [
            'id' => $invoice->id,
            'type' => 'vendor_financing',
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

  public function bulkApprove(Request $request)
  {
    ini_set('max_execution_time', 800);
    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
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
        $anchor_users,
        $updated_invoice_fees,
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
              ->where('buyer_id', $invoice->buyer_id)
              ->first();

            $invoice->approvals()->create([
              'user_id' => auth()->id(),
            ]);

            $approvals = InvoiceApproval::where('invoice_id', $invoice->id)
              ->whereIn('user_id', $anchor_users)
              ->count();

            // Get authorization matrix
            $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->buyer_id)
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
                $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->buyer_id)
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
                    SendMail::dispatchAfterResponse($user->email, 'InvoiceUpdated', [
                      'id' => $invoice->id,
                      'type' => 'vendor_financing',
                    ]);
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
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices_details = [];

    foreach (explode(',', $request->invoices) as $inv) {
      $details = new InvoiceApprovalResource(Invoice::with('invoiceFees')->find($inv));

      array_push($invoices_details, $details);
    }

    return response()->json(['data' => $invoices_details]);
  }

  public function reject(Request $request, Invoice $invoice)
  {
    $invoice->update([
      'status' => 'denied',
      'stage' => 'rejected',
      'rejected_reason' => $request->rejected_reason,
    ]);

    activity($invoice->program->bank->id)
      ->causedBy(auth()->user())
      ->performedOn($invoice)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Dealer'])
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
    }

    if (!request()->wantsJson()) {
      toastr()->success('', 'Invoices successfully updated');

      return back();
    }

    return response()->json('Invoices updated successfully', 200);
  }

  public function import(Request $request)
  {
    $programs = [];
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = ProgramVendorConfiguration::where('buyer_id', $company->id)->pluck('program_id');

    $import = new BuyerInvoicesImport($programs->toArray(), $company, 'Factoring');
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
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    Excel::store(
      new InvoicesExport(
        $company,
        'buyer',
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
        $sort_by,
        ''
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
      return response()->download(public_path('buyer_upload_template.xlsx'), 'factoring-invoices.xlsx');
    }

    return response()->download(public_path('buyer_upload_template.xlsx'), 'factoring-invoices.xlsx');
  }

  public function downloadErrorReport()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $date = now()->format('Y-m-d');

    Excel::store(
      new InvoicesErrorReport($company, 'Factoring', 'anchor'),
      'Invoices_error_report_' . $date . '.xlsx',
      'exports'
    );

    return Storage::disk('exports')->download('Invoices_error_report_' . $date . '.xlsx');
  }

  public function uploaded()
  {
    return view('content.buyer.invoices.uploaded');
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
      ->activeBuyerFactoringCompany()
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
      new UploadedInvoices($current_company, $invoice_number, $status, $upload_date, 'Buyer Factoring'),
      'Uploaded_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Uploaded_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function dealerInvoices(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $finance_status = $request->query('finance_status');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoices = Invoice::dealerFinancing()
      ->with([
        'program.vendorDiscountDetails' => function ($query) use ($current_company) {
          $query->where('company_id', $current_company->company_id);
        },
        'program.vendorConfigurations' => function ($query) use ($current_company) {
          $query->where('company_id', $current_company->company_id);
        },
        'program.vendorFeeDetails' => function ($query) use ($current_company) {
          $query->where('company_id', $current_company->company_id);
        },
        'program.vendorBankDetails' => function ($query) use ($current_company) {
          $query->where('company_id', $current_company->company_id);
        },
        'program.vendorContactDetails' => function ($query) use ($current_company) {
          $query->where('company_id', $current_company->company_id);
        },
        'invoiceItems',
        'invoiceFees',
        'invoiceTaxes',
        'paymentRequests.paymentAccounts',
        'program.anchor',
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
      ->when($status && count($status) > 0, function ($query) use ($status) {
        $query->whereIn('stage', $status);
      })
      ->when($finance_status && count($finance_status) > 0, function ($query) use ($finance_status) {
        $query->whereIn('financing_status', $finance_status);
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
      ->orderBy('updated_at', 'DESC')
      ->paginate($per_page);

    $invoices = InvoiceResource::collection($invoices)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['invoices' => $invoices], 200);
    }
  }

  public function dealerExportInvoices(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $financing_status = $request->query('finance_status');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $from_invoice_date = $request->query('from_invoice_date');
    $to_invoice_date = $request->query('to_invoice_date');
    $pi_number = $request->query('pi_number');
    $paid_date = $request->query('paid_date');
    $sort_by = $request->query('sort_by');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $date = now()->format('Y-m-d');

    $company = Company::find($current_company->company_id);

    Excel::store(
      new InvoicesExport(
        $company,
        'dealer',
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

  public function show(Invoice $invoice)
  {
    sleep(2); // Allows for the loading animation to show and close
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

  public function dpdInvoices()
  {
    return view('content.dealer.invoices.dpd-list');
  }

  public function dpdInvoicesData(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $range = $request->query('range');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoices = Invoice::dealerFinancing()
      ->with(['invoiceItems', 'invoiceFees', 'invoiceTaxes', 'paymentRequests.paymentAccounts', 'program.anchor'])
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->where('company_id', $current_company->company_id)
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
    return view('content.dealer.invoices.rejected');
  }

  public function rejectedInvoicesData(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $anchor = $request->query('anchor');
    $range = $request->query('range');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoices = Invoice::dealerFinancing()
      ->with(['invoiceItems', 'invoiceFees', 'invoiceTaxes', 'paymentRequests', 'program.anchor'])
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('program', function ($query) use ($anchor) {
          $query->whereHas('anchor', function ($query) use ($anchor) {
            $query->where('name', 'LIKE', '%' . $anchor . '%');
          });
        });
      })
      ->where('company_id', $current_company->company_id)
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

  public function dealerPendingInvoices()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoices = Invoice::dealerFinancing()
      ->where('company_id', $current_company->company_id)
      ->orderBy('created_at', 'DESC')
      ->count();

    $pending_invoices = Invoice::dealerFinancing()
      ->where('company_id', $current_company->company_id)
      ->orderBy('created_at', 'DESC')
      ->whereIn('status', ['created', 'pending'])
      ->count();

    return view('content.dealer.invoices.index', compact('invoices', 'pending_invoices'));
  }

  public function paymentInstructions()
  {
    return view('content.dealer.invoices.payment-instructions');
  }

  public function programs(Company $company)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();
    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $vendor_programs = ProgramCompanyRole::where([
      'role_id' => $dealer_role->id,
      'company_id' => $current_company->company_id,
    ])
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', 'Dealer Financing');
        });
      })
      ->pluck('program_id');

    $filtered_programs = ProgramCompanyRole::whereIn('program_id', $vendor_programs)
      ->where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::whereIn('program_id', $filtered_programs)
      ->where('company_id', $current_company->company_id)
      ->get();

    return response()->json(['programs' => $programs]);
  }

  public function program(Program $program)
  {
    $program->load('discountDetails', 'dealerDiscountRates');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $credit_accounts = ProgramBankDetails::where('program_id', $program->id)->get();
    $dealer_discount_rates = ProgramVendorDiscount::where('company_id', $current_company->company_id)
      ->where('program_id', $program->id)
      ->get();

    $vendor_configuration = ProgramVendorConfiguration::where('company_id', $current_company->company_id)
      ->where('program_id', $program->id)
      ->select('eligibility', 'withholding_tax', 'withholding_vat', 'program_id', 'company_id')
      ->first();

    return response()->json(
      [
        'program' => $program,
        'credit_accounts' => $credit_accounts,
        'dealer_discount_rates' => $dealer_discount_rates,
        'vendor_configuration' => $vendor_configuration,
      ],
      200
    );
  }

  public function initiateDrawdown(Invoice $invoice = null)
  {
    $anchors = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $program_ids = ProgramCompanyRole::where('company_id', $company->id)
      ->where('role_id', $dealer_role->id)
      ->pluck('program_id');

    $programs = Program::whereHas('programType', fn($query) => $query->where('name', 'Dealer Financing'))
      ->whereIn('id', $program_ids)
      ->whereDate('limit_expiry_date', '>=', now()->format('Y-m-d'))
      ->get();

    // Get Anchors
    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $company->id)
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->where('is_blocked', false)
      ->get();

    foreach ($vendor_configurations as $vendor_configuration) {
      array_push($anchors, $vendor_configuration->program->anchor);
    }

    $anchors = collect($anchors)->unique();

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

    $credit_accounts = null;
    $credit_to = null;
    $vendor_configuration = null;
    if ($invoice) {
      $invoice->load('program.discountDetails', 'program.dealerDiscountRates');
      $credit_accounts = ProgramBankDetails::where('program_id', $invoice->program->id)->get();
      if ($invoice->paymentRequests->count() > 0) {
        $credit_to = $invoice->paymentRequests?->first()->paymentAccounts?->first()->account;
      }
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', $current_company->company_id)
        ->where('program_id', $invoice->program->id)
        ->select('eligibility', 'withholding_tax', 'withholding_vat', 'id', 'program_id', 'company_id')
        ->where('is_blocked', false)
        ->first();
    }

    return view(
      'content.dealer.invoices.initiate-drawdown',
      compact('anchors', 'currencies', 'invoice', 'credit_accounts', 'credit_to', 'vendor_configuration')
    );
  }

  public function storeDrawdownInvoice(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $request->validate(
      [
        'invoice_amount_in_words' => ['required', 'not_in:Not a Number, Too Big'],
        'drawdown_amount_in_words' => ['required', 'not_in:Not a Number, Too Big'],
        'invoice_number' => ['required'],
        'invoice_amount' => ['required'],
        'drawdown_amount' => ['required'],
        'program_id' => ['required'],
        'credit_to' => ['required'],
      ],
      [
        'program_id.required' => ['Select the OD Account'],
        'invoice_amount_in_words.not_in' => ['Enter a valid invoice amount'],
        'drawdown_amount_in_words.not_in' => ['Enter a valid drawdown amount'],
      ]
    );

    if (str_replace(',', '', $request->drawdown_amount) > str_replace(',', '', $request->invoice_amount)) {
      toastr()->error('', 'Drawdown amount cannot be greater than invoice amount.');

      return back()->withInput();
    }

    if ($company->status == 'inactive') {
      toastr()->error('', 'Company has been deactivated. Contact bank for assistance.');

      return back();
    }

    if ($company->is_blocked) {
      toastr()->error('', 'Company has been locked from making requests. Contact bank for assistance.');

      return back();
    }

    $vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
      ->where('program_id', $request->program_id)
      ->first();

    if (session()->has('program-expired')) {
      session()->forget('program-expired');
    }

    if ($vendor_configuration && Carbon::parse($vendor_configuration->limit_expiry_date)->lessThan(now())) {
      toastr()->error(
        '',
        'Program Limit expired on ' .
          Carbon::parse($vendor_configuration->limit_expiry_date)->format('d M Y') .
          '. Contact YoFinvoice for assistance'
      );
      session()->put(
        'program-expired',
        'Oops! Your request could not be processed because the Program Limit expired on ' .
          Carbon::parse($vendor_configuration->limit_expiry_date)->format('d M Y') .
          '. Contact YoFinvoice for assistance'
      );
      return back();
    }

    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $program = ProgramCompanyRole::where('program_id', $request->program_id)
      ->where('role_id', $dealer_role->id)
      ->where('company_id', $company->id)
      ->first();

    $invoice = Invoice::where('invoice_number', $request->invoice_number)
      ->where('company_id', $company->id)
      ->first();

    if ($invoice) {
      $invoice->update([
        'payment_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
        'due_date' => $request->due_date,
        'drawdown_amount' => Str::replace(',', '', $request->drawdown_amount),
      ]);

      if ($invoice->total_invoice_fees <= 0) {
        if ($vendor_configuration->withholding_tax > 0) {
          // Add Withholding Tax
          InvoiceFee::create([
            'invoice_id' => $invoice->id,
            'name' => 'Withholding Tax',
            'amount' => ($vendor_configuration->withholding_tax / 100) * $invoice->invoice_total_amount,
          ]);
        }

        if ($vendor_configuration->withholding_vat > 0) {
          // Add Withholding VAT
          InvoiceFee::create([
            'invoice_id' => $invoice->id,
            'name' => 'Withholding VAT',
            'amount' => ($vendor_configuration->withholding_vat / 100) * $invoice->invoice_total_amount,
          ]);
        }
      }
    }

    if ($invoice && $invoice->paymentRequests->count() > 0) {
      toastr()->info('', 'Invoice has a payment request already');
      return redirect()->route('dealer.invoices.index');
    }

    // Check if company has overdue invoices
    if ($company->hasOverdueInvoices()) {
      // Get limit block overdue days configuration from program
      $limit_block_overdue_days = $program->program->vendorDiscountDetails->where('company_id', $company->id)->first()
        ->limit_block_overdue_days;

      // Get invoices that are past block overdue days
      $invoices = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->where('financing_status', 'disbursed')
        ->get()
        ->filter(function ($value) use ($limit_block_overdue_days) {
          return Carbon::parse($value->due_date)->diffInDays(now()->format('Y-m-d')) > $limit_block_overdue_days;
        })
        ->count();

      if ($invoices > 0) {
        toastr()->error('', 'You have overdue invoices that require payment.');
        return redirect()->route('dealer.invoices.index');
      }
    }

    if (!$invoice) {
      $invoice = Invoice::create([
        'program_id' => $program->program_id,
        'company_id' => $company->id,
        'invoice_number' => $request->invoice_number,
        'invoice_date' => Carbon::parse($request->invoice_date)->format('Y-m-d'),
        'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        'payment_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
        'total_amount' => Str::replace(',', '', $request->invoice_amount),
        'drawdown_amount' => Str::replace(',', '', $request->drawdown_amount),
        'currency' => $request->currency,
        'remarks' => $request->remarks,
        'status' => 'approved',
        'stage' => 'approved',
      ]);

      if ($vendor_configuration->withholding_tax > 0) {
        // Add Withholding Tax
        InvoiceFee::firstOrCreate(
          [
            'invoice_id' => $invoice->id,
            'name' => 'Withholding Tax',
          ],
          [
            'amount' =>
              ($vendor_configuration->withholding_tax / 100) *
              ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount),
          ]
        );
      }

      if ($vendor_configuration->withholding_vat > 0) {
        // Add Withholding VAT
        InvoiceFee::firstOrCreate(
          [
            'invoice_id' => $invoice->id,
            'name' => 'Withholding VAT',
          ],
          [
            'amount' =>
              ($vendor_configuration->withholding_vat / 100) *
              ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount),
          ]
        );
      }
    }

    $program = Program::find($invoice->program_id);

    // Check if program is active
    if ($program->account_status == 'suspended') {
      // Notify user
      toastr()
        ->error('Program is currently unavailable to make requests')
        ->info('Contact bank for assistance.');

      return back();
    }

    // Check if company can make the request on the program
    if (
      $vendor_configuration->is_blocked ||
      !$vendor_configuration->is_approved ||
      $vendor_configuration->status == 'inactive'
    ) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToUnblock', ['company_id' => $company->id])->afterResponse();
      }

      toastr()
        ->info('Contact bank for assistance.')
        ->error('Your account has been deactivated on the program.');

      return back();
    }

    $utilized_amount = $program->utilized_amount;

    $vendor_program_limit = $company->getProgramLimit($program);
    $vendor_utilized_amount = $company->utilizedAmount($program);
    $pipeline_requests = $company->pipelineAmount($program);
    $available_limit = $vendor_configuration->sanctioned_limit - ($vendor_utilized_amount + $pipeline_requests);

    // Check if request will exceed vendor program limit
    if ($vendor_utilized_amount + $invoice->drawdown_amount > $vendor_program_limit) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $vendor_configuration->sanctioned_limit,
          'current_exposure' => $vendor_utilized_amount,
          'pipeline_requests' => $pipeline_requests,
          'available_limit' => $available_limit,
        ])->afterResponse();
      }

      toastr()
        ->info('Contact bank for assistance.')
        ->error('Vendor Limit exceeded.');

      return redirect()->route('dealer.invoices.index');
    }

    // Get Retain Limit as set in Bank Configuration
    $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
      ->where('product_type_id', $invoice->program->program_type_id)
      ->where('name', 'retain limit')
      ->first();

    if ($bank_configurations->value > 0) {
      $retain_amount = ($bank_configurations->value / 100) * $vendor_configuration->sanctioned_limit;
      $remainder = $vendor_configuration->sanctioned_limit - $retain_amount;
      $potential_utilization_amount = $utilized_amount + $pipeline_requests + $invoice->invoice_total_amount;
      if ($potential_utilization_amount > $remainder) {
        toastr()
          ->info('Contact bank for assistance.')
          ->error('Vendor Limit exceeded.');

        return redirect()->route('dealer.invoices.index');
      }
    }

    // Check if request will exceed drawing power
    if ($vendor_configuration->drawing_power > 0) {
      if ($invoice->invoice_total_amount > $vendor_configuration->drawing_power) {
        // Notify bank of request to unblock
        foreach ($company->bank->users as $user) {
          SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
            'company_id' => $company->id,
            'approved_limit' => $vendor_configuration->sanctioned_limit,
            'current_exposure' => $vendor_utilized_amount,
            'pipeline_requests' => $pipeline_requests,
            'available_limit' => $available_limit,
          ])->afterResponse();
        }

        toastr()
          ->info('Contact bank for assistance.')
          ->error('Drawing Power exceeded.');

        return redirect()->route('dealer.invoices.index');
      }
    }

    // Check if request will exceed program limit
    if ($utilized_amount + $invoice->drawdown_amount > $program->program_limit) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $vendor_configuration->sanctioned_limit,
          'current_exposure' => $vendor_utilized_amount,
          'pipeline_requests' => $pipeline_requests,
          'available_limit' => $available_limit,
        ])->afterResponse();
      }

      toastr()
        ->info('Contact bank for assistance.')
        ->error('Program Limit exceeded.');

      return redirect()->route('dealer.invoices.index');
    }

    $invoice->update([
      'pi_number' => 'PI_' . $invoice->id,
    ]);

    if ($request->has('invoice') && !empty($request->invoice)) {
      foreach ($request->input('invoice', []) as $file) {
        $invoice
          ->addMedia(storage_path('app/public/tmp/uploads/' . $file))
          ->withCustomProperties(['user_type' => Company::class, 'user_name' => auth()->user()->name])
          ->toMediaCollection('invoice');
      }
    }

    $vendor_bank_details = ProgramBankDetails::find($request->credit_to);

    $invoice->update([
      'credit_to' => $vendor_bank_details->account_number,
    ]);

    $discount_type = $invoice->program->discountDetails->first()?->discount_type;
    $fee_type = $invoice->program->discountDetails->first()?->fee_type;

    if (!$discount_type) {
      $discount_type = Invoice::FRONT_ENDED;
    }

    if (!$fee_type) {
      $fee_type = Invoice::FRONT_ENDED;
    }

    // Get difference in days between anchor payment and repayment date
    $diff = Carbon::parse($invoice->payment_date)->diffInDays(Carbon::parse($invoice->due_date));

    // Get vendor discount details
    $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->where('from_day', '<=', $diff)
      ->where('to_day', '>=', $diff)
      ->latest()
      ->first();

    if (!$vendor_discount_details) {
      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company_id)
        ->where('program_id', $invoice->program_id)
        ->latest()
        ->first();
    }

    // Get fees for vendor
    $vendor_fees = ProgramVendorFee::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->get();
    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::where('program_id', $invoice->program_id)->first()?->tax_on_discount;

    $eligibility = $vendor_configuration->eligibility;
    $total_amount = $invoice->drawdown_amount;

    $total_roi = $vendor_discount_details ? $vendor_discount_details->total_roi : 0;
    $legible_amount = ($eligibility / 100) * $total_amount;

    // Fee charges
    $fees_amount = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type == 'amount') {
          $fees_amount += $fee->value;
        }
        if ($fee->type == 'percentage') {
          $fees_amount += ($fee->value / 100) * $legible_amount;
        }
        if ($fee->type == 'per amount') {
          $amounts = floor($legible_amount / $fee->per_amount);
          $fees_amount += $amounts * $fee->value;
        }
        if ($fee->taxes) {
          $fees_tax_amount += ($fee->taxes / 100) * $fees_amount;
        }
      }
    }

    $discount =
      // ($eligibility / 100) *
      Str::replace(',', '', $request->drawdown_amount) *
      ($total_roi / 100) *
      (Carbon::parse($request->payment_date)->diffInDays(Carbon::parse($invoice->due_date)) / 365);

    // Tax on discount
    $discount_tax_amount = 0;
    if ($discount > 0 && $tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $discount;
    }

    if ($invoice->drawdown_amount) {
      $amount = $invoice->drawdown_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
    } else {
      $amount = $invoice->total_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
    }

    $vendor_amount = $amount;

    if ($discount_type == Invoice::REAR_ENDED) {
      $vendor_amount = $amount + $discount + $discount_tax_amount;
    }

    if ($fee_type == Invoice::REAR_ENDED) {
      $vendor_amount = $vendor_amount + $fees_tax_amount + $fees_amount;
    }

    $reference_number = '';

    $words = explode(' ', $invoice->company->name);
    $acronym = '';

    foreach ($words as $w) {
      $acronym .= mb_substr($w, 0, 1);
    }

    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $reference_number = 'VFR' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
      } else {
        $reference_number = 'FR' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
      }
    } else {
      $reference_number = 'DF' . $invoice->program->bank_id . '' . $acronym . '000' . $invoice->id;
    }

    $payment_request = PaymentRequest::create([
      'reference_number' => $reference_number,
      'invoice_id' => $invoice->id,
      'amount' => round($vendor_amount, 2),
      'processing_fee' => round($fees_amount, 2),
      'payment_request_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
      'created_by' => auth()->id(),
    ]);

    $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

    $discount_income_bank_account = null;
    $fees_income_bank_account = null;
    $tax_income_bank_account = null;

    // Get Bank Configured Receivable Accounts
    $discount_income_bank_account = BankProductsConfiguration::where(
      'bank_id',
      $payment_request->invoice->program->bank->id
    )
      ->where('product_type_id', $dealer_financing->id)
      ->where('product_code_id', null)
      ->where('name', 'Discount Income Account')
      ->first();
    $unrealized_discount_bank_account = BankProductsConfiguration::where(
      'bank_id',
      $payment_request->invoice->program->bank->id
    )
      ->where('product_type_id', $dealer_financing->id)
      ->where('product_code_id', null)
      ->where('name', 'Unrealised Discount Account')
      ->first();
    $fees_income_bank_account = BankProductsConfiguration::where(
      'bank_id',
      $payment_request->invoice->program->bank->id
    )
      ->where('product_type_id', $dealer_financing->id)
      ->where('product_code_id', null)
      ->where('name', 'Fee Income Account')
      ->first();
    $tax_income_bank_account = BankProductsConfiguration::where('bank_id', $payment_request->invoice->program->bank->id)
      ->where('product_type_id', $dealer_financing->id)
      ->where('product_code_id', null)
      ->where('name', 'Tax Account Number')
      ->first();

    // Credit to vendor's account
    $payment_request->paymentAccounts()->create([
      'account' => $vendor_bank_details->account_number,
      'account_name' => $vendor_bank_details->name_as_per_bank,
      'amount' => round($vendor_amount, 2),
      'type' => 'vendor_account',
      'description' => 'Vendor Account',
    ]);

    // Credit discount to discount account
    $payment_request->paymentAccounts()->create([
      'account' =>
        $discount_type == Invoice::FRONT_ENDED
          ? ($discount_income_bank_account
            ? $discount_income_bank_account->value
            : 'Adv_Disc_Inc_Acc')
          : $unrealized_discount_bank_account->value,
      'account_name' =>
        $discount_type == Invoice::FRONT_ENDED
          ? ($discount_income_bank_account
            ? $discount_income_bank_account->name
            : 'Advanced Discount Account')
          : $unrealized_discount_bank_account->name,
      'amount' => round($discount, 2),
      'type' => 'discount',
      'description' => Invoice::DEALER_DISCOUNT_BEARING,
    ]);

    // Credit Fees to Fees Income Account
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type == 'amount') {
          $fees_amount = $fee->value;
          if ($fees_amount > 0) {
            $payment_request->paymentAccounts()->create([
              'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
              'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
              'amount' => round($fees_amount, 2),
              'type' => 'program_fees',
              'title' => $fee->fee_name,
              'description' => 'Fees for ' . $fee->fee_name,
            ]);
          }
        }
        if ($fee->type == 'percentage') {
          $fees_amount = ($fee->value / 100) * $legible_amount;
          if ($fees_amount > 0) {
            $payment_request->paymentAccounts()->create([
              'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
              'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
              'amount' => round($fees_amount, 2),
              'type' => 'program_fees',
              'title' => $fee->fee_name,
              'description' => 'Fees for ' . $fee->fee_name,
            ]);
          }
        }
        if ($fee->type == 'per amount') {
          $amounts = floor($legible_amount / $fee->per_amount);
          $fees_amount = $amounts * $fee->value;
          if ($fees_amount > 0) {
            $payment_request->paymentAccounts()->create([
              'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
              'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
              'amount' => round($fees_amount, 2),
              'type' => 'program_fees',
              'title' => $fee->fee_name,
              'description' => 'Fees for ' . $fee->fee_name,
            ]);
          }
        }
      }
    }

    // Credit Fee Taxes to Fees taxes Account
    $fees_amount = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type == 'amount') {
          $fees_amount += $fee->value;
        }
        if ($fee->type == 'percentage') {
          $fees_amount += ($fee->value / 100) * $legible_amount;
        }
        if ($fee->type == 'per amount') {
          $amounts = floor($legible_amount / $fee->per_amount);
          $fees_amount += $amounts * $fee->value;
        }

        if ($fee->taxes) {
          $fees_tax_amount += ($fee->taxes / 100) * $fees_amount;

          $tax_income_account = BankTaxRate::where('bank_id', $invoice->program->bank_id)
            ->where('value', $fee->taxes)
            ->where('status', 'active')
            ->first();

          if ($tax_income_account) {
            $payment_request->paymentAccounts()->create([
              'account' => $tax_income_account->account_no,
              'account_name' => 'Tax Income Bank Account',
              'amount' => round($fees_tax_amount, 2),
              'type' => 'tax_on_fees',
              'description' => 'Tax on Fees for ' . $fee->fee_name,
            ]);
          } else {
            $payment_request->paymentAccounts()->create([
              'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
              'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Account',
              'amount' => round($fees_tax_amount, 2),
              'type' => 'tax_on_fees',
              'description' => 'Tax on Fees for ' . $fee->fee_name,
            ]);
          }
        }
      }
    }

    // Credit to tax on discount account
    if ($discount_tax_amount > 0) {
      if ($tax_income_bank_account) {
        $payment_request->paymentAccounts()->create([
          'account' => $tax_income_bank_account->value,
          'account_name' => $tax_income_bank_account->name,
          'amount' => round($discount_tax_amount, 2),
          'type' => 'tax_on_discount',
          'description' => 'Tax on Discount',
        ]);
      } else {
        $payment_request->paymentAccounts()->create([
          'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fees Income Bank Account',
          'account_name' => $fees_income_bank_account ? $fees_income_bank_account->name : 'Fees Income Bank Account',
          'amount' => round($discount_tax_amount, 2),
          'type' => 'tax_on_discount',
          'description' => 'Tax on Discount',
        ]);
      }
    }

    // Check if requires checker approval
    $purchase_order_setting = $company->purchaseOrderSetting;

    // Days before penal
    $days_before_penal = BankGeneralProductConfiguration::where('bank_id', $invoice->program->bank_id)
      ->where('name', 'days before penal')
      ->where('product_type_id', $invoice->program->program_type_id)
      ->first();

    $invoice->update([
      'eligible_for_financing' => false,
      'financing_status' => 'submitted',
      'calculated_total_amount' => $invoice->drawdown_amount,
      'eligibility' => $vendor_configuration->eligibility,
      'discount_charge_type' => $discount_type,
      'fee_charge_type' => $fee_type,
      'penal_rate' => $vendor_discount_details->penal_discount_on_principle,
      'grace_period' => $vendor_discount_details->grace_period,
      'grace_period_discount' => $vendor_discount_details->grace_period_discount,
      'days_before_penal' => $days_before_penal ? $days_before_penal->value : 0,
      'discount_rate' => $vendor_configuration->total_toi,
    ]);

    // Update Program and Company Pipeline and Utilized Amounts
    $invoice->company->increment('pipeline_amount', $invoice->drawdown_amount);

    $invoice->program->increment('pipeline_amount', $invoice->drawdown_amount);

    $vendor_configuration->increment('pipeline_amount', $invoice->drawdown_amount);

    if ($purchase_order_setting->request_finance_add_repayment) {
      ModelFinanceRequestApproval::create([
        'payment_request_id' => $payment_request->id,
        'user_id' => auth()->id(),
      ]);

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Initiated drawdown successfully');

      return redirect()->route('dealer.invoices.index');
    } else {
      // Check if auto approve finance requests is enabled
      if ($vendor_configuration->auto_approve_finance) {
        $payment_request->update([
          'status' => 'approved',
        ]);

        // Create CBS Transactions for the payment request
        $payment_request->createCbsTransactions();
      }

      activity($invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($payment_request)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Dealer'])
        ->log('initiated drawdown');

      if ($invoice->status == 'approved') {
        $invoice->update([
          'calculated_total_amount' => $invoice->drawdown_amount,
        ]);
      }

      $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
        ->where('status', 'active')
        ->where('bank_id', $invoice->program->bank_id)
        ->first();

      if (!$noa_text) {
        $noa_text = NoaTemplate::where('product_type', 'generic')
          ->where('status', 'active')
          ->first();
      }

      // Send NOA
      $data = [];
      $data['{date}'] = Carbon::parse($invoice->invoice_date)->format('d M Y');
      $data['{buyerName}'] = $invoice->company->name;
      $data['{anchorName}'] = $invoice->program->anchor->name;
      $data['{company}'] = $invoice->company->name;
      $data['{anchorCompanyUniqueID}'] = $invoice->program->anchor->unique_identification_number;
      $data['{time}'] = now()->format('d M Y');
      $data['{agreementDate}'] = now()->format('d M Y');
      $data['{contract}'] = '';
      $data['{anchorAccountName}'] = $invoice->program->bankDetails->first()->account_name;
      $data['{anchorAccountNumber}'] = $invoice->program->bankDetails->first()->account_number;
      $data['{anchorCustomerId}'] = '';
      $data['{anchorBranch}'] = $invoice->program->anchor->branch_code;
      $data['{anchorIFSCCode}'] = '';
      $data['{anchorAddress}'] =
        $invoice->program->anchor->postal_code .
        ' ' .
        $invoice->program->anchor->address .
        ' ' .
        $invoice->program->anchor->city .
        ' ';
      $data['{penalnterestRate}'] = $vendor_discount_details?->penal_discount_on_principle;
      $data['{sellerName}'] = $invoice->company->name;

      $noa = '';

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

        SendMail::dispatch($bank_user->email, 'PaymentRequested', [
          'payment_request_id' => $payment_request->id,
          'link' => config('app.url') . '/' . $payment_request->invoice->program->bank->url,
          'type' => 'dealer_financing',
          'noa' => $noa_text != null ? $pdf->output() : null,
        ])->afterResponse();
      }

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Initiated drawdown successfully');

      return redirect()->route('dealer.invoices.index');
    }
  }

  public function sendInvoiceForApproval(Invoice $invoice)
  {
    $invoice->update([
      'status' => 'submitted',
      'stage' => 'pending_maker',
    ]);

    if (!request()->wantsJson()) {
      toastr()->success('', 'Invoice sent to anchor successfully');

      return back();
    }

    return response()->json(['message' => 'Invoice submitted successfully', 'invoice' => $invoice], 200);
  }

  public function checkInvoiceNumber($number)
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoice = Invoice::where('invoice_number', $number)
      ->where('company_id', $current_company->company_id)
      ->first();

    if ($invoice) {
      return response()->json(['exists' => true], 400);
    }

    return response()->json(['exists' => false], 200);
  }

  public function planner()
  {
    $anchors = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    // Get Anchors
    foreach ($company->programs as $program) {
      if ($program->programType->name == 'Dealer Financing') {
        array_push($anchors, $program->getAnchor());
      }
    }

    $anchors = collect($anchors)->unique();

    $anchor_role = ProgramRole::where('name', 'dealer')->first();

    $program_ids = ProgramCompanyRole::whereHas('program', function ($query) {
      $query->whereHas('programType', function ($query) {
        $query->where('name', 'Dealer Financing');
      });
    })
      ->where('role_id', $anchor_role->id)
      ->where('company_id', $company->id)
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::with('program.anchor')
      ->whereIn('program_id', $program_ids)
      ->where('company_id', $company->id)
      ->get();

    $vendor_discount_details = ProgramVendorDiscount::where('company_id', $company->id)
      ->whereIn('program_id', $program_ids)
      ->latest('to_day')
      ->first();

    $max_day = $vendor_discount_details->to_day;

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

    return view(
      'content.dealer.planner.index',
      compact('anchors', 'programs', 'company', 'off_days', 'holidays', 'max_day')
    );
  }

  public function plannerCalculate(Request $request)
  {
    $program_id = $request->query('program');
    $amount = $request->query('amount');
    $invoice_date = $request->query('invoice_date');
    $due_date = $request->query('due_date');

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendor_configurations = ProgramVendorConfiguration::find($program_id);

    $program = Program::find($vendor_configurations->program_id);

    $diff = Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date));

    $vendor_discount_details = ProgramVendorDiscount::where('company_id', $vendor_configurations->company_id)
      ->where('program_id', $program->id)
      ->where('to_day', '>=', $diff)
      ->latest()
      ->first();

    $vendor_fees = ProgramVendorFee::where('program_id', $program->id)
      ->where('company_id', $vendor_configurations->company_id)
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

    $invoices_in_processing = InvoiceProcessing::where('company_id', $company->id)->pluck('invoice_id');

    $invoice = Invoice::where('status', 'approved')
      ->where('company_id', $company->id)
      ->where('program_id', $program->id)
      ->whereNotIn('id', $invoices_in_processing)
      ->orderBy('due_date', 'DESC')
      ->first();

    $total_discount = 0;
    // Discount Calculation
    if ($total_roi > 0) {
      if ($invoice_date && $due_date) {
        $total_discount =
          $total_roi *
          ($eligibility / 100) *
          $amount *
          ($total_roi / 100) *
          (Carbon::parse($invoice_date)->diffInDays(Carbon::parse($due_date)) / 365);
      } else {
        $total_discount =
          $total_roi *
          ($eligibility / 100) *
          $amount *
          ($total_roi / 100) *
          ((now()->diffInDays(Carbon::parse($invoice->due_date)) + 1) / 365);
      }
    }

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $total_discount;
    }

    $total_actual_remittance =
      $legible_amount - $fees_amount - $fees_tax_amount - $total_discount - $discount_tax_amount + $vendor_bearing_fees;

    return [round($total_discount, 2), round($total_actual_remittance, 2)];
  }

  public function eligibleInvoices(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $per_page = $request->query('per_page');

    $invoices = Invoice::dealerFinancing()
      ->with('program', 'paymentRequests.paymentAccounts')
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
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
      ->where('eligible_for_financing', true)
      ->orderBy('due_date', 'DESC')
      ->paginate($per_page);

    $minimum_financing_days = 0;
    $highest_due_date = now();

    if ($invoices->count() > 0) {
      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoices->first()->company_id)
        ->where('program_id', $invoices->first()->program_id)
        ->select('to_day')
        ->latest('to_day')
        ->first();
      $minimum_financing_days = $vendor_discount_details->to_day;
      $highest_due_date = $invoices->first()->due_date;
    }

    foreach ($invoices as $invoice) {
      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company_id)
        ->where('program_id', $invoice->program_id)
        ->select('to_day')
        ->latest()
        ->first();
      // Get the program with the highest to day financing days
      if ($vendor_discount_details->to_day > $minimum_financing_days) {
        $minimum_financing_days = $vendor_discount_details->to_day;
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
    $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
      ->where('status', 'active')
      ->where('bank_id', $company->bank_id)
      ->first();

    if (!$noa_text) {
      $noa_text = NoaTemplate::where('product_type', 'generic')
        ->where('status', 'active')
        ->first();
    }

    $terms_text = TermsConditionsConfig::where('product_type', 'dealer_financing')
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

    if (request()->wantsJson()) {
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
  }

  public function nonEligibleInvoices(Request $request)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $per_page = $request->query('per_page');

    $invoices = Invoice::dealerFinancing()
      ->with('program', 'paymentRequests.paymentAccounts')
      ->where('company_id', $company->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
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
      ->where('eligible_for_financing', false)
      ->latest()
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(
        [
          'invoices' => InvoiceResource::collection($invoices)
            ->response()
            ->getData(),
        ],
        200
      );
    }
  }

  public function remittanceAmountDetails(Request $request)
  {
    $request->validate([
      'invoices' => ['required'],
      'date' => ['required'],
    ]);

    $total_amount = 0;

    foreach ($request->invoices as $invoice) {
      $invoice = Invoice::find($invoice['id']);
      $total_amount += $invoice->invoice_total_amount;
    }

    return response()->json([
      'total_amount' => $total_amount,
    ]);
  }

  public function requestFinance(Request $request)
  {
    $request->validate(
      [
        'invoice_id' => ['required'],
        'drawdown_amount' => ['required'],
        'payment_date' => ['required'],
      ],
      [
        'invoice_id.required' => ['Select the Invoice'],
        'payment_date.required' => ['Select the Payment Date'],
      ]
    );

    $invoice = Invoice::find($request->invoice_id);

    if (!$invoice) {
      return response()->json(['message' => 'Invalid Invoice'], 404);
    }

    if ($invoice && $invoice->paymentRequests->count() > 0) {
      $invoice->update([
        'eligible_for_financing' => false,
      ]);

      return response()->json(['message' => 'Invoice has already been requested'], 422);
    }

    if (str_replace(',', '', $request->drawdown_amount) > str_replace(',', '', $invoice->invoice_total_amount)) {
      if ($request->wantsJson()) {
        return response()->json(['message' => 'The requested amount is greater than the invoice amount'], 422);
      }

      toastr()->error('', 'Drawdown amount cannot be greater than invoice amount.');

      return back()->withInput();
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if ($company->status == 'inactive') {
      toastr()->error('', 'Company has been deactivated. Contact bank for assistance.');

      return back();
    }

    if ($company->is_blocked) {
      toastr()->error('', 'Company has been locked from making requests. Contact bank for assistance.');

      return back();
    }

    $vendor_configuration = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->first();

    if ($vendor_configuration && Carbon::parse($vendor_configuration->limit_expiry_date)->lessThan(now())) {
      if ($request->wantsJson()) {
        return response()->json(
          [
            'message' =>
              'Program Limit expired on ' .
              Carbon::parse($vendor_configuration->limit_expiry_date)->format('d M Y') .
              '. Contact YoFinvoice for assistance',
          ],
          422
        );
      }

      toastr()->error(
        '',
        'Program Limit expired on ' .
          Carbon::parse($vendor_configuration->limit_expiry_date)->format('d M Y') .
          '. Contact YoFinvoice for assistance'
      );
      return back();
    }

    $dealer_role = ProgramRole::where('name', 'dealer')->first();

    $program = ProgramCompanyRole::where('program_id', $invoice->program_id)
      ->where('role_id', $dealer_role->id)
      ->where('company_id', $company->id)
      ->first();

    if ($invoice) {
      $invoice->update([
        'payment_date' => now()->format('Y-m-d'),
        'due_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
        'drawdown_amount' => Str::replace(',', '', $request->drawdown_amount),
      ]);
    }

    // Check if company has overdue invoices
    if ($company->hasOverdueInvoices()) {
      // Get limit block overdue days configuration from program
      $limit_block_overdue_days = $program->program->vendorDiscountDetails->where('company_id', $company->id)->first()
        ->limit_block_overdue_days;

      // Get invoices that are past block overdue days
      $invoices = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->where('financing_status', 'disbursed')
        ->get()
        ->filter(function ($value) use ($limit_block_overdue_days) {
          return Carbon::parse($value->due_date)->diffInDays(now()->format('Y-m-d')) > $limit_block_overdue_days;
        })
        ->count();

      if ($invoices > 0) {
        if ($request->wantsJson()) {
          return response()->json(
            [
              'message' =>
                'You have overdue invoices that require repayment. Complete the payments to make new requests.',
            ],
            422
          );
        }

        toastr()->error('', 'You have overdue invoices that require payment.');
        return redirect()->route('dealer.invoices.index');
      }
    }

    $program = Program::find($invoice->program_id);

    // Check if program is active
    if ($program->account_status == 'suspended') {
      if ($request->wantsJson()) {
        return response()->json(
          ['message' => 'Program is currently unavailable to make requests. Contact bank for assistance.'],
          422
        );
      }
      // Notify user
      toastr()
        ->error('Program is currently unavailable to make requests')
        ->info('Contact bank for assistance.');

      return back();
    }

    // Check if company can make the request on the program
    if (!$vendor_configuration->is_approved || $vendor_configuration->status == 'inactive') {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToUnblock', ['company_id' => $company->id])->afterResponse();
      }

      if ($request->wantsJson()) {
        return response()->json(
          ['message' => 'Your account has been deactivated on the program. Contact bank for assistance.'],
          422
        );
      }

      toastr()
        ->info('Contact bank for assistance.')
        ->error('Your account has been deactivated on the program.');

      return back();
    }

    $utilized_amount = $program->utilized_amount;

    $vendor_program_limit = $company->getProgramLimit($program);
    $vendor_utilized_amount = $company->utilizedAmount($program);
    $pipeline_requests = $company->pipelineAmount($program);
    $available_limit = $vendor_configuration->sanctioned_limit - ($vendor_utilized_amount + $pipeline_requests);

    // Check if request will exceed vendor program limit
    if ($vendor_utilized_amount + $invoice->drawdown_amount > $vendor_program_limit) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $vendor_configuration->sanctioned_limit,
          'current_exposure' => $vendor_utilized_amount,
          'pipeline_requests' => $pipeline_requests,
          'available_limit' => $available_limit,
        ])->afterResponse();
      }

      if ($request->wantsJson()) {
        return response()->json(['message' => 'Vendor Funding Limit exceeded.'], 422);
      }

      toastr()
        ->info('Contact bank for assistance.')
        ->error('Vendor Limit exceeded.');

      return redirect()->route('dealer.invoices.index');
    }

    // Check if request will exceed program limit
    if ($utilized_amount + $invoice->drawdown_amount > $program->program_limit) {
      // Notify bank of request to unblock
      foreach ($company->bank->users as $user) {
        SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $company->id,
          'approved_limit' => $vendor_configuration->sanctioned_limit,
          'current_exposure' => $vendor_utilized_amount,
          'pipeline_requests' => $pipeline_requests,
          'available_limit' => $available_limit,
        ])->afterResponse();
      }

      if ($request->wantsJson()) {
        return response()->json(['message' => 'Program Funding Limit exceeded.'], 422);
      }

      toastr()
        ->info('Contact bank for assistance.')
        ->error('Program Limit exceeded.');

      return redirect()->route('dealer.invoices.index');
    }

    $invoice->update([
      'pi_number' => 'PI_' . $invoice->id,
    ]);

    // $vendor_bank_details = ProgramBankDetails::where('program_id', $invoice->program_id)->first();

    InvoiceProcessing::create([
      'company_id' => $company->id,
      'invoice_id' => $invoice->id,
      'action' => 'requesting financing',
      'status' => 'pending',
      'data' => [
        'drawdown_amount' => $invoice->drawdown_amount ? $invoice->drawdown_amount : $invoice->invoice_total_amount,
        'payment_request_date' => $request->payment_date,
        'due_date' => $invoice->due_date,
      ],
    ]);

    BulkRequestFinancing::dispatchAfterResponse($company, Program::DEALER_FINANCING);

    // $invoice->requestDealerFinance(
    //   $vendor_configuration,
    //   Str::replace(',', '', $request->drawdown_amount),
    //   $vendor_bank_details,
    //   $request->payment_date,
    //   now()
    // );

    return response()->json(['message' => 'Payment Request sent successfully']);
  }

  public function requestMultipleFinance(Request $request)
  {
    ini_set('max_execution_time', 600);

    $request->validate([
      'invoices' => ['required'],
      'payment_request_date' => ['required', 'date'],
    ]);

    // Check if company can make the request
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
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

    $sum_amount = 0;
    $message = 'Requested amount exceeds available limit';

    Invoice::whereIn('id', $request->invoices)->chunk(100, function ($invoices) use (&$sum_amount) {
      foreach ($invoices as $invoice) {
        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
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
        $invoice_total_amount = $invoice->invoice_total_amount;

        $program = Program::find($invoice->program_id);

        // Check if program is active
        if ($program->account_status == 'suspended') {
          $message = 'Program is currently unavailable to make requests. Contact bank for assistance.';
          $can_request = false;
        }

        $vendor_configurations = ProgramVendorConfiguration::where('program_id', $invoice->program_id)
          ->where('company_id', $invoice->company_id)
          ->first();

        // Check if company can make the request on the program
        if (!$vendor_configurations->is_approved || $vendor_configurations->status == 'inactive') {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatch($user->email, 'RequestToUnblock', ['company_id' => $company->id])->afterResponse();
          }

          $message = 'Your account has been deactivated on the program. Contact bank for assistance.';
          $can_request = false;
        }

        // Check limits at OD Level
        $sanctioned_limit = $vendor_configurations->sanctioned_limit;
        $utilized_amount = $vendor_configurations->utilized_amount;
        $pipeline_amount = $vendor_configurations->pipeline_amount;

        $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
            ])->afterResponse();
          }
          $message = 'Requested amount exceeds available limit at OD level.';
          $can_request = false;
        }

        $program = Program::find($invoice->program_id);

        // Check at program level
        $program_limit = $program->program_limit;
        $utilized_amount = $program->utilized_amount;
        $pipeline_amount = $program->pipeline_amount;
        $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          // Notify bank of request to unblock
          foreach ($company->bank->users as $user) {
            SendMail::dispatch($user->email, 'RequestToIncreaseFundingLimit', [
              'company_id' => $company->id,
              'approved_limit' => $vendor_configurations->sanctioned_limit,
              'current_exposure' => $utilized_amount,
              'pipeline_requests' => $pipeline_amount,
              'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
            ])->afterResponse();
          }

          $message = 'Requested amount exceeds available limit at program level.';
          $can_request = false;
        }

        // Check if request exceeds company top level borrower limit
        $top_level_borrower_limit = $company->top_level_borrower_limit;
        $utilized_amount = $company->utilized_amount;
        $pipeline_amount = $company->pipeline_amount;
        $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $sum_amount;
        if ($available_limit <= 0) {
          $message = 'Requested amount exceeds available limit at company level.';
          $can_request = false;
        }
      }
    });

    if (!$can_request) {
      return response()->json(['message' => $message], 422);
    }

    Invoice::whereIn('id', $request->invoices)->chunk(50, function ($invoices) use ($company, $requested_payment_date) {
      foreach ($invoices as $invoice) {
        InvoiceProcessing::create([
          'company_id' => $company->id,
          'invoice_id' => $invoice->id,
          'action' => 'requesting financing',
          'status' => 'pending',
          'data' => [
            'drawdown_amount' => $invoice->drawdown_amount ? $invoice->drawdown_amount : $invoice->invoice_total_amount,
            'payment_request_date' => now(),
            'due_date' => $requested_payment_date,
          ],
        ]);
      }
    });

    BulkRequestFinancing::dispatchAfterResponse($company, Program::DEALER_FINANCING);

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Payment Request(s) sent successfully']);
    }

    toastr()->success('', 'Payment request successfully created.');

    return back();
  }

  public function dealerImport(Request $request)
  {
    $request->validate([
      'invoices' => ['required', 'mimes:xlsx'],
    ]);

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $import = new DealerFinancing($company);

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

  public function dealerDownloadErrorReport()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $date = now()->format('Y-m-d');

    Excel::store(
      new InvoicesErrorReport($company, 'Dealer Financing', 'dealer'),
      'Invoices_error_report_' . $date . '.xlsx',
      'exports'
    );

    return Storage::disk('exports')->download('Invoices_error_report_' . $date . '.xlsx');
  }

  public function dealerUploadedInvoices()
  {
    return view('content.dealer.invoices.uploaded');
  }

  public function dealerUploadedInvoicesData(Request $request)
  {
    $per_page = $request->query('per_page');
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('uploaded_date');
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $invoices = InvoiceUploadReport::where('product_type', Program::DEALER_FINANCING)
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

  public function dealerExportUploadedInvoices(Request $request)
  {
    $invoice_number = $request->query('invoice_number');
    $status = $request->query('status');
    $upload_date = $request->query('uploaded_date');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $date = now()->format('Y-m-d');

    Excel::store(
      new UploadedInvoices($current_company, $invoice_number, $upload_date, $status, 'Dealer Financing'),
      'Uploaded_Invoices_' . $date . '.csv',
      'exports'
    );

    return Storage::disk('exports')->download('Uploaded_Invoices_' . $date . '.csv', \Maatwebsite\Excel\Excel::CSV, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function financingRequests()
  {
    return view('content.dealer.planner.financing-requests');
  }

  public function financingRequestsData(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $finance_requests = PaymentRequest::with(['paymentAccounts', 'companyApprovals'])
      ->where(function ($query) {
        $query
          ->whereHas('cbsTransactions', function ($query) {
            $query->whereIn('transaction_type', ['Payment Disbursement']);
          })
          ->orWhereDoesntHave('cbsTransactions');
      })
      ->whereHas('invoice', function ($query) use ($current_company) {
        $query->where('company_id', $current_company->company_id)->whereHas('program', function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', 'Dealer Financing');
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
      ->when($invoice_number && $invoice_number != '', function ($query) use ($invoice_number) {
        $query->whereHas('invoice', function ($query) use ($invoice_number) {
          $query->where('invoice_number', 'LIKE', '%' . addcslashes($invoice_number, '\\') . '%');
        });
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->latest()
      ->paginate($per_page);

    $finance_requests = PaymentRequestResource::collection($finance_requests)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['finance_requests' => $finance_requests], 200);
    }

    return view('content.dealer.planner.financing-requests');
  }

  public function odRepayments()
  {
    return view('content.dealer.planner.repayments');
  }

  public function odRepaymentsData(Request $request)
  {
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoices = InvoiceResource::collection(
      Invoice::dealerFinancing()
        ->with('program.anchor', 'paymentRequests')
        ->where('company_id', $company->id)
        ->where('financing_status', 'closed')
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json([
      'data' => $invoices,
    ]);
  }

  public function odDetails()
  {
    return view('content.dealer.planner.od-details');
  }

  public function odAccountsData(Request $request)
  {
    $payment_account_number = $request->query('payment_account_number');
    $anchor = $request->query('anchor');
    $dealer = $request->query('dealer');
    $per_page = $request->query('per_page');

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $dealer_financing = ProgramType::where('name', 'Dealer Financing')->first()->id;

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', 'Dealer Financing');
        });
      })
        ->where('company_id', $current_company->company_id)
        ->when($payment_account_number && $payment_account_number != '', function ($query) use (
          $payment_account_number
        ) {
          $query->where('payment_account_number', 'LIKE', '%' . $payment_account_number . '%');
        })
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
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

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    if ($company->bank->adminConfiguration) {
      if ($company->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = explode(',', str_replace("\"", '', $company->bank->adminConfiguration->selectedCurrencyIds));
      } elseif ($company->bank->adminConfiguration->defaultCurrency) {
        $currency = [$company->bank->adminConfiguration->defaultCurrency];
      }
    }

    $currencies = Currency::whereIn('id', $currency)->get();

    return response()->json(['invoices' => $programs, 'currencies' => $currencies]);
  }

  public function odAccountDetails(ProgramVendorConfiguration $program_vendor_configuration)
  {
    $program_vendor_configuration->load('program.anchor');

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $pipeline_amount = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->whereHas('paymentRequests', function ($query) {
        $query->where('status', 'approved');
      })
      ->whereDate('due_date', '>', now())
      ->sum('drawdown_amount');

    $utilized_amount = Invoice::dealerFinancing()
      ->where('company_id', $company->id)
      ->where('financing_status', 'disbursed')
      ->whereDate('due_date', '>', now())
      ->sum('disbursed_amount');

    return view('content.dealer.planner.od-account-details', [
      'vendor_configuration' => $program_vendor_configuration,
      'pipeline_amount' => $pipeline_amount,
      'utilized_amount' => $utilized_amount,
    ]);
  }

  public function odAccountPayments(Request $request, ProgramVendorConfiguration $program_vendor_configuration)
  {
    $per_page = $request->query('per_page');
    $date = $request->query('date');

    $program = Program::find($program_vendor_configuration->program_id);

    $data = CbsTransaction::with('creditAccountRequest.program')
      ->whereHas('creditAccountRequest', function ($query) use ($program) {
        $query->whereHas('program', function ($query) use ($program) {
          $query->where('bank_id', $program->bank->id);
        });
      })
      ->where('transaction_type', 'Funds Transfer')
      ->where('status', 'Successful')
      ->when($date && $date != '', function ($query) use ($date) {
        $query->whereDate('transaction_date', $date);
      })
      ->latest()
      ->paginate($per_page);

    return response()->json($data);
  }

  public function creditAccount(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'debit_from_account' => ['required'],
      'credit_to_account' => ['required'],
      'amount' => ['required'],
      'credit_date' => ['required'],
      'program_id' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    try {
      DB::beginTransaction();

      // Check if requested credit amount is more than balance needed to be paid in the program
      $program = Program::find($request->program_id);
      // $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
      //   ->where('program_id', $request->program_id)
      //   ->first();
      if ($request->amount > $program->balance - $program->pending_repayment_transactions_amount) {
        return response()->json(['message' => 'Amount is more than pending payment amount'], 400);
      }

      $invoices = Invoice::dealerFinancing()
        ->where('program_id', $request->program_id)
        ->where('company_id', $company->id)
        ->where('financing_status', 'disbursed')
        ->whereDate('due_date', '>', now()->format('Y-m-d'))
        ->get();

      if ($invoices->count() == 0) {
        return response()->json(['message' => 'No Invoices available for early repayment'], 404);
      }

      if ($invoices->count() > 0) {
        $balance = $request->amount;

        $invoice = Invoice::dealerFinancing()
          ->where('program_id', $request->program_id)
          ->where('company_id', $company->id)
          ->where('financing_status', 'disbursed')
          ->orderBy('created_at', 'ASC')
          ->whereDate('due_date', '>', now())
          ->first();

        do {
          // // Check if sum if created and successful repayments sum up to invoice balance
          // $repayment_transactions_amount = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
          //   $query->where('invoice_id', $invoice->id);
          // })
          //   ->whereIn('status', ['Created', 'Successful'])
          //   ->whereIn('transaction_type', ['Overdue Account', 'Repayment'])
          //   ->sum('amount');

          // if ($repayment_transactions_amount <= $invoice->balance) {
          //   $program = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
          //     ->where('program_id', $invoice->program_id)
          //     ->first();

          //   if ($balance < $invoice->balance) {
          //     $payment_request = PaymentRequest::create([
          //       'reference_number' =>
          //         'DF' . $invoice->program->bank->id . '' . $program->payment_account_number . '000' . $invoice->id,
          //       'invoice_id' => $invoice->id,
          //       'amount' => $balance,
          //       'payment_request_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          //       'status' => 'approved',
          //       'approval_status' => 'approved',
          //     ]);

          //     // Principle repayments
          //     $payment_request->paymentAccounts()->create([
          //       'account' => $program->payment_account_number,
          //       'account_name' => $invoice->program->name,
          //       'amount' => $balance,
          //       'type' => 'principle_repayment',
          //       'description' => 'Dealer Financing Repayment',
          //     ]);

          //     CbsTransaction::create([
          //       'bank_id' => $payment_request->invoice?->program?->bank?->id,
          //       'payment_request_id' => $payment_request->id,
          //       'debit_from_account' => $request->debit_from_account,
          //       'credit_to_account' => $program->payment_account_number,
          //       'amount' => $balance,
          //       'transaction_created_date' => now()->format('Y-m-d'),
          //       'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          //       'status' => 'Created',
          //       'transaction_type' => 'Repayment',
          //       'product' => 'Dealer Financing',
          //     ]);
          //   } else {
          //     $payment_request = PaymentRequest::create([
          //       'reference_number' =>
          //         'DF' . $invoice->program->bank->id . '' . $program->payment_account_number . '000' . $invoice->id,
          //       'invoice_id' => $invoice->id,
          //       'amount' => $invoice->balance,
          //       'payment_request_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          //       'status' => 'approved',
          //       'approval_status' => 'approved',
          //     ]);

          //     // Principle repayments
          //     $payment_request->paymentAccounts()->create([
          //       'account' => $program->payment_account_number,
          //       'account_name' => $invoice->program->name,
          //       'amount' => $invoice->balance,
          //       'type' => 'principle_repayment',
          //       'description' => 'Dealer Financing Repayment',
          //     ]);

          //     CbsTransaction::create([
          //       'bank_id' => $payment_request->invoice?->program?->bank?->id,
          //       'payment_request_id' => $payment_request->id,
          //       'debit_from_account' => $request->debit_from_account,
          //       'credit_to_account' => $program->payment_account_number,
          //       'amount' => $invoice->balance,
          //       'transaction_created_date' => now()->format('Y-m-d'),
          //       'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          //       'status' => 'Created',
          //       'transaction_type' => 'Repayment',
          //       'product' => 'Dealer Financing',
          //     ]);
          //   }

          //   $payment_request = PaymentRequest::create([
          //     'reference_number' =>
          //       'DF' . $invoice->program->bank->id . '' . $program->payment_account_number . '000' . $invoice->id,
          //     'invoice_id' => $invoice->id,
          //     'amount' => $balance,
          //     'payment_request_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          //     'status' => 'approved',
          //     'approval_status' => 'approved',
          //   ]);

          //   // Principle repayments
          //   $payment_request->paymentAccounts()->create([
          //     'account' => $program->payment_account_number,
          //     'account_name' => $invoice->program->name,
          //     'amount' => $balance,
          //     'type' => 'principle_repayment',
          //     'description' => 'Dealer Financing Repayment',
          //   ]);

          //   CbsTransaction::create([
          //     'bank_id' => $payment_request->invoice?->program?->bank?->id,
          //     'payment_request_id' => $payment_request->id,
          //     'debit_from_account' => $request->debit_from_account,
          //     'credit_to_account' => $program->payment_account_number,
          //     'amount' => $balance,
          //     'transaction_created_date' => now()->format('Y-m-d'),
          //     'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          //     'status' => 'Created',
          //     'transaction_type' => 'Repayment',
          //     'product' => 'Dealer Financing',
          //   ]);

          //   activity($company->bank->id)
          //     ->causedBy(auth()->user())
          //     ->performedOn($program->program)
          //     ->withProperties([
          //       'ip' => request()->ip(),
          //       'device_info' => request()->userAgent(),
          //       'user_type' => 'Dealer',
          //     ])
          //     ->log('requested OD Account to be credited');

          //   $balance -= $invoice->balance;

          //   if ($balance > 0) {
          //     $invoice = Invoice::dealerFinancing()
          //       ->where('program_id', $program->program_id)
          //       ->where('financing_status', 'disbursed')
          //       ->orderBy('created_at', 'ASC')
          //       ->whereDate('due_date', '>', now())
          //       ->first();
          //   } else {
          //     $invoice = null;
          //   }
          // } else {
          //   $invoice = Invoice::dealerFinancing()
          //     ->where('id', '!=', $invoice->id)
          //     ->where('program_id', $request->program_id)
          //     ->where('financing_status', 'disbursed')
          //     ->orderBy('created_at', 'ASC')
          //     ->whereDate('due_date', '>', now())
          //     ->first();
          // }

          $payment_request = PaymentRequest::create([
            'reference_number' =>
              'DF' . $invoice->program->bank->id . '' . $request->credit_to_account . '000' . $invoice->id,
            'invoice_id' => $invoice->id,
            'amount' => $balance,
            'payment_request_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
            'status' => 'approved',
            'approval_status' => 'approved',
          ]);

          // Principle repayments
          $payment_request->paymentAccounts()->create([
            'account' => $request->credit_to_account,
            'account_name' => $invoice->program->name,
            'amount' => $balance,
            'type' => 'principle_repayment',
            'description' => 'Dealer Financing Repayment',
          ]);

          CbsTransaction::create([
            'bank_id' => $payment_request->invoice?->program?->bank?->id,
            'payment_request_id' => $payment_request->id,
            'debit_from_account' => $request->debit_from_account,
            'credit_to_account' => $request->credit_to_account,
            'amount' => $balance,
            'transaction_created_date' => now()->format('Y-m-d'),
            'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
            'status' => 'Created',
            'transaction_type' => 'Repayment',
            'product' => Program::DEALER_FINANCING,
          ]);

          activity($company->bank->id)
            ->causedBy(auth()->user())
            ->performedOn($program)
            ->withProperties([
              'ip' => request()->ip(),
              'device_info' => request()->userAgent(),
              'user_type' => 'Dealer',
            ])
            ->log('requested OD Account to be credited');

          $balance -= $invoice->balance;

          if ($balance > 0) {
            $invoice = Invoice::dealerFinancing()
              ->where('id', '!=', $invoice->id)
              ->where('program_id', $program->program_id)
              ->where('company_id', $company->id)
              ->where('financing_status', 'disbursed')
              ->orderBy('created_at', 'ASC')
              ->whereDate('due_date', '>', now())
              ->first();
          } else {
            $invoice = null;
          }
        } while ($invoice && $balance > 0);
      }

      DB::commit();

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Request to credit account sent successfully']);
      }
    } catch (\Throwable $e) {
      info($e);
      DB::rollBack();
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Something went wrong'], 500);
      }
    }
  }

  public function dealerAnchors()
  {
    return view('content.dealer.invoices.anchors');
  }

  public function dealerDownloadSample()
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('dealer-sample-invoices.xlsx'), 'dealer-sample-invoices.xlsx');
    }

    return response()->download(public_path('dealer-sample-invoices.xlsx'), 'dealer-sample-invoices.xlsx');
  }

  public function dealerPrograms(Request $request)
  {
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = ProgramVendorConfiguration::with('program.anchor')
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', 'Dealer Financing');
        });
      })
      ->where('company_id', $company->id)
      ->select('id', 'program_id', 'company_id', 'sanctioned_limit', 'payment_account_number', 'limit_expiry_date')
      ->paginate($per_page);

    foreach ($programs as $program) {
      $program['utilized'] = $company->utilizedAmount($program->program);
      $program['pipeline'] = $company->pipelineAmount($program->program);
    }

    if (request()->wantsJson()) {
      return response()->json(['programs' => $programs], 200);
    }
  }

  public function eligibleForFinancing(Program $program)
  {
    $total_amount = 0;

    $minimum_financing_days = $program->min_financing_days;

    $invoices = Invoice::dealerFinancing()
      ->where('program_id', $program->id)
      ->where('status', 'approved')
      ->whereDoesntHave('paymentRequests')
      ->whereDate('due_date', '>=', now())
      ->orderBy('due_date', 'ASC')
      ->get();

    if ($invoices->count() > 0) {
      $max_invoice_date = Carbon::parse($invoices->first()->due_date)->subDays($minimum_financing_days);

      foreach ($invoices as $invoice) {
        $total_amount += ($program->eligibility / 100) * $invoice->invoice_total_amount;
      }

      return response()->json(['total_amount' => $total_amount, 'max_date' => $max_invoice_date]);
    }
    return response()->json(['error' => 'No valid invoices in this program']);
  }

  public function eligibleForFinancingCalculate(Program $program, $date)
  {
    $total_discount = 0;
    $total_actual_remittance = 0;

    $invoices = Invoice::dealerFinancing()
      ->where('program_id', $program->id)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now())
      ->whereDoesntHave('paymentRequests')
      ->get();

    foreach ($invoices as $invoice) {
      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->first();
      $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company->id)
        ->where('program_id', $invoice->program_id)
        ->first();

      // Add Taxes
      $total_tax_amount = 0;
      foreach ($invoice->invoiceTaxes as $key => $tax) {
        $total_tax_amount += $tax->value;
      }

      // Deduct Anchor-Set taxes
      $total_fees_amount = 0;
      foreach ($invoice->invoiceFees as $key => $fee) {
        $total_fees_amount += $fee->amount;
      }

      // Deduct Vendor-set discount
      $invoice_discount_amount = 0;
      foreach ($invoice->invoiceDiscounts as $key => $discount) {
        if ($discount->type == 'percentage') {
          $invoice_discount_amount += ($discount->value / 100) * ($invoice->getAmount() + $total_tax_amount);
        } else {
          $invoice_discount_amount += $discount->value;
        }
      }

      $eligibility = $vendor_configurations->eligibility;
      $total_amount = $invoice->getAmount() + $total_tax_amount - $invoice_discount_amount - $total_fees_amount;
      $total_roi = $vendor_discount_details->total_roi;
      $legible_amount = ($eligibility / 100) * $total_amount;

      $processing_fee = 0.01 * $legible_amount;

      $discount =
        ($eligibility / 100) *
        $legible_amount *
        ($total_roi / 100) *
        ((Carbon::createFromFormat('Y-m-d', $date)->diffInDays(Carbon::parse($invoice->due_date)) + 1) / 365);
      $total_discount +=
        ($eligibility / 100) *
        $legible_amount *
        ($total_roi / 100) *
        ((Carbon::createFromFormat('Y-m-d', $date)->diffInDays(Carbon::parse($invoice->due_date)) + 1) / 365);
      $total_actual_remittance += $legible_amount - $processing_fee - $discount;
    }

    return [round($total_discount, 2), round($total_actual_remittance, 2)];
  }

  public function updateFinanceRequest(PaymentRequest $payment_request, Request $request)
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
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Dealer'])
        ->log('initiaited drawdown');

      $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
        ->where('status', 'active')
        ->where('bank_id', $payment_request->invoice->program->bank_id)
        ->first();

      if (!$noa_text) {
        $noa_text = NoaTemplate::where('product_type', 'generic')
          ->where('status', 'active')
          ->first();
      }

      // Get difference in days between anchor payment and repayment date
      $diff = Carbon::parse($payment_request->invoice->payment_date)->diffInDays(
        Carbon::parse($payment_request->invoice->due_date)
      );

      // Get vendor discount details
      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $payment_request->invoice->company_id)
        ->where('program_id', $payment_request->invoice->program_id)
        ->where('from_day', '<=', $diff)
        ->where('to_day', '>=', $diff)
        ->latest()
        ->first();

      if (!$vendor_discount_details) {
        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $payment_request->invoice->company_id)
          ->where('program_id', $payment_request->invoice->program_id)
          ->latest()
          ->first();
      }

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

      $vendor_configurations = ProgramVendorDiscount::where('company_id', $payment_request->invoice->company_id)
        ->where('program_id', $payment_request->invoice->program_id)
        ->first();

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

        SendMail::dispatchAfterResponse($bank_user->email, 'PaymentRequested', [
          'payment_request_id' => $payment_request->id,
          'link' => config('app.url') . '/' . $payment_request->invoice->program->bank->url,
          'type' => 'dealer_financing',
          'noa' => $noa_text != null ? $pdf->output() : null,
        ]);
      }

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Initiated drawdown successfully');

      return redirect()->route('dealer.invoices.index');
    } else {
      $payment_request->update([
        'status' => 'rejected',
        'rejected_reason' => $request->rejection_reason,
      ]);

      $payment_request->companyApprovals->update([
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

      $payment_request->invoice->program->update(
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

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Initiated drawdown successfully');

      return redirect()->route('dealer.invoices.index');
    }
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
    $data['{buyerName}'] = $invoice->company->name;
    $data['{anchorName}'] = $invoice->program->anchor->name;
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
}
