<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap">
        <div class="">
          <label for="Organization Name" class="form-label">Organization Name</label>
          <input type="text" class="form-control" placeholder="Organization Name" />
        </div>
        <div class="mx-1">
          <label for="Status" class="form-label">Sort By</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="sort_by">
            <option value="">Sort By</option>
            <option value="ASC">Ascending</option>
            <option value="DESC">Descending</option>
          </select>
        </div>
        <div class="mx-1 table-search-btn">
          <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mx-1 table-clear-btn">
          <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end">
        <select class="form-select mx-1" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
          <option value="5">5</option>
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">50</option>
        </select>
        <download-buttons @download-report="downloadReport" @download-pdf="downloadPdf"></download-buttons>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>Organization Name</th>
            <th>Sanctioned Limit</th>
            <th>Utilized Amount</th>
            <th>Pipeline Requests</th>
            <th>Available Limit</th>
            <th>Limit Utilized</th>
            <th>Product</th>
            <!-- <th>Actions</th> -->
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!dealers.data">
            <td colspan="12" class="text-center">
              <span class="text-center">Loading Data...</span>
            </td>
          </tr>
          <tr v-if="dealers.data && dealers.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">No Data Available...</span>
            </td>
          </tr>
          <tr v-for="dealer in dealers.data" :key="dealer.id">
            <td>
              <a :href="'../companies/' + dealer.id + '/details'" class="text-primary text-decoration-underline">
                {{ dealer.name }}
              </a>
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.top_level_borrower_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.utilized_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.pipeline_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.available_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.utilized_percentage) }}</td>
            <td>Dealer Financing</td>
            <!-- <td></td> -->
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      class="mx-2"
      :from="dealers.from"
      :to="dealers.to"
      :links="dealers.links"
      :next_page="dealers.next_page_url"
      :prev_page="dealers.prev_page_url"
      :total_items="dealers.total"
      :first_page_url="dealers.first_page_url"
      :last_page_url="dealers.last_page_url"
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
  name: 'DistributorLimitUtilizationReport',
  props: ['bank'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    const dealers = ref([]);

    // Search fields
    const program_name_search = ref('');
    const anchor_search = ref('');
    const status_search = ref('');
    const type_search = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getReport = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'distributor-limit-utilization-report'
          }
        })
        .then(response => {
          dealers.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getReport();
    });

    // watch([program_name_search, anchor_search, status_search, per_page], async ([program_name_search, anchor_search, status_search, per_page]) => {
    //   await axios.get(base_url+props.bank+'/reports/data', {
    //     params: {
    //       per_page: per_page,
    //       anchor: anchor_search,
    //       type: 'distributor-limit-utilization-report'
    //     }
    //   })
    //     .then(response => {
    //       dealers.value = response.data
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
            type: 'distributor-limit-utilization-report',
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
            per_page: per_page.value,
            type: 'distributor-limit-utilization-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const downloadReport = () => {
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor_search.value,
            type: 'distributor-limit-utilization-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `Distributor_limit_utilization_report_${moment().format('Do_MMM_YYYY')}.csv`
          );
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const downloadPdf = () => {
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor_search.value,
            type: 'distributor-limit-utilization-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `Distributor_limit_utilization_Report_${moment().format('Do_MMM_YYYY')}.pdf`
          );
          document.body.appendChild(fileLink);

          fileLink.click();
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
            type: 'distributor-limit-utilization-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          dealers.value = response.data;
        });
    };

    return {
      moment,
      dealers,
      // Search fields
      anchor_search,
      sort_by,

      // Pagination
      per_page,

      filter,
      refresh,

      changePage,
      downloadReport,
      downloadPdf
    };
  }
};
</script>
