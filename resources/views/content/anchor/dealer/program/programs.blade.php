@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Programs')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
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

@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">Buyer Programs</span>
</h4>

<div class="card">
  <!-- Invoice List Table -->
  <div class="p-3 d-flex justify-content-between">
    <div class="w-75 row">
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Program Code" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Buyer Name" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="No. of Requests" aria-describedby="defaultFormControlHelp" />
      </div>
    </div>
    <div class="d-flex justify-content-end w-25">
      <div class="mx-2">
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
          <th>Program Code</th>
          <th>Buyer Name</th>
          <th>Financing Limit</th>
          <th>Utilized Limit</th>
          <th>No. of Requests</th>
          <th>Pipeline Amount</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap">
          <td class="">AJHSJHDJH3478</td>
          <td><span class="fw-medium">Justus Oyier</span></td>
          <td class="text-success">Ksh {{ number_format(300000) }}</td>
          <td class="text-success">Ksh {{ number_format(117000) }}</td>
          <td class="">2</td>
          <td class="text-success">Ksh {{ number_format(122000) }}</td>
        </tr>
        <tr class="text-nowrap">
          <td class="">AJHSJHDJH3478</td>
          <td><span class="fw-medium">Justus Oyier</span></td>
          <td class="text-success">Ksh {{ number_format(300000) }}</td>
          <td class="text-success">Ksh {{ number_format(117000) }}</td>
          <td class="">2</td>
          <td class="text-success">Ksh {{ number_format(122000) }}</td>
        </tr>
        <tr class="text-nowrap">
          <td class="">AJHSJHDJH3478</td>
          <td><span class="fw-medium">Justus Oyier</span></td>
          <td class="text-success">Ksh {{ number_format(300000) }}</td>
          <td class="text-success">Ksh {{ number_format(117000) }}</td>
          <td class="">2</td>
          <td class="text-success">Ksh {{ number_format(122000) }}</td>
        </tr>
        <tr class="text-nowrap">
          <td class="">AJHSJHDJH3478</td>
          <td><span class="fw-medium">Justus Oyier</span></td>
          <td class="text-success">Ksh {{ number_format(300000) }}</td>
          <td class="text-success">Ksh {{ number_format(117000) }}</td>
          <td class="">2</td>
          <td class="text-success">Ksh {{ number_format(122000) }}</td>
        </tr>
        <tr class="text-nowrap">
          <td class="">AJHSJHDJH3478</td>
          <td><span class="fw-medium">Justus Oyier</span></td>
          <td class="text-success">Ksh {{ number_format(300000) }}</td>
          <td class="text-success">Ksh {{ number_format(117000) }}</td>
          <td class="">2</td>
          <td class="text-success">Ksh {{ number_format(122000) }}</td>
        </tr>
        <tr class="text-nowrap">
          <td class="">AJHSJHDJH3478</td>
          <td><span class="fw-medium">Justus Oyier</span></td>
          <td class="text-success">Ksh {{ number_format(300000) }}</td>
          <td class="text-success">Ksh {{ number_format(117000) }}</td>
          <td class="">2</td>
          <td class="text-success">Ksh {{ number_format(122000) }}</td>
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
