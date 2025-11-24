@extends('layouts/layoutMaster')

@section('title', 'Add Company')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
{{-- <link rel="stylesheet" href="{{asset('assets/vendor/libs/dropzone/dropzone.css')}}" /> --}}
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
{{-- <script src="{{asset('assets/vendor/libs/dropzone/dropzone.js')}}"></script> --}}
@endsection

@section('page-script')
<script src="{{asset('assets/js/add-company-wizard.js')}}"></script>
<script src="{{asset('assets/js/form-wizard-validation.js')}}"></script>
{{-- <script src="{{asset('assets/js/forms-file-upload.js')}}"></script> --}}
<script>
  let bank = {!! json_encode($bank) !!}
  let drafts = {!! json_encode($drafts) !!}

  let revisions_count = drafts.length;

  let has_pipeline = {!! json_encode($pipeline) !!} ? true : false;

  if(!has_pipeline) {
    $('#submit-one').on('click', function () {
      if ($('#company-name').val() != '' && $('#top-borrower-limit').val() != '' && $('#limit_expiry_date').val() != '' && $('#unique-identification-number').val() && $('#business-identification-number').val() != '' && $('#kra-pin').val()) {
        $.post({
          "url": 'draft/store',
          "data": {
            "_token": "{{ csrf_token() }}",
            "name": $('#company-name').val(),
            "top_level_borrower_limit": $('#top-borrower-limit').val(),
            "limit_expiry_date": $('#limit_expiry_date').val(),
            "cif": $('#cif').val(),
            "unique_identification_number": $('#unique-identification-number').val(),
            "branch_code": $('#branch-code').val(),
            "customer_type": $("input[name='customer_type']:checked").val(),
            "business_identification_number": $('#business-identification-number').val(),
            "organization_type": $('#organization-type').val(),
            "kra_pin": $('#kra-pin').val(),
            // "cust_ancode": $('#cust-ancode').val(),
          },
          "dataType": "json",
          "success": function(data) {
            $('#drafts-count').text(data.revisions);
          },
          "error": function(data) {
            console.log(data);
          }
        })
      }
    })

    $('#submit-two').on('click', function () {
      if ($('#company-name').val() != '' && $('#top-borrower-limit').val() != '' && $('#limit_expiry_date').val() != '' && $('#unique-identification-number').val() && $('#business-identification-number').val() != '' && $('#kra-pin').val() && $('#city').val() != '') {
        $.post({
          "url": 'draft/store',
          "data": $('#company-details-form').serializeArray(),
          "dataType": "json",
          "success": function (data) {
            $('#drafts-count').text(data.revisions)
          },
          "error": function (data) {
            console.log(data)
          }
        })
      }
    })

    $('#submit-three').on('click', function() {
      if ($('#company-name').val() != '' && $('#top-borrower-limit').val() != '' && $('#limit_expiry_date').val() != '' && $('#unique-identification-number').val() && $('#business-identification-number').val() != '' && $('#kra-pin').val() && $('#city').val() != '')
      {
        $.post({
          "url": "draft/store",
          "data": $('#company-details-form').serializeArray(),
          "dataType": "json",
          "success": function (data) {
            $('#drafts-count').text(data.revisions)
          },
          "error": function (data) {
            console.log(data)
          }
        })
      }
    })
  }

  $('#drafts-link').on('click', function(e) {
    e.preventDefault();
    window.location.href = $('#drafts-link').attr('href');
  })

  $('#top-borrower-limit').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })

  var min_day = new Date()
  var dd = min_day.getDate();
  var mm = min_day.getMonth() + 1; //January is 0!
  var yyyy = min_day.getFullYear();

  if (dd < 10) {
    dd = '0' + dd;
  }

  if (mm < 10) {
    mm = '0' + mm;
  }

  min_day = yyyy + '-' + mm + '-' + dd;
  $('#limit_expiry_date').attr("min", min_day);

  function selectUser(key) {
    let email = $('#relationship-manager-name-'+key).find(':selected').data('email')
    let phone = $('#relationship-manager-name-'+key).find(':selected').data('phone')
    $('#relationship-manager-mobile-'+key).val(phone);
    $('#relationship-manager-email-'+key).val(email)
  }

  let relationship_managers_count = 1
  let relationship_managers = $('#relationship-managers')
  $(document.body).on('click', '#add-item', function (e) {
    e.preventDefault()
    let html = '<div class="col-sm-6" id="name-section-'+relationship_managers_count+'">'
        html += '<label class="form-label" for="relationship-manager-name-'+relationship_managers_count+'">Relationship Manager Name</label>'
        html += '<select name="manager_names['+relationship_managers_count+']" id="relationship-manager-name-'+relationship_managers_count+'" class="form-control" onchange="selectUser('+relationship_managers_count+')">'
        html += '<option value="">Search</option>'
          @foreach ($bank_users as $user)
            html += '<option value="'+{!! json_encode($user->name) !!}+'" data-phone="'+{!! json_encode($user->phone_number) !!}+'" data-email="'+{!! json_encode($user->email) !!}+'">'+{!! json_encode($user->name) !!}+' ('+{!! json_encode($user->email) !!}+')</option>'
          @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-6" id="email-section-'+relationship_managers_count+'">'
        html += '<label class="form-label" for="relationship-manager-email-'+relationship_managers_count+'">Relationship Manager Email</label>'
        html += '<input type="email" id="relationship-manager-email-'+relationship_managers_count+'" class="form-control" name="manager_emails['+relationship_managers_count+']" value="" readonly />'
        html += '</div>'
        html += '<div class="col-sm-6" id="mobile-section-'+relationship_managers_count+'">'
        html += '<label class="form-label" for="relationship-manager-mobile-'+relationship_managers_count+'">Relationship Manager Mobile</label>'
        html += '<input type="text" id="relationship-manager-mobile-'+relationship_managers_count+'" class="form-control" name="manager_phone_numbers['+relationship_managers_count+']" value="" readonly />'
        html += '</div>'
        html += '<div class="col-sm-6" id="blank-section-'+relationship_managers_count+'"></div>'
        html += '<div class="col-12 mb-2 mt-2" id="manager-delete-'+relationship_managers_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDiscount('+relationship_managers_count+')"></i>'
        html += '</div>'

      $(html).appendTo(relationship_managers);
      relationship_managers_count += 1;
  })

  function removeDiscount(index) {
    $('div').remove('#name-section-'+index+', #email-section-'+index+', #mobile-section-'+index+', #blank-section-'+index+', #manager-delete-'+index);
    $('#to-day-'+(Number(index) - 1)).removeAttr('readonly')
    if (index != 1) {
      $('#manager-delete-'+(Number(index) - 1)).removeClass('d-none')
    }
    relationship_managers_count -= 1;
  }

  let bank_accounts = 1

  $('#add-bank-details').on('click', function () {
    let html = '<div class="col-sm-6" id="name-as-per-bank-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
        html += '<input type="text" id="name-as-per-bank" class="form-control" name="company_names_as_per_banks['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="account-number-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="account-number">Account Number</label>'
        html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="bank-name-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="bank-name">Bank Name</label>'
        html += '<select class="form-select" id="bank-name-'+bank_accounts+'" name="bank_names['+bank_accounts+']" onchange="getSwiftCode('+bank_accounts+')">'
        @foreach ($banks as $company_bank)
          html += '<option value="'+{!! json_encode($company_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($company_bank->swift_code) !!}+'">'+{!! json_encode($company_bank->name) !!}+'</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-6" id="branch-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="bank-branch">Branch</label>'
        html += '<input type="text" id="bank-branch" class="form-control" name="branches['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="swift-code-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
        html += '<input type="text" id="swift-code-'+bank_accounts+'" class="form-control" name="swift_codes['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="account-type-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="account-type">Account Type</label>'
        html += '<input type="text" id="account-type" class="form-control" name="account_types['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-12" id="delete-item-div-'+bank_accounts+'">'
        html += '<i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem('+bank_accounts+')"></i>'
        html += '</div>'

      $(html).appendTo($('#bank-accounts'));
      bank_accounts += 1;
  })

  $('#company-name').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length >= 3) {
      $.ajax({url: `create/${$(this).val()}/check`,
        success: function (data) {
          $('.btn-submit').removeAttr('disabled')
          $('#company_name_error').addClass('d-none')
        },
        error: function (err) {
          if (err.status == 400) {
            $('#company_name_error').removeClass('d-none')
            $('.btn-submit').attr('disabled', 'disabled')
          }
        }
      })
    }
  })

  function deleteItem(index) {
    $('div').remove('#name-as-per-bank-section-'+index+', #account-number-section-'+index+', #bank-name-section-'+index+', #branch-section-'+index+', #swift-code-section-'+index+', #account-type-section-'+index+', #delete-item-div-'+index);
    bank_accounts -= 1;
  }

  function getSwiftCode(index) {
    $('#swift-code-'+index).val($('#bank-name-'+index).find(':selected').data('swiftcode'))
  }
