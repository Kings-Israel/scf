@extends('layouts/layoutMaster')

@section('title', 'Edit Company')

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
  $(document).ready(function () {
    $('top-borrower-limit').val(Number($('#top-borrower-limit').val().toLocaleString()))
  })

  $('#top-borrower-limit').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })

  function selectUser(key) {
    let email = $('#relationship-manager-name-'+key).find(':selected').data('email')
    let phone = $('#relationship-manager-name-'+key).find(':selected').data('phone')
    $('#relationship-manager-mobile-'+key).val(phone);
    $('#relationship-manager-email-'+key).val(email)
  }

  let company = {!! json_encode($company) !!}
  let relationship_managers_count = company.relationship_managers.length
  let relationship_managers = $('#relationship-managers')
  $(document.body).on('click', '#add-item', function (e) {
    e.preventDefault()
    let html = '<div class="col-sm-12" id="manager-id-section-'+relationship_managers_count+'">'
        html += '<input type="hidden" value="-1" name="manager_details['+relationship_managers_count+']">'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="relationship-manager-name-'+relationship_managers_count+'">Relationship Manager Name</label>'
        html += '<select name="manager_names['+relationship_managers_count+']" id="relationship-manager-name-'+relationship_managers_count+'" class="form-control" onchange="selectUser('+relationship_managers_count+')">'
        html += '<option value="">Search</option>'
          @foreach ($bank->users as $user)
            html += '<option value="'+{!! json_encode($user->name) !!}+'" data-phone="'+{!! json_encode($user->phone_number) !!}+'" data-email="'+{!! json_encode($user->email) !!}+'">'+{!! json_encode($user->name) !!}+' ('+{!! json_encode($user->email) !!}+')</option>'
          @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="relationship-manager-email-'+relationship_managers_count+'">Relationship Manager Email</label>'
        html += '<input type="email" id="relationship-manager-email-'+relationship_managers_count+'" class="form-control" name="manager_emails['+relationship_managers_count+']" value="" readonly />'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="relationship-manager-mobile-'+relationship_managers_count+'">Relationship Manager Mobile</label>'
        html += '<input type="text" id="relationship-manager-mobile-'+relationship_managers_count+'" class="form-control" name="manager_phone_numbers['+relationship_managers_count+']" value="" readonly />'
        html += '</div>'
        html += '<div class="col-sm-6"></div>'

      $(html).appendTo(relationship_managers);
      relationship_managers_count += 1;
  })

  // let bank_accounts = company.bank_details.length

  // $('#add-bank-details').on('click', function () {
  //   let html = '<div class="col-sm-12" id="bank-id-section-'+bank_accounts+'">'
  //       html += '<input type="hidden" value="-1" name="bank_details['+bank_accounts+']">'
  //       html += '</div>'
  //       html += '<div class="col-sm-6" id="name-as-per-bank-section-'+bank_accounts+'">'
  //       html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
  //       html += '<input type="text" id="name-as-per-bank" class="form-control" name="company_names_as_per_banks['+bank_accounts+']" />'
  //       html += '</div>'
  //       html += '<div class="col-sm-6" id="account-number-section-'+bank_accounts+'">'
  //       html += '<label class="form-label" for="account-number">Account Number</label>'
  //       html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+bank_accounts+']" />'
  //       html += '</div>'
  //       html += '<div class="col-sm-6" id="bank-name-section-'+bank_accounts+'">'
  //       html += '<label class="form-label" for="bank-name">Bank Name</label>'
  //       html += '<select class="form-select" id="bank-name-'+bank_accounts+'" name="bank_names['+bank_accounts+']" onchange="getSwiftCode('+bank_accounts+')">'
  //       @foreach ($banks as $company_bank)
  //         html += '<option value="'+{!! json_encode($company_bank->name) !!}+'" data-swiftcode="'+{!! $company_bank->swift_code !!}+'">'+{!! json_encode($company_bank->name) !!}+'</option>'
  //       @endforeach
  //       html += '</select>'
  //       html += '</div>'
  //       html += '<div class="col-sm-6" id="branch-section-'+bank_accounts+'">'
  //       html += '<label class="form-label" for="bank-branch">Branch</label>'
  //       html += '<input type="text" id="bank-branch" class="form-control" name="branches['+bank_accounts+']" />'
  //       html += '</div>'
  //       html += '<div class="col-sm-6" id="swift-code-section'+bank_accounts+'">'
  //       html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
  //       html += '<input type="text" id="swift-code-'+bank_accounts+'" class="form-control" name="swift_codes['+bank_accounts+']" />'
  //       html += '</div>'
  //       html += '<div class="col-sm-6" id="account-type-section-'+bank_accounts+'">'
  //       html += '<label class="form-label" for="account-type">Account Type</label>'
  //       html += '<input type="text" id="account-type" class="form-control" name="account_types['+bank_accounts+']" />'
  //       html += '</div>'
  //       html += '<div class="col-12">'
  //       html += '<hr>'
  //       html += '</div>'

  //     $(html).appendTo($('#bank-accounts'));
  //     bank_accounts += 1;
  // })

  $('#company-name').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length >= 3) {
      $.ajax({url: `edit/${$(this).val()}/check`,
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

  // function getSwiftCode(index) {
  //   $('#swift-code-'+index).val($('#bank-name-'+index).find(':selected').data('swiftcode'))
  // }

  $('.btn-submit').on('click', function(e) {
    e.preventDefault()
    $('#show-confirm-modal').click()
  })
  $("form").bind("keypress", function (e) {
    if (e.keyCode == 13) {
      e.preventDefault()
      $('#show-confirm-modal').click()
    }
  });
</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2 d-flex justify-content-between">
  <span class="fw-light">{{ __('Edit Company')}}</span>
</h4>
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
              <span class="bs-stepper-title">{{ __('Company Details')}}</span>
              <span class="bs-stepper-subtitle">{{ __('Name') }}/KRA PIN/{{ __('Type') }}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#address-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-location"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Company Address Details')}}</span>
              <span class="bs-stepper-subtitle">{{ __('Location Details')}}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#relationship-manager-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-mood-smile"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title text-wrap">{{ __('Relationship Manager Details')}}</span>
            </span>
          </button>
        </div>
        {{-- <div class="line"></div>
        <div class="step" data-target="#bank-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-currency-dollar"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title text-wrap">{{ __('Bank Details')}}</span>
            </span>
          </button>
        </div> --}}
        <div class="line d-none"></div>
        <div class="step d-none" data-target="#drafts">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-circle-check"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Drafts')}}</span>
              <span class="bs-stepper-subtitle">{{ __('Saved Drafts')}}</span>
            </span>
          </button>
        </div>
      </div>
      <div class="bs-stepper-content">
        <form action="{{ route('companies.update', ['bank' => request()->route('bank'), 'company' => $company]) }}" method="POST" id="company-details-form" enctype="multipart/form-data">
          @csrf
          <!-- Company Details -->
          <div id="company-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="company-name">{{ __('Name')}} <span class="text-danger">*</span></label>
                <input type="text" id="company-name" name="name" class="form-control" value="{{ $company->name }}" required />
                <span id="company_name_error" class="text-danger d-none">{{ __('Name already in use')}}</span>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="top-borrower-limit">{{ __('Top Level Borrower Limit')}} <span class="text-danger">*</span></label>
                <input type="text" id="top-borrower-limit" name="top_level_borrower_limit" class="form-control" value="{{ number_format($company->top_level_borrower_limit) }}" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="company-name">{{ __('Limit Expiry Date')}} <span class="text-danger">*</span></label>
                <input class="form-control" name="limit_expiry_date" type="date" id="html5-date-input" value="{{ Carbon\Carbon::parse($company->limit_expiry_date)->format('Y-m-d') }}" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="unique-identification-number">{{ __('Unique Identification No')}}. <span class="text-danger">*</span></label>
                <input type="text" id="unique-identification-number" name="unique_identification_number" class="form-control" value="{{ $company->unique_identification_number }}" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="cif">
                  {{ __('CIF') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="cif" name="cif" class="form-control" value="{{ old('cif', $company->cif) }}"  required />
                <x-input-error :messages="$errors->get('cif')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="branch-code">{{ __('Branch Code')}}</label>
                {{-- <input type="text" id="branch-code" class="form-control" name="branch_code" value="{{ $company->branch_code }}" /> --}}
                <select name="branch_code" id="" class="select2">
                  <option value="">{{ __('Select Branch')}}</option>
                  @foreach ($branches as $branch)
                    <option value="{{ $branch->code }}" @if(old('branch_code') == $branch->code || $company->branch_code == $branch->code) selected @endif>{{ $branch->name }} ({{ $branch->code }})</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('branch_code')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="business-identification-number">{{ __('Business Identification No')}}. <span class="text-danger">*</span></label>
                <input type="text" id="business-identification-number" name="business_identification_number" class="form-control" value="{{ $company->business_identification_number }}" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="organization-type">{{ __('Organization Type')}}</label>
                <select class="form-select" id="organization-type" name="organization_type">
                  <option value="Company" @if($company->organization_type == 'Company') selected @endif>{{ __('Company')}}</option>
                  <option value="Proprietor" @if($company->organization_type == 'Proprietor') selected @endif>{{ __('Proprietor')}}</option>
                  <option value="Partnership" @if($company->organization_type == 'Partnership') selected @endif>{{ __('Partnership')}}</option>
                  <option value="LLP" @if($company->organization_type == 'LLP') selected @endif>{{ __('LLP')}}</option>
                  <option value="Association of Persons" @if($company->organization_type == 'Associations of Persons') selected @endif>{{ __('Association of Persons')}}</option>
                  <option value="Cooperative Society" @if($company->organization_type == 'Cooperative Society') selected @endif>{{ __('Cooperative Society')}}</option>
                  <option value="Government" @if($company->organization_type == 'Government') selected @endif>{{ __('Government')}}</option>
                  <option value="Hindu Undivided Family" @if($company->organization_type == 'Hindu Undivided Family') selected @endif>{{ __('Hindu Undivided Family')}}</option>
                  <option value="Private Limited" @if($company->organization_type == 'Private Limited') selected @endif>{{ __('Private Limited')}}</option>
                  <option value="Public Limited" @if($company->organization_type == 'Public Limited') selected @endif>{{ __('Public Limited')}}</option>
                  <option value="Trust" @if($company->organization_type == 'Trust') selected @endif>{{ __('Trust')}}</option>
                  <option value="Others" @if($company->organization_type == 'Others') selected @endif>{{ __('Others')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="business-segment">
                  {{ __('Business Segment/Industry') }}.
                  <span class="text-danger">*</span>
                </label>
                {{-- <input type="text" id="business-segment" name="business_segment" class="form-control" value="{{ old('business_segment', $company ? $company->business_segment : '') }}" /> --}}
                <select name="business_segment" id="" class="select2" required>
                  <option value="">{{ __('Select Industry')}}</option>
                  @foreach ($industries as $industry)
                    <option value="{{ $industry->name }}" @if(old('business_segment') == $industry->name || $company->business_segment == $industry->name) selected @endif>{{ $industry->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('business_segment')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="customer-type">{{ __('Customer Type')}}</label>
                <select class="form-select" id="customer-type" name="customer_type">
                  <option value="Bank Customer" @if($company->customer_type == 'Bank Customer') selected @endif>{{ __('Bank Customer')}}</option>
                  <option value="Non-bank Customer" @if($company->customer_type == 'Non-bank Customer') selected @endif>{{ __('Non-bank Customer')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="kra-pin">{{ __('KRA PIN')}} <span class="text-danger">*</span></label>
                <input type="text" id="kra-pin" class="form-control" name="kra_pin" value="{{ $company->kra_pin }}" required />
              </div>
              <div class="col-sm-6">
                <label for="formFile" class="form-label">{{ __('Company Logo')}}</label>
                <input class="form-control" type="file" id="formFile" name="company_logo" accept=".jpg,.png">
              </div>
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" disabled> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button type="button" class="btn btn-primary btn-next"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Company Location Details -->
          <div id="address-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="city">{{ __('City') }}</label>
                <select class="select2" id="city" name="city">
                  <option label=" "></option>
                  @foreach ($locations as $location)
                    <option value="{{ $location->name }}" @if($company->city == $location->name) selected @endif>{{ $location->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="zip-code">{{ __('Pin/Zip/Postal Code')}}</label>
                <input type="text" id="zip-code" class="form-control" name="postal_code" value="{{ $company->postal_code }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="zip-code">{{ __('Address')}}</label>
                <input type="text" id="zip-code" class="form-control" name="address" value="{{ $company->address }}" />
              </div>
              <div class="col-12 d-flex justify-content-between">
                <button type="button" class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button type="button" class="btn btn-primary btn-next"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Relationship Manager -->
          <div id="relationship-manager-details" class="content">
            <div class="row g-3">
              <div class="col-12 row mt-4" id="relationship-managers">
                @foreach ($company->relationshipManagers as $key => $relationship_manager)
                  <div class="col-sm-12" id="manager-id-section-{{ $key }}">
                    <input type="hidden" value="{{ $relationship_manager->id }}" name="manager_details[{{ $key }}]">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="relationship-manager-name-{{ $key }}">{{ __('Relationship Manager Name')}}</label>
                    <select name="manager_names[{{ $key }}]" id="relationship-manager-name-{{ $key }}" class="form-control" onchange="selectUser({{ $key }})">
                      <option value="">{{ __('Search')}}</option>
                      @foreach ($bank->users as $user)
                        <option value="{{ $user->name }}" data-phone="{{ $user->phone_number }}" data-email="{{ $user->email }}" @if($relationship_manager->email == $user->email) selected @endif>{{ $user->name }} ({{ $user->email }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="relationship-manager-email-{{ $key }}">{{ __('Relationship Manager Email')}}</label>
                    <input type="email" id="relationship-manager-email-{{ $key }}" class="form-control" name="manager_emails[{{ $key }}]" value="{{ $relationship_manager->email }}" readonly />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="relationship-manager-mobile-{{ $key }}">{{ __('Relationship Manager Mobile')}}</label>
                    <input type="text" id="relationship-manager-mobile-{{ $key }}" class="form-control" name="manager_phone_numbers[{{ $key }}]" value="{{ $relationship_manager->phone_number }}" readonly />
                  </div>
                  <div class="col-sm-6"></div>
                @endforeach
              </div>
              <div class="col-12">
                <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                  <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                  <span class="mx-2">
                    {{ __('Add Relationship Manager')}}
                  </span>
                </span>
              </div>
              <div class="col-12 d-flex justify-content-between">
                {{-- <button type="button" class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button type="button" class="btn btn-primary btn-next"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button> --}}
                <div class="col-12 d-flex justify-content-between">
                  <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                    <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                  </button>
                  <button class="btn btn-primary btn-submit" type="submit">{{ __('Submit')}}</button>
                </div>
                <button class="btn d-none" type="button" id="show-confirm-modal" data-bs-toggle="modal" data-bs-target="#confirm-update-modal"></button>
                <div class="modal fade" id="confirm-update-modal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ __('Update Company')}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <h4>{{ __('Are you sure you want to update the company?')}}</h4>
                      </div>
                      <div class="modal-footer">
                        <a href="{{ route('companies.show', ['bank' => $bank, 'company' => $company]) }}" class="btn btn-secondary">{{ __('Cancel')}}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Update')}}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Bank Details -->
          {{-- <div id="bank-details" class="content">
            <div class="row g-3" id="bank-accounts">
              @foreach ($company->bankDetails as $key => $bank_details)
                <div class="col-sm-12" id="bank-id-section-{{ $key }}">
                  <input type="hidden" value="{{ $bank_details->id }}" name="bank_details[{{ $key }}]">
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="name-as-per-bank">{{ __('Account Name')}}</label>
                  <input type="text" id="name-as-per-bank" class="form-control" name="company_names_as_per_banks[{{ $key }}]" value="{{ $bank_details->name_as_per_bank }}" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="account-number">{{ __('Account Number')}}</label>
                  <input type="text" id="account-number" class="form-control" name="account_numbers[{{ $key }}]" value="{{ $bank_details->account_number }}" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="bank-name">{{ __('Bank Name')}}</label>
                  <select class="form-select" id="bank-name-{{ $key }}" name="bank_names[{{ $key }}]" onchange="getSwiftCode({{ $key }})">
                    @foreach ($banks as $company_bank)
                      <option value="{{ $company_bank->name }}" data-swiftcode="{{ $company_bank->swift_code }}" @if($bank_details->bank_name == $company_bank->name) selected @endif>{{ $company_bank->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="bank-branch">{{ __('Branch')}}</label>
                  <input type="text" id="bank-branch" class="form-control" name="branches[{{ $key }}]" value="{{ $bank_details->branch }}" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="swift-code">{{ __('SWIFT Code')}}</label>
                  <input type="text" id="swift-code-{{ $key }}" class="form-control" name="swift_codes[{{ $key }}]" value="{{ $bank_details->swift_code }}" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="account-type">{{ __('Account Type')}}</label>
                  <input type="text" id="account-type" class="form-control" name="account_types[{{ $key }}]" value="{{ $bank_details->account_type }}" />
                </div>
                <div class="col-12">
                  <hr>
                </div>
              @endforeach
            </div>
            <button class="btn btn-sm btn-primary my-2" id="add-bank-details" type="button">{{ __('Add new bank details')}}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-submit" type="submit">{{ __('Submit')}}</button>
              </div>
            </div>
            <button class="btn d-none" type="button" id="show-confirm-modal" data-bs-toggle="modal" data-bs-target="#confirm-update-modal"></button>
            <div class="modal fade" id="confirm-update-modal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">{{ __('Update Company')}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <h4>{{ __('Are you sure you want to update the company?')}}</h4>
                  </div>
                  <div class="modal-footer">
                    <a href="{{ route('companies.show', ['bank' => $bank, 'company' => $company]) }}" class="btn btn-secondary">{{ __('Cancel')}}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update')}}</button>
                  </div>
                </div>
              </div>
            </div>
          </div> --}}
          <!-- Saved Drafts -->
          <div id="drafts" class="content d-none">
            <div class="content-header mb-3">
              <h6 class="mb-0">{{ __('Drafts')}}</h6>
              <small>{{ __('Your saved drafts')}}.</small>
            </div>
            <div class="row g-3">

            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Wizard -->
</div>
@endsection
