<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Search User" class="form-label">{{ $t('Search User') }}</label>
          <input
            type="text"
            class="form-control"
            v-model="search"
            id="defaultFormControlInput"
            placeholder="Name"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Search User" class="form-label">{{ $t('Email') }}</label>
          <input
            type="email"
            class="form-control"
            v-model="email"
            id="defaultFormControlInput"
            placeholder="Email"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Search User" class="form-label">{{ $t('Company') }}</label>
          <input
            type="text"
            class="form-control"
            v-model="company"
            id="defaultFormControlInput"
            placeholder="Company"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Last Login') }})</label>
          <input
            type="text"
            id="date_search"
            class="form-control form-search"
            name="inactive-users-lastlogin-daterange"
            placeholder="Select Dates"
            autocomplete="off"
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
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
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
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Role') }}</th>
            <th>{{ $t('Email') }}</th>
            <th>{{ $t('Company') }}</th>
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
          <tr v-for="data in report_data.data" :key="data.id">
            <td>{{ data.name }}</td>
            <td>{{ data.roles.length > 0 ? data.roles[0].name : '-' }}</td>
            <td>{{ data.email }}</td>
            <td>
              <template v-if="data.mapped_companies.length > 0">
                <span v-for="company in data.mapped_companies" :key="company.id" class="mx-1">{{ company.name }}</span>
              </template>
              <span v-else>-</span>
            </td>
            <td>{{ moment(data.last_login).format(date_format) }}</td>
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
  props: ['bank', 'date_format'],
  setup(props) {
    const date_format = props.date_format;
    const base_url = inject('baseURL');
    const axios = inject('axios');
    const search = ref('');
    const email = ref('');
    const company = ref('');
    const sort_by = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const date_search = ref('');
    const per_page = ref(50);

    const report_data = ref([]);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data?type=inactive-users-report&per_page=' + per_page.value)
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
            type: 'inactive-users-report',
            search: search.value,
            per_page: per_page,
            sort_by: sort_by.value
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'inactive-users-report',
            per_page: per_page.value,
            search: search.value,
            email: email.value,
            company: company.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
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
      email.value = '';
      company.value = '';
      sort_by.value = '';
      from_date.value = '';
      to_date.value = '';
      date_search.value = '';
      $('#date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'inactive-users-report',
            per_page: per_page.value
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'inactive-users-report',
            search: search.value,
            email: email.value,
            company: company.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Inactive_Report_${moment().format('Do_MMM_YYYY')}.csv`);
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'inactive-users-report',
            search: search.value,
            email: email.value,
            company: company.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Inactive_users_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            type: 'inactive-users-report',
            per_page: per_page.value,
            search: search.value,
            email: email.value,
            company: company.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
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
      date_format,
      search,
      email,
      company,
      per_page,
      report_data,
      sort_by,
      from_date,
      to_date,
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