</script>
@endsection

@section('page-style')
<style>
  [data-title]:hover:after {
    opacity: 1;
    transition: all 0.1s ease 0.5s;
    visibility: visible;
  }

  [data-title]:after {
    content: attr(data-title);
    background-color: #0b0b0b;
    color: #f9f9f9;
    font-size: 16px;
    position: absolute;
    padding: 1px 5px 2px 5px;
    bottom: -1.6em;
    left: 100%;
    box-shadow: 1px 1px 3px #222222;
    opacity: 0;
    border: 1px solid #111111;
    z-index: 99999;
    visibility: hidden;
    border-radius: 5px;
    min-width: 250px;
    max-width: 550px;
  }

  [data-title] {
    position: relative;
  }

  input[readonly]
  {
    background-color:#e8e8e8
  }
</style>
@endsection

@section('content')
<div class="">
  <h4 class="fw-light mr-4 text-nowrap my-auto">
    {{ __('Add Company')}}
  </h4>
  @if($errors->any())
    <div class="p-2 bg-label-danger ml-2 card h-fit w-100" style="height: fit-content">
      <p class="text-danger">{{ $errors->first() }} @if($errors->count() > 1) {!! __('+ ') !!} {{ $errors->count() - 1 }} {!! __(' more errors') !!} @endif</p>
    </div>
  @endif
