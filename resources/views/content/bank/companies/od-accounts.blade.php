@extends('layouts/layoutMaster')

@section('title', 'OD Accounts')

@section('vendor-style')
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
    position: static;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>
  $('#payment-amount').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="text-muted fw-light">{{ __('OD Accounts')}}</span>
</h4>

<div id="od-accounts">
  <od-accounts bank="{!! request()->route('bank')->url !!}" ></od-accounts>
</div>
@endsection
