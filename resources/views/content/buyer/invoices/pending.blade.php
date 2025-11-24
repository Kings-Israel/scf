@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'Invoices')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/flatpickr/flatpickr.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
@endsection

@section('page-style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('vendor-script')
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
</script>
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Pending Invoice')}}</span>
</h4>

<div class="row match-height">
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $invoices }}</h5>
          <small>{{ __('Invoices')}}</small>
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
          <h5 class="mb-0 me-2">{{ $pending_invoices }}</h5>
          <small>{{ __('Pending Invoices')}}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-success rounded-pill p-2">
            <i class='ti ti-eye ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <!-- Invoice List Table -->
  <div id="buyer-pending-invoices">
    <buyer-pending-invoices></buyer-pending-invoices>
  </div>
</div>
@endsection
