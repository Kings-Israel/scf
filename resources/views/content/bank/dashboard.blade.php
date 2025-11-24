@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
@endsection

@section('page-style')
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/dashboards-analytics.js')}}"></script>
<script>
  let show_earnings_graph = false
  $('#earnings-graph').addClass('d-none')

  let cardColor, headingColor, labelColor, shadeColor, grayColor, legendColor, borderColor;
  if (isDarkStyle) {
    cardColor = config.colors_dark.cardColor;
    labelColor = config.colors_dark.textMuted;
    headingColor = config.colors_dark.headingColor;
    shadeColor = 'dark';
    grayColor = '#5E6692'; // gray color is for stacked bar chart
    legendColor = config.colors_dark.bodyColor;
    borderColor = config.colors_dark.borderColor;
  } else {
    cardColor = config.colors.cardColor;
    labelColor = config.colors.textMuted;
    headingColor = config.colors.headingColor;
    shadeColor = '';
    grayColor = '#817D8D';
    legendColor = config.colors.bodyColor;
    borderColor = config.colors.borderColor;
  }

  // Color constant
  const chartColors = {
    column: {
      series1: '#826af9',
      series2: '#d2b0ff',
      bg: '#f8d3ff'
    },
    donut: {
      series1: '#0555AD',
      series2: '#9BBBDE',
      series3: '#FF9F43',
      series4: '#FFB269',
      series5: '#FFC58E',
      series6: '#FFD9B4',
    },
    area: {
      series1: '#29dac7',
      series2: '#60f2ca',
      series3: '#a5f8cd'
    }
  };

  'use strict';

  function EarningReportsBarChart(arrayData, months) {
    const basicColor = config.colors_label.primary,
      highlightColor = '#0154AF';
    var colorArr = [];

    colorArr.push(highlightColor);

    const earningReportBarChartOpt = {
      chart: {
        height: 258,
        parentHeightOffset: 0,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          columnWidth: '32%',
          startingShape: 'rounded',
          borderRadius: 7,
          distributed: true,
          dataLabels: {
            position: 'top'
          }
        }
      },
      grid: {
        show: false,
        padding: {
          top: 0,
          bottom: 0,
          left: -10,
          right: -10
        }
      },
      colors: colorArr,
      dataLabels: {
        enabled: false,
        formatter: function (val) {
          return new Intl.NumberFormat().format(val);
        },
        offsetY: -25,
        style: {
          fontSize: '15px',
          colors: [legendColor],
          fontWeight: '600',
          fontFamily: 'Public Sans'
        }
      },
      series: [
        {
          data: arrayData
        }
      ],
      legend: {
        show: false
      },
      tooltip: {
        enabled: true
      },
      xaxis: {
        categories: months,
        axisBorder: {
          show: true,
          color: borderColor
        },
        axisTicks: {
          show: false
        },
        labels: {
          style: {
            colors: labelColor,
            fontSize: '13px',
            fontFamily: 'Public Sans'
          }
        }
      },
      yaxis: {
        labels: {
          offsetX: -15,
          formatter: function (val) {
            return new Intl.NumberFormat().format(parseInt(val / 1));
          },
          style: {
            fontSize: '13px',
            colors: labelColor,
            fontFamily: 'Public Sans'
          },
          min: 0,
          max: 60000,
          tickAmount: 6
        }
      },
      responsive: [
        {
          breakpoint: 1441,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '41%'
              }
            }
          }
        },
        {
          breakpoint: 590,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '61%',
                borderRadius: 5
              }
            },
            yaxis: {
              labels: {
                show: false
              }
            },
            grid: {
              padding: {
                right: 0,
                left: -20
              }
            },
            dataLabels: {
              style: {
                fontSize: '12px',
                fontWeight: '400'
              }
            }
          }
        }
      ]
    };
    return earningReportBarChartOpt;
  }

  let bank = {!! json_encode($bank) !!}
  let months = {!! json_encode($months_formatted) !!}
  assets = '/'+bank.url+'/graph-data/'

  let earningReportsTabsOrders = null
  let earningReportsTabsSales = null
  let earningReportsTabsProfit = null

  const earningReportsTabsOrdersEl = document.querySelector('#earningReportsTabsOrders')
  const earningReportsTabsSalesEl = document.querySelector('#earningReportsTabsSales')
  const earningReportsTabsProfitEl = document.querySelector('#earningReportsTabsProfit')

  function earningsReport() {
    // Earning Chart JSON data
    var earningReportsChart = $.ajax({
      url: assets,
      dataType: 'json',
      async: false
    }).responseJSON;

    // Earning Reports Tabs Orders
    // --------------------------------------------------------------------
    const earningReportsTabsOrdersConfig = EarningReportsBarChart(
        earningReportsChart.disbursed_amount_data,
        months,
      );

    earningReportsTabsOrders = new ApexCharts(earningReportsTabsOrdersEl, earningReportsTabsOrdersConfig);
    earningReportsTabsOrders.render();

    // Earning Reports Tabs Sales
    // --------------------------------------------------------------------
    const earningReportsTabsSalesConfig = EarningReportsBarChart(
        earningReportsChart.income_data,
        months,
      );

    earningReportsTabsSales = new ApexCharts(earningReportsTabsSalesEl, earningReportsTabsSalesConfig);
    earningReportsTabsSales.render();

    // Earning Reports Tabs Profit
    // --------------------------------------------------------------------
    const earningReportsTabsProfitConfig = EarningReportsBarChart(
        earningReportsChart.pi_amount_data,
        months,
      );

    earningReportsTabsProfit = new ApexCharts(earningReportsTabsProfitEl, earningReportsTabsProfitConfig);
    earningReportsTabsProfit.render();
  }

  $('#timeline-filter').on('change', function() {
    let value = $(this).val();

    assets = '/'+bank.url+'/graph-data?timeline='+value;

    $.ajax({
      url: assets,
      dataType: 'json',
      async: true,
      success: function(data) {
        let reportsTabsOrdersConfig = EarningReportsBarChart(data.disbursed_amount_data, data.months_formatted);
        let reportsTabsSalesConfig = EarningReportsBarChart(data.income_data, data.months_formatted);
        let reportsTabsProfitConfig = EarningReportsBarChart(data.pi_amount_data, data.months_formatted);

        earningReportsTabsOrders.destroy();
        earningReportsTabsOrders = new ApexCharts(earningReportsTabsOrdersEl, reportsTabsOrdersConfig);
        earningReportsTabsOrders.render()

        earningReportsTabsSales.destroy();
        earningReportsTabsSales = new ApexCharts(earningReportsTabsSalesEl, reportsTabsSalesConfig);
        earningReportsTabsSales.render()

        earningReportsTabsProfit.destroy();
        earningReportsTabsProfit = new ApexCharts(earningReportsTabsProfitEl, reportsTabsProfitConfig);
        earningReportsTabsProfit.render()
      },
      error: function (err) {
        console.log(err)
      }
    });
  })

  function toggleEarningsGraph() {
    show_earnings_graph = !show_earnings_graph
    if (show_earnings_graph) {
      $('#show-earnings-graph-span').text('Loading Data...')
      earningsReport()
      $('#show-earnings-graph-span').text('Show Earnings Graph')
      $('#earnings-graph').removeClass('d-none')
      $('#hide-earnings-graph').removeClass('d-none')
      $('#show-earnings-graph').addClass('d-none')
    } else {
      $('#earnings-graph').addClass('d-none')
      $('#show-earnings-graph').removeClass('d-none')
      $('#hide-earnings-graph').addClass('d-none')
    }
  }
