@extends('layouts/layoutMaster')

@section('title', 'Opportunities')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="text-muted fw-light">{{ __('Opportunities')}}</span>
</h4>

@can('View Companies')
  <div class="row match-height">
    <div class="col-lg-3 col-sm-12 mb-4">
      <div class="card h-100 border border-primary">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div class="card-title mb-0">
            <h5 class="mb-0 me-2">{{ $opportunities }}</h5>
            <small>{{ __('Opportunities') }}</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-warning rounded-pill p-2">
              <i class='ti ti-list ti-sm'></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
@endcan

@can('View Companies')
<div id="opportunities">
  <opportunities-component bank="{!! request()->route('bank')->url !!}" can_create={{ auth()->user()->hasPermissionTo('Add/Edit Companies') ? "1" : "0" }}></opportunities-component>
</div>
@endcan
@endsection
