@extends('layouts/layoutMaster')

@section('title', 'Holidays')

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
  <span class="fw-light">{{ __('Holidays')}}</span>
</h4>

<div id="holidays">
  <holidays
    bank={!! request()->route('bank')->url !!}
    can_upload={{ auth()->user()->hasPermissionTo('Manage Holidays') }}
    can_update={{ auth()->user()->hasPermissionTo('Manage Holidays') }}
    date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"
  >
  </holidays>
</div>
@endsection
