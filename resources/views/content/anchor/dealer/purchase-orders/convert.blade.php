@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Create Invoice')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
@endsection

@section('page-style')
<style>
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
@endsection

@section('page-script')
<script src="{{asset('assets/js/form-wizard-icons.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
<script>
  let items_count = 1
  let taxes_count = 1
  let items = $('#items')
  $(document.body).on('click', '#add-item', function(e) {
    e.preventDefault()
    let html = '<div class="col-sm-3" id="item-div-'+items_count+'">'
        html += '<label class="form-label" for="item">Item</label>'
        html += '<input type="text" name="item['+items_count+']" class="form-control" placeholder="Item" aria-label="item" />'
        html += '</div>'
        html += '<div class="col-sm-2" id="quantity-div-'+items_count+'">'
        html += '<label class="form-label" for="quantity">Quantity</label>'
        html += '<input type="text" name="quantity['+items_count+']" class="form-control" min="1" id="quantity-'+items_count+'" oninput="updateTotal('+items_count+')" onblur="calculateSubtotal()" placeholder="0" aria-label="quantity" required />'
        html += '</div>'
        html += '<div class="col-sm-2" id="unit-div-'+items_count+'">'
        html += '<label class="form-label" for="unit">Unit</label>'
        html += '<input type="text" name="unit['+items_count+']" class="form-control" placeholder="0" aria-label="unit" />'
        html += '</div>'
        html += '<div class="col-sm-2" id="price-per-quantity-div-'+items_count+'">'
        html += '<label class="form-label" for="price_per_quantity">Price Per Quantity</label>'
        html += '<input type="text" name="price_per_quantity['+items_count+']" class="form-control" min="1" id="price-'+items_count+'" placeholder="Ksh" oninput="updateTotal('+items_count+')" onblur="calculateSubtotal()" aria-label="price_per_quantity" required />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-div-'+items_count+'">'
        html += '<label class="form-label" for="total">Total</label>'
        html += '<input type="text" name="total['+items_count+']" class="form-control" placeholder="0" aria-label="total" id="total-'+items_count+'" disabled />'
        html += '</div>'
        html += '<div class="col-sm-4" id="discount-type-'+items_count+'">'
        html += '<label class="form-label" for="Discount Type">Discount Type</label>'
        html += '<select name="discount_type['+items_count+']" id="discount_type-'+items_count+'" class="form-control">'
        html += '<option value="">Select</option>'
        html += '<option value="percentage">Percentage</option>'
        html += '<option value="absolute">Absolute</option>'
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-4" id="discount-value-'+items_count+'">'
        html += '<label for="Discount Amount" class="form-label">Discount Value</label>'
        html += '<input type="text" class="form-control" id="discount_value-'+items_count+'" name="discount_value['+items_count+']" oninput="calculateDiscount()">'
        html += '</div>'
        html += '<div class="col-sm-4" id="total-discount-div-'+items_count+'">'
        html += '<label class="form-label" for="total">Discount Total</label>'
        html += '<input type="text" name="discount_total['+items_count+']" class="form-control" placeholder="0" aria-label="total" id="discount-total-'+items_count+'" disabled />'
        html += '</div>'
        html += '<div class="col-12 row g-3" id="taxes-section-'+items_count+'">'
        html += '<div class="col-sm-5" id="tax-name-section-'+items_count+'">'
        html += '<label class="form-label" for="tax">Tax</label>'
        html += '<select class="form-select" name="tax_name['+items_count+']['+taxes_count+']" id="tax-name-'+items_count+'-'+taxes_count+'" onchange="updateTaxes('+items_count+', '+taxes_count+')" onblur="calculateTaxes('+items_count+')">'
        html += '<option label=" "></option>'
        @foreach ($taxes as $key => $tax)
          html += '<option value="'+{!! json_encode($key) !!}+'" data-value="'+{!! json_encode($tax) !!}+'">'+{!! json_encode($key) !!}+'</option>'
        @endforeach
        html += '</select>'
        html += '</div>'
        html += '<div class="col-sm-5" id="tax-value-section-'+items_count+'">'
        html += '<label class="form-label" for="tax_value">Tax Value</label>'
        html += '<input type="number" name="tax_value['+items_count+']['+taxes_count+']" class="form-control" id="tax-value-'+items_count+'-'+taxes_count+'" placeholder="0" readonly />'
        html += '</div>'
        html += '<div class="col-sm-2 my-auto d-none" id="delete-tax-section-'+items_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger"></i>'
        html += '</div>'
        html += '</div>'
        html += '<div class="col-sm-12" id="total-tax-div-'+items_count+'">'
        html += '<label class="form-label" for="total">Tax Total</label>'
        html += '<input type="text" name="tax_total['+items_count+']" class="form-control w-25" placeholder="0" aria-label="total" id="tax-total-'+items_count+'" disabled />'
        html += '</div>'
        html += '<div class="col-12 row mt-2" id="add-taxes-section-'+items_count+'">'
        html += '<div class="col-2">'
        html += '<span class="d-flex align-items-center" id="add-tax-item" style="cursor: pointer" onclick="addTaxOnItem('+items_count+', '+taxes_count+')">'
        html += '<span class="badge bg-label-primary" style="border-radius: 100px;"><i class="ti ti-plus ti-sm"></i></span>'
        html += '<span class="mx-2">'
        html += 'Add Tax'
        html += '</span>'
        html += '</span>'
        html += '</div>'
        html += '</div>'
        html += '<div class="col-sm-12" id="delete-item-div-'+items_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem('+items_count+')"></i>'
        html += '</div>'
      $(html).appendTo(items);
      items_count += 1;
      taxes_count += 1;
  })

  function addTaxOnItem(item_index, index) {
    let html = ''
    let taxes_section = ''
    taxes_section = $('#taxes-section-' + item_index)
    html = '<div class="col-sm-5" id="tax-name-section-'+taxes_count+'">'
    html += '<label class="form-label" for="tax">Tax</label>'
    html += '<select name="tax_name['+item_index+']['+taxes_count+']" class="form-select" id="tax-name-'+item_index+'-'+taxes_count+'" onchange="updateTaxes('+item_index+', '+taxes_count+')" onblur="calculateTaxes('+item_index+')">'
    html += '<option label=""></option>'
    @foreach ($taxes as $key => $tax)
      html += '<option value="'+{!! json_encode($key) !!}+'" data-value="'+{!! json_encode($tax) !!}+'">'+{!! json_encode($key) !!}+'</option>'
    @endforeach
    html += '</select>'
    html += '</div>'
    html += '<div class="col-sm-5" id="tax-value-section-'+taxes_count+'">'
    html += '<label class="form-label" for="'+taxes_count+'">Value</label>'
    html += '<input type="number" name="tax_value['+item_index+']['+taxes_count+']" class="form-control" id="tax-value-'+item_index+'-'+taxes_count+'" placeholder="0" />'
    html += '</div>'
    html += '<div class="col-sm-2" id="delete-tax-section-'+taxes_count+'">'
    html += '<i class="ti ti-trash ti-sm text-danger mb-0" title="delete" style="cursor: pointer" onclick="deleteTax('+taxes_count+', '+index+')"></i>'
    html += '</div>'

    $(html).appendTo(taxes_section);
    taxes_count += 1;
  }

  function deleteTax(index, item_index) {
    $('#tax-name-section-'+item_index+'-'+index).remove();
    $('#tax-value-section-'+item_index+'-'+index).remove();
    $('#delete-tax-section-'+item_index+'-'+index).remove();
    taxes_count -= 1;
    calculateTaxes(item_index)
  }

  function deleteItem(index) {
    $('div').remove('#item-div-'+index+', #unit-div-'+index+', #quantity-div-'+index+', #price-per-quantity-div-'+index+', #total-div-'+index+', #taxes-section-'+index+', #total-tax-div-'+index+', #add-taxes-section-'+index+', #delete-item-div-'+index+', #discount-type-'+index+', #discount-value-'+index+'#total-discount-div-'+index);
    items_count -= 1;
    calculateInvoiceTotal()
  }

  function updateTotal(index) {
    let price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
    $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())

    let quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
    $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())

    if (price != 0 && quantity != 0) {
      let total_price = price * quantity
      $('#total-'+index).val(Number(total_price).toLocaleString())
    }
  }

  $('#submit-invoice').attr('disabled', 'disabled')

  $('#select-invoice').on('change', function(e) {
    let arr = e.target.value.split('\\')
    $('.invoice-attachment-message').removeClass('d-none')
    $('.invoice-attachment-message').removeClass('text-danger')
    $('.invoice-attachment-message').text(arr[arr.length - 1])
  })

  function calculateInvoiceTotal() {
    let invoice_total = 0;
    let sub_total = 0;
    let taxes = 0;
    let taxes_amount = 0;
    let discount_value = 0
    // Calculate values for each item
    for (let index = 0; index < items_count; index++) {
      let price = 0
      let quantity = 0
      let line_total = 0
      let line_total_tax = 0
      if (typeof($('#price-'+index).val()) == 'string' && $('#price-'+index).val() != 0) {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      }

      if (typeof($('#quantity-'+index).val()) == 'string' && $('#quantity-'+index).val() != 0) {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      }

      // Get items total
      if (price != 0 && quantity != 0) {
        line_total = Number(price * quantity)
        sub_total += Number(price * quantity)
      }

      // Get line taxes total
      for (let tax_index = 0; tax_index < taxes_count; tax_index++) {
        let tax = $('#tax-name-'+index+'-'+tax_index).find(':selected').data('value');
        if (tax !== undefined) {
          taxes_amount = Number(tax)

          line_total_tax += (tax / 100) * Number(line_total)

          taxes += (taxes_amount / 100) * Number(line_total)
        }
      }

      $('#taxes-invoice').text(new Intl.NumberFormat().format(taxes))

      // Get line discount total
      let type = $('#discount_type-'+index)
      let value = $('#discount_value-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')

      if (value != '' && Number(value) != 0) {
        if (type.val() == 'percentage') {
          discount_value += (Number(value) / 100) * Number(line_total)
        } else {
          discount_value += Number(value)
        }
      }

      $('#invoice-discount').text(new Intl.NumberFormat().format(discount_value))
    }

    invoice_total = sub_total + taxes - discount_value

    $('#total-invoice').text(new Intl.NumberFormat().format(invoice_total))
  }

  function calculateSubtotal() {
    sub_total = 0;
    $('[name^="total"]').each(index => {
      let price = 0
      if (typeof($('#price-'+index).val()) == 'string' && $('#price-'+index).val() != 0) {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = 0
      if (typeof($('#quantity-'+index).val()) == 'string' && $('#quantity-'+index).val() != 0) {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      if (price != 0 && quantity != 0) {
        sub_total += Number(price * quantity)
      }
    })
    $('#subtotal-invoice').text(new Intl.NumberFormat().format(sub_total))
    calculateInvoiceTotal()
  }

  function updateTaxes(item_index, index) {
    let tax_value = $('#tax-name-'+item_index+'-'+index).find(':selected').data('value');

    $('#tax-value-'+item_index+'-'+index).val(tax_value);
  }

  function calculateTaxes(item_index) {
    let taxes = 0;
    taxes_amount = 0;

    let line_total = 0
    let price = 0
    let quantity = 0

    if (typeof($('#price-'+item_index).val()) == 'string' && $('#price-'+item_index).val() != 0) {
      price = $('#price-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#price-'+item_index).val(Number($('#price-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    }

    if (typeof($('#quantity-'+item_index).val()) == 'string' && $('#quantity-'+item_index).val() != 0) {
      quantity = $('#quantity-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#quantity-'+item_index).val(Number($('#quantity-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    }

    line_total = Number(price * quantity)

    $('[name^="tax_name"]').each((index, item) => {
      let tax = $('#'+item.id).find(':selected').data('value');
      if (tax !== undefined) {
        taxes_amount += Number(tax)
      }
    })

    if (taxes_amount > 0) {
      $('#tax-total-'+item_index).val(Number((taxes_amount / 100) * Number(line_total)).toLocaleString())
      taxes += (taxes_amount / 100) * Number(line_total)
    } else {
      $('#tax-total-'+item_index).val(Number(0).toLocaleString())
      taxes += 0
    }

    calculateInvoiceTotal()
  }

  function calculateDiscount() {
    let taxes_value = 0
    let discount_value = 0
    let subtotal = 0
    taxes = 0;
    taxes_amount = 0;

    $('[name^="total"]').each(index => {
      let price = 0
      if (typeof($('#price-'+index).val()) == 'string' && $('#price-'+index).val() != 0) {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = 0
      if (typeof($('#quantity-'+index).val()) == 'string' && $('#quantity-'+index).val() != 0) {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      if (price != 0 && quantity != 0) {
        subtotal += Number(price * quantity)
      }
    })

    let line_total = 0
    $('[name^="tax_name"]').each((index, item) => {
      let price = 0
      if (typeof($('#price-'+index).val()) == 'string' && $('#price-'+index).val() != 0) {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = 0
      if (typeof($('#quantity-'+index).val()) == 'string' && $('#quantity-'+index).val() != 0) {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      line_total = Number(price * quantity)

      let tax = $('#tax-name-'+index).find(':selected').data('value');
      if (tax !== undefined) {
        taxes_amount += Number(tax)
      }

      let type = $('#discount_type-'+index)
      let value = $('#discount_value-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')

      if (Number(value) != 0) {
        if (type.val() == 'percentage') {
          $('#discount-total-'+index).val(Number((Number(value) / 100) * Number(line_total)).toLocaleString())
        } else {
          $('#discount-total-'+index).val(Number(value).toLocaleString())
        }
      }
    })

    if (taxes_amount > 0) {
      taxes += (taxes_amount / 100) * Number(line_total)
    } else {
      taxes += 0
    }

    $('[name^="discount_value"]').each(index => {
      let type = $('#discount_type-'+index)
      let value = $('#discount_value-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#discount_value-'+index).val(Number(value.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())

      if (Number(value) != 0) {
        if (type.val() == 'percentage') {
          discount_value += (Number(value) / 100) * Number(line_total)
        } else {
          discount_value += Number(value)
        }
      }
    })

    if (discount_value > 0) {
      $('#invoice-discount').text(new Intl.NumberFormat().format(discount_value))
      calculateInvoiceTotal()
    }
  }

  function selectInvoice() {
    $('#select-invoice').click()
  }

  $('#invoice_number').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length > 3) {
      $.ajax({url: `../../invoices/create/invoice-number/${$(this).val()}/check`,
        success: function (data) {
          $('#submit-invoice').removeAttr('disabled')
          $('#invoice_number_error').addClass('d-none')
        },
        error: function (err) {
          if (err.status == 400) {
            $('#invoice_number_error').removeClass('d-none')
            $('#submit-invoice').attr('disabled', 'disabled')
          }
        }
      })
    }
  })

  $('#anchor').on('change', function (e) {
    e.preventDefault();
    let url = invoice ? `../create/${$(this).val()}/programs` : `create/${$(this).val()}/programs`
    $.get(url, function (data, status) {
      let programOptions = document.getElementById('program_options')

      while (programOptions.options.length) {
        programOptions.remove(0)
      }

      programOptions.options.add(new Option('Select Account', ''))
      if (data.programs) {
        var i
        for (let i = 0; i < data.programs.length; i++) {
          var program = new Option(data.programs[i].payment_account_number, data.programs[i].program_id)
          programOptions.options.add(program)
        }
      }
    })
  })

  let purchase_order = {!! json_encode($purchase_order) !!}

  let default_payment_terms = 0

  default_payment_terms = purchase_order.invoice_payment_terms

  let due_date = ''

  $('#program_options').on('change', function () {
    $.ajax({
      url: `../program/${$(this).val()}`,
      headers: { 'Accept': 'application/json' },
      success: function (data) {
        default_payment_terms = data.program.default_payment_terms
        let invoice_date = $('.invoice-date').val()
        if (invoice_date) {
          due_date = moment($('.invoice-date').val()).add(default_payment_terms, 'days')

          $('.due-date').val(moment(due_date).format('YYYY-MM-DD'))
        }

        let mandatory_invoice_attachment = data.program.mandatory_invoice_attachment
        if (mandatory_invoice_attachment) {
          $('.invoice-attachment').attr('required', 'required');
          $('.invoice-attachment-message').removeClass('d-none')
        } else {
          $('.invoice-attachment').removeAttr('required')
          $('.invoice-attachment-message').addClass('d-none');
          $('#submit-invoice').removeAttr('disabled')
        }
      }
    })
  })

  $('.invoice-date').on('change', function () {
    due_date = moment($('.invoice-date').val()).add(default_payment_terms, 'days')

    $('.due-date').val(moment(due_date).format('YYYY-MM-DD'))
  })

  $('#invoice-form').on('submit', function (e) {
    $('#submit-invoice').attr('disabled', 'disabled');
  })

  $(document).ready(function () {
    calculateSubtotal()
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
  $('#invoice_date').attr("max", min_day);
  $('#due_date').attr("min", min_day);
</script>
@endsection

@section('content')
<h4 class="fw-light">
  {{ __('Create Invoice')}}
</h4>
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
              <span class="bs-stepper-title">{{ __('Invoice Details')}}</span>
              <span class="bs-stepper-subtitle">{{ __('PO No, Buyer, A/C')}}</span>
            </span>
          </button>
        </div>
        <div class="line d-none"></div>
        <div class="step d-none" data-target="#personal-info-vertical">
          <button type="button" class="step-trigger">
            <span class="bs-stepper-circle">
              <i class="ti ti-user"></i>
            </span>
            <span class="bs-stepper-label">
              <span class="bs-stepper-title">{{ __('Drafts')}}</span>
            </span>
          </button>
        </div>
      </div>
      <div class="bs-stepper-content">
        <form method="POST" action="{{ route('anchor.factoring-invoices-store') }}" enctype="multipart/form-data" id="invoice-form">
          @csrf
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="buyer">
                  {{ __('Buyer')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" name="" class="form-control" value="{{ $purchase_order->anchor->name }}" readonly />
                <input type="hidden" name="buyer" value="{{ $purchase_order->anchor->id }}" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="program_id">
                  {{ __('Program')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="program_options" name="program_id" required>
                  <option label=" ">{{ __('Select Program') }}</option>
                  @foreach ($programs as $program)
                    <option value="{{ $program->program->id }}" data-program="{{ $program->program }}">{{ $program->payment_account_number }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6 form-group">
                <label class="form-label" for="invoice_number">
                  {{ __('Invoice No')}}.
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="invoice_number" name="invoice_number" class="form-control" placeholder="Invoice Number" aria-label="invoice_number" autocomplete="off" value="{{ old('invoice_number') }}" required />
                <span id="invoice_number_error" class="text-danger d-none">{{ __('Invalid Invoice Number')}}</span>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="associated_po">{{ __('Associated PO')}}</label>
                <input type="text" class="form-control" value="{{ $purchase_order->purchase_order_number }}" readonly />
                <input type="hidden" value="{{ $purchase_order->id }}" name="purchase_order" readonly />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Invoice Date')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control invoice-date" name="invoice_date" type="date" value="{{ old('invoice_date') }}" id="invoice_date" required />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Due Date')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control due-date" name="due_date" type="date" value="{{ old('due_date') }}" id="due_date" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="currency">
                  {{ __('Currency')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="select2" name="currency" id="currency" required>
                  <option label=" "></option>
                  @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @if(($purchase_order->currency == $currency->code) || (old('currency') == $currency->code)) selected @endif>{{ $currency->code }} ({{ $currency->name }})</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6">
                <label for="remarks" class="form-label">{{ __('Remarks')}}</label>
                <textarea class="form-control" name="remarks" id="remarks" rows="3"> {{ old('remarks') }}</textarea>
              </div>
              <hr>
              <div class="col-12 g-3 row" id="items">
                @if ($purchase_order)
                  @foreach ($purchase_order->purchaseOrderItems as $key => $purchase_order_item)
                    <div class="col-sm-3">
                      <label class="form-label" for="item">
                        {{ __('Item')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="item[{{ $key }}]" value="{{ $purchase_order_item->item }}" class="form-control" placeholder="Item" aria-label="item" required />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="quantity">
                        {{ __('Quantity')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="quantity[{{ $key }}]" value="{{ number_format($purchase_order_item->quantity) }}" class="form-control" placeholder="0" id="quantity-{{ $key }}" oninput="updateTotal({{ $key }})" onblur="calculateSubtotal()" aria-label="quantity" required />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="unit">
                        {{ __('Unit')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="unit[{{ $key }}]" value="{{ $purchase_order_item->unit }}" class="form-control" placeholder="0" aria-label="unit" required />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="price_per_quantity">
                        {{ __('Price Per Quantity')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="price_per_quantity[{{ $key }}]" value="{{ number_format($purchase_order_item->price_per_quantity) }}" class="form-control" id="price-{{ $key }}" placeholder="Ksh" oninput="updateTotal({{ $key }})" onblur="calculateSubtotal()" aria-label="price_per_quantity" required />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="total">{{ __('Total')}}</label>
                      <input type="text" name="total[{{ $key }}]" value="{{ number_format($purchase_order_item->total) }}" class="form-control" placeholder="0" aria-label="total" id="total-{{ $key }}" disabled />
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="Discount Type">{{ __('Discount Type')}}</label>
                      <select name="discount_type[]" id="discount_type-{{ $key }}" class="form-control">
                        <option value="">{{ __('Select')}}</option>
                        <option value="percentage">{{ __('Percentage')}}</option>
                        <option value="absolute">{{ __('Absolute')}}</option>
                      </select>
                    </div>
                    <div class="col-sm-4">
                      <label for="Discount Amount" class="form-label">{{ __('Discount Value')}}</label>
                      <input type="text" class="form-control" id="discount_value-{{ $key }}" name="discount_value[{{ $key }}]" oninput="calculateDiscount()">
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="total">{{ __('Discount Total')}}</label>
                      <input type="text" name="discount_total[{{ $key }}]" class="form-control" placeholder="0" aria-label="total" id="discount-total-{{ $key }}" disabled />
                    </div>
                    <div class="col-12 row g-3" id="taxes-section-{{ $key }}">
                      <div class="col-sm-5" id="tax-name-section-{{ $key }}">
                        <label class="form-label" for="tax">{{ __('Tax')}}</label>
                        <select class="form-select" name="tax_name[{{ $key }}][0]" id="tax-name-0-0" onchange="updateTaxes(0, 0)" onblur="calculateTaxes({{ $key }})">
                          <option label=" "></option>
                          @foreach ($taxes as $key => $tax)
                            <option value="{{ $key }}" data-value="{{ $tax }}">{{ $key }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-sm-5" id="tax-value-section-{{ $key }}">
                        <label class="form-label" for="tax_value">{{ __('Tax Value')}}</label>
                        <input type="number" name="tax_value[{{ $key }}][0]" class="form-control" id="tax-value-{{ $key }}-0" placeholder="0" readonly />
                      </div>
                      <div class="col-sm-2 my-auto d-none" id="delete-tax-section-{{ $key }}">
                        <i class="ti ti-trash ti-sm text-danger"></i>
                      </div>
                    </div>
                    <div class="col-12 row mt-2">
                      <div class="col-2">
                        <span class="d-flex align-items-center" id="add-tax-item" style="cursor: pointer" onclick="addTaxOnItem(0, 0)">
                          <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                          <span class="mx-2">
                            {{ __('Add Tax')}}
                          </span>
                        </span>
                      </div>
                    </div>
                  @endforeach
                @endif
              </div>
              <div class="col-12">
                <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                  <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                  <span class="mx-2">
                    {{ __('Add Item')}}
                  </span>
                </span>
              </div>
              <hr>
              <div class="col-12 d-flex justify-content-end">
                <h6>{{ __('Invoice Subtotal')}}:</h6>
                <h5 class="mx-4" id="subtotal-invoice">0</h5>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <h6>{{ __('Tax')}}:</h6>
                <h5 class="mx-4" id="taxes-invoice">0</h5>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <h6>{{ __('Discount')}}:</h6>
                <h5 class="mx-4" id="invoice-discount">0</h5>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <h6>{{ __('Invoice Total')}}:</h6>
                <h5 class="mx-4" id="total-invoice">0</h5>
              </div>
              <div class="col-12 d-flex justify-content-between">
                <div class="d-flex flex-column">
                  <button type="button" class="btn btn-label-primary" onclick="event.preventDefault(); document.getElementById('select-invoice').click();">{{ __('Add Attachment')}}</button>
                  <input type="file" class="d-none" name="invoice" id="select-invoice" accept=".pdf,.jpg">
                  <span class="text-danger text-sm invoice-attachment-message d-none">{{ __('Attachment is required')}}</span>
                </div>
                <div class="d-flex" style="height: fit-content">
                  <button class="btn btn-label-secondary mx-1">
                    <span class="align-middle d-sm-inline-block d-none">{{ __('Cancel')}}</span>
                  </button>
                  <button class="btn btn-primary" id="submit-invoice"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit')}}</span></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Personal Info -->
          <div id="personal-info-vertical" class="content d-none">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>{{ __('Buyer')}}</th>
                    <th>{{ __('Program')}}</th>
                    <th>{{ __('Invoice No')}}.</th>
                    <th>{{ __('Actions')}}</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Icons Wizard -->
</div>
@endsection
