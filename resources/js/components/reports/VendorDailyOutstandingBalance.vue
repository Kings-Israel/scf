<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input type="text" class="form-control" v-model="vendor" placeholder="Vendor" v-on:keyup.enter="filter" />
        </div>
        <!-- <div class="">
          <label for="Date" class="form-label">{{ $t('Date') }}</label>
          <input class="form-control" type="date" id="html5-date-input" v-model="date" v-on:keyup.enter="filter" />
        </div> -->
        <div class="">
          <label for="Status" class="form-label">{{ $t('Sort By') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="sort_by">
            <option value="">{{ $t('Sort By') }}</option>
            <option value="ASC">{{ $t('Ascending') }}</option>
            <option value="DESC">{{ $t('Descending') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex gap-1 justify-content-md-end mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <download-buttons
            @download-report="downloadReport"
            @download-pdf="downloadPdf"
            :downloading-excel="downloadingExcel"
            :downloading-pdf="downloadingPdf"
          ></download-buttons>
        </div>
      </div>
    </div>
    <pagination
      v-if="report_data.meta"
      :from="report_data.meta.from"
      :to="report_data.meta.to"
      :links="report_data.meta.links"
      :next_page="report_data.links.next"
      :prev_page="report_data.links.prev"
      :total_items="report_data.meta.total"
      :first_page_url="report_data.links.first"
      :last_page_url="report_data.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Date') }}</th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Outstanding Balance') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Pipeline Requests') }}</th>
            <th>{{ $t('Limit Utilized') }}(%)</th>
            <th>{{ $t('Available Limit') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!report_data.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="report_data.data && report_data.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="report in report_data.data" :key="report.id">
            <td>{{ moment().format(date_format) }}</td>
            <td>
              <span class="d-flex gap-1" v-if="report.buyer_id">
                <a :href="'../companies/' + report.buyer_id + '/details'">{{ report.buyer_name }}</a>
                <span>({{ report.payment_account_number }})</span>
              </span>
              <span class="d-flex gap-1" v-else>
                <a :href="'../companies/' + report.company_id + '/details'">{{ report.company_name }}</a>
                <span>({{ report.payment_account_number }})</span>
              </span>
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.utilized_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.sanctioned_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.pipeline_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.utilized_percentage) }}</td>
            <td class="text-success">
              {{
                new Intl.NumberFormat().format(
                  report.sanctioned_limit - (report.utilized_amount + report.pipeline_amount)
                )
              }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="report_data.meta"
      :from="report_data.meta.from"
      :to="report_data.meta.to"
      :links="report_data.meta.links"
      :next_page="report_data.links.next"
      :prev_page="report_data.links.prev"
      :total_items="report_data.meta.total"
      :first_page_url="report_data.links.first"
      :last_page_url="report_data.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import moment from 'moment';

export default {
  name: 'AllPayments',
  components: {
    Pagination,
    DownloadButtons
  },
  props: ['bank', 'date_format'],
  setup(props) {
    const date_format = props.date_format;
    const toast = useToast();
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const date = ref('');
    const vendor = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vendors-daily-outstanding-balance-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    // watch([date, vendor, per_page], async ([date, vendor, per_page]) => {
    //   await axios.get(base_url+props.bank+'/reports/data', {
    //     params: {
    //       type: 'vendors-daily-outstanding-balance-report',
    //       per_page: per_page,
    //       date: date,
    //       vendor: vendor,
    //     }
    //   })
    //     .then(response => {
    //       report_data.value = response.data
    //     })
    //     .catch(err => {
    //       console.log(err)
    //     })
    // })

    const filter = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vendors-daily-outstanding-balance-report',
            per_page: per_page.value,
            date: date.value,
            vendor: vendor.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const refresh = async () => {
      date.value = '';
      vendor.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vendors-daily-outstanding-balance-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'vendors-daily-outstanding-balance-report',
            date: date.value,
            vendor: vendor.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `Vendor_daily_outstanding_balance_report_${moment().format('Do_MMM_YYYY')}.csv`
          );
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          downloadingExcel.value = false;
        });
    };

    const downloadPdf = () => {
      downloadingPdf.value = true;
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'vendors-daily-outstanding-balance-report',
            date: date.value,
            vendor: vendor.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `Vendors_daily_outstanding_balance_Report_${moment().format('Do_MMM_YYYY')}.pdf`
          );
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          downloadingPdf.value = false;
        });
    };

    onMounted(() => {
      getData();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'vendors-daily-outstanding-balance-report',
            per_page: per_page.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      date,
      vendor,
      sort_by,

      per_page,

      report_data,

      filter,
      refresh,

      changePage,
      downloadReport,
      downloadPdf,

      date_format,
      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
<style>
.m_title::first-letter {
  text-transform: capitalize;
}
</style>
