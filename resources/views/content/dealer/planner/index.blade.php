@extends('layouts/buyerLayoutMaster')

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
  })

  $(function() {
    $('input[name="non_eligible_daterange"]').daterangepicker({
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

    $('input[name="non_eligible_daterange"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
  })

  $('#select_anchor').on('change', function (e) {
    e.preventDefault();
    $.get(`../dealer/invoices/create/${$(this).val()}/programs`, function (data, status) {
      let programOptions = document.getElementById('program_options')

      while (programOptions.options.length) {
        programOptions.remove(0)
      }
      programOptions.options.add(new Option('', ''))
      if (data.programs) {
        var i
        for (let i = 0; i < data.programs.length; i++) {
          var program = new Option(data.programs[i].code, data.programs[i].id)
          programOptions.options.add(program)
        }
      }
    })
  })

  $('#program_options').on('change', function () {
    $.get(`../dealer/planner/program/${$(this).val()}/financing/eligible`, function (data, status) {
      if (data.error && data.error != '') {
        $('#show_invoices_error').removeClass('d-none')
      } else {
        $('#pi_amount').val(Number(data.total_amount).toLocaleString())
        var max_date = new Date(data.max_date);

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
        $('.payment_date').attr("max", max_date);
      }
    })
  })

  $('.payment_date').on('change', function () {
    let program_id = $('#program_options').val();
    $.get(`../dealer/planner/program/${program_id}/${$(this).val()}/financing/calculate`, function (data, status) {
      $('#total_discount').val(Number(data[0]).toLocaleString())
      $('#actual_remittance').val(Number(data[1]).toLocaleString())
    })
  })

  let max_days = {!! json_encode($max_day) !!}

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
  $('.payment_date').attr("min", today);

  $('.calculator_invoice_date').attr("min", today);

  $('#calculator_program').on('change', function() {
    let program = $(this).find(':selected').data('program')
    var max_date = new Date();
    max_date.setDate(max_date.getDate() + max_days)
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
    $('.calculator_due_date').attr("max", max_date);

    if ($('#calculator_pi_amount').val() != '') {
      let program_id = $('#calculator_program').val();
      // let amount = $('#calculator_pi_amount').val().replaceAll(',', '');
      let amount = $('#calculator_pi_amount').val();
      let date = $('.calculator_date').val();
      $.get(`planner/calculate`,
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
    $('.calculator_due_date').attr("max", null);
    var max_date = new Date($('.calculator_invoice_date').val());
    max_date.setDate(max_date.getDate() + max_days)
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

    let due_date_min = new Date($('.calculator_invoice_date').val());
    due_date_min.setDate(due_date_min.getDate() + 1)
    var due_dd = due_date_min.getDate();
    var due_mm = due_date_min.getMonth() + 1; //January is 0!
    var due_yyyy = due_date_min.getFullYear();

    if (due_dd < 10) {
      due_dd = '0' + due_dd;
    }

    if (due_mm < 10) {
      due_mm = '0' + due_mm;
    }

    due_date_min = due_yyyy + '-' + due_mm + '-' + due_dd;

    $('.calculator_due_date').attr("min", due_date_min);
    $('.calculator_due_date').attr("max", max_date);

    if ($('#calculator_program').val() != '') {
      let program_id = $('#calculator_program').val();
      let invoice_date = $('.calculator_invoice_date').val();
      let due_date = $('.calculator_due_date').val();
      if (amount != '' && invoice_date != '' && due_date != '') {
        $.get(`planner/calculate`,
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
        $.get(`planner/calculate`,
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
        $.get(`planner/calculate`,
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
        $.get(`planner/calculate`,
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
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Fund Planner')}}</span>
</h4>

<div id="cash-planner-programs">
  <cash-planner-programs></cash-planner-programs>
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
              <option value="{{ $program->id }}" data-program="{{ $program->program }}">{{ $program->payment_account_number }} ({{ $program->program->anchor->name }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label" for="associated_po">{{ __('Payment Instruction Amount')}}</label>
          <input type="number" id="calculator_pi_amount" class="form-control" min="1" step=".01" autocomplete="off" />
        </div>
        <div class="col-sm-3">
          <label class="form-label">{{ __('Anchor Payment Date') }}</label>
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
@canany(['Request Dealer Finance', 'Initiate Drawdowns'])
  <div id="dealer-cash-planner-eligible-invoices">
    <dealer-cash-planner-eligible-invoices can_request={{ auth()->user()->hasAnyPermission(['Request Dealer Finance', 'Initiate Drawdowns']) }}></dealer-cash-planner-eligible-invoices>
  </div>
@endcanany
<br>
<div id="dealer-cash-planner-non-eligible-invoices">
  <dealer-cash-planner-non-eligible-invoices></dealer-cash-planner-non-eligible-invoices>
</div>

@endsection
