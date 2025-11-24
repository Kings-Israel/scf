@extends('layouts/anchorFactoringLayoutMaster')

@section('title', 'Reports')

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light">{{ __('Dealer Programs Report')}}</span>
</h4>

<div id="factoring_dealer_programs_report">
  <factoring-dealer-programs-report></factoring-dealer-programs-report>
</div>
@endsection
