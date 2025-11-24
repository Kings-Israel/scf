<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Cron Name" class="form-label">{{ $t('Cron Name') }}</label>
          <!-- <input
            type="text"
            v-model="cron_name"
            class="form-control form-search"
            placeholder="Search Cron"
            v-on:keyup.enter="filter"
          /> -->
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="cron_name">
            <option value="">{{ $t('Select') }}</option>
            <option v-for="cron_log_type in cron_log_types" :key="cron_log_type" :value="cron_log_type">
              {{ cron_log_type }}
            </option>
          </select>
        </div>
        <div class="">
          <label for="Date" class="form-label">{{ $t('Date') }}</label>
          <input
            class="form-control form-search"
            type="text"
            id="date_search"
            name="cron-logs-daterange"
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
      <div class="d-flex gap-1 justify-content-end mt-2 mt-md-auto">
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
      :from="report_data.from"
      :to="report_data.to"
      :links="report_data.links"
      :next_page="report_data.next_page_url"
      :prev_page="report_data.prev_page_url"
      :total_items="report_data.total"
      :first_page_url="report_data.first_page_url"
      :last_page_url="report_data.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Cron Name') }}</th>
            <th>{{ $t('Date') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Start Time') }}</th>
            <th>{{ $t('End Time') }}</th>
            <th>{{ $t('Time Taken') }}</th>
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
            <td>{{ report.name }}</td>
            <td>{{ moment(report.created_at).format(date_format) }}</td>
            <td>{{ report.status }}</td>
            <td>{{ moment(report.start_time).format(date_format + ' HH:mm:ss') }}</td>
            <td>
              <span v-if="report.end_time">
                {{ moment(report.end_time).format(date_format + ' HH:mm:ss') }}
              </span>
            </td>
            <td>
              <span v-if="report.start_time && report.end_time">
                {{ moment(report.start_time).diff(report.end_time, 'minutes') }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="report_data.from"
      :to="report_data.to"
      :links="report_data.links"
      :next_page="report_data.next_page_url"
      :prev_page="report_data.prev_page_url"
      :total_items="report_data.total"
      :first_page_url="report_data.first_page_url"
      :last_page_url="report_data.last_page_url"
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
    const cron_name = ref('');
    const date = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const sort_by = ref('');

    const date_search = ref('');

    const cron_log_types = ref([
      'Auto Loan Reject Cron',
      'Discount Charge Posting',
      'Program Limit Expiry',
      'Loan Overdue Remainder',
      'Loan Repayment Remainder',
      'Invoice Repayment',
      'DF: Block Company as per "Limit Overdue Date"',
      'DF Discount Charge Accrual',
      'IF Discount Charge Accrual'
    ]);

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data?type=cron-logs&per_page=' + per_page.value)
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], async ([per_page]) => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'cron-logs',
            per_page: per_page,
            name: cron_name.value,
            date: date.value,
            from_date: from_date.value,
            to_date: to_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'cron-logs',
            per_page: per_page.value,
            name: cron_name.value,
            date: date.value,
            from_date: from_date.value,
            to_date: to_date.value,
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
      cron_name.value = '';
      date.value = '';
      sort_by.value = '';
      from_date.value = '';
      to_date.value = '';
      $('#date_search').val('');
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'cron-logs',
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

    onMounted(() => {
      getData();
    });

    const changePage = async page => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            type: 'cron-logs',
            per_page: per_page.value,
            name: cron_name.value,
            date: date.value,
            from_date: from_date.value,
            to_date: to_date.value,
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
            type: 'cron-logs',
            name: cron_name.value,
            date: date.value,
            from_date: from_date.value,
            to_date: to_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Cron_logs_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'cron-logs',
            name: cron_name.value,
            date: date.value,
            from_date: from_date.value,
            to_date: to_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Cron_logs_${moment().format('Do_MMM_YYYY')}.pdf`);
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
      cron_name,
      date,
      sort_by,
      cron_log_types,

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
