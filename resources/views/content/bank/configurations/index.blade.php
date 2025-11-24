@extends('layouts/layoutMaster')

@section('title', 'Configurations')

@section('vendor-style')
  <link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
  <link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
  <link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
  <link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
  <link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
@endsection

@section('page-style')
  <link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
  <style>
    .tab-content {
      padding: 0px !important;
    }
    .no-label {
      margin-left: -170px !important;
    }
    .checkbox {
      margin-left: 100px !important;
    }
    .yes-label {
      margin-left: 55px !important;
    }
    .no-label-2 {
      margin-left: -60px !important;
    }
    .yes-label-2 {
      margin-left: 40px !important;
    }
    .pointer {
      cursor: pointer;
    }
  </style>
@endsection

@section('vendor-script')
  <script src="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
@endsection

@section('page-script')
  <script>
    let bank_gls_count = {!! json_encode($latest_payment_account_bank_id) !!}
    let user_permissions = {!! json_encode($user_permissions) !!}

    $('#add-bank-gl-account-btn').on('click', function () {
      bank_gls_count += 1
      let html = '<div class="row px-3 mb-2" id="gl-'+bank_gls_count+'">'
          html += '  <div class="col-md-3 d-flex flex-column">'
          html += '    <input type="text" name="bank_gl_account_name['+bank_gls_count+']" class="form-control" id="html5-text-input" />'
          html += '  </div>'
          html += '  <div class="col-md-4">'
          html += '    <input class="form-control" type="text" name="bank_gl_account_number['+bank_gls_count+']" id="html5-text-input" />'
          html += '  </div>'
          html += '  <div class="col-md-4">'
          html += '    <i class="tf-icons ti ti-trash text-danger pointer" onclick="removeGl('+bank_gls_count+')"></i>'
          html += '  </div>'
          html += '</div>'

      $(html).appendTo($('#bank-gls'))
    })

    function removeGl(index) {
      $('div').remove('#gl-'+index);
      bank_gls_count -= 1;
    }

    function selectGroupPermissions(id) {
      var checked = $('#group-'+id).is(':checked');
      if (checked) {
        $("div [data-target-role-id='" + id +"']").prop('checked', true);
      } else {
        $("div [data-target-role-id='" + id +"']").prop('checked', false);
      }
    }

    $('#select-role').on('change', function() {
      let permissions = $(this).find(':selected').data('permissions')
      let html = '<div class="mt-2">'
      if ($(this).val() == 'Bank') {
        permissions.forEach(group => {
          let counter = 0
          group.access_groups.forEach(permission => {
            if (user_permissions.includes(permission.name)) {
              counter += 1
            }
          })
          if (counter > 0) {
            html += '<div class="">'
            html += '<input type="checkbox" class="form-check-input border-primary" name="" id="group-'+group.id+'" value="' + group.id + '" onchange="selectGroupPermissions('+group.id+')" />'
            html += '<label class="form-label fw-bold px-2" for="' + group.id + '">' + group.name + '</label>'
            html += '<div class="row">'
            group.access_groups.forEach(permission => {
              if (user_permissions.includes(permission.name)) {
                html += '<div class="col-4">'
                html += '<input type="checkbox" class="form-check-input border-primary" data-target-role-id="'+permission.target_role_id+'" name="permission_ids[]" value="' + permission.id + '" />'
                html += '<label class="form-label fw-light px-2" for="' + permission.id + '">' + permission.name + '</label>'
                html += '</div>'
              }
            });
            html += '</div>'
            html += '</div>'
            html += '<hr />'
          }
        });
      } else {
        permissions.forEach(group => {
          html += '<div class="">'
          html += '<input type="checkbox" class="form-check-input border-primary" name="" id="group-'+group.id+'" value="' + group.id + '" onchange="selectGroupPermissions('+group.id+')" />'
          html += '<label class="form-label fw-bold px-2" for="' + group.id + '">' + group.name + '</label>'
          html += '<div class="row">'
          group.access_groups.forEach(permission => {
            html += '<div class="col-4">'
            html += '<input type="checkbox" class="form-check-input border-primary" data-target-role-id="'+permission.target_role_id+'" name="permission_ids[]" value="' + permission.id + '" />'
            html += '<label class="form-label fw-light px-2" for="' + permission.id + '">' + permission.name + '</label>'
            html += '</div>'
          });
          html += '</div>'
          html += '</div>'
          html += '<hr />'
        });
      }
      html += '</div>'

      $('#permissions-section').html(html)
    })

    $('#productType').on('change', function() {
      let productCodes = $(this).find(':selected').data('product-codes');
      if (productCodes === undefined || productCodes.length === 0) {
        $('#productCodeContainer').addClass('d-none');
        $('#productCode').html('<option value="">' + '{{ __('Select Product Code') }}' + '</option>');
        return;
      }
      let html = '<option value="">' + '{{ __('Select Product Code') }}' + '</option>';
      if (productCodes) {
        productCodes.forEach(code => {
          html += '<option value="' + code.id + '">' + code.name + '</option>';
        });
      }
      $('#productCodeContainer').removeClass('d-none');
      $('#productCode').html(html);
    });
  </script>
