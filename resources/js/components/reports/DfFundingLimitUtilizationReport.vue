<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Organization Name" class="form-label">{{ $t('Organization Name') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="Organization Name"
            v-model="anchor_search"
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
        <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">50</option>
        </select>
        <download-buttons
          @download-report="downloadReport"
          @download-pdf="downloadPdf"
          :downloading-excel="downloadingExcel"
          :downloading-pdf="downloadingPdf"
        ></download-buttons>
      </div>
    </div>
    <pagination
      class="mx-2"
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
            <th>{{ $t('Organization Name') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Current Exposure') }}</th>
            <th>{{ $t('Pipeline Requests') }}</th>
            <th>{{ $t('Utilized Limit') }} (%)</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="text-nowrap">
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
              <a :href="'../companies/' + data.company_id + '/details'" class="text-primary text-decoration-underline">
                {{ data.company_name }}
              </a>
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(data.sanctioned_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(data.available_amount.toFixed(2)) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(data.utilized_amount.toFixed(2)) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(data.pipeline_amount.toFixed(2)) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(data.utilized_percentage.toFixed(2)) }}</td>
            <td>
              <a v-if="data.buyer_id" :href="'../reports/limit-utilization/' + data.program_id + '/' + data.buyer_id">
                <i class="ti ti-sm ti-eye"></i>
              </a>
              <a v-else :href="'../reports/limit-utilization/' + data.program_id + '/' + data.company_id">
                <i class="ti ti-sm ti-eye"></i>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      class="mx-2"
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
import { useToast } from 'vue-toastification';
import { inject, onMounted, ref, watch } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';

export default {
  name: 'VfFundingLimitUtilizationReport',
  props: ['bank', 'date_format'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const date_format = props.date_format;
    const base_url = inject('baseURL');
    const toast = useToast();
    const report_data = ref([]);

    // Search fields
    const anchor_search = ref('');
    const type_search = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getReport = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-funding-limit-utilization-report'
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
      getReport();
    });

    // watch([anchor_search, type_search, per_page], async ([anchor_search, type_search, per_page]) => {
    //   await axios.get(base_url+props.bank+'/reports/data', {
    //     params: {
    //       per_page: per_page,
    //       company_name: anchor_search,
    //       company_type: type_search,
    //       type: 'df-funding-limit-utilization-report'
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
            per_page: per_page.value,
            company_name: anchor_search.value,
            company_type: type_search.value,
            sort_by: sort_by.value,
            type: 'df-funding-limit-utilization-report'
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
      anchor_search.value = '';
      type_search.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-funding-limit-utilization-report',
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
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'df-funding-limit-utilization-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `DF_funding_limit_utilization_report_${moment().format('Do_MMM_YYYY')}.csv`
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
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'df-funding-limit-utilization-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `DF_funding_limit_utilization_report_${moment().format('Do_MMM_YYYY')}.pdf`
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'df-funding-limit-utilization-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        });
    };

    return {
      moment,
      report_data,
      // Search fields
      anchor_search,
      type_search,
      sort_by,

      // Pagination
      per_page,

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
