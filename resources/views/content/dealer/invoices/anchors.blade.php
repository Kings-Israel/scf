@extends('layouts/buyerLayoutMaster')

@section('title', 'Anchors')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Anchors')}}</span>
</h4>

<div class="card">
  <!-- Invoice List Table -->
  <div id="dealer-anchors">
    <dealer-anchors></dealer-anchors>
  </div>
</div>
@endsection