@endsection

@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('Configurations') }}</span>
</h4>
<div class="nav-align-top mb-4">
  <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
    @can('Manage Product Configurations')
      <li class="nav-item">
        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bank-details" aria-controls="navs-pills-bank-details" aria-selected="true"><i class="tf-icons ti ti-file-text ti-xs me-1"></i> {{ __('Basic Configurations') }}</button>
      </li>
      @if (collect($bank->product_types)->contains('vendor_financing') || collect($bank->product_types)->contains('factoring'))
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-configurations" aria-controls="navs-pills-configurations" aria-selected="false"><i class="tf-icons ti ti-clipboard-check ti-xs me-1"></i> {{ __('Vendor Financing Configurations') }}</button>
        </li>
      @endif
      @if (collect($bank->product_types)->contains('dealer_financing'))
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-maker-checker" aria-controls="navs-pills-maker-checker" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> {{ __('Dealer Financing Configurations') }}</button>
        </li>
      @endif
    @endcan
    @can('Manage Platform Configurations')
      <li class="nav-item">
        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-system" aria-controls="navs-pills-system" aria-selected="false"><i class="tf-icons ti ti-settings ti-xs me-1"></i> {{ __('Platform Configurations') }}</button>
      </li>
    @endcan
  </ul>
  <div class="tab-content">
    @can('Manage Product Configurations')
      <div class="tab-pane fade show active" id="navs-pills-bank-details" role="tabpanel">
        <form action="{{ route('configurations.withholding.update', ['bank' => $bank]) }}" method="POST">
          @csrf
          <div class="">
            <h5 class="fw-bold py-3 mb-2">
              <span class="px-3">{{ __('Bank GLs') }}</span>
            </h5>
            <div id="bank-gls">
              @foreach ($bank_payment_accounts as $key => $account)
                <div class="row px-3 mb-2">
                  <div class="col-md-3 d-flex flex-column">
                    <input type="text" name="bank_gl_account_name[{{ $account->id }}]" value="{{ $account->account_name }}" class="form-control" id="html5-text-input" required />
                  </div>
                  <div class="col-md-4">
                    <input class="form-control" type="text" name="bank_gl_account_number[{{ $account->id }}]" value="{{ $account->account_number }}" id="html5-text-input" required />
                  </div>
                  <div class="col-md-2">
                    @if (!$account->is_active)
                      <span class="badge bg-label-warning">{{ __('Awaiting Approval') }}</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          @can('Manage Product Configurations')
            <div class="my-2 mx-3">
              <button class="btn btn-primary" type="button" id="add-bank-gl-account-btn">{{ __('Add') }}</button>
            </div>
            <div class="d-flex my-2 mx-3">
              <button class="btn btn-primary" type="submit">
                {{ __('Submit') }}
              </button>
            </div>
          @endcan
        </form>
        <hr>
        <div class="">
          <div class="d-flex justify-content-between">
            <h5 class="fw-bold px-3">{{ __('Company KYC Documents') }}</h5>
            @can('Manage Product Configurations')
              <button class="btn btn-secondary mx-2 my-1" data-bs-toggle="modal" data-bs-target="#requestDocumentsModal">{{ __('Add Document') }}</button>
            @endcan
            <div class="modal modal-top fade" id="requestDocumentsModal" tabindex="-1">
              <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('configurations.compliance.document.add', ['bank' => $bank]) }}">
                  @csrf
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTopTitle">{{ __('Add KYC Document') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="form-group">
                      <label for="Product Type">{{ __('Product Type') }}</label>
                      <select class="form-select" name="product_type" id="productType">
                        <option value="">{{ __('Select Product Type') }}</option>
                        <option value="">{{ __('All') }}</option>
                        @foreach ($product_types as $product_type)
                          <option value="{{ $product_type->id }}" data-product-codes="{{ $product_type->programCodes }}">{{ Str::headline($product_type->name) }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group d-none mt-2" id="productCodeContainer">
                      <label for="Product Code">{{ __('Product Code') }}</label>
                      <select class="form-select" name="product_code" id="productCode">
                        <option value="">{{ __('Select Product Code') }}</option>
                      </select>
                    </div>
                    <div class="form-group mt-2">
                      <label for="nameSlideTop" class="form-label">{{ __('Enter Document Name') }}</label>
                      <input class="form-control" id="" name="name" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <form action="{{ route('configurations.compliance.documents.update', ['bank' => $bank]) }}" method="post">
            @csrf
            <div class="d-flex flex-column mb-2">
              @foreach ($documents as $key => $document)
                <div class="d-flex flex-wrap mx-3 gap-2">
                  @if ($key == "")
                    <h6 class="my-auto">
                      {{ __('All Program Types') }}:
                    </h6>
                    <div class="d-flex flex-wrap my-2">
                      @foreach ($document as $document_data)
                        <div class="d-flex">
                          <input class="form-control" type="text" name="name[{{ $document_data->name }}]" value="{{ $document_data->name }}" id="html5-text-input" />
                          <a href="{{ route('configurations.compliance.documents.delete', ['bank' => $bank, 'bank_document' => $document_data]) }}" title="Delete {{ $document_data->name }}" class="mx-2 my-auto">
                            <i class="tf-icons ti ti-trash text-danger"></i>
                          </a>
                        </div>
                      @endforeach
                    </div>
                  @else
                    @if ($key == 'Vendor Financing')
                      <div class="d-flex flex-column gap-2">
                        @foreach ($document->groupBy('programCode.name') as $key => $document_data)
                          <div class="d-flex flex-wrap gap-2">
                            <h6 class="my-auto">
                              {{ $key }}:
                            </h6>
                            <div class="d-flex flex-wrap gap-2 my-2">
                              @foreach ($document_data as $document_details)
                              <div class="d-flex">
                                <input class="form-control" type="text" name="name[{{ $document_details->name }}]" value="{{ $document_details->name }}" id="html5-text-input" />
                                <a href="{{ route('configurations.compliance.documents.delete', ['bank' => $bank, 'bank_document' => $document_details]) }}" title="Delete {{ $document_details->name }}" class="mx-2 my-auto">
                                  <i class="tf-icons ti ti-trash text-danger"></i>
                                </a>
                              </div>
                              @endforeach
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @elseif($key == 'Dealer Financing')
                      <h6 class="my-auto">
                        {{ $key }}:
                      </h6>
                      <div class="d-flex flex-wrap my-2">
                        @foreach ($document as $document_data)
                        <div class="d-flex">
                          <input class="form-control" type="text" name="name[{{ $document_data->name }}]" value="{{ $document_data->name }}" id="html5-text-input" />
                          <a href="{{ route('configurations.compliance.documents.delete', ['bank' => $bank, 'bank_document' => $document_data]) }}" title="Delete {{ $document_data->name }}" class="mx-2 my-auto">
                            <i class="tf-icons ti ti-trash text-danger"></i>
                          </a>
                        </div>
                        @endforeach
                      </div>
                    @endif
                  @endif
                </div>
              @endforeach
            </div>
            @can('Manage Product Configurations')
              <div class="d-flex my-2 mx-3">
                <button class="btn btn-primary" type="submit">
                  {{ __('Submit') }}
                </button>
              </div>
            @endcan
          </form>
        </div>
        <hr>
        <div class="mb-2">
          <div class="d-flex justify-content-between">
            <h5 class="fw-bold px-3">{{ __('Payment Request Rejection Reasons') }}</h5>
            @can('Manage Product Configurations')
              <button class="btn btn-secondary mx-2 my-1" data-bs-toggle="modal" data-bs-target="#paymentRequestRejectionReason">{{ __('Add Reason') }}</button>
            @endcan
            <div class="modal modal-top fade" id="paymentRequestRejectionReason" tabindex="-1">
              <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('configurations.rejection-reason.store', ['bank' => $bank]) }}">
                  @csrf
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTopTitle">{{ __('Add Payment Request Rejection Reason') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="form-group">
                      <label for="nameSlideTop" class="form-label">{{ __('Enter Reason Here') }}</label>
                      <textarea class="form-control" id="" name="reason" rows="8" maxlength="30"></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          @foreach ($rejection_reasons as $rejection_reason)
            <div class="mx-3 d-flex justify-content-between w-25">
              <span class="text-wrap">{{ $rejection_reason->reason }}</span>
              <a href="{{ route('configurations.rejection-reason.delete', ['bank' => $bank, 'bank_rejection_reason' => $rejection_reason]) }}" class="mx-2 my-auto">
                <i class="tf-icons ti ti-trash text-danger"></i>
              </a>
            </div>
            <hr>
          @endforeach
        </div>
      </div>
      @if (collect($bank->product_types)->contains('vendor_financing') || collect($bank->product_types)->contains('factoring'))
        <div class="tab-pane fade" id="navs-pills-configurations" role="tabpanel">
          <h5 class="fw-bold py-3 mb-2">
            <span class="px-3">{{ __('Vendor Financing Configurations')}}</span>
          </h5>
          <h5 class="fw-bold py-3 mb-2">
            <span class="px-3">{{ __('Specific Configuration')}}</span>
          </h5>
          <form action="{{ route('configurations.specific.update', ['bank' => $bank]) }}" method="POST">
            @csrf
            <input type="hidden" name="product" value="vendor financing">
            @if (collect($bank->product_types)->contains('vendor_financing'))
              <div class="accordion mx-1 border border-primary rounded my-1" id="accordionStyle1">
                <div class="accordion-item card">
                  <div class="accordion-header">
                    <h6 class="text-primary my-4 mx-3" data-bs-toggle="collapse" data-bs-target="#accordionStyle3" aria-expanded="false" style="cursor: pointer">{{ __('Vendor Finance Receivable')}} <i class="tf-icons ti ti-caret-down"></i></h6>
                  </div>
                  <div id="accordionStyle3" class="accordion-collapse collapse" data-bs-parent="#accordionStyle3">
                    <div class="accordion-body">
                      @foreach ($product_configurations as $product_configuration)
                        @if ($product_configuration->section == 'Vendor Finance Receivable')
                          <div class="row px-3 mb-2">
                            <div class="col-md-3">
                              <label for="html5-text-input" class="col-form-label">{{ $product_configuration->name }}</label>
                            </div>
                            <div class="col-md-3">
                              <select name="configuration_id[{{ $product_configuration->id }}]" id="" class="form-control">
                                <option value="">{{ __('Select Account')}}</option>
                                @foreach ($bank_payment_accounts->where('is_active', true) as $bank_payment_account)
                                  <option value="{{ $bank_payment_account->account_number }}" @if($product_configuration->value === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-md-6 d-flex justify-content-center text-nowrap">
                              <div class="form-check form-switch mb-2">
                                <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('Not Branch Specific')}}</label>
                                <input class="form-check-input mx-3" type="checkbox" id="flexSwitchCheckChecked" name="branch_specific_configuration[{{ $product_configuration->id }}]" @if($product_configuration->branch_specific) checked @endif>
                                <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Branch Specific')}}</label>
                              </div>
                            </div>
                          </div>
                        @endif
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            @endif
            @if (collect($bank->product_types)->contains('factoring'))
              <div class="accordion mx-1 border border-primary rounded my-1" id="accordionStyle1">
                <div class="accordion-item card">
                  <div class="accordion-header">
                    <h6 class="text-primary my-4 mx-3" data-bs-toggle="collapse" data-bs-target="#accordionStyle4" aria-expanded="false" style="cursor: pointer">{{ __('Factoring Without Recourse' )}}<i class="tf-icons ti ti-caret-down"></i></h6>
                  </div>
                  <div id="accordionStyle4" class="accordion-collapse collapse" data-bs-parent="#accordionStyle4">
                    <div class="accordion-body">
                      @foreach ($product_configurations as $product_configuration)
                        @if ($product_configuration->section == 'Factoring Without Recourse')
                          <div class="row px-3 mb-2">
                            <div class="col-md-3">
                              <label for="html5-text-input" class="col-form-label">{{ $product_configuration->name }}</label>
                            </div>
                            <div class="col-md-3">
                              <select name="configuration_id[{{ $product_configuration->id }}]" id="" class="form-control">
                                <option value="">{{ __('Select Account')}}</option>
                                @foreach ($bank_payment_accounts->where('is_active', true) as $bank_payment_account)
                                  <option value="{{ $bank_payment_account->account_number }}" @if($product_configuration->value === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-md-6 d-flex justify-content-center text-nowrap">
                              <div class="form-check form-switch mb-2">
                                <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('Not Branch Specific')}}</label>
                                <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="branch_specific_configuration[{{ $product_configuration->id }}]" @if ($product_configuration->branch_specific) checked @endif>
                                <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Branch Specific')}}</label>
                              </div>
                            </div>
                          </div>
                        @endif
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
              <div class="accordion mx-1 border border-primary rounded my-1" id="accordionStyle1">
                <div class="accordion-item card">
                  <div class="accordion-header">
                    <h6 class="text-primary my-4 mx-3" data-bs-toggle="collapse" data-bs-target="#accordionStyle5" aria-expanded="false" style="cursor: pointer">{{ __('Factoring With Recourse')}} <i class="tf-icons ti ti-caret-down"></i></h6>
                  </div>
                  <div id="accordionStyle5" class="accordion-collapse collapse" data-bs-parent="#accordionStyle5">
                    <div class="accordion-body">
                      @foreach ($product_configurations as $product_configuration)
                        @if ($product_configuration->section == 'Factoring With Recourse')
                          <div class="row px-3 mb-2">
                            <div class="col-md-3">
                              <label for="html5-text-input" class="col-form-label">{{ $product_configuration->name }}</label>
                            </div>
                            <div class="col-md-3">
                              <select name="configuration_id[{{ $product_configuration->id }}]" id="" class="form-control">
                                <option value="">{{ __('Select Account')}}</option>
                                @foreach ($bank_payment_accounts->where('is_active', true) as $bank_payment_account)
                                  <option value="{{ $bank_payment_account->account_number }}" @if($product_configuration->value === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-md-6 d-flex justify-content-center text-nowrap">
                              <div class="form-check form-switch mb-2">
                                <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('Not Branch Specific')}}</label>
                                <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="branch_specific_configuration[{{ $product_configuration->id }}]" @if ($product_configuration->branch_specific) checked @endif>
                                <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Branch Specific')}}</label>
                              </div>
                            </div>
                          </div>
                        @endif
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            @endif
            <div class="mt-4">
              @foreach ($general_configurations as $general_configuration)
                @if (collect($bank->product_types)->contains('vendor_financing') && $general_configuration->productType->name === 'Vendor Financing')
                  @if (!$general_configuration->section)
                    <div class="row px-3 mb-2">
                      <div class="col-md-8 d-flex flex-column">
                        <label for="html5-text-input" class="col-form-label">{{ Str::headline($general_configuration->name) }}</label>
                        <small>{{ $general_configuration->description }}</small>
                      </div>
                      <div class="col-md-4">
                        @switch($general_configuration->input_type)
                            @case('number')
                              <input class="form-control" type="number" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                              @break
                            @case('text')
                              <input class="form-control" type="text" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                              @break
                            @case('checkbox')
                              <div class="form-switch mb-2 px-0">
                                <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[1]) }}</label>
                                <input class="form-check-input mx-3" type="checkbox" id="flexSwitchCheckChecked" name="general_configuration[{{ $general_configuration->id }}]" @if ($general_configuration->value === $general_configuration->input_options[0]) checked @endif>
                                <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[0]) }}</label>
                              </div>
                              @break
                            @case('select')
                              <select name="general_configuration[{{ $general_configuration->id }}]" id="" class="form-control">
                                @foreach ($general_configuration->input_options as $option)
                                  <option value="{{ $option }}" @if($general_configuration->value === $option) selected @endif>{{ Str::headline($option) }}</option>
                                @endforeach
                              </select>
                              @break
                            @case('select-multiple')
                              @php($selected_values = explode(",", str_replace('"', '', str_replace(']', '', str_replace('[', '', $general_configuration->value)))))
                              <select name="general_configuration[{{ $general_configuration->id }}][]" id="" class="form-control select2" multiple>
                                @foreach ($general_configuration->input_options as $option)
                                  <option value="{{ $option }}" @if(collect($selected_values)->contains($option)) selected @endif>{{ Str::headline($option) }}</option>
                                @endforeach
                              </select>
                              @break
                            @default
                              <input class="form-control" type="text" name="general_configuration[{{ $general_configuration->id }}]" placeholder="8" value="{{ $general_configuration->value }}" id="html5-text-input" />
                        @endswitch
                      </div>
                    </div>
                  @endif
                @endif
              @endforeach
              {{-- Maker Checker --}}
              <h6 class="text-primary my-4 mx-3">{{ __('Maker / Checker')}}</h6>
              @foreach ($general_configurations as $general_configuration)
                @if (collect($bank->product_types)->contains('vendor_financing') && $general_configuration->productType->name === 'Vendor Financing')
                  @if ($general_configuration->section == 'Maker/Checker')
                    <div class="row px-3 mb-2">
                      <div class="col-md-8 d-flex flex-column">
                        <label for="html5-text-input" class="col-form-label">{{ Str::headline($general_configuration->name) }}</label>
                        <small>{{ $general_configuration->description }}</small>
                      </div>
                      <div class="col-md-4">
                        @switch($general_configuration->input_type)
                            @case('number')
                              <input class="form-control" type="number" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                              @break
                            @case('text')
                              <input class="form-control" type="text" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                              @break
                            @case('checkbox')
                              <div class="form-switch mb-2 px-0">
                                <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[1]) }}</label>
                                <input class="form-check-input mx-3" type="checkbox" id="flexSwitchCheckChecked" name="general_configuration[{{ $general_configuration->id }}]" @if ($general_configuration->value === $general_configuration->input_options[0]) checked @endif>
                                <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[0]) }}</label>
                              </div>
                              @break
                            @case('select')
                              <select name="general_configuration[{{ $general_configuration->id }}]" id="" class="form-control">
                                @foreach ($general_configuration->input_options as $option)
                                  <option value="{{ $option }}">{{ Str::headline($option) }}</option>
                                @endforeach
                              </select>
                              @break
                            @default
                              <input class="form-control" type="text" name="general_configuration[{{ $general_configuration->id }}]" placeholder="8" value="{{ $general_configuration->value }}" id="html5-text-input" />
                        @endswitch
                      </div>
                    </div>
                  @endif
                @endif
              @endforeach
              {{-- End Maker Checker --}}
              @if (collect($bank->product_types)->contains('vendor_financing'))
                <h6 class="text-primary my-4 mx-3">{{ __('VF - Repayment Priorities')}}</h6>
                <div class="row px-3 mb-2">
                  <div class="col-md-3"><h6>{{ __('Particulars')}}</h6></div>
                  <div class="col-md-6 d-flex justify-content-center"><h6 style="margin-left: -150px;">{{ __('Discount Indicator')}}</h6></div>
                  <div class="col-md-3"><h6>{{ __('Pre-Maturity Priority')}}</h6></div>
                </div>
              @endif
              @foreach ($repayment_priorities as $repayment_priority)
                @if (collect($bank->product_types)->contains('vendor_financing') && $repayment_priority->productType->name === 'Vendor Financing')
                  <div class="row px-3 mb-2">
                    <div class="col-md-3">
                      <label for="html5-text-input" class="col-form-label">{{ $repayment_priority->particulars }}</label>
                    </div>
                    <div class="col-md-6 d-flex justify-content-center text-nowrap">
                      <div class="form-check form-switch mb-2">
                        <label class="form-check-label" for="flexSwitchCheckChecked" style="margin-left: -170px;">{{ __('Non Discount Bearing')}}</label>
                        <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="discount_indicator[{{ $repayment_priority->id }}]" @if($repayment_priority->discount_indicator === 'discount bearing') checked @endif>
                        <label class="form-check-label" for="flexSwitchCheckChecked" style="margin-left: 40px;">{{ __('Discount Bearing')}}</label>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <input class="form-control" type="number" min="0" name="premature_priority[{{ $repayment_priority->id }}]" value="{{ $repayment_priority->premature_priority }}" id="html5-text-input" />
                    </div>
                  </div>
                @endif
              @endforeach
            </div>
            @can('Manage Product Configurations')
              <div class="d-flex justify-content-end my-2 mx-3">
                <button class="btn btn-primary">
                  {{ __('Submit')}}
                </button>
              </div>
            @endcan
          </form>
        </div>
      @endif
      @if (collect($bank->product_types)->contains('dealer_financing'))
        <div class="tab-pane fade" id="navs-pills-maker-checker" role="tabpanel">
          <h5 class="fw-bold py-3 mb-2">
            <span class="fw-light px-3">{{ __('Dealer Financing Configurations')}}</span>
          </h5>
          <h6 class="text-primary my-4 mx-3">{{ __('Specific Configurations')}}</h6>
          <form action="{{ route('configurations.specific.update', ['bank' => $bank]) }}" method="POST">
            @csrf
            <input type="hidden" name="product" value="dealer financing">
            @foreach ($product_configurations as $product_configuration)
              @if ($product_configuration->productType->name === 'Dealer Financing')
                <div class="row px-3 mb-2">
                  <div class="col-md-5 d-flex flex-column">
                    <label for="html5-text-input" class="col-form-label">{{ $product_configuration->name }}</label>
                    @if ($product_configuration->description)
                      <small>{{ $product_configuration->description }}</small>
                    @endif
                  </div>
                  <div class="col-md-3">
                    {{-- <input class="form-control" type="text" name="configuration_id[{{ $product_configuration->id }}]" value="{{ $product_configuration->value }}" id="html5-text-input" /> --}}
                    <select name="configuration_id[{{ $product_configuration->id }}]" id="" class="form-control">
                      <option value="">{{ __('Select Account')}}</option>
                      @foreach ($bank_payment_accounts as $bank_payment_account)
                        <option value="{{ $bank_payment_account->account_number }}" @if($product_configuration->value === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4 d-flex justify-content-end text-nowrap">
                    <div class="form-check form-switch mb-2">
                      <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('Not Branch Specific')}}</label>
                      <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="branch_specific_configuration[{{ $product_configuration->id }}]" @if($product_configuration->branch_specific) checked @endif>
                      <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ __('Branch Specific')}}</label>
                    </div>
                  </div>
                </div>
              @endif
            @endforeach
            @foreach ($general_configurations as $general_configuration)
              @if ($general_configuration->productType->name === 'Dealer Financing')
                @if (!$general_configuration->section)
                  <div class="row px-3 mb-2">
                    <div class="col-md-8 d-flex flex-column">
                      <label for="html5-text-input" class="col-form-label">{{ Str::headline($general_configuration->name) }}</label>
                      <small>{{ $general_configuration->description }}</small>
                    </div>
                    <div class="col-md-4">
                      @switch($general_configuration->input_type)
                          @case('number')
                            <input class="form-control" type="number" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                            @break
                          @case('text')
                            <input class="form-control" type="text" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                            @break
                          @case('checkbox')
                            <div class="form-switch mb-2 px-0">
                              <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[1]) }}</label>
                              <input class="form-check-input mx-3" type="checkbox" id="flexSwitchCheckChecked" name="general_configuration[{{ $general_configuration->id }}]" @if ($general_configuration->value === $general_configuration->input_options[0]) checked @endif>
                              <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[0]) }}</label>
                            </div>
                            @break
                          @case('select')
                            <select name="general_configuration[{{ $general_configuration->id }}]" id="" class="form-control">
                              @foreach ($general_configuration->input_options as $option)
                                <option value="{{ $option }}">{{ Str::headline($option) }}</option>
                              @endforeach
                            </select>
                            @break
                          @case('select-multiple')
                            @php($selected_values = explode(",", str_replace('"', '', str_replace(']', '', str_replace('[', '', $general_configuration->value)))))
                            <select name="general_configuration[{{ $general_configuration->id }}][]" id="" class="form-control select2" multiple>
                              @foreach ($general_configuration->input_options as $option)
                                <option value="{{ $option }}" @if(collect($selected_values)->contains($option)) selected @endif>{{ Str::headline($option) }}</option>
                              @endforeach
                            </select>
                            @break
                          @default
                            <input class="form-control" type="text" name="general_configuration[{{ $general_configuration->id }}]" placeholder="8" value="{{ $general_configuration->value }}" id="html5-text-input" />
                      @endswitch
                    </div>
                  </div>
                @endif
              @endif
            @endforeach
            {{-- Maker Checker --}}
            <h6 class="text-primary my-4 mx-3">{{ __('Maker / Checker')}}</h6>
            @foreach ($general_configurations as $general_configuration)
              @if ($general_configuration->productType->name == 'Dealer Financing')
                @if ($general_configuration->section == 'Maker/Checker')
                  <div class="row px-3 mb-2">
                    <div class="col-md-8 d-flex flex-column">
                      <label for="html5-text-input" class="col-form-label">{{ Str::headline($general_configuration->name) }}</label>
                      <small>{{ $general_configuration->description }}</small>
                    </div>
                    <div class="col-md-4">
                      @switch($general_configuration->input_type)
                          @case('number')
                            <input class="form-control" type="number" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                            @break
                          @case('text')
                            <input class="form-control" type="text" placeholder="8" name="general_configuration[{{ $general_configuration->id }}]" value="{{ $general_configuration->value }}" id="html5-text-input" />
                            @break
                          @case('checkbox')
                            <div class="form-switch mb-2 px-0">
                              <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[1]) }}</label>
                              <input class="form-check-input mx-3" type="checkbox" id="flexSwitchCheckChecked" name="general_configuration[{{ $general_configuration->id }}]" @if ($general_configuration->value === $general_configuration->input_options[0]) checked @endif>
                              <label class="form-check-label" for="flexSwitchCheckChecked">{{ Str::headline($general_configuration->input_options[0]) }}</label>
                            </div>
                            @break
                          @case('select')
                            <select name="general_configuration[{{ $general_configuration->id }}]" id="" class="form-control">
                              @foreach ($general_configuration->input_options as $option)
                                <option value="{{ $option }}">{{ Str::headline($option) }}</option>
                              @endforeach
                            </select>
                            @break
                          @default
                            <input class="form-control" type="text" name="general_configuration[{{ $general_configuration->id }}]" placeholder="8" value="{{ $general_configuration->value }}" id="html5-text-input" />
                      @endswitch
                    </div>
                  </div>
                @endif
              @endif
            @endforeach
            <h6 class="text-primary my-4 mx-3">{{ __('DF - Repayment Priorities')}}</h6>
            <div class="row px-3 mb-2">
              <div class="col-md-3"><h6>{{ __('Particulars')}}</h6></div>
              <div class="col-md-3 d-flex justify-content-center"><h6 style="margin-left: -150px;">{{ __('Discount Indicator')}}</h6></div>
              <div class="col-md-3"><h6>{{ __('Pre-Maturity Priority')}}</h6></div>
              <div class="col-md-3"><h6>{{ __('NPA Priority')}}</h6></div>
            </div>
            @foreach ($repayment_priorities as $repayment_priority)
              @if ($repayment_priority->productType->name == 'Dealer Financing')
                <div class="row px-3 mb-2">
                  <div class="col-md-3">
                    <label for="html5-text-input" class="col-form-label">{{ $repayment_priority->particulars }}</label>
                  </div>
                  <div class="col-md-3 d-flex justify-content-center text-nowrap">
                    <div class="form-check form-switch mb-2">
                      <label class="form-check-label" for="flexSwitchCheckChecked" style="margin-left: -170px;">{{ __('Non Discount Bearing')}}</label>
                      <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="discount_indicator[{{ $repayment_priority->id }}]" @if($repayment_priority->discount_indicator === 'discount bearing') checked @endif>
                      <label class="form-check-label" for="flexSwitchCheckChecked" style="margin-left: 40px;">{{ __('Discount Bearing')}}</label>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <input class="form-control" type="number" min="0" name="premature_priority[{{ $repayment_priority->id }}]" value="{{ $repayment_priority->premature_priority }}" id="html5-text-input" />
                  </div>
                  <div class="col-md-3">
                    <input class="form-control" type="number" min="0" name="npa_priority[{{ $repayment_priority->id }}]" value="{{ $repayment_priority->npa_priority }}" id="html5-text-input" />
                  </div>
                </div>
              @endif
            @endforeach
            @can('Manage Product Configurations')
              <div class="d-flex justify-content-end my-2 mx-3">
                <button class="btn btn-primary" type="submit">
                  {{ __('Submit')}}
                </button>
              </div>
            @endcan
          </form>
        </div>
      @endif
    @endcan
    @can('Manage Platform Configurations')
      <div class="tab-pane fade" id="navs-pills-system" role="tabpanel">
        <h5 class="fw-bold py-1 my-2">
          <span class="fw-light px-3">{{ __('System Configurations')}}</span>
        </h5>
        <form action="{{ route('configurations.platform.update', ['bank' => $bank]) }}" method="post" class="px-3 py-2" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <h6 class="fw-bold py-1">
              {{ __('Logo and Appearance')}}
            </h6>
            <div class="col-sm-12 col-md-6 form-group py-1">
              <label for="" class="form-label">{{ __('Logo')}}</label>
              <input type="file" name="logo" id="" accept=".png,.jpg" class="form-control">
              <x-input-error :messages="$errors->get('logo')" />
            </div>
            <div class="col-sm-12 col-md-6 form-group py-1">
              <label for="" class="form-label">{{ __('Favicon')}}</label>
              <input type="file" name="favicon" id="" accept=".png,.jpg" class="form-control">
              <x-input-error :messages="$errors->get('favicon')" />
            </div>
            <div class="col-sm-12 col-md-6 form-group py-1">
              <label for="primary color" class="form-label">{{ __('Primary Color')}}</label>
              <input type="text" name="primary_color" id="" class="form-control" value="{{ $admin_configurations ? $admin_configurations->primary_color : '' }}">
              <x-input-error :messages="$errors->get('primary_color')" />
            </div>
            <div class="col-sm-12 col-md-6 form-group py-1">
              <label for="secondary-color" class="form-label">{{ __('Secondary Color')}}</label>
              <input type="text" name="secondary_color" id="" class="form-control" value="{{ $admin_configurations ? $admin_configurations->secondary_color : '' }}">
              <x-input-error :messages="$errors->get('secondary_color')" />
            </div>
            <div class="col-sm-12 col-md-6 form-group py-1">
              <label for="secondary-color" class="form-label">{{ __('Page Title')}}</label>
              <input type="text" name="page_title" id="" class="form-control" value="{{ $admin_configurations ? $admin_configurations->page_title : '' }}">
              <x-input-error :messages="$errors->get('page_title')" />
            </div>
            <div class="col-sm-12 col-md-6 form-group py-1">
              <label for="secondary-color" class="form-label">{{ __('Date Format')}}</label>
              <select name="date_format" class="form-control" id="">
                <option value="">{{ __('Select Format') }}</option>
                @foreach ($date_formats as $date_format)
                    <option value="{{ $date_format }}" @if($admin_configurations && $date_format && explode('(Ex.', $date_format)[0] === $admin_configurations->date_format) selected @endif>{{ $date_format }}</option>
                @endforeach
              </select>
              <x-input-error :messages="$errors->get('date_format')" />
            </div>
          </div>
          <div class="d-flex justify-content-end mt-2">
            <button class="btn btn-primary" type="submit">{{ __('Submit')}}</button>
          </div>
        </form>
      </div>
    @endcan
  </div>
</div>
@endsection
