<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="anchor_search"
            placeholder="Anchor"
            aria-describedby="defaultFormControlHelp"
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
        <div style="height: fit-content" class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
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
            <th>{{ $t('Anchor') }} ({{ $t('Program') }})</th>
            <th class="text-center">{{ $t('Total Dealers') }}</th>
            <th class="text-center">{{ $t('Active Dealers') }}</th>
            <th class="text-center">{{ $t('Passive Dealers') }}</th>
            <th class="text-center">{{ $t('Percentage of Active Dealers') }}</th>
            <th class="text-center">{{ $t('Actions') }}</th>
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
              <a
                :href="'../companies/' + program.anchor.id + '/details'"
                class="text-primary text-decoration-underline"
              >
                {{ program.anchor.name }}
              </a>
              ({{ program.name }})
            </td>
            <td class="text-center">
              {{ program.dealers }}
            </td>
            <td class="text-center">{{ program.active_dealers }}</td>
            <td class="text-center">{{ program.passive_dealers }}</td>
            <td class="text-center">{{ program.active_dealers_percent.toFixed(2) }}</td>
            <td class="text-center">
              <a
                :href="'../reports/' + program.id + '/vendors'"
                class="badge bg-label-danger rounded-pill p-1"
                title="Manage Dealers"
                ><i class="ti ti-eye ti-sm"></i
              ></a>
            </td>
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
  name: 'AnchorwiseDealerReport',
  props: ['bank', 'date_format'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const date_format = props.date_format;
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
            type: 'df-anchorwise-dealer-report'
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

    // watch([anchor_search, per_page], async ([anchor_search, per_page]) => {
    //   await axios.get(base_url+props.bank+'/reports/data', {
    //     params: {
    //       per_page: per_page,
    //       anchor: anchor_search,
    //       type: 'df-anchorwise-dealer-report'
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
            type: 'df-anchorwise-dealer-report',
            per_page: per_page.value,
            anchor: anchor_search.value,
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

    const refresh = async () => {
      anchor_search.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-anchorwise-dealer-report',
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
            anchor: anchor_search.value,
            type: 'df-anchorwise-dealer-report'
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_anchorwise_dealer_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            anchor: anchor_search.value,
            type: 'df-anchorwise-dealer-report'
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_anchorwise_dealer_report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'df-anchorwise-dealer-report'
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
      anchor_search,

      // Pagination
      per_page,

      filter,
      refresh,

      resolveProgramStatus,
      NumFormatter,
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
