@extends('layouts/buyerLayoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
@endsection

@section('vendor-script')
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
    shadeColor = '#0154B0';
    grayColor = '#817D8D';
    legendColor = config.colors.bodyColor;
    borderColor = config.colors.borderColor;
  }

  // Color constant
  const chartColors = {
    column: {
      series1: '#826af9',
      series2: '#d2b0ff',
      bg: '#0154B0'
    },
    donut: {
      series1: '#0555AD',
      series2: '#9BBBDE',
      series3: '#FF9F43',
      series4: '#FFB269',
      series5: '#FFC58E',
      series6: '#FFD9B4',
      series7: '#29dac7',
      series8: '#36b6d8',
      series9: '#47c3e9',
      series10: '#58eef0',
    },
    area: {
      series1: '#29dac7',
      series2: '#60f2ca',
      series3: '#a5f8cd'
    }
  };

  'use strict';

  function EarningReportsBarChart(arrayData, highlightData) {
    const basicColor = config.colors_label.primary,
      highlightColor = '#0154B0';
    var colorArr = [];

    for (let i = 0; i < arrayData.length; i++) {
      colorArr.push(highlightColor);
    }

    let months = @json($months_formatted);

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
  var chartJson = 'earning-reports-charts.json';
  // Earning Chart JSON data
  var earningReportsChart = $.ajax({
    url: 'dashboard/invoices/data', //? Use your own search api instead
    dataType: 'json',
    async: false
  }).responseJSON;

  // Earning Reports Tabs Orders
  // --------------------------------------------------------------------
  const earningReportsTabsOrdersEl = document.querySelector('#earningReportsTabsOrders'),
    earningReportsTabsOrdersConfig = EarningReportsBarChart(earningReportsChart['pending_payment_requests_data']);
  if (typeof earningReportsTabsOrdersEl !== undefined && earningReportsTabsOrdersEl !== null) {
    const earningReportsTabsOrders = new ApexCharts(earningReportsTabsOrdersEl, earningReportsTabsOrdersConfig);
    earningReportsTabsOrders.render();
  }

  // Earning Reports Tabs Profit
  // --------------------------------------------------------------------
  const earningReportsTabsProfitEl = document.querySelector('#earningReportsTabsProfit'),
    earningReportsTabsProfitConfig = EarningReportsBarChart(
      earningReportsChart['paid_payment_requests_data']
    );
  if (typeof earningReportsTabsProfitEl !== undefined && earningReportsTabsProfitEl !== null) {
    const earningReportsTabsProfit = new ApexCharts(earningReportsTabsProfitEl, earningReportsTabsProfitConfig);
    earningReportsTabsProfit.render();
  }

  document.getElementById('total-program-limit').innerHTML = new Intl.NumberFormat().format(earningReportsChart.total_program_limit);
  document.getElementById('total-available-limit').innerHTML = new Intl.NumberFormat().format(earningReportsChart.total_available_limit.toFixed(2));
  document.getElementById('total-utilized-limit').innerHTML = new Intl.NumberFormat().format(earningReportsChart.total_utilized.toFixed(2));
  document.getElementById('total-pipeline-limit').innerHTML = new Intl.NumberFormat().format(earningReportsChart.total_pipeline.toFixed(2));
  document.getElementById('total-overdue-amount').innerHTML = new Intl.NumberFormat().format(earningReportsChart.total_overdue_amount.toFixed(2));

  // Donut Chart
  // --------------------------------------------------------------------
  let invoices = @json($invoices)

  let total_invoices = invoices.approved + invoices.closed + invoices.financed + invoices.disbursed + invoices.rejected + invoices.past_due + invoices.expired;

  const donutChartEl = document.querySelector('#donutChart'),
    donutChartConfig = {
      chart: {
        height: 400,
        type: 'donut'
      },
      labels: [invoices.pending+' Pending', invoices.approved+' Approved', invoices.financed+' Financed', invoices.disbursed+' Disbursed', invoices.closed+' Closed', invoices.rejected+' Rejected', invoices.past_due+' Past Due', invoices.expired+' Expired'],
      series: [invoices.pending, invoices.approved, invoices.financed, invoices.disbursed, invoices.closed, invoices.rejected, invoices.past_due, invoices.expired],
      colors: [
        chartColors.donut.series1,
        chartColors.donut.series2,
        chartColors.donut.series3,
        chartColors.donut.series4,
        chartColors.donut.series5,
        chartColors.donut.series6,
        chartColors.donut.series7,
        chartColors.donut.series8,
        chartColors.donut.series9,
        chartColors.donut.series10,
      ],
      stroke: {
        show: false,
        curve: 'straight'
      },
      dataLabels: {
        enabled: true,
        formatter: function (val, opt) {
          return parseInt(val, 10) + '%';
        }
      },
      legend: {
        show: true,
        position: 'bottom',
        markers: { offsetX: -3 },
        itemMargin: {
          vertical: 3,
          horizontal: 10
        },
        labels: {
          colors: legendColor,
          useSeriesColors: false
        }
      },
      plotOptions: {
        pie: {
          donut: {
            labels: {
              show: true,
              name: {
                fontSize: '2rem',
                fontFamily: 'Open Sans'
              },
              value: {
                fontSize: '1.2rem',
                color: legendColor,
                fontFamily: 'Open Sans',
                formatter: function (val) {
                  return parseInt((val / total_invoices) * 100, 10) + ' %';
                }
              },
              total: {
                show: true,
                fontSize: '1.5rem',
                color: headingColor,
                label: '',
                formatter: function (w) {
                  return '';
                }
              }
            }
          }
        }
      },
      responsive: [
        {
          breakpoint: 992,
          options: {
            chart: {
              height: 380
            },
            legend: {
              position: 'bottom',
              labels: {
                colors: legendColor,
                useSeriesColors: false
              }
            }
          }
        },
        {
          breakpoint: 576,
          options: {
            chart: {
              height: 320
            },
            plotOptions: {
              pie: {
                donut: {
                  labels: {
                    show: true,
                    name: {
                      fontSize: '1.5rem'
                    },
                    value: {
                      fontSize: '1rem'
                    },
                    total: {
                      fontSize: '1.5rem'
                    }
                  }
                }
              }
            },
            legend: {
              position: 'bottom',
              labels: {
                colors: legendColor,
                useSeriesColors: false
              }
            }
          }
        },
        {
          breakpoint: 420,
          options: {
            chart: {
              height: 280
            },
            legend: {
              show: false
            }
          }
        },
        {
          breakpoint: 360,
          options: {
            chart: {
              height: 250
            },
            legend: {
              show: false
            }
          }
        }
      ]
    };
  if (typeof donutChartEl !== undefined && donutChartEl !== null) {
    const donutChart = new ApexCharts(donutChartEl, donutChartConfig);
    donutChart.render();
  }

  let total_paid = 0

  total_paid = earningReportsChart['total_received'] + earningReportsChart['total_outstanding'];

  const paymentDonutChartEl = document.querySelector('#paymentsDonutChart'),
    paymentsDonutChartConfig = {
      chart: {
        height: 410,
        type: 'donut'
      },
      labels: [new Intl.NumberFormat().format(earningReportsChart['total_received'])+' Received', new Intl.NumberFormat().format(earningReportsChart['total_outstanding'])+' Outstanding'],
      series: [earningReportsChart['total_received'], earningReportsChart['total_outstanding']],
      colors: [
        chartColors.donut.series1,
        chartColors.donut.series4,
        chartColors.donut.series3,
        chartColors.donut.series2,
        chartColors.donut.series5,
        chartColors.donut.series7
      ],
      stroke: {
        show: false,
        curve: 'straight'
      },
      dataLabels: {
        enabled: true,
        formatter: function (val, opt) {
          return parseInt(val, 10) + '%';
        }
      },
      legend: {
        show: true,
        position: 'bottom',
        markers: { offsetX: -3 },
        itemMargin: {
          vertical: 3,
          horizontal: 10
        },
        labels: {
          colors: legendColor,
          useSeriesColors: false
        }
      },
      plotOptions: {
        pie: {
          donut: {
            labels: {
              show: true,
              name: {
                fontSize: '2rem',
                fontFamily: 'Open Sans'
              },
              value: {
                fontSize: '1.2rem',
                color: legendColor,
                fontFamily: 'Open Sans',
                formatter: function (val) {
                  return parseInt((val / total_paid) * 100, 10) + ' %';
                }
              },
              total: {
                show: true,
                fontSize: '1.5rem',
                color: headingColor,
                label: '',
                formatter: function (w) {
                  return '';
                }
              }
            }
          }
        }
      },
      responsive: [
        {
          breakpoint: 992,
          options: {
            chart: {
              height: 380
            },
            legend: {
              position: 'bottom',
              labels: {
                colors: legendColor,
                useSeriesColors: false
              }
            }
          }
        },
        {
          breakpoint: 576,
          options: {
            chart: {
              height: 320
            },
            plotOptions: {
              pie: {
                donut: {
                  labels: {
                    show: true,
                    name: {
                      fontSize: '1.5rem'
                    },
                    value: {
                      fontSize: '1rem'
                    },
                    total: {
                      fontSize: '1.5rem'
                    }
                  }
                }
              }
            },
            legend: {
              position: 'bottom',
              labels: {
                colors: legendColor,
                useSeriesColors: false
              }
            }
          }
        },
        {
          breakpoint: 420,
          options: {
            chart: {
              height: 280
            },
            legend: {
              show: false
            }
          }
        },
        {
          breakpoint: 360,
          options: {
            chart: {
              height: 250
            },
            legend: {
              show: false
            }
          }
        }
      ]
    };
  if (typeof paymentDonutChartEl !== undefined && paymentDonutChartEl !== null) {
    const paymentsDonutChart = new ApexCharts(paymentDonutChartEl, paymentsDonutChartConfig);
    paymentsDonutChart.render();
  }
</script>
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Dealer Dashboard')}}</span>
</h4>

<div class="row">
  <!-- Statistics -->
  <div class="row">
    <div class="col-lg-3 col-6 mb-4">
      <div class="card">
        <div class="card-body border-bottom border-primary">
          <div class="d-flex mb-2">
            <div class="badge p-2 bg-label-primary"><i class="ti ti-lemon-2 ti-sm"></i></div>
            <h5 class="card-title px-2 my-2">{{ $currency }} <span id="total-program-limit">0</span></h5>
          </div>
          <h6>{{ __('Total Program Limit')}}</h6>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6 mb-4">
      <div class="card">
        <div class="card-body border-bottom border-warning">
          <div class="d-flex mb-2">
            <div class="badge p-2 bg-label-warning"><i class="ti ti-git-branch ti-sm"></i></div>
            <h5 class="card-title px-2 my-2">{{ $currency }} <span id="total-available-limit">0</span></h5>
          </div>
          <h6>{{ __('Available Limit')}}</h6>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6 mb-4">
      <div class="card">
        <div class="card-body border-bottom border-info">
          <div class="d-flex mb-2">
            <div class="badge p-2 bg-label-info"><i class="ti ti-clock ti-sm"></i></div>
            <h5 class="card-title px-2 my-2">{{ $currency }} <span id="total-utilized-limit">0</span></h5>
          </div>
          <h6>{{ __('Utilized Limit')}}</h6>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6 mb-4">
      <div class="card">
        <div class="card-body border-bottom border-success">
          <div class="d-flex mb-2">
            <div class="badge p-2 bg-label-success"><i class="ti ti-keyboard-show ti-sm"></i></div>
            <h5 class="card-title px-2 my-2">{{ $currency }} <span id="total-pipeline-limit">0</span></h5>
          </div>
          <h6>{{ __('Pipeline Amount')}}</h6>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-6 mb-4">
      <div class="card">
        <div class="card-body border-bottom border-success">
          <div class="d-flex mb-2">
            <div class="badge p-2 bg-label-success"><i class="ti ti-keyboard-show ti-sm"></i></div>
            <h5 class="card-title px-2 my-2">{{ $currency }} <span id="total-overdue-amount">0</span></h5>
          </div>
          <h6>{{ __('Overdue Amount')}}</h6>
        </div>
      </div>
    </div>
  </div>
  <!--/ Statistics -->

  <!-- Requested Value Per Month -->
  <div class="col-12 col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title m-0">
          <h5 class="mb-0">{{ __('Requested Value Per Month')}}</h5>
        </div>
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs widget-nav-tabs pb-3 gap-4 mx-1 d-flex flex-nowrap" role="tablist">
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-orders-id" aria-controls="navs-orders-id" aria-selected="true">
              <h6 class="tab-widget-title mb-0 mt-2">{{ __('Amount Invoiced')}}</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-profit-id" aria-controls="navs-profit-id" aria-selected="false">
              <h6 class="tab-widget-title mb-0 mt-2">{{ __('Paid Invoices')}}</h6>
            </a>
          </li>
        </ul>
        <div class="tab-content p-0 ms-0 ms-sm-2">
          <div class="tab-pane fade show active" id="navs-orders-id" role="tabpanel">
            <div id="earningReportsTabsOrders"></div>
          </div>
          <div class="tab-pane fade" id="navs-profit-id" role="tabpanel">
            <div id="earningReportsTabsProfit"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Requested Value Per Month -->

  <!-- Requested Value -->
  <div class="col-md-3 col-12 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="card-title mb-0">{{ __('Invoice Status')}}</h5>
        </div>
      </div>
      <div class="card-body">
        <div id="donutChart"></div>
      </div>
    </div>
  </div>
  <!-- /Requested Value -->

  <div class="col-md-3 col-12 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="card-title mb-0">{{ __('Payments')}}</h5>
        </div>
      </div>
      <div class="card-body">
        <div id="paymentsDonutChart"></div>
      </div>
    </div>
  </div>

  <!-- Latest Invoices -->
  <div class="col-12 mt-4">
    <div class="card">
      <div class="d-flex justify-content-between">
        <h5 class="card-header">{{ __('Latest Invoices')}}</h5>
        {{-- <a class="card-header" href="#">View All</a> --}}
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>{{ __('Invoice No')}}.</th>
              <th>{{ __('Anchor')}}</th>
              <th>{{ __('Invoice Amount')}}</th>
              <th>{{ __('Overdue Amount')}}</th>
              <th>{{ __('DPD')}}</th>
              <th>{{ __('Status')}}</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0 text-nowrap">
            @foreach ($latest_invoices as $invoice)
              <tr>
                <td class="text-decoration-underline" style="cursor: pointer">
                  <span data-bs-toggle="modal" class="text-primary" data-bs-target="#invoice-{{ $invoice->id }}">
                    {{ $invoice->invoice_number }}
                  </span>
                  <div class="modal fade" id="invoice-{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="modalCenterTitle">{{ __('Invoice Details')}}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          <div class="d-flex">
                            <div>
                              <a class="btn btn-primary btn-sm mx-2" href="{{ route('vendor.invoice.download', ['invoice' => $invoice]) }}" target="_blank" rel="noopener noreferrer">
                                <i class="ti ti-printer"></i>
                                {{ __('Print')}}
                              </a>
                            </div>
                            @if ($invoice->getMedia('*')->count() > 0)
                              <div class="d-flex">
                                @foreach ($invoice->getMedia('*') as $key => $attachment)
                                  <div>
                                    <a href="{{ $attachment->original_url }}" target="_blank" class="btn btn-sm btn-primary mx-1" rel="noopener noreferrer">
                                      <i class="ti ti-paperclip"></i>
                                      {{ __('View Attachment') }} {{ $key + 1 }}</a>
                                  </div>
                                @endforeach
                              </div>
                            @endif
                          </div>
                        </div>
                        <div class="modal-body">
                          <div class="d-flex justify-content-between">
                            <div class="mb-3 w-25">
                              <span class="d-flex justify-content-between">
                                <h5 class="fw-light my-auto">{{ __('Anchor')}}:</h5>
                                <h6 class="fw-bold mx-2 my-auto">{{ $invoice->program->anchor->name }}</h6>
                              </span>
                            </div>
                            <div class="mb-3">
                              @if ($invoice->pi_number)
                                <span class="d-flex justify-content-between">
                                  <h5 class="my-auto fw-light">{{ __('PI No')}}:</h5>
                                  <h6 class="fw-bold mx-2 my-auto text-primary" data-bs-toggle="modal" data-bs-target="#pi-{{ $invoice->id }}">{{ $invoice->pi_number }}</h6>
                                </span>
                              @endif
                              <span class="d-flex justify-content-between">
                                <h5 class="my-auto fw-light">{{ __('Invoice/Unique Ref No')}}:</h5>
                                <h6 class="fw-bold mx-2 my-auto">{{ $invoice->invoice_number }}</h6>
                              </span>
                              <span class="d-flex justify-content-between">
                                <h5 class="my-auto fw-light">{{ __('Invoice Amount')}}:</h5>
                                <h6 class="fw-bold text-success mx-2 my-auto">{{ $invoice->currency }} {{ number_format($invoice->total) }}</h6>
                              </span>
                              <span class="d-flex justify-content-between">
                                <h5 class="my-auto fw-light">{{ __('Invoice Due Date')}}:</h5>
                                <h6 class="fw-bold mx-2 my-auto">{{ Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</h6>
                              </span>
                            </div>
                          </div>
                          @if ($invoice->invoiceItems->count() > 0)
                            <div class="table-responsive">
                              <table class="table">
                                <thead>
                                  <tr>
                                    <th>{{ __('Item')}}</th>
                                    <th>{{ __('Quantity')}}</th>
                                    <th>{{ __('Unit')}}</th>
                                    <th>{{ __('Price Per Quantity')}}</th>
                                    <th>{{ __('Total') }}</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  @foreach ($invoice->invoiceItems as $item)
                                    <tr>
                                      <td>{{ $item->item }}</td>
                                      <td>{{ $item->quantity }}</td>
                                      <td>{{ $item->unit }}</td>
                                      <td>{{ $item->price_per_quantity }}</td>
                                      <td>{{ number_format($item->quantity * $item->price_per_quantity) }}</td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          @endif
                          <span class="d-flex justify-content-end px-2">
                            <h6 class="mx-2 my-auto py-1">{{ __('Discount on Invoice Items')}}</h6>
                            <h5 class="text-success my-auto py-1">{{ number_format($invoice->total_discount) }}</h5>
                          </span>
                          @if ($invoice->invoiceTaxes->count() > 0)
                            <div class="px-2">
                              @foreach ($invoice->invoiceTaxes as $invoice_tax)
                                <span class="d-flex justify-content-end">
                                  <h6 class="mx-2 my-auto py-1">{{ $invoice_tax->name }}</h6>
                                  <h5 class="text-success my-auto py-1">{{ number_format($invoice_tax->value) }}</h5>
                                </span>
                              @endforeach
                            </div>
                          @endif
                          <div class="bg-label-secondary px-2">
                            <span class="d-flex justify-content-end">
                              <h6 class="mx-2 my-auto py-1">{{ __('Total') }}</h6>
                              <h5 class="text-success my-auto py-1">{{ number_format($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount) }}</h5>
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal fade" id="pi-{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="modalCenterTitle">{{ __('PI Details')}}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          <div class="d-flex">
                            <div>
                              <a class="btn btn-primary btn-sm mx-2" href="{{ route('vendor.invoice.download', ['invoice' => $invoice]) }}" target="_blank" rel="noopener noreferrer">
                                <i class="ti ti-printer"></i>
                                {{ __('Print')}}
                              </a>
                            </div>
                            @if ($invoice->getMedia('*')->count() > 0)
                              <div class="d-flex">
                                @foreach ($invoice->getMedia('*') as $key => $attachment)
                                  <div>
                                    <a href="{{ $attachment->original_url }}" target="_blank" class="btn btn-sm btn-primary mx-1" rel="noopener noreferrer">{{ __('View Attachment') }} {{ $key + 1 }}</a>
                                  </div>
                                @endforeach
                              </div>
                            @endif
                          </div>
                        </div>
                        <div class="modal-body">
                          <div class="d-flex justify-content-between">
                            <div class="mb-3 w-25">
                              <span class="d-flex justify-content-between">
                                <h5 class="fw-light my-auto">{{ __('Anchor')}}:</h5>
                                <h6 class="fw-bold mx-2 my-auto">{{ $invoice->program->anchor->name }}</h6>
                              </span>
                            </div>
                            <div class="mb-3">
                              @if ($invoice->pi_number)
                                <span class="d-flex justify-content-between">
                                  <h5 class="my-auto fw-light">{{ __('PI No')}}:</h5>
                                  <h6 class="fw-bold mx-2 my-auto text-primary" data-bs-toggle="modal" data-bs-target="#pi-{{ $invoice->id }}">{{ $invoice->pi_number }}</h6>
                                </span>
                              @endif
                              <span class="d-flex justify-content-between">
                                <h5 class="my-auto fw-light">{{ __('Invoice/Unique Ref No')}}:</h5>
                                <h6 class="fw-bold mx-2 my-auto">{{ $invoice->invoice_number }}</h6>
                              </span>
                              <span class="d-flex justify-content-between">
                                <h5 class="my-auto fw-light">{{ __('Invoice Amount')}}:</h5>
                                <h6 class="fw-bold text-success mx-2 my-auto">{{ $invoice->currency }} {{ number_format($invoice->total) }}</h6>
                              </span>
                              <span class="d-flex justify-content-between">
                                <h5 class="my-auto fw-light">{{ __('Invoice Due Date')}}:</h5>
                                <h6 class="fw-bold mx-2 my-auto">{{ Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</h6>
                              </span>
                            </div>
                          </div>
                          @if ($invoice->invoiceItems->count() > 0)
                            <div class="table-responsive">
                              <table class="table">
                                <thead>
                                  <tr>
                                    <th>{{ __('Item')}}</th>
                                    <th>{{ __('Quantity')}}</th>
                                    <th>{{ __('Unit')}}</th>
                                    <th>{{ __('Price Per Quantity')}}</th>
                                    <th>{{ __('Total') }}</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  @foreach ($invoice->invoiceItems as $item)
                                    <tr>
                                      <td>{{ $item->item }}</td>
                                      <td>{{ $item->quantity }}</td>
                                      <td>{{ $item->unit }}</td>
                                      <td>{{ $item->price_per_quantity }}</td>
                                      <td>{{ number_format($item->quantity * $item->price_per_quantity) }}</td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          @endif
                          <span class="d-flex justify-content-end px-2">
                            <h6 class="mx-2 my-auto py-1">{{ __('Discount on Invoice Items')}}</h6>
                            <h5 class="text-success my-auto py-1">{{ number_format($invoice->total_discount) }}</h5>
                          </span>
                          @if ($invoice->invoiceTaxes->count() > 0)
                            <div class="px-2">
                              @foreach ($invoice->invoiceTaxes as $invoice_tax)
                                <span class="d-flex justify-content-end">
                                  <h6 class="mx-2 my-auto py-1">{{ $invoice_tax->name }}</h6>
                                  <h5 class="text-success my-auto py-1">{{ number_format($invoice_tax->value) }}</h5>
                                </span>
                              @endforeach
                            </div>
                          @endif
                          @if ($invoice->invoiceFees->count() > 0)
                            <div class="px-2">
                              @foreach ($invoice->invoiceFees as $invoice_fee)
                                <span class="d-flex justify-content-end">
                                  <h6 class="mx-2 my-auto py-1">{{ $invoice_fee->name }}</h6>
                                  @if ($invoice_fee->name !== 'Credit Note Amount' && $invoice_fee->name !== 'Credit Note' && $invoice_fee->name !== 'Credit Amount ')
                                    <h5 class="text-success my-auto py-1">{{ number_format($invoice_fee->amount) }}</h5>
                                  @else
                                    <h5 class="text-success my-auto py-1">{{ $invoice_fee->amount }}</h5>
                                  @endif
                                </span>
                              @endforeach
                            </div>
                          @endif
                          <div class="bg-label-secondary px-2">
                            <span class="d-flex justify-content-end">
                              <h6 class="mx-2 my-auto py-1">{{ __('Total') }}</h6>
                              <h5 class="text-success my-auto py-1">{{ number_format($invoice->invoice_total_amount) }}</h5>
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
                <td><strong>{{ $invoice->program->anchor->name }}</strong></td>
                <td class="text-success">{{ $invoice->currency }}. {{ number_format($invoice->invoice_total_amount) }}</td>
                <td class="text-success">{{ number_format($invoice->overdue_amount) }}</td>
                <td class="text-success">{{ $invoice->days_past_due }}</td>
                <td><span class="badge {{ $invoice->resolveStatus() }} me-1">{{ Str::title($invoice->approval_stage) }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!--/ Latest Invoices -->
</div>
@endsection
