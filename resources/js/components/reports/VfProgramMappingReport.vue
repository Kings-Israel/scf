<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Status" class="form-label">{{ $t('Program Type') }}</label>
          <select class="form-select form-search" v-model="program_type_search">
            <option value="">{{ $t('Select Program Type') }}</option>
            <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
            <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
            <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Vendor"
            v-model="vendor_search"
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
            v-model="program_search"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Limit Expiry Date') }}</label>
          <input
            class="form-control form-search"
            type="text"
            id="limit_expiry_date_search"
            name="vf-program-mapping-report-limit-expiry-daterange"
            v-on:keyup.enter="filter"
          />
        </div>
        <!-- <div class="">
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
        </div> -->
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
      <div class="d-flex justify-contend-md-end gap-1 mt-2 mt-md-auto">
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
    <pagination
      v-if="dealers.meta"
      class="mx-2"
      :from="dealers.meta.from"
      :to="dealers.meta.to"
      :links="dealers.meta.links"
      :next_page="dealers.links.next"
      :prev_page="dealers.links.prev"
      :total_items="dealers.meta.total"
      :first_page_url="dealers.links.first"
      :last_page_url="dealers.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Program') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('OD Account') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Utilized Limit') }}</th>
            <th>{{ $t('Pipeline Limit') }}</th>
            <th>{{ $t('Limit Expiry Date') }}</th>
            <th>{{ $t('Margin Rate') }} (%)</th>
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
                :href="'../companies/' + dealer.buyer_id ? dealer.anchor_id : dealer.company_id + '/details'"
                class="text-primary text-decoration-underline"
              >
                {{ dealer.buyer_name ? dealer.anchor_name : dealer.company_name }}
              </a>
            </td>
            <td>
              <a :href="'../programs/' + dealer.program_id + '/details'" class="text-primary text-decoration-underline">
                {{ dealer.program_name }}
              </a>
            </td>
            <td>
              <a
                :href="'../companies/' + dealer.buyer_id ? dealer.buyer_id : dealer.anchor_id + '/details'"
                class="text-primary text-decoration-underline"
              >
                {{ dealer.buyer_name ? dealer.buyer_name : dealer.anchor_name }}
              </a>
            </td>
            <td>
              {{ dealer.payment_account_number }}
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.sanctioned_limit) }}</td>
            <td class="text-success">
              {{
                new Intl.NumberFormat().format(
                  dealer.sanctioned_limit - dealer.utilized_amount - dealer.pipeline_amount
                )
              }}
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.utilized_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(dealer.pipeline_amount) }}</td>
            <td>{{ moment(dealer.expiry_date).format(date_format) }}</td>
            <td>{{ dealer.total_roi }}</td>
            <td>
              {{ dealer.benchmark_title != null ? dealer.benchmark_rate + ' (' + dealer.benchmark_rate + '%)' : '0%' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="dealers.meta"
      class="mx-2"
      :from="dealers.meta.from"
      :to="dealers.meta.to"
      :links="dealers.meta.links"
      :next_page="dealers.links.next"
      :prev_page="dealers.links.prev"
      :total_items="dealers.meta.total"
      :first_page_url="dealers.links.first"
      :last_page_url="dealers.links.last"
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
    const program_search = ref('');
    const anchor_search = ref('');
    const vendor_search = ref('');
    const sort_by = ref('');
    const od_expiry_date_from = ref('');
    const od_expiry_date_to = ref('');
    const limit_expiry_date_from = ref('');
    const limit_expiry_date_to = ref('');
    const program_type_search = ref('');

    const limit_expiry_date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getReport = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-program-mapping-report'
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
            vendor: vendor_search.value,
            program: program_search.value,
            per_page: per_page.value,
            type: 'vf-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value,
            program_type: program_type_search.value
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
      limit_expiry_date_search.value = $('#limit_expiry_date_search').val();
      if (limit_expiry_date_search.value) {
        limit_expiry_date_from.value = moment(limit_expiry_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        limit_expiry_date_to.value = moment(limit_expiry_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            anchor: anchor_search.value,
            vendor: vendor_search.value,
            program: program_search.value,
            per_page: per_page.value,
            type: 'vf-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value,
            program_type: program_type_search.value
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
      vendor_search.value = '';
      program_search.value = '';
      sort_by.value = '';
      od_expiry_date_from.value = '';
      od_expiry_date_to.value = '';
      limit_expiry_date_from.value = '';
      limit_expiry_date_to.value = '';
      program_type_search.value = '';
      $('#limit_expiry_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'vf-program-mapping-report'
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
            vendor: vendor_search.value,
            program: program_search.value,
            type: 'vf-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value,
            program_type: program_type_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_program_mapping_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            vendor: vendor_search.value,
            program: program_search.value,
            type: 'vf-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value,
            program_type: program_type_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `VF_program_mapping_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            vendor: vendor_search.value,
            program: program_search.value,
            type: 'vf-program-mapping-report',
            sort_by: sort_by.value,
            od_expiry_date_from: od_expiry_date_from.value,
            od_expiry_date_to: od_expiry_date_to.value,
            limit_expiry_date_from: limit_expiry_date_from.value,
            limit_expiry_date_to: limit_expiry_date_to.value,
            program_type: program_type_search.value
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
      vendor_search,
      program_search,
      sort_by,
      od_expiry_date_from,
      od_expiry_date_to,
      limit_expiry_date_from,
      limit_expiry_date_to,
      program_type_search,

      filter,
      refresh,

      // Pagination
      per_page,

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
