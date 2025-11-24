<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="d-none">
          <label for="Organization Name" class="form-label">{{ $t('Type') }}</label>
          <select name="" v-model="type_search" id="" class="form-control">
            <option value="">{{ $t('Select Company Type') }}</option>
            <option value="vendor">{{ $t('Vendor') }}</option>
            <option value="anchor">{{ $t('Anchor') }}</option>
          </select>
        </div>
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
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto" style="height: fit-content">
        <select class="form-select" v-model="per_page" style="height: fit-content">
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
            <td class="text-success">
              {{ new Intl.NumberFormat().format(data.utilized_percentage.toFixed(2)) }}
            </td>
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
import { useToast } from 'vue-toastification';
import { inject, onMounted, ref, watch } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';

export default {
  name: 'VfFundingLimitUtilizationReport',
  props: ['bank'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const anchor_search = ref('');
    const type_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getReport = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-funding-limit-utilization-report'
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page,
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'vf-funding-limit-utilization-report'
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
            per_page: per_page.value,
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'vf-funding-limit-utilization-report'
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
      anchor_search.value = '';
      type_search.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vf-funding-limit-utilization-report',
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
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'vf-funding-limit-utilization-report'
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `VF_funding_limit_utilization_report_${moment().format('Do_MMM_YYYY')}.csv`
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
            type: 'vf-funding-limit-utilization-report',
            company_name: anchor_search.value,
            company_type: type_search.value,
            type: 'vf-funding-limit-utilization-report'
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `VF_funding_limit_utilization_Report_${moment().format('Do_MMM_YYYY')}.pdf`
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
            type: 'vf-funding-limit-utilization-report'
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

      // Pagination
      per_page,

      filter,
      refresh,

      changePage,
      downloadReport,
      downloadPdf,

      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
