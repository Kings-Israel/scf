@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Rejected Invoices')

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Rejected Invoices')}}</span>
</h4>

<div class="card">
  <!-- Invoice List Table -->
  <div id="dealer-rejected-invoices">
    <dealer-rejected-invoices></dealer-rejected-invoices>
  </div>
</div>
@endsection