</div>
<!-- Default -->
<div class="row">
  <!-- Vertical Wizard -->
  <div class="col-12 mb-4">
    <div class="bs-stepper vertical mt-2" id="company-details-wizard">
      <div class="bs-stepper-header">
        <div class="step" data-target="#company-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-users"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Company Details') }}</span>
              <span class="bs-stepper-subtitle">{{ __('Name') }}/KRA PIN/{{ __('Type') }}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#address-details" style="max-width: 350px; overflow-x:hidden">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-location"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Company Address Details') }}</span>
              <span class="bs-stepper-subtitle">{{ __('Location Details') }}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#relationship-manager-details" style="max-width: 350px; overflow-x:hidden">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-mood-smile"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title text-wrap">{{ __('Relationship Manager Details') }}</span>
            </span>
          </button>
        </div>
        {{-- <div class="line"></div>
        <div class="step" data-target="#bank-details" style="max-width: 350px; overflow-x:hidden">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-currency-dollar"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title text-wrap">{{ __('Bank Details') }}</span>
            </span>
          </button>
        </div> --}}
        <div class="line"></div>
        <a class="step" href="{{ route('companies.drafts', ['bank' => $bank]) }}" data-target="#drafts" id="drafts-link">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-circle-check"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Drafts') }} <small id="drafts-count" class="badge bg-danger rounded-pill">{{ $drafts->count() }}</small></span>
              <span class="bs-stepper-subtitle">{{ __('Saved Drafts') }}</span>
            </span>
          </button>
        </a>
      </div>
      <div class="bs-stepper-content">
        <form action="{{ route('companies.store', ['bank' => request()->route('bank')]) }}" method="POST" id="company-details-form" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="pipeline_id" value="@if($pipeline) {{ $pipeline->id }} @endif">
          <!-- Company Details -->
          <div id="company-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="company-name">
                  {{ __('Name') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="company-name" name="name" class="form-control" value="{{ $company_name }}" autocomplete="off" required />
                <span id="company_name_error" class="text-danger d-none">{{ __('Name already in use') }}</span>
                <x-input-error :messages="$errors->get('name')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="top-borrower-limit">
                  {{ __('Top Level Borrower Limit') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="top-borrower-limit" name="top_level_borrower_limit" class="form-control" autocomplete="off" value="{{ old('top_level_borrower_limit', $company ? number_format($company->top_level_borrower_limit) : '') }}" required />
                <x-input-error :messages="$errors->get('top_level_borrowe_limit')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="company-name">
                  {{ __('Limit Expiry Date') }}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control" name="limit_expiry_date" type="date" value="{{ old('limit_expiry_date', $company ? Carbon\Carbon::now()->format('Y-m-d') : '') }}" id="limit_expiry_date" required />
                <x-input-error :messages="$errors->get('limit_expiry_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="unique-identification-number">
                  {{ __('Unique Identification No') }}.
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="unique-identification-number" name="unique_identification_number" class="form-control" value="{{ old('unique_identification_number', $company ? $company->unique_identification_number : '') }}"  required />
                <x-input-error :messages="$errors->get('unique_identification_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="cif">
                  {{ __('CIF') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="cif" name="cif" class="form-control" value="{{ old('cif', 'CIF_' . $latest_id) }}"  required />
                <x-input-error :messages="$errors->get('cif')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="business-identification-number">
                  {{ __('Business Identification No') }}.
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="business-identification-number" name="business_identification_number" class="form-control" value="{{ $company_registration_number }}" required />
                <x-input-error :messages="$errors->get('business_identification_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="branch-code">{{ __('Branch Code') }}</label>
                <select name="branch_code" id="" class="select2">
                  <option value="">{{ __('Select Branch')}}</option>
                  @foreach ($branches as $branch)
                    <option value="{{ $branch->code }}" @if(old('branch_code') == $branch->code || $company && $company->branch_code == $branch->code) selected @endif>{{ $branch->name }} ({{ $branch->code }})</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('branch_code')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="organization-type">
                  {{ __('Organization Type') }}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="organization-type" name="organization_type" required>
                  <option value="Company" @if($company && $company->organization_type == 'Company') selected @endif>{{ __('Company')}}</option>
                  <option value="Proprietor" @if($company && $company->organization_type == 'Proprietor') selected @endif>{{ __('Proprietor')}}</option>
                  <option value="Partnership" @if($company && $company->organization_type == 'Partnership') selected @endif>{{ __('Partnership')}}</option>
                  <option value="LLP" @if($company && $company->organization_type == 'LLP') selected @endif>{{ __('LLP')}}</option>
                  <option value="Association of Persons" @if($company && $company->organization_type == 'Association of Persons') selected @endif>{{ __('Association of Persons')}}</option>
                  <option value="Cooperative Society" @if($company && $company->organization_type == 'Cooperative Society') selected @endif>{{ __('Cooperative Society')}}</option>
                  <option value="Government" @if($company && $company->organization_type == 'Government') selected @endif>{{ __('Government')}}</option>
                  <option value="Hindu Undivided Family" @if($company && $company->organization_type == 'Hindu Undivided Family') selected @endif>{{ __('Hindu Undivided Family')}}</option>
                  <option value="Private Limited" @if($company && $company->organization_type == 'Private Limited') selected @endif>{{ __('Private Limited')}}</option>
                  <option value="Public Limited" @if($company && $company->organization_type == 'Public Limited') selected @endif>{{ __('Public Limited')}}</option>
                  <option value="Trust" @if($company && $company->organization_type == 'Trust') selected @endif>{{ __('Trust')}}</option>
                  <option value="Others" @if($company && $company->organization_type == 'Others') selected @endif>{{ __('Others')}}</option>
                </select>
                <x-input-error :messages="$errors->get('organization_type')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="business-segment">
                  {{ __('Business Segment/Industry') }}.
                </label>
                {{-- <input type="text" id="business-segment" name="business_segment" class="form-control" value="{{ old('business_segment', $company ? $company->business_segment : '') }}" /> --}}
                <select name="business_segment" id="business_segment" class="form-select">
                  <option value="">{{ __('Select Industry')}}</option>
                  @foreach ($industries as $industry)
                    <option value="{{ $industry->name }}" @if(old('business_segment') == $industry->name || $company && $company->business_segment == $industry->name) selected @endif>{{ $industry->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('business_segment')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="customer-type">{{ __('Bank Customer') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="customer-type" name="customer_type">
                  <option value="Bank Customer" @if($company && $company->customer_type == 'Bank Customer') selected @endif>{{ __('Bank Customer')}}</option>
                  <option value="Non-bank Customer" @if($company && $company->organization_type == 'Non-bank Customer') selected @endif>{{ __('Non-bank Customer')}}</option>
                </select>
                <x-input-error :messages="$errors->get('customer_type')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="kra-pin">
                  {{ __('KRA PIN')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="kra-pin" class="form-control" name="kra_pin" value="{{ $kra_pin }}" required />
                <x-input-error :messages="$errors->get('kra_pin')" />
              </div>
              <div class="col-sm-6">
                <label for="formFile" class="form-label">{{ __('Company Logo') }}</label>
                <input class="form-control" type="file" id="formFile" name="company_logo" accept=".jpg,.png">
              </div>
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" disabled> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous') }}</span>
                </button>
                <div class="d-flex">
                  <a href="{{ route('companies.index', ['bank' => $bank]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                  <button type="button" class="btn btn-primary btn-next" id="submit-one"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next') }}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Company Location Details -->
          <div id="address-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="city">{{ __('City') }} <span class="text-danger">*</span></label>
                <select class="select2" id="city" name="city" required>
                  <option label=" "></option>
                  @foreach ($locations as $location)
                    <option value="{{ $location->name }}" @if($company && $company->city == $location->name) selected @endif>{{ $location->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="zip-code">{{ __('Pin') }}/{{ __('Zip') }}/{{ __('Postal') }} {{ __('Code') }} <span class="text-danger">*</span></label>
                <input type="text" id="postal-code" class="form-control" name="postal_code" value="{{ old('postal_code', $company ? $company->postal_code : '') }}" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="zip-code">{{ __('Address') }} <span class="text-danger">*</span></label>
                <input type="text" id="address" class="form-control" name="address" value="{{ old('address', $company ? $company->address : '') }}" required />
              </div>
              <div class="col-12 d-flex justify-content-between">
                <button type="button" class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous') }}</span>
                </button>
                <div class="d-flex">
                  <a href="{{ route('companies.index', ['bank' => $bank]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                  <button type="button" class="btn btn-primary btn-next" id="submit-two"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next') }}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Relationship Manager -->
          <div id="relationship-manager-details" class="content">
            <div class="row g-3">
              <div class="col-12 row mt-4" id="relationship-managers">
                @if ($company && $company->relationshipManagers)
                  @foreach ($company->relationshipManagers as $key => $manager)
                    <div class="col-sm-6">
                      <label class="form-label" for="relationship-manager-name-0">{{ __('Relationship Manager Name') }}</label>
                      <select name="manager_names[{{ $key }}]" id="relationship-manager-name-0" class="form-control" onchange="selectUser({{ $key }})">
                        <option value="">{{ __('Search') }}</option>
                        @foreach ($bank_users as $user)
                          <option value="{{ $user->name }}" @if($manager->name == $user->name) selected @endif data-phone={{ $user->phone_number }} data-email="{{ $user->email }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="relationship-manager-email-{{ $key }}">{{ __('Relationship Manager Email') }}</label>
                      <input type="email" id="relationship-manager-email-{{ $key }}" class="form-control" name="manager_emails[{{ $key }}]" value="{{ $manager->email }}" readonly />
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="relationship-manager-mobile-{{ $key }}">{{ __('Relationship Manager Mobile') }}</label>
                      <input type="text" id="relationship-manager-mobile-{{ $key }}" class="form-control" name="manager_phone_numbers[{{ $key }}]" value="{{ $manager->phone_number }}" readonly />
                    </div>
                    <div class="col-sm-6"></div>
                  @endforeach
                @else
                  <div class="col-sm-6">
                    <label class="form-label" for="relationship-manager-name-0">{{ __('Relationship Manager Name') }}</label>
                    <select name="manager_names[0]" id="relationship-manager-name-0" class="form-control" onchange="selectUser(0)">
                      <option value="">{{ __('Search')}}</option>
                      @foreach ($bank_users as $user)
                        <option value="{{ $user->name }}" data-phone={{ $user->phone_number }} data-email="{{ $user->email }}">{{ $user->name }} ({{ $user->email }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="relationship-manager-email-0">{{ __('Relationship Manager Email') }}</label>
                    <input type="email" id="relationship-manager-email-0" class="form-control" name="manager_emails[0]" readonly />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="relationship-manager-mobile-0">{{ __('Relationship Manager Mobile') }}</label>
                    <input type="text" id="relationship-manager-mobile-0" class="form-control" name="manager_phone_numbers[0]" readonly />
                  </div>
                  <div class="col-sm-6"></div>
                @endif
              </div>
              <div class="col-12">
                <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                  <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                  <span class="mx-2">
                    {{ __('Add Relationship Manager') }}
                  </span>
                </span>
              </div>
              <div class="row g-3 mt-2">
                <div class="col-12 d-flex justify-content-between">
                  <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                    <span class="align-middle d-sm-inline-block d-none">{{ __('Previous') }}</span>
                  </button>
                  <div class="d-flex">
                    <a href="{{ route('companies.index', ['bank' => $bank]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                    <button class="btn btn-primary btn-submit" type="submit">{{ __('Submit') }}</button>
                  </div>
                </div>
              </div>
              {{-- <div class="col-12 d-flex justify-content-between">
                <button type="button" class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous') }}</span>
                </button>
                <div class="d-flex">
                  <a href="{{ route('companies.index', ['bank' => $bank]) }}" class="btn btn-outline-danger mx-1">Cancel</a>
                  <button type="button" class="btn btn-primary btn-next" id="submit-three"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next') }}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div> --}}
            </div>
          </div>
          <!-- Bank Details -->
          {{-- <div id="bank-details" class="content">
            <div class="row g-3" id="bank-accounts">
              <div class="col-sm-6">
                <label class="form-label" for="name-as-per-bank">
                  {{ __('Account Name') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="name-as-per-bank" class="form-control" name="company_names_as_per_banks[]" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="account-number">
                  {{ __('Account Number') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="account-number" class="form-control" name="account_numbers[]" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="bank-name">
                  {{ __('Bank Name') }}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="bank-name-0" name="bank_names[]" required onchange="getSwiftCode(0)">
                  <option value="">{{ __('Select Bank')}}</option>
                  @foreach ($banks as $company_bank)
                    <option value="{{ $company_bank->name }}" data-swiftcode="{{ $company_bank->swift_code }}">{{ $company_bank->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="bank-branch">{{ __('Branch')}}</label>
                <input type="text" id="bank-branch" class="form-control" name="branches[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="swift-code">
                  {{ __('SWIFT Code') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="swift-code-0" class="form-control" name="swift_codes[]" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="account-type">
                  {{ __('Account Type') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="account-type" class="form-control" name="account_types[]" required />
              </div>
              <div class="col-12">
                <hr>
              </div>
            </div>
            <button class="btn btn-sm btn-primary my-2" id="add-bank-details" type="button">{{ __('Add new bank details') }}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous') }}</span>
                </button>
                <div class="d-flex">
                  <a href="{{ route('companies.index', ['bank' => $bank]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                  <button class="btn btn-primary btn-submit" type="submit">{{ __('Submit') }}</button>
                </div>
              </div>
            </div>
          </div> --}}
          <!-- Saved Drafts -->
          <div id="drafts" class="content">
            <div class="content-header mb-3">
              <h6 class="mb-0">{{ __('Drafts') }}</h6>
              <small>{{ __('Your saved drafts') }}.</small>
            </div>
            <div class="row g-3">
              {{-- <table class="table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Top Level Limit</th>
                    <th>Limit Expiry Date</th>
                    <th>Branch Code</th>
                    <th>CUST ANCODE</th>
                    <th>KRA PIN</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($drafts as $draft)
                    <tr>
                      <td>{{ $draft->name }}</td>
                      <td>{{ number_format($draft->top_level_borrower_limit) }}</td>
                      <td>{{ Carbon\Carbon::parse($draft->limit_expiry_date)->format('d M Y') }}</td>
                      <td>{{ $draft->branch_code }}</td>
                      <td>{{ $draft->cust_ancode }}</td>
                      <td>{{ $draft->kra_pin }}</td>
                      <td>
                        <a href="{{ route('companies.create', ['bank' => $bank, 'pipeline' => NULL, 'company' => $draft]) }}" class="btn btn-primary btn-sm">
                          Continue
                        </a>
                        <a href="{{ route('companies.draft.delete', ['bank' => $bank, 'company' => $draft]) }}" class="btn btn-danger btn-sm">
                          Delete
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table> --}}
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Wizard -->
</div>
@endsection
