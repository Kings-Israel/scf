@extends('layouts/vendorLayoutMaster')

@section('title', 'Financial Requests')

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
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light"> {{ __('Financial Requests')}} </span>
</h4>

<div id="vendor-finance-requests">
  <vendor-finance-requests-component></vendor-finance-requests-component>
</div>
@endsection
