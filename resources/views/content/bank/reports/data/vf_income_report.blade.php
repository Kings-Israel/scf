@extends('layouts/layoutMaster')

@section('title', 'VF - Income Report ')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
  .tab-content {
    padding: 0px !important;
  }
  .nav-tabs .nav-link {
    font-weight: 900;
    font-size: 14px;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>

</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light"> VF - Income Report  </span>
</h4>

<div class="card">
  <div class="p-3 d-flex justify-content-between">
    <div class="w-75 row">

    </div>
    <div class="d-flex justify-content-end w-25">
      <div class="">
        <select class="form-select" id="exampleFormControlSelect1">
          <option value="1">10</option>
          <option value="2">20</option>
          <option value="3">50</option>
        </select>
      </div>
      <div class="mx-2">
        <button type="button" class="btn btn-primary"><i class='ti ti-download ti-sm'></i></button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr class="">
          <th>Dealer</th>
          <th>Invoice Number</th>
          <th>Payment Request Date</th>
          <th>Processing Fee</th>
          <th>Status</th>
          <th>Payment Account</th>
          <th>Amount</th>
          <th>Reference Number</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach ($incomeData as $payment)
          <tr>
            <td>{{ $payment->invoice->program->name  }}</td>
            <td>{{ $payment->invoice->invoice_number }}</td>
            <td>{{ $payment->payment_request_date }}</td>
            <td>{{ $payment->processing_fee }}</td>
            <td>{{ $payment->status }}</td>
            <td>{{ $payment->payment_account }}</td>
            <td class="text-success"> {{ $payment->amount }} </td>
            <td>{{ $payment->reference_number }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <nav aria-label="Page navigation" class="mt-2 mx-2">
    <ul class="pagination justify-content-end">
      <li class="page-item prev">
        <a class="page-link" href="javascript:void(0);"><i class="ti ti-chevrons-left ti-xs"></i></a>
      </li>

      <li class="page-item next">
        <a class="page-link" href="javascript:void(0);"><i class="ti ti-chevrons-right ti-xs"></i></a>
      </li>
    </ul>
  </nav>
</div>
@endsection
