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
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">Pending Invoices</span>
</h4>

<div class="row match-height">
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">378</h5>
          <small>Invoices</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-primary rounded-pill p-2">
            <i class='ti ti-file ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100 border border-primary">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">378</h5>
          <small>Pending Invoices</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-success rounded-pill p-2">
            <i class='ti ti-eye ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-2 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="badge rounded-pill p-2 bg-label-primary mb-2"><i class="ti ti-upload ti-sm"></i></div>
        <h6 class="card-title mb-2">Upload Invoices</h6>
      </div>
    </div>
  </div>
</div>

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
      <div class="col-3">
        <select class="form-select w-100" id="exampleFormControlSelect1">
          <option value="1">10</option>
          <option value="2">20</option>
          <option value="3">50</option>
        </select>
      </div>
      <div class="col-2">
        <button type="button" class="btn btn-primary"><i class='ti ti-download ti-sm'></i></button>
      </div>
      <div class="col-7 d-flex justify-content-end">
        {{-- <a href="{{ route('invoices-create') }}">
          <button type="button" class="btn btn-primary"><i class='ti ti-plus ti-sm'></i>Create Invoice</button>
        </a> --}}
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr class="">
          <th>Anchor</th>
          <th>PO No.</th>
          <th>Invoice No.</th>
          <th>Invoice Amount</th>
          <th>Invoice Date</th>
          <th>Due Date</th>
          <th>Invoice Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap">
          <td><span class="fw-medium">Jicho Pevu</span></td>
          <td class="text-primary text-decoration-underline">Zak32</td>
          <td class="text-primary text-decoration-underline">Inv292</td>
          <td class="text-success">Ksh {{ number_format(3000) }}</td>
          <td>23 Nov 2023</td>
          <td>24 Dec 2023</td>
          <td><span class="badge bg-label-success me-1">Approved</span></td>
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
          <div class="modal fade" id="requestFinancing" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel3">Request Finance</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <h6>Step 1: Review Invoice Details</h6>
                  <div class="row">
                    <div class="col-6 mb-3 d-flex justify-content-between">
                      <span>Invoice Amount</span>
                      <span class="text-success">Ksh {{ number_format(15000) }}</span>
                    </div>
                    <div class="col-6 mb-3 d-flex justify-content-between">
                      <span>Due Date</span>
                      <span class="text-success">24 Dec 2023</span>
                    </div>
                    <div class="col-6 mb-3 d-flex justify-content-between">
                      <span>PI Amount (A)</span>
                      <span class="text-success">Ksh {{ number_format(15000) }}</span>
                    </div>
                    <div class="col-6 mb-3 d-flex justify-content-between">
                      <span>Eligiblity</span>
                      <span class="text-success">100%</span>
                    </div>
                    <div class="col-6 mb-3 d-flex justify-content-between">
                      <span>Eligible Amount</span>
                      <span class="text-success">Ksh {{ number_format(15000) }}</span>
                    </div>
                    <div class="col-6 mb-3 d-flex justify-content-between">
                      <span>Eligible For Finance</span>
                      <span class="text-success">Ksh {{ number_format(15000) }}</span>
                    </div>
                  </div>
                  <br>
                  <div class="row g-2">
                    <h6>Step 2: Select Early Payment Date</h6>
                    <div class="col-6 mb-0">
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Early Payment Date</span>
                        <span class="">10 Dec 2023</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Days To Payment</span>
                        <span class="">9</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Annual Discount Rate (%)</span>
                        <span class="">20</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Processing Fees</span>
                        <span class="text-success">Ksh 0</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Discount Amount</span>
                        <span class="text-success">Ksh 80</span>
                      </div>
                    </div>
                    <div class="col-6">
                      {{-- <div class="inline-calendar"></div> --}}
                    </div>
                  </div>
                  <br>
                  <div class="row g-2">
                    <h6>Step 3: Review Offer and Submit Request</h6>
                    <div class="col-12 mb-0">
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Early Payment Date</span>
                        <span class="">10 Dec 2023</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Eligible Amount</span>
                        <span class="text-success">Ksh 15,000</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Request Amount (B)</span>
                        <input type="text" id="nameLarge" class="form-control w-25 text-success" placeholder="Enter Amount" value="15000">
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Actual Remittance Amount</span>
                        <span class="text-success">Ksh 14,916</span>
                      </div>
                      <div class="d-flex justify-content-between border-bottom mb-3">
                        <span>Credit To</span>
                        <select class="form-select w-25" id="exampleFormControlSelect">
                          <option value="1">80495 - KCB</option>
                          <option value="2">98985 - DTB</option>
                        </select>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Balance Invoice Amount Paid on Maturity (A-B)</span>
                        <span class="">Ksh 0</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary">Submit</button>
                </div>
              </div>
            </div>
          </div>
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
