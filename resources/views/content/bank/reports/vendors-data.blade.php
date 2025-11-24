@extends('layouts/layoutMaster')

@section('title', Str::title($type))

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-style')
@endsection

@section('page-script')
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="fw-light">{{ Str::title($type) }}</span>
  </h4>
</div>

<div id="vendors-report">
  <vendors-report bank={{ request()->route('bank')->url }} program={{ $program->id }} type={{ $type }}></vendors-report>
</div>
@endsection
