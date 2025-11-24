@extends('layouts/buyerLayoutMaster')

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
<h4 class="fw-bold">
  <span class="fw-light">{{ __('OD Repayments')}}</span>
</h4>

<div id="od_repayments">
  <od-repayments></od-repayments>
</div>
@endsection
