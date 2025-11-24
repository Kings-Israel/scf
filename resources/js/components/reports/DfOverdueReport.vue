<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="OD Account" class="form-label">{{ $t('OD Account') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="OD Account"
            v-model="od_account"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Dealer" class="form-label">{{ $t('Dealer') }}</label>
          <input type="text" class="form-control" placeholder="Dealer" v-model="dealer" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input type="text" class="form-control" placeholder="Anchor" v-model="anchor" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Drawdown Date') }})</label>
          <input
            type="text"
            id="date_search"
            class="form-control form-search"
            name="df-overdue-disbursement-daterange"
            placeholder="Select Dates"
            autocomplete="off"
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
          <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
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
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Invoice/Unique Ref No') }}.</th>
            <th>{{ $t('Drawdown Date') }}</th>
            <th>{{ $t('Overdue Principal') }}</th>
            <th>{{ $t('Principal DPD') }} ({{ $t('Days') }})</th>
            <th>{{ $t('Overdue Interest') }}</th>
            <th>{{ $t('Interest DPD') }} ({{ $t('Days') }})</th>
            <th>{{ $t('Total Penal Interest') }}</th>
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
          <tr class="text-nowrap" v-for="od_account in report_data.data" :key="od_account.id">
            <td>{{ od_account.payment_account_number }}</td>
            <td>{{ od_account.company }}</td>
            <td>{{ od_account.anchor }}</td>
            <td class="">{{ od_account.invoice_number }}</td>
            <td class="">{{ moment(od_account.disbursement_date).format(date_format) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(od_account.disbursed_amount) }}</td>
            <td class="">{{ od_account.days_past_due }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(od_account.overdue_amount) }}</td>
            <td class="">{{ od_account.days_past_due }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(od_account.overdue_amount) }}</td>
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
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const od_account = ref('');
    const anchor = ref('');
    const dealer = ref('');
    const sort_by = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-overdue-report'
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-overdue-report',
            per_page: per_page.value,
            od_account: od_account.value,
            anchor: anchor.value,
            dealer: dealer.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
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
      anchor.value = '';
      dealer.value = '';
      sort_by.value = '';
      from_date.value = '';
      to_date.value = '';
      date_search.value = '';
      $('#date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-overdue-report',
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'df-overdue-report',
            od_account: od_account.value,
            anchor: anchor.value,
            dealer: dealer.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_overdue_invoices_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'df-overdue-report',
            od_account: od_account.value,
            anchor: anchor.value,
            dealer: dealer.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_overdue_invoices_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    const changePage = async page => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            type: 'maturing-payments-report',
            per_page: per_page.value,
            od_account: od_account.value,
            anchor: anchor.value,
            dealer: dealer.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
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
      od_account,
      anchor,
      dealer,
      sort_by,
      from_date,
      to_date,

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
