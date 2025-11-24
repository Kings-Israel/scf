<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control"
            v-model="anchor_search"
            placeholder="Anchor"
            aria-describedby="defaultFormControlHelp"
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
            <th>{{ $t('Anchor') }} ({{ $t('Program') }})</th>
            <th class="text-center">{{ $t('Mapped Companies') }}</th>
            <th class="text-center">{{ $t('Active Mapped Companies') }}</th>
            <th class="text-center">{{ $t('Passive Mapped Companies') }}</th>
            <th class="text-center">{{ $t('Active Mapped Companies(%)') }}</th>
            <th>{{ $t('Actions') }}</th>
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
              {{ program.vendors_count }}
            </td>
            <td class="text-center">{{ program.active_vendors }}</td>
            <td class="text-center">{{ program.passive_vendors }}</td>
            <td class="text-center">{{ program.active_vendors_percentage.toFixed(2) }}</td>
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
  name: 'AnchorwiseDealerReport',
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

    // Pagination
    const per_page = ref(50);

    const getPrograms = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-anchorwise-vendor-report'
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
    //       type: 'vf-anchorwise-vendor-report'
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
            anchor: anchor_search.value,
            type: 'vf-anchorwise-vendor-report'
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
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-anchorwise-vendor-report'
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
            type: 'vf-anchorwise-vendor-report'
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_anchorwise_vendor_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'vf-anchorwise-vendor-report'
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_anchorwise_vendor_report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'vf-anchorwise-vendor-report'
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

      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
