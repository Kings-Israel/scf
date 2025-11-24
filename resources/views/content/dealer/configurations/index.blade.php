@extends('layouts/buyerLayoutMaster')

@section('title', 'Configurations')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
  .tab-content {
    padding: 0px !important;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('Configurations')}}</span>
</h4>
<div id="dealer-settings">
  <dealer-settings
    can_edit={{ auth()->user()->hasPermissionTo('Manage Configuration') ? 1 : 0 }}
    can_view={{ auth()->user()->hasPermissionTo('View Configurations') ? 1 : 0 }}
    has_proposed_updates={{ $proposed_updates > 0 ? 1 : 0 }}
  ></dealer-settings>
</div>
@endsection
