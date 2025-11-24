@extends('layouts/anchorLayoutMaster')

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
  .no-label {
    margin-left: -65px !important;
  }
  .yes-label {
    margin-left: 40px !important;
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
<script src="{{asset('assets/js/forms-editors.js')}}"></script>
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="fw-light">{{ __('Configurations')}}</span>
</h4>
<div class="nav-align-top mb-4">
  <ul class="nav nav-pills mb-3 nav-fill w-75" role="tablist">
    <li class="nav-item">
      <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bank-details" aria-controls="navs-pills-bank-details" aria-selected="true"><i class="tf-icons ti ti-file-text ti-xs me-1"></i> {{ __('Bank Details(CASA)')}}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-configurations" aria-controls="navs-pills-configurations" aria-selected="false"><i class="tf-icons ti ti-clipboard-check ti-xs me-1"></i> {{ __('Configurations')}}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-maker-checker" aria-controls="navs-pills-maker-checker" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> {{ __('Maker/Checker')}}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-po-settings" aria-controls="navs-pills-po-settings" aria-selected="false"><i class="tf-icons ti ti-settings ti-xs me-1"></i>{{ __(' PO Settings')}}</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="navs-pills-bank-details" role="tabpanel">
      <h5 class="fw-bold py-3 mb-2">
        <span class="fw-light px-3">{{ __('Bank A/C Details')}}</span>
      </h5>
      <div class="table-responsive pb-2 border-top border-bottom border-secondary">
        <table class="table">
          <tbody class="table-border-bottom-0">
            <tr class="text-nowrap">
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="bank">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="90493893">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="YofInvoice">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="Runda">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="5749834">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="Current">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="KSH">
                </div>
              </td>
            </tr>
            <tr class="text-nowrap">
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="bank">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="90493893">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="YofInvoice">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="Runda">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="5749834">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="Current">
                </div>
              </td>
              <td>
                <div class="">
                  <input type="text" class="form-control" id="email" name="email-username" placeholder="" value="KSH">
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end my-2 mx-3">
        <button class="btn btn-secondary">
          {{ __('Cancel')}}
        </button>
        <button class="btn btn-primary" style="margin-left: 10px;">
          {{ __('Submit')}}
        </button>
      </div>
    </div>
    <div class="tab-pane fade" id="navs-pills-configurations" role="tabpanel">
      <h5 class="fw-bold py-3 mb-2">
        <span class="fw-light px-3">{{ __('Configurations')}}</span>
      </h5>
      <div class="row px-3 mb-2">
        <div class="col-md-8 d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">{{ __('Default Payment Terms')}}</label>
          <small>You can also define Vendor Level Payment terms under Configurations -> Vendor Settings Menu</small>
        </div>
        <div class="col-md-4">
          <input class="form-control" type="text" value="8" id="html5-text-input" />
        </div>
      </div>
      <div class="d-flex justify-content-end my-2 mx-3">
        <button class="btn btn-secondary">
          {{ __('Cancel')}}
        </button>
        <button class="btn btn-primary" style="margin-left: 10px;">
          {{ __('Submit')}}
        </button>
      </div>
    </div>
    <div class="tab-pane fade" id="navs-pills-maker-checker" role="tabpanel">
      <h5 class="fw-bold py-3 mb-2">
        <span class="fw-light px-3">{{ __('Maker / Checker')}}</span>
      </h5>
      <div class="row px-3 mb-2">
        <div class="col-md-8 d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">{{ __('POs Creation / Updation')}}</label>
        </div>
        <div class="col-md-4">
          <div class="form-check form-switch mb-2">
            <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('No')}}</label>
            <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" checked>
            <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Yes')}}</label>
          </div>
        </div>
      </div>
      <div class="row px-3 mb-2">
        <div class="col-md-8 d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">{{ __('Invoice Payment Instruction')}}</label>
        </div>
        <div class="col-md-4">
          <div class="form-check form-switch mb-2">
            <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('No')}}</label>
            <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" checked>
            <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Yes')}}</label>
          </div>
        </div>
      </div>
      <div class="row px-3 mb-2">
        <div class="col-md-8 d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">{{ __('PI Approval for bulk upload')}}</label>
        </div>
        <div class="col-md-4">
          <div class="form-check form-switch mb-2">
            <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('No')}}</label>
            <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" checked>
            <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Yes')}}</label>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-end my-2 mx-3">
        <button class="btn btn-secondary">
          {{ __('Cancel')}}
        </button>
        <button class="btn btn-primary" style="margin-left: 10px;">
          {{ __('Submit')}}
        </button>
      </div>
    </div>
    <div class="tab-pane fade" id="navs-pills-po-settings" role="tabpanel">
      <h5 class="fw-bold py-3 mb-2">
        <span class="fw-light px-3">PO Settings</span>
      </h5>
      <div class="row px-3 mb-2">
        <div class="col-md-6 border-bottom d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">{{ __('Company Logo')}}</label>
          <small>Recommended size 160 x 80 pixels, image format PNG/JPG</small>
        </div>
        <div class="col-md-6">
          <input class="form-control" type="file" id="formFile">
        </div>
      </div>
      <div class="row px-3 mb-2">
        <div class="col-md-6 d-flex flex-column">
          <label for="" class="col-form-label">{{ __('PO Description')}}</label>
          <small>{{ __('Will be shown in invoice as description')}}</small>
        </div>
        <div class="col-md-6">
          <div id="snow-toolbar" style="background-color: #e4e4e4">
            <span class="ql-formats">
              <button class="ql-bold"></button>
              <button class="ql-italic"></button>
              <button class="ql-underline"></button>
            </span>
          </div>
          <div id="snow-editor">
          </div>
        </div>
      </div>
      <div class="row px-3 mb-2">
        <div class="col-md-6 d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">{{ __('PO Foote')}}r</label>
          <small>{{ __('Will be shown in invoice footer')}}</small>
        </div>
        <div class="col-md-6">
          <input class="form-control" type="text" placeholder="PO Footer" id="html5-text-input" />
        </div>
      </div>
      <div class="d-flex justify-content-end my-2 mx-3">
        <button class="btn btn-secondary">
          {{ __('Cancel')}}
        </button>
        <button class="btn btn-primary" style="margin-left: 10px;">
          {{ __('Submit')}}
        </button>
      </div>
    </div>
  </div>
</div>
@endsection
