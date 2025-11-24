@extends('layouts/anchorFactoringLayoutMaster')

@section('title', 'Invoices')

@section('page-script')

@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Expired Invoices')}}</span>
</h4>

<div id="factoring_expired_invoices">
  <factoring-expired-invoices-component></factoring-expired-invoices-component>
</div>
@endsection
