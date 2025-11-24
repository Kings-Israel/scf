@extends('layouts/vendorLayoutMaster')

@section('title', 'Purchase Orders')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/flatpickr/flatpickr.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('page-style')
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
  $(function() {
    $('input[name="daterange"]').daterangepicker({
      "showDropdowns": true,
      autoUpdateInput: false,
      ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      },
      "alwaysShowCalendars": true,
      opens: 'left'
    });

    $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
  });
  $(function() {
    $('input[name="pending-daterange"]').daterangepicker({
      "showDropdowns": true,
      autoUpdateInput: false,
      ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      },
      "alwaysShowCalendars": true,
      opens: 'left'
    });

    $('input[name="pending-daterange"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
  });
  $('#navs-pending-approval-tab').on('click', function() {
    $('#navs-pending-approval-card').addClass('border border-primary')
    $('#navs-purchase-orders-card').removeClass('border border-primary')
    $('#navs-products-card').removeClass('border border-primary')
  })
  $('#navs-purchase-orders-tab').on('click', function() {
    $('#navs-purchase-orders-card').addClass('border border-primary')
    $('#navs-pending-approval-card').removeClass('border border-primary')
    $('#navs-products-card').removeClass('border border-primary')
  })
  $('#navs-products-tab').on('click', function() {
    $('#navs-products-card').addClass('border border-primary')
    $('#navs-purchase-orders-card').removeClass('border border-primary')
    $('#navs-pending-approval-card').removeClass('border border-primary')
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">{{ __('Purchcase Orders')}}</span>
</h4>

<div class="row">
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card border border-primary" id="navs-purchase-orders-card">
      <div class="card-body d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $purchase_orders }}</h5>
          <small>{{ __('Purchase Orders')}}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-primary rounded-pill p-3">
            <i class='ti ti-cpu ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card" id="navs-pending-approval-card">
      <div class="card-body d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $pending_purchase_orders }}</h5>
          <small>{{ __('Pending Approval')}}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-warning rounded-pill p-3">
            <i class='ti ti-cpu ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4 d-none">
    <div class="card" id="navs-products-card">
      <div class="card-body d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">12</h5>
          <small>{{ __('Products')}}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-success rounded-pill p-3">
            <i class='ti ti-cpu ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="nav-align-top nav-tabs-shadow mb-4">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
      <button type="button" class="nav-link active text-uppercase" id="navs-purchase-orders-tab" role="tab" data-bs-toggle="tab" data-bs-target="#navs-purchase-orders" aria-controls="navs-purchase-orders" aria-selected="true">{{ __('Purchase Orders')}}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm text-uppercase" id="navs-pending-approval-tab" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pending-approval" aria-controls="navs-pending-approval" aria-selected="false">{{ __('Pending Approval')}}</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="navs-purchase-orders" role="tabpanel">
      <div id="vendor-purchase-orders">
        <vendor-purchase-orders-component></vendor-purchase-orders-component>
      </div>
    </div>
    <div class="tab-pane fade show" id="navs-pending-approval" role="tabpanel">
      <div id="vendor-pending-purchase-orders">
        <vendor-pending-purchase-orders-component></vendor-pending-purchase-orders-component>
      </div>
    </div>
  </div>
</div>
@endsection
