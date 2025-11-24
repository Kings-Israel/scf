use FontLib\Table\Type\name;
<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Program" class="form-label">{{ $t('Program') }}</label>
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
            class="form-control"
            id="defaultFormControlInput"
            v-model="anchor_search"
            placeholder="Anchor"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select" id="exampleFormControlSelect" v-model="status_search">
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
            <th>{{ $t('Tenor & Rates') }}</th>
            <th>{{ $t('Base Rate Consideration') }}</th>
            <th>{{ $t('Total Mapped Dealers') }}</th>
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
            <td class="text-success">{{ program.bank.default_currency }} {{ NumFormatter(program.program_limit) }}</td>
            <td class="text-success">
              {{ program.bank.default_currency }} {{ new Intl.NumberFormat().format(program.utilized_amount) }}
            </td>
            <td class="text-success">
              {{ program.bank.default_currency }} {{ new Intl.NumberFormat().format(program.pipeline_amount) }}
            </td>
            <td>
              <span
                v-for="dealer_discount_rates in program.dealer_discount_rates"
                :key="dealer_discount_rates.id"
                class="mx-1"
              >
                {{ dealer_discount_rates.from_day }} - {{ dealer_discount_rates.to_day }} :
                {{ dealer_discount_rates.total_roi }},
              </span>
            </td>
            <td>{{ program.discount_details[0].benchmark_rate }}%</td>
            <td>{{ program.dealers_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      class="mx-2"
      :from="programs.from"
      :to="programs.to"
      :links="programs.links"
      :next_page="programs.next_page_url"
      :prev_page="programs.prev_page_url"
      :total_items="programs.total"
      :first_page_url="programs.first_page_url"
      :last_page_url="programs.last_page_url"
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
    const type_search = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getPrograms = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'dealer-financing-programs-report'
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

    // watch([program_name_search, anchor_search, status_search, per_page], async ([program_name_search, anchor_search, status_search, per_page]) => {
    //   await axios.get(base_url+props.bank+'/reports/data', {
    //     params: {
    //       per_page: per_page,
    //       name: program_name_search,
    //       anchor: anchor_search,
    //       status: status_search,
    //       type: 'dealer-financing-programs-report'
    //     }
    //   })
    //     .then(response => {
    //       programs.value = response.data
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
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'dealer-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          programs.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const refresh = async () => {
      program_name_search.value = '';
      anchor_search.value = '';
      status_search.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'dealer-financing-programs-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data;
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
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'dealer-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_programs_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'dealer-financing-programs-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Dealer_financing_programs_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'dealer-financing-programs-report',
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
