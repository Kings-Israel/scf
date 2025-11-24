@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@endsection

@section('page-style')
<style>
</style>
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light">{{ __('Vendor Analysis Report')}}</span>
</h4>
<div id="buyer-anchor-analysis-report">
  <buyer-anchor-analysis-report></buyer-anchor-analysis-report>
</div>

@endsection
