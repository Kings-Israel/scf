<?php

namespace App\Http\Controllers\Buyer;

use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Program;
use App\Models\CompanyTax;
use App\Models\CompanyUser;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Models\ProgramCompanyRole;
use App\Models\UserCurrentCompany;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateInvoicesTaxes;
use App\Models\AnchorConfigurationChange;
use App\Models\InvoiceSettingsChange;
use Illuminate\Support\Facades\Route;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Validator;
use App\Models\ProgramVendorConfiguration;
use App\Models\PurchaseOrderSettingsChange;
use App\Notifications\ConfigurationsApproval;

class ConfigurationsController extends Controller
{
  public function general()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $proposed_updates = $company->purchaseOrderSetting?->proposedUpdate?->where('user_id', '!=', auth()->id())->count();

    return view('content.buyer.configurations.index', compact('proposed_updates'));
  }

  public function bankAccounts()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    return response()->json(
      ProgramVendorBankDetail::with('program.bank')
        ->whereHas('program', function ($query) {
          $query->whereHas('programCode', function ($query) {
            $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
          });
        })
        ->where('buyer_id', $current_company->company_id)
        ->get()
    );
  }

  public function purchaseOrderSettings()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->purchaseOrderSetting) {
      $company->purchaseOrderSetting()->create();
    }

    $company = Company::find($current_company->company_id);

    return response()->json([
      'settings' => $company->purchaseOrderSetting->load('proposedUpdate'),
      'can_approve' => auth()
        ->user()
        ->hasPermissionTo('Configurations Changes Checker'),
      'user_id' => auth()->id(),
    ]);
  }

  public function updatePurchaseOrderSettings(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query
          ->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Configurations Changes Checker');
            });
          })
          ->where('user_id', '!=', auth()->id());
      })
      ->get();

    $purchase_order_settings = $company->purchaseOrderSetting;

    PurchaseOrderSettingsChange::where('po_setting_id', $purchase_order_settings->id)->delete();

    $update_data = [];

    $purchase_order_settings->maker_checker_creating_updating =
      $request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)
        ? ($request->maker_checker_creating_updating == 'true'
          ? true
          : false)
        : $purchase_order_settings->maker_checker_creating_updating;
    $purchase_order_settings->auto_request_financing =
      $request->has('auto_request_financing') && !empty($request->auto_request_financing)
        ? ($request->auto_request_financing == 'true'
          ? true
          : false)
        : $purchase_order_settings->auto_request_financing;
    $purchase_order_settings->description =
      $request->has('description') && !empty($request->description) && $request->description != 'null'
        ? $request->description
        : $purchase_order_settings->description;
    $purchase_order_settings->footer =
      $request->has('footer') && !empty($request->footer) && $request->footer != 'null'
        ? $request->footer
        : $purchase_order_settings->footer;
    $purchase_order_settings->logo = $request->hasFile('company_logo')
      ? pathinfo($request->company_logo->store('logo', 'purchase-orders'), PATHINFO_BASENAME)
      : $purchase_order_settings->logo;

    if ($checker_user->count() > 0) {
      $update_data[] = $purchase_order_settings->getDirty();
      PurchaseOrderSettingsChange::create([
        'po_setting_id' => $purchase_order_settings->id,
        'user_id' => auth()->id(),
        'changes' => $update_data,
      ]);

      // Notify checker users
      foreach ($checker_user as $checker) {
        SendMail::dispatchAfterResponse($checker->user->email, 'ConfigurationsChanged', [
          'company_id' => $company->id,
          'link' => config('app.url'),
        ]);
      }

      return response()->json(['message' => 'PO settings sent for approval']);
    } else {
      $purchase_order_settings->save();
      return response()->json(['message' => 'PO settings updated successfully']);
    }
  }

  public function updateStatus($status)
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $purchase_order_settings = $company->purchaseOrderSetting;

    $proposed_updates = $purchase_order_settings->proposedUpdate;

    if ($status == 'approve') {
      try {
        DB::beginTransaction();

        foreach ($proposed_updates->changes as $key => $update) {
          foreach ($update as $column => $value) {
            $purchase_order_settings->update([
              $column => $value,
            ]);
          }
        }

        DB::commit();

        // Notify change creator
        $proposed_updates->user->notify(new ConfigurationsApproval('Purchase Order', 'approved'));
        $proposed_updates->delete();

        return response()->json(['message' => 'Configurations Updated']);
      } catch (\Throwable $e) {
        info($e);
        DB::rollBack();
        return response()->json(['message' => 'Something went wrong']);
      }
    } else {
      // Notify change creator
      $proposed_updates->user->notify(new ConfigurationsApproval('Purchase Order', 'rejected'));
      $proposed_updates->delete();

      return response()->json(['message' => 'Configurations Discarded']);
    }
  }

  public function vendors()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendors = ProgramVendorConfiguration::with('company', 'program')
      ->where('buyer_id', $company->id)
      ->where('status', 'active')
      ->where('is_approved', true)
      ->get();

    foreach ($vendors as $vendor) {
      if (!$vendor->payment_terms || $vendor->payment_terms == 0) {
        $vendor->update([
          'payment_terms' => $vendor->program->default_payment_terms,
        ]);
      }
    }

    return view('content.buyer.configurations.vendor-settings', compact('vendors'));
  }

  public function updateVendorSettings(Request $request, $id)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $config = ProgramVendorConfiguration::find($id);

    $anchor_users = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Configurations Changes Checker');
          });
        });
      })
      ->count();

    $config->withholding_tax = $request->tax;
    $config->withholding_vat = $request->vat;
    $config->payment_terms = $request->terms;
    $config->auto_approve_invoices =
      $request->has('auto_approve_invoices') && $request->auto_approve_invoices != ''
        ? $request->auto_approve_invoices
        : $config->auto_approve_invoices;

    if (collect($config->getDirty())->count() == 0) {
      toastr()->error('', 'No Configuration was updated');

      return response()->json(['status' => 'Update']);
    }

    if ($anchor_users > 0) {
      AnchorConfigurationChange::create([
        'configurable_type' => ProgramVendorConfiguration::class,
        'configurable_id' => $config->id,
        'data' => $config->getDirty(),
        'created_by' => auth()->id(),
        'company_id' => $company->id,
      ]);

      toastr()->success('', 'Settings sent for approval');
    } else {
      $config->save();

      // Update all Vendor Invoices that are pending approval
      UpdateInvoicesTaxes::dispatch($config);

      toastr()->success('', 'Updated Vendor Settings');
    }

    return response()->json(['status' => $anchor_users > 0 ? 'Settings sent for approval' : 'Updated successfully']);
  }

  public function approveVendorSettings(ProgramVendorConfiguration $program_vendor_configuration, string $status)
  {
    if ($status == 'approve') {
      $anchor_configuration_change = $program_vendor_configuration->anchorConfigurationChange;

      foreach ($anchor_configuration_change->data as $key => $value) {
        $program_vendor_configuration->update([
          $key => $value,
        ]);
      }

      UpdateInvoicesTaxes::dispatch($program_vendor_configuration);

      toastr()->success('', 'Configuration approved successfully');
    } else {
      toastr()->success('', 'Cofiguration discarded successfully');
    }

    $program_vendor_configuration->anchorConfigurationChange->delete();

    return back();
  }

  public function anchors()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $program_ids = [];
    $programs = [];

    // Get Anchors
    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
          $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
      ) {
        array_push($program_ids, $program->id);
      }
    }

    $programs = Program::with(['anchor' => fn($query) => $query->select('name')])
      ->whereIn('id', $program_ids)
      ->select('id', 'name', 'code')
      ->get();
    foreach ($programs as $program) {
      $program['vendor_configuration'] = ProgramVendorConfiguration::where('buyer_id', $company->id)
        ->where('program_id', $program->id)
        ->select('request_auto_finance')
        ->first();
    }

    return response()->json($programs);
  }

  public function company()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::with('documents', 'pipeline')->find($current_company->company_id);

    return view('content.buyer.configurations.company-settings', ['company' => $company]);
  }

  public function dealer()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::with('documents', 'pipeline')->find($current_company->company_id);

    $proposed_updates = $company->purchaseOrderSetting?->proposedUpdate?->where('user_id', '!=', auth()->id())->count();

    return view('content.dealer.configurations.index', compact('proposed_updates'));
  }

  public function companyProfile()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::with('documents', 'pipeline')->find($current_company->company_id);

    return view('content.dealer.configurations.company-settings', [
      'company' => $company,
    ]);
  }

  public function dealerBankAccounts()
  {
    $company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    return response()->json(
      ProgramVendorBankDetail::with('program.bank')
        ->whereHas('program', function ($query) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          });
        })
        ->where('company_id', $company->company_id)
        ->get()
    );
  }

  public function dealerInvoiceSettings()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->invoiceSetting) {
      $company->invoiceSetting()->create();
    }

    $company = Company::find($current_company->company_id);

    return response()->json([
      'settings' => $company->invoiceSetting->load('proposedUpdate'),
      'can_approve' => auth()
        ->user()
        ->hasPermissionTo('Configurations Changes Checker'),
      'user_id' => auth()->id(),
    ]);
  }

  public function updateInvoiceSettings(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query
          ->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Configurations Changes Checker');
            });
          })
          ->where('user_id', '!=', auth()->id());
      })
      ->get();

    if ($request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)) {
      // Check if there are invoices pending approval
      $invoices = Invoice::factoring()
        ->where('buyer_id', $company->id)
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

    $invoice_settings->maker_checker_creating_updating =
      $request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)
        ? ($request->maker_checker_creating_updating == 'true'
          ? true
          : false)
        : $invoice_settings->maker_checker_creating_updating;
    $invoice_settings->auto_request_financing =
      $request->has('auto_request_financing') && !empty($request->auto_request_financing)
        ? ($request->auto_request_financing == 'true'
          ? true
          : false)
        : $invoice_settings->auto_request_financing;
    $invoice_settings->purchase_order_acceptance =
      $request->has('purchase_order_acceptance') && !empty($request->purchase_order_acceptance)
        ? ($request->purchase_order_acceptance == 'true'
          ? 'manual'
          : 'auto')
        : $invoice_settings->purchase_order_acceptance;
    $invoice_settings->description =
      $request->has('description') && !empty($request->description) && $request->description != 'null'
        ? $request->description
        : $invoice_settings->description;
    $invoice_settings->footer =
      $request->has('footer') && !empty($request->footer) && $request->footer != 'null'
        ? $request->footer
        : $invoice_settings->footer;
    $invoice_settings->logo = $request->hasFile('company_logo')
      ? pathinfo($request->company_logo->store('logo', 'invoices'), PATHINFO_BASENAME)
      : $invoice_settings->logo;

    if ($checker_user->count() > 0) {
      $update_data[] = $invoice_settings->getDirty();
      InvoiceSettingsChange::create([
        'invoice_setting_id' => $invoice_settings->id,
        'user_id' => auth()->id(),
        'changes' => $update_data,
      ]);

      // Notify checker users
      foreach ($checker_user as $checker) {
        SendMail::dispatchAfterResponse($checker->user->email, 'ConfigurationsChanged', [
          'company_id' => $company->id,
          'link' => config('app.url'),
        ]);
      }

      return response()->json(['message' => 'Invoice settings sent for approval']);
    } else {
      $invoice_settings->save();
      return response()->json(['message' => 'Invoice settings updated successfully']);
    }
  }

  public function updateDealerStatus($status)
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $invoice_settings = $company->invoiceSetting;

    $proposed_updates = $invoice_settings->proposedUpdate;

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

  public function dealerPurchaseOrderSettings()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->purchaseOrderSetting) {
      $company->purchaseOrderSetting()->create();
    }

    $company = Company::find($current_company->company_id);

    return response()->json([
      'settings' => $company->purchaseOrderSetting->load('proposedUpdate'),
      'can_approve' => auth()
        ->user()
        ->hasPermissionTo('Configurations Changes Checker'),
      'user_id' => auth()->id(),
    ]);
  }

  public function updateDealerPurchaseOrderSettings(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query
          ->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Configurations Changes Checker');
            });
          })
          ->where('user_id', '!=', auth()->id());
      })
      ->get();

    $purchase_order_settings = $company->purchaseOrderSetting;

    PurchaseOrderSettingsChange::where('po_setting_id', $purchase_order_settings->id)->delete();

    $update_data = [];

    $purchase_order_settings->maker_checker_creating_updating =
      $request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)
        ? ($request->maker_checker_creating_updating == 'true'
          ? true
          : false)
        : $purchase_order_settings->maker_checker_creating_updating;
    $purchase_order_settings->request_finance_add_repayment =
      $request->has('request_finance_add_repayment') && !empty($request->request_finance_add_repayment)
        ? ($request->request_finance_add_repayment == 'true'
          ? true
          : false)
        : $purchase_order_settings->request_finance_add_repayment;
    $purchase_order_settings->auto_request_financing =
      $request->has('auto_request_financing') && !empty($request->auto_request_financing)
        ? ($request->auto_request_financing == 'true'
          ? true
          : false)
        : $purchase_order_settings->auto_request_financing;
    $purchase_order_settings->description =
      $request->has('description') && !empty($request->description) && $request->description != 'null'
        ? $request->description
        : $purchase_order_settings->description;
    $purchase_order_settings->footer =
      $request->has('footer') && !empty($request->footer) && $request->footer != 'null'
        ? $request->footer
        : $purchase_order_settings->footer;
    $purchase_order_settings->logo = $request->hasFile('company_logo')
      ? pathinfo($request->company_logo->store('logo', 'purchase-orders'), PATHINFO_BASENAME)
      : $purchase_order_settings->logo;

    if ($checker_user->count() > 0) {
      $update_data[] = $purchase_order_settings->getDirty();
      PurchaseOrderSettingsChange::create([
        'po_setting_id' => $purchase_order_settings->id,
        'user_id' => auth()->id(),
        'changes' => $update_data,
      ]);

      // Notify checker users
      foreach ($checker_user as $checker) {
        SendMail::dispatchAfterResponse($checker->user->email, 'ConfigurationsChanged', [
          'company_id' => $company->id,
          'link' => config('app.url'),
        ]);
      }

      return response()->json(['message' => 'PO settings sent for approval']);
    } else {
      $purchase_order_settings->save();
      return response()->json(['message' => 'PO settings updated successfully']);
    }
  }

  public function updateDealerPurchaseOrderStatus($status)
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $purchase_order_settings = $company->purchaseOrderSetting;

    $proposed_updates = $purchase_order_settings->proposedUpdate;

    if ($status == 'approve') {
      try {
        DB::beginTransaction();

        foreach ($proposed_updates->changes as $key => $update) {
          foreach ($update as $column => $value) {
            $purchase_order_settings->update([
              $column => $value,
            ]);
          }
        }

        DB::commit();

        // Notify change creator
        $proposed_updates->user->notify(new ConfigurationsApproval('Purchase Order', 'approved'));
        $proposed_updates->delete();

        return response()->json(['message' => 'Configurations Updated']);
      } catch (\Throwable $e) {
        info($e);
        DB::rollBack();
        return response()->json(['message' => 'Something went wrong']);
      }
    } else {
      // Notify change creator
      $proposed_updates->user->notify(new ConfigurationsApproval('Purchase Order', 'rejected'));
      $proposed_updates->delete();

      return response()->json(['message' => 'Configurations Discarded']);
    }
  }

  public function taxes()
  {
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    return response()->json($company->taxes);
  }

  public function vendor()
  {
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

    $programs = ProgramVendorConfiguration::with('program.anchor')
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', 'Dealer Financing');
        });
      })
      ->whereIn('program_id', $program_ids)
      ->where('company_id', $company->id)
      ->orderBy('created_at', 'DESC')
      ->get();

    return view('content.dealer.configurations.vendor-settings', ['anchors' => $programs]);
  }

  public function updateWht(Request $request, $id)
  {
    $config = ProgramVendorConfiguration::find($id);

    $config->update([
      'withholding_tax' => $request->value,
      'withholding_vat' => $request->vat,
    ]);

    toastr()->success('', 'Updated Successfully');

    return response()->json(['status' => 'Updated successfully']);
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

    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $company->taxes()->create([
      'tax_name' => $request->tax_name,
      'tax_number' => $request->tax_number,
      'tax_value' => $request->tax_value,
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
      'company_id' => ['required'],
    ]);

    // Get company role
    $company_role = ProgramCompanyRole::where('company_id', $request->company_id)->first()->role_id;

    $program_roles = ProgramRole::find($company_role);

    switch ($program_roles->name) {
      case 'dealer':
        UserCurrentCompany::updateOrInsert(
          ['user_id' => auth()->id(), 'platform' => 'buyer dealer'],
          ['company_id' => $request->company_id]
        );
        return redirect()->route('dealer.dashboard');
        break;
      case 'buyer':
        UserCurrentCompany::updateOrInsert(
          ['user_id' => auth()->id(), 'platform' => 'buyer'],
          ['company_id' => $request->company_id]
        );
        return redirect()->route('buyer.dashboard');
      case 'anchor':
        UserCurrentCompany::updateOrInsert(
          ['user_id' => auth()->id(), 'platform' => 'anchor'],
          ['company_id' => $request->company_id]
        );
        return redirect()->route('anchor.dashboard');
      case 'vendor':
        UserCurrentCompany::updateOrInsert(
          ['user_id' => auth()->id(), 'platform' => 'vendor'],
          ['company_id' => $request->company_id]
        );
        return redirect()->route('vendor.dashboard');
      default:
        toastr()->error('', 'Invalid Company');
        return back();
        break;
    }

    // if (Route::currentRouteName() == 'dealer.company.switch') {
    //   UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'buyer dealer'], ['company_id' => $request->company_id]);
    // } else {
    //   UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'buyer'], ['company_id' => $request->company_id]);
    // }

    return back();
  }
}
