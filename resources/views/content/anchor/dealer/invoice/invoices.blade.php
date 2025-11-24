@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Invoices')

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
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="text-muted fw-light">{{ __('Invoices')}}</span>
  </h4>
  <div class="d-flex gap-2">
    <div id="factoring-dealer-invoices-upload">
      <factoring-dealer-invoices-upload></factoring-dealer-invoices-upload>
    </div>
    <div>
      <button class="btn btn-primary">
        <a href="{{ route('anchor.factoring-invoices.uploaded') }}" class="text-white">{{ __('View Uploaded Status') }}</a>
      </button>
    </div>
  </div>
</div>

<div class="row match-height">
  <div class="col-lg-3 col-sm-12 mb-2">
    <div class="card h-100 border border-primary">
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
  <div class="col-lg-3 col-sm-12 mb-2">
    <div class="card h-100 border border-warning">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $pending_invoices }}</h5>
          <small>{{ __('Pending Invoices')}}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-warning rounded-pill p-2">
            <i class='ti ti-file ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <!-- Invoice List Table -->
  <div id="factoring_dealer_invoices">
    <factoring-dealer-invoices-component></factoring-dealer-invoices-component>
  </div>
</div>
@endsection
