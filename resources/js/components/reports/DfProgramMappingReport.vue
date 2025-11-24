<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Dealer" class="form-label">{{ $t('Dealer') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Dealer"
            v-model="dealer_search"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Anchor"
            v-model="anchor_search"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Program" class="form-label">{{ $t('Program') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Program"
            v-model="program_name_search"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Invoice Ref" class="form-label">{{ $t('From') }} ({{ $t('OD Expiry Date') }}).</label>
          <input type="date" class="form-control form-search" v-model="od_expiry_date_from" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Invoice Ref" class="form-label">{{ $t('To') }} ({{ $t('OD Expiry Date') }}).</label>
          <input type="date" class="form-control form-search" v-model="od_expiry_date_to" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Invoice Ref" class="form-label">{{ $t('From') }} ({{ $t('Limit Expiry Date') }}).</label>
          <input
            type="date"
            class="form-control form-search"
            v-model="limit_expiry_date_from"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Invoice Ref" class="form-label">{{ $t('To') }} ({{ $t('Limit Expiry Date') }}).</label>
          <input
            type="date"
            class="form-control form-search"
            v-model="limit_expiry_date_to"
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
        <select class="form-select" v-model="per_page" style="width: 5rem; height: fit-content">
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
            <th>{{ $t('Program') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('OD Account') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('OD Expiry Date') }}</th>
            <th>{{ $t('Limit Expiry Date') }}</th>
            <th>{{ $t('Tenor & Rates') }} (%)</th>
            <th>{{ $t('Base Rate Consideration') }}</th>
          </tr>
        </thead>
        <tbody class="text-nowrap">
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
              <a
                :href="'../companies/' + dealer.company.id + '/details'"
                class="text-primary text-decoration-underline"
              >
                {{ dealer.company.name }}
              </a>
            </td>
            <td>
              <a :href="'../programs/' + dealer.program.id + '/details'" class="text-primary text-decoration-underline">
                {{ dealer.program.name }}
              </a>
            </td>
            <td>
              <a
                :href="'../companies/' + dealer.program.anchor.id + '/details'"
                class="text-primary text-decoration-underline"
              >
                {{ dealer.program.anchor.name }}
              </a>
            </td>
            <td>
              {{ dealer.payment_account_number }}
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.sanctioned_limit) }}</td>
            <td>{{ moment(dealer.limit_expiry_date).format(date_format) }}</td>
            <td>{{ moment(dealer.program.limit_expiry_date).format(date_format) }}</td>
            <td>
              {{ dealer.discount[0].from_day }} - {{ dealer.discount[0].to_day }} Days =
              {{ dealer.discount[0].total_roi }}
            </td>
            <td>
              {{
                dealer.discount[0].benchmark_title != null
                  ? dealer.discount[0].benchmark_title + ' (' + dealer.discount[0].benchmark_rate + '%)'
                  : ''
              }}
            </td>
            <td></td>
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
  props: ['bank', 'date_format'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const date_format = props.date_format;
    const base_url = inject('baseURL');
    const toast = useToast();
    const dealers = ref([]);

    // Search fields
    const program_name_search = ref('');
    const anchor_search = ref('');
    const dealer_search = ref('');
    const sort_by = ref('');
    const od_expiry_date_from = ref('');
    const od_expiry_date_to = ref('');
    const limit_expiry_date_from = ref('');
    const limit_expiry_date_to = ref('');

    // Pagination
    const per_page = ref(50);

    const getReport = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-program-mapping-report'
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page,
            anchor: anchor_search.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value,
            type: 'df-program-mapping-report'
          }
        })
        .then(response => {
          dealers.value = response.data;
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
            per_page: per_page.value,
            anchor: anchor_search.value,
            program_name: program_name_search.value,
            dealer: dealer_search.value,
            type: 'df-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value
          }
        })
        .then(response => {
          dealers.value = response.data;
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
      anchor_search.value = '';
      program_name_search.value = '';
      dealer_search.value = '';
      sort_by.value = '';
      od_expiry_date_from.value = '';
      od_expiry_date_to.value = '';
      limit_expiry_date_from.value = '';
      limit_expiry_date_to.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-program-mapping-report'
          }
        })
        .then(response => {
          dealers.value = response.data;
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
            anchor: anchor_search.value,
            program_name: program_name_search.value,
            dealer: dealer_search.value,
            type: 'df-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_program_mapping_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            program_name: program_name_search.value,
            dealer: dealer_search.value,
            type: 'df-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `All_payments_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            anchor: anchor_search.value,
            program_name: program_name_search.value,
            dealer: dealer_search.value,
            type: 'df-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value
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
      program_name_search,
      dealer_search,
      sort_by,
      od_expiry_date_from,
      od_expiry_date_to,
      limit_expiry_date_from,
      limit_expiry_date_to,

      // Pagination
      per_page,

      filter,
      refresh,

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
