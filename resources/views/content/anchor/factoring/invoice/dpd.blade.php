@extends('layouts/anchorFactoringLayoutMaster')

@section('title', 'DPD Invoices')

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('DPD Invoices')}}</span>
</h4>

<div id="dealer-dpd-invoices">
  <dealer-dpd-invoices></dealer-dpd-invoices>
</div>
@endsection
