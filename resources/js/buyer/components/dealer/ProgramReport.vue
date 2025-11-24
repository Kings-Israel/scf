<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex">
        <div class="">
          <label for="Loan/OD Account" class="form-label">{{ $t('Loan/OD Account') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Loan/OD Account"
            v-model="program_code_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="mx-1">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Anchor"
            v-model="anchor_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="mx-1 table-search-btn">
          <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mx-1 table-clear-btn">
          <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex align-items-end">
        <select class="form-select mx-2" id="exampleFormControlSelect1" v-model="per_page">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }}
        </button>
      </div>
    </div>
    <pagination
      v-if="programs.meta"
      :from="programs.meta.from"
      :to="programs.meta.to"
      :links="programs.meta.links"
      :next_page="programs.links.next"
      :prev_page="programs.links.prev"
      :total_items="programs.meta.total"
      :first_page_url="programs.links.first"
      :last_page_url="programs.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="text-nowrap">
            <th>{{ $t('Loan/OD Account') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Financing Limit') }}</th>
            <th>{{ $t('Utilized Limit') }}</th>
            <th>{{ $t('Pipeline Amount') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Limit Review Date') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!programs.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="programs.data && programs.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="program in programs.data" :key="program.id">
            <td class="text-primary">{{ program.payment_account_number }}</td>
            <td>
              <span class="me-1">{{ program.anchor_name }}</span>
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(program.sanctioned_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(program.utilized_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(program.pipeline_amount) }}</td>
            <td class="text-success">
              {{
                new Intl.NumberFormat().format(
                  program.sanctioned_limit - program.utilized_amount - program.pipeline_amount
                )
              }}
            </td>
            <td>{{ program.limit_review_date ? moment(program.limit_review_date).format('DD MMM YYYY') : '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="programs.meta"
      :from="programs.meta.from"
      :to="programs.meta.to"
      :links="programs.meta.links"
      :next_page="programs.links.next"
      :prev_page="programs.links.prev"
      :total_items="programs.meta.total"
      :first_page_url="programs.links.first"
      :last_page_url="programs.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import moment from 'moment';
import { ref, watch, onMounted, inject } from 'vue';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'ProgramReport',
  components: {
    Pagination
  },
  setup(props, context) {
    const base_url = inject('baseURL');
    const programs = ref([]);
    const program_code_search = ref('');
    const anchor_search = ref('');

    const per_page = ref(50);

    const getPrograms = () => {
      axios
        .get(base_url + 'reports/programs-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        });
    };

    watch([per_page], ([per_page]) => {
      axios
        .get(base_url + 'reports/programs-report', {
          params: {
            per_page: per_page,
            program_code: program_code_search.value,
            anchor: anchor_search.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = () => {
      axios
        .get(base_url + 'reports/programs-report', {
          params: {
            per_page: per_page.value,
            program_code: program_code_search.value,
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

    const refresh = () => {
      program_code_search.value = '';
      anchor_search.value = '';
      axios
        .get(base_url + '/programs-report', {
          params: {
            per_page: per_page.value
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
            per_page: per_page.value,
            program_code: program_code_search.value,
            anchor: anchor_search.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        });
    };

    const exportReport = () => {
      axios
        .get(base_url + 'report/dealer-programs-report/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            program_code: program_code_search.value,
            anchor: anchor_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Programs_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportPdfReport = () => {
      axios
        .get(base_url + 'report/dealer-programs-report/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            program_code: program_code_search.value,
            anchor: anchor_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Programs_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPrograms();
    });

    return {
      moment,
      programs,
      per_page,
      program_code_search,
      anchor_search,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh
    };
  }
};
</script>
