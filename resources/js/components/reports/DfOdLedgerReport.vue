<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="OD Account" class="form-label">{{ $t('OD Account') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="OD Account"
            v-model="od_account"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Dealer" class="form-label">{{ $t('Dealer') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Dealer"
            v-model="dealer"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Invoice Ref" class="form-label">{{ $t('Invoice Ref No') }}.</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Invoice / Unique Ref No."
            v-model="invoice_number"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Date" class="form-label">{{ $t('Date') }}</label>
          <input
            class="form-control form-search"
            type="date"
            id="html5-date-input"
            v-model="date"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Overdue Date" class="form-label">{{ $t('Overdue Date') }}</label>
          <input
            class="form-control form-search"
            type="date"
            id="html5-date-input"
            v-model="overdue_date"
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
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
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
            <th>{{ $t('Dealer') }}</th>
            <th>{{ $t('Invoice/Unique Ref No') }}.</th>
            <th>{{ $t('Date') }}</th>
            <th>{{ $t('Transaction Type') }}</th>
            <th>{{ $t('Debit') }}</th>
            <th>{{ $t('Credit') }}</th>
            <th>{{ $t('Principle Balance') }}</th>
            <th>{{ $t('Discount') }}</th>
            <th>{{ $t('Penal Discount') }}</th>
            <th>{{ $t('Overdue Date') }}</th>
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
            <td>
              {{ data.payment_request.company_name }}
            </td>
            <td>{{ data.payment_request.invoice_number }}</td>
            <td>{{ moment(data.created_at).format(date_format) }}</td>
            <td>{{ data.transaction_type }}</td>
            <td class="">{{ data.debit_from_account }}</td>
            <td class="">
              {{ data.credit_to_account }}
            </td>
            <td class="text-success">
              {{
                data.payment_request
                  ? new Intl.NumberFormat().format(data.payment_request.invoice_disbursed_amount)
                  : '0'
              }}
            </td>
            <td class="text-success">{{ data.payment_request.discount }}</td>
            <td class="text-success">{{ data.payment_request.invoice_overdue }}</td>
            <td>
              {{ data.payment_request ? moment(data.payment_request.invoice_due_date).format(date_format) : '-' }}
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
    const od_account = ref('');
    const dealer = ref('');
    const invoice_number = ref('');
    const date = ref('');
    const overdue_date = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-od-ledger-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-od-ledger-report',
            per_page: per_page.value,
            od_account: od_account.value,
            dealer: dealer.value,
            invoice_number: invoice_number.value,
            date: date.value,
            overdue_date: overdue_date.value,
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
      invoice_number.value = '';
      date.value = '';
      sort_by.value = '';
      overdue_date.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-od-ledger-report',
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
      await axios
        .get(page, {
          params: {
            type: 'df-od-ledger-report',
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'df-od-ledger-report',
            od_account: od_account.value,
            dealer: dealer.value,
            invoice_number: invoice_number.value,
            date: date.value,
            overdue_date: overdue_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_OD_ledger_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'df-od-ledger-report',
            od_account: od_account.value,
            dealer: dealer.value,
            invoice_number: invoice_number.value,
            date: date.value,
            overdue_date: overdue_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_od_ledger_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
      invoice_number,
      date,
      overdue_date,
      sort_by,

      per_page,

      report_data,

      filter,
      refresh,

      changePage,
      downloadPdf,
      downloadReport,

      date_format
    };
  }
};
</script>
<style>
.m_title::first-letter {
  text-transform: capitalize;
}
</style>