</script>
@endsection

@section('content')
<h4 class="fw-bold py-1 mb-2">
  <span class="fw-light">{{ $bank->name }}</span>
</h4>

<div id="dashboard-cards">
  <dashboard-cards
    bank={{ request()->route('bank')->url }}
    can_view_vendor_financing_requests={{ auth()->user()->hasPermissionTo('View Vendor Financing Requests') ? '1' : 0 }}
    can_view_dealer_financing_requests={{ auth()->user()->hasPermissionTo('View Dealer Financing Requests') ? '1' : 0 }}
    date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"
  ></dashboard-cards>
</div>

<!-- Latest Vendor Financing Requests -->
@can('View Vendor Financing Requests')
  <div id="dashboard-vendor-financing">
    <dashboard-vendor-financing bank={{ request()->route('bank')->url }} date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"></dashboard-vendor-financing>
  </div>
  <br>
@endcan
<!--/ Latest Vendor Financing Requests -->

<!-- Latest Factoring Requests -->
@can('View Vendor Financing Requests')
  <div id="dashboard-factoring-requests">
    <dashboard-factoring-requests bank={{ request()->route('bank')->url }} date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"></dashboard-factoring-requests>
  </div>
  <br>
@endcan
<!--/ Latest Factoring Requests -->

<!-- Latest Dealer Financing Requests -->
@can('View Dealer Financing Requests')
  <div id="dashboard-dealer-financing">
    <dashboard-dealer-financing bank={{ request()->route('bank')->url }} date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"></dashboard-dealer-financing>
  </div>
  <br>
