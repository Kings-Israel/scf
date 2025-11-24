@extends('layouts/anchorLayoutMaster')

@section('title', 'Purchase Orders')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">Purchcase Orders</span>
</h4>

<div class="row">
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">378</h5>
          <small>Purchase Orders</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-primary rounded-pill p-3">
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-9 col-sm-12"></div>
</div>
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <div class="w-50 d-flex">
      <div class="">
        <input type="text" class="form-control w-75" id="defaultFormControlInput" placeholder="Vendor" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="pr-2">
        <input type="text" class="form-control w-75" id="defaultFormControlInput" placeholder="PO No" aria-describedby="defaultFormControlHelp" />
      </div>
      <div>
        <input type="text" class="form-control w-75" id="defaultFormControlInput" placeholder="Status" aria-describedby="defaultFormControlHelp" />
      </div>
    </div>
    <div class="d-flex align-items-end">
      <div style="margin-right: 10px;">
        <select class="form-select" id="exampleFormControlSelect1" aria-label="Default select example">
          <option value="1">10</option>
          <option value="2">20</option>
          <option value="3">50</option>
        </select>
      </div>
      <button type="button" class="btn btn-primary" style="margin-right: 10px;"><i class='ti ti-download ti-sm'></i></button>
      <button type="button" class="btn btn-primary"><i class='ti ti-plus ti-sm'></i>Create New PO</button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr class="text-nowrap">
          <th>
            <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
          </div>
          </th>
          <th>Vendor</th>
          <th>P.O No.</th>
          <th>PO Amount</th>
          <th>AMount Invoiced</th>
          <th>P.O Status</th>
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
          <td><span class="fw-medium">Jicho Pevu</span></td>
          <td class="text-primary text-decoration-underline">DGHD743</td>
          <td>2300</td>
          <td>2100</td>
          <td><span class="badge bg-label-success me-1">Approved</span></td>
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
          <td>
            <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
          </div>
          </td>
          <td><span class="fw-medium">Jicho Pevu</span></td>
          <td class="text-primary text-decoration-underline">DGHD743</td>
          <td>2300</td>
          <td>2100</td>
          <td><span class="badge bg-label-success me-1">Approved</span></td>
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
          <td>
            <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
          </div>
          </td>
          <td><span class="fw-medium">Jicho Pevu</span></td>
          <td class="text-primary text-decoration-underline">DGHD743</td>
          <td>2300</td>
          <td>2100</td>
          <td><span class="badge bg-label-success me-1">Approved</span></td>
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
          <td>
            <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
          </div>
          </td>
          <td><span class="fw-medium">Jicho Pevu</span></td>
          <td class="text-primary text-decoration-underline">DGHD743</td>
          <td>2300</td>
          <td>2100</td>
          <td><span class="badge bg-label-success me-1">Approved</span></td>
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
</div>
@endsection
