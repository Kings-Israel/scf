@extends('layouts/layoutMaster')

@section('title', 'Discount Account Details')

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
<h4 class="fw-bold my-1">
  <span class="fw-light">{{ __('Discount Account Details')}}</span>
</h4>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Date')}}</th>
            <th>{{ __('Particulars')}}</th>
            <th>{{ __('Amount')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          {{-- @foreach ($cbs_transactions as $cbs_transaction)
            <tr>
              <td>{{ Carbon\Carbon::parse($cbs_transaction->paid_date)->format('d M Y')  }}</td>
              <td>
                @if ($cbs_transaction->transaction_type == 'Payment Disbursement')
                  @if ($cbs_transaction->paymentRequest->invoice->program->programType->name == 'Dealer Financing')
                    <span>Drawdown against {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                  @else
                    <span>Disbursement for {{ $cbs_transaction->paymentrequest->invoice->invoice_number }}</span>
                  @endif
                @else
                  @if ($cbs_transaction->transaction_type == 'Fees/Charges')
                    @if (Carbon\Carbon::parse($cbs_transaction->paid_date)->greaterThan(Carbon\Carbon::parse($cbs_transaction->paymentRequest->invoice->due_date)))
                      <span>Penal Payment againt {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                    @else
                      <span>Repayment for {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                    @endif
                  @endif
                @endif
              </td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>-</td>
              <td>-</td>
            </tr>
          @endforeach --}}
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
