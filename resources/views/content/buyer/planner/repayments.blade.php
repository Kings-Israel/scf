@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'OD Repayments')

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
  <span class="">OD Repayments</span>
</h4>

<div class="card">
  <div class="p-3 d-flex justify-content-between">
    <div class="w-75 row">
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="OD Account" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Initiated By" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Approved By" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
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
          <th>OD Account</th>
          <th>Credit Date</th>
          <th>Amount</th>
          <th>Initiated By</th>
          <th>Status</th>
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
          <td class="">Jack Mjuzi</td>
          <td>24 Nov 2023</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td>-</td>
          <td><span class="badge bg-label-success me-1">Approved</span></td>
          <td></td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-boundary="viewport" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="javascript:void(0);">View</a>
                <a class="dropdown-item" href="javascript:void(0);">Print</a>
              </div>
            </div>
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
