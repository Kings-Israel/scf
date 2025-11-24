<template>
  <div class="p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor Name') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Anchor"
            v-model="anchor_search"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="mx-1 table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mx-1 table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex align-items-end">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <i class="ti ti-download ti-xs px-1"></i> Excel
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
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Anchor Limit') }}</th>
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
            <td class="text-success">
              {{ company.default_currency }}.{{
                new Intl.NumberFormat().format(company.configuration.sanctioned_limit)
              }}
            </td>
            <td class="text-success">
              {{ company.default_currency }}.{{ new Intl.NumberFormat().format(company.utilized_amount) }}
            </td>
            <td class="text-success">
              {{ company.default_currency }}.{{
                new Intl.NumberFormat().format(company.configuration.sanctioned_limit - company.utilized_amount)
              }}
            </td>
            <td class="text-success">
              {{ company.default_currency }}.{{ new Intl.NumberFormat().format(company.pipeline_amount) }}
            </td>
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
    const anchor_search = ref('');

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

    context.expose({ getReport });

    const filter = () => {
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value
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
            type: type.value,
            anchor: anchor_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const refresh = () => {
      anchor_search.value = '';
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: type.value,
            anchor: anchor_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(error => {
          console.log(error);
        });
    };

    const exportReport = () => {
      axios
        .get('report/vendor-analysis/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Anchors_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      report,
      per_page,
      anchor_search,
      changePage,
      exportReport,
      filter,
      refresh
    };
  }
};
</script>
