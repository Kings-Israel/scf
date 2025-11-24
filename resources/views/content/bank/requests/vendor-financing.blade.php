@extends('layouts/layoutMaster')

@section('title', 'Financing Requests')

@section('vendor-style')
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
    $('input[name="request_daterange"]').daterangepicker({
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

    $('input[name="request_daterange"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
  });
</script>
<script>
  let bank_url = {!! request()->route('bank')->url !!}
  $('#download-template').click(function (e) {
    e.preventDefault();
    fetch('/'+bank_url+'/requests/payment-requests/sample/download')
    .then(resp => resp.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      // the filename you want
      a.download = 'IF_Template.csv';
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
  </script>
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="fw-light">{{ __('Vendor Financing Requests') }}</span>
  </h4>
  <div class="flex">
    @can('Upload VF Transactions')
      <button class="btn btn-primary btn-sm mx-1">
        <a href="{{ route('requests.uploaded.status', ['bank' => request()->route('bank')]) }}" class="text-white">{{ __('View Uploaded Status') }}</a>
      </button>
      <button type="button" data-bs-toggle="modal" data-bs-target="#upload-requests-modal" class="btn btn-primary btn-sm">{{ __('Upload Payment Requests')}}</button>
      <div class="modal modal-top fade" id="upload-requests-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalCenterTitle">{{ __('Upload Payment Request')}}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payment-requests.import', ['bank' => request()->route('bank')]) }}" method="post" enctype="multipart/form-data">
              @csrf
              <div class="modal-body">
                <div class="row mb-1">
                  <div class="form-group">
                    <label for="">{{ __('Select File')}}</label>
                    <input type="file" class="form-control" name="payment_requests" accept=".csv,.xlsx">
                  </div>
                  <span class="py-1 text-danger d-none" id="show-download-error">{{ __('An error occurred while downloading template') }}</span>
                </div>
              </div>
              <div class="modal-footer">
                <a href="{{ route('payment-requests.sample.download', ['bank' => request()->route('bank')]) }}" id="download-template" target="_blank" class="btn btn-label-warning">{{ __('Download Template') }}</a>
                <button class="btn btn-primary" type="submit">{{ __('Submit') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endcan
  </div>
</div>

<div id="vendor-financing">
  <vendor-financing bank={{ request()->route('bank')->url }} date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}" params="{{ json_encode($params) }}"></vendor-financing>
</div>
@endsection
