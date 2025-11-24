@extends('layouts/layoutMaster')

@section('title', 'Total Outstanding Payments')

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
  <span class="fw-light">{{ __('Total Outstanding Payments')}}</span>
</h4>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Particulars')}}</th>
            <th>{{ __('Drawdown Date')}}</th>
            <th>{{ __('Overdue Date')}}</th>
            <th>{{ __('Principle Balance')}}</th>
            <th>{{ __('Fees/Charges Balance')}}</th>
            <th>{{ __('Accrued Discount Balance')}}</th>
            <th>{{ __('Accrued Penal Discount Balance')}}</th>
            <th>{{ __('Total Outstanding')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @foreach ($invoices as $invoice)
            <tr>
              <td>{{ $invoice->invoice_number }}</td>
              <td>{{ Carbon\Carbon::parse($invoice->disbursement_date)->format('d M Y')  }}</td>
              <td>{{ Carbon\Carbon::parse($invoice->due_date)->format('d M Y')  }}</td>
              <td class="text-success">{{ number_format($invoice->invoice_total_amount) }}</td>
              <td class="text-success">{{ number_format($invoice->paymentRequests->where('created_at', '>', $invoice->due_date)->sum('amount')) }}</td>
              <td class="text-success">0</td>
              <td class="text-success">0</td>
              <td class="text-success">{{ number_format($invoice->overdue_amount) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
