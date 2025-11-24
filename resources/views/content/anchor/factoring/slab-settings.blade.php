@extends('layouts/anchorFactoringLayoutMaster')

@section('title', 'Discount Slab Settings')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('Early Payment Discount Slab List')}}</span>
</h4>
<div id="discount_slab">
  <discount-slab></discount-slab>
</div>
@endsection
