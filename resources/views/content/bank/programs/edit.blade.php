@extends('layouts/layoutMaster')

@section('title', 'Edit Program')

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
  $(document).ready(function () {
    $('#program-limit').val(Number($('#program-limit').val()).toLocaleString())
    $('#limit-per-account').val(Number($('#limit-per-account').val()).toLocaleString())
  })

  $('#program-limit').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })

  $('#limit-per-account').on('input', function () {
    $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
  })

  let program = {!! json_encode($program) !!}
  let bank = {!! json_encode($bank) !!}

  // if (program.program_type.name == 'Vendor Financing') {
  //   function getProgramCodes() {
  //     let codes = $('#product-type').find(':selected').data('codes')
  //     let codeOptions = document.getElementById('product-code')
  //     while (codeOptions.options.length) {
  //       codeOptions.remove(0);
  //     }
  //     if (codes) {
  //       var i;
  //       for (i = 0; i < codes.length; i++) {
  //         var subcounty = new Option(codes[i].name+' - '+codes[i].abbrev, codes[i].id);
  //         codeOptions.options.add(subcounty);
  //       }
  //     }
  //   }
  //   getProgramCodes()
  // }

  function changeProgramType() {
    let program_type = $('#product-type').find(':selected').text();

    if (program_type == 'Vendor Financing') {
      getProgramCodes()
      $('.vendor-financing').removeClass('d-none')
      $('.dealer-financing').addClass('d-none')
      $('.repayment-appropriation').removeClass('d-none')
      $('.collection-account').removeClass('d-none')
      $('#from-day-0').removeAttr('required')
      $('#to-day-0').removeAttr('required')
      $('#dealer-benchmark-title').removeAttr('required')
      $('#dealer-business-strategy-spread').removeAttr('required')
      $('#dealer-total-spread').removeAttr('required')
      $('#dealer-total-roi').removeAttr('required')
      $('#benchmark-title').attr('required', true)
      $('#business-strategy-spread').attr('required', true)
      $('#total-spread').attr('required', true)
      $('#total-roi').attr('required', true)
    }

    if (program_type == 'Dealer Financing') {
      $('.vendor-financing').addClass('d-none')
      $('.dealer-financing').removeClass('d-none')
      $('.repayment-appropriation').addClass('d-none')
      $('#from-day-0').attr('required', true)
      $('#to-day-0').attr('required', true)
      $('#dealer-benchmark-title').attr('required', true)
      $('#dealer-business-strategy-spread').attr('required', true)
      $('#dealer-total-spread').attr('required', true)
      $('#dealer-total-roi').attr('required', true)
      $('#benchmark-title').removeAttr('required')
      $('#business-strategy-spread').removeAttr('required')
      $('#total-spread').removeAttr('required')
      $('#total-roi').removeAttr('required')
    }

    $('.buyer-invoice-approval-required').addClass('d-none')
    $('.due-date-calculated-from').addClass('d-none')
  }

  let anchor_fee_bearing_label = 'Anchor Bearing'
  $(document).ready(function () {
    if (program.program_type.name == 'Vendor Financing') {
      $('.repayment-appropriation').removeClass('d-none')
    } else {
      $('.repayment-appropriation').addClass('d-none')
    }

    if (program.program_code && (program.program_code.name == 'Factoring With Recourse' || program.program_code.name == 'Factoring Without Recourse')) {
      $('.buyer-invoice-approval-required').removeClass('d-none')
      $('#anchor-discount-bearing-label').text('Buyer Discount Bearing')
      anchor_fee_bearing_label = 'Buyer Bearing'
      $('#anchor-fee-bearing-label').text(anchor_fee_bearing_label)
      $('#anchor-discount-bearing-title').attr('data-title', 'Discount Bourne by the Buyer')
    }
  })

  function changeVendorFinancingType() {
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
      $('.due-date-calculated-from').addClass('d-none')
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
      $('.due-date-calculated-from').removeClass('d-none')
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
      $('.due-date-calculated-from').removeClass('d-none')
    }
  }

  $(document).ready(() => {
    changeVendorFinancingType()
  })

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

  function changeDealerDiscountRates(index) {
    // Benchmark rate
    let rate = $('#dealer-benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-dealer-benchmark-rate').val(rate);
    if (rate) {
      current_benchmark_rate.val(rate)
    }

    // Business Strategy Spread
    let business_strategy_spread = $('#business-strategy-spread-'+index);
    // Credit Spread
    let credit_spread = $('#credit-spread-'+index);
    // Total ROI
    let total_roi = $('#total-roi-'+index);
    // Total Spread
    let total_spread = $('#total-spread-'+index);

    let daily_discount_charge = $('#dealer-daily-discount-charge-' + index)

    if (business_strategy_spread.val() != '' && credit_spread.val() != '') {
      let business_spread = float(business_strategy_spread.val()) ? Number(business_strategy_spread.val()).toFixed(2) : Number(business_strategy_spread.val())
      let credit = float(Number(credit_spread.val())) ? Number(credit_spread.val()).toFixed(2) : Number(credit_spread.val())

      total_spread.val(Number(Number(business_spread) + Number(credit)).toFixed(2));

      total_roi.val(Number(Number(total_spread.val()) + Number(current_benchmark_rate.val())).toFixed(2))

      daily_discount_charge.val(Number(total_roi.val() / 365).toFixed(2))
    }
  }

  function float(a) {
    return a - a === 0 && a.toString(32).indexOf('.') !== -1
  }

  function setProgramName() {
    let name = $('#anchor').find(':selected').data('name')

    $('#program-name').val(name)
  }

  let max_limit_per_account = 0

  function updateAccountLimit() {
    $('#program-limit').val()

    max_limit_per_account = Number($('#program-limit').val())

    $('#limit-per-account').attr('max', max_limit_per_account)
  }

  let fees_count = 1

  let dealer_fees_count = 0
  let dealer_fees = $('#dealer-program-fees')
  dealer_fees_count = program.fees.length
  if (program.program_type.name == 'Dealer Financing') {
    $(document.body).on('click', '#add-dealer-item', function (e) {
      e.preventDefault()
      let html = '<div class="col-sm-12" id="dealer-fee-id-section-'+dealer_fees_count+'">'
          html += '<input type="hidden" value="-1" name="dealer_fee_key['+dealer_fees_count+']">'
          html += '</div>'
          html += '<div class="col-sm-4" id="dealer-fee-name-section-'+dealer_fees_count+'">'
          html += '<label class="form-label" for="fee-name">Fee Name</label>'
          html += '<input type="text" id="fee-name" class="form-control" name="dealer_fee_names['+dealer_fees_count+']" />'
          html += '</div>'
          html += '<div class="col-sm-4" id="dealer-fee-type-section-'+dealer_fees_count+'">'
          html += '<label class="form-label" for="fee-type-'+dealer_fees_count+'">Type</label>'
          html += '<select class="form-select" id="fee-type-'+dealer_fees_count+'" name="dealer_fee_types['+dealer_fees_count+']">'
          html += '<option value="percentage">Percentage</option>'
          html += '<option value="amount">Amount</option>'
          html += '<option value="per amount">Per Amount</option>'
          html += '</select>'
          html += '</div>'
          html += '<div class="col-sm-4" id="dealer-fee-value-section-'+dealer_fees_count+'">'
          html += '<label class="form-label" for="value">Value</label>'
          html += '<input type="number" step=".0001" id="dealer-fee-value-'+dealer_fees_count+'" class="form-control" name="dealer_fee_values['+dealer_fees_count+']" oninput="updateDealerBearingFee('+dealer_fees_count+')" />'
          html += '</div>'
          html += '<div class="col-sm-6" id="dealer-fee-bearing-section-'+dealer_fees_count+'">'
          html += '<label class="form-label" for="dealer-fee-bearing">Dealer Bearing (%)</label>'
          html += '<input type="number" step=".0001" id="dealer-fee-bearing-'+dealer_fees_count+'" class="form-control" name="fee_dealer_bearing_discount['+dealer_fees_count+']" readonly />'
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
          html += '<div class="col-sm-6" id="dealer-fee-taxes-section-'+dealer_fees_count+'">'
          html += '<label class="form-label" for="taxes">Taxes</label>'
          html += '<select class="form-select" id="taxes" name="dealer_taxes['+dealer_fees_count+']">'
          @foreach ($taxes as $tax)
            html += '<option value="'+{!! json_encode($tax) !!}+'">'+{!! json_encode($tax) !!}+'</option>'
          @endforeach
          html += '</select>'
          html += '</div>'
          html += '<div class="col-12 mb-2 mt-2" id="dealer-fee-delete-'+dealer_fees_count+'">'
          html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDealerFee('+dealer_fees_count+')"></i>'
          html += '</div>'

        $(html).appendTo(dealer_fees);
        dealer_fees_count += 1;
    })
  }

  if (program.program_type.name == 'Vendor Financing') {
    let fees_count = program.fees.length
    let fees = $('#program-fees')
    $(document.body).on('click', '#add-item', function (e) {
      e.preventDefault()
      let html = '<div class="col-sm-12" id="fee-id-section-'+fees_count+'">'
          html += '<input type="hidden" value="-1" name="fee_key['+fees_count+']">'
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-name-'+fees_count+'">'
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
          html += '<input type="number" step=".00000001" class="form-control" name="fee_per_amount['+fees_count+']" />'
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-values-'+fees_count+'">'
          html += '<label class="form-label" for="value">Value</label>'
          html += '<input type="number" step=".00000001" id="fee-value-'+fees_count+'" class="form-control" name="fee_values['+fees_count+']" />'
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-anchor-bearing-'+fees_count+'">'
          html += '<label class="form-label" for="anchor-fee-bearing">'+anchor_fee_bearing_label+' (%)</label>'
          html += '<input type="number" id="anchor-fee-bearing-'+fees_count+'" class="form-control" min="0" max="100" step=".0001" name="fee_anchor_bearing_discount['+fees_count+']" oninput="updateFeeBearing('+fees_count+')" />'
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-vendor-bearing-'+fees_count+'">'
          html += '<label class="form-label" for="vendor-fee-bearing">Vendor Bearing (%)</label>'
          html += '<input type="number" step=".0001" id="vendor-fee-bearing-'+fees_count+'" class="form-control" name="fee_vendor_bearing_discount['+fees_count+']" readonly />'
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
          @foreach ($taxes as $tax)
            html += '<option value="'+{!! json_encode($tax) !!}+'">'+{!! json_encode($tax) !!}+'</option>'
          @endforeach
          html += '</select>'
          html += '</div>'
          html += '<div class="col-12 mb-2 mt-2" id="fee-delete-'+fees_count+'">'
          html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeFee('+fees_count+')"></i>'
          html += '</div>'

        $(html).appendTo(fees);
        fees_count += 1;
    })
  }

  function removeFee(index, id = 0) {
    // Remove fee from the DOM
    $('div').remove('#fee-values-'+index+', #fee-id-section-'+index+', #fee-name-'+index+', #fee-name-section-'+index+', #fee-type-'+index+', #fee-type-section-'+index+', #fee-value-'+index+', #fee-value-section-'+index+', #fee-anchor-bearing-'+index+', #fee-anchor-bearing-section-'+index+', #fee-vendor-bearing-'+index+', #fee-vendor-bearing-section-'+index+', #fee-charge-types-'+index+', #fee-account-numbers-'+index+', #fee-taxes-'+index+', #fee-taxes-section-'+index+', #fee-delete-'+index);
    fees_count -= 1;
    if (id != 0) {
      // Send Request to backend to delete fee
      $.ajax({
        url: '/'+bank.url+'/programs/'+program.id+'/fee/'+id+'/delete',
        type: 'GET',
        success: function (response) {
          console.log(response)
        },
        error: function (error) {
          // Show error message
          console.log(error)
        }
      })
    }
  }

  function removeDealerFee(index, id = 0) {
    $('div').remove('#dealer-fee-id-section-'+index+', #dealer-fee-name-'+index+', #dealer-fee-name-section-'+index+', #dealer-fee-type-'+index+', #dealer-fee-type-section-'+index+', #dealer-fee-value-'+index+', #dealer-fee-value-section-'+index+', #dealer-fee-bearing-'+index+', #dealer-fee-bearing-section-'+index+', #dealer-fee-charge-types-'+index+', #dealer-fee-account-numbers-'+index+', #dealer-fee-taxes-'+index+', #dealer-fee-taxes-section-'+index+', #dealer-fee-delete-'+index);
    fees_count -= 1;
    if (id != 0) {
      // Send Request to backend to delete fee
      $.ajax({
        url: '/'+bank.url+'/programs/'+program.id+'/fee/'+id+'/delete',
        type: 'GET',
        success: function (response) {
          console.log(response)
        },
        error: function (error) {
          // Show error message
          console.log(error)
        }
      })
    }
  }

  function updateFeeBearing(index) {
    let anchor_bearing = $('#anchor-fee-bearing-'+index).val()

    let vendor_bearing = $('#vendor-fee-bearing-'+index).val(100 - anchor_bearing)
  }

  function updateDealerBearingFee(index) {
    let dealer_bearing = $('#dealer-fee-value-'+index).val()
    $('#dealer-fee-bearing-'+index).val(dealer_bearing)
  }

  let discount_count = program.dealer_discount_rates.length > 0 ? program.dealer_discount_rates.length : 1
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

    if (Number(last_day_val) < Number($('#from-day-'+(Number(discount_count) - 1)).val())) {
      $('#to-day-'+(Number(discount_count) - 1)).addClass('border border-danger')
      setTimeout(() => {
        $('#to-day-'+(Number(discount_count) - 1)).removeClass('border border-danger')
      }, 3000);
      return
    }

    $('#to-day-'+(Number(discount_count) - 1)).attr('readonly', 'readonly')
    $('#discount-delete-'+(Number(discount_count) - 1)).addClass('d-none')

    last_day_val = Number(last_day_val) + 1
    let min_val = last_day_val + 1

    let html = '<div class="col-sm-12" id="id-section-'+discount_count+'">'
        html += '<input type="hidden" value="-1" name="discount_details_key['+discount_count+']">'
        html += '</div>'
        html += '<div class="col-sm-6" id="from-day-section-'+discount_count+'">'
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
        html += '<input type="number" name="to_day['+discount_count+']" id="to-day-'+discount_count+'" class="form-control" min="'+min_val+'" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="credit-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="credit-spread">'
        html += 'Credit Spread (%)'
        html += '<span class="text-danger">*</span>'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="credit-spread-'+discount_count+'" class="form-control" name="dealer_credit_spread['+discount_count+']" min="0" max="100" oninput="changeDealerDiscountRates('+discount_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="business-strategy-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="business-strategy-spread">'
        html += 'Business Strategy Spread (%)'
        html += '<span class="text-danger">*</span>'
        html += '</label>'
        html += '<input type="number" id="business-strategy-spread-'+discount_count+'" class="form-control" min="0" max="100" name="dealer_business_strategy_spread['+discount_count+']" oninput="changeDealerDiscountRates('+discount_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="total-spread">'
        html += 'Total Spread (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="total-spread-'+discount_count+'" readonly class="form-control" min="0" max="100" name="dealer_total_spread['+discount_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-roi-section-'+discount_count+'">'
        html += '<label class="form-label" for="total-roi">'
        html += 'Total ROI (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="total-roi-'+discount_count+'" readonly class="form-control" min="0" max="100" name="dealer_total_roi['+discount_count+']" />'
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
    $('div').remove('#id-section-'+index+', #from-day-section-'+index+', #to-day-section-'+index+', #credit-spread-section-'+index+', #business-strategy-spread-section-'+index+', #total-spread-section-'+index+', #total-roi-section-'+index+', #daily-discount-charge-'+index+', #discount-delete-'+index);
    $('#to-day-'+(Number(index) - 1)).removeAttr('readonly')
    if (index != 1) {
      $('#discount-delete-'+(Number(index) - 1)).removeClass('d-none')
    }
    discount_count -= 1;
  }

  function getPostedDiscount() {
    let roi = $('#total-roi-0').val()
    let posted_discount_spread = $('#discount-posted-discount-spread').val()

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

  let selected_managers = []

  let selected = {!! $program->bankUserDetails !!}

  let bank_users = {!! $bank->users !!}

  selected.forEach(manager => {
    selected_managers.push(manager.email)
  })

  let relationship_managers_count = selected.length

  $('#add-relationship-manager').click(function (e) {
    e.preventDefault();
    selected_managers.forEach(manager => {
      let index = bank_users.findIndex(item => item.email == manager)
      bank_users.splice(index, 1);
    })
    let html = '<div class="col-sm-12" id="bank-user-section-'+relationship_managers_count+'">'
        html +=   '<input type="hidden" value="-1" name="bank_user_key['+relationship_managers_count+']">'
        html += '</div>'
        html += '<div class="col-sm-6">'
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

  function getSwiftCode(index) {
    $('#swift-code-'+index).val($('#bank-name-'+index).find(':selected').data('swiftcode'))
  }

  let bank_accounts = program.bank_details.length

  function deleteItem(index, id = 0) {
    $('div').remove('#bank-id-section-'+index+', #name-as-per-bank-section-'+index+', #account-number-section-'+index+', #bank-name-section-'+index+', #branch-section-'+index+', #swift-code-section-'+index+', #account-type-section-'+index+', #delete-item-div-'+index);
    bank_accounts -= 1;
    if (id != 0) {
      // Send Request to backend to delete fee
      $.ajax({
        url: '/'+bank.url+'/programs/'+program.id+'/bank_details/'+id+'/delete',
        type: 'GET',
        success: function (response) {
          console.log(response)
        },
        error: function (error) {
          // Show error message
          console.log(error)
        }
      })
    }
  }

  $('#add-bank-details').on('click', function () {
    let html = '<div class="col-sm-12" id="bank-id-section-'+bank_accounts+'">'
        html += '<input type="hidden" value="-1" name="bank_details['+bank_accounts+']">'
        html += '</div>'
        html += '<div class="col-sm-6" id="name-as-per-bank-section-'+bank_accounts+'">'
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
</script>
@endsection

@section('content')
<div class="d-flex">
  <h4 class="fw-light mr-4 text-nowrap my-auto">
    {{ __('Edit Program')}}
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
        <form id="program-details-form" method="POST" action="{{ route('programs.update', ['bank' => $bank, 'program' => $program]) }}">
          @csrf
          <!-- Program Details -->
          <div id="program-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="product-type">{{ __('Product Type')}} <span class="text-danger">*</span></label>
                <select class="form-select" id="product-type" name="program_type_id" onchange="changeProgramType()" disabled>
                  <option value="">{{ __('Select')}}</option>
                  @foreach ($program_types as $program_type)
                    <option value="{{ $program_type->id }}" data-codes="{{ $program_type->programCodes }}" @if($program->program_type_id == $program_type->id) selected @endif>{{ $program_type->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('product_type_id')" />
              </div>
              @if ($program->programType == 'Vendor Financing')
                <div class="col-sm-6 vendor-financing">
                  <label class="form-label" for="product-code">{{ __('Product Code')}}</label>
                  <select class="form-select" id="product-code" name="program_code_id" onchange="changeVendorFinancingType()">
                    <option value="">{{ __('Select')}}</option>
                  </select>
                  <x-input-error :messages="$errors->get('program_code_id')" />
                </div>
              @endif
              <div class="col-sm-6">
                <div class="d-flex">
                  <label class="form-label" for="anchor" id="anchor-label">{{ __('Anchor')}}</label>
                  <span class="text-danger mx-1">*</span>
                </div>
                <select class="form-select select2" id="anchor" name="anchor_id" onchange="setProgramName()" disabled>
                  <option value="">{{ __('Select')}}</option>
                  @foreach ($companies as $company)
                    <option value="{{ $company->id }}" data-name="{{ $company->name }}" @if($program->getAnchor()->id == $company->id) selected @endif>{{ $company->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('anchor_id')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="program-name">{{ __('Name')}} <span class="text-danger">*</span></label>
                <input type="text" id="program-name" class="form-control" name="name" value="{{ $program->name }}" required />
                <x-input-error :messages="$errors->get('name')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="program-code">{{ __('Program Code')}} <span class="text-danger">*</span></label>
                <input type="text" id="program-code" class="form-control" name="program_code" value="{{ $program->code }}" required />
                <x-input-error :messages="$errors->get('program_code')" />
              </div>
              @if ($program->programType->name == 'Vendor Financing')
                <div class="col-sm-6 vendor-financing">
                  <label class="form-label" for="eligibility">{{ __('Eligibility')}} (%) <span class="text-danger">*</span></label>
                  <input type="number" id="eligibility" class="form-control" max="100" name="eligibility" value="{{ $program->eligibility }}" />
                  <x-input-error :messages="$errors->get('eligiblity')" />
                </div>
              @endif
              @if ($program->programType->name == 'Dealer Financing')
                <div class="col-sm-6 dealer-financing">
                  <label class="form-label" for="eligibility">{{ __('Eligibility')}} (%) <span class="text-danger">*</span></label>
                  <input type="number" id="eligibility" class="form-control" max="100" readonly name="eligibility" value="100" />
                  <x-input-error :messages="$errors->get('eligiblity')" />
                </div>
              @endif
              <div class="col-sm-6">
                <label class="form-label" for="program-limit">{{ __('Total Program Limit')}} <span class="text-danger">*</span></label>
                <input type="text" id="program-limit" class="form-control" min="1" name="program_limit" value="{{ $program->program_limit }}" required oninput="updateAccountLimit()" autocomplete="off" />
                <x-input-error :messages="$errors->get('program_limit')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="approval-date">{{ __('Program Approval Date')}}</label>
                <input class="form-control" type="date" id="html5-date-input" name="approved_date" value="{{ Carbon\Carbon::parse($program->approved_date)->format('Y-m-d') }}" />
                <x-input-error :messages="$errors->get('approved_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit-expiry-date">{{ __('Limit Expiry Date')}} <span class="text-danger">*</span></label>
                <input class="form-control" type="date" id="html5-date-input" name="limit_expiry_date" value="{{ Carbon\Carbon::parse($program->limit_expiry_date)->format('Y-m-d') }}" />
                <x-input-error :messages="$errors->get('limit_expiry_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit-per-account">{{ __('Maximum Limit Per Account')}} <span class="text-danger">*</span></label>
                <input type="text" id="limit-per-account" class="form-control" name="max_limit_per_account" min="1" value="{{ $program->max_limit_per_account }}" required autocomplete="off" />
                <x-input-error :messages="$errors->get('max_limit_per_account')" />
              </div>
              @if ($program->programType->name == 'Vendor Financing' && $program->programCode->name == 'Vendor Financing Receivable')
                <div class="col-sm-6 collection-account">
                  <label for="collection-account">{{ __('Collection Account')}}</label>
                  <input type="text" id="collection-account" class="form-control" name="collection_account" value="{{ $program->collection_account }}" />
                  <x-input-error :messages="$errors->get('collection_account')" />
                </div>
              @endif
              @if ($program->programType->name == 'Vendor Financing' && ($program->programCode->name == 'Factoring With Recourse' || $program->programCode->name == 'Factoring Without Recourse'))
                <div class="col-sm-6 factoring-payment-account">
                  <label for="factoring-payment-account">{{ __('Factoring Payment Account')}}</label>
                  <input type="text" id="factoring-payment-account" class="form-control" name="factoring_payment_account" value="{{ $program->factoring_payment_account }}" />
                  <x-input-error :messages="$errors->get('factoring_payment_account')" />
                </div>
              @endif
              <div class="col-sm-6">
                <label class="form-label" for="request-autofinance">
                  {{ __('Request Auto Finance')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select class="form-select" id="request-autofinance" name="request_auto_finance" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->request_auto_finance == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->request_auto_finance == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('request_auto_finance')" />
              </div>
              @if ($program->programType->name == 'Vendor Financing')
                <div class="col-sm-6 vendor-financing">
                  <label class="form-label" for="min-financing-days">
                    {{ __('Minimum Financing Days')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs" data-title="Vendors will not be able to request for financing for PI(s) maturing within the specified days from the date of request"></i>
                  </label>
                  <input type="number" id="min-financing-days" class="form-control" min="1" value="{{ $program->min_financing_days }}" name="min_financing_days" />
                  <x-input-error :messages="$errors->get('min_financing_days')" />
                </div>
              @endif
              <div class="col-sm-6 dealer-financing">
                <label class="form-label" for="stale-invoice-period">
                  {{ __('Stale Invoice Period')}}
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="An email is sent to anchor users tagged at program level to stop supply to those dealers whose loans are overdue beyond the number of days set in this field."></i>
                </label>
                <input type="number" id="stale-invoice-period" class="form-control" min="0" value="{{ $program->stale_invoice_period }}" name="stale_invoice_period" />
                <x-input-error :messages="$errors->get('stale_invoice_period')" />
              </div>
              @if ($program->programType->name == 'Dealer Financing')
                <div class="col-sm-6 dealer-financing">
                  <label class="form-label" for="stop-supply">
                    {{ __('Stop Supply (Days)')}} ({{ __('Days') }})
                    <i class="tf-icons ti ti-info-circle ti-xs" title=""></i>
                  </label>
                  <input type="number" id="stop-supply" class="form-control" min="0" value="{{ $program->stop_supply }}" name="stop_supply" />
                  <x-input-error :messages="$errors->get('stop_supply')" />
                </div>
              @endif
              @if ($program->programType->name == 'Dealer Financing')
                <div class="col-sm-6 dealer-financing">
                  <label class="form-label" for="fldg">
                    {{ __('FLDG') }} ({{ __('Days') }})
                    <i class="tf-icons ti ti-info-circle ti-xs" data-title="An Email is sent to anchor users tagged at program level every day for all dealers who have been overdue after a certain no. of days mentioned in this field till the overdue is cleared."></i>
                  </label>
                  <input type="number" id="fldg-days" class="form-control" min="0" value="{{ $program->fldg_days }}" name="fldg_days" />
                  <x-input-error :messages="$errors->get('fldg_days')" />
                </div>
              @endif
              @if ($program->programType->name == 'Vendor Financing')
                <div class="col-sm-6">
                  <label class="form-label" for="max-financing-days">
                    {{ __('Maximum Financing Days')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs" data-title="Vendors will not be able to request for financing for PI(s) maturing beyond the specified days from the date of request"></i>
                  </label>
                  <input type="number" id="max-financing-days" class="form-control" min="1" value="{{ $program->max_financing_days }}" name="max_financing_days" required />
                  <x-input-error :messages="$errors->get('max_financing_days')" />
                </div>
              @endif
              <div class="col-sm-6">
                <label class="form-label" for="auto-debit-anchor">
                  {{ __('Auto Debit Anchor for Financed Invoices')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select class="form-select" id="auto-debit-anchor" name="auto_debit_anchor_financed_invoices" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->auto_debit_anchor_financed_invoices == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->auto_debit_anchor_financed_invoices == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('auto_debit_anchor_financed_invoices')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="auto-debit-anchor-for-non-financed">
                  {{ __('Auto Debit Anchor for Non-Financed Invoices')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="auto-debit-anchor-for-non-financed" name="auto_debit_anchor_non_financed_invoices" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->auto_debit_anchor_non_financed_invoices == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->auto_debit_anchor_non_financed_invoices == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('auto_debit_anchor_non_financed_invoices')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="allow-anchor-to-change-due-date">
                  {{ __('Allow Anchor to change due date')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="allow-anchor-to-change-due-date" name="anchor_can_change_due_date" required>
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->anchor_can_change_due_date == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->anchor_can_change_due_date == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('anchor_can_change_due_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="max-days-for-invoice-date-extension">{{ __('Maximum No. of days for Invoice Due Date Extensions')}}</label>
                <input type="number" id="max-days-for-invoice-date-extension" class="form-control" value="{{ $program->max_days_due_date_extension }}" name="max_days_due_date_extension" />
                <x-input-error :messages="$errors->get('max_days_due_date_extension')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="number-of-days-for-due-date-change">{{ __('No. of Days Limit for changing Invoice Due Date')}}</label>
                <input type="number" id="number-of-days-for-due-date-change" class="form-control" value="{{ $program->days_limit_for_due_date_change }}" name="days_limit_for_due_date_change" />
                <x-input-error :messages="$errors->get('days_limit_for_due_date_change')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="default-payment-terms">
                  {{ __('Default Payment Terms(Days)')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="number" id="default-payment-terms" class="form-control" value="{{ $program->default_payment_terms }}" name="default_payment_terms" />
                <x-input-error :messages="$errors->get('default_payment_terms')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="allow-anchor-to-change-payment-terms">
                  {{ __('Allow Anchor to change Payment Terms')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="allow-anchor-to-change-payment-terms" name="anchor_can_change_payment_term">
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->anchor_can_change_payment_term == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->anchor_can_change_payment_term == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('anchor_can_change_payment_term')" />
              </div>
              <div class="col-sm-6 recourse">
                <label class="form-label" for="recourse">
                  {{ __('Recourse')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select class="form-select" id="recourse" name="recourse">
                  <option value="">{{ __('Select')}}</option>
                  <option value="With Recourse" @if($program->recourse == "With Recourse") selected @endif>{{ __('With Recourse')}}</option>
                  <option value="Without Recourse" @if($program->recourse == "Without Recourse") selected @endif>{{ __('Without Recourse')}}</option>
                </select>
                <x-input-error :messages="$errors->get('recourse')" />
              </div>
              {{-- Repayment Appropriation --}}
              <div class="col-sm-6 repayment-appropriation">
                <label class="form-label" for="repayment-appropriation">
                  {{ __('Repayment Appropriation')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="repayment_appropriation" name="repayment_appropriation">
                  <option value="">{{ __('Select')}}</option>
                  <option value="FIFO" @if(old('repayment_appropriation') == "FIFO" || ($program && $program->repayment_appropriation == 'FIFO')) selected @endif>{{ __('FIFO')}}</option>
                  <option value="Loanwise" @if(old('repayment_appropriation') == "FIFO" || ($program && $program->repayment_appropriation == 'Loanwise')) selected @endif>{{ __('Loanwise')}}</option>
                </select>
                <x-input-error :messages="$errors->get('repayment_appropriation')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="mandatory_invoice_attachment">
                  {{ __('Mandatory for Invoice Attachment')}}
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <select class="form-select" id="mandatory_invoice_attachment" name="mandatory_invoice_attachment">
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($program->mandatory_invoice_attachment == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->mandatory_invoice_attachment == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('mandatory_invoice_attachment')" />
              </div>
              <div class="col-sm-6 due-date-calculated-from">
                <label class="form-label" for="due-date-calculated-from">
                  {{ __('Due Date Calculated From')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="due-date-calculated-from" name="due_date_calculated_from">
                  <option value="">{{ __('Select')}}</option>
                  <option value="Invoice Creation Date" @if($program->due_date_calculated_from == 'Invoice Creation Date') selected @endif>{{ __('Invoice Creation Date')}}</option>
                  <option value="Disbursement Date" @if($program->due_date_calculated_from == 'Disbursement Date') selected @endif>{{ __('Disbursement Date')}}</option>
                </select>
                <x-input-error :messages="$errors->get('due_date_calculated_from')" />
              </div>
              <div class="col-sm-6 d-none buyer-invoice-approval-required">
                <label class="form-label" for="buyer-invoice-approval-required">
                  {{ __('Buyer Invoice Approval Required')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="buyer-invoice-approval-required" name="buyer_invoice_approval_required">
                  <option value="">{{ __('Select') }}</option>
                  <option value="1" @if($program->buyer_invoice_approval_required == 1) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if($program->buyer_invoice_approval_required == 0) selected @endif>{{ __('No')}}</option>
                </select>
                <x-input-error :messages="$errors->get('buyer_invoice_approval_required')" />
              </div>
              <div class="col-sm-6">
                <label for="formFile" class="form-label">{{ __('Company Board Resolution Attachment')}}</label>
                <input class="form-control" type="file" id="formFile" name="board_resolution_attachment">
                <x-input-error :messages="$errors->get('board_resolution_attachment')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="account-status">{{ __('Status')}}</label>
                <select class="form-select" id="account-status" name="account_status">
                  <option value="">{{ __('Select') }}</option>
                  <option value="active" @if($program->account_status == "active") selected @endif>{{ __('Active')}}</option>
                  <option value="suspended" @if($program->account_status == "suspended") selected @endif>{{ __('Suspended')}}</option>
                </select>
                <x-input-error :messages="$errors->get('account_status')" />
              </div>
              <div class="col-12 d-flex justify-content-between mt-2">
                <button class="btn btn-label-secondary btn-prev" readonly> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Discount Details -->
          <div id="discount-details" class="content">
            @if ($program->programType->name == 'Vendor Financing')
              <div class="row g-3 vendor-financing">
                <div class="col-sm-6">
                  <label class="form-label" for="benchmark-title">
                    {{ __('Benchmark Title(Maturity)')}}
                    <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="benchmark-title" name="benchmark_title" onchange="changeDiscountRates()">
                    <option value="">{{ __('Select Base Rate')}}</option>
                    @foreach ($benchmark_rates as $key => $benchmark_rate)
                      <option value="{{ $key }}" data-rate="{{ $benchmark_rate }}" @if($program->discountDetails?->first()?->benchmark_title == $key) selected @endif>{{ $key }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('benchmark_title')" />
                </div>
                <div class="col-sm-6">
                  <div class="d-flex justify-content-between">
                    <label class="form-label" for="current-benchmark-rate">{{ __('Current Base Rate')}}</label>
                    <span class="text-primary">{{ __('Set As Per Current Master')}}</span>
                  </div>
                  <input type="number" readonly id="current-benchmark-rate" class="form-control" readonly name="benchmark_rate" step=".01" value="{{ $program->discountDetails?->first()?->benchmark_rate }}" />
                  <x-input-error :messages="$errors->get('benchmark_rate')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="business-strategy-spread">
                    {{ __('Business Strategy Spread')}} (%)
                    <span class="text-danger">*</span>
                  </label>
                  <input type="number" id="business-strategy-spread" class="form-control" min="0" max="100" step=".001" name="business_strategy_spread" value="{{ $program->discountDetails?->first()?->business_strategy_spread }}" oninput="changeDiscountRates()" />
                  <x-input-error :messages="$errors->get('business_strategy_spread')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="credit-spread">
                    {{ __('Credit Spread')}} (%)
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="credit-spread" class="form-control" name="credit_spread" value="{{ $program->discountDetails?->first()?->credit_spread }}" min="0" max="100" step=".01" oninput="changeDiscountRates()" />
                  <x-input-error :messages="$errors->get('credit_spread')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="total-spread">
                    {{ __('Total Spread')}} (%)
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="total-spread" readonly class="form-control" value="{{ $program->discountDetails?->first()?->total_spread }}" min="0" max="100" step=".1" name="total_spread" />
                  <x-input-error :messages="$errors->get('total_spread')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="total-roi">
                    {{ __('Total ROI')}} (%)
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="total-roi" readonly class="form-control" min="0" max="100" step=".0001" name="total_roi" value="{{ $program->discountDetails?->first()?->total_roi }}" />
                  <x-input-error :messages="$errors->get('total_roi')" />
                </div>
                {{-- Discount Charge --}}
                <div class="col-sm-6">
                  <label class="form-label" for="discount-charge">
                    {{ __('Discount Charge (%)')}}
                    <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                  </label>
                  <input type="number" id="daily-discount-charge" readonly class="form-control" min="0" max="100" step=".0001" name="daily_charge" value="{{ $program ? round($program->discountDetails?->first()?->total_roi / 365, 2) : '' }}" />
                  <x-input-error :messages="$errors->get('daily_charge')" />
                </div>
                {{-- Tax on Discount --}}
                <div class="col-sm-6">
                  <label class="form-label" for="tax-on-discount">
                    {{ __('Tax on Discount')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <select name="tax_on_discount" id="tax-on-discount" class="form-control">
                    <option value="">{{ __('Select')}}</option>
                    @foreach ($taxes as $key => $tax)
                      <option value="{{ $tax }}" @if($program && $program->discountDetails?->first()?->tax_on_discount == $tax) selected @endif>{{ $key }} ({{ $tax }}%)</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('tax_on_discount')" />
                </div>
                {{-- Anchor Bearing Discount --}}
                <div class="col-sm-6">
                  <label class="form-label" for="anchor-discount-bearing">
                    <span id="anchor-discount-bearing-label">
                      {{ __('Anchor Discount Bearing')}}
                    </span>
                    (%)
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs" id="anchor-discount-bearing-title" data-title="Discount Bourne by Anchor"></i>
                  </label>
                  <input type="number" id="anchor-discount-bearing" class="form-control" min="0" max="100" step=".01" name="anchor_discount_bearing" value="{{ $program->discountDetails?->first()?->anchor_discount_bearing }}" oninput="changeDiscountRates()" />
                  <x-input-error :messages="$errors->get('anchor_discount_bearing')" />
                </div>
                {{-- Vendor Bearing Discount --}}
                <div class="col-sm-6">
                  <label class="form-label" for="vendor-discount-bearing">
                    {{ __('Vendor Discount Bearing')}} (%)
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="vendor-discount-bearing" class="form-control" min="0" max="100" step=".01" readonly name="vendor_discount_bearing" value="{{ $program->discountDetails?->first()?->vendor_discount_bearing }}" />
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
                    <option value="Front Ended" @if($program->discountDetails?->first()?->discount_type == "Front Ended") selected @endif>{{ __('Front Ended')}}</option>
                    <option value="Rear Ended" @if($program->discountDetails?->first()?->discount_type == "Rear Ended") selected @endif>{{ __('Rear Ended')}}</option>
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
                    <option value="Front Ended" @if(old('fee_type') == "Front Ended" || ($program && $program->discountDetails?->first()?->fee_type == 'Front Ended')) selected @endif>{{ __('Front Ended')}}</option>
                    <option value="Rear Ended" @if(old('fee_type') == "Rear Ended" || ($program && $program->discountDetails?->first()?->fee_type == 'Rear Ended')) selected @endif>{{ __('Rear Ended')}}</option>
                  </select>
                  <x-input-error :messages="$errors->get('fee_type')" />
                </div>
                {{-- Penal Discount On Principle --}}
                <div class="col-sm-6">
                  <label class="form-label" for="penal-discount-on-principle">
                  {{ __('Penal Discount on Principle')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="penal-discount-on-principle" class="form-control" min="0" max="100" step=".01" name="penal_discount_on_principle" value="{{ $program->discountDetails?->first()?->penal_discount_on_principle }}" />
                  <x-input-error :messages="$errors->get('penal_discount_on_principle')" />
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
                {{-- Grace Period --}}
                <div class="col-sm-6">
                  <label class="form-label" for="grace-period">
                  {{ __('Grace Period (Days)')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="grace-period" class="form-control" min="0" name="grace_period" value="{{ $program->discountDetails?->first()?->grace_period }}" />
                  <x-input-error :messages="$errors->get('grace_period')" />
                </div>
                {{-- Grace Period Discount --}}
                <div class="col-sm-6">
                  <label class="form-label" for="grace-period-discount">
                  {{ __('Grace Period Discount')}}
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input class="form-control" type="number" id="grace-period-discount" min="0" max="100" step=".01" name="grace_period_discount" value="{{ $program->discountDetails?->first()?->grace_period_discount }}" />
                  <x-input-error :messages="$errors->get('grace_period_discount')" />
                </div>
                {{-- Maturity Handling ON Holidays --}}
                <div class="col-sm-6">
                  <label class="form-label" for="maturity-handling-on-holidays">
                  {{ __('Maturity Handling on Holidays')}}
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <select class="form-select" id="maturity-handling-on-holidays" name="maturity_handling_on_holidays">
                    <option value="No Effect" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == "No Effect") selected @endif>{{ __('No Effect')}}</option>
                    <option value="Prepone to previous working day" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == "Prepone to previous working day") selected @endif>{{ __('Prepone to previous working day')}}</option>
                    <option value="Postpone to next working day" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == "Postpone to next working day") selected @endif>{{ __('Postpone to next working day')}}</option>
                  </select>
                  <x-input-error :messages="$errors->get('maturity_handling_on_holidays')" />
                </div>
                <div class="col-sm-6">
                </div>
                <hr>
                <div class="col-12 row" id="program-fees">
                  @foreach ($program->fees as $key => $fee)
                    <div class="col-sm-12" id="fee-id-section-{{ $key }}">
                      <input type="hidden" value="{{ $fee->id }}" name="fee_key[{{ $key }}]">
                    </div>
                    <div class="col-sm-4" id="fee-name-section-{{ $key }}">
                      <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                      <input type="text" id="fee-name" class="form-control" name="fee_names[{{ $key }}]" value="{{ $fee->fee_name }}" />
                    </div>
                    <div class="col-sm-4" id="fee-type-section-{{ $key }}">
                      <label class="form-label" for="fee-type-{{ $key }}">{{ __('Type')}}</label>
                      <select class="form-select" id="fee-type-{{ $key }}" name="fee_types[{{ $key }}]" onchange="changeFeeType({{ $key }})">
                        <option value="percentage" @if($fee->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                        <option value="amount" @if($fee->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                        <option value="per amount" @if($fee->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                      </select>
                    </div>
                    @if ($fee->type == 'per amount')
                      <div class="col-sm-4" id="fee-per-amount-value-section-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Amount')}}</label>
                        <input type="number" step=".000000001" class="form-control" name="fee_per_amount[{{ $key }}]" value="{{ $fee->per_amount }}" />
                      </div>
                    @endif
                    <div class="col-sm-4" id="fee-value-section-{{ $key }}">
                      <label class="form-label" for="value">{{ __('Value')}}</label>
                      <input type="number" step=".00000001" id="fee-value-{{ $key }}" class="form-control" name="fee_values[{{ $key }}]" value="{{ $fee->value }}" />
                    </div>
                    <div class="col-sm-4" id="fee-anchor-bearing-section-{{ $key }}">
                      <label class="form-label" for="anchor-fee-bearing">
                        <span id="anchor-fee-bearing-label">
                          {{ __('Anchor Bearing')}}
                        </span>
                        (%)
                      </label>
                      <input type="number" id="anchor-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" step=".0001" name="fee_anchor_bearing_discount[{{ $key }}]" value="{{ $fee->anchor_bearing_discount }}" oninput="updateFeeBearing({{ $key }})" />
                    </div>
                    <div class="col-sm-4" id="fee-vendor-bearing-section-{{ $key }}">
                      <label class="form-label" for="vendor-fee-bearing">{{ __('Vendor Bearing')}} (%)</label>
                      <input type="number" id="vendor-fee-bearing-{{ $key }}" class="form-control" step=".0001" name="fee_vendor_bearing_discount[{{ $key }}]" value="{{ $fee->vendor_bearing_discount }}" readonly />
                    </div>
                    <div class="col-sm-4" id="fee-charge-types-{{ $key }}">
                      <label class="form-label" for="value">{{ __('Charge')}}</label>
                      <select class="form-select" id="fee-charge-type-{{ $key }}" name="charge_types[{{ $key }}]">
                        <option value="fixed" @if($fee->charge_type == 'fixed') selected @endif>{{ __('Fixed')}}</option>
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
                    <div class="col-sm-4" id="fee-taxes-section-{{ $key }}">
                      <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                      <select class="form-select" id="taxes" name="taxes[{{ $key }}]">
                        <option value="">{{ __('Select Tax')}}</option>
                        @foreach ($taxes as $tax_key => $tax)
                          <option value="{{ $tax }}" @if($fee->taxes == $tax) selected @endif>{{ $tax_key }} ({{ $tax }}%)</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-12 mb-2 mt-2" id="fee-delete-{{ $key }}">
                      <i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeFee({{ $key }}, {{ $fee->id }})"></i>
                    </div>
                  @endforeach
                </div>
                <div class="col-12">
                  <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                    <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                    <span class="mx-2">
                      {{ __('Add Fee')}}
                    </span>
                  </span>
                </div>
                <div class="col-12 d-flex justify-content-between">
                  <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                    <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                  </button>
                  <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            @else
              <div class="row g-3 dealer-financing">
                <div class="col-sm-6">
                  <label class="form-label" for="benchmark-title">
                    {{ __('Benchmark Title(Maturity)')}}
                    <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="dealer-benchmark-title" name="dealer_benchmark_title" onchange="changeDealerBenchmarkRate()">
                    <option value="">{{ __('Select Base Rate')}}</option>
                    @foreach ($benchmark_rates as $key => $benchmark_rate)
                      <option value="{{ $key }}" data-rate="{{ $benchmark_rate }}" @if($program->discountDetails?->first()?->benchmark_title == $key) selected @endif>{{ $key }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('benchmark_title')" />
                </div>
                <div class="col-sm-6">
                  <div class="d-flex justify-content-between">
                    <label class="form-label" for="current-benchmark-rate">{{ __('Current Base Rate')}}</label>
                    <span class="text-primary">{{ __('Set As Per Current Master')}}</span>
                  </div>
                  <input type="number" readonly id="current-dealer-benchmark-rate" class="form-control" name="dealer_benchmark_rate" value="{{ $program->discountDetails?->first()?->benchmark_rate }}" />
                  <x-input-error :messages="$errors->get('benchmark_rate')" />
                </div>
                <div class="col-12 row" id="program-discounts">
                  @if ($program->dealerDiscountRates->count() > 0)
                    @foreach ($program->dealerDiscountRates as $key => $discount_details)
                      <div class="col-sm-12" id="id-section-{{ $key }}">
                        <input type="hidden" value="{{ $discount_details->id }}" name="discount_details_key[{{ $key }}]">
                      </div>
                      @if ($loop->first)
                        {{-- From Day --}}
                        <div class="col-sm-6">
                          <label for="" class="form-label">
                            {{ __('From Day') }}
                            <span class="text-danger">*</span>
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                          </label>
                          <input type="number" name="from_day[]" id="from-day-0" class="form-control" min="1" readonly value="{{ $discount_details->from_day }}" />
                        </div>
                        {{-- To Day --}}
                        <div class="col-sm-6">
                          <label for="" class="form-label">
                            {{ __('To Day') }}
                            <span class="text-danger">*</span>
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                          </label>
                          <input type="number" name="to_day[]" id="to-day-{{ $key }}" class="form-control" min="2" value="{{ $discount_details->to_day }}" />
                        </div>
                        {{-- Credit Spread --}}
                        <div class="col-sm-3">
                          <label class="form-label" for="credit-spread">
                            {{ __('Credit Spread') }} (%)
                            <span class="text-danger">*</span>
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                          </label>
                          <input type="number" id="credit-spread-{{ $key }}" class="form-control" name="dealer_credit_spread[]" min="0" max="100" step=".01" value="{{ $discount_details->credit_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                        </div>
                        {{-- Business Strategy Spread --}}
                        <div class="col-sm-3">
                          <label class="form-label" for="business-strategy-spread">
                            {{ __('Business Strategy Spread') }} (%)
                            <span class="text-danger">*</span>
                          </label>
                          <input type="number" id="business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_business_strategy_spread[]" value="{{ $discount_details->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                        </div>
                        {{-- Total Spread --}}
                        <div class="col-sm-3">
                          <label class="form-label" for="total-spread">
                            {{ __('Total Spread') }} (%)
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                          </label>
                          <input type="number" id="total-spread-{{ $key }}" readonly class="form-control" min="0" max="100" step=".01" name="dealer_total_spread[]" value="{{ $discount_details->total_spread }}" />
                        </div>
                        {{-- Total ROI --}}
                        <div class="col-sm-3">
                          <label class="form-label" for="total-roi">
                            {{ __('Total ROI') }} (%)
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                          </label>
                          <input type="number" id="total-roi-{{ $key }}" readonly class="form-control" min="0" max="100" step=".01" name="dealer_total_roi[]" value="{{ $discount_details->total_roi }}" oninput="getPostedDiscount()" />
                        </div>
                        {{-- Daily Discount Charge --}}
                        <div class="col-sm-3">
                          <label class="form-label" for="discount-charge">
                            {{ __('Discount Charge')}} (%)
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                          </label>
                          <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[{{ $key }}]" value="{{ round($discount_details->total_roi / 365, 2) }}" />
                        </div>
                        <div class="col-sm-9"></div>
                      @else
                        {{-- From Day --}}
                        <div class="col-sm-6" id="from-day-section-{{ $key }}">
                          <label for="" class="form-label">
                            {{ __('From Day') }}
                            <span class="text-danger">*</span>
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                          </label>
                          <input type="number" name="from_day[{{ $key }}]" id="from-day-{{ $key }}" class="form-control" min="1" readonly value="{{ $discount_details->from_day }}" />
                        </div>
                        {{-- To Day --}}
                        <div class="col-sm-6" id="to-day-section-{{ $key }}">
                          <label for="" class="form-label">
                            {{ __("To Day") }}
                            <span class="text-danger">*</span>
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                          </label>
                          <input type="number" name="to_day[{{ $key }}]" id="to-day-{{ $key }}" class="form-control" min="2" value="{{ $discount_details->to_day }}" />
                        </div>
                        {{-- Credit Spread --}}
                        <div class="col-sm-3" id="credit-spread-section-{{ $key }}">
                          <label class="form-label" for="credit-spread">
                            {{ __('Credit Spread') }} (%)
                            <span class="text-danger">*</span>
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                          </label>
                          <input type="number" id="credit-spread-{{ $key }}" class="form-control" name="dealer_credit_spread[{{ $key }}]" min="0" max="100" step=".01" value="{{ $discount_details->credit_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                        </div>
                        {{-- Business Strategy Spread --}}
                        <div class="col-sm-3" id="business-strategy-spread-section-{{ $key }}">
                          <label class="form-label" for="business-strategy-spread">
                            {{ __('Business Strategy Spread') }} (%)
                            <span class="text-danger">*</span>
                          </label>
                          <input type="number" id="business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_business_strategy_spread[{{ $key }}]" value="{{ $discount_details->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                        </div>
                        {{-- Total Spread --}}
                        <div class="col-sm-3" id="total-spread-section-{{ $key }}">
                          <label class="form-label" for="total-spread">
                            {{ __('Total Spread') }} (%)
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                          </label>
                          <input type="number" id="total-spread-{{ $key }}" readonly class="form-control" min="0" max="100" step=".01" name="dealer_total_spread[{{ $key }}]" value="{{ $discount_details->total_spread }}" />
                        </div>
                        {{-- Total ROI --}}
                        <div class="col-sm-3" id="total-roi-section-{{ $key }}">
                          <label class="form-label" for="total-roi">
                            {{ __('Total ROI') }} (%)
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                          </label>
                          <input type="number" id="total-roi-{{ $key }}" readonly class="form-control" min="0" max="100" step=".01" name="dealer_total_roi[{{ $key }}]" value="{{ $discount_details->total_roi }}" oninput="getPostedDiscount()" />
                        </div>
                        {{-- Discount Charge --}}
                        <div class="col-sm-3" id="daily-daily-discount-charge-{{ $key }}">
                          <label class="form-label" for="daily-charge">
                            {{ __('Discount Charge')}} (%)
                            <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                          </label>
                          <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[{{ $key }}]" value="{{ round($discount_details->total_roi / 365, 2) }}" />
                        </div>
                        <div class="col-12 mb-2 mt-2" id="discount-delete-{{ $key }}">
                          <i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDiscount({{ $key }})"></i>
                        </div>
                      @endif
                    @endforeach
                  @else
                    <div class="col-sm-12" id="id-section-0">
                      <input type="hidden" value="-1" name="discount_details_key[0]">
                    </div>
                    @if ($loop->first)
                      <div class="col-sm-6">
                        <label for="" class="form-label">
                          {{ __('From Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                        </label>
                        <input type="number" name="from_day[]" id="from-day-0" class="form-control" min="1" readonly value="{{ $discount_details->from_day }}" />
                      </div>
                      <div class="col-sm-6">
                        <label for="" class="form-label">
                          {{ __('To Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                        </label>
                        <input type="number" name="to_day[]" id="to-day-{{ $key }}" class="form-control" min="2" value="{{ $discount_details->to_day }}" />
                      </div>
                      <div class="col-sm-3">
                        <label class="form-label" for="credit-spread">
                          {{ __('Credit Spread')}} (%)
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                        </label>
                        <input type="number" id="credit-spread-{{ $key }}" class="form-control" name="dealer_credit_spread[]" min="0" max="100" step=".01" value="{{ $discount_details->credit_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      <div class="col-sm-3">
                        <label class="form-label" for="business-strategy-spread">
                          {{ __('Business Strategy Spread')}} (%)
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_business_strategy_spread[]" value="{{ $discount_details->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      <div class="col-sm-3">
                        <label class="form-label" for="total-spread">
                          {{ __('Total Spread')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                        </label>
                        <input type="number" id="total-spread-{{ $key }}" readonly class="form-control" min="0" max="100" step=".01" name="dealer_total_spread[]" value="{{ $discount_details->total_spread }}" />
                      </div>
                      <div class="col-sm-3">
                        <label class="form-label" for="total-roi">
                          {{ __('Total ROI')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                        </label>
                        <input type="number" id="total-roi-{{ $key }}" readonly class="form-control" min="0" max="100" step=".01" name="dealer_total_roi[]" value="{{ $discount_details->total_roi }}" oninput="getPostedDiscount()" />
                      </div>
                      {{-- Discount Charge --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="daily-discount-charge">
                          {{ __('Discount Charge')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                        </label>
                        <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[]" value="{{ round($discount_details->total_roi / 365, 2) }}" />
                      </div>
                      <div class="col-sm-9"></div>
                    @else
                      <div class="col-sm-6" id="from-day-section-{{ $key }}">
                        <label for="" class="form-label">
                          {{ __('From Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>
                        </label>
                        <input type="number" name="from_day[{{ $key }}]" id="from-day-{{ $key }}" class="form-control" min="1" readonly value="{{ $discount_details->from_day }}" />
                      </div>
                      <div class="col-sm-6" id="to-day-section-{{ $key }}">
                        <label for="" class="form-label">
                          {{ __('To Day')}}
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>
                        </label>
                        <input type="number" name="to_day[{{ $key }}]" id="to-day-{{ $key }}" class="form-control" min="2" value="{{ $discount_details->to_day }}" />
                      </div>
                      <div class="col-sm-3" id="credit-spread-section-{{ $key }}">
                        <label class="form-label" for="credit-spread">
                          {{ __('Credit Spread')}} (%)
                          <span class="text-danger">*</span>
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>
                        </label>
                        <input type="number" id="credit-spread-{{ $key }}" class="form-control" name="dealer_credit_spread[{{ $key }}]" min="0" max="100" step=".01" value="{{ $discount_details->credit_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      <div class="col-sm-3" id="business-strategy-spread-section-{{ $key }}">
                        <label class="form-label" for="business-strategy-spread">
                          {{ __('Business Strategy Spread')}} (%)
                          <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_business_strategy_spread[{{ $key }}]" value="{{ $discount_details->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                      </div>
                      <div class="col-sm-3" id="total-spread-section-{{ $key }}">
                        <label class="form-label" for="total-spread">
                          {{ __('Total Spread')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>
                        </label>
                        <input type="number" id="total-spread-{{ $key }}" readonly class="form-control" min="0" max="100" step=".001" name="dealer_total_spread[{{ $key }}]" value="{{ $discount_details->total_spread }}" />
                      </div>
                      <div class="col-sm-3" id="total-roi-section-{{ $key }}">
                        <label class="form-label" for="total-roi">
                         {{ __(' Total ROI')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>
                        </label>
                        <input type="number" id="total-roi-{{ $key }}" readonly class="form-control" min="0" max="100" step=".001" name="dealer_total_roi[{{ $key }}]" value="{{ $discount_details->total_roi }}" oninput="getPostedDiscount()" />
                      </div>
                      {{-- Discount Charge --}}
                      <div class="col-sm-3">
                        <label class="form-label" for="daily-discount-charge-{{ $key }}">
                          {{ __('Discount Charge')}} (%)
                          <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                        </label>
                        <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[{{ $key }}]" value="{{ round($discount_details->total_roi / 365, 2) }}" />
                      </div>
                      <div class="col-sm-9"></div>
                      <div class="col-12 mb-2 mt-2" id="discount-delete-{{ $key }}">
                        <i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDiscount({{ $key }})"></i>
                      </div>
                    @endif
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
                  <select name="dealer_tax_on_discount" id="tax-on-discount" class="form-control">
                    <option value="">{{ __('Select')}}</option>
                    @foreach ($taxes as $key => $tax)
                      <option value="{{ $tax }}" @if($program && $program->discountDetails?->first()?->tax_on_discount == $tax) selected @endif>{{ $key }} ({{ $tax }}%)</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('dealer_tax_on_discount')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="limit-block-overdue-days">
                    {{ __('Limit Block Overdue Days')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="limit-block-overdue-days" class="form-control" min="0" name="limit_block_overdue_days" value="{{ $program->discountDetails?->first()?->limit_block_overdue_days }}" />
                  <x-input-error :messages="$errors->get('limit_block_overdue_days')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="penal-discount-on-principle">
                    {{ __('Penal Discount on Principle')}} (%)
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="penal-discount-on-principle" class="form-control" min="0" max="100" step=".01" name="dealer_penal_discount_on_principle" value="{{ $program->discountDetails?->first()?->penal_discount_on_principle }}" />
                  <x-input-error :messages="$errors->get('dealer_penal_discount_on_principle')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="discount-posted-discount-spread">
                    {{ __('Discount on Posted Discount Spread')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="discount-posted-discount-spread" class="form-control" min="0" max="100" step=".01" name="discount_posted_spread" value="{{ $program->discountDetails?->first()?->discount_on_posted_discount_spread }}" oninput="getPostedDiscount()" />
                  <x-input-error :messages="$errors->get('discount_posted_spread')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="discount-posted-discount">
                    {{ __('Discount on Posted Discount')}} (%)
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="discount-posted" class="form-control" min="0" max="100" step=".01" name="discount_posted" readonly value="{{ $program->discountDetails?->first()?->discount_on_posted_discount }}" />
                  <x-input-error :messages="$errors->get('discount_posted')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="discount-type">
                    {{ __('Discount Type')}}
                    <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="discount-type" name="dealer_discount_type">
                    <option value="">{{ __('Select Discount Type')}}</option>
                    <option value="Front Ended" @if($program->discountDetails?->first()?->discount_type == "Front Ended") selected @endif>{{ __('Front Ended')}}</option>
                    <option value="Rear Ended" @if($program->discountDetails?->first()?->discount_type == "Rear Ended") selected @endif>{{ __('Rear Ended')}}</option>
                  </select>
                  <x-input-error :messages="$errors->get('dealer_discount_type')" />
                </div>
                {{-- Fee Type --}}
                <div class="col-sm-6 d-none">
                  <label class="form-label" for="fee-type">
                    {{ __('Fee Type')}}
                  </label>
                  <select class="form-select" id="fee-type" name="dealer_fee_type">
                    <option value="">{{ __('Select Fee Type')}}</option>
                    <option value="Front Ended" @if(old('fee_type') == "Front Ended" || ($program && $program->discountDetails?->first()?->fee_type == 'Front Ended')) selected @endif>{{ __('Front Ended')}}</option>
                    <option value="Rear Ended" @if(old('fee_type') == "Rear Ended" || ($program && $program->discountDetails?->first()?->fee_type == 'Rear Ended')) selected @endif>{{ __('Rear Ended')}}</option>
                  </select>
                  <x-input-error :messages="$errors->get('fee_type')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="grace-period">
                   {{ __(' Grace Period (Days)')}}
                    <span class="text-danger">*</span>
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input type="number" id="grace-period" class="form-control" min="0" name="dealer_grace_period" value="{{ $program->discountDetails?->first()?->grace_period }}" />
                  <x-input-error :messages="$errors->get('dealer_grace_period')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="grace-period-discount">
                    {{ __('Grace Period Discount')}}
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <input class="form-control" type="number" id="grace-period-discount" min="0" max="100" step=".01" name="dealer_grace_period_discount" value="{{ $program->discountDetails?->first()?->grace_period_discount }}" />
                  <x-input-error :messages="$errors->get('dealer_grace_period_discount')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="maturity-handling-on-holidays">
                    {{ __('Maturity Handling on Holidays')}}
                    <i class="tf-icons ti ti-info-circle ti-xs"></i>
                  </label>
                  <select class="form-select" id="maturity-handling-on-holidays" name="dealer_maturity_handling_on_holidays">
                    <option value="No Effect" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == "No Effect") selected @endif>{{ __('No Effect')}}</option>
                    <option value="Prepone to previous working day" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == "Prepone to previous working day") selected @endif>{{ __('Prepone to previous working day')}}</option>
                    <option value="Postpone to next working day" @if($program->discountDetails?->first()?->maturity_handling_on_holidays == "Postpone to next working day") selected @endif>{{ __('Postpone to next working day')}}</option>
                  </select>
                  <x-input-error :messages="$errors->get('maturity_handling_on_holidays')" />
                </div>
                <div class="col-sm-6">
                </div>
                <hr>
                <div class="col-12 row dealer-financing" id="dealer-program-fees">
                  @foreach ($program->fees as $key => $fee)
                    <div class="col-sm-12" id="dealer-fee-id-section-{{ $key }}">
                      <input type="hidden" value="{{ $fee->id }}" name="dealer_fee_key[{{ $key }}]">
                    </div>
                    <div class="col-sm-4" id="dealer-fee-name-section-{{ $key }}">
                      <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                      <input type="text" id="dealer-fee-name-{{ $key }}" class="form-control" name="dealer_fee_names[{{ $key }}]" value="{{ $fee->fee_name }}" />
                    </div>
                    <div class="col-sm-4" id="dealer-fee-type-section-{{ $key }}">
                      <label class="form-label" for="fee-type-0">{{ __('Type')}}</label>
                      <select class="form-select" id="fee-type-0" name="dealer_fee_types[{{ $key }}]" onchange="changeDealerFeeType({{ $key }})">
                        <option value="percentage" @if($fee->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                        <option value="amount" @if($fee->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                        <option value="per amount" @if($fee->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                      </select>
                    </div>
                    @if ($fee->type == 'per amount')
                      <div class="col-sm-4" id="dealer-fee-per-amount-value-{{ $key }}">
                        <label class="form-label" for="value">{{ __('Amount')}}</label>
                        <input type="number" step=".0001" class="form-control" name="dealer_fee_per_amount[{{ $key }}]" value="{{ $fee->per_amount }}" />
                      </div>
                    @endif
                    <div class="col-sm-4" id="dealer-fee-value-section-{{ $key }}">
                      <label class="form-label" for="value">{{ __('Value')}}</label>
                      <input type="number" step=".0000001" id="dealer-fee-value-{{ $key }}" class="form-control" name="dealer_fee_values[{{ $key }}]" value="{{ $fee->value }}" oninput="updateDealerBearingFee({{ $key }})" />
                    </div>
                    <div class="col-sm-6" id="dealer-fee-bearing-section-{{ $key }}">
                      <label class="form-label" for="dealer-fee-bearing">{{ __('Dealer Bearing')}} (%)</label>
                      <input type="number" step=".0000001" id="dealer-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" name="fee_dealer_bearing_discount[{{ $key }}]" value="{{ $fee->dealer_bearing }}" readonly />
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
                    <div class="col-sm-6" id="dealer-fee-taxes-section-{{ $key }}">
                      <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                      <select class="form-select" id="taxes" name="dealer_taxes[{{ $key }}]">
                        <option value="">{{ __('Select')}}</option>
                        @foreach ($taxes as $tax)
                          <option value="{{ $tax }}" @if($fee->taxes == $tax) selected @endif>{{ $tax }}%</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-12 mb-2 mt-2" id="dealer-fee-delete-{{ $key }}">
                      <i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDealerFee({{ $key }}, {{ $fee->id }})"></i>
                    </div>
                  @endforeach
                </div>
                <div class="col-12 dealer-financing">
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
                  <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            @endif
          </div>
          <!-- Email Mobile Details -->
          <div id="comm-details" class="content">
            <div class="row g-3" id="program-relationship-managers">
              @foreach ($program->bankUserDetails as $key => $bank_user)
                <div class="col-sm-12" id="bank-user-section-{{ $key }}">
                  <input type="hidden" value="{{ $bank_user->id }}" name="bank_user_key[{{ $key }}]">
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="bank-user-name">{{ __('Bank User Name')}}</label>
                  <input type="text" id="bank-user-email" class="form-control" name="bank_user_names[{{ $key }}]" value="{{ $bank_user->name }}" readonly />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="bank-user-email">{{ __('Bank Email')}}</label>
                  <input type="email" id="bank-user-email" class="form-control" name="bank_user_emails[{{ $key }}]" value="{{ $bank_user->email }}" readonly />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="bank-user-phone-number">{{ __('Bank User Mobile No')}}.</label>
                  <input type="text" id="bank-user-phone-number" class="form-control" name="bank_user_phone_numbers[{{ $key }}]" value="{{ $bank_user->phone_number }}" readonly />
                </div>
              @endforeach
            </div>
            <button class="btn btn-sm btn-primary my-2" type="button" id="add-relationship-manager">{{ __('Add Relationship Manager')}}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between mt-2">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-next" type="button">{{ __('Next')}} <i class="ti ti-arrow-right"></i></button>
              </div>
            </div>
          </div>
          <!-- Bank Details -->
          <div id="bank-details" class="content">
            <div class="row g-3" id="bank-accounts">
              @foreach ($program->bankDetails as $key => $bank_details)
                @if ($loop->first)
                  <div class="col-sm-12" id="bank-id-section-{{ $key }}">
                    <input type="hidden" value="{{ $bank_details->id }}" name="bank_details[{{ $key }}]">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="name-as-per-bank">
                      {{ __('Name as per Bank')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[{{ $key }}]" value="{{ $bank_details->name_as_per_bank }}" required />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="account-number">
                      {{ __('Account Number')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="account-number" class="form-control" name="account_numbers[{{ $key }}]" value="{{ $bank_details->account_number }}" required />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="bank-name">
                      {{ __('Bank Name')}}
                      <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="bank-name-{{ $key }}" name="bank_names[{{ $key }}]" required onchange="getSwiftCode({{ $key }})">
                      @foreach ($banks as $program_bank)
                        <option value="{{ $program_bank->name }}" @if ($bank_details->bank_name == $program_bank->name) selected @endif data-swiftcode="{{ $program_bank->swift_code }}">{{ $program_bank->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="bank-branch">
                      {{ __('Branch')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="bank-branch" class="form-control" name="branches[{{ $key }}]" value="{{ $bank_details->branch }}" required />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="swift-code">
                      {{ __('SWIFT Code')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="swift-code-{{ $key }}" class="form-control" name="swift_codes[{{ $key }}]" value="{{ $bank_details->swift_code }}" />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="account-type">
                      {{ __('Account Type')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="account-type" class="form-control" name="account_types[{{ $key }}]" value="{{ $bank_details->account_type }}" />
                  </div>
                @else
                  <div class="col-sm-12" id="bank-id-section-{{ $key }}">
                    <input type="hidden" value="{{ $bank_details->id }}" name="bank_details[{{ $key }}]">
                  </div>
                  <div class="col-sm-6 mt-2" id="name-as-per-bank-section-{{ $key }}">
                    <label class="form-label" for="name-as-per-bank">
                      {{ __('Name as per Bank')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[{{ $key }}]" value="{{ $bank_details->name_as_per_bank }}" required />
                  </div>
                  <div class="col-sm-6" id="account-number-section-{{ $key }}">
                    <label class="form-label" for="account-number">
                      {{ __('Account Number')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="account-number" class="form-control" name="account_numbers[{{ $key }}]" value="{{ $bank_details->account_number }}" required />
                  </div>
                  <div class="col-sm-6" id="bank-name-section-{{ $key }}">
                    <label class="form-label" for="bank-name">
                      {{ __('Bank Name')}}
                      <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="bank-name-{{ $key }}" name="bank_names[{{ $key }}]" required onchange="getSwiftCode({{ $key }})">
                      @foreach ($banks as $program_bank)
                        <option value="{{ $program_bank->name }}" @if ($bank_details->bank_name == $program_bank->name) selected @endif data-swiftcode="{{ $program_bank->swift_code }}">{{ $program_bank->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-6" id="branch-section-{{ $key }}">
                    <label class="form-label" for="bank-branch">
                      {{ __('Branch')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="bank-branch" class="form-control" name="branches[{{ $key }}]" value="{{ $bank_details->branch }}" required />
                  </div>
                  <div class="col-sm-6" id="swift-code-section-{{ $key }}">
                    <label class="form-label" for="swift-code">
                      {{ __('SWIFT Code')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="swift-code-{{ $key }}" class="form-control" name="swift_codes[{{ $key }}]" value="{{ $bank_details->swift_code }}" />
                  </div>
                  <div class="col-sm-6" id="account-type-section-{{ $key }}">
                    <label class="form-label" for="account-type">
                      {{ __('Account Type')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="account-type" class="form-control" name="account_types[{{ $key }}]" value="{{ $bank_details->account_type }}" />
                  </div>
                  <div class="col-sm-12" id="delete-item-div-{{ $key }}">
                    <i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem({{ $key }}, {{ $bank_details->id }})"></i>'
                  </div>
                @endif
              @endforeach
            </div>
            <button class="btn btn-sm btn-primary my-2" id="add-bank-details" type="button">{{ __('Add new bank details')}}</button>
            <div class="row g-3 mt-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <button class="btn btn-primary btn-submit">{{ __('Update Program')}}</button>
              </div>
            </div>
          </div>
          <button class="btn d-none" type="button" id="show-confirm-modal" data-bs-toggle="modal" data-bs-target="#confirm-update-modal"></button>
          <div class="modal fade" id="confirm-update-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalCenterTitle">{{ __('Update Program')}}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <h4>{{ __('Are you sure you want to update the program?')}}</h4>
                  <div>
                    <h5>{{ __('Select Program Mappings where changes will apply') }}:</h5>
                  </div>
                  @foreach ($mappings as $mapping)
                    <div class="form-check">
                      <input class="form-check-input border-primary" name="program_vendor_configurations[]" type="checkbox" value="{{ $mapping->id }}" />
                      <label for="{{ $mapping->id }}" class="form-label">{{ $mapping->buyer ? $mapping->buyer->name : $mapping->company->name }}({{ $mapping->payment_account_number }})</label>
                    </div>
                  @endforeach
                </div>
                <div class="modal-footer">
                  <a href="{{ route('programs.show', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-secondary">{{ __('Cancel')}}</a>
                  <button type="submit" class="btn btn-primary">{{ __('Update')}}</button>
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
