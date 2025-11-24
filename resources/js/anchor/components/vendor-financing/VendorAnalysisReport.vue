<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="vendors_search"
            id="defaultFormControlInput"
            placeholder="Vendor"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary">
          <span class="d-flex" v-if="!downloadingExcel">
            <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
          </span>
          <img :src="spinner" style="width: 1rem" v-else />
        </button>
      </div>
    </div>
    <pagination
      :from="report.from"
      :to="report.to"
      :links="report.links"
      :next_page="report.next_page_url"
      :prev_page="report.prev_page_url"
      :total_items="report.total"
      :first_page_url="report.first_page_url"
      :last_page_url="report.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="text-nowrap">
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Vendor Limit') }}</th>
            <th>{{ $t('Utilized Limit') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Pipeline Amount') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!report.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="report.data && report.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="company in report.data" :key="company.id">
            <td>
              <span class="text-primary">{{ company.name }}</span>
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(company.configuration.sanctioned_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(company.utilized_amount) }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(company.configuration.sanctioned_limit - company.utilized_amount) }}
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(company.pipeline_amount) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="report.from"
      :to="report.to"
      :links="report.links"
      :next_page="report.next_page_url"
      :prev_page="report.prev_page_url"
      :total_items="report.total"
      :first_page_url="report.first_page_url"
      :last_page_url="report.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, onMounted, watch } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'VendorAnalysisReport',
  components: {
    Pagination
  },
  setup(props, context) {
    const per_page = ref(50);
    const type = ref('vendor-analysis');
    const report = ref([]);
    const vendors_search = ref('');

    const spinner = '../../../../../assets/img/spinner.svg';

    const getReport = () => {
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getReport();
    });

    const downloadingExcel = ref(false);

    const exportReport = () => {
      downloadingExcel.value = true;
      axios
        .get('../report/vendor-analysis/export', {
          responseType: 'arraybuffer',
          method: 'GET'
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Vendors_${moment().format('Do_MMM_YYYY')}.csv`);
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

    const filter = () => {
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value,
            vendors: vendors_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const refresh = () => {
      vendors_search.value = '';
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], ([per_page]) => {
      axios
        .get('report', {
          params: {
            per_page: per_page,
            type: type.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: type.value,
            vendors: vendors_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(error => {
          console.log(error);
        });
    };

    return {
      moment,
      report,
      vendors_search,
      per_page,
      changePage,
      exportReport,
      filter,
      refresh,

      downloadingExcel,
      spinner
    };
  }
};
</script>
