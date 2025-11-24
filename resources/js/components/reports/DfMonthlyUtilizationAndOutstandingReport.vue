<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Status" class="form-label">{{ $t('OD Account') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="OD Account"
            v-model="od_account"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Dealer') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Dealer"
            v-model="dealer"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Month') }}</label>
          <input
            type="month"
            class="form-control form-search"
            placeholder="Month"
            v-model="month"
            v-on:keyup.enter="filter"
          />
        </div>
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
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
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
            <th>{{ $t('OD Account') }}</th>
            <th>{{ $t('Month') }}</th>
            <th>{{ $t('Total OD Limit') }}</th>
            <th>{{ $t('Utilized Limit') }} (%)</th>
            <th>{{ $t('Principle Outstanding') }}</th>
            <th>{{ $t('Interest Outstanding') }} ({{ $t('Posted') }})</th>
            <th>{{ $t('Principle DPD') }} ({{ $t('Days') }})</th>
            <th>{{ $t('Principle DPD Amount') }}</th>
            <th>{{ $t('Interest DPD') }} ({{ $t('Days') }})</th>
            <th>{{ $t('Interest DPD Amount') }}</th>
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
          <tr v-for="data in report_data.data" :key="data.id">
            <td>{{ data.payment_request.payment_account_number }}</td>
            <td>{{ moment(data.created_at).format('MMM YYYY') }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(data.payment_request.sanctioned_limit) }}</td>
            <td>{{ data.payment_request.utilized_percentage_ratio }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(data.payment_request.invoice_disbursed_amount) }}
            </td>
            <td class="text-success">-</td>
            <td class="">{{ new Intl.NumberFormat().format(data.payment_request.invoice_days_past_due) }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(data.payment_request.invoice_overdue_amount) }}
            </td>
            <td class="">{{ new Intl.NumberFormat().format(data.payment_request.invoice_days_past_due) }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(data.payment_request.invoice_overdue_amount) }}
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
  props: ['bank'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const od_account = ref('');
    const dealer = ref('');
    const month = ref('');
    const selected_month = ref('');
    const year = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-monthly-utilization-and-outstanding-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getData();
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      if (month.value != '') {
        selected_month.value = month.value.split('-')[1];
        year.value = month.value.split('-')[0];
      }
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-monthly-utilization-and-outstanding-report',
            per_page: per_page.value,
            od_account: od_account.value,
            dealer: dealer.value,
            month: selected_month.value,
            year: year.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      od_account.value = '';
      dealer.value = '';
      month.value = '';
      selected_month.value = '';
      year.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-monthly-utilization-and-outstanding-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'maturing-payments-report',
            per_page: per_page.value,
            od_account: od_account.value,
            dealer: dealer.value,
            month: month.value,
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'df-monthly-utilization-and-outstanding-report',
            od_account: od_account.value,
            dealer: dealer.value,
            month: selected_month.value,
            year: year.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `DF_monthly_utilization_and_outstanding_report_${moment().format('Do_MMM_YYYY')}.csv`
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
            type: 'df-monthly-utilization-and-outstanding-report',
            od_account: od_account.value,
            dealer: dealer.value,
            month: selected_month.value,
            year: year.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `DF_monthly_utilization_and_outstanding_report_${moment().format('Do_MMM_YYYY')}.pdf`
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

    return {
      moment,
      od_account,
      dealer,
      month,
      sort_by,

      per_page,

      report_data,

      changePage,
      filter,
      refresh,
      downloadReport,
      downloadPdf,

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
