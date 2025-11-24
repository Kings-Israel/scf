<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\ProgramVendorFee;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentRequestResource;
use App\Jobs\SendMail;
use App\Models\NoaTemplate;
use App\Models\Program;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Notifications\PaymentRequestNotification;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialRequestsController extends Controller
{
  public function index()
  {
    return view('content.vendor.finance-requests.index');
  }

  public function financeRequests(Request $request)
  {
    $anchor = $request->query('anchor');
    $invoice_number = $request->query('invoice');
    $per_page = $request->query('per_page');
    $status = $request->query('status');

    // Get active company
    $current_company = auth()
      ->user()
      ->activeVendorCompany()
      ->first();

    $finance_requests = PaymentRequest::where('processing_fee', '!=', null)
      // Filter to not show transactions for anchor bearing fees and discount
      ->with('paymentAccounts', 'companyApprovals')
      ->whereHas('invoice', function ($query) use ($current_company, $anchor) {
        $query->where('company_id', $current_company->company_id)->whereHas('program', function ($query) use ($anchor) {
          $query
            ->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            })
            ->when($anchor && $anchor != '', function ($query) use ($anchor) {
              $query->whereHas('anchor', function ($query) use ($anchor) {
                $query->where('name', 'LIKE', '%' . $anchor . '%');
              });
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

  public function updateFinanceRequest(PaymentRequest $payment_request, Request $request)
  {
    $request->validate([
      'status' => ['required', 'in:approved,rejected'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    if ($request->status == 'approved') {
      $payment_request->companyApprovals()->delete();

      $payment_request->invoice->program->bank->notify(new PaymentRequestNotification($payment_request));

      activity($payment_request->invoice->program->bank->id)
        ->causedBy(auth()->user())
        ->performedOn($payment_request)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Dealer'])
        ->log('requested financing');

      $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
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
        ->first();

      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $payment_request->invoice->company_id)
        ->where('program_id', $payment_request->invoice->program_id)
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

        SendMail::dispatchAfterResponse($bank_user->email, 'PaymentRequested', [
          'payment_request_id' => $payment_request->id,
          'link' => config('app.url') . '/' . $payment_request->invoice->program->bank->url,
          'type' => 'vendor_financing',
          'noa' => $noa_text != null ? $pdf->output() : null,
        ]);
      }

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Financing Request updated successfully');

      return back();
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

      if (request()->wantsJson()) {
        return response()->json(['payment_request' => $payment_request]);
      }

      toastr()->success('', 'Financing Request updated successfully');

      return back();
    }
  }
}
