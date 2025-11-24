<?php

namespace App\Http\Controllers\Vendor;

use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Program;
use App\Models\CompanyTax;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use App\Models\UserCurrentCompany;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\InvoiceSettingsChange;
use App\Models\ProgramCompanyRole;
use App\Models\ProgramRole;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\ProgramVendorConfiguration;
use App\Notifications\ConfigurationsApproval;

class SettingController extends Controller
{
  public function index()
  {
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $proposed_updates = $company->invoiceSetting?->proposedUpdate?->where('user_id', '!=', auth()->id())->count();

    return view('content.vendor.settings.index', compact('proposed_updates'));
  }

  public function companyProfile(Company $company)
  {
    $company->load('documents', 'pipeline');

    return view('content.vendor.profile.index', [
      'company' => $company
    ]);
  }

  public function bankAccountsData()
  {
    $company = auth()->user()->activeVendorCompany()->first();

    return response()->json(ProgramVendorBankDetail::with('program.bank')
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->where('company_id', $company->company_id)
      ->get());
  }

  public function anchors()
  {
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $programs = [];

    $program_ids = ProgramVendorConfiguration::whereHas('program', function ($query) {
      $query->whereHas('programCode', function ($query) {
        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
      });
    })
    ->where('company_id', $company->id)
    ->pluck('program_id');

    $programs = Program::with(['anchor' => fn($query) => $query->select('name')])->whereIn('id', $program_ids)->select('id', 'name', 'code')->get();
    foreach ($programs as $program) {
      $program['vendor_configuration'] = ProgramVendorConfiguration::where('company_id', $company->id)->where('program_id', $program->id)->select('id', 'program_id', 'company_id', 'auto_request_finance', 'payment_account_number')->first();
    }

    return response()->json($programs);
  }

  public function updateAnchorSettings(Request $request)
  {
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $vendor_configuration = $company->programConfigurations->where('program_id', $request->program_id)->first();

    if ($vendor_configuration) {
      $vendor_configuration->update([
        'auto_request_finance' => !$vendor_configuration->auto_request_finance
      ]);
    }

    return response()->json(['message' => 'Invoice settings updated successfully']);
  }

  public function invoiceSettings()
  {
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    if (!$company->invoiceSetting) {
      $company->invoiceSetting()->create();
    }

    $company = Company::find($current_company->company_id);

    $settings = $company->invoiceSetting->load('proposedUpdate');

    return response()->json(['settings' => $settings, 'can_approve' => auth()->user()->hasPermissionTo('Configurations Changes Checker'), 'user_id' => auth()->id()]);
  }

