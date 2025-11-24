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

class ConfigurationsController extends Controller
{
  public function general()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
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
      'content.anchor.reverse-factoring.configurations.general',
      compact('proposed_updates', 'proposed_invoice_updates')
    );
  }

  public function bankAccounts()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    return response()->json(
      ProgramBankDetails::with('program.bank')
        ->whereHas('program', function ($query) use ($company) {
          $query->whereHas('programCode', function ($query) {
            $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
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
      ->activeAnchorCompany()
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
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Update Configurations')
    ) {
      return response()->json(['message' => 'You do not have permission to perform this action'], 403);
    }

    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
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

  public function invoiceSettings()
  {
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
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
      'can_approve' => auth()
        ->user()
        ->hasPermissionTo('Seller Configurations Changes Checker'),
      'user_id' => auth()->id(),
    ]);
  }

  public function updateAnchorInvoiceSettings(Request $request)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Seller Configurations')
    ) {
      return response()->json(['message' => 'You do not have permission to perform this action'], 403);
    }

    $validator = Validator::make($request->all(), [
      'company_logo' => ['max:5000'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $checker_user = CompanyUser::where('company_id', $company->id)
      ->whereHas('user', function ($query) {
        $query
          ->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Seller Configurations Changes Checker');
            });
          })
          ->where('user_id', '!=', auth()->id());
      })
      ->get();

    if ($request->has('maker_checker_creating_updating') && !empty($request->maker_checker_creating_updating)) {
      // Check if there are invoices pending approval
      $invoices = Invoice::vendorFinancing()
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
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Configurations Changes Checker')
    ) {
      return response()->json(['message' => 'You do not have permission to perform this action'], 403);
    }

    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
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
      $invoices = Invoice::vendorFinancing()
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
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = Program::whereHas('anchor', function ($query) use ($company) {
      $query->where('companies.id', $company->id);
    })
      ->whereHas('programCode', function ($query) {
        $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
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

    return view('content.anchor.reverse-factoring.configurations.vendor', compact('vendors'));
  }

  public function updateVendorSettings(Request $request, $id)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Vendor Settings')
    ) {
      return response()->json(['message' => 'You do not have permission to perform this action'], 403);
    }

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
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
      ->activeAnchorCompany()
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
      ->activeAnchorCompany()
      ->first();

    $company = Company::with('documents', 'pipeline')->find($current_company->company_id);

    return view('content.anchor.profile.index', ['company' => $company]);
  }

  public function factoring()
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $proposed_updates = $company->invoiceSetting?->proposedUpdate?->where('user_id', '!=', auth()->id())->count();

    $has_factoring_programs = $company->has_factoring_programs;
    $has_dealer_financing_programs = $company->has_dealer_financing_programs;

    return view('content.anchor.factoring.settings', compact('proposed_updates', 'has_factoring_programs', 'has_dealer_financing_programs'));
  }

  public function companyProfile(Company $company)
  {
    $company->load('documents', 'pipeline');

    return view('content.anchor.profile.index', ['company' => $company]);
  }

  public function factoringCompanyProfile(Company $company)
  {
    $company->load('documents', 'pipeline');

    return view('content.anchor.factoring.profile.index', ['company' => $company]);
  }

  public function factoringBankAccounts()
  {
    $company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $accounts = [];

    $dealer_financing_accounts = ProgramBankDetails::whereHas('program', function ($query) use ($company) {
      $query
        ->whereHas('anchor', function ($query) use ($company) {
          $query->where('companies.id', $company->company_id);
        })
        ->where(function ($query) {
          $query
            ->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            })
            ->orWhereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
        });
    })->get();

    foreach ($dealer_financing_accounts as $dealer_financing_account) {
      array_push($accounts, $dealer_financing_account);
    }

    return response()->json($accounts);
  }

  public function factoringInvoiceSettings()
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    if (!$company->invoiceSetting) {
      $company->invoiceSetting()->create();
    }

    $company = Company::find($current_company->company_id);

    $settings = $company->invoiceSetting->load('proposedUpdate');

    return response()->json([
      'settings' => $settings,
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
      ->activeFactoringCompany()
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

  public function factoringUpdateStatus($status)
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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
      $invoices = Invoice::factoring()
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
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

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

    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
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

  public function slabSettings()
  {
    return view('content.anchor.factoring.slab-settings');
  }

  public function discountSlabs(Request $request)
  {
    $per_page = $request->query('per_page');

    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $slabs = PaymentDiscountSlab::with('discountSlabs')
      ->where('company_id', $current_company->company_id)
      ->paginate($per_page);

    return response()->json(['slabs' => $slabs]);
  }

  public function storeSlabSettings(Request $request)
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $from_days = json_decode($request->from_days);
    $to_days = json_decode($request->to_days);
    $discount_percentages = json_decode($request->discount_percentages);

    try {
      DB::beginTransaction();

      $slab = PaymentDiscountSlab::create([
        'company_id' => $current_company->company_id,
        'title' => $request->title,
        'status' => $request->status,
      ]);

      foreach ($discount_percentages as $key => $discount_percentage) {
        if (array_key_exists($key, $from_days) && array_key_exists($key, $to_days)) {
          DiscountSlab::create([
            'payment_discount_slab_id' => $slab->id,
            'from_day' => $from_days[$key],
            'to_day' => $to_days[$key],
            'discount_percentage' => $discount_percentage,
          ]);
        }
      }
      DB::commit();
    } catch (\Throwable $e) {
      info($e);
      DB::rollBack();
    }

    return response()->json(['slab' => $slab]);
  }

  public function updateSlabSettings(Request $request)
  {
    $from_days = json_decode($request->from_days);
    $to_days = json_decode($request->to_days);
    $discount_percentages = json_decode($request->discount_percentages);

    try {
      DB::beginTransaction();

      $slab = PaymentDiscountSlab::find($request->slab_id);

      $slab->update([
        'title' => $request->title,
        'status' => $request->status,
      ]);

      $slab->discountSlabs()->delete();

      foreach ($discount_percentages as $key => $discount_percentage) {
        if (array_key_exists($key, $from_days) && array_key_exists($key, $to_days)) {
          DiscountSlab::create([
            'payment_discount_slab_id' => $slab->id,
            'from_day' => $from_days[$key],
            'to_day' => $to_days[$key],
            'discount_percentage' => $discount_percentage,
          ]);
        }
      }
      DB::commit();
    } catch (\Throwable $e) {
      info($e);
      DB::rollBack();
    }

    return response()->json(['slab' => $slab]);
  }

  public function anchors()
  {
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $programs = [];

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with('program', 'buyer')
        ->whereHas('program', function ($query) {
          $query->whereHas('programCode', function ($query) {
            $query
              ->where('name', Program::FACTORING_WITH_RECOURSE)
              ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
          });
        })
        ->where('company_id', $company->id)
        ->get()
    );

    return response()->json($programs);
  }

  public function updateAnchorSettings(Request $request)
  {
    $vendor_configuration = ProgramVendorConfiguration::find($request->program_id);

    if ($vendor_configuration) {
      $vendor_configuration->update([
        'auto_request_finance' => !$vendor_configuration->auto_request_finance,
      ]);
    }

    return response()->json(['message' => 'Invoice settings updated successfully']);
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
