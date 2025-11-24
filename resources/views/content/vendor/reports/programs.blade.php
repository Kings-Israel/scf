@extends('layouts/vendorLayoutMaster')

@section('title', 'Reports')

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light">{{ __('Programs Report')}}</span>
</h4>

<div id="vendor_programs_report">
  <vendor-programs-report></vendor-programs-report>
</div>
@endsection
