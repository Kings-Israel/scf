<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Program Code" class="form-label">{{ $t('Loan/OD Account') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Loan/OD Account No."
            v-model="loan_od_account_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Dealer" class="form-label">{{ $t('Buyer') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Buyer"
            aria-describedby="defaultFormControlHelp"
            v-model="buyer_search"
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
      <div class="d-flex gap-1 align-items-end mt-auto">
        <select class="form-select mx-2" v-model="per_page" style="width: 5rem">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button type="button" @click="exportReport" class="btn btn-primary" title="Download Excel">
          <i class="ti ti-download ti-sm"></i> {{ $t('Excel') }}
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary" title="Download PDF">
          <i class="ti ti-download ti-sm"></i> {{ $t('PDF') }}
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
            <th>{{ $t('Loan Account No') }}</th>
            <th>{{ $t('Buyer') }}</th>
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
            <td class="">{{ program.payment_account_number }}</td>
            <td>
              <span class="me-1">{{ program.company_name }}</span>
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
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'ProgramReport',
  components: {
    Pagination
  },
  setup(props, context) {
    const programs = ref([]);
    const per_page = ref(50);
    const base_url = inject('baseURL');
    const loan_od_account_search = ref('');
    const buyer_search = ref('');

    const getPrograms = () => {
      axios
        .get(base_url + 'dealer/reports/dealer-programs-report', {
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
        .get(base_url + 'dealer/reports/dealer-programs-report', {
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
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'dealer/reports/dealer-programs-report', {
          params: {
            per_page: per_page.value,
            loan_account: loan_od_account_search.value,
            buyer: buyer_search.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      loan_od_account_search.value = '';
      buyer_search.value = '';
      axios
        .get(base_url + 'dealer/reports/dealer-programs-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const exportReport = () => {
      axios
        .get(base_url + 'dealer/reports/report/factoring-dealer-program-report/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            loan_account: loan_od_account_search.value,
            buyer: buyer_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Dealer_Programs_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportPdfReport = () => {
      axios
        .get(base_url + 'reports/dealer-dealer-program-report/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET'
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Dealer_Programs_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            loan_account: loan_od_account_search.value,
            buyer: buyer_search.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        });
    };

    return {
      moment,
      programs,
      per_page,
      loan_od_account_search,
      buyer_search,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh
    };
  }
};
</script>
