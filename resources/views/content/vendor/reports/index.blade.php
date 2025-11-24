@extends('layouts/vendorLayoutMaster')

@section('title', 'Reports')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
@endsection

@section('page-style')
<style>
  .tab-content {
    padding: 0px !important;
  }
  .nav-tabs .nav-link {
    font-weight: 900;
    font-size: 14px;
  }
</style>
@endsection

@section('vendor-script')
  <script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
  <script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script>
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
      series1: '#fee802',
      series2: '#3fd0bd',
      series3: '#826bf8',
      series4: '#2b9bf4'
    },
    area: {
      series1: '#29dac7',
      series2: '#60f2ca',
      series3: '#a5f8cd'
    }
  };

  'use strict';

  let data
  $(document).ready(function () {
    data = $.ajax({
      url: '/vendor/reports/dashboard/data', //? Use your own search api instead
      dataType: 'json',
      async: false
    }).responseJSON;

    const projectStatusEl = document.querySelector('#projectStatusChart'),
    projectStatusConfig = {
      chart: {
        height: 252,
        type: 'area',
        toolbar: false
      },
      markers: {
        strokeColor: 'transparent'
      },
      series: [
        {
          data: data.borrowing_levels.data
        }
      ],
      dataLabels: {
        enabled: true
      },
      grid: {
        show: false,
        padding: {
          left: -10,
          right: -5
        }
      },
      stroke: {
        width: 3,
        curve: 'straight'
      },
      colors: ['#0154AF'],
      fill: {
        type: 'gradient',
        gradient: {
          opacityFrom: 0.6,
          opacityTo: 0.15,
          stops: [0, 95, 100]
        }
      },
      xaxis: {
        labels: {
          show: true
        },
        axisBorder: {
          show: false
        },
        axisTicks: {
          show: false
        },
        lines: {
          show: false
        },
        categories: data.months,
      },
      yaxis: {
        labels: {
          show: false
        },
        min: data.borrowing_levels.min,
        max: data.borrowing_levels.max,
        tickAmount: 5
      },
      tooltip: {
        enabled: false
      }
    };

    if (typeof projectStatusEl !== undefined && projectStatusEl !== null) {
      const projectStatus = new ApexCharts(projectStatusEl, projectStatusConfig);
      projectStatus.render();
    }

    function EarningReportsBarChart(arrayData, months) {
      const highlightColor = '#0154AF';
      var colorArr = [];

      for (let i = 0; i < arrayData.length; i++) {
        colorArr.push(highlightColor);
      }

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
          enabled: true,
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
          enabled: false
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

    // Earning Reports Tabs Orders
    // --------------------------------------------------------------------
    const earningReportsTabsOrdersEl = document.querySelector('#earningReportsTabsOrders'),
      earningReportsTabsOrdersConfig = EarningReportsBarChart(data.all_invoices, data.months);
    if (typeof earningReportsTabsOrdersEl !== undefined && earningReportsTabsOrdersEl !== null) {
      const earningReportsTabsOrders = new ApexCharts(earningReportsTabsOrdersEl, earningReportsTabsOrdersConfig);
      earningReportsTabsOrders.render();
    }

    // Earning Reports Tabs Sales
    // --------------------------------------------------------------------
    const earningReportsTabsSalesEl = document.querySelector('#earningReportsTabsSales'),
      earningReportsTabsSalesConfig = EarningReportsBarChart(data.pending_invoices, data.months);
    if (typeof earningReportsTabsSalesEl !== undefined && earningReportsTabsSalesEl !== null) {
      const earningReportsTabsSales = new ApexCharts(earningReportsTabsSalesEl, earningReportsTabsSalesConfig);
      earningReportsTabsSales.render();
    }

    // Earning Reports Tabs Income
    // --------------------------------------------------------------------
    const earningReportsTabsIncomeEl = document.querySelector('#earningReportsTabsIncome'),
      earningReportsTabsIncomeConfig = EarningReportsBarChart(data.discount_amounts, data.months);
    if (typeof earningReportsTabsIncomeEl !== undefined && earningReportsTabsIncomeEl !== null) {
      const earningReportsTabsIncome = new ApexCharts(earningReportsTabsIncomeEl, earningReportsTabsIncomeConfig);
      earningReportsTabsIncome.render();
    }
  })

  $('*[data-bs-target="#navs-invoice-analysis"]').on('click', function (e) {
    $('#navs-invoice-analysis').removeClass('d-none')
    $('#navs-all-invoices-report').addClass('d-none')
    $('#navs-vendor-analysis').addClass('d-none')
    $('#navs-maturing-invoices-report').addClass('d-none')
    $('#navs-paid-invoices-report').addClass('d-none')
  })

  $('*[data-bs-target="#navs-all-invoices-report"]').on('click', function (e) {
    $('#navs-all-invoices-report').removeClass('d-none')
    $('#navs-invoice-analysis').addClass('d-none')
    $('#navs-vendor-analysis').addClass('d-none')
    $('#navs-maturing-invoices-report').addClass('d-none')
    $('#navs-paid-invoices-report').addClass('d-none')
  })

  $('*[data-bs-target="#navs-vendor-analysis"]').on('click', function (e) {
    $('#navs-vendor-analysis').removeClass('d-none')
    $('#navs-all-invoices-report').addClass('d-none')
    $('#navs-invoice-analysis').addClass('d-none')
    $('#navs-maturing-invoices-report').addClass('d-none')
    $('#navs-paid-invoices-report').addClass('d-none')
  })

  $('*[data-bs-target="#navs-maturing-invoices-report"]').on('click', function (e) {
    $('#navs-maturing-invoices-report').removeClass('d-none')
    $('#navs-all-invoices-report').addClass('d-none')
    $('#navs-invoice-analysis').addClass('d-none')
    $('#navs-vendor-analysis').addClass('d-none')
    $('#navs-paid-invoices-report').addClass('d-none')
  })

  $('*[data-bs-target="#navs-paid-invoices-report"]').on('click', function (e) {
    $('#navs-paid-invoices-report').removeClass('d-none')
    $('#navs-all-invoices-report').addClass('d-none')
    $('#navs-invoice-analysis').addClass('d-none')
    $('#navs-vendor-analysis').addClass('d-none')
    $('#navs-maturing-invoices-report').addClass('d-none')
  })
</script>
@endsection

@section('content')
<div class="row">
  @can('View Funding Limit Utilization Report')
    <div class="col-xl-4 col-md-4 col-6 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between">
          <h5 class="mb-0 card-title">{{ __('Limit Utilization Report')}}</h5>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-start">
            <div class="badge rounded bg-label-primary p-2 me-3 rounded"><i class="ti ti-currency-dollar ti-sm"></i></div>
            <div class="d-flex justify-content-between w-100 gap-2 align-items-center">
              <div class="me-2">
                <h6 class="mb-0">Ksh {{ number_format($total_program_limit) }}</h6>
              </div>
            </div>
          </div>
          <div id="projectStatusChart"></div>
          <div class="d-flex justify-content-between mb-3">
            <h6 class="mb-0">{{ __('Available Limit')}}</h6>
            <div class="d-flex">
              <p class="mb-0 me-3">Ksh. {{ number_format($available_amount) }}</p>
            </div>
          </div>
          <div class="d-flex justify-content-between mb-3 pb-1">
            <h6 class="mb-0">{{ __('Utilized Limit')}}</h6>
            <div class="d-flex">
              <p class="mb-0 me-3">Ksh. {{ number_format($utilized_amount) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endcan
  <div class="col-12 col-xl-8 mb-4 order-1 order-xl-2">
    <div class="card h-100">
      @can('View All Invoices Report')
        <div class="card-header d-flex justify-content-between">
          <div class="card-title m-0">
            <h5 class="mb-0">{{ __('Invoices Reports')}}</h5>
          </div>
          <div class="dropdown">
            <button class="btn p-0" type="button" id="earningReportsTabsId" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="ti ti-dots-vertical ti-sm text-muted"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsTabsId">
              <a class="dropdown-item" href="javascript:void(0);">{{ __('View More')}}</a>
              <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete')}}</a>
            </div>
          </div>
        </div>
      @endcan
      <div class="card-body">
        <ul class="nav nav-tabs widget-nav-tabs pb-3 gap-4 mx-1 d-flex flex-nowrap" role="tablist">
          @can('View All Invoices Report')
            <li class="nav-item">
              <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-orders-id" aria-controls="navs-orders-id" aria-selected="true">
                <div class="badge bg-label-secondary rounded p-2"><i class="ti ti-shopping-cart ti-sm"></i></div>
                <h6 class="tab-widget-title mb-0 mt-2">{{ __('All Invoices')}}</h6>
              </a>
            </li>
          @endcan
          @can('View All Invoices Report')
            <li class="nav-item">
              <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-sales-id" aria-controls="navs-sales-id" aria-selected="false">
                <div class="badge bg-label-secondary rounded p-2"><i class="ti ti-chart-bar ti-sm"></i></div>
                <h6 class="tab-widget-title mb-0 mt-2"> {{ __('Pending Approvals')}}</h6>
              </a>
            </li>
          @endcan
          @can('View IF - Payment Details Report')
            <li class="nav-item">
              <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-income-id" aria-controls="navs-income-id" aria-selected="false">
                <div class="badge bg-label-secondary rounded p-2"><i class="ti ti-chart-pie-2 ti-sm"></i></div>
                <h6 class="tab-widget-title mb-0 mt-2">{{ __('Discount Value')}}</h6>
              </a>
            </li>
          @endcan
        </ul>
        <div class="tab-content p-0 ms-0 ms-sm-2">
          @can('View All Invoices Report')
            <div class="tab-pane fade show active" id="navs-orders-id" role="tabpanel">
              <div id="earningReportsTabsOrders"></div>
            </div>
          @endcan
          @can('View Financing Report')
            <div class="tab-pane fade" id="navs-sales-id" role="tabpanel">
              <div id="earningReportsTabsSales"></div>
            </div>
          @endcan
          @can('View IF - Payment Details Report')
            <div class="tab-pane fade" id="navs-income-id" role="tabpanel">
              <div id="earningReportsTabsIncome"></div>
            </div>
          @endcan
        </div>
      </div>
    </div>
  </div>
</div>

<div id="vendor-reports">
  <vendor-reports
    can_view_all_invoices={{ auth()->user()->hasPermissionTo('View All Invoices Report') ? 1 : 0 }}
    can_view_programs={{ auth()->user()->hasPermissionTo('View Financing Report') ? 1 : 0 }}
    can_view_payments={{ auth()->user()->hasPermissionTo('View IF - Payment Details Report') ? 1 : 0 }}
  ></vendor-reports>
</div>
@endsection
