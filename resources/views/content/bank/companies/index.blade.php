@extends('layouts/layoutMaster')

@section('title', 'Companies')

@section('vendor-style')
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

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>
$('#view-company-documents').on('click', function() {
  $('#companies').addClass('d-none')
  $('#company-documents').removeClass('d-none')
  $('#view-companies').removeClass('border', 'border-primary')
  $(this).addClass('border', 'border-primary')
})
</script>
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold mb-2">
    <span class="text-muted fw-light">{{ __('Companies')}}</span>
  </h4>
  <div class="d-flex gap-2">
    @can('Add/Edit Companies')
      <div id="upload-companies">
        <upload-companies bank={!! request()->route('bank')->url !!}></upload-companies>
      </div>
    @endcan
    @can('View Companies')
      <div>
        <a href="{{ route('companies.uploaded', ['bank' => request()->route('bank')->url]) }}" class="btn btn-secondary btn-sm">{{ __('View Uploaded Companies') }}</a>
      </div>
    @endcan
  </div>
</div>

@can('View Companies')
  <div class="row match-height">
    <div class="col-lg-3 col-sm-12 mb-4">
      <div class="card h-100 border border-primary" id="view-companies">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div class="card-title mb-0">
            <h5 class="mb-0 me-2">{{ $companies }}</h5>
            <small>{{ __('Companies') }}</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded-pill p-2">
              <i class='ti ti-list ti-sm'></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
@endcan

@can('View Companies')
  <div class="nav-align-top nav-tabs-shadow mb-4">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <button type="button" class="nav-link active text-sm text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-active-companies" aria-controls="navs-active-companies" aria-selected="false">{{ __('Companies') }}</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pending-companies" aria-controls="navs-pending-companies" aria-selected="true">{{ __('Pending Companies') }}</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-opportunities" aria-controls="navs-opportunities" aria-selected="true">{{ __('Opportunities') }}</button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-active-companies" role="tabpanel">
        <div id="companies">
          <companies-component bank="{!! request()->route('bank')->url !!}"can_manage_authorization_matrix={{ auth()->user()->hasPermissionTo('Manage Authorization Matrix') ? '1' : '0' }} can_manage_authorization_group={{ auth()->user()->hasPermissionTo('Manage Authorization Group') ? '1' : '0' }} can_create={{ auth()->user()->hasPermissionTo('Add/Edit Companies') ? "1" : "0" }}></companies-component>
        </div>
      </div>
      <div class="tab-pane fade show" id="navs-pending-companies" role="tabpanel">
        <div id="pending-approval">
          <pending-approval-component bank="{!! request()->route('bank')->url !!}" can_create={{ auth()->user()->hasPermissionTo('Add/Edit Companies') ? "1" : "0" }}></pending-approval-component>
        </div>
      </div>
      <div class="tab-pane fade show" id="navs-opportunities" role="tabpanel">
        <div id="opportunities">
          <opportunities-component bank="{!! request()->route('bank')->url !!}" can_create={{ auth()->user()->hasPermissionTo('Add/Edit Companies') ? "1" : "0" }}></opportunities-component>
        </div>
      </div>
    </div>
  </div>
@endcan
@endsection
