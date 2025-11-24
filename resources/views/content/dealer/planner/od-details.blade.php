@extends('layouts/buyerLayoutMaster')

@section('title', 'OD Accounts')

@section('vendor-style')
@endsection

@section('page-style')

@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>

</script>
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('OD Accounts')}}</span>
</h4>

<div id="od_details">
  <od-details></od-details>
</div>
@endsection
