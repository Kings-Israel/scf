<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Program" class="form-label">{{ $t('Program') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Program Name"
            v-model="program_name"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="CBS ID" class="form-label">{{ $t('CBS ID') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="CBS ID"
            v-model="cbs_id"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Date" class="form-label">{{ $t('Date') }}</label>
          <input
            type="date"
            class="form-control form-search"
            placeholder="Date"
            v-model="date"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Set Off Type" class="form-label">{{ $t('Set Off Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="set_off_type">
            <option value="">{{ $t('Set off type') }}</option>
            <option value="Penal Interest">{{ $t('Penal Interest') }}</option>
            <option value="Principal">{{ $t('Principal') }}</option>
            <option value="Interest">{{ $t('Interest') }}</option>
          </select>
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
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
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
            <th>{{ $t('CBS ID') }}</th>
            <th>{{ $t('Program Name') }}</th>
            <th>{{ $t('Total Repayment Amount') }}</th>
            <th>{{ $t('Set off Amount') }}</th>
            <th>{{ $t('Balance Amount') }}</th>
            <th>{{ $t('Set off particulars') }}</th>
            <th>{{ $t('Set off type') }}</th>
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
            <td>
              <span>{{ moment(data.transaction_created_date).format(date_format) }}</span>
            </td>
            <td>
              <span>{{ data.cbs_id }}</span>
            </td>
            <td>
              <span>
                {{ data.payment_request.program_name }}
              </span>
            </td>
            <td>
              <span>
                {{ new Intl.NumberFormat().format(data.amount) }}
              </span>
            </td>
            <td>
              <span>{{ new Intl.NumberFormat().format(data.payment_request.drawdown_amount) }}</span>
            </td>
            <td>
              <span>{{ new Intl.NumberFormat().format(data.payment_request.invoice_balance) }}</span>
            </td>
            <td>
              <span v-if="data.transaction_type == 'Overdue Account'"
                >{{ $t('Penal Interest for') }} {{ data.payment_request.invoice_number }}</span
              >
              <span v-if="data.transaction_type == 'Bank Invoice Payment' || data.transaction_type == 'Repayment'"
                >{{ $t('Principle for') }} {{ data.payment_request.invoice_number }}</span
              >
              <span v-if="data.transaction_type == 'Fees/Charges' || data.transaction_type == 'Accrual/Posted Interest'"
                >{{ $t('Interest for') }} {{ data.payment_request.invoice_number }}</span
              >
            </td>
            <td>
              <span v-if="data.transaction_type == 'Overdue Account'">{{ $t('Penal Interest') }}</span>
              <span v-if="data.transaction_type == 'Bank Invoice Payment' || data.transaction_type == 'Repayment'">{{
                $t('Principle')
              }}</span>
              <span
                v-if="data.transaction_type == 'Fees/Charges' || data.transaction_type == 'Accrual/Posted Interest'"
                >{{ $t('Interest') }}</span
              >
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
    const cbs_id = ref('');
    const program_name = ref('');
    const date = ref('');
    const set_off_type = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-repayment-details-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-repayment-details-report',
            per_page: per_page,
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
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
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-repayment-details-report',
            per_page: per_page.value,
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
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
      program_name.value = '';
      cbs_id.value = '';
      date.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-repayment-details-report',
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'df-repayment-details-report',
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_Repayment_Details_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'df-repayment-details-report',
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_Repayment_Details_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'df-repayment-details-report',
            per_page: per_page.value,
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
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
      program_name,
      cbs_id,
      date,
      set_off_type,
      sort_by,

      per_page,

      report_data,

      filter,
      refresh,

      changePage,
      downloadPdf,
      downloadReport,

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
