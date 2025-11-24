@extends('layouts/layoutMaster')

@section('title', 'OD Daily Interest')

@section('vendor-style')
@endsection

@section('page-style')

@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')

@endsection

@section('content')
<h4 class="fw-bold my-1">
  <span class="fw-light">{{ __('OD Daily Interest')}}</span>
</h4>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Date')}}</th>
            <th>{{ __('Invoice No')}}</th>
            <th>{{ __('Balance Amount')}}</th>
            <th>{{ __('Discount Rate')}}</th>
            <th>{{ __('Discount Amount')}}</th>
            <th>{{ __('Penal Rate')}}</th>
            <th>{{ __('Penal Amount')}}</th>
            <th>{{ __('Cumulative Discount')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @foreach ($cbs_transactions as $cbs_transaction)
            <tr>
              <td>{{ $cbs_transaction->created_at->format('d M Y') }}</td>
              <td>{{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</td>
              <td class="text-success">{{ number_format($cbs_transaction->paymentRequest->invoice->balance) }}</td>
              <td>{{ $program_vendor_configuration->program->vendorDiscountDetails->where('company_id', $program_vendor_configuration->company_id)->first()->total_roi }}%</td>
              <td class="text-success">{{ number_format($cbs_transaction->paymentRequest->discount) }}</td>
              <td>{{ $program_vendor_configuration->program->vendorDiscountDetails->where('company_id', $program_vendor_configuration->company_id)->first()->penal_discount_on_principle }}%</td>
              <td class="text-success">{{ number_format($cbs_transaction->paymentRequest->sum('amount')) }}</td>
              <td class="text-success">{{ number_format($cbs_transaction->paymentRequest->invoice->overdue_amount) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
