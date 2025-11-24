@extends('layouts/anchorFactoringLayoutMaster')

@section('title', 'Fund Planner')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
@endsection

@section('page-style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.css" integrity="sha512-YdYyWQf8AS4WSB0WWdc3FbQ3Ypdm0QCWD2k4hgfqbQbRCJBEgX0iAegkl2S1Evma5ImaVXLBeUkIlP6hQ1eYKQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
@endsection

@section('page-script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.js" integrity="sha512-RCgrAvvoLpP7KVgTkTctrUdv7C6t7Un3p1iaoPr1++3pybCyCsCZZN7QEHMZTcJTmcJ7jzexTO+eFpHk4OCFAg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
  $(function() {
    $('input[name="daterange"]').daterangepicker({
      "showDropdowns": true,
      autoUpdateInput: false,
      ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      },
      "alwaysShowCalendars": true,
      opens: 'left'
    });

    $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
  });
  $('#select_anchor').on('change', function (e) {
    e.preventDefault();
    $.get(`../factoring/invoices/create/${$(this).val()}/programs`, function (data, status) {
      let programOptions = document.getElementById('program_options')

      while (programOptions.options.length) {
        programOptions.remove(0)
      }
      programOptions.options.add(new Option('Select Program', ''))
      if (data.programs) {
        var i
        for (let i = 0; i < data.programs.length; i++) {
          var program = new Option(data.programs[i].payment_account_number, data.programs[i].program_id)
          programOptions.options.add(program)
        }
      }
    })
  })

  let max_days = 0

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

  let max_date = ''

  let off_days = {!! json_encode($off_days) !!}
  let holidays = {!! json_encode($holidays) !!}
  holidays.forEach(holiday => {
    off_days.push(holiday.date_formatted)
  });

  $('#program_options').on('change', function () {
    $('#selected_program').val($(this).val())
    $('#pi_amount').val(0)
    $.get(`../factoring/cash-planner/program/${$(this).val()}/financing/eligible`, function (data, status) {
      if (data.error && data.error != '') {
        $('#show_invoices_error').removeClass('d-none')
      } else {
        $('#show_invoices_error').addClass('d-none')
        $('#pi_amount').val(Number(data.total_amount).toLocaleString())
        max_days = data.min_financing_days

        var max_date = new Date(data.max_date);
        var min_date = new Date(data.min_date);

        var dd = max_date.getDate();
        var mm = max_date.getMonth() + 1; //January is 0!
        var yyyy = max_date.getFullYear();

        if (dd < 10) {
          dd = '0' + dd;
        }

        if (mm < 10) {
          mm = '0' + mm;
        }

        var min_dd = min_date.getDate();
        var min_mm = min_date.getMonth() + 1; //January is 0!
        var min_yyyy = min_date.getFullYear();

        if (min_dd < 10) {
          min_dd = '0' + min_dd;
        }

        if (min_mm < 10) {
          min_mm = '0' + min_mm;
        }

        max_date = yyyy + '-' + mm + '-' + dd;
        min_date = min_yyyy + '-' + min_mm + '-' + min_dd;
        $('.payment_date').attr("max", max_date);
        $('.payment_date').attr("min", min_date);
        $('.payment_date_temp').addClass("d-none");
        $('.datepicker').removeClass("d-none");
        $('.datepicker').attr('data-date-end-date', max_days+"d")
        $('.datepicker').datepicker({
          format: 'yyyy-mm-dd',
          startDate: today,
          daysOfWeekDisabled: off_days,
          endDate: max_date
        });
      }
    })
  })

  $('.payment_date').on('change', function () {
    let program_id = $('#program_options').val();
    $('#selected_date').val($(this).val())
    $.get(`../factoring/cash-planner/program/${program_id}/${$(this).val()}/financing/calculate`, function (data, status) {
      $('#total_discount').val(Number(data[0]).toLocaleString())
      $('#actual_remittance').val(Number(data[1]).toLocaleString())
      $('#submit-invoices').removeClass('disabled')
      $('#export-invoices').removeClass('disabled')
    })
  })

  $('.payment_date').attr("min", today);
  $('.calculator_date').attr("min", today);

  $('#calculator_program').on('change', function() {
    let program = $(this).find(':selected').data('program')
    var max_date = new Date();
    max_date.setDate(max_date.getDate() + program.default_payment_terms)
    var dd = max_date.getDate();
    var mm = max_date.getMonth() + 1; //January is 0!
    var yyyy = max_date.getFullYear();

    if (dd < 10) {
      dd = '0' + dd;
    }

    if (mm < 10) {
      mm = '0' + mm;
    }

    max_date = yyyy + '-' + mm + '-' + dd;
    $('.calculator_date').attr("max", max_date);

    if ($('#calculator_pi_amount').val() != '') {
      let program_id = $('#calculator_program').val();
      // let amount = $('#calculator_pi_amount').val().replaceAll(',', '');
      let amount = $('#calculator_pi_amount').val();
      let date = $('.calculator_date').val();
      $.get(`../factoring/cash-planner/program/planner/calculate`,
      {
        program: program_id,
        amount: amount,
        date: date,
      },
      function (data, status) {
        $('#planner_total_discount').val(Number(data[0]).toLocaleString())
        $('#planner_actual_remittance').val(Number(data[1]).toLocaleString())
      })
    }
  })

  $('.calculator_invoice_date').on('input', function() {
    let amount = $('#calculator_pi_amount').val();

    if ($('#calculator_program').val() != '') {
      let program_id = $('#calculator_program').val();
      let invoice_date = $('.calculator_invoice_date').val();
      let due_date = $('.calculator_due_date').val();
      if (amount != '' && invoice_date != '' && due_date != '') {
        $.get(`../factoring/cash-planner/program/planner/calculate`,
        {
          program: program_id,
          amount: amount,
          invoice_date: invoice_date,
          due_date: due_date,
        },
        function (data, status) {
          $('#planner_total_discount').val(Number(data[0]).toLocaleString())
          $('#planner_actual_remittance').val(Number(data[1]).toLocaleString())
        })
      }
    }
  })

  $('.calculator_due_date').on('input', function() {
    let amount = $('#calculator_pi_amount').val();

    if ($('#calculator_program').val() != '') {
      let program_id = $('#calculator_program').val();
      let invoice_date = $('.calculator_invoice_date').val();
      let due_date = $('.calculator_due_date').val();
      if (amount != '' && invoice_date != '' && due_date != '') {
        $.get(`../factoring/cash-planner/program/planner/calculate`,
        {
          program: program_id,
          amount: amount,
          invoice_date: invoice_date,
          due_date: due_date,
        },
        function (data, status) {
          $('#planner_total_discount').val(Number(data[0]).toLocaleString())
          $('#planner_actual_remittance').val(Number(data[1]).toLocaleString())
        })
      }
    }
  })

  $('#calculator_pi_amount').on('input', function() {
    let amount = $('#calculator_pi_amount').val();

    if ($('#calculator_program').val() != '') {
      let program_id = $('#calculator_program').val();
      let invoice_date = $('.calculator_invoice_date').val();
      let due_date = $('.calculator_due_date').val();
      if (amount != '' && invoice_date != '' && due_date != '') {
        $.get(`../factoring/cash-planner/program/planner/calculate`,
        {
          program: program_id,
          amount: amount,
          invoice_date: invoice_date,
          due_date: due_date,
        },
        function (data, status) {
          $('#planner_total_discount').val(Number(data[0]).toLocaleString())
          $('#planner_actual_remittance').val(Number(data[1]).toLocaleString())
        })
      }
    }
  })

  $('#calculator_program').on('change', function() {
    let amount = $('#calculator_pi_amount').val();

    if ($('#calculator_program').val() != '') {
      let program_id = $('#calculator_program').val();
      let invoice_date = $('.calculator_invoice_date').val();
      let due_date = $('.calculator_due_date').val();
      if (amount != '' && invoice_date != '' && due_date != '') {
        $.get(`../factoring/cash-planner/program/planner/calculate`,
        {
          program: program_id,
          amount: amount,
          invoice_date: invoice_date,
          due_date: due_date,
        },
        function (data, status) {
          $('#planner_total_discount').val(Number(data[0]).toLocaleString())
          $('#planner_actual_remittance').val(Number(data[1]).toLocaleString())
        })
      }
    }
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold py-2">
  <span class="fw-light">{{ __('Cash Planner') }}</span>
</h4>

<div class="card">
  <div id="factoring-cash-planner-programs">
    <factoring-cash-planner-programs></factoriing-cash-planner-programs>
  </div>
</div>

<br>

<div class="card">
  <div class="card-header">
    <span>{{ __('Fund Planner') }}</span>
  </div>
  <div class="card-body">
    <form action="{{ route('anchor.factoring-cash-planner.financing_requests.store') }}" method="post">
      @csrf
      <div class="row clearfix">
        <div class="col-sm-6">
          <label class="form-label" for="select_anchor">{{ __('Select Buyer')}}</label>
          <select class="form-select" id="select_anchor" name="anchor">
            <option label=" "></option>
            @foreach ($anchors as $anchor)
              <option value="{{ $anchor->id }}">{{ $anchor->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="select_program">{{ __('Select Program')}}</label>
          <select class="form-select" id="program_options" name="program_id">
            <option label=" "></option>
          </select>
          <span class="text-danger d-none" id="show_invoices_error">{{ __('No Eligible Invoices in this program')}}</span>
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="associated_po">{{ __('Payment Instruction Eligible For Financing')}}</label>
          <input type="text" id="pi_amount" class="form-control" readonly />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="associated_po">{{ __('Date') }}</label>
          <input type="date" id="html5-date-input" name="payment_date" class="form-control payment_date" />
          {{-- <div class="input-group date datepicker d-none" data-provide="datepicker">
            <input type="text" class="form-control payment_date" name="payment_date">
            <div class="input-group-addon p-1">
              <span class="glyphicon glyphicon-th"></span>
            </div>
          </div> --}}
          <x-input-error :messages="$errors->get('payment_date')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="credit_to">{{ __('Total Discount') }}</label>
          <input type="text" id="total_discount" class="form-control" readonly />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="credit_to">{{ __('Actual Remittance') }}</label>
          <input type="text" id="actual_remittance" class="form-control" readonly />
        </div>
        <div class="col-12 d-flex mt-2">
          <button class="btn btn-primary" type="submit"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit') }}</span></button>
        </div>
      </div>
    </form>
    <form action="{{ route('anchor.factoring-cash-planner.financing_requests.export') }}" method="post">
      @csrf
      <input type="hidden" name="selected_program" id="selected_program">
      <input type="hidden" name="selected_date" id="selected_date">
      <button class="btn btn-secondary mt-2 disabled" id="export-invoices" type="submit">
        {{ __('Export Invoices') }}
      </button>
    </form>
  </div>
</div>
<br>
<div class="card">
  <div class="card-header">
    <h6>{{ __('Fund Planner') }}</h6>
  </div>
  <div class="card-body">
    <form action="#" method="post">
      @csrf
      <div class="row clearfix">
        <div class="col-sm-3">
          <label class="form-label" for="select_program">{{ __('Select Program')}}</label>
          <select class="form-select select2" id="calculator_program" name="program_id">
            <option label="Select Program"></option>
            @foreach ($programs as $program)
              <option value="{{ $program->id }}" data-program="{{ $program->program }}">{{ $program->payment_account_number }} ({{ $program->buyer->name }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label" for="associated_po">{{ __('Payment Instruction Amount')}}</label>
          <input type="number" id="calculator_pi_amount" class="form-control" min="1" step=".01" autocomplete="off" />
        </div>
        <div class="col-sm-3">
          <label class="form-label">{{ __('Invoice Date') }}</label>
          <input type="date" id="html5-date-input" class="form-control calculator_invoice_date" />
        </div>
        <div class="col-sm-3">
          <label class="form-label">{{ __('Due Date') }}</label>
          <input type="date" id="html5-date-input" class="form-control calculator_due_date" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="credit_to">{{ __('Total Discount')}}</label>
          <input type="text" id="planner_total_discount" class="form-control" readonly />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="credit_to">{{ __('Actual Remittance')}}</label>
          <input type="text" id="planner_actual_remittance" class="form-control" readonly />
        </div>
      </div>
    </form>
  </div>
</div>
<br>
<div id="eligible-invoices">
  <cash-planner-eligible-invoices-component can_request={{ auth()->user()->hasPermissionTo('Request Seller Finance') }}></cash-planner-eligible-invoices-component>
</div>
<br>
<div id="non-eligible-invoices">
  <cash-planner-non-eligible-invoices-component></cash-planner-non-eligible-invoices-component>
</div>
<br>
@endsection
