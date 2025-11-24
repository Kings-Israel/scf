@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'Fund Planner')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
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
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">Fund Planner</span>
</h4>

<div class="card">
  <div class="card-header">
    <span>Limit Details</span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th></th>
            <th>Sanctioned Limit</th>
            <th>Drawing Power</th>
            <th>Utilized Limit</th>
            <th>Pipeline Requests</th>
            <th>Excess Credit</th>
            <th>Available Limit</th>
            <th>OD Expiry Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap">
            <td><span class="fw-medium">Deveint Anchor</span></td>
            <td class="text-success">Ksh {{ number_format(3200000) }}</td>
            <td class="text-success">Ksh {{ number_format(1200000) }}</td>
            <td class="text-success">Ksh {{ number_format(2400000) }}</td>
            <td class="text-success">Ksh {{ number_format(2050000) }}</td>
            <td class="text-success">Ksh {{ number_format(134000) }}</td>
            <td class="text-success">Ksh {{ number_format(300000) }}</td>
            <td>23 Nov 2023</td>
            <td>
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#requestFinancing">Request Financing</a>
                  <a class="dropdown-item" href="javascript:void(0);">View</a>
                  <a class="dropdown-item" href="javascript:void(0);">Print</a>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<br>

<div class="card">
  <div class="card-header">
    <span>Fund Planner For Vendor Financing</span>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-sm-6">
        <label class="form-label" for="anchor">Select Anchor</label>
        <select class="form-select" id="exampleFormControlSelect">
          <option value="">Deveint Anchor</option>
        </select>
      </div>
      <div class="col-sm-6">
        <label class="form-label" for="pi">Payment Instruction Eligible For Financing</label>
        <input type="text" id="pi" class="form-control" aria-label="pi" />
      </div>
      <div class="col-sm-6">
        <label for="html5-date-input" class="col-form-label">Date</label>
        <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
      </div>
      <div class="col-sm-6">
        <label for="total-amount" class="col-form-label">Total Amount</label>
        <input class="form-control" type="number" id="total_amount" placeholder="Ksh" />
      </div>
      <div class="col-sm-6">
        <label for="actual-remittance" class="col-form-label">Actual Remittance</label>
        <input class="form-control" type="number" id="actual_amount" />
      </div>
    </div>
    <div class="d-flex my-2">
      <button class="btn btn-primary"> <span class="align-middle d-sm-inline-block d-none me-sm-1">Submit</span></button>
      <button class="btn btn-label-secondary mx-1">
        <span class="align-middle d-sm-inline-block d-none">Cancel</span>
      </button>
    </div>
  </div>
</div>

<br>

<div class="card">
  <div class="card-header">
    <span>Payment Instruction eligible for Financing (from YofInvoice)</span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>
              <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </th>
            <th></th>
            <th>Invoice No</th>
            <th>Invoice Date</th>
            <th>Invoice Amount</th>
            <th>Due Date</th>
            <th>PI Amount</th>
            <th>Eligible Amount</th>
            <th>Progress Status</th>
            <th>Request Finance</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap">
            <td>
              <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </td>
            <td></td>
            <td class="text-primary text-decoration-underline">Inv_2343</td>
            <td>23 Nov 2023</td>
            <td class="text-success">Ksh {{ number_format(7000) }}</td>
            <td>23 Dec 2023</td>
            <td class="text-success">Ksh {{ number_format(7000) }}</td>
            <td class="text-success">Ksh {{ number_format(7000) }}</td>
            <td></td>
            <td>
              <i class='text-success ti ti-premium-rights ti-sm'></i>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection
