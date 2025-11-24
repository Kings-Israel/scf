@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$brand_img = asset('assets/img/branding/logo-name.png');
$bank = request()->route('bank');
$configurations = $bank->adminConfiguration()->exists() ? $bank->adminConfiguration : NULL;
if ($configurations) {
  $brand_img = $configurations->logo;
}
@endphp
@extends('layouts/layoutMaster')

@section('title', 'Upload Requested Documents')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
@endsection

@section('page-style')
<style>
  input[readonly]
  {
    background-color:#e8e8e8
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/form-wizard-icons.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
@endsection

@section('content')
@if($documents->count() > 0)
<h4 class="fw-light mx-4 mt-4">
  {{ __('Upload Requested Documents') }}
</h4>
<!-- Default -->
<div class="row mx-3">
  <!-- Vertical Icons Wizard -->
  <div class="col-12 mb-4">
    <div class="bs-stepper vertical wizard-vertical-icons-example mt-2">
      <div class="bs-stepper-header">
        <div class="step" data-target="#account-details-vertical">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle">
              <i class="ti ti-file-description"></i>
            </span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Upload Documents')}}</span>
            </span>
          </button>
        </div>
      </div>
      <div class="bs-stepper-content">
        <form method="POST" action="{{ route('company-documents-store', ['bank' => $bank, 'company' => $company]) }}" enctype="multipart/form-data" id="invoice-form">
          @csrf
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-12">
                <div class="d-flex flex-column">
                  @forelse ($documents as $key => $document)
                    <label for="" class="form-label">{{ $document->name }}</label>
                    <input type="file" class="form-control my-1" name="files[{{ $document->name }}]" accept=".pdf">
                  @empty
                    <span class="badge badge-label-success">{{ __('All Requested Documents have been uploaded')}}</span>
                  @endforelse
                </div>
                @if($documents->count() > 0)
                  <div class="d-flex my-2 gap-2">
                    <button class="btn btn-label-secondary">
                      <span class="align-middle d-sm-inline-block d-none">{{ __('Cancel')}}</span>
                    </button>
                    <button class="btn btn-primary my-auto" id="submit-invoice"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Upload')}}</span></button>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@else
<div class="mx-4 my-4">
  <div class="card">
    <div class="card-body text-center">
      <i class="ti ti-circle-check ti-xl text-success"></i>
      <h3>{{ __('All Requested Documents Are Uploaded')}}</h3>
      <small>{{ __('You can close this tab')}}</small>
    </div>
  </div>
</div>
@endif
@endsection
