@extends('layouts/layoutMaster')

@section('title', 'Programs')

@section('vendor-style')
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
  .tab-content {
    padding: 0px !important;
  }
  .nav-tabs .nav-link {
    font-weight: 900;
    font-size: 14px;
  }
</style>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('Programs') }}</span>
</h4>

<div class="row match-height">
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100 border border-primary">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $programs }}</h5>
          <small>{{ __('Programs') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-primary rounded-pill p-2">
            <i class='ti ti-clipboard ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100 border border-secondary">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $pending_programs }}</h5>
          <small>{{ __('Pending Approval') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-secondary rounded-pill p-2">
            <i class='ti ti-circle-check ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100 border border-warning">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $exhausted_programs }}</h5>
          <small>{{ __('Exhausted Programs') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-warning rounded-pill p-2">
            <i class='ti ti-x ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-12 mb-4">
    <div class="card h-100 border border-info">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $expired_programs }}</h5>
          <small>{{ __('Expired Programs') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-info rounded-pill p-2">
            <i class='ti ti-clock ti-xs'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="nav-align-top nav-tabs-shadow mb-4">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
      <button type="button" class="nav-link active text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-active-programs" aria-controls="navs-active-programs" aria-selected="false">{{ __('Programs') }}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pending-programs" aria-controls="navs-pending-programs" aria-selected="true">{{ __('Pending Approval') }}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-exhausted-programs" aria-controls="navs-exhausted-programs" aria-selected="false">{{ __('Exhausted Programs') }}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-expired-programs" aria-controls="navs-expired-programs" aria-selected="false">{{ __('Expired Programs') }}</button>
    </li>
  </ul>
  <div class="tab-content">
    {{-- Programs --}}
    <div class="tab-pane fade show active" id="navs-active-programs" role="tabpanel">
      <div id="programs">
        <programs bank="{{ request()->route('bank')->url }}" can_add={{ auth()->user()->hasPermissionTo('Add/Edit Program & Mapping') }}></programs>
      </div>
    </div>
    {{-- End Programs --}}
    {{-- Pending Programs --}}
    <div class="tab-pane fade show" id="navs-pending-programs" role="tabpanel">
      <div id="pending-programs">
        <pending-programs bank="{{ request()->route('bank')->url }}" can_add={{ auth()->user()->hasPermissionTo('Add/Edit Program & Mapping') }}></pending-programs>
      </div>
    </div>
    {{-- End Pending Programs --}}
    {{-- Exhausted Programs --}}
    <div class="tab-pane fade show" id="navs-exhausted-programs" role="tabpanel">
      <div id="exhausted-programs">
        <exhausted-programs bank={{ request()->route('bank')->url }} can_add={{ auth()->user()->hasPermissionTo('Add/Edit Program & Mapping') }}></exhausted-programs>
      </div>
    </div>
    {{-- End Exhausted Programs --}}
    {{-- Expired Programs --}}
    <div class="tab-pane fade show" id="navs-expired-programs" role="tabpanel">
      <div id="expired-programs">
        <expired-programs bank={{ request()->route('bank')->url }} can_add={{ auth()->user()->hasPermissionTo('Add/Edit Program & Mapping') }}></expired-programs>
      </div>
    </div>
    {{-- End Expited Programs --}}
  </div>
</div>
@endsection
