@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'Purchase Orders')

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
  });
</script>
@endsection

@section('content')
<h4 class="fw-light mb-2">
  {{ __('Purchase Orders')}}
</h4>
<div id="buyer-purchase-orders">
  <buyer-purchase-orders-component can_edit={{ auth()->user()->hasPermissionTo('Manage POs') ? 1 : 0 }}></buyer-purchase-orders-component>
</div>
@endsection
