@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Configurations')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/quill/typography.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/quill/katex.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/quill/editor.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
  .tab-content {
    padding: 0px !important;
  }
  .company-profile .nav {
    min-width: 25% !important;
  }
  .company-profile .nav-link {
    padding: 0px !important;
  }
  .company-profile .nav-link:hover {
    cursor: pointer; !important;
  }
  .company-profile .nav-pills .nav-link.active, .company-profile .nav-pills .nav-link.active:hover, .company-profile .nav-pills .nav-link.active:focus {
    background-color: #e4e4e4 !important;
    color: #2c2c2c;
    width: 100%;
  }
  .company-profile .nav-pills .nav-link.active, .company-profile .nav-pills .nav-link.active:hover, .company-profile .nav-pills .nav-link.active:focus, .company-profile .tf-icons {
    padding: 10px;
    border-radius: 5px;
    background-color: #0154AF;
  }
</style>
@endsection

@section('vendor-script')
  <script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/quill/katex.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/quill/quill.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">{{ __('Company Profile')}}</span>
</h4>
<div class="nav-align-left mb-4 company-profile">
  <ul class="nav nav-pills me-3" role="tablist">
    <li class="nav-item my-1">
      <div class="nav-link active d-flex text-nowrap" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-left-home" aria-controls="navs-pills-left-home" aria-selected="true">
        <span><i class="company-profile tf-icons ti ti-users ti-xs me-1 text-white"></i></span>
        <div class="d-flex flex-column">
          <span>{{ __('General Information')}}</span>
          <small style="color: #939393">{{ __('Name/KRA PIN/Type')}}</small>
        </div>
      </div>
    </li>
    <li class="nav-item my-1">
      <div class="nav-link d-flex text-nowrap" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-left-profile" aria-controls="navs-pills-left-profile" aria-selected="false">
        <span><i class="company-profile tf-icons ti ti-location ti-xs me-1 text-white"></i></span>
        <div class="d-flex flex-column">
          <span>{{ __('Company Address Details')}}</span>
          <small style="color: #939393">{{ __('Location Details') }}</small>
        </div>
      </div>
    </li>
    <li class="nav-item my-1">
      <div class="nav-link d-flex" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-left-messages" aria-controls="navs-pills-left-messages" aria-selected="false">
        <span><i class="company-profile tf-icons ti ti-mood-smile ti-xs me-1 text-white"></i></span>
        <div class="d-flex flex-column">
          <span>{{ __('Relationship Manager Details')}}</span>
        </div>
      </div>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active p-3" id="navs-pills-left-home" role="tabpanel">
      <div class="row">
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Organization Name')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Organization Type')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Business Segment')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Business Identification Number')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Company's Unique Identification Number')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Bank Customer ID')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Role Type')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Status')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Branch Code')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Industry')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade p-3" id="navs-pills-left-profile" role="tabpanel">
      <div class="row">
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Country')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('State/Province')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('City')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Pin/Zip/Postal Code')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Address')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade p-3" id="navs-pills-left-messages" role="tabpanel">
      <div class="row">
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Relationship Manager Name')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Relationship Manager Email')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="mb-3">
            <label for="formFile" class="form-label">{{ __('Relationship Manager Mobile')}}</label>
            <input class="form-control" type="text" value="" id="html5-text-input" />
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
