@extends('layouts/layoutMaster')

@section('title', 'Notifications')

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
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="text-muted fw-light">{{ __('Notifications')}}</span>
</h4>

<div id="notifications">
  <notifications bank={{ $bank->url }}></notifications>
</div>
@endsection
