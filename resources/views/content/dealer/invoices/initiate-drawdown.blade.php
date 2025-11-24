@extends('layouts/buyerLayoutMaster')

@section('title', 'Initiate Drawdown')

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>
<script>
  var th = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

  var dg = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
  var tn = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
  var tw = ['Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
  function toWords(s) {
      s = s.toString();
      s = s.replace(/[\, ]/g, '');
      if (s != parseFloat(s)) return 'Not a Number';
      var x = s.indexOf('.');
      if (x == -1) x = s.length;
      if (x > 15) return 'Too Big';
      var n = s.split('');
      var str = '';
      var sk = 0;
      for (var i = 0; i < x; i++) {
          if ((x - i) % 3 == 2) {
              if (n[i] == '1') {
                  str += tn[Number(n[i + 1])] + ' ';
                  i++;
                  sk = 1;
              } else if (n[i] != 0) {
                  str += tw[n[i] - 2] + ' ';
                  sk = 1;
              }
          } else if (n[i] != 0) {
              str += dg[n[i]] + ' ';
              if ((x - i) % 3 == 0) str += 'Hundred ';
              sk = 1;
          }
          if ((x - i) % 3 == 1) {
              if (sk) str += th[(x - i - 1) / 3] + ' ';
              sk = 0;
          }
      }
      if (x != s.length) {
          var y = s.length;
          str += 'point ';
          for (var i = x + 1; i < y; i++) str += dg[n[i]] + ' ';
      }
      return str.replace(/\s+/g, ' ');
  }
  var a = ['','one ','two ','three ','four ', 'five ','six ','seven ','eight ','nine ','ten ','eleven ','twelve ','thirteen ','fourteen ','fifteen ','sixteen ','seventeen ','eighteen ','nineteen '];
  var b = ['', '', 'twenty','thirty','forty','fifty', 'sixty','seventy','eighty','ninety'];

  function NumInWords (n) {
    if (n < 0) return false;
    single_digit = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine']
    double_digit = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen']
    below_hundred = ['Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety']
    if (n === 0) return 'Zero'
    function translate(n) {
      word = ""
      if (n < 10) {
        word = single_digit[n] + ' '
      }
      else if (n < 20) {
        word = double_digit[n - 10] + ' '
      }
      else if (n < 100) {
        rem = translate(n % 10)
        word = below_hundred[(n - n % 10) / 10 - 2] + ' ' + rem
      }
      else if (n < 1000) {
        word = single_digit[Math.trunc(n / 100)] + ' Hundred ' + translate(n % 100)
      }
      else if (n < 1000000) {
        word = translate(parseInt(n / 1000)).trim() + ' Thousand ' + translate(n % 1000)
      }
      else if (n < 1000000000) {
        word = translate(parseInt(n / 1000000)).trim() + ' Million ' + translate(n % 1000000)
      }
      else {
        word = translate(parseInt(n / 1000000000)).trim() + ' Billion ' + translate(n % 1000000000)
      }
      return word
    }
    result = translate(n)
    return result.trim()+'.'
  }

  let vendor_configuration = null
  let max_drawdown_amount = 0

  $('#submit-invoice').attr('disabled', 'disabled')

  let invoice = {!! json_encode($invoice) !!}
  if (invoice) {
    $('#invoice-amount-in-words').val(toWords(invoice.invoice_total_amount))

    $('#invoice-amount').val(invoice.invoice_total_amount.toLocaleString())
    $('#drawdown-amount-in-words').val(toWords(invoice.drawdown_amount))

    let creditAccountOptions = document.getElementById('credit-to')

    while (creditAccountOptions.options.length) {
      creditAccountOptions.remove(0)
    }
    creditAccountOptions.options.add(new Option('', ''))
    let accounts = {!! json_encode($credit_accounts) !!}
    let account = {!! json_encode($credit_to) !!}
    vendor_configuration = {!! json_encode($vendor_configuration) !!}
    if (accounts) {
      var i
      for (let i = 0; i < accounts.length; i++) {
        var credit_account = new Option(accounts[i].account_number, accounts[i].id)
        if (account && account == accounts[i].account_number) {
          credit_account.setAttribute('selected', true)
        }
        creditAccountOptions.options.add(credit_account)
      }
    }

    let discount_rates = ''
    invoice.program.dealer_discount_rates.forEach((dealer_discount_rates, index) => {
      if (index === invoice.program.dealer_discount_rates.length - 1) {
        discount_rates += ''+dealer_discount_rates.from_day+' - '+dealer_discount_rates.to_day+' days: '+dealer_discount_rates.total_roi+'% ';
      } else {
        discount_rates += ''+dealer_discount_rates.from_day+' - '+dealer_discount_rates.to_day+' days: '+dealer_discount_rates.total_roi+'%, ';
      }
    });

    $('#discount-rate').val(discount_rates)

    if (vendor_configuration) {
      if (vendor_configuration.withholding_tax || vendor_configuration.withholding_vat) {
        max_drawdown_amount = vendor_configuration.eligibility / 100 * (invoice.invoice_total_amount - ((vendor_configuration.withholding_tax / 100) * invoice.invoice_total_amount)) - ((vendor_configuration.withholding_vat / 100) * invoice.invoice_total_amount)
      } else {
        max_drawdown_amount = vendor_configuration.eligibility / 100 * invoice.invoice_total_amount
      }

      $('#drawdown-amount').attr('max', max_drawdown_amount)
      $('#drawdown-amount-error').text('Max Drawdown Amount is '+max_drawdown_amount.toLocaleString()).removeClass('d-none').addClass('text-danger')

      if (invoice.drawdown_amount > max_drawdown_amount) {
        $('#submit-invoice').attr('disabled', 'disabled')
      } else {
        $('#submit-invoice').removeAttr('disabled')
      }
    }

    var invoice_min_day = new Date(invoice.due_date)
    invoice_min_day.setDate(invoice_min_day.getDate())
    var invoice_min_dd = invoice_min_day.getDate();
    var invoice_min_mm = invoice_min_day.getMonth() + 1; //January is 0!
    var invoice_min_yyyy = invoice_min_day.getFullYear();

    if (invoice_min_dd < 10) {
      invoice_min_dd = '0' + invoice_min_dd;
    }

    if (invoice_min_mm < 10) {
      invoice_min_mm = '0' + invoice_min_mm;
    }

    invoice_min_day = invoice_min_yyyy + '-' + invoice_min_mm + '-' + invoice_min_dd;

    $('.due_date').attr("min", invoice_min_day);

    // Set max date for anchor payment date
    let max_payment_date = 0
    var max_day = new Date(invoice.due_date)
    max_payment_date = invoice.program.dealer_discount_rates[invoice.program.dealer_discount_rates.length - 1].to_day - 1
    max_day.setDate(max_day.getDate() + max_payment_date)
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

    $('.payment_date').val(invoice.due_date);
    // $('.payment_date').attr("max", max_day);
    // if (invoice.due_date > max_day) {
    //   $('.due_date').val(max_day);
    // } else {
    //   $('.due_date').val(invoice.due_date);
    // }
    $('.due_date').attr("max", max_day);
  }

  $('#invoice-amount').on('input', function (evt) {
    let invoice_amount = $(this).val()
    if (isNaN(invoice_amount)) {
      return
    }
    // $(this).val(formatNumberInput(invoice_amount))
    // $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    $('#invoice-amount-in-words').val(toWords($(this).val().replaceAll(',', '')))
    // $('#invoice-amount-in-words').val(NumInWords($(this).val().replaceAll(',', '')))
    $('#drawdown-amount').attr('max', $(this).val().replaceAll(',', ''))
    // Set the max limit
    if (vendor_configuration) {
      if (vendor_configuration.withholding_tax || vendor_configuration.withholding_vat) {
        max_drawdown_amount = vendor_configuration.eligibility / 100 * ($(this).val().replaceAll(',', '') - ((vendor_configuration.withholding_tax / 100) * $(this).val().replaceAll(',', '')) - ((vendor_configuration.withholding_vat / 100) * $(this).val().replaceAll(',', '')))
      } else {
        max_drawdown_amount = vendor_configuration.eligibility / 100 * $(this).val().replaceAll(',', '')
      }

      $('#drawdown-amount').attr('max', max_drawdown_amount)
      $('#drawdown-amount-error').text('Max Drawdown Amount is '+max_drawdown_amount.toLocaleString()).removeClass('d-none').addClass('text-danger')
    }
  })

  const formatValue = (element) => {
    let value = $('#'+element).val()
    $('#'+element+'-in-words').val(toWords(Number(value).toFixed(2)))
    $('#'+element).val(new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value))
  }

  $('#drawdown-amount').on('input', function () {
    let drawdown_amount = $(this).val()
    if (isNaN(drawdown_amount)) {
      return
    }
    // $(this).val(Number($(this).val().replaceAll(/\D/g,'').replaceAll(',', '')).toLocaleString())
    // $('#drawdown-amount-in-words').val(NumInWords($(this).val().replaceAll(',', '')))
    $('#drawdown-amount-in-words').val(toWords(drawdown_amount.replaceAll(',', '')))

    if (drawdown_amount.replaceAll(',', '') > max_drawdown_amount) {
      $('#submit-invoice').attr('disabled', 'disabled')
    } else {
      $('#submit-invoice').removeAttr('disabled')
    }
  })

  $('#anchor').on('change', function (e) {
    e.preventDefault();
    $.get(`initiate-drawdown/${$(this).val()}/programs`, function (data, status) {
      let programOptions = document.getElementById('program_options')

      while (programOptions.options.length) {
        programOptions.remove(0)
      }
      programOptions.options.add(new Option('', ''))
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
  let max_payment_date = 0
  let max_payment_days = 0
  let due_date_calculated_from = 'Disbursement Date'

  $('#program_options').on('change', function () {
    if ($(this).val() != '') {
      $('#invoice_number').removeAttr('readonly')
    } else {
      $('#invoice_number').val('')
      $('#invoice_number').attr('readonly', 'readonly')
    }

    $.get(`initiate-drawdown/program/${$(this).val()}/details`, function (data, status) {
      // Set OD Accounts
      let creditAccountOptions = document.getElementById('credit-to')

      while (creditAccountOptions.options.length) {
        creditAccountOptions.remove(0)
      }
      creditAccountOptions.options.add(new Option('', ''))
      if (data.credit_accounts) {
        var i
        for (let i = 0; i < data.credit_accounts.length; i++) {
          var credit_account = new Option(data.credit_accounts[i].account_number, data.credit_accounts[i].id)
          creditAccountOptions.options.add(credit_account)
        }
      }

      default_payment_terms = data.program.default_payment_terms
      due_date_calculated_from = data.program.due_date_calculated_from
      var invoice_date_min_day = new Date()
      invoice_date_min_day.setDate(invoice_date_min_day.getDate() - default_payment_terms)
      var dd = invoice_date_min_day.getDate();
      var mm = invoice_date_min_day.getMonth() + 1; //January is 0!
      var yyyy = invoice_date_min_day.getFullYear();

      if (dd < 10) {
        dd = '0' + dd;
      }

      if (mm < 10) {
        mm = '0' + mm;
      }

      invoice_date_min_day = yyyy + '-' + mm + '-' + dd;
      $('.invoice_date').attr("min", invoice_date_min_day);

      default_payment_terms = data.program.default_payment_terms
      var min_day = new Date()
      min_day.setDate(min_day.getDate() + default_payment_terms)
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
      $('.due_date').attr("max", min_day);

      var max_day = new Date()
      // max_payment_date = data.dealer_discount_rates[data.dealer_discount_rates.length - 1].to_day - 1
      max_payment_days = data.dealer_discount_rates[data.dealer_discount_rates.length - 1].to_day - 1
      max_payment_date = default_payment_terms

      max_day.setDate(max_day.getDate() + max_payment_date)
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
      $('.payment_date').attr("max", max_day);

      let discount_rates = ''
      data.dealer_discount_rates.forEach((dealer_discount_rates, index) => {
        if (index === data.dealer_discount_rates.length - 1) {
          discount_rates += ''+dealer_discount_rates.from_day+' - '+dealer_discount_rates.to_day+' days: '+dealer_discount_rates.total_roi+'% ';
        } else {
          discount_rates += ''+dealer_discount_rates.from_day+' - '+dealer_discount_rates.to_day+' days: '+dealer_discount_rates.total_roi+'%, ';
        }
      });

      $('#discount-rate').val(discount_rates)

      vendor_configuration = data.vendor_configuration
    })
  })

  $('.invoice_date').on('input', function () {
    if (due_date_calculated_from == 'Invoice Creation Date') {
      var invoice_date = $(this).val();

      var min_day = new Date(invoice_date);

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

      $('.due_date').attr("min", min_day);

      var max_day = new Date(min_day)
      max_day.setDate(max_day.getDate() + max_payment_date)
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

      $('.due_date').attr("max", max_day);
    }
  })

  $('.payment_date').on('input', function () {
    if (due_date_calculated_from == 'Disbursement Date') {
      // Get Date Anchor is going to be paid
      var payment_date = $(this).val();

      // Set minimum due date that can be set
      var min_day = new Date(payment_date);

      var dd = min_day.getDate() + 1;
      var mm = min_day.getMonth() + 1; //January is 0!
      var yyyy = min_day.getFullYear();

      if (dd < 10) {
        dd = '0' + dd;
      }

      if (mm < 10) {
        mm = '0' + mm;
      }

      min_day = yyyy + '-' + mm + '-' + dd;

      $('.due_date').attr("min", min_day);

      // Set max due date that can be set
      var max_day = new Date(min_day)
      max_day.setDate(max_day.getDate() + max_payment_days)
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
      if (invoice) {
        $('.due_date').attr("max", invoice.due_date);
      } else {
        $('.due_date').attr("max", max_day);
      }
    } else {
      // var max_day = new Date()
      // max_day.setDate(max_day.getDate() + max_payment_date)
      // var dd = max_day.getDate();
      // var mm = max_day.getMonth() + 1; //January is 0!
      // var yyyy = max_day.getFullYear();

      // if (dd < 10) {
      //   dd = '0' + dd;
      // }

      // if (mm < 10) {
      //   mm = '0' + mm;
      // }

      // max_day = yyyy + '-' + mm + '-' + dd;
      // if (invoice) {
      //   $('.due_date').attr("max", invoice.due_date);
      // }
    }
  })

  var today = new Date();

  var dd = today.getDate();
  var mm = today.getMonth() + 1; //January is 0!
  var yyyy = today.getFullYear();

  if (dd < 10) {
    dd = '0' + dd;
  }

  if (mm < 10) {
    mm = '0' + mm;
  }

  today = yyyy + '-' + mm + '-' + dd;
  $('.invoice_date').attr("max", today);
  if (!invoice) {
    $('.due_date').attr("min", today);
  }
  $('.payment_date').attr("min", today);

  $('#invoice_number').on('input', function (e) {
    e.preventDefault();
    if ($(this).val().length > 3) {
      $.ajax({url: `../invoices/invoice-number/${$(this).val()}/check`,
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

  $('#invoice-form').on('submit', function (e) {
    $('#submit-invoice').text('Processing...');
    $('#submit-invoice').attr('disabled', 'disabled');
  })
</script>
<script>
  var uploadedDocumentMap = {}
  Dropzone.options.documentDropzone = {
    url: '{{ route('dealer.invoices.attachment.store') }}',
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

@section('page-style')
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css" rel="stylesheet" />
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

@section('content')
<h4 class="fw-light d-flex justify-content-between">
  {{ __('Initiate Drawdown')}}
</h4>
@if (session()->has('program-expired'))
  <div class="card w-100 bg-label-danger p-2">
    <span class="">{{ session()->get('program-expired') }}</span>
  </div>
@endif
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
              <span class="bs-stepper-subtitle">{{ __('Ref No., Anchor, Currency')}}</span>
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
        <form method="POST" action="{{ route('dealer.invoices.initiate-drawdown.store') }}" enctype="multipart/form-data" id="invoice-form">
          @csrf
          <!-- Account Details -->
          <div id="account-details-vertical" class="content">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="invoice-number">
                  {{ __('Invoice / Unique Ref No')}}.
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="invoice_number" class="form-control" name="invoice_number" value="{{ old('invoice_number', $invoice ? $invoice->invoice_number : '') }}">
                <span id="invoice_number_error" class="text-danger d-none">{{ __('Invalid Invoice Number') }}</span>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="anchor">
                  {{ __('Anchor')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="select2" id="anchor" name="anchor_id" required>
                  <option label=" "></option>
                  @foreach($anchors as $anchor)
                    <option value="{{ $anchor->id }}" @if(old('anchor_id') === $anchor->id || ($invoice && $invoice->program->anchor->id == $anchor->id)) selected @endif>{{ $anchor->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="program_id">
                  {{ __('OD Account')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="program_options" name="program_id" required>
                  <option label=" "></option>
                  @if ($invoice)
                    <option value="{{ $invoice->program_id }}" data-program="{{ $invoice->program }}" selected>{{ $invoice->program->name }}</option>
                  @endif
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="credit-to">
                  {{ __('Credit To')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="select2" id="credit-to" name="credit_to" required>
                  <option label=" "></option>
                </select>
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Invoice Date')}}
                  <span class="text-danger">*</span>
                </label>
                <input class="form-control invoice_date" type="date" id="html5-date-input" name="invoice_date" value="{{ old('invoice_date', $invoice ? $invoice->invoice_date : '') }}" required />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Anchor Payment Date')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Date when the anchor will be paid"></i>
                </label>
                <input class="form-control payment_date" type="date" id="html5-date-input" name="payment_date" value="{{ old('payment_date', $invoice ? $invoice->payment_date : '') }}" required />
              </div>
              <div class="col-sm-6">
                <label for="html5-date-input" class="col-form-label">
                  {{ __('Payment Due Date')}}
                  <span class="text-danger">*</span>
                  <i class="tf-icons ti ti-info-circle ti-xs" data-title="Expected Pay date"></i>
                </label>
                <input class="form-control due_date" type="date" id="html5-date-input" name="due_date" value="{{ old('due_date') }}" required />
              </div>
              <div class="col-sm-12">
                <label class="form-label" for="discount-rate">
                  {{ __('Discount Rate')}}
                </label>
                <input type="text" id="discount-rate" class="form-control" placeholder="Discount Rate" value="{{ old('discount_rate') }}" readonly aria-label="discount_rate" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice-amount">
                  {{ __('Invoice Amount')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="invoice-amount" class="form-control" name="invoice_amount" placeholder="Invoice Amount" aria-label="invoice_amount" autocomplete="off" onblur="formatValue('invoice-amount')" value="{{ old('invoice_amount', $invoice ? number_format($invoice->total_amount, 2) : '') }}" required />
                {{-- <x-input-error :messages="$errors->get('invoice_amount')" />
                <x-input-error :messages="$errors->get('invoice_amount_in_words')" /> --}}
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice-amount-in-words"></label>
                <input type="text" id="invoice-amount-in-words" name="invoice_amount_in_words" class="form-control" readonly placeholder="In Words" aria-label="invoice_amount_in_wwords" required />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="drawdown-amount">
                  {{ __('Drawdown Amount')}}
                  <span class="text-danger">*</span>
                </label>
                <input type="text" id="drawdown-amount" class="form-control" name="drawdown_amount" placeholder="Drawdown Amount" aria-label="drawdown_amount" autocomplete="off" onblur="formatValue('drawdown-amount')" value="{{ old('drawdown_amount', $invoice ? number_format($invoice->drawdown_amount, 2) : '') }}" required />
                <span class="d-none text-danger" id="drawdown-amount-error">{{ __('Drawdown amount cannot be more that invoice amount') }}</span>
                <x-input-error :messages="$errors->get('drawdown_amount')" />
                <x-input-error :messages="$errors->get('drawdown_amount_in_words')" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="invoice-amount-in-words"></label>
                <input type="text" id="drawdown-amount-in-words" name="drawdown_amount_in_words" class="form-control" readonly placeholder="In Words" aria-label="invoice_amount_in_wwords" />
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="currency">
                  {{ __('Currency')}}
                  <span class="text-danger">*</span>
                </label>
                <select class="select2" id="currency" name="currency" required>
                  <option label=" "></option>
                  @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @if(($invoice && $invoice->currency == $currency->code) || (old('currency') == $currency->code)) selected @endif>{{ $currency->code }} ({{ $currency->name }})</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-12">
                <label class="form-label" for="remarks">{{ __('Remarks')}}</label>
                <input type="text" id="remarks" name="remarks" class="form-control" value="{{ old('remarks', $invoice ? $invoice->remarks : '') }}" placeholder="Remarks" aria-label="remarks" />
              </div>
              <div class="d-flex justify-content-between">
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
                          <button data-bs-dismiss="modal" type="button" class="btn btn-secondary">{{ __('Close') }}</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-end">
                  <div class="d-flex">
                    <a href="{{ route('dealer.invoices.index') }}" class="btn btn-label-secondary mx-1">
                      <span class="align-middle d-sm-inline-block d-none">{{ __('Cancel')}}</span>
                    </a>
                    <button class="btn btn-primary" id="submit-invoice"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit')}}</span></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Personal Info -->
          <div id="personal-info-vertical" class="content">
            <div class="content-header mb-3">
              <h6 class="mb-0">{{ __('Drafts')}}</h6>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Vertical Icons Wizard -->
</div>
@endsection
