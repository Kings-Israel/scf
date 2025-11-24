<?php

namespace App\Http\Controllers\Anchor;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Models\Currency;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Models\BankConvertionRate;
use App\Models\ProgramVendorConfiguration;
use App\Notifications\PurchaseOrderCreated;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrdersController extends Controller
{
  public function index()
  {
    return view('content.anchor.reverse-factoring.purchase-orders.index');
  }

  public function purchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $status = $request->query('status');
    $companies = $request->query('vendor');
    $po_number = $request->query('po_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');

    $purchase_orders = [];

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);
    $vendors = [];
    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
      ) {
        foreach ($program->getVendors() as $vendor) {
          array_push($vendors, $vendor->id);
        }
      }
    }

    $purchase_orders = PurchaseOrder::with('company')
      ->whereIn('company_id', $vendors)
      ->when($companies && $companies != '', function ($query) use ($companies) {
        $query->whereHas('company', function ($query) use ($companies) {
          $query->where('name', 'LIKE', '%' . $companies . '%');
        });
      })
      ->when($po_number && $po_number != '', function ($query) use ($po_number) {
        $query->where('purchase_order_number', 'LIKE', '%' . $po_number . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('delivery_from', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('delivery_from', $to_date);
      })
      ->latest()
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function create()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendors = [];

    foreach ($company->programs as $program) {
      if (
        $program->programType->name == Program::VENDOR_FINANCING &&
        $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
      ) {
        foreach ($program->getVendors() as $key => $vendor) {
          array_push($vendors, $vendor);
        }
      }
    }

    $vendors = collect($vendors)->unique();

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

    return view('content.anchor.reverse-factoring.purchase-orders.create', compact('vendors', 'currencies'));
  }

  public function store(Request $request)
  {
    $request->validate(
      [
        'company_id' => 'required',
        'purchase_order_number' => ['required', 'unique:purchase_orders,purchase_order_number'],
        'duration_from' => 'required',
        'duration_to' => 'required',
        'delivery_date' => 'required',
        'delivery_address' => 'required',
        'invoice_payment_terms' => 'required',
        'item' => ['required', 'array', 'min:1'],
        'quantity' => ['required', 'array', 'min:1'],
        'unit' => ['required', 'array', 'min:1'],
        'price_per_quantity' => ['required', 'array', 'min:1'],
      ],
      [
        'company_id.required' => 'Select a vendor',
      ]
    );

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $purchase_order_settings = $company->purchaseOrderSetting;

    $purchase_order = PurchaseOrder::create([
      'company_id' => $request->company_id,
      'purchase_order_number' => $request->purchase_order_number,
      'currency' => $request->currency,
      'duration_from' => Carbon::parse($request->duration_from)->format('Y-m-d'),
      'duration_to' => Carbon::parse($request->duration_to)->format('Y-m-d'),
      'delivery_date' => Carbon::parse($request->delivery_date)->format('Y-m-d'),
      'delivery_address' => $request->delivery_address,
      'invoice_payment_terms' => $request->invoice_payment_terms,
      'remarks' => $request->remarks,
      'anchor_id' => $current_company->company_id,
      'created_by' => auth()->id(),
      'status' => 'pending',
    ]);

    foreach ($request->item as $key => $item) {
      if (
        array_key_exists($key, $request->quantity) &&
        (float) str_replace(',', '', $request->quantity[$key]) > 0 &&
        array_key_exists($key, $request->unit) &&
        array_key_exists($key, $request->price_per_quantity) &&
        (float) str_replace(',', '', $request->price_per_quantity[$key]) > 0
      ) {
        PurchaseOrderItem::create([
          'purchase_order_id' => $purchase_order->id,
          'item' => $item,
          'quantity' => array_key_exists($key, $request->quantity)
            ? str_replace(',', '', $request->quantity[$key])
            : null,
          'unit' => array_key_exists($key, $request->unit) ? $request->unit[$key] : null,
          'price_per_quantity' => array_key_exists($key, $request->price_per_quantity)
            ? str_replace(',', '', $request->price_per_quantity[$key])
            : null,
          'description' => array_key_exists($key, $request->description) ? $request->description[$key] : null,
        ]);
      }
    }

    if ($purchase_order_settings && !$purchase_order_settings->maker_checker_creating_updating) {
      $purchase_order->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      $purchase_order->update([
        'status' => 'pending_acceptance',
      ]);

      $users = User::whereIn('id', $purchase_order->company->users->pluck('id'))->get();

      $purchase_order->company->notify(new PurchaseOrderCreated($purchase_order));

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoCreation', [
          'purchase_order_id' => $purchase_order->id,
          'type' => 'vendor_financing',
        ]);
      }
    } else {
      // requires maker checker approval
      // add maker approval
      $purchase_order->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      foreach ($company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoCreation', [
          'purchase_order_id' => $purchase_order->id,
          'type' => 'vendor_financing',
        ]);
      }
    }

    toastr()->success('', 'Purchase Order created successfully');

    return redirect()->route('anchor.purchase-orders.index');
  }

  public function approve(Request $request)
  {
    $request->validate([
      'purchase_order_id' => ['required'],
      'status' => ['required', 'in:approve,rejected'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    $purchase_order = PurchaseOrder::find($request->purchase_order_id);

    if ($request->status == 'approve') {
      $purchase_order->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      $purchase_order->update([
        'status' => 'pending_acceptance',
      ]);

      $users = User::whereIn('id', $purchase_order->company->users->pluck('id'))->get();

      $purchase_order->company->notify(new PurchaseOrderCreated($purchase_order));

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoCreation', [
          'purchase_order_id' => $purchase_order->id,
          'type' => 'vendor_financing',
        ]);
      }
    } else {
      $purchase_order->update([
        'status' => $request->status,
        'rejection_reason' => $request->rejection_reason,
      ]);
    }
  }

  public function bulkApprove(Request $request)
  {
    $request->validate([
      'purchase_orders' => ['required', 'array'],
      'status' => ['required', 'in:approved,rejected'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    foreach ($request->purchase_orders as $purchase_order_id) {
      $purchase_order = PurchaseOrder::find($purchase_order_id);

      if ($request->status == 'approved') {
        $purchase_order->approvals()->create([
          'user_id' => auth()->id(),
        ]);

        $purchase_order->update([
          'status' => 'pending_acceptance',
        ]);

        $users = User::whereIn('id', $purchase_order->company->users->pluck('id'))->get();

        $purchase_order->company->notify(new PurchaseOrderCreated($purchase_order));

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PoCreation', [
            'purchase_order_id' => $purchase_order->id,
            'type' => 'vendor_financing',
          ]);
        }
      } else {
        $purchase_order->update([
          'status' => $request->status,
          'rejection_reason' => $request->rejection_reason,
        ]);
      }
    }

    if ($request->wantsJson()) {
      return response()->json('Purchase Order udpated', 200);
    }

    return back();
  }

  public function show($purchase_order)
  {
    $purchase_order = PurchaseOrder::with('company', 'anchor', 'purchaseOrderItems')->find($purchase_order);

    // Find common program between the anchor and the vendor
    $program_vendor_configurations = ProgramVendorConfiguration::where('buyer_id', $purchase_order->anchor_id)
      ->where('company_id', $purchase_order->company_id)
      ->get();

    return response()->json([
      'purchase_order' => $purchase_order,
      'program_vendor_configurations' => $program_vendor_configurations,
    ]);
  }

  public function downloadPurchaseOrder(PurchaseOrder $purchase_order)
  {
    $header = asset('assets/img/branding/logo-name.png');

    $pdf = Pdf::loadView('pdf.purchase-order', [
      'purchase_order' => $purchase_order->load('purchaseOrderItems', 'anchor', 'company'),
      'header' => $header,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('Purchase_Order_' . $purchase_order->po_number . '.pdf');
  }

  public function updateStatus(Request $request)
  {
    $request->validate([
      'purchase_order_id' => ['required'],
      'status' => ['required', 'in:accepted,rejected'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    $purchase_order = PurchaseOrder::find($request->purchase_order_id);

    if ($request->status == 'accepted') {
      $purchase_order->update([
        'status' => $request->status,
      ]);

      $users = User::whereIn('id', $purchase_order->anchor->users->pluck('id'))->get();

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoAcceptance', [
          'purchase_order_id' => $purchase_order->id,
          'type' => 'factoring',
        ]);
      }
    } else {
      $purchase_order->update([
        'status' => $request->status,
        'rejection_reason' => $request->rejection_reason,
      ]);

      $users = User::whereIn('id', $purchase_order->anchor->users->pluck('id'))->get();

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoRejection', [
          'purchase_order_id' => $purchase_order->id,
          'type' => 'factoring',
        ]);
      }
    }

    return response()->json(['message' => 'Updated Purchase Order'], 200);
  }

  // Factoring
  public function factoringIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $purchase_orders = PurchaseOrder::where('company_id', $current_company->company_id)->count();
    $pending_purchase_orders = PurchaseOrder::where('company_id', $current_company->company_id)
      ->where('status', 'pending')
      ->count();

    return view(
      'content.anchor.factoring.purchase-orders.index',
      compact('purchase_orders', 'pending_purchase_orders')
    );
  }

  public function factoringPurchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $po_number = $request->query('po_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'anchor')
      ->where('company_id', $current_company->company_id)
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($po_number && $po_number != '', function ($query) use ($po_number) {
        $query->whereDate('purchase_order_number', 'LIKE', '%' . $po_number . '%');
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('delivery_from', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('delivery_to', $to_date);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        if ($status == 'invoiced') {
          $query->whereHas('invoices');
        } else {
          $query->whereDate('status', $status);
        }
      })
      ->latest()
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function factoringPendingPurchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'anchor')
      ->where('status', 'pending')
      ->where('company_id', $current_company->company_id)
      ->paginate($per_page);

    foreach ($purchase_orders as $purchase_order) {
      $purchase_order['total'] = $purchase_order->total_amount;
    }

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function convertToInvoice(PurchaseOrder $purchase_order)
  {
    $purchase_order->load('purchaseOrderItems', 'anchor');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $anchor_programs = ProgramCompanyRole::where(['role_id' => $anchor_role->id, 'company_id' => $company->id])
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', 'Factoring With Recourse')->orWhere('name', 'Factoring Without Recourse');
        });
      })
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::with('program.anchor')
      ->whereIn('program_id', $anchor_programs)
      ->where('buyer_id', $purchase_order->anchor->id)
      ->get();

    $taxes = [];

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

    return view('content.anchor.factoring.purchase-orders.convert', [
      'purchase_order' => $purchase_order,
      'programs' => $programs,
      'taxes' => $taxes,
      'currencies' => $currencies,
    ]);
  }

  public function checkPurchaseOrderNumber($number)
  {
    $purchase_order = PurchaseOrder::where('purchase_order_number', $number)->first();

    if ($purchase_order) {
      return response()->json(['exists' => true], 400);
    }

    return response()->json(['exists' => false], 200);
  }

  public function program(Program $program)
  {
    return response()->json(['program' => $program], 200);
  }

  // Dealer Financing
  public function dealerIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $purchase_orders = PurchaseOrder::where('anchor_id', $current_company->company_id)->count();
    $pending_purchase_orders = PurchaseOrder::where('anchor_id', $current_company->company_id)
      ->where('status', 'pending')
      ->count();

    return view('content.anchor.dealer.purchase-orders.index', compact('purchase_orders', 'pending_purchase_orders'));
  }

  public function dealerPurchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $po_number = $request->query('po_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'anchor')
      ->where('anchor_id', $current_company->company_id)
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($po_number && $po_number != '', function ($query) use ($po_number) {
        $query->whereDate('purchase_order_number', 'LIKE', '%' . $po_number . '%');
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('delivery_from', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('delivery_to', $to_date);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        if ($status == 'invoiced') {
          $query->whereHas('invoices');
        } else {
          $query->whereDate('status', $status);
        }
      })
      ->latest()
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function dealerPendingPurchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'anchor')
      ->where('status', 'pending')
      ->where('anchor_id', $current_company->company_id)
      ->paginate($per_page);

    foreach ($purchase_orders as $purchase_order) {
      $purchase_order['total'] = $purchase_order->total_amount;
    }

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function dealerConvertToInvoice(PurchaseOrder $purchase_order)
  {
    $purchase_order->load('purchaseOrderItems', 'anchor');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $anchor_programs = ProgramCompanyRole::where(['role_id' => $anchor_role->id, 'company_id' => $company->id])
      ->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      })
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::with('program.anchor')
      ->whereIn('program_id', $anchor_programs)
      ->where('buyer_id', $purchase_order->anchor->id)
      ->get();

    $taxes = [];

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

    return view('content.anchor.dealer.purchase-orders.convert', [
      'purchase_order' => $purchase_order,
      'programs' => $programs,
      'taxes' => $taxes,
      'currencies' => $currencies,
    ]);
  }

  public function dealerCheckPurchaseOrderNumber($number)
  {
    $purchase_order = PurchaseOrder::where('purchase_order_number', $number)->first();

    if ($purchase_order) {
      return response()->json(['exists' => true], 400);
    }

    return response()->json(['exists' => false], 200);
  }

  public function dealerProgram(Program $program)
  {
    return response()->json(['program' => $program], 200);
  }
}
