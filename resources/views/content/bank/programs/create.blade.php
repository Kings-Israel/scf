@extends('layouts/layoutMaster')

@section('title', 'Add Program')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
{{-- <link rel="stylesheet" href="{{asset('assets/vendor/libs/dropzone/dropzone.css')}}" /> --}}
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
<script src="{{asset('assets/js/add-program-wizard.js')}}"></script>
<script src="{{asset('assets/js/form-wizard-validation.js')}}"></script>
<script>
  $('#program-limit').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })

  $('#limit-per-account').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })

  let program = {!! json_encode($program) !!}
  let relationship_managers_count = 0
  let selected_managers = []

  let bank = {!! json_encode($bank) !!}

  let bank_users = {!! $bank_users !!}

  if (program) {
    if (program.program_type.name == 'Vendor Financing') {
      getProgramCodes()
      $('.vendor-financing').removeClass('d-none')
      $('.dealer-financing').addClass('d-none')
      $('.collection-account').removeClass('d-none')
      $('#max-financing-days').removeAttr('required')
      $('#max-financing-days').removeAttr('min')
      $('#min-financing-days').removeAttr('required')
      $('#min-financing-days').removeAttr('min')
      $('#from-day-0').removeAttr('required')
      $('#to-day-0').removeAttr('required')
      $('#dealer-benchmark-title').removeAttr('required')
      $('#dealer-business-strategy-spread-0').removeAttr('required')
      $('#dealer-credit-spread-0').removeAttr('required')
      $('#dealer-total-roi-0').removeAttr('required')
      // $('#benchmark-title').attr('required', true)
      // $('#business-strategy-spread').attr('required', true)
      // $('#credit-spread').attr('required', true)
      // $('#total-roi').attr('required', true)
      changeVendorFinancingType()
    }

    if (program.program_type.name == 'Dealer Financing') {
      $('.vendor-financing').addClass('d-none')
      $('.dealer-financing').removeClass('d-none')
      $('#eligibility').val(100)
      // $('#from-day-0').attr('required', true)
      // $('#to-day-0').attr('required', true)
      // $('#dealer-benchmark-title').attr('required', true)
      // $('#dealer-business-strategy-spread-0').attr('required', true)
      // $('#dealer-credit-spread-0').attr('required', true)
      // $('#dealer-total-roi-0').attr('required', true)
      $('#benchmark-title').removeAttr('required')
      $('#business-strategy-spread').removeAttr('required')
      $('#credit-spread').removeAttr('required')
      $('#total-roi').removeAttr('required')
    }

    if (program.anchor) {
      let html = ''
      if (program.anchor.bank_details.length > 0) {
        bank_accounts = program.anchor.bank_details.length
        program.anchor.bank_details.forEach((bank_details, index) => {
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
          html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks['+index+']" value="'+bank_details.name_as_per_bank+'" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="account-number">Account Number</label>'
          html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+index+']" value="'+bank_details.account_number+'" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="bank-name">Bank Name</label>'
          html += '<select class="form-select" id="bank-name-'+index+'" name="bank_names['+index+']" onchange="getSwiftCode('+index+')">'
          @foreach ($banks as $vendor_bank)
            if (bank_details.bank_name == {!! json_encode($vendor_bank->name) !!}) {
              html += '<option value="'+{!! json_encode($vendor_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($vendor_bank->swift_code) !!}+'" selected>'+{!! json_encode($vendor_bank->name) !!}+'</option>'
            } else {
              html += '<option value="'+{!! json_encode($vendor_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($vendor_bank->swift_code) !!}+'">'+{!! json_encode($vendor_bank->name) !!}+'</option>'
            }
          @endforeach
          html += '</select>'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="bank-branch">Branch</label>'
          html += '<input type="text" id="bank-branch" class="form-control" name="branches['+index+']" value="'+bank_details.branch+'" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
          html += '<input type="text" id="swift-code-'+index+'" class="form-control" name="swift_codes['+index+']" value="'+bank_details.swift_code+'" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="account-type">Account Type</label>'
          html += '<input type="text" id="account-type" class="form-control" name="account_types['+index+']" value="'+bank_details.account_type+'" />'
          html += '</div>'
          html += '<div class="col-12">'
          html += '<hr>'
          html += '</div>'

          $(html).appendTo($('#bank-accounts'));
          bank_accounts += 1;
        })
      } else {
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
        html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[]" required />'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="account-number">Account Number</label>'
        html += '<input type="text" id="account-number" class="form-control" name="account_numbers[]" required />'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="bank-name">Bank Name</label>'
        html += '<select class="form-select" id="bank-name-0" name="bank_names[]" onchange="getSwiftCode(0)" required>'
        @foreach ($banks as $buyer_bank)
          html += '<option value="'+{!! json_encode($buyer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($buyer_bank->swift_code) !!}+'">'+{!! json_encode($buyer_bank->name) !!}+'</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="bank-branch">Branch</label>'
        html += '<input type="text" id="bank-branch" class="form-control" name="branches[]" required />'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
        html += '<input type="text" id="swift-code-0" class="form-control" name="swift_codes[]" required />'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '<label class="form-label" for="account-type">Account Type</label>'
        html += '<input type="text" id="account-type" class="form-control" name="account_types[]" required />'
        html += '</div>'
        html += '<div class="col-12">'
        html += '<hr>'
        html += '</div>'

        $(html).appendTo($('#bank-accounts'));
      }

      let relationship_manager_html = ''
      if (program.anchor.relationship_managers.length > 0) {
        relationship_managers_count = program.anchor.relationship_managers.length
        program.anchor.relationship_managers.forEach((user, key) => {
          relationship_manager_html += '<div class="col-sm-6">'
          relationship_manager_html += '  <label class="form-label" for="bank-user-email">Bank Email</label>'
          relationship_manager_html += '  <select type="email" id="bank-user-email-'+key+'" class="form-control" name="bank_user_emails['+key+']" onchange="selectBankUser('+key+')">'
          relationship_manager_html += '    <option value="">Select Bank User</option>'
          bank_users.forEach(bank_user => {
            if (bank_user.email == user.email) {
              relationship_manager_html += '<option value="'+bank_user.email+'" selected data-name="'+bank_user.name+'" data-phone-number="'+bank_user.phone_number+'">'+bank_user.email+'</option>'
            } else {
              relationship_manager_html += '<option value="'+bank_user.email+'" data-name="'+bank_user.name+'" data-phone-number="'+bank_user.phone_number+'">'+bank_user.email+'</option>'
            }
          })
          relationship_manager_html += '  </select>'
          relationship_manager_html += '</div>'
          relationship_manager_html += '<div class="col-sm-6">'
          relationship_manager_html += '  <label class="form-label" for="bank-user-name">Bank User Name</label>'
          relationship_manager_html += '  <input id="bank-user-name-'+key+'" class="form-control" name="bank_user_names['+key+']" value="'+user.name+'" readonly />'
          relationship_manager_html += '</div>'
          relationship_manager_html += '<div class="col-sm-6">'
          relationship_manager_html += '  <label class="form-label" for="bank-user-phone-number">Bank User Mobile No.</label>'
          relationship_manager_html += '  <input type="text" id="bank-user-phone-number-'+key+'" class="form-control" name="bank_user_phone_numbers['+key+']" value="'+user.phone_number+'" readonly />'
          relationship_manager_html += '</div>'
          relationship_manager_html += '<div class="col-6"></div>'
        });
      } else {
        relationship_manager_html += '<div class="col-sm-6">'
        relationship_manager_html += '  <label class="form-label" for="bank-user-email">Bank Email</label>'
        relationship_manager_html += '  <select type="email" id="bank-user-email-'+key+'" class="form-control" name="bank_user_emails['+key+']" onchange="selectBankUser('+key+')">'
        relationship_manager_html += '    <option value="">Select Bank User</option>'
        bank_users.forEach(user => {
          relationship_manager_html += '<option value="'+user.email+'" data-name="'+user.name+'" data-phone-number="'+user.phone_number+'">'+user.email+'</option>'
        })
        relationship_manager_html += '  </select>'
        relationship_manager_html += '</div>'
        relationship_manager_html += '<div class="col-sm-6">'
        relationship_manager_html += '  <label class="form-label" for="bank-user-name">Bank User Name</label>'
        relationship_manager_html += '  <input id="bank-user-name-'+relationship_managers_count+'" class="form-control" name="bank_user_names['+relationship_managers_count+']" readonly />'
        relationship_manager_html += '</div>'
        relationship_manager_html += '<div class="col-sm-6">'
        relationship_manager_html += '  <label class="form-label" for="bank-user-phone-number">Bank User Mobile No.</label>'
        relationship_manager_html += '  <input type="text" id="bank-user-phone-number-'+relationship_managers_count+'" class="form-control" name="bank_user_phone_numbers['+relationship_managers_count+']" readonly />'
        relationship_manager_html += '</div>'
        relationship_manager_html += '<div class="col-6"></div>'
      }

      $(relationship_manager_html).appendTo('#program-relationship-managers')
    }
  }

  let bank_product_types = {!! json_encode(collect($bank->product_types)->map(fn ($product_type) => Str::headline(str_replace('_', ' ', $product_type)))) !!}

  function getProgramCodes() {
    $(document).ready(() => {
      let codes = $('#product-type').find(':selected').data('codes')

      let codeOptions = document.getElementById('product-code')
      while (codeOptions.options.length) {
        codeOptions.remove(0);
      }
      if (codes) {
        var i;
        for (i = 0; i < codes.length; i++) {
          if (codes[i].name == 'Factoring With Recourse' || codes[i].name == 'Factoring Without Recourse') {
            if (bank_product_types.includes('Factoring')) {
              var program_codes = new Option(codes[i].name+' - '+codes[i].abbrev, codes[i].id);
            }
          } else {
            var program_codes = new Option(codes[i].name+' - '+codes[i].abbrev, codes[i].id);
          }
          if (program && program.program_code_id == codes[i].id) {
            program_codes.setAttribute('selected', true);
          }
          codeOptions.options.add(program_codes);
        }
      }
    })
  }

  function changeProgramType() {
    let program_type = $('#product-type').find(':selected').text();

    if (program_type == 'Vendor Financing') {
      getProgramCodes()
      $('.vendor-financing').removeClass('d-none')
      $('.dealer-financing').addClass('d-none')
      $('.repayment-appropriation').removeClass('d-none')
      $('.collection-account').removeClass('d-none')
      $('#max-financing-days').removeAttr('required')
      $('#max-financing-days').removeAttr('min')
      $('#min-financing-days').removeAttr('required')
      $('#min-financing-days').removeAttr('min')
      $('#from-day-0').removeAttr('required')
      $('#to-day-0').removeAttr('required')
      $('#dealer-benchmark-title').removeAttr('required')
      $('#dealer-business-strategy-spread-0').removeAttr('required')
      $('#dealer-credit-spread-0').removeAttr('required')
      $('#dealer-total-roi-0').removeAttr('required')
      // $('#benchmark-title').attr('required', true)
      // $('#business-strategy-spread').attr('required', true)
      // $('#credit-spread').attr('required', true)
      // $('#total-roi').attr('required', true)

      let fees_account = {!! json_encode($vendor_financing_fees_income_account->value) !!}
      let penal_account = {!! json_encode($vendor_financing_penal_account->value) !!}

      if (!penal_account) {
        $('#penal-discount-on-principle').attr('readonly', 'readonly')
        $('#penal-discount-on-interest').val(0)
        $('.vendor-financing-penal-message').removeClass('d-none')
      } else {
        $('#penal-discount-on-principle').removeAttr('readonly')
        $('.vendor-financing-penal-message').addClass('d-none')
      }

      if (!fees_account) {
        $('#fee-name-0').attr('readonly', 'readonly')
        $('#fee-type-0').attr('readonly', 'readonly')
        $('#fee-value-0').attr('readonly', 'readonly')
        $('#anchor-fee-bearing-0').attr('readonly', 'readonly')
        $('#vendor-fee-bearing-0').attr('readonly', 'readonly')
        $('#taxes-0').attr('readonly', 'readonly')
        $('.add-item-section').addClass('d-none')

        $('#vendor-financing-fees-message').removeClass('d-none')
      } else {
        $('#fee-name-0').removeAttr('readonly')
        $('#fee-type-0').removeAttr('readonly')
        $('#fee-value-0').removeAttr('readonly')
        $('#anchor-fee-bearing-0').removeAttr('readonly')
        $('#vendor-fee-bearing-0').removeAttr('readonly')
        $('#taxes-0').removeAttr('readonly')
        $('.add-item-section').removeClass('d-none')

        $('#vendor-financing-fees-message').addClass('d-none')
      }
    }

    if (program_type == 'Dealer Financing') {
      $('.vendor-financing').addClass('d-none')
      $('.dealer-financing').removeClass('d-none')
      $('#eligibility').val(100)
      $('.repayment-appropriation').addClass('d-none')
      // $('#from-day-0').attr('required', true)
      // $('#to-day-0').attr('required', true)
      // $('#dealer-benchmark-title').attr('required', true)
      // $('#dealer-business-strategy-spread-0').attr('required', true)
      // $('#dealer-credit-spread-0').attr('required', true)
      // $('#dealer-total-roi-0').attr('required', true)
      $('#benchmark-title').removeAttr('required')
      $('#business-strategy-spread').removeAttr('required')
      $('#credit-spread').removeAttr('required')
      $('#total-roi').removeAttr('required')

      let fees_account = {!! json_encode($dealer_fees_income_account->value) !!}

      let penal_account = {!! json_encode($dealer_financing_penal_account->value) !!}

      if (!penal_account) {
        $('#dealer-penal-discount-on-principle').attr('readonly', 'readonly')
        $('#dealer-penal-discount-on-interest').val(0)
        $('.dealer-financing-penal-message').removeClass('d-none')
      } else {
        $('#dealer-penal-discount-on-principle').removeAttr('readonly')
        $('.dealer-financing-penal-message').addClass('d-none')
      }

      if (!fees_account) {
        $('#dealer-fee-name-0').attr('readonly', 'readonly')
        $('#dealer-fee-type-0').attr('readonly', 'readonly')
        $('#dealer-fee-value-0').attr('readonly', 'readonly')
        $('#dealer-fee-bearing-0').attr('readonly', 'readonly')
        $('#dealer-taxes-0').attr('readonly', 'readonly')
        $('#add-dealer-item').addClass('d-none')

        $('.dealer-financing-fees-message').removeClass('d-none')
        $('.dealer-financing-add-fees-btn').addClass('d-none')
      } else {
        $('#dealer-fee-name-0').removeAttr('readonly')
        $('#dealer-fee-type-0').removeAttr('readonly')
        $('#dealer-fee-value-0').removeAttr('readonly')
        $('#dealer-fee-bearing-0').removeAttr('readonly')
        $('#dealer-taxes-0').removeAttr('readonly')
        $('#add-dealer-item').removeClass('d-none')

        $('.dealer-financing-fees-message').addClass('d-none')
        $('.dealer-financing-add-fees-btn').removeClass('d-none')
      }
    }

    $('.buyer-invoice-approval-required').addClass('d-none')
  }

  let anchor_fee_bearing_label = 'Anchor Bearing'

  function changeVendorFinancingType() {
    $(document).ready(() => {
      let product_code = $('#product-code').find(':selected').text();

      if (product_code == 'Vendor Financing Receivable - VFR') {
        $('#anchor-label').text('Anchor')
        $('#anchor-discount-bearing-label').text('Anchor Discount Bearing')
        anchor_fee_bearing_label = 'Anchor Bearing'
        $('#anchor-fee-bearing-label').text(anchor_fee_bearing_label)
        $('#anchor-discount-bearing-title').attr('data-title', 'Discount Bourne by the Anchor')
        $('.collection-account').removeClass('d-none')
        $('.recourse').removeClass('d-none')
        $('.factoring-payment-account').addClass('d-none')
        $('.buyer-invoice-approval-required').addClass('d-none')

        let fees_account = {!! json_encode($vendor_financing_fees_income_account->value) !!}

        let penal_account = {!! json_encode($vendor_financing_penal_account->value) !!}

        if (!penal_account) {
          $('#penal-discount-on-principle').attr('readonly', 'readonly')
          $('#penal-discount-on-interest').val(0)
          $('.vendor-financing-penal-message').removeClass('d-none')
        } else {
          $('#penal-discount-on-principle').removeAttr('readonly')
          $('.vendor-financing-penal-message').addClass('d-none')
        }

        if (!fees_account) {
          $('#fee-name-0').attr('readonly', 'readonly')
          $('#fee-type-0').attr('readonly', 'readonly')
          $('#fee-value-0').attr('readonly', 'readonly')
          $('#anchor-fee-bearing-0').attr('readonly', 'readonly')
          $('#vendor-fee-bearing-0').attr('readonly', 'readonly')
          $('#taxes-0').attr('readonly', 'readonly')
          $('.add-item-section').addClass('d-none')

          $('#vendor-financing-fees-message').removeClass('d-none')
        } else {
          $('#fee-name-0').removeAttr('readonly')
          $('#fee-type-0').removeAttr('readonly')
          $('#fee-value-0').removeAttr('readonly')
          $('#anchor-fee-bearing-0').removeAttr('readonly')
          $('#vendor-fee-bearing-0').removeAttr('readonly')
          $('#taxes-0').removeAttr('readonly')
          $('.add-item-section').removeClass('d-none')

          $('#vendor-financing-fees-message').addClass('d-none')
        }
      }

      if (product_code == 'Factoring With Recourse - FR') {
        $('#anchor-label').text('Vendor')
        $('#anchor-discount-bearing-label').text('Buyer Discount Bearing')
        anchor_fee_bearing_label = 'Buyer Bearing'
        $('#anchor-fee-bearing-label').text(anchor_fee_bearing_label)
        $('#anchor-discount-bearing-title').attr('data-title', 'Discount Bourne by the Buyer')
        $('.recourse').addClass('d-none')
        $('.collection-account').addClass('d-none')
        $('.factoring-payment-account').removeClass('d-none')
        $('.buyer-invoice-approval-required').removeClass('d-none')
        let fees_account = {!! json_encode($factoring_fees_income_account->value) !!}
        if (!fees_account) {
          $('#fee-name-0').attr('readonly', 'readonly')
          $('#fee-type-0').attr('readonly', 'readonly')
          $('#fee-value-0').attr('readonly', 'readonly')
          $('#anchor-fee-bearing-0').attr('readonly', 'readonly')
          $('#vendor-fee-bearing-0').attr('readonly', 'readonly')
          $('#taxes-0').attr('readonly', 'readonly')
          $('.add-item-section').addClass('d-none')

          $('#vendor-financing-fees-message').removeClass('d-none')
        } else {
          $('#fee-name-0').removeAttr('readonly')
          $('#fee-type-0').removeAttr('readonly')
          $('#fee-value-0').removeAttr('readonly')
          $('#anchor-fee-bearing-0').removeAttr('readonly')
          $('#vendor-fee-bearing-0').removeAttr('readonly')
          $('#taxes-0').removeAttr('readonly')
          $('.add-item-section').removeClass('d-none')

          $('#vendor-financing-fees-message').addClass('d-none')
        }

        let penal_account = {!! json_encode($factoring_penal_account->value) !!}

        if (!penal_account) {
          $('#penal-discount-on-principle').attr('readonly', 'readonly')
          $('#penal-discount-on-interest').val(0)
          $('.vendor-financing-penal-message').removeClass('d-none')
        } else {
          $('#penal-discount-on-principle').removeAttr('readonly')
          $('.vendor-financing-penal-message').addClass('d-none')
        }
      }

      if (product_code == 'Factoring Without Recourse - FWR') {
        $('#anchor-label').text('Vendor')
        $('#anchor-discount-bearing-label').text('Buyer Discount Bearing')
        anchor_fee_bearing_label = 'Buyer Bearing'
        $('#anchor-fee-bearing-label').text(anchor_fee_bearing_label)
        $('#anchor-discount-bearing-title').attr('data-title', 'Discount Bourne by the Buyer')
        $('.recourse').addClass('d-none')
        $('.collection-account').addClass('d-none')
        $('.factoring-payment-account').addClass('d-none')
        $('.buyer-invoice-approval-required').removeClass('d-none')
        let fees_account = {!! json_encode($factoring_fees_income_account->value) !!}
        if (!fees_account) {
          $('#fee-name-0').attr('readonly', 'readonly')
          $('#fee-type-0').attr('readonly', 'readonly')
          $('#fee-value-0').attr('readonly', 'readonly')
          $('#anchor-fee-bearing-0').attr('readonly', 'readonly')
          $('#vendor-fee-bearing-0').attr('readonly', 'readonly')
          $('#taxes-0').attr('readonly', 'readonly')
          $('.add-item-section').addClass('d-none')

          $('#vendor-financing-fees-message').removeClass('d-none')
        } else {
          $('#fee-name-0').removeAttr('readonly')
          $('#fee-type-0').removeAttr('readonly')
          $('#fee-value-0').removeAttr('readonly')
          $('#anchor-fee-bearing-0').removeAttr('readonly')
          $('#vendor-fee-bearing-0').removeAttr('readonly')
          $('#taxes-0').removeAttr('readonly')
          $('.add-item-section').removeClass('d-none')

          $('#vendor-financing-fees-message').addClass('d-none')
        }

        let penal_account = {!! json_encode($factoring_penal_account->value) !!}

        if (!penal_account) {
          $('#penal-discount-on-principle').attr('readonly', 'readonly')
          $('#penal-discount-on-interest').val(0)
          $('.vendor-financing-penal-message').removeClass('d-none')
        } else {
          $('#penal-discount-on-principle').removeAttr('readonly')
          $('.vendor-financing-penal-message').addClass('d-none')
        }
      }
    })
  }

  function updateResetFrequency() {
    let days = $('#reset-frequency').find(":selected").data('days');

    if (!days) {
      $('#reset-frequency-days').removeAttr('readonly');
    } else {
      $('#reset-frequency-days').attr('readonly', true);
      $('#reset-frequency-days').val(days);
    }
  }

  function changeDiscountRates() {
    // Benchmark rate
    let rate = $('#benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-benchmark-rate').val(rate);
    if (rate) {
      current_benchmark_rate.val(rate)
    }

    // Business Strategy Spread
    let business_strategy_spread = $('#business-strategy-spread');
    // Credit Spread
    let credit_spread = $('#credit-spread');
    // Total ROI
    let total_roi = $('#total-roi');
    // Total Spread
    let total_spread = $('#total-spread');
    // Anchor Discount Bearing
    let anchor_discount_bearing = $('#anchor-discount-bearing');
    // Vendor Discount bearing
    let vendor_discount_bearing = $('#vendor-discount-bearing');

    let daily_discount_charge = $('#daily-discount-charge');

    if (business_strategy_spread.val() != '' && credit_spread.val() != '') {
      let business_spread = float(business_strategy_spread.val()) ? Number(business_strategy_spread.val()).toFixed(2) : Number(business_strategy_spread.val())
      let credit = float(Number(credit_spread.val())) ? Number(credit_spread.val()).toFixed(2) : Number(credit_spread.val())

      total_spread.val(Number(Number(business_spread) + Number(credit)).toFixed(2));

      total_roi.val(Number(Number(total_spread.val()) + Number(current_benchmark_rate.val())).toFixed(2))

      if (anchor_discount_bearing.val()) {
        let anchor_bearing = float(Number(anchor_discount_bearing.val())) ? Number(anchor_discount_bearing.val()).toFixed(2) : Number(anchor_discount_bearing.val())
        vendor_discount_bearing.val(Number(total_roi.val()) - Number(anchor_bearing))
      } else {
        vendor_discount_bearing.val(Number(total_roi.val()))
      }

      daily_discount_charge.val(Number(total_roi.val() / 365).toFixed(2))
    }
  }

  function float(a) {
    return a - a === 0 && a.toString(32).indexOf('.') !== -1
  }

  function setProgramName() {
    let name = $('#anchor').find(':selected').data('name')

    $('#program-name').val(name)

    let id = $('#anchor').find(':selected').val()

    let url = "companies/"+id+"/details"

    $.post({
      url: `name/check`,
      data: {
        'name': name,
        'company_id': id,
      },
      dataType: 'json',
      success: function (data) {
        if (data.exists) {
          $('#program_name_error').removeClass('d-none')
          $('#program-name').addClass('border-danger')
        } else {
          $('#program_name_error').addClass('d-none')
          $('#program-name').removeClass('border-danger')
        }
      },
      error: function (err) {
        if (err.status == 400) {
          $('#program_name_error').removeClass('d-none')
        }
      }
    })

    // Get Relationship managers
    $.ajax({
      method: 'GET',
      url: url,
      dataType: 'json',
      success: function (data) {
        $('#program-relationship-managers').html('')
        let relationship_managers = data.company.relationship_managers;
        relationship_managers.forEach((manager, index) => {
          selected_managers.push(manager.email)
          let html = '<div class="col-sm-6">'
              html += '  <label class="form-label" for="bank-user-email">Bank Email</label>'
              html += '  <input type="email" id="bank-user-email" class="form-control" name="bank_user_emails['+index+']" value="'+manager.email+'" readonly />'
              html += '</div>'
              html += '<div class="col-sm-6">'
              html += '  <label class="form-label" for="bank-user-name">Bank User Name</label>'
              html += '  <input type="text" id="bank-user-name" class="form-control" name="bank_user_names['+index+']" value="'+manager.name+'" readonly />'
              html += '</div>'
              html += '<div class="col-sm-6">'
              html += '  <label class="form-label" for="bank-user-phone-number">Bank User Mobile No.</label>'
              html += '  <input type="text" id="bank-user-phone-number" class="form-control" name="bank_user_phone_numbers['+index+']" value="'+manager.phone_number+'" readonly />'
              html += '</div>'
              html += '<div class="col-6"></div>'

            $(html).appendTo('#program-relationship-managers')
            relationship_managers_count += 1
        })
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(textStatus)
      }
    })
  }

  $('#program-name').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length > 3) {
      $.post({
        url: `name/check`,
        data: {
          'name': $(this).val(),
          'company_id': $('#anchor').find(':selected').val(),
        },
        dataType: 'json',
        success: function (data) {
          if (data.exists) {
            $('#program_name_error').removeClass('d-none')
            $('#program-name').addClass('border-danger')
          } else {
            $('#program_name_error').addClass('d-none')
            $('#program-name').removeClass('border-danger')
          }
        },
        error: function (err) {
          if (err.status == 400) {
            $('#program_name_error').removeClass('d-none')
          }
        }
      })
    }
  })

  $('#add-relationship-manager').click(function (e) {
    e.preventDefault();
    selected_managers.forEach(manager => {
      let index = bank_users.findIndex(item => item.email == manager)
      bank_users.splice(index, 1);
    })
    let html = '<div class="col-sm-6">'
        html += '  <label class="form-label" for="bank-user-email">Bank Email</label>'
        html += '  <select type="email" id="bank-user-email-'+relationship_managers_count+'" class="form-control" name="bank_user_emails['+relationship_managers_count+']" onchange="selectBankUser('+relationship_managers_count+')">'
        html += '    <option value="">Select Bank User</option>'
        bank_users.forEach(user => {
          html += '<option value="'+user.email+'" data-name="'+user.name+'" data-phone-number="'+user.phone_number+'">'+user.email+'</option>'
        })
        html += '  </select>'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '  <label class="form-label" for="bank-user-name">Bank User Name</label>'
        html += '  <input id="bank-user-name-'+relationship_managers_count+'" class="form-control" name="bank_user_names['+relationship_managers_count+']" readonly />'
        html += '</div>'
        html += '<div class="col-sm-6">'
        html += '  <label class="form-label" for="bank-user-phone-number">Bank User Mobile No.</label>'
        html += '  <input type="text" id="bank-user-phone-number-'+relationship_managers_count+'" class="form-control" name="bank_user_phone_numbers['+relationship_managers_count+']" readonly />'
        html += '</div>'
        html += '<div class="col-6"></div>'

      $(html).appendTo('#program-relationship-managers')
  })

  function selectBankUser(index) {
    let name = $('#bank-user-email-'+index).find(':selected').data('name')
    let phone_number = $('#bank-user-email-'+index).find(':selected').data('phone-number')
    $('#bank-user-name-'+index).val(name)
    $('#bank-user-phone-number-'+index).val(phone_number)
  }

  let max_limit_per_account = 0

  function updateAccountLimit() {
    $('#program-limit').val()

    max_limit_per_account = Number($('#program-limit').val())

    $('#limit-per-account').attr('max', max_limit_per_account)
  }

  let fees_count = 1
  let fees = $('#program-fees')
  $(document.body).on('click', '#add-item', function (e) {
    e.preventDefault()
    let html = '<div class="col-sm-4" id="fee-name-'+fees_count+'">'
        html += '<label class="form-label" for="fee-name">Fee Name</label>'
        html += '<input type="text" id="fee-name" class="form-control" name="fee_names['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-type-'+fees_count+'">'
        html += '<label class="form-label" for="fee-type-'+fees_count+'">Type</label>'
        html += '<select class="form-select" id="fee-type-'+fees_count+'" name="fee_types['+fees_count+']" onchange="changeFeeType('+fees_count+')">'
        html += '<option value="percentage">Percentage</option>'
        html += '<option value="amount">Amount</option>'
        html += '<option value="per amount">Per Amount</option>'
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-4 d-none" id="fee-per-amount-value-'+fees_count+'">'
        html += '<label class="form-label" for="value">Amount</label>'
        html += '<input type="number" class="form-control" step=".0001" name="fee_per_amount['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-values-'+fees_count+'">'
        html += '<label class="form-label" for="value">Value</label>'
        html += '<input type="number" step=".0001" id="fee-value-'+fees_count+'" class="form-control" name="fee_values['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-anchor-bearing-'+fees_count+'">'
        html += '<label class="form-label" for="anchor-fee-bearing">'+anchor_fee_bearing_label+' (%)</label>'
        html += '<input type="number" step=".0000001" id="anchor-fee-bearing-'+fees_count+'" class="form-control" min="0" max="100" name="fee_anchor_bearing_discount['+fees_count+']" oninput="updateFeeBearing('+fees_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-vendor-bearing-'+fees_count+'">'
        html += '<label class="form-label" for="vendor-fee-bearing">Vendor Bearing (%)</label>'
        html += '<input type="number" step=".0000001" id="vendor-fee-bearing-'+fees_count+'" class="form-control" name="fee_vendor_bearing_discount['+fees_count+']" readonly />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-charge-types-'+fees_count+'">'
        html += '<label class="form-label" for="value">Charge</label>'
        html += '<select class="form-select" id="fee-charge-type-'+fees_count+'" name="charge_types['+fees_count+']">'
        html += '<option value="fixed">Fixed</option>'
        html += '<option value="daily" title="Daily">Per Day</option>'
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-account-numbers-'+fees_count+'">'
        html += '<label class="form-label" for="value">Credit To</label>'
        html += '<select class="form-select" id="fee-account-numbers-'+fees_count+'" name="fee_account_numbers['+fees_count+']">'
        html += '<option value="">Select Account</option>'
        @foreach ($bank_payment_accounts as $key => $bank_payment_account)
          html += '<option value="'+{!! json_encode($bank_payment_account->account_number) !!}+'">'+{!! json_encode($bank_payment_account->account_number) !!}+' ('+ {!! json_encode($bank_payment_account->account_name) !!} +')</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-taxes-'+fees_count+'">'
        html += '<label class="form-label" for="taxes">Taxes</label>'
        html += '<select class="form-select" id="taxes" name="taxes['+fees_count+']">'
        html += '<option value="">Select</option>'
        @foreach ($taxes as $key => $tax)
          html += '<option value="'+{!! json_encode($tax['rate']) !!}+'">'+ {!! json_encode($key) !!} +' ('+ {!! json_encode($tax['rate']) !!}+'%)</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-12 mb-2 mt-2" id="fee-delete-'+fees_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeFee('+fees_count+')"></i>'
        html += '</div>'

      $(html).appendTo(fees);
      fees_count += 1;
  })

  let dealer_fees_count = 1
  let dealer_fees = $('#dealer-program-fees')
  $(document.body).on('click', '#add-dealer-item', function (e) {
    e.preventDefault()
    let html = '<div class="col-sm-4" id="dealer-fee-name-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="fee-name">Fee Name</label>'
        html += '<input type="text" id="fee-name" class="form-control" name="dealer_fee_names['+dealer_fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="dealer-fee-type-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="fee-type-'+dealer_fees_count+'">Type</label>'
        html += '<select class="form-select" id="fee-type-'+dealer_fees_count+'" name="dealer_fee_types['+dealer_fees_count+']" onchange="changeDealerFeeType('+dealer_fees_count+')">'
        html += '<option value="percentage">Percentage</option>'
        html += '<option value="amount">Amount</option>'
        html += '<option value="per amount">Per Amount</option>'
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-4 d-none" id="dealer-fee-per-amount-value-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="value">Amount</label>'
        html += '<input type="number" class="form-control" step=".000001" name="dealer_fee_per_amount['+dealer_fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="dealer-fee-values-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="value">Value</label>'
        html += '<input type="number" step=".000001" id="dealer-fee-value-'+dealer_fees_count+'" class="form-control" name="dealer_fee_values['+dealer_fees_count+']" oninput="updateDealerBearingFee('+dealer_fees_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="fee-dealer-bearing-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="dealer-fee-bearing">Dealer Bearing (%)</label>'
        html += '<input type="number" id="dealer-fee-bearing-'+dealer_fees_count+'" min="0" max="100" step=".000001" value="100" class="form-control" name="fee_dealer_bearing_discount['+dealer_fees_count+']" readonly />'
        html += '</div>'
        html += '<div class="col-sm-4" id="dealer-fee-charge-types-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="value">Charge</label>'
        html += '<select class="form-select" id="dealer-fee-charge-type-'+dealer_fees_count+'" name="dealer_charge_types['+dealer_fees_count+']">'
        html += '<option value="fixed">Fixed</option>'
        html += '<option value="daily" title="Daily">Per Day</option>'
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-4" id="dealer-fee-account-numbers-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="value">Credit To</label>'
        html += '<select class="form-select" id="dealer-fee-account-numbers-'+dealer_fees_count+'" name="dealer_fee_account_numbers['+dealer_fees_account+']">'
        html += '<option value="">Select Account</option>'
        @foreach ($bank_payment_accounts as $key => $bank_payment_account)
          html += '<option value="'+{!! json_encode($bank_payment_account->account_number) !!}+'">'+{!! json_encode($bank_payment_account->account_number) !!}+' ('+ {!! json_encode($bank_payment_account->account_name) !!} +')</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-6" id="dealer-fee-taxes-'+dealer_fees_count+'">'
        html += '<label class="form-label" for="taxes">Taxes</label>'
        html += '<select class="form-select" id="taxes" name="dealer_taxes['+dealer_fees_count+']">'
        html += '<option value="">Select</option>'
        @foreach ($taxes as $key => $tax)
          html += '<option value="'+{!! json_encode($tax['rate']) !!}+'">'+{!! json_encode($key) !!}+' ('+{!! json_encode($tax['rate']) !!}+'%)</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-12 mb-2 mt-2" id="dealer-fee-delete-'+dealer_fees_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDealerFee('+dealer_fees_count+')"></i>'
        html += '</div>'

      $(html).appendTo(dealer_fees);
      dealer_fees_count += 1;
  })

  function removeFee(index) {
    $('div').remove('#fee-name-'+index+', #fee-type-'+index+', #fee-values-'+index+',  #fee-per-amount-value-'+index+', #fee-anchor-bearing-'+index+', #fee-vendor-bearing-'+index+', #fee-charge-types-'+index+', #fee-account-numbers-'+index+', #fee-taxes-'+index+', #fee-delete-'+index);
    fees_count -= 1;
  }

  function removeDealerFee(index) {
    $('div').remove('#dealer-fee-name-'+index+', #dealer-fee-type-'+index+', #dealer-fee-values-'+index+', #fee-dealer-bearing-'+index+', #dealer-fee-charge-types-'+index+', #dealer-fee-account-numbers-'+index+', #dealer-fee-taxes-'+index+', #dealer-fee-delete-'+index);
    fees_count -= 1;
  }

  function updateFeeBearing(index) {
    let anchor_bearing = $('#anchor-fee-bearing-'+index).val()

    let vendor_bearing = $('#vendor-fee-bearing-'+index).val(100 - anchor_bearing)
  }

  function updateDealerBearingFee(index) {
    // let dealer_bearing = $('#dealer-fee-value-'+index).val()
    // $('#dealer-fee-bearing-'+index).val(dealer_bearing)
  }

  let discount_count = 1
  let discounts = $('#program-discounts')

  function changeDealerBenchmarkRate() {
    // Benchmark rate
    let rate = $('#dealer-benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-dealer-benchmark-rate').val(rate);
    if (rate) {
      current_benchmark_rate.val(rate)
    }

    for (let index = 0; index <= discount_count; index++) {
      // Business Strategy Spread
      let business_strategy_spread = $('#business-strategy-spread-'+index);
      // Credit Spread
      let credit_spread = $('#credit-spread-'+index);
      // Total ROI
      let total_roi = $('#total-roi-'+index);
      // Total Spread
      let total_spread = $('#total-spread-'+index);

      if (business_strategy_spread.val() != '' && credit_spread.val() != '') {
        let business_spread = float(business_strategy_spread.val()) ? Number(business_strategy_spread.val()).toFixed(2) : Number(business_strategy_spread.val())
        let credit = float(Number(credit_spread.val())) ? Number(credit_spread.val()).toFixed(2) : Number(credit_spread.val())

        total_spread.val(Number(Number(business_spread) + Number(credit)).toFixed(2));

        total_roi.val(Number(Number(total_spread.val()) + Number(current_benchmark_rate.val())).toFixed(2))
      }
    }
  }

  function changeDealerDiscountRates(index) {
    // Benchmark rate
    let rate = $('#dealer-benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-dealer-benchmark-rate').val(rate);
    if (rate) {
      current_benchmark_rate.val(rate)
    }

    // Business Strategy Spread
    let business_strategy_spread = $('#dealer-business-strategy-spread-'+index);
    // Credit Spread
    let credit_spread = $('#dealer-credit-spread-'+index);
    // Total ROI
    let total_roi = $('#dealer-total-roi-'+index);
    // Total Spread
    let total_spread = $('#dealer-total-spread-'+index);

    let daily_discount_charge = $('#dealer-daily-discount-charge-' + index)

    if (business_strategy_spread.val() != '' && credit_spread.val() != '') {
      let business_spread = float(business_strategy_spread.val()) ? Number(business_strategy_spread.val()).toFixed(2) : Number(business_strategy_spread.val())
      let credit = float(Number(credit_spread.val())) ? Number(credit_spread.val()).toFixed(2) : Number(credit_spread.val())

      total_spread.val(Number(Number(business_spread) + Number(credit)).toFixed(2));

      total_roi.val(Number(Number(total_spread.val()) + Number(current_benchmark_rate.val())).toFixed(2))

      daily_discount_charge.val(Number(total_roi.val() / 365).toFixed(2))
    }
  }

  $(document.body).on('click', '#add-discount', function (e) {
    e.preventDefault()
    // Get previous last day value
    let last_day_val = $('#to-day-'+(Number(discount_count) - 1)).val()
    if (!last_day_val) {
      $('#to-day-'+(Number(discount_count) - 1)).addClass('border border-danger')
      setTimeout(() => {
        $('#to-day-'+(Number(discount_count) - 1)).removeClass('border border-danger')
      }, 3000);
      return
    }

    if (last_day_val < $('#from-day-'+(Number(discount_count) - 1)).val()) {
      $('#to-day-'+(Number(discount_count) - 1)).addClass('border border-danger')
      setTimeout(() => {
        $('#to-day-'+(Number(discount_count) - 1)).removeClass('border border-danger')
      }, 3000);
      return
    }

    $('#to-day-'+(Number(discount_count) - 1)).attr('readonly', 'readonly')
    $('#discount-delete-'+(Number(discount_count) - 1)).addClass('d-none')

    let default_payment_terms = $('#default-payment-terms').val()

    last_day_val = Number(last_day_val) + 1
    let min_val = last_day_val + 1

    let html = '<div class="col-sm-6" id="from-day-section-'+discount_count+'">'
        html += '<label for="" class="form-label">'
        html += 'From Day'
        html += '<span class="text-danger">*</span>'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>'
        html += '</label>'
        html += '<input type="number" name="from_day['+discount_count+']" id="from-day-'+discount_count+'" class="form-control" min="1" readonly value="'+last_day_val+'" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="to-day-section-'+discount_count+'">'
        html += '<label for="" class="form-label">'
        html += 'To Day'
        html += '<span class="text-danger">*</span>'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>'
        html += '</label>'
        html += '<input type="number" name="to_day['+discount_count+']" id="to-day-'+discount_count+'" class="form-control" min="'+min_val+'" max="'+default_payment_terms+'" placeholder="Max. Day is '+default_payment_terms+' from the default payment terms" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="credit-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="credit-spread">'
        html += 'Credit Spread (%)'
        html += '<span class="text-danger">*</span>'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-credit-spread-'+discount_count+'" class="form-control" name="dealer_credit_spread['+discount_count+']" step=".00000000001" oninput="changeDealerDiscountRates('+discount_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="business-strategy-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="business-strategy-spread">'
        html += 'Business Strategy Spread (%)'
        html += '<span class="text-danger">*</span>'
        html += '</label>'
        html += '<input type="number" id="dealer-business-strategy-spread-'+discount_count+'" class="form-control" step=".00000000001" name="dealer_business_strategy_spread['+discount_count+']" oninput="changeDealerDiscountRates('+discount_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="total-spread">'
        html += 'Total Spread (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-total-spread-'+discount_count+'" readonly class="form-control" name="dealer_total_spread['+discount_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-roi-section-'+discount_count+'">'
        html += '<label class="form-label" for="total-roi">'
        html += 'Total ROI (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-total-roi-'+discount_count+'" readonly class="form-control" name="dealer_total_roi['+discount_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="daily-discount-charge-'+discount_count+'">'
        html += '<label class="form-label" for="discount-charge">'
        html += 'Discount Charge (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-daily-discount-charge-'+discount_count+'" readonly class="form-control" name="dealer_daily_discount_charge['+discount_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-9"></div>'
        html += '<div class="col-12 mb-2 mt-2" id="discount-delete-'+discount_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDiscount('+discount_count+')"></i>'
        html += '</div>'

      $(html).appendTo(discounts);
      discount_count += 1;
  })

  function removeDiscount(index) {
    $('div').remove('#from-day-section-'+index+', #to-day-section-'+index+', #credit-spread-section-'+index+', #business-strategy-spread-section-'+index+', #total-spread-section-'+index+', #total-roi-section-'+index+', #daily-discount-charge-'+index+', #discount-delete-'+index);
    $('#to-day-'+(Number(index) - 1)).removeAttr('readonly')
    if (index != 1) {
      $('#discount-delete-'+(Number(index) - 1)).removeClass('d-none')
    }
    discount_count -= 1;
  }

  function getPostedDiscount() {
    let roi = $('#dealer-total-roi-0').val()
    let posted_discount_spread = $('#discount-posted-spread').val()

    $('#discount-posted').val(Number(roi) + Number(posted_discount_spread))
  }

  function changeFeeType(index) {
    let type = $('#fee-type-'+index).find(':selected').val()
    if(type == 'per amount') {
      $('#fee-per-amount-value-'+index).removeClass('d-none')
    } else {
      $('#fee-per-amount-value-'+index).addClass('d-none')
    }
  }

  function changeDealerFeeType(index) {
    let type = $('#fee-type-'+index).find(':selected').val()
    if(type == 'per amount') {
      $('#dealer-fee-per-amount-value-'+index).removeClass('d-none')
    } else {
      $('#dealer-fee-per-amount-value-'+index).addClass('d-none')
    }
  }

  $('#anchor').on('change', function() {
    let html = ''
    $('#bank-accounts').text('');
    let id = $(this).val();
    $.get({
      "url": '/'+bank.url+'/programs/companies/'+id+'/details',
      "dataType": "json",
      "success": function(data) {
        $('#bank-accounts').html('');
        if (data.company.bank_details.length > 0) {
          bank_accounts = data.company.bank_details.length
          data.company.bank_details.forEach((bank_details, index) => {
            html += '<div class="col-sm-6">'
            html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
            html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks['+index+']" value="'+bank_details.name_as_per_bank+'" />'
            html += '</div>'
            html += '<div class="col-sm-6">'
            html += '<label class="form-label" for="account-number">Account Number</label>'
            html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+index+']" value="'+bank_details.account_number+'" />'
            html += '</div>'
            html += '<div class="col-sm-6">'
            html += '<label class="form-label" for="bank-name">Bank Name</label>'
            html += '<select class="form-select" id="bank-name-'+index+'" name="bank_names['+index+']" onchange="getSwiftCode('+index+')">'
            @foreach ($banks as $vendor_bank)
              if (bank_details.bank_name == {!! json_encode($vendor_bank->name) !!}) {
                html += '<option value="'+{!! json_encode($vendor_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($vendor_bank->swift_code) !!}+'" selected>'+{!! json_encode($vendor_bank->name) !!}+'</option>'
              } else {
                html += '<option value="'+{!! json_encode($vendor_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($vendor_bank->swift_code) !!}+'">'+{!! json_encode($vendor_bank->name) !!}+'</option>'
              }
            @endforeach
            html += '</select>'
            html += '</div>'
            html += '<div class="col-sm-6">'
            html += '<label class="form-label" for="bank-branch">Branch</label>'
            html += '<input type="text" id="bank-branch" class="form-control" name="branches['+index+']" value="'+bank_details.branch+'" />'
            html += '</div>'
            html += '<div class="col-sm-6">'
            html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
            html += '<input type="text" id="swift-code-'+index+'" class="form-control" name="swift_codes['+index+']" value="'+bank_details.swift_code+'" />'
            html += '</div>'
            html += '<div class="col-sm-6">'
            html += '<label class="form-label" for="account-type">Account Type</label>'
            html += '<input type="text" id="account-type" class="form-control" name="account_types['+index+']" value="'+bank_details.account_type+'" />'
            html += '</div>'
            html += '<div class="col-12">'
            html += '<hr>'
            html += '</div>'

            $(html).appendTo($('#bank-accounts'));
            bank_accounts += 1;
          })
        } else {
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="name-as-per-bank">Account Name <span class="text-danger">*</span></label>'
          html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[]" required />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="account-number">Account Number <span class="text-danger">*</span></label>'
          html += '<input type="text" id="account-number" class="form-control" name="account_numbers[]" required />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="bank-name">Bank Name <span class="text-danger">*</span></label>'
          html += '<select class="form-select" id="bank-name-0" name="bank_names[]" onchange="getSwiftCode(0)" required>'
          @foreach ($banks as $buyer_bank)
            html += '<option value="'+{!! json_encode($buyer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($buyer_bank->swift_code) !!}+'">'+{!! json_encode($buyer_bank->name) !!}+'</option>'
          @endforeach
          html += '</select>'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="bank-branch">Branch</label>'
          html += '<input type="text" id="bank-branch" class="form-control" name="branches[]" required />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="swift-code">SWIFT Code <span class="text-danger">*</span></label>'
          html += '<input type="text" id="swift-code-0" class="form-control" name="swift_codes[]" required />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="account-type">Account Type</label>'
          html += '<input type="text" id="account-type" class="form-control" name="account_types[]" required />'
          html += '</div>'
          html += '<div class="col-12">'
          html += '<hr>'
          html += '</div>'

          $(html).appendTo($('#bank-accounts'));
        }
      },
      "error": function(data) {
        console.log(data);
      }
    })
  })

  $('#default-payment-terms').on('input', function() {
    // Check if program is dealer financing
    let program_type = $('#product-type').val();

    if (program_type == '2') {
      $('#to-day-0').attr('max', $(this).val())
      $('#to-day-0').attr('placeholder', 'Max Day is '+$(this).val()+' from the default payment terms')
    }
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
  $('#approval_date').attr("min", min_day);

  $('#submit-one').on('click', function() {
    if ($('#program_type_id').val() != '' &&
        $('#anchor_id').val() != '' &&
        $('#program-name').val() != '' &&
        $('#eligibility_id').val() != '' &&
        $('#program-limit').val() != '' &&
        $('#approval_date').val() != '' &&
        $('#limit_expiry_date').val() != '' &&
        $('#limit-per-account').val() != '' &&
        $('#min-financing-days').val() != '' &&
        $('#max-financing-days').val() != '' &&
        $('#default-payment-terms').val() != '' &&
        $('#account-status').val() != '')
    {
      $.post({
        "url": "draft/store",
        "data": $('#program-details-form').serializeArray(),
        "dataType": "json",
        "success": function(data) {
          $('#drafts-count').text(data.revisions)
        },
        "error": function(data) {
          console.log(data);
        }
      })
    }
  })

  $('#submit-two').on('click', function() {
    if ($('#program_type_id').val() != '' &&
        $('#anchor_id').val() != '' &&
        $('#program-name').val() != '' &&
        $('#eligibility_id').val() != '' &&
        $('#program-limit').val() != '' &&
        $('#approval_date').val() != '' &&
        $('#limit_expiry_date').val() != '' &&
        $('#limit-per-account').val() != '' &&
        $('#min-financing-days').val() != '' &&
        $('#max-financing-days').val() != '' &&
        $('#default-payment-terms').val() != '' &&
        $('#account-status').val() != '')
    {
      $.post({
        "url": "draft/store",
        "data": $('#program-details-form').serializeArray(),
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
    if ($('#program_type_id').val() != '' &&
        $('#anchor_id').val() != '' &&
        $('#program-name').val() != '' &&
        $('#eligibility_id').val() != '' &&
        $('#program-limit').val() != '' &&
        $('#approval_date').val() != '' &&
        $('#limit_expiry_date').val() != '' &&
        $('#limit-per-account').val() != '' &&
        $('#min-financing-days').val() != '' &&
        $('#max-financing-days').val() != '' &&
        $('#default-payment-terms').val() != '' &&
        $('#account-status').val() != '')
    {
      $.post({
        "url": "draft/store",
        "data": $('#program-details-form').serializeArray(),
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

  $('#submit-four').on('click', function() {
    if ($('#program_type_id').val() != '' &&
        $('#anchor_id').val() != '' &&
        $('#program-name').val() != '' &&
        $('#eligibility_id').val() != '' &&
        $('#program-limit').val() != '' &&
        $('#approval_date').val() != '' &&
        $('#limit_expiry_date').val() != '' &&
        $('#limit-per-account').val() != '' &&
        $('#min-financing-days').val() != '' &&
        $('#max-financing-days').val() != '' &&
        $('#default-payment-terms').val() != '' &&
        $('#account-status').val() != '')
    {
      $.post({
        "url": "draft/store",
        "data": $('#program-details-form').serializeArray(),
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

  $('#drafts-link').on('click', function(e) {
    e.preventDefault();
    window.location.href = $('#drafts-link').attr('href');
  })

  function getSwiftCode(index) {
    $('#swift-code-'+index).val($('#bank-name-'+index).find(':selected').data('swiftcode'))
  }

  let selected_product_type = ''

  // Discount Check if Bank Accounts exist
  $('#discount-type').on('change', function() {
    $.ajax({
      url: '/'+bank.url+'/programs/accounts-checker/'+$(this).val()+'/'+$('#product-type').val()+'/'+$('#product-code').val(),
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        selected_product_type = data.product_type;
        if (selected_product_type == 'Vendor Financing') {
          // If Program is Front Ended, Check if Advance Discount and Discount Income Accounts exist
          // If Program is Rear Ended, Check if Unrealised Discount and Discount Receivable Bank Accounts exist
          // In All Cases, Check if Discount Income, Fee Income, Penal Discount Bank Accounts exist
          if (!data.fee_income_account) {
            $('#program-fees').addClass('d-none');
          }
          if (!data.discount_income_account) {
            $('#program-discounts').addClass('d-none');
          }
          if (!data.penal_discount_income_account) {
            $('#penal-discount-on-principle').attr('disabled', 'disabled');
            $('.vendor-financing-penal-message').removeClass('d-none');
            $('#vendor-financing-fees-message').removeClass('d-none');
          }
        }
        if (selected_product_type == 'Dealer Financing') {
          if (data.penal_discount_income_account) {
            $('#dealer-penal-discount-on-principle').attr('disabled', 'disabled');
            $('.dealer-financing-penal-message').removeClass('d-none');
            $('#dealer-financing-fees-message').removeClass('d-none');
          }
        }
      },
      error: function(data) {
        console.log(data);
      }
    })
  })
  $('#dealer-discount-type').on('change', function() {
    $.ajax({
      url: '/'+bank.url+'/programs/accounts-checker/'+$(this).val()+'/'+$('#product-type').val()+'/'+$('#product-code').val(),
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        selected_product_type = data.product_type;
        if (selected_product_type == 'Vendor Financing') {
          // If Program is Front Ended, Check if Advance Discount and Discount Income Accounts exist
          // If Program is Rear Ended, Check if Unrealised Discount and Discount Receivable Bank Accounts exist
          // In All Cases, Check if Discount Income, Fee Income, Penal Discount Bank Accounts exist
          if (!data.fee_income_account) {
            $('#program-fees').addClass('d-none');
          }
          if (!data.discount_income_account) {
            $('#program-discounts').addClass('d-none');
          }
          if (!data.penal_discount_income_account) {
            $('#penal-discount-on-principle').attr('disabled', 'disabled');
            $('.vendor-financing-penal-message').removeClass('d-none');
            $('#vendor-financing-fees-message').removeClass('d-none');
          }
        }
        if (selected_product_type == 'Dealer Financing') {
          if (!data.penal_discount_income_account) {
            $('#dealer-penal-discount-on-principle').attr('disabled', 'disabled');
            $('.dealer-financing-penal-message').removeClass('d-none');
            $('#dealer-financing-fees-message').removeClass('d-none');
          }
        }
      },
      error: function(data) {
        console.log(data);
      }
    })
  })

  $('#eligibility').on('input', function() {
    let eligibility = $(this).val()
    $('#invoice_margin').val(100 - eligibility)
  })

  $('#approval_date').on('change', function () {
    let val = $(this).val()
    var min_day = new Date(val)
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

    $('#limit_expiry_date').attr('min', min_day)
  })

  let bank_accounts = 0

  function deleteItem(index, id = 0) {
    $('div').remove('#bank-id-section-'+index+', #name-as-per-bank-section-'+index+', #account-number-section-'+index+', #bank-name-section-'+index+', #branch-section-'+index+', #swift-code-section-'+index+', #account-type-section-'+index+', #delete-item-div-'+index);
    bank_accounts -= 1;
  }

  $('#add-bank-details').on('click', function () {
    let html = '<div class="col-sm-6" id="name-as-per-bank-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
        html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="account-number-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="account-number">Account Number</label>'
        html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="bank-name-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="bank-name">Bank Name</label>'
        html += '<select class="form-select" id="bank-name" name="bank_names['+bank_accounts+']">'
        @foreach ($banks as $vendor_bank)
          html += '<option value="'+{!! json_encode($vendor_bank->name) !!}+'">'+{!! json_encode($vendor_bank->name) !!}+'</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-6" id="branch-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="bank-branch">Branch</label>'
        html += '<input type="text" id="bank-branch" class="form-control" name="branches['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="swift-code-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
        html += '<input type="text" id="swift-code" class="form-control" name="swift_codes['+bank_accounts+']" />'
        html += '</div>'
        html += '<div class="col-sm-6" id="account-type-section-'+bank_accounts+'">'
        html += '<label class="form-label" for="account-type">Account Type</label>'
        html += '<input type="text" id="account-type" class="form-control" name="account_types['+bank_accounts+']" />'
        html += '</div>'
        if (bank_accounts != 0) {
          html += '<div class="col-sm-12" id="delete-item-div-'+bank_accounts+'">'
          html += '<i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem('+bank_accounts+')"></i>'
          html += '</div>'
        }
      console.log(html)
      $(html).appendTo($('#bank-accounts'));
      bank_accounts += 1;
  })
</script>
@endsection

@section('content')
<div class="">
  <h4 class="fw-light mr-4 text-nowrap my-auto">
    {{ __('Add Program')}}
  </h4>
  @if($errors->any())
    <div class="p-2 bg-label-danger ml-2 card h-fit w-100" style="height: fit-content">
      {{-- {!! implode('', $errors->first('<p class="mx-2">:message</p>')) !!} --}}
      <p class="text-danger">{{ $errors->first() }} @if($errors->count() > 1) {!! __('+ ') !!} {{ $errors->count() - 1 }} {!! __(' more errors') !!} @endif</p>
    </div>
  @endif
</div>
@php
  // Get Bank Program Types
  $bank_product_types = collect($bank->product_types)->map(fn ($product_type) => Str::headline(str_replace('_', ' ', $product_type)));
@endphp
<!-- Default -->
<div class="row">
  <!-- Vertical Wizard -->
  <div class="col-12 mb-4">
    <div class="bs-stepper wizard-vertical vertical mt-2" id="program-details-wizard">
      <div class="bs-stepper-header">
        <div class="step" data-target="#program-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-users"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Program Details')}}</span>
              <span class="bs-stepper-subtitle">{{ __('Name/Anchor/Type')}}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#discount-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-location"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Discount & Fee Details')}}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#comm-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-mood-smile"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title text-wrap">{{ __('Email & Mobile Details')}}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#bank-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-mood-smile"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title text-wrap">{{ __('Bank Details')}}</span>
            </span>
          </button>
        </div>
        <div class="line"></div>
        <a class="step" href="{{ route('programs.drafts', ['bank' => $bank]) }}" data-target="#drafts" id="drafts-link">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-circle-check"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Drafts')}} <small id="drafts-count" class="badge bg-danger rounded-pill">{{ $drafts->count() }}</small></span>
              <span class="bs-stepper-subtitle">{{ __('Saved Drafts')}}</span>
            </span>
          </button>
        </a>
      </div>
      <div class="bs-stepper-content">
        <form id="program-details-form" method="POST" action="{{ route('programs.store', ['bank' => $bank]) }}">
          @csrf
          <!-- Program Details -->
          <div id="program-details" class="content">
            <div class="row g-3">
              {{-- Program Type --}}
              <div class="col-sm-6">
                <label class="form-label" for="product-type">{{ __('Product Type')}} <span class="text-danger">*</span></label>
                <select class="form-select" id="product-type" name="program_type_id" oninput="changeProgramType()" onchange="changeProgramType()">
                  <option value="">{{ __('Select Program Type') }}</option>
                  @foreach ($program_types as $program_type)
                    @if (collect($bank_product_types)->contains($program_type->name))
                      <option value="{{ $program_type->id }}" data-codes="{{ $program_type->programCodes }}" @if(old('program_type_id') == $program_type->id || ($program && $program->program_type_id == $program_type->id)) selected @endif>{{ $program_type->name }}</option>
                    @endif
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('program_type_id')" />
              </div>
              {{-- Product Type Code --}}
              <div class="col-sm-6 vendor-financing">
                <label class="form-label" for="product-code">{{ __('Product Code')}}</label>
                <select class="form-select" id="product-code" name="program_code_id" onchange="changeVendorFinancingType()">
                  <option value="">{{ __('Select')}}</option>
                </select>
                <x-input-error :messages="$errors->get('program_code_id')" />
              </div>
              {{-- Anchor --}}
              <div class="col-sm-6">
                <div class="d-flex">
                  <label class="form-label" for="anchor" id="anchor-label">{{ __('Anchor') }}</label>
                  <span class="text-danger mx-1">*</span>
                </div>
                <select class="form-select" id="anchor" name="anchor_id" onchange="setProgramName()" required>
                  <option value="">{{ __('Select') }}</option>
                  @foreach ($companies as $company)
                    <option value="{{ $company->id }}" data-name="{{ $company->name }}" @if(old('anchor_id') == $company->id || ($program && $program->anchor && $program->anchor->id == $company->id)) selected @endif>{{ $company->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('anchor_id')" />
              </div>
              {{-- Program Name --}}
              <div class="col-sm-6">
                <label class="form-label" for="program-name">{{ __('Name')}} <span class="text-danger">*</span></label>
                <input type="text" id="program-name" class="form-control" name="name" value="{{ old('name', $program ? $program->name : '') }}" required />
                <span id="program_name_error" class="text-danger d-none">{{ __('Program Name already in use') }}</span>
                <x-input-error :messages="$errors->get('name')" />
              </div>
              {{-- Program Code --}}
              <div class="col-sm-6">
                <label class="form-label" for="program-code">{{ __('Program Code')}} <span class="text-danger">*</span></label>
                <input type="text" id="program-code" class="form-control" name="program_code" value="{{ old('program_code', $program ? $program->code : '') }}" required />
                <x-input-error :messages="$errors->get('program_code')" />
              </div>
              {{-- Eligibility --}}
              <div class="col-sm-6">
                <label class="form-label" for="eligibility">{{ __('Eligibility')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="eligibility" class="form-control" max="100" name="eligibility" step=".01" value="{{ old('eligibility', $program ? $program->eligibility : '') }}" />
                <x-input-error :messages="$errors->get('eligiblity')" />
              </div>
              {{-- Invoice Margin --}}
              <div class="col-sm-6">
                <label class="form-label" for="invoice_margin">{{ __('Invoice Margin')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="invoice_margin" class="form-control" max="100" name="invoice_margin" step=".01" value="{{ old('invoice_margin', $program ? 100 - $program->eligibility : '') }}" readonly />
                <x-input-error :messages="$errors->get('invoice_margin')" />
              </div>
              {{-- Total Program Limit --}}
              <div class="col-sm-6">
                <label class="form-label" for="program-limit">{{ __('Total Program Limit')}} <span class="text-danger">*</span></label>
                <input type="text" id="program-limit" class="form-control" min="1" name="program_limit" value="{{ old('program_limit', $program ? number_format($program->program_limit) : '') }}" required oninput="updateAccountLimit()" autocomplete="off" />
                <x-input-error :messages="$errors->get('program_limit')" />
              </div>
              {{-- Program Approval Date --}}
              <div class="col-sm-6">
                <label class="form-label" for="approval+date">{{ __('Program Approval Date')}}</label>
                <input class="form-control" type="date" id="approval_date" name="approved_date" value="{{ old('approved_date', $program ? Carbon\Carbon::parse($program->approval_date)->format('Y-m-d') : '') }}" required />
                <x-input-error :messages="$errors->get('approved_date')" />
              </div>
              {{-- Limit Expiry Date --}}
              <div class="col-sm-6">
                <label class="form-label" for="limit_expiry_date">
                  {{ __('Limit Expiry Date')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Program is valid until this date. Post this date, new financing requests cannot be raised"></i>
                </label>
                <input class="form-control" type="date" id="limit_expiry_date" name="limit_expiry_date" value="{{ old('limit_expiry_date', $program ? Carbon\Carbon::parse($program->limit_expiry_date)->format('Y-m-d') : '') }}" />
                <x-input-error :messages="$errors->get('limit_expiry_date')" />
              </div>
              {{-- Maximum Limit Per Account --}}
              <div class="col-sm-6">
                <label class="form-label" for="limit-per-account">{{ __('Maximum Limit Per Account')}} <span class="text-danger">*</span></label>
                <input type="text" id="limit-per-account" class="form-control" name="max_limit_per_account" min="1" value="{{ old('max_limit_per_account', $program ? number_format($program->max_limit_per_account) : '') }}" required autocomplete="off" />
                <x-input-error :messages="$errors->get('max_limit_per_account')" />
              </div>
              {{-- Collection Account --}}
              <div class="col-sm-6 collection-account d-none">
                <label for="collection-account">{{ __('Collection Account')}}</label>
                <input type="text" id="collection-account" class="form-control" name="collection_account" value="{{ old('collection_account', $program ? $program->collection_account : '') }}" />
                <x-input-error :messages="$errors->get('collection_account')" />
              </div>
              {{-- Factoring Payment Account --}}
              <div class="col-sm-6 factoring-payment-account d-none">
                <label for="factoring-payment-account">{{ __('Factoring Payment Account')}}</label>
                <input type="text" id="factoring-payment-account" class="form-control" name="factoring_payment_account" value="{{ old('factoring_payment_account', $program ? $program->factoring_payment_account : '') }}" />
                <x-input-error :messages="$errors->get('factoring_payment_account')" />
              </div>
              {{-- Request Auto Finance --}}
              <div class="col-sm-6">
                <label class="form-label" for="request-autofinance">
                  {{ __('Request Auto Finance')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Select 'Yes' for Top-down approach or 'No' for Bottom-up approach"></i>
                </label>
                <select class="form-select" id="request-autofinance" name="request_auto_finance" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if(old('request_auto_finance') == 1 || ($program && $program->request_auto_finance)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('request_auto_finance') == 0 || ($program && !$program->request_auto_finance)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('request_auto_finance')" />
              </div>
              {{-- Minimum Financing Days --}}
              <div class="col-sm-6 vendor-financing">
                <label class="form-label" for="min-financing-days">
                  {{ __('Minimum Financing Days')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Vendors will not be able to request for financing for PI(s) maturing within the specified days from the date of request"></i>
                </label>
                <input type="number" id="min-financing-days" class="form-control" min="1" value="{{ old('min_financing_days', $program ? $program->min_financing_days : 1) }}" name="min_financing_days" />
                <x-input-error :messages="$errors->get('min_financing_days')" />
              </div>
              {{-- Maximum Financing Days --}}
              <div class="col-sm-6 vendor-financing">
                <label class="form-label" for="max-financing-days">
                  {{ __('Maximum Financing Days')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Vendors will not be able to request for financing for PI(s) maturing beyond the specified days from the date of request"></i>
                </label>
                <input type="number" id="max-financing-days" class="form-control" min="1" value="{{ old('max_financing_days', $program ? $program->max_financing_days : 1) }}" name="max_financing_days" required />
                <x-input-error :messages="$errors->get('max_financing_days')" />
              </div>
              {{-- Stale Invoice Period --}}
              <div class="col-sm-6">
                <label class="form-label" for="stale-invoice-period">
                  {{ __('Stale Invoice Period')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Invoices older than this date will not be eligible for financing."></i>
                </label>
                <input type="number" id="stale-invoice-period" class="form-control" value="{{ old('stale_invoice_period', $program ? $program->stale_invoice_period : 0) }}" name="stale_invoice_period" />
                <x-input-error :messages="$errors->get('stale_invoice_period')" />
              </div>
              {{-- FLDG --}}
              <div class="col-sm-6 dealer-financing d-none">
                <label class="form-label" for="fldg">
                  {{ __('FLDG (Days)') }}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="An Email is sent to anchor users tagged at program level every day for all dealers who have been overdue after a certain no. of days mentioned in this field till the overdue is cleared."></i>
                </label>
                <input type="number" id="fldg-days" class="form-control" value="{{ old('fldg_days', $program ? $program->fldg_days : 0) }}" name="fldg_days" />
                <x-input-error :messages="$errors->get('fldg_days')" />
              </div>
              {{-- Stop Supply --}}
              <div class="col-sm-6 dealer-financing d-none">
                <label class="form-label" for="stop-supply">
                  {{ __('Stop Supply (Days)')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="An email is sent to anchor users tagged at program level to stop supply to those dealers whose loans are overdue beyond the number of days set in this field."></i>
                </label>
                <input type="number" id="stop-supply" class="form-control" value="{{ old('stop_supply', $program ? $program->stop_supply : 0) }}" name="stop_supply" />
                <x-input-error :messages="$errors->get('stop_supply')" />
              </div>
              {{-- Auto Debit Anchor For Financed Invoices --}}
              <div class="col-sm-6">
                <label class="form-label" for="auto-debit-anchor">
                  {{ __('Auto Debit Anchor for Financed Invoices')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="If set 'Yes', system will auto-debit anchor for financed invoices"></i>
                </label>
                <select class="form-select" id="auto-debit-anchor" name="auto_debit_anchor_financed_invoices" required>
                  <option value="">{{ __('Select') }}</option>
                  <option value="1" @if(old('auto_debit_anchor_financed_invoices') == 1 || ($program && $program->auto_debit_anchor_financed_invoices)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('auto_debit_anchor_financed_invoices') == 0 || ($program && $program->auto_debit_anchor_financed_invoices)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('auto_debit_anchor_financed_invoices')" />
              </div>
              {{-- Auto Debit Anchor For Non-financed Invoices --}}
              <div class="col-sm-6">
                <label class="form-label" for="auto-debit-anchor-for-non-financed">
                  {{ __('Auto Debit Anchor for Non-Financed Invoices')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="auto-debit-anchor-for-non-financed" name="auto_debit_anchor_non_financed_invoices" required>
                  <option value="">{{ __('Select') }}</option>
                  <option value="1" @if(old('auto_debit_anchor_non_financed_invoices') == 1 || ($program && $program->auto_debit_anchor_non_financed_invoices)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('auto_debit_anchor_non_financed_invoices') == 0 || ($program && $program->auto_debit_anchor_non_financed_invoices)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('auto_debit_anchor_non_financed_invoices')" />
              </div>
              {{-- Allow Anchor to Change Due Date --}}
              <div class="col-sm-6">
                <label class="form-label" for="allow-anchor-to-change-due-date">
                  {{ __('Allow Anchor to change due date')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="allow-anchor-to-change-due-date" name="anchor_can_change_due_date" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if(old('anchor_can_change_due_date') == 1 || ($program && $program->anchor_can_change_due_date)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('anchor_can_change_due_date') == 0 || ($program && $program->anchor_can_change_due_date)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('anchor_can_change_due_date')" />
              </div>
              {{-- Max No of Days for Invoice due date extensions --}}
              <div class="col-sm-6">
                <label class="form-label" for="max-days-for-invoice-date-extension">
                  {{ __('Maximum No. of days for Invoice Due Date Extensions')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="For financed invoices, set number of days up till which the anchor can extend due date"></i>
                </label>
                <input type="number" id="max-days-for-invoice-date-extension" class="form-control" value="{{ old('max_days_due_date_extension', $program ? $program->max_days_due_date_extension : '') }}" name="max_days_due_date_extension" />
                <x-input-error :messages="$errors->get('max_days_due_date_extension')" />
              </div>
              {{-- No. of days Limit for changing Invoice Due Date --}}
              <div class="col-sm-6">
                <label class="form-label" for="number-of-days-for-due-date-change">
                  {{ __('No. of Days Limit for changing Invoice Due Date')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="For financed invoices, set number of days from maturity within which anchor can't change the invoice due date"></i>
                </label>
                <input type="number" id="number-of-days-for-due-date-change" class="form-control" value="{{ old('days_limit_for_due_date_change', $program ? $program->days_limit_for_due_date_change : '') }}" name="days_limit_for_due_date_change" />
                <x-input-error :messages="$errors->get('days_limit_for_due_date_change')" />
              </div>
              {{-- Default Payment Terms --}}
              <div class="col-sm-6">
                <label class="form-label" for="default-payment-terms">
                  {{ __('Default Payment Terms(Days)')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="number" id="default-payment-terms" class="form-control" value="{{ old('default_payment_terms', $program ? $program->default_payment_terms : '') }}" name="default_payment_terms" required />
                <x-input-error :messages="$errors->get('default_payment_terms')" />
              </div>
              {{-- Allow anchor to change payment terms --}}
              <div class="col-sm-6">
                <label class="form-label" for="allow-anchor-to-change-payment-terms">
                  {{ __('Allow Anchor to change Payment Terms')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="allow-anchor-to-change-payment-terms" name="anchor_can_change_payment_term" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if(old('anchor_can_change_payment_term') == 1 || ($program && $program->anchor_can_change_payment_term)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('anchor_can_change_payment_term') == 0 || ($program && $program->anchor_can_change_payment_term)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('anchor_can_change_payment_term')" />
              </div>
              {{-- Recourse --}}
              <div class="col-sm-6 recourse">
                <label class="form-label" for="recourse">
                  {{ __('Recourse')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Captured only for reporting purposes, whether recourse is on the anchor."></i>
                </label>
                <select class="form-select" id="recourse" name="recourse">
                  <option value="">{{ __('Select')}}</option>
                  <option value="With Recourse" @if(old('recourse') == "With Recourse" || ($program && $program->recourse == 'With Recourse')) selected @endif>{{ __('With Recourse')}}</option>
                  <option value="Without Recourse" @if(old('recourse') == "Without Recourse" || ($program && $program->recourse == 'Without Recourse')) selected @endif>{{ __('Without Recourse')}}</option>
                </select>
                <x-input-error :messages="$errors->get('recourse')" />
              </div>
              {{-- Repayment Appropriation --}}
              <div class="col-sm-6 repayment-appropriation vendor-financing">
                <label class="form-label" for="repayment-appropriation">
                  {{ __('Repayment Appropriation')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="repayment_appropriation_field" name="repayment_appropriation">
                  <option value="">{{ __('Select')}}</option>
                  <option value="FIFO" @if(old('repayment_appropriation') == "FIFO" || ($program && $program->repayment_appropriation == 'FIFO')) selected @endif>{{ __('FIFO')}}</option>
                  <option value="Loanwise" @if(old('repayment_appropriation') == "Loanwise" || ($program && $program->repayment_appropriation == 'Loanwise')) selected @endif>{{ __('Loanwise')}}</option>
                </select>
                <x-input-error :messages="$errors->get('repayment_appropriation')" />
              </div>
              {{-- Mandatory Invoice Attachment --}}
              <div class="col-sm-6">
                <label class="form-label" for="mandatory_invoice_attachment">
                  {{ __('Mandatory for Invoice Attachment')}}
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select class="form-select" id="mandatory_invoice_attachment" name="mandatory_invoice_attachment" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if(old('mandatory_invoice_attachment') == 1 || ($program && $program->mandatory_invoice_attachment)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('mandatory_invoice_attachment') == 0 || ($program && !$program->mandatory_invoice_attachment)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('mandatory_invoice_attachment')" />
              </div>
              {{-- Due date calculated from --}}
              <div class="col-sm-6 due-date-calculated-from dealer-financing d-none">
                <label class="form-label" for="due-date-calculated-from">
                  {{ __('Due Date Calculated From')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="due-date-calculated-from" name="due_date_calculated_from">
                  <option value="">{{ __('Select')}}</option>
                  <option value="Invoice Creation Date" @if(old('due_date_calculated_from') == 'Invoice Creation Date' || ($program && $program->due_date_calculated_from == 'Invoice Creation Date')) selected @endif>{{ __('Invoice Creation Date')}}</option>
                  <option value="Disbursement Date" @if(old('due_date_calculated_from') == 'Disbursement Date' || ($program && $program->due_date_calculated_from) == 'Disbursement Date') selected @endif>{{ __('Disbursement Date')}}</option>
                </select>
                <x-input-error :messages="$errors->get('due_date_calculated_from')" />
              </div>
              {{-- Buyer Invoice Approval Required --}}
              <div class="col-sm-6 d-none buyer-invoice-approval-required">
                <label class="form-label" for="buyer-invoice-approval-required">
                  {{ __('Buyer Invoice Approval Required')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="buyer-invoice-approval-required" name="buyer_invoice_approval_required">
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if(old('buyer_invoice_approval_required') == 1 || ($program && $program->buyer_invoice_approval_required)) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(old('buyer_invoice_approval_required') == 0 || ($program && !$program->buyer_invoice_approval_required)) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('buyer_invoice_approval_required')" />
              </div>
              {{-- Company Resolution Attachment --}}
              <div class="col-sm-6">
                <label for="formFile" class="form-label">{{ __('Company Board Resolution Attachment')}}</label>
                <input class="form-control" type="file" accept=".pdf" id="formFile" name="board_resolution_attachment">
                <x-input-error :messages="$errors->get('board_resolution_attachment')" />
              </div>
              {{-- Status --}}
              <div class="col-sm-6">
                <label class="form-label" for="account-status">{{ __('Status')}}</label>
                <select class="form-select" id="account-status" name="account_status">
                  <option value="">{{ __('Select')}}</option>
                  <option value="active" @if(old('account_status') == "active" || ($program && $program->account_status == 'active')) selected @endif>{{ __('Active')}}</option>
                  <option value="suspended" @if(old('account_status') == "suspended" || ($program && $program->account_status == 'suspended')) selected @endif>{{ __('Suspended')}}</option>
                </select>
                <x-input-error :messages="$errors->get('account_status')" />
              </div>
              <div class="col-12 d-flex justify-content-between mt-2">
                <button class="btn btn-label-secondary btn-prev" readonly> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-next" type="button" id="submit-one"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Discount Details -->
          <div id="discount-details" class="content">
            @php($selected_base_rate = '')
            <div class="row g-3 vendor-financing">
              {{-- Benchmark Title --}}
              <div class="col-sm-6">
                <label class="form-label" for="benchmark-title">
                  {{ __('Benchmark Title(Maturity)')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="benchmark-title" name="benchmark_title" onchange="changeDiscountRates()">
                  <option value="">{{ __('Select Base Rate')}}</option>
                  @foreach ($benchmark_rates as $key => $benchmark_rate)
                    @if (old('benchmark_title') == $key || ($program && $program->discountDetails?->first()?->benchmark_title == $key) || $benchmark_rate['is_default'])
                      @php($selected_base_rate = $benchmark_rate['rate'])
                    @endif
                    <option value="{{ $key }}" data-rate="{{ $benchmark_rate['rate'] }}" @if(old('benchmark_title') == $key || ($program && $program->discountDetails?->first()?->benchmark_title == $key) || $benchmark_rate['is_default']) selected @endif>{{ $key }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('benchmark_title')" />
              </div>
              {{-- Benchmark Rate --}}
              <div class="col-sm-6">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="current-benchmark-rate">{{ __('Current Base Rate')}}</label>
                  {{-- <span class="text-primary">{{ __('Set As Per Current Master')}}</span> --}}
                </div>
                <input type="number" readonly id="current-benchmark-rate" class="form-control" readonly name="benchmark_rate" step=".01" value="{{ $selected_base_rate != '' ? $selected_base_rate : old('benchmark_rate', $program ? $program->discountDetails?->first()?->benchmark_rate : '') }}" />
                <x-input-error :messages="$errors->get('benchmark_rate')" />
              </div>
              {{-- Business Strategy Spread --}}
              <div class="col-sm-6">
                <label class="form-label" for="business-strategy-spread">
                  {{ __('Business Strategy Spread (%)')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="number" id="business-strategy-spread" class="form-control" min="0" max="100" step=".01" name="business_strategy_spread" value="{{ $program ? $program->discountDetails?->first()?->business_strategy_spread : '' }}" oninput="changeDiscountRates()" />
                <x-input-error :messages="$errors->get('business_strategy_spread')" />
              </div>
              {{-- Credit Spread --}}
              <div class="col-sm-6">
                <label class="form-label" for="credit-spread">
                  {{ __('Credit Spread (%)')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread(%)"></i>
                </label>
                <input type="number" id="credit-spread" class="form-control" name="credit_spread" min="0" max="100" step=".01" value="{{ $program ? $program->discountDetails?->first()?->credit_spread : '' }}" oninput="changeDiscountRates()" />
                <x-input-error :messages="$errors->get('credit_spread')" />
              </div>
              {{-- Total Spread --}}
              <div class="col-sm-6">
                <label class="form-label" for="total-spread">
                  {{ __('Total Spread (%)')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread(%) + Credit Spread(%)"></i>
                </label>
                <input type="number" id="total-spread" readonly class="form-control" min="0" max="100" step=".01" name="total_spread" value="{{ $program ? $program->discountDetails?->first()?->total_spread : '' }}" />
                <x-input-error :messages="$errors->get('total_spread')" />
              </div>
              {{-- Total ROI --}}
              <div class="col-sm-6">
                <label class="form-label" for="total-roi">
                  {{ __('Total ROI (%)')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                </label>
                <input type="number" id="total-roi" readonly class="form-control" min="0" max="100" step=".01" name="total_roi" value="{{ $program ? $program->discountDetails?->first()?->total_roi : '' }}" />
                <x-input-error :messages="$errors->get('total_roi')" />
              </div>
              {{-- Discount Charge --}}
              <div class="col-sm-6">
                <label class="form-label" for="discount-charge">
                  {{ __('Discount Charge (%)')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                </label>
                <input type="number" id="daily-discount-charge" readonly class="form-control" min="0" max="100" step=".01" name="discount_charge" value="{{ $program ? round($program->discountDetails?->first()?->total_roi / 365, 2) : '' }}" />
                <x-input-error :messages="$errors->get('total_roi')" />
              </div>
              {{-- Tax on Discount --}}
              <div class="col-sm-6">
                <label class="form-label" for="tax-on-discount">
                  {{ __('Tax on Discount')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Tax Applied on Total ROI"></i>
                </label>
                <select name="tax_on_discount" id="tax-on-discount" class="form-control">
                  <option value="">{{ __('Select')}}</option>
                  @foreach ($taxes as $key => $tax)
                    <option value="{{ $tax['rate'] }}" @if($program && $program->discountDetails?->first()?->tax_on_discount == $tax['rate']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                  @endforeach
                </select>
                {{-- <input type="number" id="tax-on-discount" class="form-control" min="0" max="100" name="tax_on_discount" step=".01" value="{{ old('tax_on_discount') }}" /> --}}
                <x-input-error :messages="$errors->get('tax_on_discount')" />
              </div>
              {{-- Anchor Bearing Discount --}}
              <div class="col-sm-6">
                <label class="form-label" for="anchor-discount-bearing">
                  <span id="anchor-discount-bearing-label">
                    {{ __('Anchor Discount Bearing')}} (%)
                  </span>
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" id="anchor-discount-bearing-title" data-title="Discount Bourne by the Anchor"></i>
                </label>
                <input type="number" id="anchor-discount-bearing" class="form-control" min="0" max="100" step=".01" name="anchor_discount_bearing" oninput="changeDiscountRates()" value="{{ $program ? $program->discountDetails?->first()?->anchor_discount_bearing : '' }}" />
                <x-input-error :messages="$errors->get('anchor_discount_bearing')" />
              </div>
              {{-- Vendor Bearing Discount --}}
              <div class="col-sm-6">
                <label class="form-label" for="vendor-discount-bearing">
                  {{ __('Vendor Discount Bearing')}} (%)
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Vendor Discount Bearing = Total ROI - Discount Bourne by the Anchor"></i>
                </label>
                <input type="number" id="vendor-discount-bearing" class="form-control" min="0" max="100" step=".01" readonly name="vendor_discount_bearing" value="{{ $program ? $program->discountDetails?->first()?->vendor_discount_bearing : '' }}" />
                <x-input-error :messages="$errors->get('vendor_discount_bearing')" />
              </div>
              {{-- Discount Type --}}
              <div class="col-sm-6">
                <label class="form-label" for="discount-type">
                  {{ __('Discount Type')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="discount-type" name="discount_type">
                  <option value="">{{ __('Select Discount Type')}}</option>
                  <option value="Front Ended" @if(old('discount_type') == "Front Ended" || ($program && $program->discountDetails?->first()?->discount_type == 'Front Ended')) selected @endif>{{ __('Front Ended')}}</option>
                  <option value="Rear Ended" @if(old('discount_type') == "Rear Ended" || ($program && $program->discountDetails?->first()?->discount_type == 'Rear Ended')) selected @endif>{{ __('Rear Ended')}}</option>
                </select>
                <x-input-error :messages="$errors->get('discount_type')" />
              </div>
              {{-- Fee Type --}}
              <div class="col-sm-6 d-none">
                <label class="form-label" for="fee-type">
                  {{ __('Fee Type')}}
                </label>
                <select class="form-select" id="fee-type" name="fee_type">
                  <option value="">{{ __('Select Fee Type')}}</option>
                  <option value="Front Ended" @if(old('fee_type') === "Front Ended" || ($program && $program->discountDetails?->first()?->fee_type === 'Front Ended')) selected @endif>{{ __('Front Ended')}}</option>
                  <option value="Rear Ended" @if(old('fee_type') === "Rear Ended" || ($program && $program->discountDetails?->first()?->fee_type === 'Rear Ended')) selected @endif>{{ __('Rear Ended')}}</option>
                </select>
                <x-input-error :messages="$errors->get('fee_type')" />
              </div>
              {{-- Penal Discount on Principle --}}
              <div class="col-sm-6">
                <label class="form-label" for="penal-discount-on-principle">
                  {{ __('Penal Discount on Principle')}} (%)
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Charges applied if failure of payment on invoice due date"></i>
                </label>
                <input type="number" id="penal-discount-on-principle" class="form-control" min="0" max="100" step=".01" name="penal_discount_on_principle" value="{{ old('penal_discount_on_principle', $program ? $program->discountDetails?->first()?->penal_discount_on_principle : '') }}" />
                <x-input-error :messages="$errors->get('penal_discount_on_principle')" />
                <div class="d-flex d-none vendor-financing-penal-message">
                  <span class="text-danger">{{ __('Penal Income Accounts have not been set') }}</span>
                  @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                    <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                  @endif
                </div>
              </div>
              {{-- Anchor Fee Recovery --}}
              <div class="col-sm-6">
                <label class="form-label" for="anchor-fee-recovery">{{ __('Anchor Fee Recovery')}}</label>
                {{-- <input type="text" id="anchor-fee-recovery" class="form-control" name="anchor_fee_recovery" value="{{ old('anchor_fee_recovery', $program ? $program->discountDetails?->first()?->anchor_fee_recovery : '') }}" /> --}}
                <select class="form-select" id="anchor-fee-recovery" name="anchor_fee_recovery">
                  <option value="Beginning of Tenor" @if(old('anchor_fee_recovery') == "Beginning of Tenor" || ($program && $program->discountDetails?->first()?->anchor_fee_recovery == 'Beginning of Tenor')) selected @endif>{{ __('Beginning of Tenor')}}</option>
                  <option value="End of Tenor" @if(old('anchor_fee_recovery') == "End of Tenor" || ($program && $program->discountDetails?->first()?->anchor_fee_recovery == 'End of Tenor')) selected @endif>{{ __('End of Tenor')}}</option>
                </select>
                <x-input-error :messages="$errors->get('anchor_fee_recovery')" />
              </div>
              {{-- Grace Period Days --}}
              <div class="col-sm-6">
                <label class="form-label" for="grace-period">
                  {{ __('Grace Period (Days)')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Number of days after due date that the anchor is allowed to payback without applying full penal charges"></i>
                </label>
                <input type="number" id="grace-period" class="form-control" min="0" name="grace_period" value="{{ old('grace_period', $program ? $program->discountDetails?->first()?->grace_period : '') }}" />
                <x-input-error :messages="$errors->get('grace_period')" />
              </div>
              {{-- Grace Period Discount --}}
              <div class="col-sm-6">
                <label class="form-label" for="grace-period-discount">
                  {{ __('Grace Period Discount')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Penal Rate applied in the grace period"></i>
                </label>
                <input class="form-control" type="number" id="grace-period-discount" min="0" max="100" step=".01" name="grace_period_discount" value="{{ old('grace_period_discount', $program ? $program->discountDetails?->first()?->grace_period_discount : '') }}" />
                <x-input-error :messages="$errors->get('grace_period_discount')" />
              </div>
              {{-- Maturity Handling on Holidays --}}
              <div class="col-sm-6">
                <label class="form-label" for="maturity-handling-on-holidays">
                  {{ __('Maturity Handling on Holidays')}}
                </label>
                <select class="form-select" id="maturity-handling-on-holidays" name="maturity_handling_on_holidays">
                  <option value="No Effect" @if(old('maturity_handling_on_holidays') == "No Effect" || ($program && $program->discountDetails?->first()?->maturity_handling_on_holidays == 'No Effect')) selected @endif>{{ __('No Effect')}}</option>
                  <option value="Prepone to previous working day" @if(old('maturity_handling_on_holidays') == "Prepone to previous working day" || ($program && $program->discountDetails?->first()?->maturity_handling_on_holidays == 'Prepone to previous working day')) selected @endif>{{ __('Prepone to previous working day')}}</option>
                  <option value="Postpone to next working day" @if(old('maturity_handling_on_holidays') == "Postpone to next working day" || ($program && $program->discountDetails?->first()?->maturity_handling_on_holidays == 'Postpone to next working day')) selected @endif>{{ __('Postpone to next working day')}}</option>
                </select>
                <x-input-error :messages="$errors->get('maturity_handling_on_holidays')" />
              </div>
              <div class="col-sm-6">
              </div>
              <hr>
              <div class="col-12 row" id="program-fees">
                @if ($program && $program->fees)
                  @if (!$vendor_financing_fees_income_account->value)
                    <div class="col-12 d-none" id="vendor-financing-fees-message">
                      <div class="d-flex">
                        <span class="text-danger">{{ __('Fees Income Accounts have not been set') }}</span>
                        @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                          <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                        @endif
                      </div>
                    </div>
                  @endif
                  @forelse ($program->fees as $key => $fee_details)
                    <div class="col-sm-4">
                      <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                      <input type="text" id="fee-name" class="form-control" name="fee_names[{{ $key }}]" value="{{ $fee_details->fee_name }}" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="fee-type-{{ $key }}">{{ __('Type')}}</label>
                      <select class="form-select" id="fee-type-{{ $key }}" name="fee_types[{{ $key }}]" onchange="changeFeeType({{ $key }})">
                        <option value="percentage" @if($fee_details->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                        <option value="amount" @if($fee_details->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                        <option value="per amount" @if($fee_details->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                      </select>
                    </div>
                    <div class="col-sm-4 @if($fee_details->type != 'per amount') d-none @endif" id="fee-per-amount-value-{{ $key }}">
                      <label class="form-label" for="value">{{ __('Amount')}}</label>
                      <input type="number" class="form-control" name="fee_per_amount[{{ $key }}]" step=".01" value="{{ $fee_details->per_amount }}" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="value">{{ __('Value')}}</label>
                      <input type="number" id="fee-value-{{ $key }}" class="form-control" step=".01" name="fee_values[{{ $key }}]" value="{{ $fee_details->value }}" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="anchor-fee-bearing">
                        <span id="anchor-fee-bearing-label">
                          {{ __('Anchor Bearing')}}
                        </span>
                        (%)
                      </label>
                      <input type="number" id="anchor-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="fee_anchor_bearing_discount[{{ $key }}]" value="{{ $fee_details->anchor_bearing_discount }}" oninput="updateFeeBearing(0)" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="vendor-fee-bearing">{{ __('Vendor Bearing')}} (%)</label>
                      <input type="number" id="vendor-fee-bearing-{{ $key }}" class="form-control" name="fee_vendor_bearing_discount[{{ $key }}]" value="{{ $fee_details->vendor_bearing_discount }}" step=".01" readonly />
                    </div>
                    <div class="col-sm-4" id="fee-charge-types-{{ $key }}">
                      <label class="form-label" for="value">{{ __('Charge')}}</label>
                      <select class="form-select" id="fee-charge-type-{{ $key }}" name="charge_types[{{ $key }}]">
                        <option value="fixed" @if($fee_details->charge_type == 'amount') selected @endif>{{ __('Fixed')}}</option>
                        <option value="daily" @if($fee_details->charge_type == 'daily') selected @endif title="Daily">{{ __('Per Day')}}</option>
                      </select>
                    </div>
                    <div class="col-sm-4" id="fee-account-numbers-{{ $key }}">
                      <label class="form-label" for="value">{{ __('Credit To')}}</label>
                      <select class="form-select" id="fee-account-numbers-{{ $key }}" name="fee_account_numbers[{{ $key }}]">
                        <option value="">{{ __('Select Account') }}</option>
                        @foreach ($bank_payment_accounts as $bank_payment_account)
                          <option value="{{ $bank_payment_account->account_number }}" @if($fee_details->account_number === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                      <select class="form-select" id="taxes" name="taxes[{{ $key }}]">
                        <option value="">{{ __('Select Tax')}}</option>
                        @foreach ($taxes as $key => $tax)
                          <option value="{{ $tax['rate'] }}" @if($fee_details->taxes == $tax['rate'] || $tax['is_default']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-4"></div>
                  @empty
                    <div class="col-sm-4">
                      <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                      <input type="text" id="fee-name" class="form-control" name="fee_names[0]" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="fee-type-0">{{ __('Type')}}</label>
                      <select class="form-select" id="fee-type-0" name="fee_types[0]" onchange="changeFeeType(0)">
                        <option value="percentage">{{ __('Percentage')}}</option>
                        <option value="amount">{{ __('Amount')}}</option>
                        <option value="per amount">{{ __('Per Amount')}}</option>
                      </select>
                    </div>
                    <div class="col-sm-4 d-none" id="fee-per-amount-value-0">
                      <label class="form-label" for="value">{{ __('Amount')}}</label>
                      <input type="number" class="form-control" name="fee_per_amount[0]" step=".0000001" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="value">{{ __('Value')}}</label>
                      <input type="number" id="fee-value-0" class="form-control" step=".000001" name="fee_values[0]" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="anchor-fee-bearing">
                        <span id="anchor-fee-bearing-label">
                          {{ __('Anchor Bearing')}}
                        </span>
                        (%)
                      </label>
                      <input type="number" id="anchor-fee-bearing-0" class="form-control" min="0" max="100" step=".000001" name="fee_anchor_bearing_discount[0]" oninput="updateFeeBearing(0)" />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="vendor-fee-bearing">{{ __('Vendor Bearing')}} (%)</label>
                      <input type="number" id="vendor-fee-bearing-0" class="form-control" name="fee_vendor_bearing_discount[0]" step=".0000001" readonly />
                    </div>
                    <div class="col-sm-4" id="fee-charge-types-0">
                      <label class="form-label" for="value">{{ __('Charge')}}</label>
                      <select class="form-select" id="fee-charge-type-0" name="charge_types[0]">
                        <option value="fixed">{{ __('Fixed')}}</option>
                        <option value="daily">{{ __('Per Day')}}</option>
                      </select>
                    </div>
                    <div class="col-sm-4" id="fee-account-numbers-0">
                      <label class="form-label" for="value">{{ __('Credit To')}}</label>
                      <select class="form-select" id="fee-account-numbers-0" name="fee_account_numbers[0]">
                        <option value="">{{ __('Select Account') }}</option>
                        @foreach ($bank_payment_accounts as $bank_payment_account)
                          <option value="{{ $bank_payment_account->account_number }}">{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                      <select class="form-select" id="taxes" name="taxes[0]">
                        <option value="">{{ __('Select Tax')}}</option>
                        @foreach ($taxes as $key => $tax)
                          <option value="{{ $tax['rate'] }}">{{ $key }} ({{ $tax['rate'] }}%)</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-4"></div>
                  @endforelse
                @else
                  <div class="col-12 d-none" id="vendor-financing-fees-message">
                    <div class="d-flex">
                      <span class="text-danger">{{ __('Fees Income Accounts have not been set') }}</span>
                      @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                        <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                      @endif
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                    <input type="text" id="fee-name-0" class="form-control" name="fee_names[]" @if (!$vendor_financing_fees_income_account->value && !$factoring_fees_income_account->value) readonly @endif />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="fee-type-0">
                      {{ __('Type')}}
                      <span class="text-danger">*</span>
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="Per Amount Calculation: The 'Value' for block of 'Amount' of the Payment Amount"></i>
                    </label>
                    <select class="form-select" id="fee-type-0" name="fee_types[]" onchange="changeFeeType(0)" @if (!$vendor_financing_fees_income_account->value && !$factoring_fees_income_account->value) readonly @endif>
                      <option value="percentage">{{ __('Percentage')}}</option>
                      <option value="amount">{{ __('Amount')}}</option>
                      <option value="per amount">{{ __('Per Amount')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4 d-none" id="fee-per-amount-value-0">
                    <label class="form-label" for="value">{{ __('Amount')}}</label>
                    <input type="number" class="form-control" name="fee_per_amount[]" step=".000001" @if (!$vendor_financing_fees_income_account->value && !$factoring_fees_income_account->value) readonly @endif />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="value">{{ __('Value')}}</label>
                    <input type="number" id="fee-value-0" class="form-control" step=".0000001" name="fee_values[]" @if (!$vendor_financing_fees_income_account->value && !$factoring_fees_income_account->value) readonly @endif />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="anchor-fee-bearing">
                      <span id="anchor-fee-bearing-label">
                        {{ __('Anchor Bearing')}}
                      </span>
                      (%)
                    </label>
                    <input type="number" id="anchor-fee-bearing-0" class="form-control" min="0" max="100" step=".0000001" name="fee_anchor_bearing_discount[]" oninput="updateFeeBearing(0)" @if (!$vendor_financing_fees_income_account->value && !$factoring_fees_income_account->value) readonly @endif  />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="vendor-fee-bearing">{{ __('Vendor Bearing')}} (%)</label>
                    <input type="number" id="vendor-fee-bearing-0" class="form-control" name="fee_vendor_bearing_discount[]" step=".01" readonly />
                  </div>
                  <div class="col-sm-4" id="fee-charge-types-0">
                    <label class="form-label" for="value">{{ __('Charge')}}</label>
                    <select class="form-select" id="fee-charge-type-0" name="charge_types[0]">
                      <option value="fixed">{{ __('Fixed')}}</option>
                      <option value="daily" title="Daily">{{ __('Per Day')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4" id="fee-account-numbers-0">
                    <label class="form-label" for="value">{{ __('Credit To')}}</label>
                    <select class="form-select" id="fee-account-numbers-0" name="fee_account_numbers[0]">
                      <option value="">{{ __('Select Account') }}</option>
                      @foreach ($bank_payment_accounts as $bank_payment_account)
                        <option value="{{ $bank_payment_account->account_number }}">{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                    <select class="form-select" id="taxes-0" name="taxes[]" @if (!$vendor_financing_fees_income_account->value && !$factoring_fees_income_account->value) readonly @endif >
                      <option value="">{{ __('Select Tax')}}</option>
                      @foreach ($taxes as $key => $tax)
                        <option value="{{ $tax['rate'] }}" @if($tax['is_default']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-4"></div>
                @endif
              </div>
              <div class="col-12 d-none add-item-section" id="vendor-financing-fees-message">
                <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                  <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                  <span class="mx-2">
                    {{ __('Add Item')}}
                  </span>
                </span>
              </div>
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-next" type="button" id="submit-two"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
            <div class="row g-3 dealer-financing d-none">
              {{-- Benchmark Title --}}
              <div class="col-sm-6">
                <label class="form-label" for="benchmark-title">
                  {{ __('Benchmark Title(Maturity)')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="dealer-benchmark-title" name="dealer_benchmark_title" onchange="changeDealerBenchmarkRate()">
                  <option value="">{{ __('Select Base Rate')}}</option>
                  @foreach ($benchmark_rates as $key => $benchmark_rate)
                    @if (old('benchmark_title') == $key || ($program && $program->discountDetails?->first()?->benchmark_title == $key) || $benchmark_rate['is_default'])
                      @php($selected_base_rate = $benchmark_rate['rate'])
                    @endif
                    <option value="{{ $key }}" data-rate="{{ $benchmark_rate['rate'] }}" @if(old('dealer_benchmark_title') == $key || ($program && $program->discountDetails?->first()?->benchmark_title == $key) || $benchmark_rate['is_default']) selected @endif>{{ $key }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('benchmark_title')" />
              </div>
              {{-- Current Base Rate --}}
              <div class="col-sm-6">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="current-benchmark-rate">{{ __('Current Base Rate')}}</label>
                  <span class="text-primary">{{ __('Set As Per Current Master')}}</span>
                </div>
                <input type="number" readonly id="current-dealer-benchmark-rate" class="form-control" step=".01" name="dealer_benchmark_rate" value="{{ $selected_base_rate != '' ? $selected_base_rate : old('dealer_benchmark_rate', $program ? $program->discountDetails?->first()?->benchmark_rate : '') }}" />
                <x-input-error :messages="$errors->get('benchmark_rate')" />
              </div>
              {{-- Discounts --}}
              <div class="col-12 row" id="program-discounts">
                @if ($program && $program->dealerDiscountRates->count() > 0)
                  @foreach ($program->dealerDiscountRates as $key => $dealer_discount_rate)
                    @if ($loop->first)
                      {{-- From Day --}}
                      <div class="col-sm-6">
                        <label for="" class="form-label">
                          {{ __('From Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                        </label>
                        <input type="number" name="from_day[{{ $key }}]" id="from-day-{{ $key }}" class="form-control" min="1" readonly value="{{ $dealer_discount_rate->from_day }}" />
                      </div>
                      {{-- To Day --}}
                      <div class="col-sm-6">
                        <label for="" class="form-label">
                          {{ __('To Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                        </label>
                        <input type="number" name="to_day[{{ $key }}]" id="to-day-{{ $key }}" class="form-control" min="2" value="{{ $dealer_discount_rate->to_day }}" />
                      </div>
                      {{-- Credit Spread --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="credit-spread">
                          {{ __('Credit Spread')}} (%)
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                        </label>
                        <input type="number" id="dealer-credit-spread-{{ $key }}" class="form-control" name="dealer_credit_spread[{{ $key }}]" value="{{ $dealer_discount_rate->credit_spread }}" min="0" max="100" step=".0000000000001" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      {{-- Business Strategy Spread --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="business-strategy-spread">
                          {{ __('Business Strategy Spread')}} (%)
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="dealer-business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".00000000001" name="dealer_business_strategy_spread[{{ $key }}]" value="{{ $dealer_discount_rate->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      {{-- Total Spread --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="total-spread">
                          {{ __('Total Spread')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                        </label>
                        <input type="number" id="dealer-total-spread-{{ $key }}" readonly class="form-control" min="0" max="100" step=".0000000001" name="dealer_total_spread[{{ $key }}]" value="{{ $dealer_discount_rate->total_spread }}" />
                      </div>
                      {{-- Total ROI --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="total-roi">
                          {{ __('Total ROI')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                        </label>
                        <input type="number" id="dealer-total-roi-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_total_roi[{{ $key }}]" value="{{ $dealer_discount_rate->total_roi }}" oninput="getPostedDiscount()" />
                      </div>
                      {{-- Daily Discount Charge --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="discount-charge">
                          {{ __('Discount Charge')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                        </label>
                        <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[{{ $key }}]" value="{{ round($dealer_discount_rate->total_roi / 365, 2) }}" />
                      </div>
                      <div class="col-sm-9"></div>
                    @else
                      {{-- From Day --}}
                      <div class="col-sm-6" id="from-day-section-{{ $key }}">
                        <label for="" class="form-label">
                          {{ __('From Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                        </label>
                        <input type="number" name="from_day[{{ $key }}]" id="from-day-{{ $key }}" class="form-control" min="1" readonly value="{{ $dealer_discount_rate->from_day }}" />
                      </div>
                      {{-- To Day --}}
                      <div class="col-sm-6" id="to-day-section-{{ $key }}">
                        <label for="" class="form-label">
                          {{ __('To Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                        </label>
                        <input type="number" name="to_day[{{ $key }}]" id="to-day-{{ $key }}" class="form-control" min="2" value="{{ $dealer_discount_rate->to_day }}" />
                      </div>
                      {{-- Credit Spread --}}
                      <div class="col-sm-3" id="credit-spread-section-{{ $key }}">
                        <label class="form-label" for="credit-spread">
                          {{ __('Credit Spread')}} (%)
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                        </label>
                        <input type="number" id="dealer-credit-spread-{{ $key }}" class="form-control" name="dealer_credit_spread[{{ $key }}]" value="{{ $dealer_discount_rate->credit_spread }}" min="0" max="100" step=".0000000000001" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      {{-- Business Strategy Spread --}}
                      <div class="col-sm-3" id="business-strategy-spread-section-{{ $key }}">
                        <label class="form-label" for="business-strategy-spread">
                          {{ __('Business Strategy Spread')}} (%)
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="dealer-business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".00000000001" name="dealer_business_strategy_spread[{{ $key }}]" value="{{ $dealer_discount_rate->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      {{-- Total Spread --}}
                      <div class="col-sm-3" id="total-spread-{{ $key }}">
                        <label class="form-label" for="total-spread">
                          {{ __('Total Spread')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                        </label>
                        <input type="number" id="dealer-total-spread-{{ $key }}" readonly class="form-control" min="0" max="100" step=".0000000001" name="dealer_total_spread[{{ $key }}]" value="{{ $dealer_discount_rate->total_spread }}" />
                      </div>
                      {{-- Total ROI --}}
                      <div class="col-sm-3" id="total-roi-{{ $key }}">
                        <label class="form-label" for="total-roi">
                          {{ __('Total ROI')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                        </label>
                        <input type="number" id="dealer-total-roi-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_total_roi[{{ $key }}]" value="{{ $dealer_discount_rate->total_roi }}" oninput="getPostedDiscount()" />
                      </div>
                      {{-- Discount Charge --}}
                      <div class="col-sm-3" id="daily-daily-discount-charge-{{ $key }}">
                        <label class="form-label" for="daily-charge">
                          {{ __('Discount Charge')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                        </label>
                        <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[{{ $key }}]" value="{{ round($dealer_discount_rate->total_roi / 365, 2) }}" />
                      </div>
                      <div class="col-12 mb-2 mt-2" id="discount-delete-{{ $key }}">
                        <i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDiscount({{ $key }})"></i>
                      </div>
                    @endif
                  @endforeach
                @else
                  {{-- From Day --}}
                  <div class="col-sm-6">
                    <label for="" class="form-label">
                      {{ __('From Day')}}
                      <span class="text-danger">*</span>
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                    </label>
                    <input type="number" name="from_day[]" id="from-day-0" class="form-control" min="1" readonly value="1" />
                  </div>
                  {{-- To Day --}}
                  <div class="col-sm-6">
                    <label for="" class="form-label">
                      {{ __('To Day')}}
                      <span class="text-danger">*</span>
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                    </label>
                    <input type="number" name="to_day[]" id="to-day-0" class="form-control" min="2" />
                  </div>
                  {{-- Credit Spread --}}
                  <div class="col-sm-3">
                    <label class="form-label" for="credit-spread">
                      {{ __('Credit Spread')}} (%)
                      <span class="text-danger">*</span>
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                    </label>
                    <input type="number" id="dealer-credit-spread-0" class="form-control" name="dealer_credit_spread[]" min="0" max="100" step=".0000000000001" oninput="changeDealerDiscountRates(0)" />
                  </div>
                  {{-- Business Strategy Spread --}}
                  <div class="col-sm-3">
                    <label class="form-label" for="business-strategy-spread">
                      {{ __('Business Strategy Spread')}} (%)
                      <span class="text-danger">*</span>
                    </label>
                    <input type="number" id="dealer-business-strategy-spread-0" class="form-control" min="0" max="100" step=".00000000001" name="dealer_business_strategy_spread[]" oninput="changeDealerDiscountRates(0)" />
                  </div>
                  {{-- Total Spread --}}
                  <div class="col-sm-3">
                    <label class="form-label" for="total-spread">
                      {{ __('Total Spread')}} (%)
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                    </label>
                    <input type="number" id="dealer-total-spread-0" readonly class="form-control" min="0" max="100" step=".0000000001" name="dealer_total_spread[]" />
                  </div>
                  {{-- Total ROI --}}
                  <div class="col-sm-3">
                    <label class="form-label" for="total-roi">
                      {{ __('Total ROI')}} (%)
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                    </label>
                    <input type="number" id="dealer-total-roi-0" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_total_roi[]" oninput="getPostedDiscount()" />
                  </div>
                  {{-- Discount Charge --}}
                  <div class="col-sm-3">
                    <label class="form-label" for="daily-discount-charge">
                      {{ __('Discount Charge')}} (%)
                      <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                    </label>
                    <input type="number" id="dealer-daily-discount-charge-0" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[]" />
                  </div>
                  <div class="col-sm-9"></div>
                @endif
              </div>
              <div class="col-12 row">
                <div class="col-10"></div>
                <div class="col-2">
                  <span class="d-flex justify-content-end align-items-center" id="add-discount" style="cursor: pointer">
                    <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                    <span class="mx-2">
                      {{ __('Add')}}
                    </span>
                  </span>
                </div>
              </div>
              {{-- Tax on Discount --}}
              <div class="col-sm-6">
                <label class="form-label" for="tax-on-discount">
                  {{ __('Tax on Discount')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select name="dealer_tax_on_discount" id="dealer_tax-on-discount" class="form-control">
                  <option value="">{{ __('Select')}}</option>
                  @foreach ($taxes as $key => $tax)
                    <option value="{{ $tax['rate'] }}" @if(old('dealer_tax_on_discount') == $tax['rate'] || $program && $program->discountDetails?->first()?->tax_on_discount == $tax || $tax['is_default']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                  @endforeach
                </select>
                {{-- <input type="number" id="dealer_tax-on-discount" class="form-control" min="0" max="100" name="dealer_tax_on_discount" step=".01" value="{{ old('dealer_tax_on_discount') }}" /> --}}
                <x-input-error :messages="$errors->get('dealer_tax_on_discount')" />
              </div>
              {{-- Discount Type --}}
              <div class="col-sm-6">
                <label class="form-label" for="discount-type">
                  {{ __('Discount Type')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="dealer-discount-type" name="dealer_discount_type">
                  <option value="">{{ __('Select Discount Type')}}</option>
                  <option value="Front Ended" @if(old('dealer_discount_type') == "Front Ended" || ($program && $program->discountDetails?->first()?->discount_type == 'Front Ended')) selected @endif>{{ __('Front Ended')}}</option>
                  <option value="Rear Ended" @if(old('dealer_discount_type') == "Rear Ended" || ($program && $program->discountDetails?->first()?->discount_type == 'Rear Ended')) selected @endif>{{ __('Rear Ended')}}</option>
                </select>
                <x-input-error :messages="$errors->get('dealer_discount_type')" />
              </div>
              {{-- Fee Type --}}
              <div class="col-sm-6 d-none">
                <label class="form-label" for="fee-type">
                  {{ __('Fee Type')}}
                </label>
                <select class="form-select" id="dealer-fee-type" name="dealer_fee_type">
                  <option value="">{{ __('Select Fee Type')}}</option>
                  <option value="Front Ended" @if(old('dealer_fee_type') == "Front Ended" || ($program && $program->discountDetails?->first()?->fee_type == 'Front Ended')) selected @endif>{{ __('Front Ended')}}</option>
                  <option value="Rear Ended" @if(old('dealer_fee_type') == "Rear Ended" || ($program && $program->discountDetails?->first()?->fee_type == 'Rear Ended')) selected @endif>{{ __('Rear Ended')}}</option>
                </select>
                <x-input-error :messages="$errors->get('dealer_fee_type')" />
              </div>
              {{-- Limit Overdue Days --}}
              <div class="col-sm-6">
                <label class="form-label" for="limit-block-overdue-days">
                  {{ __('Limit Block Overdue Days')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="limit-block-overdue-days" class="form-control" min="0" name="limit_block_overdue_days" value="{{ old('limit_block_overdue_days', $program ? $program->discountDetails?->first()?->limit_block_overdue_days : '') }}" />
                <x-input-error :messages="$errors->get('limit_block_overdue_days')" />
              </div>
              {{-- Penal Discount on Principle --}}
              <div class="col-sm-6">
                <label class="form-label" for="penal-discount-on-principle">
                  {{ __('Penal Discount on Principle')}} (%)
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="dealer-penal-discount-on-principle" class="form-control" min="0" max="100" step=".01" name="dealer_penal_discount_on_principle" value="{{ old('dealer_penal_discount_on_principle', $program ? $program->discountDetails?->first()?->penal_discount_on_principle : '') }}" />
                <x-input-error :messages="$errors->get('dealer_penal_discount_on_principle')" />
                <div class="d-flex d-none dealer-financing-penal-message">
                  <span class="text-danger">{{ __('Penal Income Accounts have not been set') }}</span>
                  @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                    <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                  @endif
                </div>
              </div>
              {{-- Discount on Posted Discount Spread --}}
              <div class="col-sm-6">
                <label class="form-label" for="discount-posted-spread">
                  {{ __('Discount on Posted Discount Spread')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="discount-posted-spread" class="form-control" min="0" max="100" step=".01" name="discount_posted_spread" value="{{ old('discount_posted_spread', $program ? $program->discountDetails?->first()?->discount_on_posted_discount_spread : '') }}" oninput="getPostedDiscount()" />
                <x-input-error :messages="$errors->get('discount_posted_spread')" />
              </div>
              {{-- Discount on Posted Spread --}}
              <div class="col-sm-6">
                <label class="form-label" for="discount-posted-discount">
                  {{ __('Discount on Posted Discount')}} (%)
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="text" id="discount-posted" class="form-control" min="0" max="100" step=".01" name="discount_posted" readonly value="{{ old('discount_posted', $program ? $program->discountDetails?->first()?->discount_on_posted_discount : '') }}" />
                <x-input-error :messages="$errors->get('discount_posted')" />
              </div>
              {{-- Grace Period --}}
              <div class="col-sm-6">
                <label class="form-label" for="grace-period">
                  {{ __('Grace Period (Days)')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="grace-period" class="form-control" min="0" name="dealer_grace_period" value="{{ old('dealer_grace_period', $program ? $program->discountDetails?->first()?->grace_period : '') }}" />
                <x-input-error :messages="$errors->get('dealer_grace_period')" />
              </div>
              {{-- Grace Period Discount --}}
              <div class="col-sm-6">
                <label class="form-label" for="grace-period-discount">
                  {{ __('Grace Period Discount')}}
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input class="form-control" type="number" id="grace-period-discount" min="0" max="100" step=".01" name="dealer_grace_period_discount" value="{{ old('dealer_grace_period_discount', $program ? $program->discountDetails?->first()?->grace_period_discount : '') }}" />
                <x-input-error :messages="$errors->get('dealer_grace_period_discount')" />
              </div>
              {{-- Maturity Handling on holidays --}}
              <div class="col-sm-6">
                <label class="form-label" for="maturity-handling-on-holidays">
                  {{ __('Maturity Handling on Holidays')}}
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select class="form-select" id="maturity-handling-on-holidays" name="dealer_maturity_handling_on_holidays">
                  <option value="No Effect" @if(old('dealer_maturity_handling_on_holidays') == "No Effect" || ($program && $program->discountDetails?->first()?->maturity_handling_on_holidays == 'No Effect')) selected @endif>{{ __('No Effect')}}</option>
                  <option value="Prepone to previous working day" @if(old('dealer_maturity_handling_on_holidays') == "Prepone to previous working day" || ($program && $program->discountDetails?->first()?->maturity_handling_on_holidays == 'Prepone to previous working day')) selected @endif>{{ __('Prepone to previous working day')}}</option>
                  <option value="Postpone to next working day" @if(old('dealer_maturity_handling_on_holidays') == "Postpone to next working day" || ($program && $program->discountDetails?->first()?->maturity_handling_on_holidays == 'Postpone to next working day')) selected @endif>{{ __('Postpone to next working day')}}</option>
                </select>
                <x-input-error :messages="$errors->get('dealer_maturity_handling_on_holidays')" />
              </div>
              <div class="col-sm-6">
              </div>
              <hr>
              <div class="col-12 row dealer-financing d-none" id="dealer-program-fees">
                @if ($program && $program->fees)
                  @if (!$dealer_fees_income_account->value)
                    <div class="d-flex">
                      <span class="text-danger">{{ __('Fees Accounts have not been set') }}</span>
                      @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                        <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                      @endif
                    </div>
                  @endif
                  @foreach ($program->fees as $key => $fee)
                    @if ($loop->first)
                      <div class="col-sm-4">
                        <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                        <input type="text" id="dealer-fee-name-{{ $key }}" class="form-control" name="dealer_fee_names[{{ $key }}]" value="{{ $fee->fee_name }}" />
                      </div>
                      <div class="col-sm-4">
                        <label class="form-label" for="fee-type-{{ $key }}">{{ __('Type')}}</label>
                        <select class="form-select" id="fee-type-{{ $key }}" name="dealer_fee_types[{{ $key }}]" onchange="changeDealerFeeType({{ $key }})">
                          <option value="percentage" @if($fee->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                          <option value="amount" @if($fee->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                          <option value="per amount" @if($fee->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                        </select>
                      </div>
                      <div class="col-sm-4 @if($fee->type != 'per amount') d-none @endif" id="dealer-fee-per-amount-value-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Amount')}}</label>
                        <input type="number" class="form-control" name="dealer_fee_per_amount[{{ $key }}]" value="{{ $fee->per_amount }}" />
                      </div>
                      <div class="col-sm-4">
                        <label class="form-label" for="value">{{ __('Value')}}</label>
                        <input type="number" id="dealer-fee-value-{{ $key }}" class="form-control" step=".01" name="dealer_fee_values[{{ $key }}]" value="{{ $fee->value }}" oninput="updateDealerBearingFee({{ $key }})" />
                      </div>
                      <div class="col-sm-4" id="fee-dealer-bearing-{{ $key }}">
                        <label class="form-label" for="dealer-fee-bearing">{{ __('Dealer Bearing')}} (%)</label>
                        <input type="number" id="dealer-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="fee_dealer_bearing_discount[{{ $key }}]" value="{{ $fee->dealer_bearing }}" readonly />
                      </div>
                      <div class="col-sm-4" id="dealer-fee-charge-types-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Charge')}}</label>
                        <select class="form-select" id="fee-charge-type-{{ $key }}" name="dealer_charge_types[{{ $key }}]">
                          <option value="fixed" @if($fee->charge_type == 'amount') selected @endif>{{ __('Fixed')}}</option>
                          <option value="daily" @if($fee->charge_type == 'daily') selected @endif title="Daily">{{ __('Per Day')}}</option>
                        </select>
                      </div>
                      <div class="col-sm-4" id="dealer-fee-account-numbers-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Credit To')}}</label>
                        <select class="form-select" id="dealer-fee-account-numbers-{{ $key }}" name="dealer_fee_account_numbers[{{ $key }}]">
                          <option value="">{{ __('Select Account') }}</option>
                          @foreach ($bank_payment_accounts as $bank_payment_account)
                            <option value="{{ $bank_payment_account->account_number }}" @if($fee->account_number === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                        <select class="form-select" id="dealer-taxes-{{ $key }}" name="dealer_taxes[{{ $key }}]">
                          <option value="">{{ __('Select') }}</option>
                          @foreach ($taxes as $key => $tax)
                            <option value="{{ $tax['rate'] }}" @if($fee->taxes == $tax['rate']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                          @endforeach
                        </select>
                      </div>
                    @else
                      @if (!$dealer_fees_income_account->value)
                        <div class="d-flex">
                          <span class="text-danger">{{ __('Fees Accounts have not been set') }}</span>
                          @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                            <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                          @endif
                        </div>
                      @endif
                      <div class="col-sm-4" id="dealer-fee-name-{{ $key }}">
                        <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                        <input type="text" id="dealer-fee-name-{{ $key }}" class="form-control" name="dealer_fee_names[{{ $key }}]" value="{{ $fee->fee_name }}" @if (!$dealer_fees_income_account->value) readonly @endif />
                      </div>
                      <div class="col-sm-4" id="dealer-fee-type-{{ $key }}">
                        <label class="form-label" for="fee-type-{{ $key }}">{{ __('Type')}}</label>
                        <select class="form-select" id="fee-type-{{ $key }}" name="dealer_fee_types[{{ $key }}]" onchange="changeDealerFeeType({{ $key }})" @if (!$dealer_fees_income_account->value) readonly @endif>
                          <option value="percentage" @if($fee->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                          <option value="amount" @if($fee->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                          <option value="per amount" @if($fee->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                        </select>
                      </div>
                      <div class="col-sm-4 d-none" id="dealer-fee-per-amount-value-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Amount')}}</label>
                        <input type="number" class="form-control" step=".00000001" name="dealer_fee_per_amount[{{ $key }}]" value="{{ $fee->per_amount }}" @if (!$dealer_fees_income_account->value) readonly @endif />
                      </div>
                      <div class="col-sm-4" id="dealer-fee-values-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Value')}}</label>
                        <input type="number" id="dealer-fee-value-{{ $key }}" class="form-control" step=".000000001" name="dealer_fee_values[{{ $key }}]" value="{{ $fee->value }}" oninput="updateDealerBearingFee({{ $key }})" @if (!$dealer_fees_income_account->value) readonly @endif />
                      </div>
                      <div class="col-sm-4" id="fee-dealer-bearing-{{ $key }}">
                        <label class="form-label" for="dealer-fee-bearing">{{ __('Dealer Bearing')}} (%)</label>
                        <input type="number" id="dealer-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="fee_dealer_bearing_discount[{{ $key }}]" value="{{ $fee->dealer_bearing }}" readonly />
                      </div>
                      <div class="col-sm-4" id="dealer-fee-charge-types-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Charge')}}</label>
                        <select class="form-select" id="fee-charge-type-{{ $key }}" name="dealer_charge_types[{{ $key }}]">
                          <option value="fixed" @if($fee->charge_type == 'amount') selected @endif>{{ __('Fixed')}}</option>
                          <option value="daily" @if($fee->charge_type == 'daily') selected @endif title="Daily">{{ __('Per Day')}}</option>
                        </select>
                      </div>
                      <div class="col-sm-4" id="dealer-fee-account-numbers-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Credit To')}}</label>
                        <select class="form-select" id="dealer-fee-account-numbers-{{ $key }}" name="dealer_fee_account_numbers[{{ $key }}]">
                          <option value="">{{ __('Select Account') }}</option>
                          @foreach ($bank_payment_accounts as $bank_payment_account)
                            <option value="{{ $bank_payment_account->account_number }}" @if($fee->account_number === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-sm-6" id="dealer-fee-taxes-{{ $key }}">
                        <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                        <select class="form-select" id="dealer-taxes-{{ $key }}" name="dealer_taxes[{{ $key }}]" @if (!$dealer_fees_income_account->value) readonly @endif>
                          <option value="">{{ __('Select')}}</option>
                          @foreach ($taxes as $key => $tax)
                            <option value="{{ $tax['rate'] }}" @if($fee->taxes == $tax['rate']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-12 mb-2 mt-2" id="dealer-fee-delete-{{ $key }}">
                        <i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDealerFee({{ $key }})"></i>
                      </div>
                    @endif
                  @endforeach
                @else
                  <div class="d-flex d-none dealer-financing-fees-message">
                    <span class="text-danger">{{ __('Fees Accounts have not been set') }}</span>
                    @if (auth()->user()->hasPermissionTo('Manage Product Configurations'))
                      <a href="{{ route('configurations.index', ['bank' => $bank]) }}" class="mx-2">{{ __('Click Here to Set') }}</a>
                    @endif
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                    <input type="text" id="dealer-fee-name-0" class="form-control" name="dealer_fee_names[]" @if (!$dealer_fees_income_account->value) readonly @endif />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="fee-type-0">{{ __('Type')}}</label>
                    <select class="form-select" id="dealer-fee-type-0" name="dealer_fee_types[]" onchange="changeDealerFeeType(0)" @if (!$dealer_fees_income_account->value) readonly @endif>
                      <option value="percentage">{{ __('Percentage')}}</option>
                      <option value="amount">{{ __('Amount')}}</option>
                      <option value="per amount">{{ __('Per Amount') }}</option>
                    </select>
                  </div>
                  <div class="col-sm-4 d-none" id="dealer-fee-per-amount-value-0">
                    <label class="form-label" for="value">{{ __('Amount')}}</label>
                    <input type="number" class="form-control" name="dealer_fee_per_amount[]" step=".01" @if (!$dealer_fees_income_account->value) readonly @endif />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="value">{{ __('Value')}}</label>
                    <input type="number" id="dealer-fee-value-0" class="form-control" step=".01" name="dealer_fee_values[]" oninput="updateDealerBearingFee(0)" @if (!$dealer_fees_income_account->value) readonly @endif />
                  </div>
                  <div class="col-sm-4" id="fee-dealer-bearing-0">
                    <label class="form-label" for="dealer-fee-bearing">{{ __('Dealer Bearing')}} (%)</label>
                    <input type="number" id="dealer-fee-bearing-0" class="form-control" min="0" max="100" step=".01" name="fee_dealer_bearing_discount[0]" readonly />
                  </div>
                  <div class="col-sm-4" id="dealer-fee-charge-types-0">
                    <label class="form-label" for="value">{{ __('Charge')}}</label>
                    <select class="form-select" id="fee-charge-type-0" name="dealer_charge_types[0]">
                      <option value="fixed">{{ __('Fixed')}}</option>
                      <option value="daily" title="Daily">{{ __('Per Day')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4" id="dealer-fee-account-numbers-0">
                    <label class="form-label" for="value">{{ __('Credit To')}}</label>
                    <select class="form-select" id="dealer-fee-account-numbers-0" name="dealer_fee_account_numbers[0]">
                      <option value="">{{ __('Select Account') }}</option>
                      @foreach ($bank_payment_accounts as $bank_payment_account)
                        <option value="{{ $bank_payment_account->account_number }}">{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                    <select class="form-select" id="dealer-taxes-0" name="dealer_taxes[]" @if (!$dealer_fees_income_account->value) readonly @endif>
                      <option value="">{{ __('Select')}}</option>
                      @foreach ($taxes as $key => $tax)
                        <option value="{{ $tax['rate'] }}" @if($tax['is_default']) selected @endif>{{ $key }} ({{ $tax['rate'] }}%)</option>
                      @endforeach
                    </select>
                  </div>
                @endif
              </div>
              <div class="col-12 dealer-financing d-none dealer-financing-add-fees-btn">
                <span class="d-flex align-items-center" id="add-dealer-item" style="cursor: pointer">
                  <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                  <span class="mx-2">
                    {{ __('Add Fee')}}
                  </span>
                </span>
              </div>
              <div class="col-12 d-flex justify-content-between mt-2">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-next" type="button" id="submit-three"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Email Mobile Details -->
          <div id="comm-details" class="content">
            <div class="row">
              <div class="col-12">
                <h5>{{ __('New Anchor User Details')}}</h5>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="anchor-email">{{ __('Anchor User Name')}}</label>
                <input type="text" id="anchor-name" class="form-control" name="anchor_names[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="anchor-email">{{ __('Anchor User Email ID')}}</label>
                <input type="email" id="anchor-email" class="form-control" name="anchor_emails[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="anchor-mobile-no">{{ __('Anchor User Mobile No')}}</label>
                <div class="row">
                  <div class="col-5">
                    <select name="country_code[]" id="country-code" class="select2">
                      <option value="">{{ __('Select Country Code')}}</option>
                      @foreach ($countries as $country)
                        <option value="{{ $country->dial_code }}">{{ $country->name }}({{ $country->dial_code }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-7">
                    <input type="tel" id="phone-number" class="form-control mx-1" name="anchor_phone_numbers[]" placeholder="Enter Phone Number" maxlength="10" />
                  </div>
                </div>
                {{-- <input type="tel" id="anchor-mobile-no" class="form-control" name="anchor_phone_numbers[]" /> --}}
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="role">{{ __('User Role')}}</label>
                <select name="anchor_roles[]" id="" class="form-select">
                  <option value="">{{ __('Select Users Role')}}</option>
                  @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->RoleName }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" />
              </div>
            </div>
            {{-- <button class="btn btn-sm btn-primary my-2">Add</button> --}}
            <hr>
            <div class="col-12">
              <h5>{{ __('Bank Relationship Manager Details')}}</h5>
            </div>
            <div class="row">
              <div class="col-12 row" id="program-relationship-managers">
                @if ($program && $program->bankUserDetails)
                  @foreach ($program->bankUserDetails as $key => $bank_user)
                  <div class="col-sm-6">
                    <label class="form-label" for="bank-user-email">{{ __('Bank Email')}}</label>
                    <select name="bank_user_emails[{{ $key }}]" id="bank-user-email-{{$key}}" class="form-control">
                      <option value="">{{ __('Select User')}}</option>
                      @foreach ($bank_users as $user)
                        <option value="{{ $user->email }}" data-name="{{ $user->name }}" data-phone-number="{{ $user->phone_number }}" @if($bank_user->email == $user->email) selected @endif>{{ $user->email }}</option>
                      @endforeach
                    </select>
                  </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="bank-user-name">{{ __('Bank User Name')}}</label>
                      <input type="text" id="bank-user-name-{{ $key }}" class="form-control" name="bank_user_names[{{ $key }}]" value="{{ $bank_user->name }}" readonly />
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="bank-user-phone-number">{{ __('Bank User Mobile No')}}.</label>
                      <input type="text" id="bank-user-phone-number-{{ $key }}" class="form-control" name="bank_user_phone_numbers[{{ $key }}]" value="{{ $bank_user->phone_number }}" readonly />
                    </div>
                    <div class="col-6"></div>
                  @endforeach
                @endif
              </div>
            </div>
            <button class="btn btn-sm btn-primary my-2" type="button" id="add-relationship-manager">{{ __('Add Relationship Manager')}}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between mt-2">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-next" type="button" id="submit-four">{{ __('Next')}} <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Bank Details -->
          <div id="bank-details" class="content">
            <div id="bank-accounts" class="row"></div>
            {{-- <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="name-as-per-bank">
                  {{ __('Name as per Bank') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[]" required />
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
                  <option value="">{{ __('Select Bank') }}</option>
                  @foreach ($banks as $bank_list)
                    <option value="{{ $bank_list->name }}" data-swiftcode="{{ $bank_list->swift_code }}">{{ $bank_list->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="bank-branch">
                  {{ __('Branch') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="bank-branch" class="form-control" name="branches[]" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="swift-code">
                  {{ __('SWIFT Code') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="swift-code-0" class="form-control" name="swift_codes[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="account-type">
                  {{ __('Account Type') }}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="account-type" class="form-control" name="account_types[]" />
              </div>
            </div> --}}
            <button class="btn btn-sm btn-primary my-2" id="add-bank-details" type="button">{{ __('Add new bank details') }}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-submit">{{ __('Add Program')}}</button>
              </div>
            </div>
          </div>
          <!-- Saved Drafts -->
          <div id="drafts" class="content">
            <div class="content-header mb-3">
              <h6 class="mb-0">{{ __('Drafts') }}</h6>
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
