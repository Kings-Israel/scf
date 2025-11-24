@extends('layouts/layoutMaster')

@section('title', 'Pending Configurations')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
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
  <span class="fw-light">{{ __('Proposed Configurations Changes') }} ({{ $pending_configurations }})</span>
</h4>

<div id="pending-configurations">
  <pending-configurations bank={!! $bank->url !!} date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"></pending-configurations>
</div>
@endsection
