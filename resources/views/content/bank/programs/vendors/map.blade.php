@extends('layouts/layoutMaster')

@section('title', 'Map Vendor To Program')

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
    background-color:#f0f0f0
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
<script src="{{asset('assets/js/add-vendor-to-program.js')}}"></script>
<script src="{{asset('assets/js/form-wizard-validation.js')}}"></script>
<script>
  $(document).ready(function() {
    $('#sanctioned_limit').val(Number($('#sanctioned_limit').val()).toLocaleString())
    $('#drawing_power').val(Number($('#drawing_power').val()).toLocaleString())
  })

  let max_limit = {!! json_encode($max_limit) !!}
  let sanctioned_limit = max_limit
  $('#sanctioned_limit').on('input', function () {
    if (Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')) <= max_limit) {
      $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    } else {
      $(this).val(Number(max_limit).toLocaleString())
    }
    sanctioned_limit = Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', ''))
    let drawing_power = $('#drawing_power').val()
    if (drawing_power > Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', ''))) {
      $('#drawing_power').val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    }
    $('#drawing_power').attr('max', Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')))
  })

  $('#drawing_power').on('input', function () {
    if (Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')) <= sanctioned_limit) {
      $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    } else {
      $(this).val(Number(sanctioned_limit).toLocaleString())
    }
  })

  function updateResetFrequency() {
    let days = $('#reset-frequency').find(":selected").data('days');

    if (!days) {
      $('#reset-frequency-days').removeAttr('disabled');
    } else {
      $('#reset-frequency-days').attr('disabled', true);
      $('#reset-frequency-days').val(days);
    }
  }

  let program = {!! json_encode($program) !!}

  let fees_count = 1
  let fees = $('#program-fees')

  $('#flexSwitchCheckChecked').click(function () {
    if ($(this).is(':checked')) {
      $('#benchmark-title').attr('disabled', true);
      $('#benchmark-title').val(program.discount_details[0].benchmark_title)
      $('#current-benchmark-rate').val(program.discount_details[0].benchmark_rate)

      $('#business-strategy-spread').val(program.discount_details[0].business_strategy_spread)
      $('#business-strategy-spread').attr('readonly', 'readonly');

      $('#credit-spread').val(program.discount_details[0].credit_spread)
      $('#credit-spread').attr('readonly', 'readonly');

      // $('#total-spread').val(program.discount_details[0].total_spread)
      // $('#total-spread').attr('readonly', 'readonly');

      // $('#total-roi').val(program.discount_details[0].total_roi)
      // $('#total-roi').attr('readonly', 'readonly');

      $('#anchor-discount-bearing').val(program.discount_details[0].anchor_discount_bearing)
      $('#anchor-discount-bearing').attr('readonly', 'readonly');

      $('#vendor-discount-bearing').val(program.discount_details[0].vendor_discount_bearing)
      $('#vendor-discount-bearing').attr('readonly', 'readonly');

      $('#penal-discount-on-principle').val(program.discount_details[0].penal_discount_on_principle)
      $('#penal-discount-on-principle').attr('readonly', 'readonly');

      $('#grace-period').val(program.discount_details[0].grace_period)
      $('#grace-period').attr('readonly', 'readonly');

      $('#grace-period-discount').val(program.discount_details[0].grace_period_discount)
      $('#grace-period-discount').attr('readonly', 'readonly');

      changeDiscountRates()

      fees.text('')
      program.fees.forEach((program_fee, index) => {
        let html = '<div class="col-sm-4" id="fee-name-'+index+'">'
            html += '<label class="form-label" for="fee-name">Fee Name</label>'
            html += '<input type="text" id="fee-name-'+index+'" class="form-control" name="fee_names['+index+']" value="'+program_fee.fee_name+'" readonly />'
            html += '</div>'
            html += '<div class="col-sm-4" id="fee-type-'+index+'">'
            html += '<label class="form-label" for="fee-type-'+index+'">Type</label>'
            html += '<select class="form-select" id="fee-type-'+index+'" name="fee_types['+index+']" onchange="changeFeeType('+index+')">'
            if (program_fee.type == 'percentage') {
              html += '<option value="percentage" selected>Percentage</option>'
            } else {
              html += '<option value="percentage">Percentage</option>'
            }
            if (program_fee.type == 'amount') {
              html += '<option value="amount" selected>Amount</option>'
            } else {
              html += '<option value="amount">Amount</option>'
            }
            if (program_fee.type == 'per amount') {
              html += '<option value="per amount" selected>Per Amount</option>'
            } else {
              html += '<option value="per amount">Per Amount</option>'
            }
            html += '</select>'
            html += '</div>'
            if (program_fee.type == 'per amount') {
              html += '<div class="col-sm-4" id="fee-per-amount-value-'+index+'">'
              html += '<label class="form-label" for="value">Amount</label>'
              html += '<input type="number" id="fee-per-amount-value-input-'+index+'" step=".01" class="form-control" name="fee_per_amount['+index+']" value="'+program_fee.per_amount+'" readonly />'
              html += '</div>'
            } else {
              html += '<div class="col-sm-4 d-none" id="fee-per-amount-value-'+index+'">'
              html += '<label class="form-label" for="value">Amount</label>'
              html += '<input type="number" id="fee-per-amount-value-input-'+index+'" step=".000001" class="form-control" name="fee_per_amount['+index+']" readonly />'
              html += '</div>'
            }
            html += '<div class="col-sm-4" id="fee-values-'+index+'">'
            html += '<label class="form-label" for="value">Value</label>'
            html += '<input type="number" step=".000001" id="fee-value-'+index+'" class="form-control" name="fee_values['+index+']" value="'+program_fee.value+'" readonly />'
            html += '</div>'
            html += '<div class="col-sm-4" id="fee-anchor-bearing-'+index+'">'
            html += '<label class="form-label" for="anchor-fee-bearing">Anchor Bearing (%)</label>'
            html += '<input type="number" id="anchor-fee-bearing-'+index+'" class="form-control" min="0" max="100" step=".000001" value="'+program_fee.anchor_bearing_discount+'" name="fee_anchor_bearing_discount['+index+']" oninput="updateFeeBearing('+index+')" readonly />'
            html += '</div>'
            html += '<div class="col-sm-4" id="fee-vendor-bearing-'+index+'">'
            html += '<label class="form-label" for="vendor-fee-bearing">Vendor Bearing (%)</label>'
            html += '<input type="number" id="vendor-fee-bearing-'+index+'" class="form-control" step=".000001" value="'+program_fee.vendor_bearing_discount+'" name="fee_vendor_bearing_discount['+index+']" readonly />'
            html += '</div>'
            html += '<div class="col-sm-4" id="fee-charge-types-'+fees_count+'">'
            html += '<label class="form-label" for="value">Charge</label>'
            html += '<select class="form-select" id="fee-charge-types-'+fees_count+'" name="charge_types['+fees_count+']">'
            if (program_fee.charge_type === 'fixed') {
              html += '<option value="fixed" selected>Fixed</option>'
            } else {
              html += '<option value="fixed">Fixed</option>'
            }
            if (program_fee.charge_type === 'daily') {
              html += '<option value="daily" title="Daily" selected>PD</option>'
            } else {
              html += '<option value="daily" title="Daily">Per Day</option>'
            }
            html += '</select>'
            html += '</div>'
            html += '<div class="col-sm-4" id="fee-account-numbers-'+fees_count+'">'
            html += '<label class="form-label" for="value">Credit To</label>'
            html += '<select class="form-select" id="fee-account-numbers-'+fees_count+'" name="fee_account_numbers['+fees_count+']">'
            html += '<option value="">Select Account</option>'
            @foreach ($bank_payment_accounts as $key => $bank_payment_account)
              if (program_fee.account_number === {!! json_encode($bank_payment_account->account_number) !!}) {
                html += '<option value="'+{!! json_encode($bank_payment_account->account_number) !!}+'" selected>'+{!! json_encode($bank_payment_account->account_number) !!}+' ('+ {!! json_encode($bank_payment_account->account_name) !!} +')</option>'
              } else {
                html += '<option value="'+{!! json_encode($bank_payment_account->account_number) !!}+'">'+{!! json_encode($bank_payment_account->account_number) !!}+' ('+ {!! json_encode($bank_payment_account->account_name) !!} +')</option>'
              }
            @endforeach
            html += '</select>'
            html += '</div>'
            html += '<div class="col-sm-4" id="fee-taxes-'+index+'">'
            html += '<label class="form-label" for="taxes">Taxes</label>'
            html += '<select class="form-select" id="taxes-'+index+'" name="taxes['+index+']" readonly>'
            html += '<option value="">Select</option>'
            @foreach ($taxes as $key => $tax)
              if (program_fee.taxes == {!! json_encode($tax) !!}) {
                html += '<option value="'+{!! json_encode($tax) !!}+'" selected>'+{!! json_encode($key) !!}+' ('+{!! json_encode($tax) !!}+'%)</option>'
              } else {
                html += '<option value="'+{!! json_encode($tax) !!}+'">'+{!! json_encode($key) !!}+' ('+{!! json_encode($tax) !!}+'%)</option>'
              }
            @endforeach
            html += '</select>'
            html += '</div>'
            html += '<div class="col-12 mb-2 mt-2" id="fee-delete-'+fees_count+'">'
            html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeFee('+fees_count+')"></i>'
            html += '</div>'

        $(html).appendTo(fees);
        fees_count += 1;
      });
    } else {
      $('#benchmark-title').attr('disabled', false);
      $('#business-strategy-spread').removeAttr('readonly');
      $('#credit-spread').removeAttr('readonly');
      $('#anchor-discount-bearing').removeAttr('readonly');
      $('#vendor-discount-bearing').removeAttr('readonly');
      $('#penal-discount-on-principle').removeAttr('readonly');
      $('#grace-period').removeAttr('readonly');
      $('#grace-period-discount').removeAttr('readonly');

      program.fees.forEach((program_fee, index) => {
        $('#fee-name-'+index).removeAttr('readonly', 'readonly');
        $('#fee-value-'+index).removeAttr('readonly', 'readonly');
        $('#fee-per-amount-value-input-'+index).removeAttr('readonly', 'readonly');
        $('#anchor-fee-bearing-'+index).removeAttr('readonly', 'readonly');
      })
    }
  })

  changeDiscountRates()

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

    if (Number(business_strategy_spread.val()) >= 0 && Number(credit_spread.val()) >= 0) {
      total_spread.val(Number(business_strategy_spread.val()) + Number(credit_spread.val()));
    }

    if (Number(total_spread.val()) >= 0 && Number(current_benchmark_rate.val()) >= 0) {
      total_roi.val(Number(total_spread.val()) + Number(current_benchmark_rate.val()))
    }

    if (Number(total_roi.val()) >= 0 && Number(anchor_discount_bearing.val()) >= 0) {
      vendor_discount_bearing.val(Number(total_roi.val()) - Number(anchor_discount_bearing.val()))
    }

    daily_discount_charge.val(Number(total_roi.val() / 365).toFixed(2))
  }

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
        html += '<input type="number" class="form-control" name="fee_per_amount['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-values-'+fees_count+'">'
        html += '<label class="form-label" for="value">Value</label>'
        html += '<input type="number" step=".000001" id="fee-value-'+fees_count+'" class="form-control" name="fee_values['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-anchor-bearing-'+fees_count+'">'
        html += '<label class="form-label" for="anchor-fee-bearing">Anchor Bearing (%)</label>'
        html += '<input type="number" step=".000001" id="anchor-fee-bearing-'+fees_count+'" class="form-control" min="0" max="100" name="fee_anchor_bearing_discount['+fees_count+']" oninput="updateFeeBearing('+fees_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-vendor-bearing-'+fees_count+'">'
        html += '<label class="form-label" for="vendor-fee-bearing">Vendor Bearing (%)</label>'
        html += '<input type="number" step=".000001" id="vendor-fee-bearing-'+fees_count+'" class="form-control" name="fee_vendor_bearing_discount['+fees_count+']" readonly />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-charge-types-'+fees_count+'">'
        html += '<label class="form-label" for="value">Charge</label>'
        html += '<select class="form-select" id="fee-charge-types-'+fees_count+'" name="charge_types['+fees_count+']">'
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
        html += '<select class="form-select" id="taxes-'+fees_count+'" name="taxes['+fees_count+']">'
        html += '<option value="">Select</option>'
        @foreach ($taxes as $key => $tax)
          html += '<option value="'+{!! json_encode($tax) !!}+'">'+{!! json_encode($key) !!}+' ('+{!! json_encode($tax) !!}+'%)</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-12 mb-2 mt-2" id="fee-delete-'+fees_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeFee('+fees_count+')"></i>'
        html += '</div>'

      $(html).appendTo(fees);
      fees_count += 1;
  })

  function removeFee(index) {
    $('div').remove('#fee-name-'+index+', #fee-type-'+index+', #fee-values-'+index+', #fee-anchor-bearing-'+index+', #fee-vendor-bearing-'+index+', #fee-charge-types-'+index+', #fee-account-numbers-'+index+', #fee-taxes-'+index+', #fee-delete-'+index)
    fees_count -= 1;
  }

  function updateFeeBearing(index) {
    let anchor_bearing = $('#anchor-fee-bearing-'+index).val()

    let vendor_bearing = $('#vendor-fee-bearing-'+index).val(100 - anchor_bearing)
  }

  function changeFeeType(index) {
    let type = $('#fee-type-'+index).find(':selected').val()

    if(type == 'per amount') {
      $('#fee-per-amount-value-'+index).removeClass('d-none')
    } else {
      $('#fee-per-amount-value-'+index).addClass('d-none')
    }
  }

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
  $('#limit_approval_date').attr("min", min_day);
  $('#limit_expiry_date').attr("min", min_day);
  $('#limit_review_date').attr("min", min_day);

  let bank_accounts = 1

  function deleteItem(index) {
    $('div').remove('#name-as-per-bank-section-'+index+', #account-number-section-'+index+', #bank-name-section-'+index+', #branch-section-'+index+', #swift-code-section-'+index+', #account-type-section-'+index+', #delete-item-div-'+index);
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

      $(html).appendTo($('#bank-accounts'));
      bank_accounts += 1;
  })

  // Get Bank Account Details
  let bank = {!! json_encode($bank) !!}
  $('#vendor').on('change', function() {
    let html = ''
    $('#bank-accounts').text('');
    let id = $(this).val();
    $.get({
      "url": '/'+bank.url+'/programs/companies/'+id+'/details',
      "dataType": "json",
      "success": function(data) {
        $('#gst-no').val(data.company.kra_pin)
        if (data.company.bank_details.length > 0) {
          bank_accounts = data.company.bank_details.length
          data.company.bank_details.forEach((bank_details, index) => {
            html += '<div class="col-sm-6" id="name-as-per-bank-section-'+index+'">'
            html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
            html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks['+index+']" value="'+bank_details.name_as_per_bank+'" />'
            html += '</div>'
            html += '<div class="col-sm-6" id="account-number-section-'+index+'">'
            html += '<label class="form-label" for="account-number">Account Number</label>'
            html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+index+']" value="'+bank_details.account_number+'" />'
            html += '</div>'
            html += '<div class="col-sm-6" id="bank-name-section-'+index+'">'
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
            html += '<div class="col-sm-6" id="branch-section-'+index+'">'
            html += '<label class="form-label" for="bank-branch">Branch</label>'
            html += '<input type="text" id="bank-branch" class="form-control" name="branches['+index+']" value="'+bank_details.branch+'" />'
            html += '</div>'
            html += '<div class="col-sm-6" id="swift-code-section-'+index+'">'
            html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
            html += '<input type="text" id="swift-code-'+index+'" class="form-control" name="swift_codes['+index+']" value="'+bank_details.swift_code+'" />'
            html += '</div>'
            html += '<div class="col-sm-6" id="account-type-section-'+index+'">'
            html += '<label class="form-label" for="account-type">Account Type</label>'
            html += '<input type="text" id="account-type" class="form-control" name="account_types['+index+']" value="'+bank_details.account_type+'" />'
            html += '</div>'
            if (index != 0) {
              html += '<div class="col-sm-12" id="delete-item-div-'+index+'">'
              html += '<i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem('+index+')"></i>'
              html += '</div>'
            }

            $(html).appendTo($('#bank-accounts'));
            bank_accounts += 1;
          })
        } else {
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
          html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[]" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="account-number">Account Number</label>'
          html += '<input type="text" id="account-number" class="form-control" name="account_numbers[]" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="bank-name">Bank Name</label>'
          html += '<select class="form-select" id="bank-name-0" name="bank_names[]" onchange="getSwiftCode(0)">'
          @foreach ($banks as $buyer_bank)
            html += '<option value="'+{!! json_encode($buyer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($buyer_bank->swift_code) !!}+'">'+{!! json_encode($buyer_bank->name) !!}+'</option>'
          @endforeach
          html += '</select>'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="bank-branch">Branch</label>'
          html += '<input type="text" id="bank-branch" class="form-control" name="branches[]" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
          html += '<input type="text" id="swift-code-0" class="form-control" name="swift_codes[]" />'
          html += '</div>'
          html += '<div class="col-sm-6">'
          html += '<label class="form-label" for="account-type">Account Type</label>'
          html += '<input type="text" id="account-type" class="form-control" name="account_types[]" />'
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

  let old_vendor_id = {!! json_encode(old('vendor_id')) !!}

  if (old_vendor_id) {
    $('#bank-accounts').text('');
    $.get({
      "url": '/'+bank.url+'/programs/companies/'+old_vendor_id+'/details',
      "dataType": "json",
      "success": function(data) {
        $('#gst-no').val(data.company.kra_pin)
        if (data.company.bank_details.length > 0) {
          bank_accounts = data.company.bank_details.length
          data.company.bank_details.forEach((bank_details, index) => {
            let html = '<div class="col-sm-6" id="name-as-per-bank-section-'+index+'">'
                html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
                html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks['+index+']" value="'+bank_details.name_as_per_bank+'" />'
                html += '</div>'
                html += '<div class="col-sm-6" id="account-number-section-'+index+'">'
                html += '<label class="form-label" for="account-number">Account Number</label>'
                html += '<input type="text" id="account-number" class="form-control" name="account_numbers['+index+']" value="'+bank_details.account_number+'" />'
                html += '</div>'
                html += '<div class="col-sm-6" id="bank-name-section-'+index+'">'
                html += '<label class="form-label" for="bank-name">Bank Name</label>'
                html += '<select class="form-select" id="bank-name" name="bank_names['+index+']" onchange="getSwiftCode('+index+')">'
                @foreach ($banks as $dealer_bank)
                  if (bank_details.bank_name == {!! json_encode($dealer_bank->name) !!}) {
                    html += '<option value="'+{!! json_encode($dealer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($dealer_bank->swift_code) !!}+'" selected>'+{!! json_encode($dealer_bank->name) !!}+'</option>'
                  } else {
                    html += '<option value="'+{!! json_encode($dealer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($dealer_bank->swift_code) !!}+'">'+{!! json_encode($dealer_bank->name) !!}+'</option>'
                  }
                @endforeach
                html += '</select>'
                html += '</div>'
                html += '<div class="col-sm-6" id="branch-section-'+index+'">'
                html += '<label class="form-label" for="bank-branch">Branch</label>'
                html += '<input type="text" id="bank-branch" class="form-control" name="branches['+index+']" value="'+bank_details.branch+'" />'
                html += '</div>'
                html += '<div class="col-sm-6" id="swift-code-section-'+index+'">'
                html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
                html += '<input type="text" id="swift-code-'+index+'" class="form-control" name="swift_codes['+index+']" value="'+bank_details.swift_code+'" />'
                html += '</div>'
                html += '<div class="col-sm-6" id="account-type-section-'+index+'">'
                html += '<label class="form-label" for="account-type">Account Type</label>'
                html += '<input type="text" id="account-type" class="form-control" name="account_types['+index+']" value="'+bank_details.account_type+'" />'
                html += '</div>'
                if (index != 0) {
                  html += '<div class="col-sm-12" id="delete-item-div-'+index+'">'
                  html += '<i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem('+index+')"></i>'
                  html += '</div>'
                }

            $(html).appendTo($('#bank-accounts'));
            bank_accounts += 1;
          })
        } else {
          let html = '<div class="col-sm-6">'
                html += '<label class="form-label" for="name-as-per-bank">Account Name</label>'
                html += '<input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[]" />'
                html += '</div>'
                html += '<div class="col-sm-6">'
                html += '<label class="form-label" for="account-number">Account Number</label>'
                html += '<input type="text" id="account-number" class="form-control" name="account_numbers[]" />'
                html += '</div>'
                html += '<div class="col-sm-6">'
                html += '<label class="form-label" for="bank-name">Bank Name</label>'
                html += '<select class="form-select" id="bank-name" name="bank_names[]" onchange="getSwiftCode(0)">'
                @foreach ($banks as $buyer_bank)
                  html += '<option value="'+{!! json_encode($buyer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($buyer_bank->swift_code) !!}+'">'+{!! json_encode($buyer_bank->name) !!}+'</option>'
                @endforeach
                html += '</select>'
                html += '</div>'
                html += '<div class="col-sm-6">'
                html += '<label class="form-label" for="bank-branch">Branch</label>'
                html += '<input type="text" id="bank-branch" class="form-control" name="branches[]" />'
                html += '</div>'
                html += '<div class="col-sm-6">'
                html += '<label class="form-label" for="swift-code">SWIFT Code</label>'
                html += '<input type="text" id="swift-code-0" class="form-control" name="swift_codes[]" />'
                html += '</div>'
                html += '<div class="col-sm-6">'
                html += '<label class="form-label" for="account-type">Account Type</label>'
                html += '<input type="text" id="account-type" class="form-control" name="account_types[]" />'
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
  }

  function getSwiftCode(index) {
    $('#swift-code-'+index).val($('#bank-name-'+index).find(':selected').data('swiftcode'))
  }

  $('#eligibility').on('input', function() {
    let eligibility = $(this).val()
    $('#invoice_margin').val(100 - eligibility)
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2 d-flex justify-content-between">
  <span class="fw-light">{{ __('Map Vendor to Program')}} - <span class="text-primary text-decoration-underline">{{ $program->name }}</span></span>
</h4>
<div class="">
  @if($errors->any())
    <div class="p-2 bg-label-danger ml-2 card h-fit w-100" style="height: fit-content">
      {{-- {!! implode('', $errors->first('<p class="mx-2">:message</p>')) !!} --}}
      <p class="text-danger">{{ $errors->first() }} @if($errors->count() > 1) {!! __('+ ') !!} {{ $errors->count() - 1 }} {!! __(' more errors') !!} @endif</p>
    </div>
  @endif
</div>
<!-- Default -->
<div class="row">
  <!-- Vertical Wizard -->
  <div class="col-12 mb-4">
    <div class="bs-stepper wizard-vertical vertical mt-2" id="vendor-details-wizard">
      <div class="bs-stepper-header">
        <div class="step" data-target="#vendor-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-users"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Vendor Details')}}</span>
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
        <form id="vendor-details-form" method="POST" action="{{ route('programs.vendors.map.store', ['bank' => $bank, 'program' => $program]) }}">
          @csrf
          <!-- Company Details -->
          <div id="vendor-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="vendor">{{ __('Vendor')}} <span class="text-danger">*</span></label>
                <select class="form-select select2" id="vendor" name="vendor_id" required>
                  <option value="">{{ __('Select')}}</option>
                  @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @if(old('vendor_id') == $company->id) selected @endif>{{ $company->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('vendor_id')" />
              </div>
              <div class="col-sm-6">
                <label for="payment-od-account">{{ __('Payment / OD Account')}} <span class="text-danger">*</span></label>
                <input type="text" id="payment-od-account" class="form-control" name="payment_account_number" value="{{ old('payment_account_number') }}" autocomplete="off" required />
                <x-input-error :messages="$errors->get('payment_account_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="eligibility">{{ __('Eligibility')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="eligibility" class="form-control" name="eligibility" min="0.01" step=".01" max="100" value="{{ old('eligibility', $program->eligibility) }}" required />
                <x-input-error :messages="$errors->get('eligibility')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice_margin">{{ __('Invoice Margin')}} (%)</label>
                <input type="number" id="invoice_margin" class="form-control" name="invoice_margin" disabled value="{{ 100 - $program->eligibility }}" />
                <x-input-error :messages="$errors->get('invoice_margin')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="sanctioned_limit">{{ __('Sanctioned Limit')}} <span class="text-danger">*</span></label>
                <input type="text" id="sanctioned_limit" class="form-control" name="sanctioned_limit" autocomplete="off" max="{{ $sanctioned_limit }}" required />
                <x-input-error :messages="$errors->get('sanctioned_limit')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="drawing_power">{{ __('Drawing Power')}} <span class="text-danger">*</span></label>
                <input type="text" id="drawing_power" class="form-control" name="drawing_power" max="{{ $sanctioned_limit }}" required />
                <x-input-error :messages="$errors->get('drawing_power')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit_approved_date">{{ __('Limit Approval Date')}} <span class="text-danger">*</span></label>
                <input class="form-control" type="date" id="limit_approval_date" name="limit_approved_date" value="{{ old('limit_approved_date', Carbon\Carbon::parse($program->limit_approved_date)->format('Y-m-d')) }}" required />
                <x-input-error :messages="$errors->get('limit_approved_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit-expiry-date">{{ __('Limit Expiry Date')}} <span class="text-danger">*</span></label>
                <input class="form-control" type="date" id="limit_expiry_date" name="limit_expiry_date" value="{{ old('limit_expiry_date', Carbon\Carbon::parse($program->limit_expiry_date)->format('Y-m-d')) }}" />
                <x-input-error :messages="$errors->get('limit_expiry_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit_review_date">{{ __('Limit Review Date')}}</label>
                <input class="form-control" type="date" id="limit_review_date" name="limit_review_date" value="{{ old('limit_review_date') }}" />
                <x-input-error :messages="$errors->get('limit_review_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="request-autofinance">{{ __('Request Auto Finance')}}</label>
                <select class="form-select" id="request-autofinance" name="request_auto_finance">
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->request_auto_finance) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(!$program->request_auto_finance) selected @endif>{{ __('No')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="auto-approve-finance">{{ __('Auto Approve Finance')}}</label>
                <select class="form-select" id="auto-approve-finance" name="auto_approve_finance">
                  <option value="">Select</option>
                  <option value="1" @if($program->request_auto_finance) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(!$program->request_auto_finance) selected @endif>{{ __('No')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="schema-code">{{ __('Scheme Code')}}</label>
                <input type="text" id="schema-code" class="form-control" name="schema_code" value="{{ old('schema_code') }}" />
                <x-input-error :messages="$errors->get('schema_code')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="vendor-code">{{ __('Vendor Code')}}</label>
                <input type="text" id="vendor-code" class="form-control" name="vendor_code" value="{{ old('vendor_code') }}" />
                <x-input-error :messages="$errors->get('vendor_code')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="gst-no">{{ __('KRA PIN') }} <span class="text-danger">*</span></label>
                <input type="text" id="gst-no" class="form-control" name="gst_number" value="{{ old('gst_number') }}" required />
                <x-input-error :messages="$errors->get('gst_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="classification">{{ __('Classification')}}</label>
                <select class="form-select" id="classification" name="classification">
                  <option value="">{{ __('Select')}}</option>
                  <option value="secured" @if(old('classification') == 'secured') selected @endif>{{ __('Secured')}}</option>
                  <option value="unsecured" @if(old('classification') == 'unsecured') selected @endif>{{ __('Unsecured')}}</option>
                </select>
              </div>
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" disabled> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <div class="d-flex">
                  <a href="{{ route('programs.vendors.manage', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                  <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Discount Details -->
          <div id="discount-details" class="content">
            <div class="row g-3">
              <div class="col-md-12 d-flex justify-content-end text-nowrap">
                <div class="form-check form-switch mb-2">
                  <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ __('Apply Program Information')}}</label>
                  <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" checked>
                </div>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="benchmark-title">{{ __('Benchmark Title(Maturity)')}} <span class="text-danger">*</span></label>
                <select class="form-select" id="benchmark-title" name="benchmark_title" onchange="changeDiscountRates()">
                  <option value="">{{ __('Select Base Rate')}}</option>
                  @foreach ($benchmark_rates as $key => $benchmark_rate)
                    <option value="{{ $key }}" @if($program->discountDetails?->first()?->benchmark_title == $key) selected @endif data-rate="{{ $benchmark_rate }}">{{ $key }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('benchmark_title')" />
              </div>
              <div class="col-sm-6">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="current-benchmark-rate">{{ __('Current Base Rate')}} <span class="text-danger">*</span></label>
                  <span class="text-primary">{{ __('Set As Per Current Master')}}</span>
                </div>
                <input type="number" readonly id="current-benchmark-rate" class="form-control" min="0" max="100" step=".01" readonly name="benchmark_rate" value="{{ $program->discountDetails?->first()?->benchmark_rate ?? 0 }}" />
                <x-input-error :messages="$errors->get('benchmark_rate')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="business-strategy-spread">{{ __('Business Strategy Spread')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="business-strategy-spread" class="form-control" name="business_strategy_spread" min="0" max="100" step=".01" readonly value="{{ $program->discountDetails?->first()?->business_strategy_spread }}" oninput="changeDiscountRates()" />
                <x-input-error :messages="$errors->get('business_strategy_spread')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="credit-spread">{{ __('Credit Spread')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="credit-spread" class="form-control" name="credit_spread" min="0" max="100" step=".01" readonly value="{{ $program->discountDetails?->first()?->credit_spread ?? 0 }}" required oninput="changeDiscountRates()" />
                <x-input-error :messages="$errors->get('credit_spread')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="total-spread">{{ __('Total Spread')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="total-spread" class="form-control" name="total_spread" min="0" max="100" step=".01" readonly value="{{ $program->discountDetails?->first()?->total_spread ?? 0 }}" required />
                <x-input-error :messages="$errors->get('total_spread')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="total-roi">{{ __('Total ROI') }} (%) <span class="text-danger">*</span></label>
                <input type="number" id="total-roi" class="form-control" name="total_roi" min="0" max="100" step=".01" readonly value="{{ $program->discountDetails?->first()?->total_roi ?? 0 }}" required />
                <x-input-error :messages="$errors->get('total_roi')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="daily-discount-charge">{{ __('Discount Charge') }} (%)</label>
                <input type="number" id="daily-discount-charge" class="form-control" name="discount_charge" min="0" max="100" step=".01" readonly value="{{ round($program->discountDetails?->first()?->total_roi / 365, 2) }}" />
                <x-input-error :messages="$errors->get('total_roi')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="anchor-discount-bearing">{{ __('Anchor Discount Bearing')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="anchor-discount-bearing" class="form-control" min="0" max="100" step=".01" name="anchor_discount_bearing" readonly value="{{ $program->discountDetails?->first()?->anchor_discount_bearing ?? 0 }}" required oninput="changeDiscountRates()" />
                <x-input-error :messages="$errors->get('anchor_bearing_discount')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="vendor-discount-bearing">{{ __('Vendor Discount Bearing')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="vendor-discount-bearing" class="form-control" min="0" max="100" step=".01" name="vendor_discount_bearing" readonly value="{{ $program->discountDetails?->first()?->vendor_discount_bearing ?? 100 }}" required />
                <x-input-error :messages="$errors->get('vendor_bearing_discount')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="penal-discount-on-principle">{{ __('Penal Discount on Principle')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="penal-discount-on-principle" class="form-control" min="0" max="100" step=".01" name="penal_discount_on_principle" readonly value="{{ $program->discountDetails?->first()?->penal_discount_on_principle ?? 0 }}" required />
                <x-input-error :messages="$errors->get('penal_discount_on_principle')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="grace-period">{{ __('Grace Period (Days)')}} <span class="text-danger">*</span></label>
                <input type="number" id="grace-period" class="form-control" name="grace_period" readonly value="{{ $program->discountDetails?->first()?->grace_period ?? 0 }}" required />
                <x-input-error :messages="$errors->get('grace_period')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="grace-period-discount">{{ __('Grace Period Discount')}} <span class="text-danger">*</span></label>
                <input class="form-control" type="number" id="grace-period-discount" name="grace_period_discount" readonly value="{{ $program->discountDetails?->first()?->grace_period_discount ?? 0 }}" required />
                <x-input-error :messages="$errors->get('grace_period_discount')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="maturity-handling-on-holidays">{{ __('Maturity Handling on Holidays')}}</label>
                <select class="form-select" id="maturity-handling-on-holidays" name="maturity_handling_on_holidays">
                  <option value="No Effect" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == 'No Effect') selected @endif>{{ __('No Effect')}}</option>
                  <option value="Prepone to previous working day" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == 'Prepone to previous working day') selected @endif>{{ __('Prepone to previous working day')}}</option>
                  <option value="Postpone to next working day" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == 'Postpone to next working day') selected @endif>{{ __('Postpone to next working day')}}</option>
                </select>
                <x-input-error :messages="$errors->get('maturity_handling_on_holidays')" />
              </div>
              <div class="col-sm-6">
              </div>
              <hr>
              <div class="col-12 row" id="program-fees">
                @foreach ($program->fees as $key => $fee)
                  <div class="col-sm-4">
                    <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                    <input type="text" id="fee-name-{{ $key }}" class="form-control" name="fee_names[{{ $key }}]" value="{{ $fee->fee_name }}" readonly />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="fee-type-{{ $key }}">{{ __('Type')}}</label>
                    <select class="form-select" id="fee-type-{{ $key }}" name="fee_types[{{ $key }}]" onchange="changeFeeType({{ $key }})">
                      <option value="percentage" @if($fee->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                      <option value="amount" @if($fee->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                      <option value="per amount" @if($fee->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4 @if ($fee->type != 'per amount') d-none @endif" id="fee-per-amount-value-{{ $key }}">
                    <label class="form-label" for="value">{{ __('Amount')}}</label>
                    <input type="number" step=".000001" class="form-control" id="fee-per-amount-value-input-{{ $key }}" name="fee_per_amount[{{ $key }}]" value="{{ $fee->per_amount }}" readonly />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="value">{{ __('Value')}}</label>
                    <input type="number" step=".000001" id="fee-value-{{ $key }}" class="form-control" name="fee_values[{{ $key }}]" value="{{ $fee->value }}" readonly />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="ancor-discount-bearing">{{ __('Anchor Bearing')}} (%)</label>
                    <input type="number" id="anchor-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" step=".000001" readonly name="fee_anchor_bearing_discount[{{ $key }}]" value="{{ $fee->anchor_bearing_discount ? $fee->anchor_bearing_discount : 0 }}" oninput="updateFeeBearing({{ $key }})" />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="vendor-discount-bearing">{{ __('Vendor Bearing')}} (%)</label>
                    <input type="text" id="vendor-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" step=".000001" readonly name="fee_vendor_bearing_discount[{{ $key }}]" value="{{ $fee->vendor_bearing_discount ? $fee->vendor_bearing_discount : 0 }}" />
                  </div>
                  <div class="col-sm-4" id="fee-charge-types-{{ $key }}">
                    <label class="form-label" for="value">{{ __('Charge')}}</label>
                    <select class="form-select" id="fee-charge-type-{{ $key }}" name="charge_types[{{ $key }}]">
                      <option value="fixed" @if($fee->charge_type == 'amount') selected @endif>{{ __('Fixed')}}</option>
                      <option value="daily" @if($fee->charge_type == 'daily') selected @endif title="Daily">{{ __('Per Day')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4" id="fee-account-numbers-{{ $key }}">
                    <label class="form-label" for="value">{{ __('Credit To')}}</label>
                    <select class="form-select" id="fee-account-numbers-{{ $key }}" name="fee_account_numbers[{{ $key }}]">
                      <option value="">{{ __('Select Account') }}</option>
                      @foreach ($bank_payment_accounts as $bank_payment_account)
                        <option value="{{ $bank_payment_account->account_number }}" @if($fee->account_number === $bank_payment_account->account_number) selected @endif>{{ $bank_payment_account->account_number }} ({{ $bank_payment_account->account_name }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                    <select class="form-select" id="taxes-{{ $key }}" name="taxes[{{ $key }}]">
                      <option value="">{{ __('Select') }}</option>
                      @foreach ($taxes as $key => $tax)
                        <option value="{{ $tax }}" @if($fee->taxes == $tax) selected @endif>{{ $key }} ({{ $tax }}%)</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-12"></div>
                @endforeach
              </div>
              <div class="col-12">
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
                <div class="d-flex">
                  <button class="btn btn-outline-danger mx-1" type="button" data-bs-toggle="modal" data-bs-target="#confirm-cancel-modal">{{ __('Cancel') }}</button>
                  <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Email Mobile Details -->
          <div id="comm-details" class="content">
            <div class="row g-3">
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label" for="vendor-name">{{ __('New User Name')}}</label>
                  <input type="text" id="vendor-name" class="form-control" name="vendor_user_name" value="{{ old('vendor_user_name') }}" />
                  <x-input-error :messages="$errors->get('vendor_user_name')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="vendor-email">{{ __('New User Email')}}</label>
                  <input type="email" id="vendor-email" class="form-control" name="vendor_user_email" value="{{ old('vendor_user_email') }}" />
                  <x-input-error :messages="$errors->get('vendor_user_email')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="vendor-mobile-no">{{ __('New User Mobile No')}}</label>
                  <div class="row">
                    <div class="col-5">
                      <select name="vendor_user_country_code" id="country-code" class="select2">
                        <option value="">{{ __('Select Country Code')}}</option>
                        @foreach ($countries as $country)
                          <option value="{{ $country->dial_code }}" @if(old('vendor_user_country_code') == $country->dial_code) selected @endif>{{ $country->name }}({{ $country->dial_code }})</option>
                        @endforeach
                      </select>
                      <x-input-error :messages="$errors->get('vendor_user_country_code')" />
                    </div>
                    <div class="col-7">
                      <input type="tel" id="vendor-mobile-no" class="form-control mx-1" name="vendor_user_phone_number" placeholder="Enter Phone Number" value="{{ old('vendor_user_phone_number') }}" maxlength="12" />
                      <x-input-error :messages="$errors->get('vendor_user_phone_number')" />
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="role">{{ __('User\'s Role') }}</label>
                  <select name="vendor_user_role" id="" class="form-select">
                    <option value="">{{ __('Select User\'s Role') }}</option>
                    @foreach ($roles as $role)
                      <option value="{{ $role->id }}" @if(old('vendor_user_role') == $role->id) selected @endif>{{ $role->RoleName }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('vendor_user_role')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="phone-number">{{ __('Receive Notifications on')}}</label>
                  <select name="vendor_user_receive_notifications" id="" class="form-select">
                    <option value="">{{ __('Select Channel')}}</option>
                    @foreach ($notification_channels as $key => $channel)
                      <option value="{{ $key }}" @if(old('vendor_user_receive_notifications') == $key) selected @endif>{{ $channel }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('vendor_user_receive_notifications')" />
                </div>
              </div>
            </div>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <div class="d-flex">
                  <button class="btn btn-outline-danger mx-1" type="button" data-bs-toggle="modal" data-bs-target="#confirm-cancel-modal">{{ __('Cancel') }}</button>
                  <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Bank Details -->
          <div id="bank-details" class="content">
            <small class="text-danger">* {{ __('First Account Entered is the default account')}}</small>
            <div class="row g-3" id="bank-accounts">
              {{-- <div class="col-sm-6">
                <label class="form-label" for="name-as-per-bank">Account Name</label>
                <input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="account-number">Account Number</label>
                <input type="text" id="account-number" class="form-control" name="account_numbers[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="bank-name">Bank Name</label>
                <select class="form-select" id="bank-name" name="bank_names[]">
                  @foreach ($banks as $vendor_bank)
                    <option value="{{ $vendor_bank->name }}">{{ $vendor_bank->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="bank-branch">Branch</label>
                <input type="text" id="bank-branch" class="form-control" name="branches[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="swift-code">SWIFT Code</label>
                <input type="text" id="swift-code" class="form-control" name="swift_codes[]" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="account-type">Account Type</label>
                <input type="text" id="account-type" class="form-control" name="account_types[]" />
              </div>
              <div class="col-12">
                <hr>
              </div> --}}
            </div>
            <button class="btn btn-sm btn-primary my-2" id="add-bank-details" type="button">{{ __('Add new bank details')}}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <div class="d-flex">
                  <button class="btn btn-outline-danger mx-1" type="button" data-bs-toggle="modal" data-bs-target="#confirm-cancel-modal">{{ __('Cancel') }}</button>
                  <button class="btn btn-primary btn-submit" type="submit">{{ __('Submit')}}</button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal fade" id="confirm-cancel-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <h4>{{ __('Are you sure you want to cancel? All Progress will be lost.')}}</h4>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('Continue')}}</button>
                  <a href="{{ route('programs.vendors.manage', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-danger">{{ __('Confirm')}}</a>
                </div>
              </div>
            </div>
          </div>
          <!-- Saved Drafts -->
          <div id="drafts" class="content">
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
