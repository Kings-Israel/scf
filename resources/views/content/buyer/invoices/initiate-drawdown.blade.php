@extends('layouts/buyerFactoringLayoutMaster')

@section('title', 'Create Invoice')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
@endsection

@section('page-style')
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css" rel="stylesheet" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/form-wizard-icons.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>
@endsection

@section('content')
<div class="">
  <h4 class="fw-bold py-2 mb-2 d-flex justify-content-between">
    <span class="fw-light">Initiate Drawdown</span>
    <div class="d-flex">
      <button class="btn btn-label-secondary mx-2">Discard</button>
      <button class="btn btn-label-primary">Save Draft</button>
    </div>
  </h4>
</div>
<!-- Default -->
<div class="row">
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
              <span class="bs-stepper-title">PO Details</span>
              <span class="bs-stepper-subtitle">PO No, Currency</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#personal-info-vertical">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle">
              <i class="ti ti-user"></i>
            </span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">Drafts</span>
            </span>
          </button>
        </div>
      </div>
      <div class="bs-stepper-content">
        <form onSubmit="return false">
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="invoice-number">Invoice / Unique Ref No.</label>
                <select class="select2" id="invoice-number">
                  <option label=" "></option>
                  <option>Inv123</option>
                  <option>Inv234</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="anchor">Anchor</label>
                <select class="select2" id="anchor">
                  <option label=" "></option>
                  <option>Jicho Pevu</option>
                  <option>Naivas</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="dealer-code">Dealer Code</label>
                <select class="select2" id="dealer-code">
                  <option label=" "></option>
                  <option>Deal1231</option>
                  <option>Dealer2</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="od-account">OD Account</label>
                <select class="select2" id="od-account">
                  <option label=" "></option>
                  <option>234SDA234</option>
                  <option>34DS342</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="credit-to">Credit To</label>
                <select class="select2" id="credit-to">
                  <option label=" "></option>
                  <option>00484384</option>
                  <option>3458939845</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">Payment Date</label>
                <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">Invoice Due Date</label>
                <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">Delivery Date</label>
                <input class="form-control" type="date" value="2021-06-18" id="html5-date-input" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="discount-rate">Discount Rate</label>
                <input type="text" id="discount-rate" class="form-control" disabled placeholder="Discount Rate" value="1-60 Days 15%" aria-label="discount_rate" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="currency">Currency</label>
                <select class="select2" id="currency">
                  <option label=" "></option>
                  <option>KSH</option>
                  <option>USD</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice-amount">Invoice Amount</label>
                <input type="text" id="invoice-amount" class="form-control" placeholder="Invoice Amount" aria-label="invoice_amount" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice-amount-in-words"></label>
                <input type="text" id="invoice-amount-in-words" class="form-control" disabled placeholder="In Words" aria-label="invoice_amount_in_wwords" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="drawdown-amount">Drawdown Amount</label>
                <input type="text" id="drawdown-amount" class="form-control" placeholder="Drawdown Amount" aria-label="drawdown_amount" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice-amount-in-words"></label>
                <input type="text" id="invoice-amount-in-words" class="form-control" disabled placeholder="In Words" aria-label="invoice_amount_in_wwords" />
              </div>
              <div class="col-sm-12">
                <label class="form-label" for="remarks">Remarks</label>
                <input type="text" id="remarks" class="form-control" placeholder="Remarks" aria-label="remarks" />
              </div>
              <div class="col-sm-12">
                <label for="formFile" class="form-label">Attachment</label>
                <input class="form-control" type="file" id="formFile">
              </div>
              <div class="col-12 d-flex justify-content-end">
                <div class="d-flex">
                  <button class="btn btn-label-secondary mx-1">
                    <span class="align-middle d-sm-inline-block d-none">Cancel</span>
                  </button>
                  <button class="btn btn-primary"> <span class="align-middle d-sm-inline-block d-none me-sm-1">Submit</span></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Personal Info -->
          <div id="personal-info-vertical" class="content">
            <div class="content-header mb-3">
              <h6 class="mb-0">Drafts</h6>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Icons Wizard -->
</div>
@endsection
