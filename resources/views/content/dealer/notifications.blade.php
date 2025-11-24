@extends('layouts/buyerLayoutMaster')

@section('title', 'Notifications')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="text-muted fw-light">{{ __('Notifications')}}</span>
</h4>

<div id="dealer-notifications">
  <dealer-notifications></dealer-notifications>
</div>
@endsection
