@extends('layouts/anchorLayoutMaster')

@section('title', 'Invoices')

@section('vendor-style')
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

    $('input[name="pending_daterange"]').daterangepicker({
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

    $('input[name="pending_daterange"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
  });

  $('#download-invoices').click(function (e) {
    e.preventDefault();
    fetch('invoices/sample/download')
    .then(resp => resp.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      // the filename you want
      a.download = 'Invoices_Template.xlsx';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
    })
    .catch(() => {
      $('#show-download-error').removeClass('d-none')
      setTimeout(() => {
        $('#show-download-error').addClass('d-none')
      }, 3000);
    });
  })

  $('#submit-invoices-btn').on('click', function(e) {
    e.preventDefault();
    $('#upload-invoices-form').submit()
    setTimeout(() => {
      $('#invalid-data-message').removeClass('d-none')
    }, 4000);
  })

  $('#upload-product-type').on('change', function () {
    let value = $(this).val();
    if (value == 'Vendor Financing Receivable') {
      $('#download-invoices').removeClass('d-none')
      $('#download-dealer-invoices').addClass('d-none')
      $('#view-vendors').removeClass('d-none')
      $('#view-dealers').addClass('d-none')
    } else {
      $('#download-invoices').addClass('d-none')
      $('#download-dealer-invoices').removeClass('d-none')
      $('#view-vendors').addClass('d-none')
      $('#view-dealers').removeClass('d-none')
    }
  })
</script>
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="text-muted fw-light">{{ __('Invoices')}}</span>
  </h4>
  <div class="d-flex gap-2">
    @can('Upload Invoices')
      <div id="invoices_upload">
        <invoices-upload dealer_financing="{{ $dealer_financing > 0 ? '1' : '0' }}"></invoices-upload>
      </div>
    @endcan
    <div>
      <button class="btn btn-primary">
        <a href="{{ route('anchor.invoices.uploaded') }}" class="text-white">{{ __('View Uploaded Status') }}</a>
      </button>
    </div>
  </div>
</div>

<div class="row match-height w-50">
  <div class="col-lg-6 col-sm-12 mb-2">
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
  <div class="col-lg-6 col-sm-12 mb-2">
    <div class="card h-100 border border-warning">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $pending_invoices }}</h5>
          <small>{{ __('Pending Invoices')}}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-warning rounded-pill p-2">
            <i class='ti ti-file-text ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="mt-2" id="invoices-view">
  <invoices-view></invoices-view>
</div>
@endsection
