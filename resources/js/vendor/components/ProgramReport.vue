<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Status" class="form-label mx-1">{{ $t('Loan/OD Account') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Program Code"
            v-model="program_code_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="mx-1">
          <label for="Status" class="form-label mx-1">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Anchor"
            v-model="program_anchor_search"
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
      <div class="d-flex justify-content-md-end mt-2 mt-md-auto gap-1">
        <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button type="button" @click="exportReport" class="btn btn-primary">
          <span class="d-flex" v-if="!downloadingExcel">
            <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
          </span>
          <img :src="spinner" style="width: 1rem" v-else />
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <span class="d-flex" v-if="!downloadingPdf"> <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }} </span>
          <img :src="spinner" style="width: 1rem" v-else />
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
            <td>
              <span class="me-1">{{ program.anchor_name }}</span>
            </td>
            <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(program.sanctioned_limit) }}</td>
            <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(program.utilized_amount) }}</td>
            <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(program.pipeline_amount) }}</td>
            <td class="text-success text-nowrap">
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
import Pagination from './partials/Pagination.vue';

export default {
  name: 'ProgramReport',
  components: {
    Pagination
  },
  setup(props, context) {
    const base_url = inject('baseURL');
    const programs = ref([]);
    const per_page = ref(50);
    const program_code_search = ref('');
    const program_anchor_search = ref('');

    const spinner = '../../../../../assets/img/spinner.svg';

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

    onMounted(() => {
      getPrograms();
    });

    watch([per_page], ([per_page]) => {
      axios
        .get(base_url + 'reports/programs-report', {
          params: {
            per_page: per_page
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
            anchor: program_anchor_search.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const refresh = () => {
      program_code_search.value = '';
      program_anchor_search.value = '';
      axios
        .get(base_url + 'reports/programs-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const exportReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + 'report/programs-report/export', {
          responseType: 'arraybuffer',
          method: 'GET'
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
        })
        .finally(() => {
          downloadingExcel.value = false;
        });
    };

    const exportPdfReport = () => {
      downloadingPdf.value = true;
      axios
        .get(base_url + 'report/programs-report/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET'
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
        })
        .finally(() => {
          downloadingPdf.value = false;
        });
    };

    const changePage = async page => {
      await axios.get(page).then(response => {
        programs.value = response.data.programs;
      });
    };

    return {
      moment,
      program_code_search,
      program_anchor_search,
      programs,
      per_page,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh,

      downloadingExcel,
      downloadingPdf,
      spinner
    };
  }
};
</script>
