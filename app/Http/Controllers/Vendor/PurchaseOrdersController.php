<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Program;
use App\Models\Currency;
use App\Models\ProgramRole;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use App\Models\PurchaseOrderItem;
use App\Models\ProgramCompanyRole;
use App\Http\Controllers\Controller;
use App\Models\ProgramVendorConfiguration;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrdersController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $purchase_orders = PurchaseOrder::where('company_id', $current_company->company_id)->whereHas('approvals')->count();
    $pending_purchase_orders = PurchaseOrder::where('company_id', $current_company->company_id)
      ->where('status', 'pending')
      ->whereHas('approvals', function ($query) use ($company) {
        $users = $company->users->pluck('id');
        $query->whereNotIn('user_id', $users);
      })
      ->count();

    return view('content.vendor.purchase-orders.index', compact('purchase_orders', 'pending_purchase_orders'));
  }

  public function purchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $po_number = $request->query('po_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()->user()->activeVendorCompany()->first();

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'anchor')
      ->withCount('approvals')
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
          $query->where('status', $status);
        }
      })
      ->orderBy('created_at', 'DESC')
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
    }
  }

  public function pendingPurchaseOrdersData(Request $request)
  {
    $per_page = $request->query('per_page');
    $anchor = $request->query('anchor');
    $po_number = $request->query('po_number');
    $from_date = $request->query('from_date');
    $to_date = $request->query('to_date');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()->user()->activeVendorCompany()->first();

    $purchase_orders = PurchaseOrder::with('purchaseOrderItems', 'anchor')
      ->where('status', 'pending')
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
      ->latest()
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['purchase_orders' => $purchase_orders], 200);
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

  public function update(Request $request)
  {
    $request->validate([
      'purchase_order_id' => ['required'],
      'status' => ['required', 'in:accepted,rejected'],
      'rejection_reason' => ['required_if:status,rejected']
    ]);

    $purchase_order = PurchaseOrder::find($request->purchase_order_id);

    if ($request->status == 'accepted') {
      // If purchase order has one approval
      $purchase_order->update([
        'status' => $request->status,
      ]);

      $users = User::whereIn('id', $purchase_order->anchor->users->pluck('id'))->get();

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoAcceptance', ['purchase_order_id' => $purchase_order->id]);
      }
    } else {
      $purchase_order->update([
        'status' => $request->status,
        'rejection_reason' => $request->rejection_reason,
      ]);

      $users = User::whereIn('id', $purchase_order->anchor->users->pluck('id'))->get();

      foreach ($users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'PoRejection', ['purchase_order_id' => $purchase_order->id]);
      }
    }

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Purchase order updated successfully']);
    }

    return back();
  }

  public function bulkUpdateStatus(Request $request)
  {
    $request->validate([
      'purchase_orders' => ['required'],
      'status' => ['required', 'in:accepted,rejected'],
      'rejection_reason' => ['required_if:status,rejected']
    ]);

    foreach ($request->purchase_orders as $order) {
      $purchase_order = PurchaseOrder::find($order);

      if ($request->status == 'accepted') {
        $purchase_order->update([
          'status' => $request->status,
        ]);

        $users = User::whereIn('id', $purchase_order->anchor->users->pluck('id'))->get();

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PoAcceptance', ['purchase_order_id' => $purchase_order->id, 'type' => 'factoring']);
        }
      } else {
        $purchase_order->update([
          'status' => $request->status,
          'rejection_reason' => $request->rejection_reason,
        ]);

        $users = User::whereIn('id', $purchase_order->anchor->users->pluck('id'))->get();

        foreach ($users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PoRejection', ['purchase_order_id' => $purchase_order->id, 'type' => 'factoring']);
        }
      }
    }

    return response()->json(['message' => 'Updated Purchase Order'], 200);
  }

  public function convertToInvoice(PurchaseOrder $purchase_order)
  {
    $purchase_order->load('purchaseOrderItems', 'anchor');

    // Get active company
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $vendor_programs = ProgramCompanyRole::where(['role_id' => $vendor_role->id, 'company_id' => $company->id])
      ->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      })
      ->pluck('program_id');

    $programs = ProgramVendorConfiguration::with('program.anchor')->whereIn('program_id', $vendor_programs)->where('company_id', $company->id)->get();

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
        $currency = explode(',', str_replace("\"", "", $company->bank->adminConfiguration->selectedCurrencyIds));
      } elseif ($company->bank->adminConfiguration->defaultCurrency) {
        $currency = [$company->bank->adminConfiguration->defaultCurrency];
      }
    }

    $currencies = Currency::whereIn('id', $currency)->get();

    return view('content.vendor.purchase-orders.convert', ['purchase_order' => $purchase_order, 'programs' => $programs, 'taxes' => $taxes, 'currencies' => $currencies]);
  }

  public function edit(PurchaseOrder $purchase_order)
  {
    $purchase_order->load('purchaseOrderItems');

    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $currency = [Currency::where('name', 'Kenyan Shilling')->first()?->id];

    if ($company->bank->adminConfiguration) {
      if ($company->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = explode(',', str_replace("\"", "", $company->bank->adminConfiguration->selectedCurrencyIds));
      } elseif ($company->bank->adminConfiguration->defaultCurrency) {
        $currency = [$company->bank->adminConfiguration->defaultCurrency];
      }
    }

    $currencies = Currency::whereIn('id', $currency)->get();

    return view('content.vendor.purchase-orders.edit', ['purchase_order' => $purchase_order, 'currencies' => $currencies]);
  }

  public function updatePO(Request $request, PurchaseOrder $purchase_order)
  {
    $request->validate([
      'purchase_order_number' => ['required', Rule::unique('purchase_orders')->ignore($purchase_order)],
      'duration_from' => 'required',
      'duration_to' => 'required',
      'delivery_date' => 'required',
      'delivery_address' => 'required',
      'invoice_payment_terms' => 'required',
      'item' => ['required', 'array', 'min:1'],
      'quantity' => ['required', 'array', 'min:1'],
      'quantity.*' => ['min:1'],
      'unit' => ['required', 'array', 'min:1'],
      'price_per_quantity' => ['required', 'array', 'min:1'],
      'price_per_quantity.*' => ['min:1'],
    ]);

    // Get active company
    $current_company = auth()->user()->activeVendorCompany()->first();

    $company = Company::find($current_company->company_id);

    $purchase_order->update([
      'purchase_order_number' => $request->purchase_order_number,
      'currency' => $request->currency,
      'duration_from' => Carbon::parse($request->duration_from)->format('Y-m-d'),
      'duration_to' => Carbon::parse($request->duration_to)->format('Y-m-d'),
      'delivery_date' => Carbon::parse($request->delivery_date)->format('Y-m-d'),
      'delivery_address' => $request->delivery_address,
      'invoice_payment_terms' => $request->invoice_payment_terms,
      'remarks' => $request->remarks,
    ]);

    $purchase_order->purchaseOrderItems()->delete();

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

    toastr()->success('', 'Purchase Order updated successfully');

    return redirect()->route('vendor.purchase-orders.index');
  }
}
