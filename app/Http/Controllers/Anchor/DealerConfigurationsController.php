<?php

namespace App\Http\Controllers\Anchor;

use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Program;
use App\Models\CompanyTax;
use App\Models\CompanyUser;
use App\Models\DiscountSlab;
use Illuminate\Http\Request;
use App\Models\UserCurrentCompany;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentDiscountSlab;
use App\Http\Controllers\Controller;
use App\Http\Resources\OdAccountsResource;
use App\Jobs\UpdateInvoicesTaxes;
use App\Models\AnchorConfigurationChange;
use App\Models\Invoice;
use App\Models\InvoiceSettingsChange;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCompanyRole;
use App\Models\ProgramRole;
use Illuminate\Support\Facades\Route;
use App\Models\ProgramVendorBankDetail;
use Illuminate\Support\Facades\Validator;
use App\Models\ProgramVendorConfiguration;
use App\Models\PurchaseOrderSettingsChange;
use App\Notifications\ConfigurationsApproval;

class DealerConfigurationsController extends Controller
{
  public function general()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->purchaseOrderSetting) {
      $company->purchaseOrderSetting()->create();
    }

    if (!$company->invoiceSetting) {
      $company->invoiceSetting()->create();
    }

    $proposed_updates = $company->purchaseOrderSetting?->proposedUpdate?->where('user_id', '!=', auth()->id())->count();
    $proposed_invoice_updates = $company->invoiceSetting?->proposedUpdate
      ?->where('user_id', '!=', auth()->id())
      ->count();

    return view(
      'content.anchor.dealer.configurations.general',
      compact('proposed_updates', 'proposed_invoice_updates')
    );
  }

  public function bankAccounts()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    return response()->json(
      ProgramBankDetails::with('program.bank')
        ->whereHas('program', function ($query) use ($company) {
          $query->whereHas('programType', function ($query) {
            $query->where('name', Program::DEALER_FINANCING);
          })
          ->whereHas('anchor', function ($query) use ($company) {
            $query->where('companies.id', $company->id);
          });
        })
        ->get()
    );
  }

  public function purchaseOrderSettings()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->purchaseOrderSetting) {
      $company->purchaseOrderSetting()->create();
    }

    $company = Company::find($current_company->company_id);

    return response()->json([
      'settings' => $company->purchaseOrderSetting->load('proposedUpdate'),
      'can_approve' => true,
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
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query->where('user_id', '!=', auth()->id());
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

  public function invoiceSettings()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->invoiceSetting) {
      $company->invoiceSetting()->create([
        'maker_checker_creating_updating' => true,
      ]);
    }

    $company = Company::find($current_company->company_id);

    $settings = $company->invoiceSetting->load('proposedUpdate');

    return response()->json([
      'settings' => $settings,
      'can_approve' => true,
      'user_id' => auth()->id(),
    ]);
  }

  public function updateAnchorInvoiceSettings(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query->where('user_id', '!=', auth()->id());
      })
      ->get();

    if ($request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)) {
      // Check if there are invoices pending approval
      $invoices = Invoice::dealerFinancing()
      ->whereHas('program', function ($query) use ($company) {
        $query->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        });
      })
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
    $invoice_settings->request_financing_maker_checker =
      $request->has('request_financing_maker_checker') && !empty($request->request_financing_maker_checker)
        ? ($request->request_financing_maker_checker == 'true'
          ? true
          : false)
        : $invoice_settings->request_financing_maker_checker;
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

  public function updateAnchorStatus($status)
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

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
      $invoices = Invoice::dealerFinancing()
      ->whereHas('program', function ($query) use ($company) {
        $query->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->id);
        });
      })
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

  public function vendor()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = Program::whereHas('anchor', function ($query) use ($company) {
      $query->where('companies.id', $company->id);
    })
      ->whereHas('programType', function ($query) {
        $query->where('name', Program::DEALER_FINANCING);
      })
      ->pluck('programs.id');

    $vendors = ProgramVendorConfiguration::with('company', 'anchorConfigurationChange')
      ->whereIn('program_id', $programs)
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

    return view('content.anchor.dealer.configurations.vendor', compact('vendors'));
  }

  public function updateVendorSettings(Request $request, $id)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $config = ProgramVendorConfiguration::find($id);

    $anchor_users = CompanyUser::where('company_id', $company->id)
      ->where('user_id', '!=', auth()->id())
      ->count();

    $config->withholding_tax = $request->tax;
    $config->withholding_vat = $request->vat;
    $config->payment_terms = $request->terms;

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

  public function updateStatus($status)
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
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

  public function company()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::with('documents', 'pipeline')->find($current_company->company_id);

    return view('content.anchor.dealer.profile.index', ['company' => $company]);
  }

  public function switchCompany(Request $request)
  {
    $request->validate([
      'company_id' => ['required'],
    ]);

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

    // if (Route::currentRouteName() == 'anchor.factoring.company.switch') {
    //   UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'anchor factoring'], ['company_id' => $request->company_id]);
    // } else {
    //   UserCurrentCompany::updateOrInsert(['user_id' => auth()->id(), 'platform' => 'anchor'], ['company_id' => $request->company_id]);
    // }

    return back();
  }
}
