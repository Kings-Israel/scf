<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Dealer" class="form-label">{{ $t('Dealer') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="Dealer"
            v-model="vendor_search"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="RM" class="form-label">{{ $t('RM') }}</label>
          <input type="text" class="form-control" placeholder="RM" v-model="rm" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Unique Identification Number" class="form-label">{{ $t('Unique ID Number') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="Unique Identification No."
            v-model="unique_id"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Branch Code" class="form-label">{{ $t('Branch Code') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="Branch Code"
            v-model="branch_code"
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
        <select class="form-select mx-1" v-model="per_page" style="height: fit-content">
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
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Dealer') }}</th>
            <th>{{ $t('Branch Code') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Limit Expiry Date') }}</th>
            <th>{{ $t('Limit Utilized') }}</th>
            <th>{{ $t('DPD Days') }}</th>
            <th>{{ $t('RM') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!dealers.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="dealers.data && dealers.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="dealer in dealers.data" :key="dealer.id">
            <td>
              <a :href="'../companies/' + dealer.id + '/details'" class="text-primary text-decoration-underline">
                {{ dealer.name }}
              </a>
            </td>
            <td>
              {{ dealer.branch_code }}
            </td>
            <td class="text-success">
              {{ dealer.bank.default_currency }}.{{ new Intl.NumberFormat().format(dealer.sanctioned_limit) }}
            </td>
            <td>{{ moment(dealer.limit_expiry_date).format('DD MMM YYYY') }}</td>
            <td class="text-success">
              {{ dealer.bank.default_currency }}.{{ new Intl.NumberFormat().format(dealer.utilized_limit) }}
            </td>
            <td>{{ dealer.dpd_days }}</td>
            <td>{{ dealer.relationship_managers.length > 0 ? dealer.relationship_managers[0].name : '' }}</td>
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
  name: 'DfDealerClassificationReport',
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
    const vendor_search = ref('');
    const unique_id = ref('');
    const rm = ref('');
    const branch_code = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getReport = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-vendor-classification-report'
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

    const filter = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vf-vendor-classification-report',
            per_page: per_page.value,
            vendor: vendor_search.value,
            unique_id: unique_id.value,
            branch_code: branch_code.value,
            rm: rm.value,
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
      vendor_search.value = '';
      unique_id.value = '';
      branch_code.value = '';
      rm.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'vf-vendor-classification-report',
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
            vendor: vendor_search.value,
            unique_id: unique_id.value,
            branch_code: branch_code.value,
            rm: rm.value,
            type: 'vf-vendor-classification-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_dealer_classification_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            vendor: vendor_search.value,
            unique_id: unique_id.value,
            branch_code: branch_code.value,
            rm: rm.value,
            type: 'vf-vendor-classification-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_vendor_classification_report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'vf-vendor-classification-report',
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
      vendor_search,
      unique_id,
      branch_code,
      rm,
      sort_by,

      // Pagination
      per_page,

      refresh,
      filter,

      changePage,
      downloadReport,
      downloadPdf,

      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
