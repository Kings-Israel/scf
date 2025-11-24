@extends('layouts/layoutMaster')

@section('title', 'Daily Discount')

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
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Daily Discount')}}</span>
</h4>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Date')}}</th>
            <th>{{ __('Invoice No')}}.</th>
            <th>{{ __('Balance Amount')}}</th>
            <th>{{ __('Discount Rate')}} (%)</th>
            <th>{{ __('Discount Amount')}}</th>
            <th>{{ __('Penal Rate')}} (%)</th>
            <th>{{ __('Penal Amount')}}</th>
            <th>{{ __('Cumulative Discount')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @foreach ($cbs_transactions as $cbs_transaction)
            <tr>
              <td>{{ $cbs_transaction->created_at->format('d M Y')  }}</td>
              <td>
                {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}
              </td>
              <td>{{ number_format($cbs_transaction->paymentRequest->invoice->disbursed_amount - $cbs_transaction->paymentRequest->invoice->paid_amount) }}</td>
              <td>{{ $cbs_transaction->paymentRequest->invoice->program->vendorConfigurations->where('company_id', $cbs_transaction->paymentRequest->invoice->company_id)->first()->total_roi }}</td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>{{ $cbs_transaction->paymentRequest->invoice->program->discountDetails->first()->penal_discount_on_principle }}</td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>{{ number_format($cbs_transaction->paymentRequest->invoice->calculateDailyInterest($cbs_transaction->created_at)) }}</td>
              <td>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
