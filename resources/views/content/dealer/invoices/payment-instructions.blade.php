@extends('layouts/buyerLayoutMaster')

@section('title', 'Invoices')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/flatpickr/flatpickr.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
@endsection

@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/app-calendar.css')}}" />
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
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
<script src="{{asset('assets/vendor/libs/flatpickr/flatpickr.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">Payment Instructions</span>
</h4>

<div class="card">
  <!-- Invoice List Table -->
  <div class="p-3 d-flex justify-content-between">
    <div class="w-75 row">
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Vendor" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="PO No" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Amount" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
      </div>
      <div class="col-2">
        <select class="form-select" id="exampleFormControlSelect">
          <option value="">Status</option>
          <option value="1">Pending</option>
          <option value="2">Approved</option>
          <option value="3">Denied</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-6">
        <select class="form-select w-100" id="exampleFormControlSelect1">
          <option value="1">10</option>
          <option value="2">20</option>
          <option value="3">50</option>
        </select>
      </div>
      <div class="col-2">
        <button type="button" class="btn btn-primary"><i class='ti ti-download ti-sm'></i></button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr class="">
          <th>Invoice No.</th>
          <th>Disbursement Date</th>
          <th>Due Date</th>
          <th>Invoice Amount</th>
          <th>Expiry Date</th>
          <th>Financing Date</th>
          <th>Repayment Status</th>
          <th>Anchor</th>
          <th>Vendor</th>
          <th>Discount Value</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap">
          <td class="text-primary text-decoration-underline">Inv292</td>
          <td class="">23 Nov 2023</td>
          <td class="">23 Dec 2023</td>
          <td class="text-success">Ksh {{ number_format(37000) }}</td>
          <td>25 Dec 2023</td>
          <td>21 Dec 2023</td>
          <td><span class="badge bg-label-success me-1">Closed</span></td>
          <td><span class="">Jicho Pevu</span></td>
          <td><span class="">Laila G</span></td>
          <td class="text-success">Ksh {{ number_format(3500) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <nav aria-label="Page navigation" class="mt-2">
    <ul class="pagination justify-content-end">
      <li class="page-item prev">
        <a class="page-link" href="javascript:void(0);"><i class="ti ti-chevrons-left ti-xs"></i></a>
      </li>
      <li class="page-item">
        <a class="page-link" href="javascript:void(0);">1</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="javascript:void(0);">2</a>
      </li>
      <li class="page-item active">
        <a class="page-link" href="javascript:void(0);">3</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="javascript:void(0);">4</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="javascript:void(0);">5</a>
      </li>
      <li class="page-item next">
        <a class="page-link" href="javascript:void(0);"><i class="ti ti-chevrons-right ti-xs"></i></a>
      </li>
    </ul>
  </nav>
</div>
@endsection
