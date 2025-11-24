@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Reports')

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light">{{ __('Programs Report')}}</span>
</h4>

<div id="factoring_programs_report">
  <factoring-programs-report></factoring-programs-report>
</div>
@endsection