  public function updateInvoiceSettings(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000']
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Configurations Changes Checker');
          });
        })
          ->where('user_id', '!=', auth()->id());
      })
      ->get();

    if ($request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)) {
      // Check if there are invoices pending approval
      $invoices = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereIn('stage', ['pending_checker', 'pending_maker'])
      ->count();

      if ($invoices > 0) {
        // Cannot make changes when there invoices pending checker as some invoices might be left hanging
        return response()->json(['message' => 'Cannot update Approval Settings with pending invoices'], 400);
      }
    }

    $invoice_settings = $company->invoiceSetting;

    InvoiceSettingsChange::where('invoice_setting_id', $invoice_settings->id)->delete();

    $update_data = [];

    $invoice_settings->maker_checker_creating_updating = $request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating) ? ($request->maker_checker_creating_updating == 'true' ? true : false) : $invoice_settings->maker_checker_creating_updating;
    $invoice_settings->auto_request_financing = $request->has('auto_request_financing') && !empty($request->auto_request_financing) ? ($request->auto_request_financing == 'true' ? true : false) : $invoice_settings->auto_request_financing;
    $invoice_settings->request_financing_maker_checker = $request->has('request_financing_maker_checker') && !empty($request->request_financing_maker_checker) ? ($request->request_financing_maker_checker == 'true' ? true : false) : $invoice_settings->request_financing_maker_checker;
    $invoice_settings->purchase_order_acceptance = $request->has('purchase_order_acceptance') && !empty($request->purchase_order_acceptance) ? ($request->purchase_order_acceptance == 'true' ? 'manual' : 'auto') : $invoice_settings->purchase_order_acceptance;
    $invoice_settings->description = $request->has('description') && !empty($request->description) && $request->description != 'null' ? $request->description : $invoice_settings->description;
    $invoice_settings->footer = $request->has('footer') && !empty($request->footer) && $request->footer != 'null' ? $request->footer : $invoice_settings->footer;
    $invoice_settings->logo = $request->hasFile('company_logo') ? pathinfo($request->company_logo->store('logo', 'invoices'), PATHINFO_BASENAME) : $invoice_settings->logo;

    if ($checker_user->count() > 0) {
      $update_data[] = $invoice_settings->getDirty();
      InvoiceSettingsChange::create([
        'invoice_setting_id' => $invoice_settings->id,
        'user_id' => auth()->id(),
        'changes' => $update_data,
      ]);

      // Notify checker users
      foreach ($checker_user as $checker) {
        SendMail::dispatchAfterResponse($checker->user->email, 'ConfigurationsChanged', ['company_id' => $company->id, 'link' => config('app.url')]);
      }

      return response()->json(['message' => 'Invoice settings sent for approval']);
    } else {
      $invoice_settings->save();
      return response()->json(['message' => 'Invoice settings updated successfully']);
    }
  }

  public function updateStatus($status)
  {
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $invoice_settings = $company->invoiceSetting;

    $proposed_updates = $invoice_settings->proposedUpdate;

    // Check if setting changes has maker/checker setting changes with pending invoices
    $maker_checker_setting = false;
    foreach ($proposed_updates->changes as $update) {
      foreach ($update as $column => $value) {
        if ($column == 'maker_checker_creating_updating') {
          $maker_checker_setting = true;
        }
      }
    }

    if ($maker_checker_setting) {
      // Check if there are invoices pending approval
      $invoices = Invoice::vendorFinancing()
      ->where('company_id', $company->id)
      ->whereIn('stage', ['pending_checker', 'pending_maker'])
      ->count();

      if ($invoices > 0) {
        // Cannot make changes when there invoices pending checker as some invoices might be left hanging
        return response()->json(['message' => 'Cannot update Approval Settings with pending invoices'], 400);
      }
    }

    if ($status == 'approve') {
      try {
        DB::beginTransaction();

        foreach ($proposed_updates->changes as $key => $update) {
          foreach ($update as $column => $value) {
            $invoice_settings->update([
              $column => $value,
            ]);
          }
        }

        DB::commit();

        // Notify change creator
        $proposed_updates->user->notify(new ConfigurationsApproval('Invoice', 'approved'));
        $proposed_updates->delete();

        return response()->json(['message' => 'Configurations Updated']);
      } catch (\Throwable $e) {
        info($e);
        DB::rollBack();
        return response()->json(['message' => 'Something went wrong']);
      }
    } else {
      // Notify change creator
      $proposed_updates->user->notify(new ConfigurationsApproval('Invoice', 'rejected'));
      $proposed_updates->delete();

      return response()->json(['message' => 'Configurations Discarded']);
    }
  }

  public function taxes()
  {
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    return response()->json($company->taxes);
  }

  public function addTax(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'tax_name' => ['required'],
      'tax_number' => ['required'],
      'tax_value' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $company->taxes()->create([
      'tax_name' => $request->tax_name,
      'tax_number' => $request->tax_number,
      'tax_value' => $request->tax_value
    ]);

    return response()->json(['message' => 'Tax added successfully']);
  }

  public function deleteTax($id)
  {
    CompanyTax::find($id)->delete();

    return response()->json(['message' => 'Tax deleted successfully']);
  }

  public function switchCompany(Request $request)
  {
    $request->validate([
      'company_id' => ['required']
    ]);

    $company_role = ProgramCompanyRole::where('company_id', $request->company_id)->first()->role_id;

    $program_roles = ProgramRole::find($company_role);

    switch ($program_roles->name) {
      case 'dealer':
        UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'buyer dealer'], ['company_id' => $request->company_id]);
        return redirect()->route('dealer.dashboard');
        break;
      case 'buyer':
        UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'buyer'], ['company_id' => $request->company_id]);
        return redirect()->route('buyer.dashboard');
      case 'anchor':
        UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'anchor'], ['company_id' => $request->company_id]);
        return redirect()->route('anchor.dashboard');
      case 'vendor':
        UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'vendor'], ['company_id' => $request->company_id]);
        return redirect()->route('vendor.dashboard');
      default:
        toastr()->error('', 'Invalid Company');
        return back();
        break;
    }

    // UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'vendor'])->update(['company_id' => $request->company_id]);

    return back();
  }
}
