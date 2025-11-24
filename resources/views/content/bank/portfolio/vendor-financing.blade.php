@extends('layouts/layoutMaster')

@section('title', 'Invoices')

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
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Invoice Details</span>
</h4>

<div class="row match-height">
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100 border border-primary">
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
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">8</h5>
          <small>Pending Invoices</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-warning rounded-pill p-2">
            <i class='ti ti-file-text ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h6>View Uploaded Status</h6>
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
        <div class="badge rounded-pill p-2 bg-label-info mb-2"><i class="ti ti-upload ti-sm"></i></div>
        <h6 class="card-title mb-2">Upload Invoices</h6>
      </div>
    </div>
  </div>
</div>

<div class="nav-align-top nav-tabs-shadow mb-4">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
      <button type="button" class="nav-link active text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-all-invoices" aria-controls="navs-all-invoices" aria-selected="true">All Invoices</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pending-invoices" aria-controls="navs-pending-invoices" aria-selected="false">Pending</button>
    </li>
  </ul>
  <div class="tab-content mt-4">
    <div class="tab-pane fade show active" id="navs-all-invoices" role="tabpanel">
      <!-- Invoice List Table -->
      <div class="p-3 d-flex justify-content-between">
        <div class="d-flex flex-wrap">
          <div class="">
            <input type="text" class="form-control w-50" id="defaultFormControlInput" placeholder="Invoice No" aria-describedby="defaultFormControlHelp" />
          </div>
          <div class="pr-2">
            <input type="text" class="form-control w-75" id="defaultFormControlInput" placeholder="Invoice Amount" aria-describedby="defaultFormControlHelp" />
          </div>
          <div class="row">
            <div class="col-md-10">
              <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-10">
              <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
            </div>
          </div>
          <div>
            <select class="form-select" id="exampleFormControlSelect">
              <option value="">Status</option>
              <option value="1">Pending</option>
              <option value="2">Approved</option>
              <option value="3">Denied</option>
            </select>
          </div>
          {{-- <div>
            <select class="form-select" id="exampleFormControlSelect">
              <option value="">Sort By</option>
              <option value="1">Asc</option>
              <option value="2">Desc</option>
            </select>
          </div>
          <div>
            <select class="form-select" id="exampleFormControlSelect">
              <option value="">Bulk Actions</option>
              <option value="1">Approve</option>
              <option value="2">Reject</option>
            </select>
          </div> --}}
        </div>
        <div class="d-flex">
          <div style="margin-right: 10px;">
            <select class="form-select" id="exampleFormControlSelect1">
              <option value="1">10</option>
              <option value="2">20</option>
              <option value="3">50</option>
            </select>
          </div>
          <button type="button" class="btn btn-primary" style="margin-right: 10px;"><i class='ti ti-download ti-sm'></i></button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="text-nowrap">
              <th>Vendor</th>
              <th>Invoice No.</th>
              <th>Invoice Amnt</th>
              <th>Acrrued Amnt</th>
              <th>Issue Date</th>
              <th>Due Date</th>
              <th>Invoice Status</th>
              <th>Finance Status</th>
              <th>Disbursement Date</th>
              <th>Disb Amnt</th>
              <th>Progress Status</th>
              <th>UTR No.</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>24 Dec 2023</td>
              <td><span class="badge bg-label-success me-1">Approved</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>30 Nov 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>30 Nov 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>24 Nov 2023</td>
              <td><span class="badge bg-label-danger me-1">Denied</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>25 Dec 2023</td>
              <td><span class="badge bg-label-success me-1">Approved</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>24 Dec 2023</td>
              <td><span class="badge bg-label-success me-1">Approved</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
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
    <div class="tab-pane fade show" id="navs-pending-invoices" role="tabpanel">
      <div class="p-3 d-flex justify-content-between">
        <div class="d-flex flex-wrap">
          <div class="">
            <input type="text" class="form-control w-50" id="defaultFormControlInput" placeholder="Invoice No" aria-describedby="defaultFormControlHelp" />
          </div>
          <div class="pr-2">
            <input type="text" class="form-control w-75" id="defaultFormControlInput" placeholder="Invoice Amount" aria-describedby="defaultFormControlHelp" />
          </div>
          <div class="row">
            <div class="col-md-10">
              <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-10">
              <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
            </div>
          </div>
          {{-- <div>
            <select class="form-select" id="exampleFormControlSelect">
              <option value="">Sort By</option>
              <option value="1">Asc</option>
              <option value="2">Desc</option>
            </select>
          </div>
          <div>
            <select class="form-select" id="exampleFormControlSelect">
              <option value="">Bulk Actions</option>
              <option value="1">Approve</option>
              <option value="2">Reject</option>
            </select>
          </div> --}}
        </div>
        <div class="d-flex">
          <div style="margin-right: 10px;">
            <select class="form-select" id="exampleFormControlSelect1">
              <option value="1">10</option>
              <option value="2">20</option>
              <option value="3">50</option>
            </select>
          </div>
          <button type="button" class="btn btn-primary" style="margin-right: 10px;"><i class='ti ti-download ti-sm'></i></button>
        </div>
      </div>
      <!-- Invoice List Table -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="text-nowrap">
              <th>Vendor</th>
              <th>Invoice No.</th>
              <th>Invoice Amnt</th>
              <th>Acrrued Amnt</th>
              <th>Issue Date</th>
              <th>Due Date</th>
              <th>Invoice Status</th>
              <th>Finance Status</th>
              <th>Disbursement Date</th>
              <th>Disb Amnt</th>
              <th>Progress Status</th>
              <th>UTR No.</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>25 Nov 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>28 Nov 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>28 Nov 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>24 Dec 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>30 Nov 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td><span class="fw-medium">Jicho Pevu</span></td>
              <td>784848875</td>
              <td class="text-success">48455</td>
              <td class="text-success">448485</td>
              <td>23 Nov 2023</td>
              <td>30 Dec 2023</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td><span class="badge bg-label-secondary me-1">Closed</span></td>
              <td>24 Nov 2023</td>
              <td>2300</td>
              <td><span class="badge bg-label-primary me-1">Pending</span></td>
              <td>484854848</td>
              <td>
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);">Approve</a>
                    <a class="dropdown-item" href="javascript:void(0);">Reject</a>
                    <a class="dropdown-item" href="javascript:void(0);">View Attachment</a>
                    <a class="dropdown-item" href="javascript:void(0);">Print</a>
                  </div>
                </div>
              </td>
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
  </div>
</div>
@endsection
