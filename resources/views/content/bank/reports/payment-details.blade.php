@extends('layouts/layoutMaster')

@section('title', 'Payment Details')

@section('vendor-style')
@endsection

@section('page-style')

@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>

</script>
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="fw-light">{{ __('Payment Details') }}</span>
  </h4>
  <div class="d-flex">
    <a target="_blank" href="{{ route('reports.payment-details.daily-discount', ['bank' => $bank, 'invoice' => $invoice]) }}" class="btn btn-primary btn-sm mx-1">{{ __('View Daily Discount Details') }}</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div
        class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Payment Amount') }}:</h6>
        <h5 class="px-2 text-right">
            {{ number_format($invoice->disbursed_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Principle Balance:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->disbursed_amount - $invoice->paid_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Due Date:</h6>
        <h5 class="px-2 text-right">{{ Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Total Penal Amount:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->overdue_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Total Accrued Discount:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->discount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Balance Accrued Discount:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->discount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Total Penal Amount:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->overdue_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Balance Penal Amount:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->overdue_amount - $invoice->paid_overdue_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Total Outstanding:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($invoice->overdue_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Payment Reference No:</h6>
        <h5 class="px-2 text-right">
          {{ $invoice->paymentRequests->first()->reference_number }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">Payment A/C Number:</h6>
        <h5 class="px-2 text-right">
          {{ $invoice->program->vendorConfigurations->where('company_id', $invoice->company_id)->first()->payment_account_number }}
        </h5>
      </div>
    </div>
  </div>
</div>
<hr>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Date')}}</th>
            <th>{{ __('Particulars')}}</th>
            <th>{{ __('Amount')}}</th>
            <th>{{ __('Line Balance')}}</th>
            <th>{{ __('Discount Type')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @foreach ($payment_requests as $payment_request)
            @foreach ($payment_request->cbsTransactions as $cbs_transaction)
              <tr>
                <td>{{ Carbon\Carbon::parse($cbs_transaction->paid_date)->format('d M Y')  }}</td>
                <td>
                  @if ($cbs_transaction->transaction_type == 'Payment Disbursement')
                    @if ($invoice->program->programType->name == 'Dealer Financing')
                      <span>Drawdown against {{ $invoice->invoice_number }}</span>
                    @else
                      <span>Disbursement for {{ $invoice->invoice_number }}</span>
                    @endif
                  @else
                    @if ($cbs_transaction->transaction_type == 'Overdue Account')
                      <span>Penal Payment againt {{ $invoice->invoice_number }}</span>
                    @elseif($cbs_transaction->transaction_type == 'Repayment' || $cbs_transaction->transaction_type == 'Bank Invoice Payment')
                      <span>Repayment for {{ $invoice->invoice_number }}</span>
                    @endif
                  @endif
                  @if ($cbs_transaction->transaction_type == 'Fees/Charges')
                    <span>Fees/Charges Payment againt {{ $invoice->invoice_number }}</span>
                  @endif
                  @if ($cbs_transaction->transaction_type == 'Accrual/Posted Interest')
                    <span>Discount Payment againt {{ $invoice->invoice_number }}</span>
                  @endif
                </td>
                <td>{{ number_format($cbs_transaction->amount) }}</td>
                <td>{{ number_format($cbs_transaction->amount) }}</td>
                <td>
                  @if ($cbs_transaction->transaction_type == 'Payment Disbursement')
                    @if ($invoice->program->programType->name == 'Dealer Financing')
                      <span>-</span>
                    @else
                      <span>-</span>
                    @endif
                  @else
                    @if ($cbs_transaction->transaction_type == 'Overdue Account')
                      <span>Penal Payment againt {{ $invoice->invoice_number }}</span>
                    @elseif($cbs_transaction->transaction_type == 'Repayment' || $cbs_transaction->transaction_type == 'Bank Invoice Payment')
                      <span>Repayment for {{ $invoice->invoice_number }}</span>
                    @endif
                  @endif
                </td>
              </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
