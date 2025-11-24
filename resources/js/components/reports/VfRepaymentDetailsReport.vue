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
            type="text"
            id="transaction_date_search"
            class="form-control form-search"
            name="vf-repayment-details-report-transaction-daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <div class="">
          <label for="Set Off Type" class="form-label">{{ $t('Set Off Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="set_off_type">
            <option value="">{{ $t('Set off type') }}</option>
            <option value="Overdue Account">{{ $t('Penal Interest') }}</option>
            <option value="Bank Invoice Payment">{{ $t('Principal') }}</option>
            <option value="Discount Charge">{{ $t('Discount Charge') }}</option>
            <option value="Fees/Charges">{{ $t('Fee Charge') }}</option>
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
      <div class="d-flex gap-1 justify-content-md-end mt-2 mt-md-auto">
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
              <span>{{ data.id }}</span>
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
              <span>{{ new Intl.NumberFormat().format(data.payment_request.invoice_amount) }}</span>
            </td>
            <td>
              <span>{{ new Intl.NumberFormat().format(data.payment_request.invoice_balance) }}</span>
            </td>
            <td>
              <span v-if="data.transaction_type === 'Overdue Account'"
                >{{ $t('Penal Charge for') }} {{ data.payment_request.invoice_number }}</span
              >
              <span v-if="data.transaction_type === 'Bank Invoice Payment' || data.transaction_type === 'Repayment'"
                >{{ $t('Principle for') }} {{ data.payment_request.invoice_number }}</span
              >
              <span
                v-if="
                  data.transaction_type === 'Discount Charge' || data.transaction_type === 'Accrual/Posted Interest'
                "
                >{{ $t('Discount Charge for') }} {{ data.payment_request.invoice_number }}</span
              >
              <span v-if="data.transaction_type === 'Fees/Charges'"
                >{{ $t('Fee Charge for') }} {{ data.payment_request.invoice_number }}</span
              >
            </td>
            <td>
              <span v-if="data.transaction_type === 'Overdue Account'">{{ $t('Penal Charge') }}</span>
              <span v-if="data.transaction_type === 'Bank Invoice Payment' || data.transaction_type == 'Repayment'">{{
                $t('Principle')
              }}</span>
              <span
                v-if="
                  data.transaction_type === 'Discount Charge' || data.transaction_type === 'Accrual/Posted Interest'
                "
                >{{ $t('Discount Charge') }}</span
              >
              <span v-if="data.transaction_type === 'Fees/Charges'">{{ $t('Fee Charge') }}</span>
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
    const from_transaction_date_search = ref('');
    const to_transaction_date_search = ref('');
    const set_off_type = ref('');
    const sort_by = ref('');

    const transaction_date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-repayment-details-report'
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
            type: 'vf-repayment-details-report',
            per_page: per_page,
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            from_transaction_date: from_transaction_date_search.value,
            to_transaction_date: to_transaction_date_search.value,
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
      transaction_date_search.value = $('#transaction_date_search').val();
      if (transaction_date_search.value) {
        from_transaction_date_search.value = moment(transaction_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_transaction_date_search.value = moment(transaction_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vf-repayment-details-report',
            per_page: per_page.value,
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            from_transaction_date: from_transaction_date_search.value,
            to_transaction_date: to_transaction_date_search.value,
            sort_by: sort_by.value,
            set_off_type: set_off_type.value
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
      from_transaction_date_search.value = '';
      to_transaction_date_search.value = '';
      sort_by.value = '';
      set_off_type.value = '';
      $('#transaction_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vf-repayment-details-report',
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
            type: 'vf-repayment-details-report',
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            from_transaction_date: from_transaction_date_search.value,
            to_transaction_date: to_transaction_date_search.value,
            sort_by: sort_by.value,
            set_off_type: set_off_type.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_Repayment_details_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'vf-repayment-details-report',
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            from_transaction_date: from_transaction_date_search.value,
            to_transaction_date: to_transaction_date_search.value,
            sort_by: sort_by.value,
            set_off_type: set_off_type.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_Repayment_details_report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'vf-repayment-details-report',
            per_page: per_page.value,
            program_name: program_name.value,
            cbs_id: cbs_id.value,
            date: date.value,
            from_transaction_date: from_transaction_date_search.value,
            to_transaction_date: to_transaction_date_search.value,
            sort_by: sort_by.value,
            set_off_type: set_off_type.value
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
