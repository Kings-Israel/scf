@extends('layouts/vendorLayoutMaster')

@section('title', 'Edit Purchase Order')

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
<script>
  let purchase_order = {!! json_encode($purchase_order) !!}
  if (purchase_order) {
    $(document).ready(function () {
      setTimeout(() => {
        calculateInvoiceTotal()
        $('#total-invoice').text(Number(purchase_order.total_amount).toLocaleString())
      }, 1000);
    })
  }

  let invoice_total = 0;

  let items_count = 1
  let items = $('#items')
  $(document.body).on('click', '#add-item', function(e) {
    e.preventDefault()
    let html = '<div class="row">'
        html += '<div class="col-sm-3" id="item-div-'+items_count+'">'
        html += '<label class="form-label" for="item">Item</label>'
        html += '<input type="text" name="item['+items_count+']" class="form-control" placeholder="Item" aria-label="item" />'
        html += '</div>'
        html += '<div class="col-sm-2" id="quantity-div-'+items_count+'">'
        html += '<label class="form-label" for="quantity">Quantity</label>'
        html += '<input type="text" name="quantity['+items_count+']" class="form-control" min="1" id="quantity-'+items_count+'" oninput="updateTotal('+items_count+')" onblur="calculateInvoiceTotal()" placeholder="0" aria-label="quantity" required />'
        html += '</div>'
        html += '<div class="col-sm-2" id="unit-div-'+items_count+'">'
        html += '<label class="form-label" for="unit">Unit</label>'
        html += '<input type="text" name="unit['+items_count+']" class="form-control" placeholder="0" aria-label="unit" />'
        html += '</div>'
        html += '<div class="col-sm-2" id="price-per-quantity-div-'+items_count+'">'
        html += '<label class="form-label" for="price_per_quantity">Price Per Quantity</label>'
        html += '<input type="text" name="price_per_quantity['+items_count+']" class="form-control" min="1" id="price-'+items_count+'" placeholder="Ksh" oninput="updateTotal('+items_count+')" onblur="calculateInvoiceTotal()" aria-label="price_per_quantity" required />'
        html += '</div>'
        html += '<div class="col-sm-3" id="total-div-'+items_count+'">'
        html += '<label class="form-label" for="total">Total</label>'
        html += '<input type="text" name="total['+items_count+']" class="form-control" placeholder="0" aria-label="total" id="total-'+items_count+'" disabled />'
        html += '</div>'
        html += '<div class="col-sm-4" id="description-div-'+items_count+'">'
        html += '<label class="form-label" for="description">Description</label>'
        html += '<textarea name="description['+items_count+']" class="form-control" rows="3" placeholder="Enter Description" aria-label="description" id="description-'+items_count+'"></textarea>';
        html += '</div>'
        html += '<div class="col-sm-12" id="delete-item-div-'+items_count+'">'
        html += '<i class="ti ti-trash ti-sm text-danger mt-4" title="delete" style="cursor: pointer" onclick="deleteItem('+items_count+')"></i>'
        html += '</div>'
        html += '</div>'
      $(html).appendTo(items);
      items_count += 1;
  })

  function deleteItem(index) {
    $('div').remove('#item-div-'+index+', #unit-div-'+index+', #quantity-div-'+index+', #price-per-quantity-div-'+index+', #total-div-'+index+', #description-div-'+index+', #delete-item-div-'+index);
    items_count -= 1;
    calculateInvoiceTotal()
  }

  function calculateInvoiceTotal() {
    invoice_total = 0;

    // Get subtotal
    $('[name^="total"]').each(index => {
      let price = $('#price-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#price-'+index).val(Number(price.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())

      let quantity = $('#quantity-'+index).val().replaceAll(/\D/g,'').replaceAll(',', '')
      $('#quantity-'+index).val(Number(quantity.replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())

      if (price != 0 && quantity != 0) {
        invoice_total += Number(price * quantity)
      }
    })

    $('#total-invoice').text(new Intl.NumberFormat().format(invoice_total))
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

  $('#purchase_order_number').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length > 3) {
      $.ajax({url: `create/purchase-order-number/${$(this).val()}/check`,
        success: function (data) {
          $('#submit-purchase-order').removeAttr('disabled')
          $('#purchase_order_number_error').addClass('d-none')
        },
        error: function (err) {
          if (err.status == 400) {
            $('#purchase_order_number_error').removeClass('d-none')
            $('#submit-purchase-order').attr('disabled', 'disabled')
          }
        }
      })
    }
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold py-2">
  {{ __('Create Purchase Order')}}
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
              <span class="bs-stepper-title">{{ __('PO Details')}}</span>
              <span class="bs-stepper-subtitle">{{ __('PO No, Vendor, Currency')}}</span>
            </span>
          </button>
        </div>
        <div class="line d-none"></div>
        <div class="step d-none" data-target="#drafts">
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
        <form method="POST" action="{{ route('vendor.purchase-order.update', ['purchase_order' => $purchase_order]) }}" enctype="multipart/form-data">
          @csrf
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="purchase_order_number">
                  {{ __('PO No')}}.
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="purchase_order_number" name="purchase_order_number" class="form-control" placeholder="PO Number" aria-label="purchase_order_number" value="{{ $purchase_order->purchase_order_number }}" required />
                <span id="purchase_order_number_error" class="text-danger d-none">{{ __('PO Number already in use')}}</span>
                <x-input-error :messages="$errors->get('purchase_order_number')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="currency">
                  {{ __('Currency')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="select2" id="currency" name="currency" required>
                  <option label=" "></option>
                  @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @if($purchase_order->currency == $currency->code) selected @endif>{{ $currency->code }} ({{ $currency->name }})</option>
                  @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" />
              </div>
              <div class="col-sm-3">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Duration (From)')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control" type="date" name="duration_from" id="html5-date-input" value="{{ $purchase_order->duration_from }}" required />
                <x-input-error :messages="$errors->get('duration_from')" />
              </div>
              <div class="col-sm-3">
                <label for="html5-date-input" class="col-form-label">
                 {{ __(' Duration (To)')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control" type="date" name="duration_to" id="html5-date-input" required value="{{ $purchase_order->duration_to }}" />
                <x-input-error :messages="$errors->get('duration_to')" />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Delivery Date')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control" type="date" name="delivery_date" id="html5-date-input" required value="{{ $purchase_order->delivery_date }}" />
                <x-input-error :messages="$errors->get('delivery_date')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="delivery_address">
                  {{ __('Delivery Address')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="delivery_address" name="delivery_address" class="form-control" placeholder="Delivery Address" aria-label="Delivery Address" required value="{{ $purchase_order->delivery_address }}" />
                <x-input-error :messages="$errors->get('delivery_address')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice_payment_terms">
                  {{ __('Invoice Payment Terms(Days)')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="number" min="0" id="invoice_payment_terms" name="invoice_payment_terms" class="form-control" placeholder="Payment Terms" aria-label="invoice_payment_terms" required value="{{ $purchase_order->invoice_payment_terms }}" />
                <x-input-error :messages="$errors->get('invoice_payment_terms')" />
              </div>
              <div class="col-12">
                <label for="exampleFormControlTextarea1" class="form-label">{{ __('Remarks')}}</label>
                <textarea class="form-control" id="exampleFormControlTextarea1" name="remarks" rows="2">{{ $purchase_order->remarks }}</textarea>
                <x-input-error :messages="$errors->get('remarks')" />
              </div>
              <div class="col-12 row" id="items">
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
                    <input type="text" name="quantity[{{ $key }}]" value="{{ $purchase_order_item->quantity }}" class="form-control" placeholder="0" id="quantity-{{ $key }}" min="1" step=".01" oninput="updateTotal({{ $key }})" onblur="calculateInvoiceTotal()" aria-label="quantity" required />
                  </div>
                  <div class="col-sm-2">
                    <label class="form-label" for="unit">
                      {{ __('Unit')}}
                    </label>
                    <input type="text" name="unit[{{ $key }}]" value="{{ $purchase_order_item->unit }}" class="form-control" placeholder="0" aria-label="unit" required />
                  </div>
                  <div class="col-sm-2">
                    <label class="form-label" for="price_per_quantity">
                      {{ __('Price Per Quantity')}}
                      <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="price_per_quantity[{{ $key }}]" value="{{ $purchase_order_item->price_per_quantity }}" class="form-control" id="price-{{ $key }}" min="1" step=".01" placeholder="0" oninput="updateTotal({{ $key }})" onblur="calculateInvoiceTotal()" aria-label="price_per_quantity" required />
                  </div>
                  <div class="col-sm-3">
                    <label class="form-label" for="total">{{ __('Total')}}</label>
                    <input type="text" name="total[{{ $key }}]" class="form-control" placeholder="0" aria-label="total" id="total-{{ $key }}" disabled />
                  </div>
                  <div class="col-sm-4">
                    <label class="form-label" for="description">{{ __('Description')}}</label>
                    <textarea name="description[{{ $key }}]" id="" rows="3" class="form-control">{{ $purchase_order_item->description }}</textarea>
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
              <div class="col-12 d-flex justify-content-end">
                <h6>{{ __('Total')}}:</h6>
                <h5 class="mx-4" id="total-invoice">0</h5>
              </div>
              <div class="d-flex justify-content-end">
                <button class="btn btn-primary" id="submit-purchase-order"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit')}}</span></button>
              </div>
            </div>
          </div>
          <!-- Drafts -->
          <div id="drafts" class="content">

          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Icons Wizard -->
</div>
@endsection
