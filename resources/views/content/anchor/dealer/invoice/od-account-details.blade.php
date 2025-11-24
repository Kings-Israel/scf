@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'OD Accounts')

@section('vendor-style')
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
    position: static;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')

@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold mb-2">
    <span class="fw-light">{{ __('OD Account Details')}}</span>
  </h4>
</div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Payment/OD Account Number')}}</h5>
          <h6>{{ $program_vendor_configuration->payment_account_number }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Dealer')}}</h5>
          <h6>
            {{ $program_vendor_configuration->company->name }}
          </h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Sanctioned Limit')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->sanctioned_limit, 2) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Available Limit')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->sanctioned_limit - ($program_vendor_configuration->pipeline + $program_vendor_configuration->utilized), 2) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Utilized Limit')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->utilized, 2) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Pipleine Requests')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->pipeline, 2) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('OD Expiry Date')}}</h5>
          <h6>{{ Carbon\Carbon::parse($program_vendor_configuration->limit_expiry_date)->format('d M Y') }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Total Outstanding')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->overdue_amount, 2) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Overdue Days')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->days_past_due) }}</h6>
        </div>
      </div>
    </div>
  </div>
</div>
<h4 class="fw-bold my-1">
  <span class="fw-light">{{ __('OD Account Summary')}}</span>
</h4>
<div id="factoring-cbs-transactions">
  <factoring-cbs-transactions program_vendor_configuration={!! $program_vendor_configuration->id !!}></factoring-cbs-transactions>
</div>
{{-- <div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Date')}}</th>
            <th>{{ __('Particulars')}}</th>
            <th>{{ __('Amount')}}</th>
            <th>{{ __('Line Balance')}}</th>
            <th>{{ __('Discount Balance')}}</th>
            <th>{{ __('Penal Discount Balance')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @foreach ($cbs_transactions as $cbs_transaction)
            <tr>
              <td>{{ Carbon\Carbon::parse($cbs_transaction->created_at)->format('d M Y')  }}</td>
              <td>
                @if ($cbs_transaction->transaction_type == 'Payment Disbursement')
                  @if ($cbs_transaction->paymentRequest->invoice->program->programType->name == 'Dealer Financing')
                    <span>Drawdown against {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                  @else
                    <span>Disbursement for {{ $cbs_transaction->paymentrequest->invoice->invoice_number }}</span>
                  @endif
                @endif
                @if ($cbs_transaction->transaction_type == 'Accrual/Posted Interest')
                  <span>Discount Payment for {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                @endif
                @if ($cbs_transaction->transaction_type == 'Fees/Charges')
                  <span>Fees/Charges Payment for {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                @endif
                @if ($cbs_transaction->transaction_type == 'Repayment')
                  <span>Repayment for {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                @endif
                @if ($cbs_transaction->transaction_type == 'Bank Invoice Payment')
                  <span>Repayment for {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                @endif
                @if ($cbs_transaction->transaction_type == 'Overdue Account')
                  <span>Penal Payment againt {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                @endif
              </td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>-</td>
              <td>-</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div> --}}
@endsection
