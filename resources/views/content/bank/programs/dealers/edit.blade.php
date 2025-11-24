@extends('layouts/layoutMaster')

@section('title', 'Edit Dealer Mapping')

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
    background-color:#ececec
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

    $('#flexSwitchCheckChecked').trigger('click')
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

  let discount_details_count = program.dealer_discount_rates.length

  let fees_count = 1
  let fees = $('#program-fees')

  let discount_count = 1
  let discounts = $('#program-discounts')

  $('#flexSwitchCheckChecked').click(function () {
    if ($(this).is(':checked')) {
      $('#dealer-benchmark-title').attr('disabled', true);
      $('#dealer-benchmark-title').val(program.discount_details[0].benchmark_title)
      discounts.text('')
      for (let index = 0; index <= discount_details_count - 1; index++) {
        let html = ''
        if (index == 0) {
          html += '<div class="col-sm-6" id="from-day-section-'+index+'">'
          html += '<label for="" class="form-label">'
          html += 'From Day'
          html += '<span class="text-danger">*</span>'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>'
          html += '</label>'
          html += '<input type="number" name="from_day['+index+']" id="from-day-'+index+'" class="form-control" min="1" readonly value="'+program.dealer_discount_rates[index].from_day+'" />'
          html += '</div>'
          html += '<div class="col-sm-6" id="to-day-section-'+index+'">'
          html += '<label for="" class="form-label">'
          html += 'To Day'
          html += '<span class="text-danger">*</span>'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>'
          html += '</label>'
          html += '<input type="number" name="to_day['+index+']" id="to-day-'+index+'" class="form-control" min="'+program.dealer_discount_rates[index].from_day+'" value="'+program.dealer_discount_rates[index].to_day+'" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="credit-spread-section-'+index+'">'
          html += '<label class="form-label" for="credit-spread">'
          html += 'Credit Spread (%)'
          html += '<span class="text-danger">*</span>'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>'
          html += '</label>'
          html += '<input type="number" id="dealer-credit-spread-'+index+'" value="'+program.dealer_discount_rates[index].credit_spread+'" class="form-control" name="dealer_credit_spread['+index+']" min="0" max="100" oninput="changeDealerDiscountRates('+index+')" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="business-strategy-spread-section-'+index+'">'
          html += '<label class="form-label" for="business-strategy-spread">'
          html += 'Business Strategy Spread (%)'
          html += '<span class="text-danger">*</span>'
          html += '</label>'
          html += '<input type="number" id="dealer-business-strategy-spread-'+index+'" value="'+program.dealer_discount_rates[index].business_strategy_spread+'" class="form-control" min="0" max="100" name="dealer_business_strategy_spread['+index+']" oninput="changeDealerDiscountRates('+index+')" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="total-spread-section-'+index+'">'
          html += '<label class="form-label" for="total-spread">'
          html += 'Total Spread (%)'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>'
          html += '</label>'
          html += '<input type="number" id="dealer-total-spread-'+index+'" readonly class="form-control" min="0" max="100" name="dealer_total_spread['+index+']" value="'+program.dealer_discount_rates[index].total_spread+'" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="total-roi-section-'+index+'">'
          html += '<label class="form-label" for="total-roi">'
          html += 'Total ROI (%)'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>'
          html += '</label>'
          html += '<input type="number" id="dealer-total-roi-'+index+'" readonly class="form-control" min="0" max="100" name="dealer_total_roi['+index+']" value="'+program.dealer_discount_rates[index].total_roi+'" />'
          html += '</div>'
        } else {
          html += '<div class="col-sm-6" id="from-day-section-'+index+'">'
          html += '<label for="" class="form-label">'
          html += 'From Day'
          html += '<span class="text-danger">*</span>'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="From Day"></i>'
          html += '</label>'
          html += '<input type="number" name="from_day['+index+']" id="from-day-'+index+'" class="form-control" readonly min="'+program.dealer_discount_rates[index - 1].to_day+'" value="'+program.dealer_discount_rates[index].from_day+'" />'
          html += '</div>'
          html += '<div class="col-sm-6" id="to-day-section-'+index+'">'
          html += '<label for="" class="form-label">'
          html += 'To Day'
          html += '<span class="text-danger">*</span>'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="To Day"></i>'
          html += '</label>'
          html += '<input type="number" name="to_day['+index+']" id="to-day-'+index+'" class="form-control" min="'+program.dealer_discount_rates[index].from_day+'" value="'+program.dealer_discount_rates[index].to_day+'" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="credit-spread-section-'+index+'">'
          html += '<label class="form-label" for="credit-spread">'
          html += 'Credit Spread (%)'
          html += '<span class="text-danger">*</span>'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>'
          html += '</label>'
          html += '<input type="number" id="dealer-credit-spread-'+index+'" class="form-control" value="'+program.dealer_discount_rates[index].credit_spread+'" name="dealer_credit_spread['+index+']" min="0" max="100" oninput="changeDealerDiscountRates('+index+')" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="business-strategy-spread-section-'+index+'">'
          html += '<label class="form-label" for="business-strategy-spread">'
          html += 'Business Strategy Spread (%)'
          html += '<span class="text-danger">*</span>'
          html += '</label>'
          html += '<input type="number" id="dealer-business-strategy-spread-'+index+'" value="'+program.dealer_discount_rates[index].business_strategy_spread+'" class="form-control" min="0" max="100" name="dealer_business_strategy_spread['+index+']" oninput="changeDealerDiscountRates('+index+')" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="total-spread-section-'+index+'">'
          html += '<label class="form-label" for="total-spread">'
          html += 'Total Spread (%)'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>'
          html += '</label>'
          html += '<input type="number" id="dealer-total-spread-'+index+'" readonly class="form-control" min="0" max="100" name="dealer_total_spread['+index+']"  value="'+program.dealer_discount_rates[index].total_spread+'" />'
          html += '</div>'
          html += '<div class="col-sm-3" id="total-roi-section-'+index+'">'
          html += '<label class="form-label" for="total-roi">'
          html += 'Total ROI (%)'
          html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>'
          html += '</label>'
          html += '<input type="number" id="dealer-total-roi-'+index+'" readonly class="form-control" min="0" max="100" name="dealer_total_roi['+index+']" value="'+program.dealer_discount_rates[index].total_roi+'" />'
          html += '</div>'
          html += '<div class="col-12 mb-2 mt-2" id="discount-delete-'+index+'">'
          html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeDiscount('+index+')"></i>'
          html += '</div>'
        }

        $(html).appendTo(discounts);
        discount_count += 1;
      }

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

      fees.text('')
      program.fees.forEach((program_fee, index) => {
        let html = '<div class="col-sm-4" id="fee-name-'+index+'">'
          html += '<label class="form-label" for="fee-name">Fee Name</label>'
          html += '<input type="text" id="fee-name" class="form-control" name="fee_names['+index+']" />'
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
            html += '<input type="number" id="fee-per-amount-value-input-'+index+'" step=".01" class="form-control" name="fee_per_amount['+index+']" readonly />'
            html += '</div>'
          }
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-values-'+index+'">'
          html += '<label class="form-label" for="value">Value</label>'
          html += '<input type="number" id="dealer-fee-value-'+index+'" class="form-control" name="fee_values['+index+']" />'
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-dealer-bearing-'+index+'">'
          html += '<label class="form-label" for="dealer-fee-bearing">Dealer Bearing</label>'
          html += '<input type="number" id="dealer-fee-bearing-'+index+'" class="form-control" value="100" name="fee_dealer_bearing['+index+']" readonly />'
          html += '</div>'
          html += '<div class="col-sm-4" id="fee-charge-types-'+index+'">'
          html += '<label class="form-label" for="value">Charge</label>'
          html += '<select class="form-select" id="fee-charge-types-'+index+'" name="charge_types['+index+']">'
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
          html += '<div class="col-sm-4" id="fee-account-numbers-'+index+'">'
          html += '<label class="form-label" for="value">Credit To</label>'
          html += '<select class="form-select" id="fee-account-numbers-'+index+'" name="fee_account_numbers['+index+']">'
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
          html += '<select class="form-select" id="taxes" name="taxes['+index+']">'
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
          html += '<div class="col-12 mb-2 mt-2" id="fee-delete-'+index+'">'
          html += '<i class="ti ti-trash ti-sm text-danger" style="cursor: pointer;" onclick="removeFee('+index+')"></i>'
          html += '</div>'

        $(html).appendTo(fees);
        fees_count += 1;
      });
    } else {
      $('#dealer-benchmark-title').attr('disabled', false);
      for (let index = 0; index <= discount_details_count - 1; index++) {
        $('#business-strategy-spread-'+index).removeAttr('readonly');
        $('#credit-spread-'+index).removeAttr('readonly');
        // $('#total-spread-'+index).removeAttr('readonly');
        // $('#total-roi-'+index).removeAttr('readonly');
      }

      $('#anchor-discount-bearing').removeAttr('readonly');
      $('#vendor-discount-bearing').removeAttr('readonly');
      $('#penal-discount-on-principle').removeAttr('readonly');
      $('#grace-period').removeAttr('readonly');
      $('#grace-period-discount').removeAttr('readonly');
    }
  })

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
        html += '<input type="number" step=".000001" id="fee-per-amount-value-input-'+fees_count+'" class="form-control" name="fee_per_amount['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-values-'+fees_count+'">'
        html += '<label class="form-label" for="value">Value</label>'
        html += '<input type="number" step=".000001" id="dealer-fee-value-'+fees_count+'" class="form-control" name="fee_values['+fees_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-4" id="fee-dealer-bearing-'+fees_count+'">'
        html += '<label class="form-label" for="dealer-fee-bearing">Dealer Bearing</label>'
        html += '<input type="number" step=".000001" id="dealer-fee-bearing-'+fees_count+'" class="form-control" value="100" name="fee_dealer_bearing['+fees_count+']" readonly />'
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
        html += '<select class="form-select" id="taxes" name="taxes['+fees_count+']">'
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
    $('div').remove('#fee-name-'+index+', #fee-type-'+index+', #fee-values-'+index+', #fee-anchor-bearing-'+index+', #fee-vendor-bearing-'+index+', #fee-dealer-bearing-'+index+', #fee-taxes-'+index+', #fee-delete-'+index)
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

  changeDiscountRates()

  function changeDiscountRates() {
    // Benchmark rate
    let rate = $('#dealer-benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-dealer-benchmark-rate').val(rate);
    if (rate) {
      current_benchmark_rate.val(rate)
    }

    for (let index = 0; index <= discount_count; index++) {
      changeDealerDiscountRates(index);
    }
  }

  function changeDealerBenchmarkRate() {
    // Benchmark rate
    let rate = $('#dealer-benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-dealer-benchmark-rate').val(rate);
    if (rate) {
      current_benchmark_rate.val(rate)
    }

    for (let index = 0; index <= discount_count; index++) {
      // Business Strategy Spread
      let business_strategy_spread = $('#dealer-business-strategy-spread-'+index);
      // Credit Spread
      let credit_spread = $('#dealer-credit-spread-'+index);
      // Total ROI
      let total_roi = $('#dealer-total-roi-'+index);
      // Total Spread
      let total_spread = $('#dealer-total-spread-'+index);

      let dealer_daily_discount_charge = $('#dealer-daily-discount-charge-' + index)

      if (business_strategy_spread.val() != '' && credit_spread.val() != '') {
        let business_spread = float(business_strategy_spread.val()) ? Number(business_strategy_spread.val()).toFixed(2) : Number(business_strategy_spread.val())
        let credit = float(Number(credit_spread.val())) ? Number(credit_spread.val()).toFixed(2) : Number(credit_spread.val())

        total_spread.val(Number(Number(business_spread) + Number(credit)).toFixed(2));

        total_roi.val(Number(Number(total_spread.val()) + Number(current_benchmark_rate.val())).toFixed(2))

        dealer_daily_discount_charge.val(Number(total_roi.val() / 365).toFixed(2))
      }
    }
  }

  function changeDealerDiscountRates(index) {
    // Benchmark rate
    let rate = $('#dealer-benchmark-title').find(':selected').data('rate')

    let current_benchmark_rate = $('#current-dealer-benchmark-rate');

    // Business Strategy Spread
    let business_strategy_spread = $('#dealer-business-strategy-spread-'+index);
    // Credit Spread
    let credit_spread = $('#dealer-credit-spread-'+index);
    // Total ROI
    let total_roi = $('#dealer-total-roi-'+index);
    // Total Spread
    let total_spread = $('#dealer-total-spread-'+index);

    let dealer_daily_discount_charge = $('#dealer-daily-discount-charge-' + index)

    if (business_strategy_spread.val() != '' && credit_spread.val() != '') {
      let business_spread = business_strategy_spread.val() ? Number(business_strategy_spread.val()).toFixed(2) : Number(business_strategy_spread.val())
      let credit = credit_spread.val() ? Number(credit_spread.val()).toFixed(2) : Number(credit_spread.val())

      total_spread.val(Number(Number(business_spread) + Number(credit)).toFixed(2));

      total_roi.val(Number(Number(total_spread.val()) + Number(current_benchmark_rate.val())).toFixed(2))

      dealer_daily_discount_charge.val(Number(total_roi.val() / 365).toFixed(2))
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
        html += '<input type="number" name="to_day['+discount_count+']" id="to-day-'+discount_count+'" class="form-control" min="'+min_val+'" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="credit-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="credit-spread">'
        html += 'Credit Spread (%)'
        html += '<span class="text-danger">*</span>'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Credit Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-credit-spread-'+discount_count+'" class="form-control" name="dealer_credit_spread['+discount_count+']" min="0" max="100" oninput="changeDealerDiscountRates('+discount_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="business-strategy-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="business-strategy-spread">'
        html += 'Business Strategy Spread (%)'
        html += '<span class="text-danger">*</span>'
        html += '</label>'
        html += '<input type="number" id="dealer-business-strategy-spread-'+discount_count+'" class="form-control" min="0" max="100" name="dealer_business_strategy_spread['+discount_count+']" oninput="changeDealerDiscountRates('+discount_count+')" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-spread-section-'+discount_count+'">'
        html += '<label class="form-label" for="total-spread">'
        html += 'Total Spread (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total Spread = Business Strategy Spread + Credit Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-total-spread-'+discount_count+'" readonly class="form-control" min="0" max="100" name="dealer_total_spread['+discount_count+']" />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-roi-section-'+discount_count+'">'
        html += '<label class="form-label" for="total-roi">'
        html += 'Total ROI (%)'
        html += '<i class="tf-icons ti ti-info-circle ti-xs" data-title="Total ROI = Benchmark + Total Spread"></i>'
        html += '</label>'
        html += '<input type="number" id="dealer-total-roi-'+discount_count+'" readonly class="form-control" min="0" max="100" name="dealer_total_roi['+discount_count+']" />'
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
  // $('#limit_approval_date').attr("min", min_day);
  // $('#limit_expiry_date').attr("min", min_day);
  // $('#limit_review_date').attr("min", min_day);

  function getSwiftCode(index) {
    $('#swift-code-'+index).val($('#bank-name-'+index).find(':selected').data('swiftcode'))
  }

  let bank_accounts = {!! $mapping->bank_details->count() !!}

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
        html += '<select class="form-select" id="bank-name-'+bank_accounts+'" name="bank_names['+bank_accounts+']" onchange="getSwiftCode('+bank_accounts+')">'
        html += '<option value="">Select Bank</option>'
        @foreach ($banks as $dealer_bank)
          html += '<option value="'+{!! json_encode($dealer_bank->name) !!}+'" data-swiftcode="'+{!! json_encode($dealer_bank->swift_code) !!}+'">'+{!! json_encode($dealer_bank->name) !!}+'</option>'
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

  function deleteItem(index) {
    $('div').remove('#name-as-per-bank-section-'+index+', #account-number-section-'+index+', #bank-name-section-'+index+', #branch-section-'+index+', #swift-code-section-'+index+', #account-type-section-'+index+', #delete-item-div-'+index);
    bank_accounts -= 1;
  }

  function updateDealerBearingFee(index) {
    let dealer_bearing = $('#dealer-fee-value-'+index).val()
    $('#dealer-fee-bearing-'+index).val(dealer_bearing)
  }

  $('#eligibility').on('input', function() {
    let eligibility = $(this).val()
    $('#invoice_margin').val(100 - eligibility)
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2 d-flex justify-content-between">
  <span class="fw-light">{{ __('Edit Mapping of Dealer')}}, {{ $company->name }}, {{ __('to Program')}} - <a href="{{ route('programs.show', ['bank' => $bank, 'program' => $program]) }}" class="text-primary text-decoration-underline">{{ $program->name }}</a></span>
  <div class="d-flex">
    <a href="{{ route('programs.show', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-label-secondary mx-2">{{ __('Discard')}}</a>
  </div>
</h4>
<div class="">
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
    <div class="bs-stepper wizard-vertical vertical mt-2" id="vendor-details-wizard">
      <div class="bs-stepper-header">
        <div class="step" data-target="#vendor-details">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle"><i class="tf-icons ti ti-users"></i></span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Dealer Details')}}</span>
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
        <form id="vendor-details-form" method="POST" action="{{ route('programs.dealers.map.update', ['bank' => $bank, 'program' => $program, 'company' => $company]) }}">
          @csrf
          <!-- Company Details -->
          <div id="vendor-details" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="dealer">{{ __('Dealer')}} <span class="text-danger">*</span></label>
                <input type="text" value="{{ $company->name }}" readonly class="form-control">
              </div>
              <div class="col-sm-6">
                <label for="payment-od-account">{{ __('Payment / OD Account')}} <span class="text-danger">*</span></label>
                <input type="text" id="payment-od-account" class="form-control" name="payment_account_number" value="{{ $mapping->configuration->payment_account_number }}" />
                <x-input-error :messages="$errors->get('payment_account_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="eligibility">{{ __('Eligibility')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="eligibility" class="form-control" name="eligibility" min="0.01" step=".01" max="100" value="{{ $mapping->configuration->eligibility }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice_margin">{{ __('Invoice Margin')}} (%) <span class="text-danger">*</span></label>
                <input type="number" id="invoice_margin" class="form-control" name="invoice_margin" disabled readonly value="{{ 100 - $mapping->configuration->eligibility }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="sanctioned_limit">{{ __('Sanctioned Limit')}} <span class="text-danger">*</span></label>
                <input type="text" id="sanctioned_limit" class="form-control" name="sanctioned_limit" max="{{ $sanctioned_limit }}" value="{{ $mapping->configuration->sanctioned_limit }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="drawing_power">{{ __('Drawing Power')}} <span class="text-danger">*</span></label>
                <input type="text" id="drawing_power" class="form-control" name="drawing_power" max="{{ $sanctioned_limit }}" value="{{ $mapping->configuration->drawing_power ? $mapping->configuration->drawing_power : $mapping->configuration->sanctioned_limit }}" required />
                <x-input-error :messages="$errors->get('drawing_power')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit_approved_date">{{ __('Limit Approval Date')}}</label>
                <input class="form-control" type="date" id="limit_approval_date" name="limit_approved_date" value="{{ Carbon\Carbon::parse($mapping->configuration->limit_approved_date)->format('Y-m-d') }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit-expiry-date">{{ __('Limit Expiry Date')}}</label>
                <input class="form-control" type="date" id="limit_expiry_date" name="limit_expiry_date" value="{{ Carbon\Carbon::parse($mapping->configuration->limit_expiry_date)->format('Y-m-d') }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit_review_date">{{ __('Limit Review Date')}}</label>
                <input class="form-control" type="date" id="limit_review_date" name="limit_review_date" value="{{ Carbon\Carbon::parse($mapping->configuration->limit_review_date)->format('Y-m-d') }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="request-autofinance">{{ __('Request Auto Finance')}}</label>
                <select class="form-select" id="request-autofinance" name="request_auto_finance">
                  <option value="">{{ __('Select')}}</option>
                  <option value="1" @if($mapping->configuration->request_auto_finance) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(!$mapping->configuration->request_auto_finance) selected @endif>{{ __('No')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="auto-approve-finance">{{ __('Auto Approve Finance')}}</label>
                <select class="form-select" id="auto-approve-finance" name="auto_approve_finance">
                  <option value="">Select</option>
                  <option value="1" @if($mapping->configuration->auto_approve_finance) selected @endif>{{ __('Yes')}}</option>
                  <option value="0" @if(!$mapping->configuration->auto_approve_finance) selected @endif>{{ __('No')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="schema-code">{{ __('Scheme Code')}}</label>
                <input type="text" id="schema-code" class="form-control" name="schema_code" value="{{ $mapping->configuration->schema_code }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="vendor-code">{{ __('Dealer Code')}}</label>
                <input type="text" id="vendor-code" class="form-control" name="vendor_code" value="{{ $mapping->configuration->vendor_code }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="gst-no">KRA PIN</label>
                <input type="text" id="gst-no" class="form-control" name="gst_number" value="{{ $mapping->configuration->gst_number }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="classification">{{ __('Classification')}}</label>
                <select class="form-select" id="classification" name="classification">
                  <option value="">Select</option>
                  <option value="secured" @if($mapping->configuration->classification == 'secured') selected @endif>{{ __('Secured')}}</option>
                  <option value="unsecured" @if($mapping->configuration->classification == 'unsecured') selected @endif>{{ __('Unsecured')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="status">{{ __('Status')}}</label>
                <select class="form-select" id="status" name="status">
                  <option value="">{{ __('Select')}}</option>
                  <option value="active" @if($mapping->configuration->status == 'active') selected @endif>{{ __('Active')}}</option>
                  <option value="inactive" @if($mapping->configuration->status == 'inactive') selected @endif>{{ __('Inactive')}}</option>
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
                <select class="form-select" id="dealer-benchmark-title" name="benchmark_title" onchange="changeDiscountRates()">
                  <option value="">{{ __('Select Base Rate')}}</option>
                  @foreach ($benchmark_rates as $key => $benchmark_rate)
                    <option value="{{ $key }}" @if($mapping->discount->first()->benchmark_title == $key) selected @endif data-rate="{{ $benchmark_rate }}">{{ $key }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('benchmark_title')" />
              </div>
              <div class="col-sm-6">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="current-dealer-benchmark-rate">{{ __('Current Base Rate')}}</label>
                  <span class="text-primary">{{ __('Set As Per Current Master')}}</span>
                </div>
                <input type="number" readonly id="current-dealer-benchmark-rate" class="form-control" min="0" max="100" step=".01" readonly value="{{ $mapping->discount->first()->benchmark_rate }}" name="benchmark_rate" />
                <x-input-error :messages="$errors->get('benchmark_rate')" />
              </div>
              <div class="col-12 row" id="program-discounts">
                @foreach ($mapping->discount as $key => $discount_details)
                  @if ($loop->first)
                    <div class="col-sm-6">
                      <label class="form-label" for="from-day-{{ $key }}">{{ __('From Day')}} <span class="text-danger">*</span></label>
                      <input type="number" id="from-day-{{ $key }}" class="form-control" name="from_day[{{ $key }}]" @if($loop->first) readonly @endif value="{{ $discount_details->from_day }}" />
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="to-day-{{ $key }}">{{ __('To Day')}} <span class="text-danger">*</span></label>
                      <input type="number" id="to-day-{{ $key }}" class="form-control" name="to_day[{{ $key }}]" value="{{ $discount_details->to_day }}" />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="business-strategy-spread-{{ $key }}">{{ __('Business Strategy Spread')}} (%) <span class="text-danger">*</span></label>
                      <input type="number" id="dealer-business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_business_strategy_spread[{{ $key }}]" readonly value="{{ $discount_details->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="credit-spread-{{ $key }}">{{ __('Credit Spread')}} (%) <span class="text-danger">*</span></label>
                      <input type="number" id="dealer-credit-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_credit_spread[{{ $key }}]" readonly value="{{ $discount_details->credit_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="total-spread-{{ $key }}">{{ __('Total Spread')}} (%) <span class="text-danger">*</span></label>
                      <input type="number" id="dealer-total-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_total_spread[{{ $key }}]" readonly value="{{ $discount_details->total_spread }}" />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="total-roi-{{ $key }}">Total ROI (%) <span class="text-danger">*</span></label>
                      <input type="number" id="dealer-total-roi-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_total_roi[{{ $key }}]" readonly value="{{ $discount_details->total_roi }}" />
                    </div>
                    {{-- Discount Charge --}}
                    <div class="col-sm-3">
                      <label class="form-label" for="daily-discount-charge-{{ $key }}">
                        {{ __('Discount Charge')}} (%)
                        <i class="tf-icons ti ti-info-circle ti-xs" data-title="Discount Charge = Total ROI / 365"></i>
                      </label>
                      <input type="number" id="dealer-daily-discount-charge-{{ $key }}" readonly class="form-control" min="0" max="100" step=".00000000001" name="dealer_daily_discount_charge[]" value="{{ round($discount_details->total_roi / 365, 2) }}" />
                    </div>
                    <div class="col-sm-9"></div>
                  @else
                    <div class="col-sm-6" id="from-day-section-{{ $key }}">
                      <label class="form-label" for="from-day-{{ $key }}">{{ __('From Day')}}</label>
                      <input type="number" id="from-day-{{ $key }}" class="form-control" name="from_day[{{ $key }}]" @if($loop->first) readonly @endif value="{{ $discount_details->from_day }}" />
                    </div>
                    <div class="col-sm-6" id="to-day-section-{{ $key }}">
                      <label class="form-label" for="to-day-{{ $key }}">{{ __('To Day')}}</label>
                      <input type="number" id="to-day-{{ $key }}" class="form-control" name="to_day[{{ $key }}]" value="{{ $discount_details->to_day }}" />
                    </div>
                    <div class="col-sm-3" id="business-strategy-spread-section-{{ $key }}">
                      <label class="form-label" for="business-strategy-spread-{{ $key }}">{{ __('Business Strategy Spread')}} (%)</label>
                      <input type="number" id="dealer-business-strategy-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_business_strategy_spread[{{ $key }}]" value="{{ $discount_details->business_strategy_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                    </div>
                    <div class="col-sm-3" id="credit-spread-section-{{ $key }}">
                      <label class="form-label" for="credit-spread-{{ $key }}">{{ __('Credit Spread')}} (%)</label>
                      <input type="number" id="dealer-credit-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_credit_spread[{{ $key }}]" value="{{ $discount_details->credit_spread }}" oninput="changeDealerDiscountRates({{ $key }})" />
                    </div>
                    <div class="col-sm-3" id="total-spread-section-{{ $key }}">
                      <label class="form-label" for="total-spread-{{ $key }}">{{ __('Total Spread ')}}(%)</label>
                      <input type="number" id="dealer-total-spread-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_total_spread[{{ $key }}]" readonly value="{{ $discount_details->total_spread }}" />
                    </div>
                    <div class="col-sm-3" id="total-roi-section-{{ $key }}">
                      <label class="form-label" for="total-roi-{{ $key }}">Total ROI (%)</label>
                      <input type="number" id="dealer-total-roi-{{ $key }}" class="form-control" min="0" max="100" step=".01" name="dealer_total_roi[{{ $key }}]" readonly value="{{ $discount_details->total_roi }}" />
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
                @endforeach
              </div>
              <div class="col-12">
                <span class="d-flex justify-content-end align-items-center" id="add-discount" style="cursor: pointer">
                  <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                  <span class="mx-2">
                    {{ __('Add')}}
                  </span>
                </span>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="limit-block-overdue-days">
                  {{ __('Limit Block Overdue Days')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="limit-block-overdue-days" class="form-control" min="0" name="limit_block_overdue_days" value="{{ $mapping->discount->first()->limit_block_overdue_days }}" />
                <x-input-error :messages="$errors->get('limit_block_overdue_days')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="penal-discount-on-principle">{{ __('Penal Discount on Principle')}} (%)</label>
                <input type="number" id="penal-discount-on-principle" class="form-control" min="0" max="100" step=".01" name="penal_discount_on_principle" readonly value="{{ $mapping->discount->first()->penal_discount_on_principle }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="grace-period">{{ __('Grace Period (Days)')}}</label>
                <input type="number" id="grace-period" class="form-control" name="grace_period" readonly value="{{ $mapping->discount->first()->grace_period }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="grace-period-discount">{{ __('Grace Period Discount')}}</label>
                <input class="form-control" type="number" id="grace-period-discount" name="grace_period_discount" readonly value="{{ $mapping->discount->first()->grace_period_discount }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="maturity-handling-on-holidays">{{ __('Maturity Handling on Holidays')}}</label>
                <select class="form-select" id="maturity-handling-on-holidays" name="maturity_handling_on_holidays">
                  <option value="No Effect" @if($mapping->discount->first()->maturity_handling_on_holidays == 'No Effect') selected @endif>{{ __('No Effect')}}</option>
                  <option value="Prepone to previous working day" @if($mapping->discount->first()->maturity_handling_on_holidays == 'Prepone to previous working day') selected @endif>{{ __('Prepone to previous working day')}}</option>
                  <option value="Postpone to next working day" @if($mapping->discount->first()->maturity_handling_on_holidays == 'Postpone to next working day') selected @endif>{{ __('Postpone to next working day')}}</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="discount-posted-discount-spread">
                  {{ __('Discount on Posted Discount Spread')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="discount-posted-discount-spread" class="form-control" min="0" max="100" step=".01" name="discount_posted_spread" value="{{ $mapping->discount->first()->discount_on_posted_discount_spread }}" oninput="getPostedDiscount()" />
                <x-input-error :messages="$errors->get('discount_posted_spread')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="discount-posted-discount">
                  {{ __('Discount on Posted Discount')}} (%)
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs"></i>
                </label>
                <input type="number" id="discount-posted" class="form-control" min="0" max="100" step=".01" name="discount_posted" readonly value="{{ $mapping->discount->first()->discount_on_posted_discount }}" />
                <x-input-error :messages="$errors->get('discount_posted')" />
              </div>
              <hr>
              <div class="col-12 row" id="program-fees">
                @foreach ($mapping->fees as $key => $fee)
                  <div class="col-sm-4" id="fee-name-{{ $key }}">
                    <label class="form-label" for="fee-name">{{ __('Fee Name')}}</label>
                    <input type="text" id="fee-name" class="form-control" name="fee_names[{{ $key }}]" value="{{ $fee->fee_name }}" />
                  </div>
                  <div class="col-sm-4" id="fee-type-{{ $key }}">
                    <label class="form-label" for="fee-type-{{ $key }}">{{ __('Type')}}</label>
                    <select class="form-select" id="fee-type-{{ $key }}" name="fee_types[{{ $key }}]" onchange="changeFeeType({{ $key }})">
                      <option value="percentage" @if($fee->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                      <option value="amount" @if($fee->type == 'amount') selected @endif>{{ __('Amount')}}</option>
                      <option value="per amount" @if($fee->type == 'per amount') selected @endif>{{ __('Per Amount')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4 @if ($fee->type != 'per amount') d-none @endif" id="fee-per-amount-value-{{ $key }}">
                    <label class="form-label" for="value">{{ __('Amount')}}</label>
                    <input type="number" step=".000001" class="form-control" name="fee_per_amount[{{ $key }}]" value="{{ $fee->per_amount }}" />
                  </div>
                  <div class="col-sm-4" id="fee-values-{{ $key }}">
                    <label class="form-label" for="value">{{ __('Value')}}</label>
                    <input type="number" step=".000001" id="dealer-fee-value-{{ $key }}" class="form-control" name="fee_values[{{ $key }}]" value="{{ $fee->value }}" oninput="updateDealerBearingFee({{ $key }})" />
                  </div>
                  <div class="col-sm-4" id="fee-dealer-bearing-{{ $key }}">
                    <label class="form-label" for="dealer-fee-bearing">{{ __('Dealer Bearing')}}</label>
                    <input type="number" step=".000001" id="dealer-fee-bearing-{{ $key }}" class="form-control" min="0" max="100" name="fee_dealer_bearing[{{ $key }}]" value="{{ $fee->dealer_bearing }}" readonly />
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
                  <div class="col-sm-4" id="fee-taxes-{{ $key }}">
                    <label class="form-label" for="taxes">{{ __('Taxes')}}</label>
                    <select class="form-select" id="taxes" name="taxes[{{ $key }}]">
                      <option value="">{{ __('Select') }}</option>
                      @foreach ($taxes as $key => $tax)
                        <option value="{{ $tax }}" @if($fee->taxes == $tax) selected @endif>{{ $key }} ({{ $tax }}%)</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-12" id="fee-delete-{{ $key }}">
                    <i class="ti ti-trash ti-sm text-danger mt-2" title="delete" style="cursor: pointer" onclick="removeFee({{ $key }})"></i>
                  </div>
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
                  <a href="{{ route('programs.vendors.manage', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
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
                  <a href="{{ route('programs.vendors.manage', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                  <button class="btn btn-primary btn-next" type="button"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Next')}}</span> <i class="ti ti-arrow-right"></i></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Bank Details -->
          <div id="bank-details" class="content">
            <div class="row g-3" id="bank-accounts">
              @foreach ($mapping->bank_details as $key => $bank_details)
                @if ($loop->first)
                  <div class="col-sm-6">
                    <label class="form-label" for="name-as-per-bank">{{ __('Account Name')}}</label>
                    <input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[{{ $key }}]" value="{{ $bank_details->name_as_per_bank }}" />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="account-number">{{ __('Account Number')}}</label>
                    <input type="text" id="account-number" class="form-control" name="account_numbers[{{ $key }}]" value="{{ $bank_details->account_number }}" />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label" for="bank-name">{{ __('Bank Name')}}</label>
                    <select class="form-select" id="bank-name-{{ $key }}" name="bank_names[{{ $key }}]" required onchange="getSwiftCode({{ $key }})">
                      @foreach ($banks as $dealer_bank)
                        <option value="{{ $dealer_bank->name }}" @if($bank_details->bank_name == $dealer_bank->name) selected @endif data-swiftcode={{ $dealer_bank->swift_code }}>{{ $dealer_bank->name }}</option>
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
                  <hr>
                @else
                  <div class="col-sm-6" id="name-as-per-bank-section-{{ $key }}">
                    <label class="form-label" for="name-as-per-bank">{{ __('Account Name')}}</label>
                    <input type="text" id="name-as-per-bank" class="form-control" name="bank_names_as_per_banks[{{ $key }}]" value="{{ $bank_details->name_as_per_bank }}" />
                  </div>
                  <div class="col-sm-6" id="account-number-section-{{ $key }}">
                    <label class="form-label" for="account-number">{{ __('Account Number')}}</label>
                    <input type="text" id="account-number" class="form-control" name="account_numbers[{{ $key }}]" value="{{ $bank_details->account_number }}" />
                  </div>
                  <div class="col-sm-6" id="bank-name-section-{{ $key }}">
                    <label class="form-label" for="bank-name">{{ __('Bank Name')}}</label>
                    <select class="form-select" id="bank-name-{{ $key }}" name="bank_names[{{ $key }}]" required onchange="getSwiftCode({{ $key }})">
                      @foreach ($banks as $dealer_bank)
                        <option value="{{ $dealer_bank->name }}" @if($bank_details->bank_name == $dealer_bank->name) selected @endif data-swiftcode={{ $dealer_bank->swift_code }}>{{ $dealer_bank->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-sm-6" id="branch-section-{{ $key }}">
                    <label class="form-label" for="bank-branch">{{ __('Branch')}}</label>
                    <input type="text" id="bank-branch" class="form-control" name="branches[{{ $key }}]" value="{{ $bank_details->branch }}" />
                  </div>
                  <div class="col-sm-6" id="swift-code-section-{{ $key }}">
                    <label class="form-label" for="swift-code">{{ __('SWIFT Code')}}</label>
                    <input type="text" id="swift-code-{{ $key }}" class="form-control" name="swift_codes[{{ $key }}]" value="{{ $bank_details->swift_code }}" />
                  </div>
                  <div class="col-sm-6" id="account-type-section-{{ $key }}">
                    <label class="form-label" for="account-type">{{ __('Account Type')}}</label>
                    <input type="text" id="account-type" class="form-control" name="account_types[{{ $key }}]" value="{{ $bank_details->account_type }}" />
                  </div>
                  <div class="col-sm-12" id="delete-item-div-{{ $key }}">
                    <i class="ti ti-trash ti-sm text-danger mt-2" title="delete" style="cursor: pointer" onclick="deleteItem({{ $key }})"></i>
                  </div>
                  <hr>
                @endif
              @endforeach
            </div>
            <button class="btn btn-sm btn-primary my-2" id="add-bank-details" type="button">{{ __('Add new bank details')}}</button>
            <div class="row g-3 my-2">
              <div class="col-12 d-flex justify-content-between">
                <button class="btn btn-label-secondary btn-prev" type="button"> <i class="ti ti-arrow-left me-sm-1 me-0"></i>
                  <span class="align-middle d-sm-inline-block d-none">{{ __('Previous')}}</span>
                </button>
                <div class="d-flex">
                  <a href="{{ route('programs.vendors.manage', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-outline-danger mx-1">{{ __('Cancel') }}</a>
                  <button class="btn btn-primary btn-submit" type="submit">{{ __('Submit')}}</button>
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
