@extends('layouts/layoutMaster')

@section('title', 'Terms and Conditions')

@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('Terms and Conditions') }}</span>
</h4>

<div id="terms-and-conditions">
  <terms-and-conditions
    bank={!! request()->route('bank')->url !!}
    can_upload={{ auth()->user()->hasPermissionTo('Manage Product Configurations') }}
    can_update={{ auth()->user()->hasPermissionTo('Manage Product Configurations') }}
  >
  </terms-and-conditions>
</div>
@endsection
