@extends('layouts/vendorLayoutMaster')

@section('title', 'Payments')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light">{{ __('Payments')}}</span>
</h4>

<div id="vendor-payments">
  <vendor-payments-component></vendor-payments-component>
</div>
@endsection
