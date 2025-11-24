@extends('layouts/vendorLayoutMaster')

@section('title', 'Loan/OD Accounts')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">{{ __('Loan/OD Accounts')}}</span>
</h4>

<div id="vendor-loan-accounts">
  <vendor-loan-accounts></vendor-loan-accounts>
</div>
@endsection
