<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Company Name" class="form-label">{{ $t('Company Name') }}e</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="name_search"
            id="defaultFormControlInput"
            :placeholder="$t('Company Name')"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="">
          <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <button type="button" class="btn btn-primary btn-sm d-none" style="height: fit-content">
            <i class="ti ti-download ti-xs"></i>
          </button>
        </div>
      </div>
    </div>
    <pagination
      v-if="companies"
      class="mx-2"
      :from="companies.from"
      :to="companies.to"
      :links="companies.links"
      :next_page="companies.next_page_url"
      :prev_page="companies.prev_page_url"
      :total_items="companies.total"
      :first_page_url="companies.first_page_url"
      :last_page_url="companies.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Company Name') }}</th>
            <th>{{ $t('Organization Type') }}</th>
            <th>{{ $t('Branch Code') }}</th>
            <th>{{ $t('Status') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!companies.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="companies.data && companies.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="company in companies.data" :key="company.id">
            <td class="">
              <span>{{ company.name }}</span>
            </td>
            <td class="">{{ company.organization_type }}</td>
            <td>
              <span>{{ company.branch_code }}</span>
            </td>
            <td>
              <span :class="'m_title badge ' + resolveStatus(company.status)">{{ company.status }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="companies"
      class="mx-2"
      :from="companies.from"
      :to="companies.to"
      :links="companies.links"
      :next_page="companies.next_page_url"
      :prev_page="companies.prev_page_url"
      :total_items="companies.total"
      :first_page_url="companies.first_page_url"
      :last_page_url="companies.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { onMounted, ref, watch, inject } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from './partials/Pagination.vue';
import axios from 'axios';
export default {
  name: 'Uploaded Companies',
  props: ['bank'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    // const base_url = process.env.NODE_ENV == 'development' ? '/' : '/bank/'
    const companies = ref([]);
    const bank = ref('');

    // Search fields
    const name_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getCompanies = async () => {
      await axios.get(base_url + props.bank + '/companies/uploaded/data?per_page=' + per_page.value).then(response => {
        companies.value = response.data;
      });
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'successful':
          style = 'bg-label-success';
          break;
        case 'failed':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/companies/uploaded/data', {
          params: {
            per_page: per_page.value,
            search: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      name_search.value = '';
      approval_status_search.value = '';
      branch_code.value = '';
      status_search.value = [];
      type_search.value = '';
      company_type_search.value = [];
      await axios
        .get(base_url + props.bank + '/companies/uploaded/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          companies.value = response.data;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    onMounted(() => {
      getCompanies();
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/companies/uploaded/data', {
          params: {
            per_page: per_page,
            search: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            search: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
        });
    };

    return {
      base_url,
      bank,
      companies,

      // Search fields
      name_search,

      // Pagination
      per_page,

      filter,
      refresh,

      resolveStatus,
      changePage
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
