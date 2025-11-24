@extends('layouts/layoutMaster')

@section('title', 'Ledger Reports')

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
  <span class="fw-light">Ledger Reports</span>
</h4>

<div class="card">
  <div class="p-3 d-flex justify-content-between">
    <div class="w-75 row">
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="OD Account" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Invoice/Unique Ref No" aria-describedby="defaultFormControlHelp" />
      </div>
      <div class="col-2">
        <input type="text" class="form-control" id="defaultFormControlInput" placeholder="Amount" aria-describedby="defaultFormControlHelp" />
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
      <div class="mx-2">
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
          <th>Dealer</th>
          <th>Invoice/Unique Ref No</th>
          <th>Date</th>
          <th>Transaction Type</th>
          <th>Debit</th>
          <th>Credit</th>
          <th>Principle Balance</th>
          <th>Discount Balance</th>
          <th>Penal Discount Balance</th>
          <th>Overdue Date</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap">
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </div>
          </td>
          <td class="">KKDealerAA</td>
          <td class="">KK Dealer</td>
          <td class="text-primary text-decoration-underline">UU-01</td>
          <td class="">23 Nov 2023</td>
          <td>Interest Posting For Invoice UU-01</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="">23 Nov 2023</td>
        </tr>
        <tr class="text-nowrap">
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </div>
          </td>
          <td class="">KKDealerAA</td>
          <td class="">KK Dealer</td>
          <td class="text-primary text-decoration-underline">UU-01</td>
          <td class="">23 Nov 2023</td>
          <td>Interest Posting For Invoice UU-01</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="">23 Nov 2023</td>
        </tr>
        <tr class="text-nowrap">
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </div>
          </td>
          <td class="">KKDealerAA</td>
          <td class="">KK Dealer</td>
          <td class="text-primary text-decoration-underline">UU-01</td>
          <td class="">23 Nov 2023</td>
          <td>Interest Posting For Invoice UU-01</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="">23 Nov 2023</td>
        </tr>
        <tr class="text-nowrap">
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
            </div>
          </td>
          <td class="">KKDealerAA</td>
          <td class="">KK Dealer</td>
          <td class="text-primary text-decoration-underline">UU-01</td>
          <td class="">23 Nov 2023</td>
          <td>Interest Posting For Invoice UU-01</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(7000) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="text-success">Ksh {{ number_format(0) }}</td>
          <td class="">23 Nov 2023</td>
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
