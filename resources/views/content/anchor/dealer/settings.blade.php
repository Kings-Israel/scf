@extends('layouts/anchorDealerLayoutMaster')

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
<div id="factoring-settings">
  <factoring-settings
    {{-- can_edit={{ auth()->user()->hasPermissionTo('Update Configurations') ? 1 : 0 }}
    can_view={{ auth()->user()->hasPermissionTo('View Seller Configurations') ? 1 : 0 }} --}}
    can_edit={{ auth()->user()->hasPermissionTo('Manage Seller Configurations') ? 1 : 0 }}
    can_edit_anchor={{ auth()->user()->hasPermissionTo('Manage Buyer Settings') ? 1 : 0 }}
    checker={{ auth()->user()->hasPermissionTo('Seller Configurations Changes Checker') ? 1 : 0 }}
    has_proposed_updates={{ $proposed_updates > 0 ? 1 : 0 }}
    has_factoring_programs={{ $has_factoring_programs ? 1 : 0 }}
    has_dealer_financing_programs={{ $has_dealer_financing_programs ? 1 : 0 }}
  ></factoring-settings>
</div>
@endsection
