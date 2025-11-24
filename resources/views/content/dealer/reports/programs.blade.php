@extends('layouts/buyerLayoutMaster')

@section('title', 'Reports')

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Programs Report')}}</span>
</h4>

<div id="dealer_programs_report">
  <dealer-programs-report></dealer-programs-report>
</div>
@endsection
