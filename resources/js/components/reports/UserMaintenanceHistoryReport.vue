<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="User Type" class="form-label">{{ $t('User Type') }}</label>
          <select class="form-select" id="exampleFormControlSelect1" v-model="user_type">
            <option value="bank">{{ $t('Bank') }}</option>
            <option value="company">{{ $t('Company') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Company" class="form-label">{{ $t('Company') }}</label>
          <input
            type="text"
            class="form-control"
            v-model="company_search"
            id="defaultFormControlInput"
            placeholder="Company"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Search" class="form-label">{{ $t('Search') }}</label>
          <input
            type="text"
            class="form-control"
            v-model="search"
            id="defaultFormControlInput"
            placeholder="Name/Email/Phone Number"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Last Login" class="form-label">{{ $t('Last Login') }}</label>
          <input
            type="date"
            class="form-control"
            v-model="last_login"
            id="defaultFormControlInput"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select" id="exampleFormControlSelect1" v-model="status">
            <option value="">{{ $t('All') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex gap-1 justify-content-md-end mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <download-buttons
            @download-report="downloadReport"
            @download-pdf="downloadPdf"
            :downloading-excel="downloadingExcel"
            :downloading-pdf="downloadingPdf"
          ></download-buttons>
        </div>
      </div>
    </div>
    <pagination
      :from="report_data.from"
      :to="report_data.to"
      :links="report_data.links"
      :next_page="report_data.next_page_url"
      :prev_page="report_data.prev_page_url"
      :total_items="report_data.total"
      :first_page_url="report_data.first_page_url"
      :last_page_url="report_data.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Type') }}</th>
            <th>{{ $t('Company') }}</th>
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Email') }}</th>
            <th>{{ $t('Phone Number') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Last Updated At') }}</th>
            <th>{{ $t('Last Login') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!report_data.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="report_data.data && report_data.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="data in report_data.data" :key="data.id" class="text-nowrap">
            <td>{{ data.company ? 'Company' : 'Bank' }}</td>
            <td>{{ data.company ? data.company.name : '-' }}</td>
            <td>{{ data.user.name }}</td>
            <td>{{ data.user.email }}</td>
            <td>{{ data.user.phone_number }}</td>
            <td>{{ data.active ? 'Active' : 'Inactive' }}</td>
            <td>{{ moment(data.user.updated_at).format('DD MMM YYYY') }}</td>
            <td>{{ moment(data.user.last_login).format('DD MMM YYYY') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="report_data.from"
      :to="report_data.to"
      :links="report_data.links"
      :next_page="report_data.next_page_url"
      :prev_page="report_data.prev_page_url"
      :total_items="report_data.total"
      :first_page_url="report_data.first_page_url"
      :last_page_url="report_data.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import moment from 'moment';
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';

export default {
  name: 'InactiveUsers',
  components: {
    Pagination,
    DownloadButtons
  },
  props: ['bank'],
  setup(props) {
    const base_url = inject('baseURL');
    const axios = inject('axios');
    const search = ref('');
    const user_type = ref('bank');
    const company_search = ref('');
    const last_login = ref('');
    const status = ref('');
    const per_page = ref(50);

    const report_data = ref([]);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            user_type: user_type.value,
            type: 'user-maintenance-history-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'user-maintenance-history-report',
            per_page: per_page,
            search: search.value,
            user_type: user_type.value,
            company: company_search.value,
            last_login: last_login.value,
            status: status.value
          }
        })
        .then(response => {
          report_data.value = response.data;
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
            type: 'user-maintenance-history-report',
            per_page: per_page.value,
            search: search.value,
            user_type: user_type.value,
            company: company_search.value,
            last_login: last_login.value,
            status: status.value
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
      search.value = '';
      user_type.value = 'bank';
      last_login.value = '';
      status.value = '';
      company_search.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'user-maintenance-history-report',
            per_page: per_page.value,
            user_type: user_type.value
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
            type: 'user-maintenance-history-report',
            search: search.value,
            user_type: user_type.value,
            company: company_search.value,
            last_login: last_login.value,
            status: status.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `User_maintenance_history_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'user-maintenance-history-report',
            search: search.value,
            user_type: user_type.value,
            company: company_search.value,
            last_login: last_login.value,
            status: status.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `User_maintenance_history_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    onMounted(() => {
      getData();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'user-maintenance-history-report',
            per_page: per_page.value,
            search: search.value,
            user_type: user_type.value,
            company: company_search.value,
            last_login: last_login.value,
            status: status.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      search,
      company_search,
      status,
      user_type,
      last_login,
      per_page,
      report_data,
      filter,
      refresh,
      changePage,
      downloadReport,
      downloadPdf,

      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
