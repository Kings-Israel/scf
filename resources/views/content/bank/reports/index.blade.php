@extends('layouts/layoutMaster')

@section('title', 'All Reports')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
@endsection

@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script>
  $(document).ready(() => {  let cardColor, headingColor, legendColor, labelColor, borderColor;
    if (isDarkStyle) {
      cardColor = config.colors_dark.cardColor;
      labelColor = config.colors_dark.textMuted;
      legendColor = config.colors_dark.bodyColor;
      headingColor = config.colors_dark.headingColor;
      borderColor = config.colors_dark.borderColor;
    } else {
      cardColor = config.colors.cardColor;
      labelColor = config.colors.textMuted;
      legendColor = config.colors.bodyColor;
      headingColor = config.colors.headingColor;
      borderColor = config.colors.borderColor;
    }

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

    let bank = {!! json_encode($bank) !!}
    let months = {!! json_encode($months_formatted) !!}
    assets = '/'+bank.url+'/reports/graph/data'
    let invoices_status_data = '/'+bank.url+'/reports/invoices/data'
    let revenue_pie_data = '/'+bank.url+'/reports/graph/revenue/pie/data'
    let requests_tracker_data = '/'+bank.url+'/reports/requests/tracker/data'

    // Earning Chart JSON data
    var earningReportsChart = $.ajax({
      url: assets, //? Use your own search api instead
      dataType: 'json',
      async: false
    }).responseJSON;

    const donutChartEl = document.querySelector('#donutChart')
    const revenueDonutChartEl = document.querySelector('#revenueDonutChart')

    // Total Revenue Report Chart - Bar Chart
    // --------------------------------------------------------------------
    let totalRevenueChartEl = document.querySelector('#totalRevenueChart')
    let max_income_data = 0
    let max_interest_income_data = 0
    let max_fees_income_data = 0
    earningReportsChart.income_data.forEach(income_data => {
      if (income_data > max_income_data) {
        max_income_data = income_data
      }
    });
    earningReportsChart.interest_income_data.forEach(income_data => {
      if (income_data > max_interest_income_data) {
        max_interest_income_data = income_data
      }
    });
    earningReportsChart.fees_income_data.forEach(income_data => {
      if (income_data > max_fees_income_data) {
        max_fees_income_data = income_data
      }
    });
    let totalRevenueChartOptions = {
        series: [
          {
            name: 'Discount Revenue',
            data: earningReportsChart.income_data
          }
        ],
        chart: {
          height: 250,
          parentHeightOffset: 0,
          stacked: true,
          type: 'bar',
          toolbar: { show: false }
        },
        tooltip: {
          enabled: true
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '40%',
            borderRadius: 9,
            startingShape: 'rounded',
            endingShape: 'rounded'
          }
        },
        colors: [config.colors.primary, config.colors.warning],
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 6,
          lineCap: 'round',
          colors: [cardColor]
        },
        legend: {
          show: true,
          horizontalAlign: 'left',
          position: 'top',
          fontFamily: 'Public Sans',
          markers: {
            height: 12,
            width: 12,
            radius: 12,
            offsetX: -3,
            offsetY: 2
          },
          labels: {
            colors: legendColor
          },
          itemMargin: {
            horizontal: 5
          }
        },
        grid: {
          show: false,
          padding: {
            bottom: -8,
            top: 20
          }
        },
        xaxis: {
          categories: earningReportsChart.months_formatted,
          labels: {
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans'
            }
          },
          axisTicks: {
            show: false
          },
          axisBorder: {
            show: false
          }
        },
        yaxis: {
          labels: {
            offsetX: 0,
            formatter: function (val) {
              return new Intl.NumberFormat().format(parseInt(val / 1));
            },
            style: {
              fontSize: '13px',
              colors: labelColor,
              fontFamily: 'Public Sans'
            }
          },
          min: 0,
          max: max_income_data,
          tickAmount: 5
        },
        responsive: [
          {
            breakpoint: 1700,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '43%'
                }
              }
            }
          },
          {
            breakpoint: 1441,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '50%'
                }
              }
            }
          },
          {
            breakpoint: 1300,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '62%'
                }
              }
            }
          },
          {
            breakpoint: 991,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '38%'
                }
              }
            }
          },
          {
            breakpoint: 850,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '50%'
                }
              }
            }
          },
          {
            breakpoint: 449,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '73%'
                }
              },
              xaxis: {
                labels: {
                  offsetY: -5
                }
              }
            }
          },
          {
            breakpoint: 394,
            options: {
              plotOptions: {
                bar: {
                  columnWidth: '88%'
                }
              }
            }
          }
        ],
        states: {
          hover: {
            filter: {
              type: 'none'
            }
          },
          active: {
            filter: {
              type: 'none'
            }
          }
        }
      };

      let totalRevenueChart = new ApexCharts(totalRevenueChartEl, totalRevenueChartOptions);
      totalRevenueChart.render();

      let totalInterestRevenueChartEl = document.querySelector('#totalInterestRevenueChart')
      let totalInterestRevenueChartOptions = {
          series: [
            {
              name: 'Penal Revenue',
              data: earningReportsChart.interest_income_data
            }
          ],
          chart: {
            height: 250,
            parentHeightOffset: 0,
            stacked: true,
            type: 'bar',
            toolbar: { show: false }
          },
          tooltip: {
            enabled: true
          },
          plotOptions: {
            bar: {
              horizontal: false,
              columnWidth: '40%',
              borderRadius: 9,
              startingShape: 'rounded',
              endingShape: 'rounded'
            }
          },
          colors: [config.colors.primary, config.colors.warning],
          dataLabels: {
            enabled: false
          },
          stroke: {
            curve: 'smooth',
            width: 6,
            lineCap: 'round',
            colors: [cardColor]
          },
          legend: {
            show: true,
            horizontalAlign: 'left',
            position: 'top',
            fontFamily: 'Public Sans',
            markers: {
              height: 12,
              width: 12,
              radius: 12,
              offsetX: -3,
              offsetY: 2
            },
            labels: {
              colors: legendColor
            },
            itemMargin: {
              horizontal: 5
            }
          },
          grid: {
            show: false,
            padding: {
              bottom: -8,
              top: 20
            }
          },
          xaxis: {
            categories: earningReportsChart.months_formatted,
            labels: {
              style: {
                fontSize: '13px',
                colors: labelColor,
                fontFamily: 'Public Sans'
              }
            },
            axisTicks: {
              show: false
            },
            axisBorder: {
              show: false
            }
          },
          yaxis: {
            labels: {
              offsetX: 0,
              formatter: function (val) {
                return new Intl.NumberFormat().format(parseInt(val / 1));
              },
              style: {
                fontSize: '13px',
                colors: labelColor,
                fontFamily: 'Public Sans'
              }
            },
            min: 0,
            max: max_interest_income_data,
            tickAmount: 5
          },
          responsive: [
            {
              breakpoint: 1700,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '43%'
                  }
                }
              }
            },
            {
              breakpoint: 1441,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '50%'
                  }
                }
              }
            },
            {
              breakpoint: 1300,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '62%'
                  }
                }
              }
            },
            {
              breakpoint: 991,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '38%'
                  }
                }
              }
            },
            {
              breakpoint: 850,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '50%'
                  }
                }
              }
            },
            {
              breakpoint: 449,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '73%'
                  }
                },
                xaxis: {
                  labels: {
                    offsetY: -5
                  }
                }
              }
            },
            {
              breakpoint: 394,
              options: {
                plotOptions: {
                  bar: {
                    columnWidth: '88%'
                  }
                }
              }
            }
          ],
          states: {
            hover: {
              filter: {
                type: 'none'
              }
            },
            active: {
              filter: {
                type: 'none'
              }
            }
          }
        };

        let totalInterestRevenueChart = new ApexCharts(totalInterestRevenueChartEl, totalInterestRevenueChartOptions);
        totalInterestRevenueChart.render();

        let totalFeesRevenueChartEl = document.querySelector('#totalFeesRevenueChart')
        let totalFeesRevenueChartOptions = {
            series: [
              {
                name: 'Fees Revenue',
                data: earningReportsChart.fees_income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: earningReportsChart.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_fees_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          let totalFeesRevenueChart = new ApexCharts(totalFeesRevenueChartEl, totalFeesRevenueChartOptions);
          totalFeesRevenueChart.render();

    let supportTracker = null
    var requestsTrackerChart = $.ajax({
      url: requests_tracker_data, //? Use your own search api instead
      dataType: 'json',
      async: false
    }).responseJSON;

    let total_requests = requestsTrackerChart.requests_count
    let dealer_financing_requests = requestsTrackerChart.dealer_financing_requests_count
    let factoring_requests = requestsTrackerChart.factoring_requests_count
    let vendor_financing_requests = requestsTrackerChart.vendor_financing_requests_count
    document.querySelector('#total-requests').innerHTML = new Intl.NumberFormat().format(total_requests)
    document.querySelector('#dealer-financing-requests').innerHTML = new Intl.NumberFormat().format(dealer_financing_requests)
    document.querySelector('#factoring-requests').innerHTML = new Intl.NumberFormat().format(factoring_requests)
    document.querySelector('#vendor-financing-requests').innerHTML = new Intl.NumberFormat().format(vendor_financing_requests)
    // Requests Tracker - Radial Bar Chart
    // --------------------------------------------------------------------
    let closed_requests_count = requestsTrackerChart.closed_requests_count;
    const supportTrackerEl = document.querySelector('#supportTracker'),
      supportTrackerOptions = {
        series: [closed_requests_count],
        labels: ['Closed Requests'],
        chart: {
          height: 320,
          type: 'radialBar'
        },
        plotOptions: {
          radialBar: {
            offsetY: 10,
            startAngle: -140,
            endAngle: 130,
            hollow: {
              size: '65%'
            },
            track: {
              background: cardColor,
              strokeWidth: '100%'
            },
            dataLabels: {
              name: {
                offsetY: -20,
                color: labelColor,
                fontSize: '13px',
                fontWeight: '400',
                fontFamily: 'Public Sans'
              },
              value: {
                offsetY: 10,
                color: headingColor,
                fontSize: '38px',
                fontWeight: '600',
                fontFamily: 'Public Sans'
              }
            }
          }
        },
        colors: [config.colors.primary],
        fill: {
          type: 'gradient',
          gradient: {
            shade: 'dark',
            shadeIntensity: 0.5,
            gradientToColors: [config.colors.primary],
            inverseColors: true,
            opacityFrom: 1,
            opacityTo: 0.6,
            stops: [30, 70, 100]
          }
        },
        stroke: {
          dashArray: 10
        },
        grid: {
          padding: {
            top: -20,
            bottom: 5
          }
        },
        states: {
          hover: {
            filter: {
              type: 'none'
            }
          },
          active: {
            filter: {
              type: 'none'
            }
          }
        },
        responsive: [
          {
            breakpoint: 1025,
            options: {
              chart: {
                height: 330
              }
            }
          },
          {
            breakpoint: 769,
            options: {
              chart: {
                height: 280
              }
            }
          }
        ]
      };
    if (typeof supportTrackerEl !== undefined && supportTrackerEl !== null) {
      supportTracker = new ApexCharts(supportTrackerEl, supportTrackerOptions);
      supportTracker.render();
    }

    $('#timeline-filter').on('change', function() {
      let value = $(this).val();
      let program_type = $('#revenue-graph-program-type-filter').val()

      assets = '/'+bank.url+'/reports/graph/data?timeline='+value+'&program_type='+program_type;

      $.ajax({
        url: assets,
        dataType: 'json',
        async: true,
        success: function(data) {
          data.income_data.forEach(income_data => {
            if (income_data > max_income_data) {
              max_income_data = income_data
            }
          });
          data.interest_income_data.forEach(income_data => {
            if (income_data > max_interest_income_data) {
              max_interest_income_data = income_data
            }
          });
          data.fees_income_data.forEach(income_data => {
            if (income_data > max_fees_income_data) {
              max_fees_income_data = income_data
            }
          });

          totalRevenueChartOptions = null
          let revenueChartOptions = {
            series: [
              {
                name: 'Discount Revenue',
                data: data.income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: data.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          totalRevenueChart.destroy();
          totalRevenueChart = new ApexCharts(totalRevenueChartEl, revenueChartOptions);
          totalRevenueChart.render()

          let interestRevenueChartOptions = {
            series: [
              {
                name: 'Penal Revenue',
                data: data.interest_income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: data.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_interest_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          totalInterestRevenueChart.destroy();
          totalInterestRevenueChart = new ApexCharts(totalInterestRevenueChartEl, interestRevenueChartOptions);
          totalInterestRevenueChart.render()

          let feesRevenueChartOptions = {
            series: [
              {
                name: 'Fees Revenue',
                data: data.fees_income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: data.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_fees_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          totalFeesRevenueChart.destroy();
          totalFeesRevenueChart = new ApexCharts(totalFeesRevenueChartEl, feesRevenueChartOptions);
          totalFeesRevenueChart.render()
        },
        error: function (err) {
          console.log(err)
        }
      });
    })

    $('#revenue-graph-program-type-filter').on('change', function() {
      let value = $(this).val();
      let timeline = $('#timeline-filter').val()

      assets = '/'+bank.url+'/reports/graph/data?program_type='+value+'&timeline='+timeline;

      $.ajax({
        url: assets,
        dataType: 'json',
        async: true,
        success: function(data) {
          data.income_data.forEach(income_data => {
            if (income_data > max_income_data) {
              max_income_data = income_data
            }
          });
          data.interest_income_data.forEach(income_data => {
            if (income_data > max_interest_income_data) {
              max_interest_income_data = income_data
            }
          });
          data.fees_income_data.forEach(income_data => {
            if (income_data > max_fees_income_data) {
              max_fees_income_data = income_data
            }
          });

          totalRevenueChartOptions = null
          let revenueChartOptions = {
            series: [
              {
                name: 'Discount Revenue',
                data: data.income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: data.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          totalRevenueChart.destroy();
          totalRevenueChart = new ApexCharts(totalRevenueChartEl, revenueChartOptions);
          totalRevenueChart.render()

          let interestRevenueChartOptions = {
            series: [
              {
                name: 'Penal Revenue',
                data: data.interest_income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: data.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_interest_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          totalInterestRevenueChart.destroy();
          totalInterestRevenueChart = new ApexCharts(totalInterestRevenueChartEl, interestRevenueChartOptions);
          totalInterestRevenueChart.render()

          let feesRevenueChartOptions = {
            series: [
              {
                name: 'Fees Revenue',
                data: data.fees_income_data
              }
            ],
            chart: {
              height: 250,
              parentHeightOffset: 0,
              stacked: true,
              type: 'bar',
              toolbar: { show: false }
            },
            tooltip: {
              enabled: true
            },
            plotOptions: {
              bar: {
                horizontal: false,
                columnWidth: '40%',
                borderRadius: 9,
                startingShape: 'rounded',
                endingShape: 'rounded'
              }
            },
            colors: [config.colors.primary, config.colors.warning],
            dataLabels: {
              enabled: false
            },
            stroke: {
              curve: 'smooth',
              width: 6,
              lineCap: 'round',
              colors: [cardColor]
            },
            legend: {
              show: true,
              horizontalAlign: 'left',
              position: 'top',
              fontFamily: 'Public Sans',
              markers: {
                height: 12,
                width: 12,
                radius: 12,
                offsetX: -3,
                offsetY: 2
              },
              labels: {
                colors: legendColor
              },
              itemMargin: {
                horizontal: 5
              }
            },
            grid: {
              show: false,
              padding: {
                bottom: -8,
                top: 20
              }
            },
            xaxis: {
              categories: data.months_formatted,
              labels: {
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              axisTicks: {
                show: false
              },
              axisBorder: {
                show: false
              }
            },
            yaxis: {
              labels: {
                offsetX: 0,
                formatter: function (val) {
                  return new Intl.NumberFormat().format(parseInt(val / 1));
                },
                style: {
                  fontSize: '13px',
                  colors: labelColor,
                  fontFamily: 'Public Sans'
                }
              },
              min: 0,
              max: max_fees_income_data,
              tickAmount: 5
            },
            responsive: [
              {
                breakpoint: 1700,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '43%'
                    }
                  }
                }
              },
              {
                breakpoint: 1441,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 1300,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '62%'
                    }
                  }
                }
              },
              {
                breakpoint: 991,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '38%'
                    }
                  }
                }
              },
              {
                breakpoint: 850,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '50%'
                    }
                  }
                }
              },
              {
                breakpoint: 449,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '73%'
                    }
                  },
                  xaxis: {
                    labels: {
                      offsetY: -5
                    }
                  }
                }
              },
              {
                breakpoint: 394,
                options: {
                  plotOptions: {
                    bar: {
                      columnWidth: '88%'
                    }
                  }
                }
              }
            ],
            states: {
              hover: {
                filter: {
                  type: 'none'
                }
              },
              active: {
                filter: {
                  type: 'none'
                }
              }
            }
          };

          totalFeesRevenueChart.destroy();
          totalFeesRevenueChart = new ApexCharts(totalFeesRevenueChartEl, feesRevenueChartOptions);
          totalFeesRevenueChart.render()
        },
        error: function (err) {
          console.log(err)
        }
      });
    })

    let donutChart = null
    let revenueDonutChart = null

    $.ajax({
      url: invoices_status_data, //? Use your own search api instead
      dataType: 'json',
      async: false,
      success: function (data) {
        let invoices = data.invoices
        let total_invoices = invoices.approved + invoices.closed + invoices.financed + invoices.disbursed + invoices.rejected + invoices.past_due + invoices.expired;
        let donutChartConfig = {
          chart: {
            height: 320,
            type: 'donut'
          },
          labels: [invoices.pending+' Pending', invoices.approved+' Approved', invoices.financed+' Financed', invoices.disbursed+' Disbursed', invoices.closed+' Closed', invoices.rejected+' Rejected', invoices.past_due+' Past Due', invoices.expired+' Expired'],
          series: [invoices.pending, invoices.approved, invoices.financed, invoices.disbursed, invoices.closed, invoices.rejected, invoices.past_due, invoices.expired],
          colors: [
            chartColors.donut.series1,
            chartColors.donut.series4,
            chartColors.donut.series3,
            chartColors.donut.series2,
            chartColors.donut.series5,
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
                    fontFamily: 'Public Sans'
                  },
                  value: {
                    fontSize: '1.2rem',
                    color: legendColor,
                    fontFamily: 'Public Sans',
                    formatter: function (val) {
                      // return parseInt((val / total_invoices) * 100, 10) + ' %';
                      return ''
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
          donutChart = new ApexCharts(donutChartEl, donutChartConfig);
          donutChart.render();
        }
      }
    });

    $('#type-filter').on('change', function () {
      let value = $(this).val();

      invoices_status_data = '/'+bank.url+'/reports/invoices/data?program_type='+value;

      $.ajax({
        url: invoices_status_data, //? Use your own search api instead
        dataType: 'json',
        async: false,
        success: function (data) {
          let invoices = data.invoices
          let total_invoices = invoices.approved + invoices.closed + invoices.financed + invoices.disbursed + invoices.rejected + invoices.past_due + invoices.expired;
          donutChartConfig = null
          donutChartConfig = {
            chart: {
              height: 320,
              type: 'donut'
            },
            labels: [invoices.approved+' Approved', invoices.closed+' Closed', invoices.pending+' Pending', invoices.financed+' Financed', invoices.disbursed+' Disbursed', invoices.rejected+' Rejected', invoices.past_due+' Past Due', invoices.expired+' Expired'],
            series: [invoices.approved, invoices.closed, invoices.pending, invoices.financed, invoices.disbursed, invoices.rejected, invoices.past_due, invoices.expired],
            colors: [
              chartColors.donut.series1,
              chartColors.donut.series4,
              chartColors.donut.series3,
              chartColors.donut.series2,
              chartColors.donut.series5,
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
                      fontFamily: 'Public Sans'
                    },
                    value: {
                      fontSize: '1.2rem',
                      color: legendColor,
                      fontFamily: 'Public Sans',
                      formatter: function (val) {
                        // return parseInt((val / total_invoices) * 100, 10) + ' %';
                        return ''
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
          donutChart.destroy()
          donutChart = new ApexCharts(donutChartEl, donutChartConfig);
          donutChart.render();
        }
      })
    })

    $.ajax({
      url: revenue_pie_data, //? Use your own search api instead
      dataType: 'json',
      async: false,
      success: function (data) {
        let total_revenue = data.discount + data.fees + data.penal;
        let revenueDonutChartConfig = {
          chart: {
            height: 320,
            type: 'donut'
          },
          labels: [new Intl.NumberFormat().format(data.discount)+' Discount', new Intl.NumberFormat().format(data.fees)+' Fees/Charges', new Intl.NumberFormat().format(data.penal)+' Penal'],
          series: [data.discount, data.fees, data.penal],
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
                    fontFamily: 'Public Sans'
                  },
                  value: {
                    fontSize: '1.2rem',
                    color: legendColor,
                    fontFamily: 'Public Sans',
                    formatter: function (val) {
                      return parseInt((val / total_revenue) * 100).toFixed(2) + ' %';
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
        if (typeof revenueDonutChartEl !== undefined && revenueDonutChartEl !== null) {
          revenueDonutChart = new ApexCharts(revenueDonutChartEl, revenueDonutChartConfig);
          revenueDonutChart.render();
        }
      }
    });

    $('#duration-filter').on('change', function () {
      let program_type_filter = $('#revenue-program-type-filter').val();
      let value = $(this).val();

      revenue_pie_data = '/'+bank.url+'/reports/graph/revenue/pie/data?program_type='+program_type_filter+'&filter='+value;

      $.ajax({
        url: revenue_pie_data, //? Use your own search api instead
        dataType: 'json',
        async: false,
        success: function (data) {
          let total_revenue = data.discount + data.fees + data.penal;
          revenueDonutChartConfig = null
          revenueDonutChartConfig = {
            chart: {
              height: 320,
              type: 'donut'
            },
            labels: [new Intl.NumberFormat().format(data.discount)+' Discount', new Intl.NumberFormat().format(data.fees)+' Fees/Charges', new Intl.NumberFormat().format(data.penal)+' Penal'],
            series: [data.discount, data.fees, data.penal],
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
                      fontFamily: 'Public Sans'
                    },
                    value: {
                      fontSize: '1.2rem',
                      color: legendColor,
                      fontFamily: 'Public Sans',
                      formatter: function (val) {
                        return parseInt((val / total_revenue) * 100, 10) + ' %';
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
          revenueDonutChart.destroy()
          revenueDonutChart = new ApexCharts(revenueDonutChartEl, revenueDonutChartConfig);
          revenueDonutChart.render();
        }
      })
    })

    $('#revenue-program-type-filter').on('change', function () {
      let duration_filter = $('#duration-filter').val();
      let value = $(this).val();

      revenue_pie_data = '/'+bank.url+'/reports/graph/revenue/pie/data?program_type='+value+'&filter='+duration_filter;

      $.ajax({
        url: revenue_pie_data, //? Use your own search api instead
        dataType: 'json',
        async: false,
        success: function (data) {
          let total_revenue = data.discount + data.fees + data.penal;
          revenueDonutChartConfig = null
          revenueDonutChartConfig = {
            chart: {
              height: 320,
              type: 'donut'
            },
            labels: [new Intl.NumberFormat().format(data.discount)+' Discount', new Intl.NumberFormat().format(data.fees)+' Fees/Charges', new Intl.NumberFormat().format(data.penal)+' Penal'],
            series: [data.discount, data.fees, data.penal],
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
                      fontFamily: 'Public Sans'
                    },
                    value: {
                      fontSize: '1.2rem',
                      color: legendColor,
                      fontFamily: 'Public Sans',
                      formatter: function (val) {
                        return parseInt((val / total_revenue) * 100, 10) + ' %';
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
          revenueDonutChart.destroy()
          revenueDonutChart = new ApexCharts(revenueDonutChartEl, revenueDonutChartConfig);
          revenueDonutChart.render();
        }
      })
    })

    $('#requests-tracker-timeline-filter').on('change', function () {
      let duration_filter = $('#requests-tracker-timeline-filter').val();

      requests_tracker_data = '/'+bank.url+'/reports/requests/tracker/data?&filter='+duration_filter;

      $.ajax({
        url: requests_tracker_data, //? Use your own search api instead
        dataType: 'json',
        async: false,
        success: function (data) {
          total_requests = data.requests_count
          dealer_financing_requests = data.dealer_financing_requests_count
          factoring_requests = data.factoring_requests_count
          vendor_financing_requests = data.vendor_financing_requests_count
          document.querySelector('#total-requests').innerHTML = new Intl.NumberFormat().format(total_requests)
          document.querySelector('#dealer-financing-requests').innerHTML = new Intl.NumberFormat().format(dealer_financing_requests)
          document.querySelector('#factoring-requests').innerHTML = new Intl.NumberFormat().format(factoring_requests)
          document.querySelector('#vendor-financing-requests').innerHTML = new Intl.NumberFormat().format(vendor_financing_requests)

          let supportTrackerOptions = {
              series: [data.closed_requests_count],
              labels: ['Closed Requests'],
              chart: {
                height: 320,
                type: 'radialBar'
              },
              plotOptions: {
                radialBar: {
                  offsetY: 10,
                  startAngle: -140,
                  endAngle: 130,
                  hollow: {
                    size: '65%'
                  },
                  track: {
                    background: cardColor,
                    strokeWidth: '100%'
                  },
                  dataLabels: {
                    name: {
                      offsetY: -20,
                      color: labelColor,
                      fontSize: '13px',
                      fontWeight: '400',
                      fontFamily: 'Public Sans'
                    },
                    value: {
                      offsetY: 10,
                      color: headingColor,
                      fontSize: '38px',
                      fontWeight: '600',
                      fontFamily: 'Public Sans'
                    }
                  }
                }
              },
              colors: [config.colors.primary],
              fill: {
                type: 'gradient',
                gradient: {
                  shade: 'dark',
                  shadeIntensity: 0.5,
                  gradientToColors: [config.colors.primary],
                  inverseColors: true,
                  opacityFrom: 1,
                  opacityTo: 0.6,
                  stops: [30, 70, 100]
                }
              },
              stroke: {
                dashArray: 10
              },
              grid: {
                padding: {
                  top: -20,
                  bottom: 5
                }
              },
              states: {
                hover: {
                  filter: {
                    type: 'none'
                  }
                },
                active: {
                  filter: {
                    type: 'none'
                  }
                }
              },
              responsive: [
                {
                  breakpoint: 1025,
                  options: {
                    chart: {
                      height: 330
                    }
                  }
                },
                {
                  breakpoint: 769,
                  options: {
                    chart: {
                      height: 280
                    }
                  }
                }
              ]
            };

          supportTracker.destroy()
          supportTracker = new ApexCharts(supportTrackerEl, supportTrackerOptions);
          supportTracker.render();
        }
      })
    })
  })
</script>
@endsection
@section('content')
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('All Reports')}}</span>
</h4>

<div class="row">
  <div class="col-12 col-lg-7 mb-4">
    <div class="card">
      <div class="card-header pb-0 d-flex flex-column flex-md-row justify-content-md-between">
        <h5 class="m-0 card-title">{{ __('Revenue Report')}}</h5>
        <div class="d-flex">
          <div class="p-0 mx-2">
            <label for="timeline-filter" class="form-label">{{ __('Select Program Type')}}</label>
            <select class="form-select" id="revenue-graph-program-type-filter" name="revenue_graph_program_type_filter">
              <option value="vendor_financing" selected>{{ __('Vendor Financing')}}</option>
              <option value="factoring">{{ __('Factoring')}}</option>
              <option value="dealer_financing">{{ __('Dealer Financing')}}</option>
            </select>
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
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs widget-nav-tabs pb-3 gap-4 mx-1 d-flex flex-nowrap" role="tablist">
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-fees-id" aria-controls="navs-fees-id" aria-selected="false">
              <h6 class="tab-widget-title mb-0 mt-2">{{ __('Fees Revenue')}}</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-orders-id" aria-controls="navs-orders-id" aria-selected="true">
              <h6 class="tab-widget-title mb-0 mt-2">{{ __('Discount Revenue')}}</h6>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-profit-id" aria-controls="navs-profit-id" aria-selected="false">
              <h6 class="tab-widget-title mb-0 mt-2">{{ __('Penal Revenue')}}</h6>
            </a>
          </li>
        </ul>
        <div class="tab-content p-0 ms-0 ms-sm-2">
          <div class="tab-pane fade show active" id="navs-fees-id" role="tabpanel">
            <div id="totalFeesRevenueChart"></div>
          </div>
          <div class="tab-pane fade" id="navs-orders-id" role="tabpanel">
            <div id="totalRevenueChart"></div>
          </div>
          <div class="tab-pane fade" id="navs-profit-id" role="tabpanel">
            <div id="totalInterestRevenueChart"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 d-flex flex-column flex-md-row justify-content-md-between">
        <h5 class="m-0 card-title">{{ __('Revenue')}}</h5>
        <div class="d-flex">
          <div class="p-0 mx-2">
            <label for="timeline-filter" class="form-label">{{ __('Select Program Type')}}</label>
            <select class="form-select" id="revenue-program-type-filter" name="revenue_program_type_filter">
              <option value="vendor_financing" selected>{{ __('Vendor Financing')}}</option>
              <option value="factoring">{{ __('Factoring')}}</option>
              <option value="dealer_financing">{{ __('Dealer Financing')}}</option>
            </select>
          </div>
          <div class="p-0">
            <label for="timeline-filter" class="form-label">{{ __('Select Duration')}}</label>
            <select class="form-select" id="duration-filter" name="duration_filter">
              <option value="past_five_years">{{ __('Past 5 Years')}}</option>
              <option value="past_three_years">{{ __('Past 3 Years')}}</option>
              <option value="past_year" selected>{{ __('Past Year')}}</option>
              <option value="past_six_months">{{ __('Past 6 Months')}}</option>
              <option value="past_three_months">{{ __('Past 3 Months')}}</option>
              <option value="past_one_month">{{ __('Past 1 Month')}}</option>
            </select>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div id="revenueDonutChart"></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6 mb-4">
    <div class="card">
      <div class="card-header pb-0 d-flex flex-column flex-md-row justify-content-md-between">
        <div class="card-title mb-0">
          <h5 class="mb-0">{{ __('Requests') }}</h5>
        </div>
        <div class="p-0">
          <label for="requests-tracker-timeline-filter" class="form-label">{{ __('Select Timeline')}}</label>
          <select class="form-select" id="requests-tracker-timeline-filter" name="timeline_filter">
            <option value="past_ten_years">{{ __('Past 10 Years')}}</option>
            <option value="past_five_years">{{ __('Past 5 Years')}}</option>
            <option value="past_three_years">{{ __('Past 3 Years')}}</option>
            <option value="past_year" selected>{{ __('Past Year')}}</option>
            <option value="past_six_months">{{ __('Past 6 Months')}}</option>
            <option value="past_three_months">{{ __('Past 3 Months')}}</option>
            <option value="past_one_month">{{ __('Past 1 Month')}}</option>
          </select>
        </div>
      </div>
      <div class="card-body row">
        <div class="col-12 col-sm-4 col-md-12 col-lg-4">
          <div class="mt-lg-4 mt-lg-2 mb-lg-4 mb-2 pt-1">
            <h1 class="mb-0" id="total-requests">0</h1>
            <p class="mb-0">{{ __('Total Requests') }}</p>
          </div>
          <ul class="p-0 m-0">
            <li class="d-flex gap-3 align-items-center mb-lg-3 pt-2 pb-1">
              <div class="badge rounded bg-label-primary p-1"><i class="ti ti-ticket ti-sm"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">{{ __('Dealer Financing')}}</h6>
                <small class="text-muted" id="dealer-financing-requests">0</small>
              </div>
            </li>
            <li class="d-flex gap-3 align-items-center mb-lg-3 pb-1">
              <div class="badge rounded bg-label-info p-1"><i class="ti ti-circle-check ti-sm"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">{{ __('Vendor Financing (VFR)')}}</h6>
                <small class="text-muted" id="vendor-financing-requests">0</small>
              </div>
            </li>
            <li class="d-flex gap-3 align-items-center mb-lg-3 pb-1">
              <div class="badge rounded bg-label-warning p-1"><i class="ti ti-address-book ti-sm"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">{{ __('Vendor Financing (FR & FWR)')}}</h6>
                <small class="text-muted" id="factoring-requests">0</small>
              </div>
            </li>
          </ul>
        </div>
        <div class="col-12 col-sm-8 col-md-12 col-lg-8 pt-3">
          <div id="supportTracker"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header pb-0 d-flex justify-content-between">
        <h5 class="m-0 card-title">{{ __('Invoices Status')}}</h5>
        <div class="p-0">
          <label for="timeline-filter" class="form-label">{{ __('Select Program Type')}}</label>
          <select class="form-select" id="type-filter" name="type_filter">
            <option value="vendor_financing" selected>{{ __('Vendor Financing')}}</option>
            <option value="factoring">{{ __('Factoring')}}</option>
            <option value="dealer_financing">{{ __('Dealer Financing')}}</option>
          </select>
        </div>
      </div>
      <div class="card-body">
        <div id="donutChart"></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  @foreach ($reports as $key => $report)
    @if($report['permission'])
      <div class="col-12 col-md-4">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">{{ $key }}</h5>
            <div class="d-flex gap-1 flex-wrap">
              <a href="{{ route('reports.report', ['bank' => $bank, 'report' => $report['name']]) }}" class="btn btn-primary btn-sm">{{ __('Click to View')}}</a>
              <a href="{{ $report['link'] }}" class="btn btn-sm">{{ __('Download Excel')}}</a>
              <a href="{{ $report['pdf_link'] }}" class="btn btn-sm">{{ __('Download PDF')}}</a>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endforeach
</div>

@endsection

