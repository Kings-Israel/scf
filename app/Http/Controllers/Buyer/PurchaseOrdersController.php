<?php

namespace App\Http\Controllers\Buyer;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Helpers\Helpers;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Http\Controllers\Controller;
use App\Models\BankConvertionRate;
use App\Models\Program;
use App\Notifications\PurchaseOrderCreated;

class PurchaseOrdersController extends Controller
{
  public function index()
  {
    return view('content.buyer.purchase-orders.index');
  }

  public function purchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $vendor = $request->query('vendor');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');

    $purchase_orders = [];

    // Get active company
    $current_company = auth()->user()->activeBuyerFactoringCompany()->first();

    $company = Company::find($current_company->company_id);

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'company')
      ->where('anchor_id', $company->id)
      ->when($vendor && $vendor != '', function ($query) use ($vendor) {
        $query->whereHas('company', function ($query) use ($vendor) {
          $query->where('name', 'LIKE', '%' . $vendor . '%');
        });
      })
      ->when($from_date && $from_date != '', function ($query) use ($from_date) {
        $query->whereDate('delivery_from', $from_date);
      })
      ->when($to_date && $to_date != '', function ($query) use ($to_date) {
        $query->whereDate('delivery_to', $to_date);
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->whereDate('status', $status);
      })
      ->paginate($per_page);

    foreach ($purchase_orders as $purchase_order) {
      $purchase_order['total'] = $purchase_order->total_amount;
    }

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function create()
  {
    // Get active company
    $current_company = auth()->user()->activeBuyerFactoringCompany()->first();

    $company = Company::find($current_company->company_id);

    $vendors = [];

    foreach ($company->programs as $program) {
      if ($program->programType->name == Program::VENDOR_FINANCING && ($program->programCode->name == Program::FACTORING_WITH_RECOURSE || $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)) {
        array_push($vendors, $program->getAnchor());
      }
    }

    $vendors = collect($vendors)->unique();

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    $bank_default_currency = Currency::find($company->bank->adminConfiguration?->defaultCurrency);

    // Get Currencies that have a conversion rate set
    $bank_converstion_rates = BankConvertionRate::where('bank_id', $company->bank_id)->where('rate', '!=', 0)->pluck('from_currency');
    if ($bank_converstion_rates->count() > 0) {
      $currencies = Currency::whereIn('code', [$bank_converstion_rates, $bank_default_currency->code])->get();
    } else {
      $currencies = Currency::whereIn('code', [$bank_default_currency->code])->get();
    }

    return view('content.buyer.purchase-orders.create', compact('vendors', 'currencies'));
  }

  public function store(Request $request)
  {
    $request->validate([
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
    ], [
      'company_id.required' => 'Select an anchor'
    ]);

    // Get active company
    $current_company = auth()->user()->activeBuyerFactoringCompany()->first();

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
      'status' => 'pending'
    ]);

    foreach ($request->item as $key => $item) {
      if (array_key_exists($key, $request->quantity) && (float) str_replace(',', '', $request->quantity[$key]) > 0 && array_key_exists($key, $request->unit) && array_key_exists($key, $request->price_per_quantity) && (float) str_replace(',', '', $request->price_per_quantity[$key]) > 0) {
        PurchaseOrderItem::create([
          'purchase_order_id' => $purchase_order->id,
          'item' => $item,
          'quantity' => array_key_exists($key, $request->quantity) ? str_replace(',', '', $request->quantity[$key]) : NULL,
          'unit' => array_key_exists($key, $request->unit) ? $request->unit[$key] : NULL,
          'price_per_quantity' => array_key_exists($key, $request->price_per_quantity) ? str_replace(',', '', $request->price_per_quantity[$key]) : NULL,
          'description' => array_key_exists($key, $request->description) ? $request->description[$key] : NULL,
        ]);
      }
    }

    if ($purchase_order_settings && !$purchase_order_settings->maker_checker_creating_updating) {
      $purchase_order->approvals()->create([
        'user_id' => auth()->id()
      ]);

      $purchase_order->update([
        'status' => 'pending_acceptance'
      ]);

      $users = User::whereIn('id', $purchase_order->company->users->pluck('id'))->get();

      $purchase_order->company->notify(new PurchaseOrderCreated($purchase_order));

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoCreation', ['purchase_order_id' => $purchase_order->id, 'type' => 'vendor_financing']);
      }
    } else {
      foreach ($company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoCreation', ['purchase_order_id' => $purchase_order->id, 'type' => 'vendor_financing']);
      }
      // requires maker checker approval
      // add maker approval
      $purchase_order->approvals()->create([
        'user_id' => auth()->id()
      ]);
    }

    toastr()->success('', 'Purchase Order created successfully');

    return redirect()->route('buyer.purchase-orders.index');
  }

  public function approve(Request $request)
  {
    $request->validate([
      'purchase_order_id' => ['required'],
      'status' => ['required', 'in:approve,rejected'],
      'rejection_reason' => ['required_if:status,rejected']
    ]);

    $purchase_order = PurchaseOrder::find($request->purchase_order_id);

    if ($request->status == 'approve') {
      $purchase_order->approvals()->create([
        'user_id' => auth()->id(),
      ]);

      $purchase_order->update([
        'status' => 'pending_acceptance'
      ]);

      $users = User::whereIn('id', $purchase_order->company->users->pluck('id'))->get();

      $purchase_order->company->notify(new PurchaseOrderCreated($purchase_order));

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoCreation', ['purchase_order_id' => $purchase_order->id, 'type' => 'vendor_financing']);
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
      'purchase_orders' => ['required'],
      'status' => ['required'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    foreach ($request->purchase_orders as $order) {
      $purchase_order = PurchaseOrder::find($order);

      if ($request->status == 'approve') {
        $purchase_order->approvals()->create([
          'user_id' => auth()->id(),
        ]);

        $users = User::whereIn('id', $purchase_order->company->users->pluck('id'))->get();

        $purchase_order->company->notify(new PurchaseOrderCreated($purchase_order));

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PoCreation', ['purchase_order_id' => $purchase_order->id, 'type' => 'vendor_financing']);
        }
      } else {
        $purchase_order->update([
          'status' => $request->status,
          'rejection_reason' => $request->rejection_reason,
        ]);
      }
    }
  }

  public function show($purchase_order)
  {
    $purchase_order = PurchaseOrder::with('company', 'anchor', 'purchaseOrderItems')->find($purchase_order);

    if (request()->wantsJson()) {
      return response()->json($purchase_order);
    }

    return $purchase_order;
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
}
