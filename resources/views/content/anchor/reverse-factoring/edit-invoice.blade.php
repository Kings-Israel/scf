@extends('layouts/anchorLayoutMaster')

@section('title', 'Edit Invoice')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
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

  let sub_total = 0;
  let taxes_amount = 0;
  let invoice_total = 0;

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

  // $('#submit-invoice').attr('disabled', 'disabled')

  let invoice = {!! json_encode($invoice) !!}
  if (invoice) {
    $(document).ready(function () {
      setTimeout(() => {
        calculateInvoiceTotal()
        $('#taxes-invoice').text(Number(invoice.total_invoice_taxes).toLocaleString())
        $('#invoice-discount').text(Number(invoice.total_invoice_discount).toLocaleString())
        $('#total-invoice').text(Number(invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount).toLocaleString())
      }, 1000);
    })
  }

  function calculateInvoiceTotal() {
    let taxes = 0;
    invoice_total = 0;
    taxes_amount = 0;
    sub_total = 0;
    let subtotal = 0
    let discount_value = 0

    // Get subtotal
    $('[name^="total"]').each(index => {
      let price = ''
      if (typeof($('#price-'+index).val()) == 'string') {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = ''
      if (typeof($('#quantity-'+index).val()) == 'string') {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      if (price != 0 && quantity != 0) {
        sub_total += Number(price * quantity)
      }
    })

    $('#subtotal-invoice').text(new Intl.NumberFormat().format(sub_total))

    // Get Taxes
    let line_total = 0
    let price = 0
    let quantity = 0
    $('[name^="tax_name"]').each((index, item) => {
      if (typeof($('#price-'+index).val()) == 'string') {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      if (typeof($('#quantity-'+index).val()) == 'string') {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      line_total = Number(price * quantity)
      let line_tax = 0

      let tax = $('#'+item.id).find(':selected').data('value');
      if (tax !== undefined) {
        taxes_amount += Number(tax)
      }
    })

    if (taxes_amount > 0) {
      taxes += (taxes_amount / 100) * Number(line_total)
    } else {
      taxes += 0
    }

    $('#taxes-invoice').text(new Intl.NumberFormat().format(taxes))

    $('[name^="discount_value"]').each(index => {
      let price = ''
      if (typeof($('#price-'+index).val()) == 'string') {
        price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let quantity = ''
      if (typeof($('#quantity-'+index).val()) == 'string') {
        quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
        $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      }

      let line_total = Number(price * quantity)

      let line_tax = 0
      let line_taxes = 0
      $('[name^="tax_name"]').each((index, item) => {
        let tax = $('#'+item.id).find(':selected').data('value');
        if (tax !== undefined) {
          line_tax += Number(tax)
        }
      })

      if (line_tax > 0) {
        line_taxes += (line_tax / 100) * Number(line_total)
      } else {
        line_taxes = 0
      }

      let type = $('#discount_type-'+index)
      let value = $('#discount_value-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#discount_value-'+index).val(Number(value.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
      if (value != '' && Number(value) != 0) {
        if (type.val() == 'percentage') {
          discount_value += (Number(value) / 100) * Number(line_total + line_taxes)
        } else {
          discount_value += Number(value)
        }
      }
    })

    $('#invoice-discount').text(new Intl.NumberFormat().format(discount_value))

    invoice_total = sub_total + taxes - discount_value

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

  function updateTaxes(index) {
    let tax_value = $('#tax-name-'+index).find(':selected').data('value');

    $('#tax-value-'+index).val(tax_value);
  }

  function calculateTaxes(item_index) {
    let taxes = 0;
    taxes_amount = 0;

    let line_total = 0
    let price = 0
    let quantity = 0

    if (typeof($('#price-'+item_index).val()) == 'string') {
      price = $('#price-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#price-'+item_index).val(Number($('#price-'+item_index).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    }

    if (typeof($('#quantity-'+item_index).val()) == 'string') {
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
      taxes += (taxes_amount / 100) * Number(line_total)
    } else {
      taxes += 0
    }

    // $('#taxes-invoice').text(new Intl.NumberFormat().format(taxes))
    // $('#total-invoice').text(new Intl.NumberFormat().format(sub_total + taxes))
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
          discount_value += (Number(value) / 100) * Number(subtotal + taxes)
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

  $('.invoice-date').on('change', function () {
    let due_date = moment($('.invoice-date').val()).add(default_payment_terms, 'days')

    $('.due-date').val(moment(due_date).format('YYYY-MM-DD'))
  })

  default_payment_terms = {!! $max_days !!}
  let invoice_due_date = {!! json_encode($invoice->due_date) !!}
  console.log(invoice_due_date)
  var min_day = new Date(invoice_due_date)
  min_day.setDate(min_day.getDate())
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
  $('#due-date').attr("min", min_day);

  var max_day = new Date(invoice_due_date)
  max_day.setDate(max_day.getDate() + default_payment_terms)
  var dd = max_day.getDate();
  var mm = max_day.getMonth() + 1; //January is 0!
  var yyyy = max_day.getFullYear();

  if (dd < 10) {
    dd = '0' + dd;
  }

  if (mm < 10) {
    mm = '0' + mm;
  }

  max_day = yyyy + '-' + mm + '-' + dd;
  $('#due-date').attr("max", max_day);
</script>
@endsection

@section('content')
<h4 class="fw-light">
  {{ __('Edit Invoice')}}
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
              <span class="bs-stepper-subtitle">{{ __('Anchor, PO No, A/C')}}</span>
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
        <form method="POST" action="{{ route('anchor.invoices.update', ['invoice' => $invoice]) }}" enctype="multipart/form-data" id="invoice-form">
          @csrf
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="buyer">{{ __('Vendor')}}</label>
                <input type="vendor" class="form-control" readonly value="{{ $invoice->company->name }}">
                <x-input-error :messages="$errors->get('buyer')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="program_id">{{ __('Payment/OD Account')}}</label>
                <input type="text" class="form-control" readonly value="{{ $payment_account_number }}">
                <x-input-error :messages="$errors->get('program_id')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice_number">{{ __('Invoice No')}}.</label>
                <input type="text" id="invoice_number" name="invoice_number" class="form-control" placeholder="Invoice Number" aria-label="invoice_number" value="{{ $invoice->invoice_number }}" readonly />
                <x-input-error :messages="$errors->get('invoice_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="associated_po">{{ __('Associated PO')}}</label>
                <input type="text" class="form-control" readonly value="{{ $invoice->purchaseOrder?->purchase_order_number }}">
                <x-input-error :messages="$errors->get('purchase_order')" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">{{ __('Invoice Date')}}</label>
                <input class="form-control invoice-date" name="invoice_date" type="date" value="{{ $invoice->invoice_date }}" id="html5-date-input" readonly />
                <x-input-error :messages="$errors->get('invoice_date')" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">{{ __('Due Date')}}</label>
                <input class="form-control due-date" name="due_date" type="date" value="{{ old('due_date', $invoice ? $invoice->due_date : '') }}" id="due-date" required />
                <x-input-error :messages="$errors->get('due_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="currency">{{ __('Currency')}}</label>
                <input type="text" class="form-control" value="{{ $invoice->currency }}" readonly />
                <x-input-error :messages="$errors->get('currency')" />
              </div>
              <div class="col-12">
                <label for="remarks" class="form-label">{{ __('Remarks')}}</label>
                <textarea class="form-control" name="remarks" id="remarks" rows="3" disabled> {{ old('remarks', $invoice ? $invoice->remarks : '') }}</textarea>
                <x-input-error :messages="$errors->get('remarks')" />
              </div>
              <hr>
              <div class="col-12 g-3 row" id="items">
                @if ($invoice)
                  @foreach ($invoice->invoiceItems as $key => $invoice_item)
                    <div class="col-sm-3">
                      <label class="form-label" for="item">{{ __('Item')}}</label>
                      <input type="text" name="item[{{ $key }}]" value="{{ $invoice_item->item }}" class="form-control" placeholder="Item" aria-label="item" readonly />
                      <x-input-error :messages="$errors->get('item.'.$key)" />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="quantity">{{ __('Quantity')}}</label>
                      <input type="text" name="quantity[{{ $key }}]" value="{{ $invoice_item->quantity }}" readonly class="form-control" placeholder="0" id="quantity-{{ $key }}" oninput="updateTotal({{ $key }})" onblur="calculateSubtotal()" aria-label="quantity" />
                      <x-input-error :messages="$errors->get('quantity.'.$key)" />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="unit">{{ __('Unit')}}</label>
                      <input type="text" name="unit[{{ $key }}]" value="{{ $invoice_item->unit }}" readonly class="form-control" placeholder="0" aria-label="unit" />
                      <x-input-error :messages="$errors->get('unit.'.$key)" />
                    </div>
                    <div class="col-sm-2">
                      <label class="form-label" for="price_per_quantity">{{ __('Price Per Quantity')}}</label>
                      <input type="text" name="price_per_quantity[{{ $key }}]" value="{{ $invoice_item->price_per_quantity }}" readonly class="form-control" id="price-{{ $key }}" placeholder="Ksh" oninput="updateTotal({{ $key }})" onblur="calculateSubtotal()" aria-label="price_per_quantity" />
                      <x-input-error :messages="$errors->get('price_per_quantity.'.$key)" />
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="total">{{ __('Total')}}</label>
                      <input type="text" name="total[{{ $key }}]" value="{{ $invoice_item->calculateTotal() }}" class="form-control" placeholder="0" aria-label="total" id="total-{{ $key }}" disabled />
                      <x-input-error :messages="$errors->get('total.'.$key)" />
                    </div>
                    <div class="col-12 row" id="taxes-section-{{ $key }}">
                      @if ($invoice_item->invoiceTaxes)
                        @foreach ($invoice_item->invoiceTaxes as $tax_key => $invoice_item_tax)
                          @if ($loop->first)
                            <div class="col-sm-5">
                              <label class="form-label" for="tax">{{ __('Tax')}}</label>
                              <select class="form-select" name="tax_name[{{ $key }}][{{ $tax_key }}]" id="tax-name-{{ $key }}-{{ $tax_key }}" onchange="updateTaxes({{ $tax_key }})" onblur="calculateTaxes({{ $key }})" disabled>
                                <option label="" value="">{{ __('Select Tax') }}</option>
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
                              <select class="form-select" name="tax_name[{{ $key }}][{{ $tax_key }}]" id="tax-name-{{ $key }}-{{ $tax_key }}" onchange="updateTaxes({{ $tax_key }})" onblur="calculateTaxes({{ $key }})" disabled>
                                <option label="" value="">{{ __('Select Tax') }}</option>
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
                      @endif
                    </div>
                    @if ($invoice_item->invoiceDiscount)
                      <div class="col-sm-5">
                        <label class="form-label" for="Discount Type">{{ __('Discount Type')}}</label>
                        <select name="discount_type[{{ $key }}]" id="discount_type-0" class="form-control" disabled>
                          <option value="">{{ __('Select Type')}}</option>
                          <option value="percentage" @if($invoice_item->invoiceDiscount->type == 'percentage') selected @endif>{{ __('Percentage')}}</option>
                          <option value="absolute" @if($invoice_item->invoiceDiscount->type == 'absolute') selected @endif>{{ __('Absolute')}}</option>
                        </select>
                      </div>
                      <div class="col-sm-5">
                        <label for="Discount Amount" class="form-label">{{ __('Discount Value')}}</label>
                        <input type="text" class="form-control" id="discount_value-{{ $key }}" name="discount_value[{{ $key }}]" value="{{ $invoice_item->invoiceDiscount->value }}" oninput="calculateDiscount()" readonly />
                      </div>
                      <div class="col-sm-2 my-auto"></div>
                    @endif
                  @endforeach
                @else
                  <div class="col-sm-3">
                    <label class="form-label" for="item">{{ __('Item')}}</label>
                    <input type="text" name="item[]" class="form-control" placeholder="Item" aria-label="item" />
                  </div>
                  <div class="col-sm-2">
                    <label class="form-label" for="quantity">{{ __('Quantity')}}</label>
                    <input type="text" name="quantity[]" class="form-control" placeholder="0" id="quantity-0" oninput="updateTotal(0)" onblur="calculateSubtotal()" aria-label="quantity" required />
                  </div>
                  <div class="col-sm-2">
                    <label class="form-label" for="unit">{{ __('Unit')}}</label>
                    <input type="text" name="unit[]" class="form-control" placeholder="0" aria-label="unit" />
                  </div>
                  <div class="col-sm-2">
                    <label class="form-label" for="price_per_quantity">{{ __('Price Per Quantity')}}</label>
                    <input type="text" name="price_per_quantity[]" class="form-control" id="price-0" placeholder="Ksh" oninput="updateTotal(0)" onblur="calculateSubtotal()" aria-label="price_per_quantity" required />
                  </div>
                  <div class="col-sm-3">
                    <label class="form-label" for="total">{{ __('Total')}}</label>
                    <input type="text" name="total[]" class="form-control" placeholder="0" aria-label="total" id="total-0" disabled />
                  </div>
                  <div class="col-12 row g-3" id="taxes-section-0">
                    <div class="col-sm-5" id="tax-name-section-0">
                      <label class="form-label" for="tax">{{ __('Tax')}}</label>
                      <select class="form-select" name="tax_name[0][0]" id="tax-name-0" onchange="updateTaxes(0)" onblur="calculateTaxes(0)">
                        <option label="" value="">{{ __('Select Tax') }}</option>
                        @foreach ($taxes as $key => $tax)
                          <option value="{{ $key }}" data-value="{{ $tax }}">{{ $key }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-sm-5" id="tax-value-section-0">
                      <label class="form-label" for="tax_value">{{ __('Tax Value')}}</label>
                      <input type="number" name="tax_value[0][0]" class="form-control" id="tax-value-0" placeholder="0" readonly />
                    </div>
                    <div class="col-sm-2 my-auto d-none" id="delete-tax-section-0">
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
                  <div class="col-sm-6">
                    <label class="form-label" for="Discount Type">{{ __('Discount Type')}}</label>
                    <select name="discount_type[]" id="discount_type-0" class="form-control">
                      <option value="" value="">{{ __('Select Type')}}</option>
                      <option value="percentage">{{ __('Percentage')}}</option>
                      <option value="absolute">{{ __('Absolute')}}</option>
                    </select>
                  </div>
                  <div class="col-sm-6">
                    <label for="Discount Amount" class="form-label">{{ __('Discount Value')}}</label>
                    <input type="text" class="form-control" id="discount_value-0" name="discount_value[]" oninput="calculateDiscount()">
                  </div>
                @endif
              </div>
              {{-- <div class="col-12 row mt-2">
                <div class="col-2">
                  <span class="d-flex align-items-center" id="add-item" style="cursor: pointer">
                    <span class="badge bg-label-primary" style="border-radius: 100px;"><i class='ti ti-plus ti-sm'></i></span>
                    <span class="mx-2">
                      {{ __('Add Invoice Item')}}
                    </span>
                  </span>
                </div>
              </div> --}}
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
                </div>
                <div class="d-flex">
                  <button class="btn btn-label-secondary mx-1" type="button">
                    <a href="{{ route('anchor.invoices') }}" class="align-middle d-sm-inline-block d-none">{{ __('Cancel')}}</a>
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
