@extends('layouts/anchorFactoringLayoutMaster')

@section('title', 'OD Accounts')

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('OD Accounts')}}</span>
</h4>

<div id="dealer-accounts">
  <dealer-accounts></dealer-accounts>
</div>
@endsection
