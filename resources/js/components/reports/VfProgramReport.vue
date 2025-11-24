<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Program" class="form-label">{{ $t('Program Name') }}</label>
          <input
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            v-model="program_name_search"
            placeholder="Program Name"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="anchor_search"
            placeholder="Anchor"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="suspended">{{ $t('Suspended') }}</option>
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
            <option value="100">50</option>
          </select>
        </div>
        <download-buttons
          @download-report="downloadReport"
          @download-pdf="downloadPdf"
          :downloading-excel="downloadingExcel"
          :downloading-pdf="downloadingPdf"
        ></download-buttons>
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
          <tr class="">
            <th>{{ $t('Program Name') }}</th>
            <th>{{ $t('Anchor Name') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Total Program Limit') }}</th>
            <th>{{ $t('Utilized Limit') }}</th>
            <th>{{ $t('Pipeline Requests') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Base Rate Consideration') }}</th>
            <th>{{ $t('Anchor Discount Bearing') }}</th>
            <th>{{ $t('Eligibility') }}</th>
            <th>{{ $t('Total Mapped Companies') }}</th>
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
          <tr class="text-nowrap" v-for="program in programs.data" :key="program.id">
            <td>
              <a :href="'programs/' + program.id + '/details'" class="text-primary text-decoration-underline">
                {{ program.name }}
              </a>
            </td>
            <td class="text-primary text-decoration-underline">
              <a :href="'companies/' + program.anchor.id + '/details'">{{ program.anchor.name }}</a>
            </td>
            <td>
              <span class="badge me-1 m_title" :class="resolveProgramStatus(program.account_status)">{{
                program.account_status
              }}</span>
            </td>
            <td class="text-success">{{ NumFormatter(program.program_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(program.utilized) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(program.pipeline) }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(program.program_limit - program.utilized + program.pipeline) }}
            </td>
            <td>
              <span v-if="program.discount_details.length"> {{ program.discount_details[0].benchmark_rate }}% </span>
              <span v-else>-</span>
            </td>
            <td>
              <span v-if="program.discount_details.length">
                {{ program.discount_details[0].anchor_discount_bearing }}%
              </span>
              <span v-else>-</span>
            </td>
            <td>{{ program.eligibility }}%</td>
            <td>{{ program.vendors_count }}</td>
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
import { useToast } from 'vue-toastification';
import { inject, onMounted, ref, watch } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';

export default {
  name: 'Programs',
  props: ['bank'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    const programs = ref([]);

    // Search fields
    const program_name_search = ref('');
    const anchor_search = ref('');
    const status_search = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getPrograms = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vendor-financing-programs-report'
          }
        })
        .then(response => {
          programs.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPrograms();
    });

    const resolveProgramStatus = status => {
      switch (status) {
        case 'active':
          return 'bg-label-success';
          break;
        case 'pending':
          return 'bg-label-primary';
          break;
        case 'suspended':
          return 'bg-label-danger';
          break;
        default:
          break;
      }
    };

    const NumFormatter = data => {
      return parseFloat(data).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'vendor-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          programs.value = response.data;
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
            type: 'vendor-financing-programs-report',
            per_page: per_page.value,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
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
      program_name_search.value = '';
      anchor_search.value = '';
      status_search.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vendor-financing-programs-report',
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
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'vendor-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_program_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'vendor-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_programs_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'vendor-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          programs.value = response.data;
        });
    };

    return {
      moment,
      programs,
      // Search fields
      program_name_search,
      anchor_search,
      status_search,
      sort_by,

      // Pagination
      per_page,

      filter,
      refresh,

      resolveProgramStatus,
      NumFormatter,
      changePage,
      downloadReport,
      downloadPdf,

      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
