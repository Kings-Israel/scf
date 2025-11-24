@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'Finance Requests')

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
  <span class="text-muted fw-light">Financing Requests</span>
</h4>

<div class="card">
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
      <div class="col-2">
        <select class="form-select" id="exampleFormControlSelect">
          <option value="">Sort By</option>
          <option value="1">Asc</option>
          <option value="2">Desc</option>
        </select>
      </div>
      <div class="col-3 mt-2">
        <select class="form-select" id="exampleFormControlSelect">
          <option value="">Bulk Actions</option>
          <option value="1">Approve</option>
          <option value="2">Reject</option>
        </select>
      </div>
    </div>
    <div class="d-flex justify-content-end w-25">
      <div class="">
        <select class="form-select" id="exampleFormControlSelect1">
          <option value="1">10</option>
          <option value="2">20</option>
          <option value="3">50</option>
        </select>
      </div>
      <div class="">
        <button type="button" class="btn btn-primary"><i class='ti ti-download ti-sm'></i></button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr class="">
          <th>
            <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
          </th>
          <th>Payment Ref No</th>
          <th>Invoice No.</th>
          <th>Anchor</th>
          <th>Payment Date</th>
          <th>Payment End Date</th>
          <th>Eligibility</th>
          <th>Payment Amount</th>
          <th>Status</th>
          <th>Progress</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap">
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </div>
          </td>
          <td>23764763</td>
          <td class="text-primary text-decoration-underline">Inv_4234</td>
          <td class="">Jack Mjuzi</td>
          <td>24 Nov 2023</td>
          <td>24 Dec 2023</td>
          <td>100%</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td><span class="badge bg-label-success me-1">Disbursed</span></td>
          <td>-</td>
          <td>
            <i class="tf-icons ti ti-print ti-xs me-1"></i>
            <i class="tf-icons ti ti-reload ti-xs me-1"></i>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <nav aria-label="Page navigation" class="mt-2 mx-2">
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