@endcan
<!--/ Latest Dealer Financing Requests -->

<button type="button" class="btn btn-primary my-2" id="show-earnings-graph" onclick="toggleEarningsGraph()"><span id="show-earnings-graph-span">{{ __('Show Earnings Report') }}</span></button>
<button type="button" class="btn btn-secondary my-2 d-none" id="hide-earnings-graph" onclick="toggleEarningsGraph()"><span>{{ __('Hide Earnings Report') }}</span></button>

<!-- Earnings Report -->
<div class="card" id="earnings-graph">
  <div class="card-header d-flex justify-content-between">
    <div class="card-title m-0">
      <h5 class="mb-0">{{ __('Earnings Report')}}</h5>
    </div>
    <div class="p-0">
      <label for="timeline-filter" class="form-label">{{ __('Select Timeline')}}</label>
      <select class="form-select" id="timeline-filter" name="timeline_filter">
        <option value="past_five_years">{{ __('Past 5 Years')}}</option>
        <option value="past_three_years">{{ __('Past 3 Years')}}</option>
        <option value="past_year" selected>{{ __('Past Year')}}</option>
        <option value="past_six_months">{{ __('Past 6 Months')}}</option>
        <option value="past_three_months">{{ __('Past 3 Months')}}</option>
      </select>
    </div>
  </div>
  <div class="card-body">
    <ul class="nav nav-tabs widget-nav-tabs pb-3 gap-4 mx-1 d-flex flex-nowrap" role="tablist">
      <li class="nav-item">
        <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-orders-id" aria-controls="navs-orders-id" aria-selected="true">
          <h6 class="tab-widget-title mb-0 mt-2">{{ __('Disbursed Value')}}</h6>
        </a>
      </li>
      <li class="nav-item">
        <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-sales-id" aria-controls="navs-sales-id" aria-selected="false">
          <h6 class="tab-widget-title mb-0 mt-2"> {{ __('Total Income')}}</h6>
        </a>
      </li>
      <li class="nav-item">
        <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-profit-id" aria-controls="navs-profit-id" aria-selected="false">
          <h6 class="tab-widget-title mb-0 mt-2">{{ __('Total PI Value')}}</h6>
        </a>
      </li>
    </ul>
    <div class="tab-content p-0 ms-0 ms-sm-2">
      <div class="tab-pane fade show active" id="navs-orders-id" role="tabpanel">
        <div id="earningReportsTabsOrders"></div>
      </div>
      <div class="tab-pane fade" id="navs-sales-id" role="tabpanel">
        <div id="earningReportsTabsSales"></div>
      </div>
      <div class="tab-pane fade" id="navs-profit-id" role="tabpanel">
        <div id="earningReportsTabsProfit"></div>
      </div>
    </div>
  </div>
</div>
<!--/ Earnings Report -->
@endsection
