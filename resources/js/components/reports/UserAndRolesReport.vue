<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="User Type" class="form-label">{{ $t('User Type') }}</label>
          <select class="form-select" id="exampleFormControlSelect1" v-model="user_type">
            <option value="">{{ $t('Select User Type') }}</option>
            <option value="bank">{{ $t('Bank Users') }}</option>
            <option value="company">{{ $t('Company Users') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Company" class="form-label">{{ $t('Company') }}</label>
          <input type="text" class="form-control" placeholder="Company" v-model="company" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Search" class="form-label">{{ $t('Search') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="User/Email/Mobile"
            v-model="search"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Status') }}</label>
          <select class="form-select" id="exampleFormControlSelect1" v-model="status">
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
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
            <th>{{ $t('User Type') }}</th>
            <th>{{ $t('Company') }}</th>
            <th>{{ $t('User') }}</th>
            <th>{{ $t('Email') }}</th>
            <th>{{ $t('Role') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Last Login') }}</th>
            <th>{{ $t('Last Login IP') }}</th>
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
          <tr v-for="report in report_data.data" :key="report.id">
            <td>
              <span v-if="report.mapped_companies.length > 0">{{ $t('Company') }}</span>
              <span v-else>{{ $t('Bank') }}</span>
            </td>
            <td>
              <template v-if="report.mapped_companies.length > 0">
                <span v-for="company in report.mapped_companies" :key="company.id">
                  <a :href="'../companies/' + company.id + '/details'">{{ company.name + ', ' }}</a>
                </span>
              </template>
            </td>
            <td>{{ report.name }}</td>
            <td>{{ report.email }}</td>
            <td>
              <template v-for="role in report.roles" :key="role.id">
                <span class="btn btn-xs btn-label-warning mx-1">
                  {{ role.name }}
                </span>
              </template>
            </td>
            <td>
              <span class="badge bg-label-success m_title" v-if="report.is_active">{{ $t('Active') }}</span>
              <span class="badge bg-label-danger m_title" v-else>{{ $t('Inactive') }}</span>
            </td>
            <td>{{ report.last_login ? moment(report.last_login).format('DD MMM YYYY') : '-' }}</td>
            <td>{{ report.activity ? (report.activity.properties ? report.activity.properties.ip : '-') : '-' }}</td>
            <td></td>
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
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import moment from 'moment';

export default {
  name: 'AllPayments',
  components: {
    Pagination,
    DownloadButtons
  },
  props: ['bank'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const user_type = ref('');
    const company = ref('');
    const status = ref('');
    const search = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'users-and-roles-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    // watch([anchor, vendor, per_page], async ([anchor, vendor, per_page]) => {
    //   await axios.get(base_url+props.bank+'/reports/data', {
    //     params: {
    //       type: 'users-and-roles-report',
    //       per_page: per_page,
    //       anchor: anchor,
    //       vendor: vendor,
    //     }
    //   })
    //     .then(response => {
    //       report_data.value = response.data
    //     })
    //     .catch(err => {
    //       console.log(err)
    //     })
    // })

    const filter = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'users-and-roles-report',
            per_page: per_page.value,
            user_type: user_type.value,
            company: company.value,
            status: status.value,
            search: search.value,
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
      user_type.value = '';
      (status.value = ''), (search.value = '');
      company.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'users-and-roles-report',
            per_page: per_page
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
            type: 'users-and-roles-report',
            user_type: user_type.value,
            company: company.value,
            status: status.value,
            search: search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Users_and_roles_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'users-and-roles-report',
            user_type: user_type.value,
            company: company.value,
            status: status.value,
            search: search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `User_and_roles_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'users-and-roles-report',
            per_page: per_page.value,
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

    return {
      moment,
      user_type,
      company,
      status,
      search,
      sort_by,

      filter,
      refresh,

      per_page,

      report_data,

      changePage,
      downloadReport,
      downloadPdf,

      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
<style>
.m_title::first-letter {
  text-transform: capitalize;
}
</style>
