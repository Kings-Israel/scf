@extends('layouts/anchorDealerLayoutMaster')

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
    $('div').remove('#item-div-'+index+', #unit-div-'+index+', #quantity-div-'+index+', #price-per-quantity-div-'+index+', #total-div-'+index+', #taxes-section-'+index+', #total-tax-div-'+index+', #add-taxes-section-'+index+', #delete-item-div-'+index+', #discount-type-'+index+', #discount-value-'+index+', #total-discount-div-'+index);
    items_count -= 1;
    calculateSubtotal()
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

  let invoice = {!! json_encode($invoice) !!}
  if (invoice) {
    $(document).ready(function () {
      setTimeout(() => {
        calculateInvoiceTotal()
        $('#taxes-invoice').text(Number(invoice.total_invoice_taxes).toLocaleString())
        $('#invoice-discount').text(Number(invoice.total_invoice_discount).toLocaleString())
        $('#total-invoice').text(Number(invoice.total - invoice.total_invoice_discount + invoice.total_invoice_taxes).toLocaleString())
      }, 1000);
    })
  }

  let attachment_required = {!! json_encode($attachment_required) !!}
  if (attachment_required) {
    $('.invoice-attachment').attr('required', 'required');
    $('.invoice-attachment-message').removeClass('d-none');
    $('#submit-invoice').attr('disabled', 'disabled');
  } else {
    $('.invoice-attachment').removeAttr('required')
    $('.invoice-attachment-message').addClass('d-none')
    $('#submit-invoice').removeAttr('disabled')
  }

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
    let discount_value = 0;
    let discount_total = 0;
    // Calculate values for each item
    for (let index = 0; index < items_count; index++) {
      let price = 0
      let quantity = 0
      let line_total = 0
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

      if ($('#discount_value-'+index).val()) {
        // Get line discount total
        let type = $('#discount_type-'+index)
        let value = $('#discount_value-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')

        if (value != '' && Number(value) != 0) {
          if (type.val() == 'percentage') {
            discount_value = (Number(value) / 100) * Number(line_total)
          } else {
            discount_value = Number(value)
          }
          discount_total += discount_value
        }
      }

      $('#invoice-discount').text(new Intl.NumberFormat().format(discount_total))

      // Get line taxes total
      for (let tax_index = 0; tax_index < taxes_count; tax_index++) {
        let tax = $('#tax-name-'+index+'-'+tax_index).find(':selected').data('value');
        if (tax !== undefined) {
          taxes_amount = Number(tax)
          taxes += (taxes_amount / 100) * Number(line_total - discount_value)
        }
      }

      $('#taxes-invoice').text(new Intl.NumberFormat().format(taxes))
    }

    invoice_total = sub_total + taxes - discount_total

    $('#total-invoice').text(new Intl.NumberFormat().format(invoice_total))
  }

  function calculateSubtotal() {
    sub_total = 0;
    $('[name^="total"]').each(index => {
      let price = 0
      if (typeof($('#price-'+index).val()) == 'string') {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = 0
      if (typeof($('#quantity-'+index).val()) == 'string') {
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
    let discount_value = 0

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
      // let tax = $('#'+item.id).find(':selected').data('value');
      let tax = $('#tax-name-'+item_index+'-'+index).find(':selected').data('value');
      if (tax !== undefined) {
        taxes_amount += Number(tax)
      }
    })

    let type = $('#discount_type-'+item_index)
    let value = $('#discount_value-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')

    if (Number(value) != 0) {
      if (type.val() == 'percentage') {
        discount_value += Number((Number(value) / 100) * Number(line_total))
      } else {
        discount_value += Number(value)
      }
    }

    if (taxes_amount > 0) {
      $('#tax-total-'+item_index).val(Number((taxes_amount / 100) * Number(line_total - discount_value)).toLocaleString())
      taxes += (taxes_amount / 100) * Number(line_total - discount_value)
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
      if (typeof($('#price-'+index).val()) == 'string') {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = 0
      if (typeof($('#quantity-'+index).val()) == 'string') {
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
      if (typeof($('#price-'+index).val()) == 'string') {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = 0
      if (typeof($('#quantity-'+index).val()) == 'string') {
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
      $('#total-invoice').text(new Intl.NumberFormat().format(subtotal + taxes - discount_value))
    }
  }

  function selectInvoice() {
    $('#select-invoice').click()
  }

  $('#invoice_number').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length > 3) {
      $.ajax({url: `create/invoice-number/${$(this).val()}/check`,
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

  $('#buyer').on('change', function (e) {
    e.preventDefault();
    if ($(this).val() != '') {
      $('#invoice_number').removeAttr('readonly')
    } else {
      $('#invoice_number').val('')
      $('#invoice_number').attr('readonly', 'readonly')
    }
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

  let default_payment_terms = 0

  $('#program_options').on('change', function () {
    let buyer = $('#buyer').val()
    $.ajax({
      url: `../programs/${$(this).val()}/${buyer}/details`,
      headers: { 'Accept': 'application/json' },
      success: function (data) {
        default_payment_terms = data.payment_terms
        let invoice_date = $('.invoice-date').val()
        if (invoice_date) {
          let due_date = moment($('.invoice-date').val()).add(default_payment_terms, 'days')

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

        let creditToOptions = document.getElementById('credit_to_options')

        while (creditToOptions.options.length) {
          creditToOptions.remove(0)
        }

        creditToOptions.options.add(new Option('Credit To', ''))
        if (data.bank_accounts.length > 0) {
          var i
          if (data.bank_accounts.length > 1) {
            for (let i = 0; i < data.bank_accounts.length; i++) {
              var account = new Option(data.bank_accounts[i].account_number, data.bank_accounts[i].account_number)
              creditToOptions.options.add(account)
            }
          } else {
            for (let i = 0; i < data.bank_accounts.length; i++) {
              var account = new Option(data.bank_accounts[i].account_number, data.bank_accounts[i].account_number)
              account.setAttribute('selected', true)
              creditToOptions.options.add(account)
            }
          }
        }
      }
    })
  })

  $('.invoice-date').on('change', function () {
    let due_date = moment($('.invoice-date').val()).add(default_payment_terms, 'days')

    $('.due-date').val(moment(due_date).format('YYYY-MM-DD'))
  })

  $('#invoice-form').on('submit', function (e) {
    $('#submit-invoice').text('Processing...');
    $('#submit-invoice').attr('disabled', 'disabled');
  })

  $('#purchase_order').on('change', function () {
    let po_val = $(this).val()
    if (po_val != '' && po_val != 'Select Purchase Order') {
      $.get(`../purchase-orders/${po_val}/show`, function (data, status) {
        for (let index = 0; index <= items_count; index++) {
          deleteItem(index);
        }
        $('#buyer').val(data.purchase_order.anchor_id)

        program_vendor_configurations = data.program_vendor_configurations
        let programOptions = document.getElementById('program_options')

        while (programOptions.options.length) {
          programOptions.remove(0)
        }

        programOptions.options.add(new Option('Select Account', ''))
        if (program_vendor_configurations) {
          var i
          for (let i = 0; i < program_vendor_configurations.length; i++) {
            var program = new Option(program_vendor_configurations[i].payment_account_number, program_vendor_configurations[i].program_id)
            programOptions.options.add(program)
          }
        }

        items_count = 0
        data.purchase_order.purchase_order_items.forEach((item, index) => {
          let html = '<div class="col-sm-3" id="item-div-'+items_count+'">'
              html += '<label class="form-label" for="item">Item</label>'
              html += '<input type="text" name="item['+items_count+']" class="form-control" placeholder="Item" aria-label="item" value="'+item.item+'" />'
              html += '</div>'
              html += '<div class="col-sm-2" id="quantity-div-'+items_count+'">'
              html += '<label class="form-label" for="quantity">Quantity</label>'
              html += '<input type="text" name="quantity['+items_count+']" value="'+Number(item.quantity).toLocaleString()+'" class="form-control" min="1" id="quantity-'+items_count+'" oninput="updateTotal('+items_count+')" onblur="calculateSubtotal()" placeholder="0" aria-label="quantity" required />'
              html += '</div>'
              html += '<div class="col-sm-2" id="unit-div-'+items_count+'">'
              html += '<label class="form-label" for="unit">Unit</label>'
              html += '<input type="text" name="unit['+items_count+']" value="'+item.unit+'" class="form-control" placeholder="0" aria-label="unit" />'
              html += '</div>'
              html += '<div class="col-sm-2" id="price-per-quantity-div-'+items_count+'">'
              html += '<label class="form-label" for="price_per_quantity">Price Per Quantity</label>'
              html += '<input type="text" name="price_per_quantity['+items_count+']" value="'+Number(item.price_per_quantity).toLocaleString()+'" class="form-control" min="1" id="price-'+items_count+'" placeholder="Ksh" oninput="updateTotal('+items_count+')" onblur="calculateSubtotal()" aria-label="price_per_quantity" required />'
              html += '</div>'
              html += '<div class="col-sm-3" id="total-div-'+items_count+'">'
              html += '<label class="form-label" for="total">Total</label>'
              html += '<input type="text" name="total['+items_count+']" value="'+Number(item.total).toLocaleString()+'" class="form-control" placeholder="0" aria-label="total" id="total-'+items_count+'" disabled />'
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

          $(html).prependTo(items);
          items_count += 1;
          taxes_count += 1
        })

        // Remove required attribute from current invoice items
        $('#item-0').removeAttr('required', false)
        $('#unit-0').removeAttr('required', false)
        $('#price-0').removeAttr('required', false)
        $('#quantity-0').removeAttr('required', false)

        calculateSubtotal()
      })
    } else {
      let purchase_order_items_count = items_count
      for (let index = 0; index <= purchase_order_items_count; index++) {
        deleteItem(index);
      }
      items_count = 0
      taxes_count = 0

      let html = '<div class="col-sm-3" id="item-div-'+items_count+'">'
          html += '<label class="form-label" for="item">Item</label>'
          html += '<input type="text" name="item['+items_count+']" class="form-control" placeholder="Item" aria-label="item" autocomplete="off" />'
          html += '</div>'
          html += '<div class="col-sm-2" id="quantity-div-'+items_count+'">'
          html += '<label class="form-label" for="quantity">Quantity</label>'
          html += '<input type="text" name="quantity['+items_count+']" class="form-control" min="1" autocomplete="off" id="quantity-'+items_count+'" oninput="updateTotal('+items_count+')" onblur="calculateSubtotal()" placeholder="0" aria-label="quantity" required />'
          html += '</div>'
          html += '<div class="col-sm-2" id="unit-div-'+items_count+'">'
          html += '<label class="form-label" for="unit">Unit</label>'
          html += '<input type="text" name="unit['+items_count+']" class="form-control" placeholder="0" aria-label="unit" autocomplete="off" />'
          html += '</div>'
          html += '<div class="col-sm-2" id="price-per-quantity-div-'+items_count+'">'
          html += '<label class="form-label" for="price_per_quantity">Price Per Quantity</label>'
          html += '<input type="text" name="price_per_quantity['+items_count+']" class="form-control" autocomplete="off" min="1" id="price-'+items_count+'" placeholder="Ksh" oninput="updateTotal('+items_count+')" onblur="calculateSubtotal()" aria-label="price_per_quantity" required />'
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

      $(html).prependTo(items);

      // Add required attribute from current invoice items
      $('#item-0').removeAttr('required', true)
      $('#unit-0').removeAttr('required', true)
      $('#price-0').removeAttr('required', true)
      $('#quantity-0').removeAttr('required', true)

      calculateSubtotal()
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
  $('#invoice_date').attr("min", min_day);
  $('#due_date').attr("min", min_day);
</script>
<script>
  var uploadedDocumentMap = {}
  Dropzone.options.documentDropzone = {
    url: '{{ route('anchor.invoices.attachment.store') }}',
    maxFilesize: 4, // MB
    acceptedFiles: '.jpeg, .jpg, .pdf',
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="invoice[]" value="' + response.name + '">')
      uploadedDocumentMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedDocumentMap[file.name]
      }
      $('form').find('input[name="invoice[]"][value="' + name + '"]').remove()
    }
  }
</script>
@endsection

@section('content')
<h4 class="fw-light">
  {{ __('Create Invoice')}}
</h4>
<!-- Default -->
<div class="row">
  <!-- Vertical Icons Wizard -->
  <div class="col-12">
    <div class="bs-stepper vertical wizard-vertical-icons-example">
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
        <form method="POST" action="{{ route('anchor.dealer-invoices-drawdown-store') }}" enctype="multipart/form-data" id="invoice-form">
          @csrf
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="buyer">
                  {{ __('Buyer')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select form-control" id="buyer" name="buyer" required>
                  <option label=" " value="">{{ __('Select Buyer') }}</option>
                  @foreach ($buyers as $buyer)
                    <option value="{{ $buyer->id }}" @if($invoice && $invoice->buyer->id == $buyer->id || old('buyer') == $buyer->id) selected @endif>{{ $buyer->name }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('buyer')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="program_id">
                  {{ __('Payment/OD Account')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="program_options" name="program_id" required>
                  @if ($invoice)
                    <option value="{{ $invoice->program_id }}" data-program="{{ $invoice->program }}" selected>{{ $vendor_configuration->payment_account_number }}</option>
                  @endif
                  @if (old('program_id'))
                    <option value="{{ old('program_id') }}" selected></option>
                  @endif
                </select>
                <x-input-error :messages="$errors->get('program_id')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice_number">
                  {{ __('Invoice No')}}.
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="invoice_number" name="invoice_number" class="form-control" placeholder="Invoice Number" aria-label="invoice_number" value="{{ old('invoice_number', $invoice ? $invoice->invoice_number : '') }}" required @if(!$invoice) readonly @endif />
                <span id="invoice_number_error" class="text-danger d-none">{{ __('Invoice Number already in use')}}</span>
                <x-input-error :messages="$errors->get('invoice_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="associated_po">{{ __('Associated PO')}}</label>
                <select class="form-control" id="purchase_order" name="purchase_order">
                  <option label=" "></option>
                  @foreach ($purchase_orders as $purchase_order)
                    <option value="{{ $purchase_order->id }}" @if($invoice && $invoice->purchase_order_id == $purchase_order->id || old('purchase_order') == $purchase_order->id) selected @endif>{{ $purchase_order->purchase_order_number }}</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('purchase_order')" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Invoice Date')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control invoice-date" name="invoice_date" type="date" value="{{ old('invoice_date', $invoice ? $invoice->invoice_date : '') }}" id="invoice_date" required />
                <x-input-error :messages="$errors->get('invoice_date')" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Due Date')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control due-date" name="due_date" type="date" value="{{ old('due_date', $invoice ? $invoice->due_date : '') }}" id="due_date" required />
                <x-input-error :messages="$errors->get('due_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="currency">
                  {{ __('Currency')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="select2" name="currency" id="currency" required>
                  <option label=" "></option>
                  @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @if(($invoice && $invoice->currency == $currency->code) || (old('currency') == $currency->code)) selected @endif>{{ $currency->code }} ({{ $currency->name }})</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" />
              </div>
              <div class="col-12">
                <label for="remarks" class="form-label">{{ __('Remarks')}}</label>
                <textarea class="form-control" name="remarks" id="remarks" rows="3"> {{ old('remarks', $invoice ? $invoice->remarks : '') }}</textarea>
                <x-input-error :messages="$errors->get('remarks')" />
              </div>
              <hr>
              <div class="col-12 g-3 row" id="items">
                @if ($invoice)
                  @foreach ($invoice->invoiceItems as $key => $invoice_item)
                    <div class="col-sm-3">
                      <label class="form-label" for="item">
                        {{ __('Item')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="item[{{ $key }}]" value="{{ $invoice_item->item }}" class="form-control" placeholder="Item" aria-label="item" required/>
                      <x-input-error :messages="$errors->get('item.'.$key)" />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="quantity">
                        {{ __('Quantity')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="quantity[{{ $key }}]" value="{{ $invoice_item->quantity }}" class="form-control" placeholder="0" id="quantity-{{ $key }}" oninput="updateTotal({{ $key }})" onblur="calculateSubtotal()" aria-label="quantity" required />
                      <x-input-error :messages="$errors->get('quantity.'.$key)" />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="unit">
                        {{ __('Unit')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="unit[{{ $key }}]" value="{{ $invoice_item->unit }}" class="form-control" placeholder="0" aria-label="unit" required />
                      <x-input-error :messages="$errors->get('unit.'.$key)" />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="price_per_quantity">
                        {{ __('Price Per Quantity')}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" name="price_per_quantity[{{ $key }}]" value="{{ $invoice_item->price_per_quantity }}" class="form-control" id="price-{{ $key }}" placeholder="Ksh" oninput="updateTotal({{ $key }})" onblur="calculateSubtotal()" aria-label="price_per_quantity" />
                      <x-input-error :messages="$errors->get('price_per_quantity.'.$key)" />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="total">{{ __('Total')}}</label>
                      <input type="text" name="total[{{ $key }}]" value="{{ $invoice_item->calculateTotal() }}" class="form-control" placeholder="0" aria-label="total" id="total-{{ $key }}" disabled />
                      <x-input-error :messages="$errors->get('total.'.$key)" />
                    </div>
                    @if ($invoice_item->invoiceDiscount)
                      <div class="col-sm-4">
                        <label class="form-label" for="Discount Type">{{ __('Discount Type')}}</label>
                        <select name="discount_type[{{ $key }}]" id="discount_type-0" class="form-control">
                          <option value="">{{ __('Select')}}</option>
                          <option value="percentage" @if($invoice_item->invoiceDiscount->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                          <option value="absolute" @if($invoice_item->invoiceDiscount->type == 'absolute') selected @endif>{{ __('Absolute')}}</option>
                        </select>
                      </div>
                      <div class="col-sm-4">
                        <label for="Discount Amount" class="form-label">{{ __('Discount Value')}}</label>
                        <input type="text" class="form-control" id="discount_value-{{ $key }}" name="discount_value[{{ $key }}]" value="{{ $invoice_item->invoiceDiscount->value }}" oninput="calculateDiscount()">
                      </div>
                      <div class="col-sm-4">
                        <label class="form-label" for="total">{{ __('Total')}}</label>
                        <input type="text" name="discount_total[{{ $key }}]" class="form-control" placeholder="0" aria-label="total" id="discount-total-{{ $key }}" disabled />
                      </div>
                    @else
                      <div class="col-sm-4">
                        <label class="form-label" for="Discount Type">{{ __('Discount Type')}}</label>
                        <select name="discount_type[{{ $key }}]" id="discount_type-{{ $key }}" class="form-control">
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
                        <label class="form-label" for="total">{{ __('Total')}}</label>
                        <input type="text" name="discount_total[{{ $key }}]" class="form-control" placeholder="0" aria-label="total" id="discount-total-{{ $key }}" disabled />
                      </div>
                    @endif
                    <div class="col-12 row" id="taxes-section-{{ $key }}">
                      @if ($invoice_item->invoiceTaxes)
                        @foreach ($invoice_item->invoiceTaxes as $tax_key => $invoice_item_tax)
                          @if ($loop->first)
                            <div class="col-sm-5">
                              <label class="form-label" for="tax">{{ __('Tax')}}</label>
                              <select class="form-select" name="tax_name[{{ $key }}][{{ $tax_key }}]" id="tax-name-{{ $key }}-{{ $tax_key }}" onchange="updateTaxes({{ $key }}, {{ $tax_key }})" onblur="calculateTaxes({{ $key }})">
                                <option label=" "></option>
                                @foreach ($taxes as $name => $tax)
                                  <option value="{{ $name }}" @if($invoice_item_tax->name == $name) selected @endif data-value="{{ $tax }}">{{ $name }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-sm-5">
                              <label class="form-label" for="tax_value">{{ __('Tax Value')}}</label>
                              <input type="number" name="tax_value[{{ $key }}][{{ $tax_key }}]" value="{{ $invoice_item_tax->percentage }}" class="form-control" id="tax-value-{{ $key }}-{{ $tax_key }}" placeholder="0" readonly />
                            </div>
                            <div class="col-sm-2"></div>
                          @else
                            <div class="col-sm-5" id="tax-name-section-{{ $key }}-{{ $tax_key }}">
                              <label class="form-label" for="tax">{{ __('Tax')}}</label>
                              <select class="form-select" name="tax_name[{{ $key }}][{{ $tax_key }}]" id="tax-name-{{ $key }}-{{ $tax_key }}" onchange="updateTaxes({{ $key }}, {{ $tax_key }})" onblur="calculateTaxes({{ $key }})">
                                <option label=" "></option>
                                @foreach ($taxes as $name => $tax)
                                  <option value="{{ $name }}" @if($invoice_item_tax->name == $name) selected @endif data-value="{{ $tax }}">{{ $name }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-sm-5" id="tax-value-section-{{ $key }}-{{ $tax_key }}">
                              <label class="form-label" for="tax_value">{{ __('Tax Value')}}</label>
                              <input type="number" name="tax_value[{{ $key }}][{{ $tax_key }}]" value="{{ $invoice_item_tax->percentage }}" class="form-control" id="tax-value-{{ $key }}-{{ $tax_key }}" placeholder="0" readonly />
                            </div>
                            <div class="col-sm-2 my-auto" id="delete-tax-section-{{ $key }}-{{ $tax_key }}">
                              <i class="ti ti-trash ti-sm text-danger mb-0" title="delete" style="cursor: pointer" onclick="deleteTax({!! $tax_key !!}, {!! $key !!})"></i>
                            </div>
                          @endif
                        @endforeach
                        <div class="col-sm-12" id="total-tax-div-0">
                          <label class="form-label" for="total">Tax Total</label>
                          <input type="text" name="tax_total[{{ $key }}]" class="form-control w-25" placeholder="0" aria-label="total" id="tax-total-{{ $key }}" disabled />
                        </div>
                      @endif
                    </div>
                  @endforeach
                @else
                  <div class="col-sm-3" id="item-div-0">
                    <label class="form-label" for="item">
                      {{ __('Item')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="item[]" class="form-control" placeholder="Item" id="item-0" aria-label="item" autocomplete="off" required />
                  </div>
                  <div class="col-sm-2" id="quantity-div-0">
                    <label class="form-label" for="quantity">
                      {{ __('Quantity')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="quantity[]" class="form-control" placeholder="0" id="quantity-0" autocomplete="off" oninput="updateTotal(0)" onblur="calculateSubtotal()" aria-label="quantity" required />
                  </div>
                  <div class="col-sm-2" id="unit-div-0">
                    <label class="form-label" for="unit">
                      {{ __('Unit')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="unit[]" class="form-control" placeholder="0" id="unit-0" autocomplete="off" aria-label="unit" required />
                  </div>
                  <div class="col-sm-2" id="price-per-quantity-div-0">
                    <label class="form-label" for="price_per_quantity">
                      {{ __('Price Per Quantity')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="price_per_quantity[]" class="form-control" id="price-0" autocomplete="off" placeholder="Ksh" oninput="updateTotal(0)" onblur="calculateSubtotal()" aria-label="price_per_quantity" required />
                  </div>
                  <div class="col-sm-3" id="total-div-0">
                    <label class="form-label" for="total">{{ __('Total')}}</label>
                    <input type="text" name="total[]" class="form-control" placeholder="0" aria-label="total" id="total-0" disabled />
                  </div>
                  <div class="col-sm-4" id="discount-type-0">
                    <label class="form-label" for="Discount Type">{{ __('Discount Type')}}</label>
                    <select name="discount_type[]" id="discount_type-0" class="form-control">
                      <option value="">{{ __('Select')}}</option>
                      <option value="percentage">{{ __('Percentage')}}</option>
                      <option value="absolute">{{ __('Absolute')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-4" id="discount-value-0">
                    <label for="Discount Amount" class="form-label">{{ __('Discount Value')}}</label>
                    <input type="text" class="form-control" id="discount_value-0" name="discount_value[]" oninput="calculateDiscount()">
                  </div>
                  <div class="col-sm-4" id="total-discount-div-0">
                    <label class="form-label" for="total">{{ __('Discount Total')}}</label>
                    <input type="text" name="discount_total[]" class="form-control" placeholder="0" aria-label="total" id="discount-total-0" disabled />
                  </div>
                  <div class="col-12 row g-3" id="taxes-section-0">
                    <div class="col-sm-5" id="tax-name-section-0">
                      <label class="form-label" for="tax">{{ __('Tax')}}</label>
                      <select class="form-select" name="tax_name[0][0]" id="tax-name-0-0" onchange="updateTaxes(0, 0)" onblur="calculateTaxes(0)">
                        <option label=" "></option>
                        @foreach ($taxes as $key => $tax)
                          <option value="{{ $key }}" data-value="{{ $tax }}">{{ $key }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-5" id="tax-value-section-0">
                      <label class="form-label" for="tax_value">{{ __('Tax Value')}}</label>
                      <input type="number" name="tax_value[0][0]" class="form-control" id="tax-value-0-0" placeholder="0" readonly />
                    </div>
                    <div class="col-sm-2 my-auto d-none" id="delete-tax-section-0">
                      <i class="ti ti-trash ti-sm text-danger"></i>
                    </div>
                  </div>
                  <div class="col-sm-12" id="total-tax-div-0">
                    <label class="form-label" for="total">{{ __('Tax Total')}}</label>
                    <input type="text" name="tax_total[]" class="form-control w-25" placeholder="0" aria-label="total" id="tax-total-0" disabled />
                  </div>
                  <div class="col-12 row mt-2" id="add-taxes-section-0">
                    <div class="col-2">
                      <span class="d-flex align-items-center" id="add-tax-item" style="cursor: pointer" onclick="addTaxOnItem(0, 0)">
                        <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                        <span class="mx-2">
                          {{ __('Add Tax')}}
                        </span>
                      </span>
                    </div>
                  </div>
                @endif
              </div>
              <hr>
              <div class="col-12 row mt-2">
                <div class="col-2">
                  <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                    <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                    <span class="mx-2">
                      {{ __('Add Invoice Item')}}
                    </span>
                  </span>
                </div>
              </div>
              <hr>
              <div class="col-12">
                <div class="row">
                  <div class="col-12 d-flex justify-content-end">
                    <h6>{{ __('Invoice Subtotal')}}:</h6>
                    <h5 class="mx-4" id="subtotal-invoice">0</h5>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <h6>{{ __('Discount')}}:</h6>
                    <h5 class="mx-4" id="invoice-discount">0</h5>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <h6>{{ __('Tax')}}:</h6>
                    <h5 class="mx-4" id="taxes-invoice">0</h5>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <h6>{{ __('Invoice Total')}}:</h6>
                    <h5 class="mx-4" id="total-invoice">0</h5>
                  </div>
                  <div class="col-sm-12 col-md-6"></div>
                  <div class="col-sm-12 col-md-6">
                    <label class="form-label d-flex justify-content-end" for="credit to">
                      {{ __('Credit To')}}
                      <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="credit_to_options" name="credit_to">
                      @if ($invoice && count($vendor_bank_accounts) > 0)
                          @foreach ($vendor_bank_accounts as $vendor_bank_account)
                            <option value="{{ $vendor_bank_account->account_number }}" @if($vendor_bank_account->account_number == $invoice->credit_to) selected @endif>{{ $vendor_bank_account->account_number }}</option>
                          @endforeach
                      @endif
                      @if (old('credit_to'))
                        <option value="{{ old('credit_to') }}" selected></option>
                      @endif
                    </select>
                    <x-input-error :messages="$errors->get('credit_to')" />
                  </div>
                </div>
              </div>
              <div class="col-12 d-flex justify-content-between">
                <div class="d-flex flex-column">
                  <button type="button" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#invoice-upload-modal">{{ __('Add Attachment')}}</button>
                  <span class="text-danger text-sm invoice-attachment-message d-none">{{ __('Attachment is required')}}</span>
                  <div class="modal fade" id="invoice-upload-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="modalCenterTitle">{{ __('Add Attachments') }}</h5>
                        </div>
                        <div class="modal-body">
                          <div class="">
                            <div class="needsclick dropzone" id="document-dropzone">
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button data-bs-dismiss="modal" type="button" class="btn btn-secondary">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex">
                  <button class="btn btn-label-secondary mx-1">
                    <span class="align-middle d-sm-inline-block d-none">{{ __('Cancel')}}</span>
                  </button>
                  <button class="btn btn-primary" id="submit-invoice"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit')}}</span></button>
                </div>
              </div>
            </div>
          </div>
          <!-- Personal Info -->
          <div id="personal-info-vertical" class="content">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>{{ __('Anchor')}}</th>
                    <th>{{ __('Program')}}</th>
                    <th>{{ __('Invoice No')}}.</th>
                    <th>{{ __('Actions')}}</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Icons Wizard -->
</div>
@endsection
