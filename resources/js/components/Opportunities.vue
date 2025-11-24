<template>
  <div class="card" id="companies">
    <div class="p-2 d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Company Name" class="form-label">{{ $t('Company Name') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="name_search"
            :placeholder="$t('Company Name')"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Product Type" class="form-label">{{ $t('Product Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type_search">
            <option value="">{{ $t('All') }}</option>
            <option value="Vendor Financing">{{ $t('Vendor Financing') }}</option>
            <option value="Dealer Financing">{{ $t('Dealer Financing') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
    <pagination
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
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Department') }}</th>
            <th>{{ $t('Product') }}</th>
            <th>{{ $t('Email') }}</th>
            <th class="d-flex justify-content-center">{{ $t('Actions') }}</th>
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
            <td class="text-primary text-decoration-underline">
              <a v-if="company.can_view" :href="'companies/pending/' + company.id + '/details'">
                {{ company.company }}
              </a>
              <span v-else>{{ company.company }}</span>
            </td>
            <td class="">{{ company.department }}</td>
            <td>
              <span class="me-1">{{ company.product }}</span>
            </td>
            <td>
              <span class="me-1">{{ company.email }}</span>
            </td>
            <td class="d-flex justify-content-center">
              <a v-if="company.can_view" :href="'companies/pending/' + company.id + '/details'">
                <i class="ti ti-eye ti-sm text-primary"></i>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
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
import { computed, inject, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import Pagination from './partials/Pagination.vue';

export default {
  name: 'Opportinities',
  props: ['bank', 'can_create'],
  components: {
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const companies = ref([]);
    const can_create = props.can_create == '1' ? ref(true) : ref(false);

    // Search fields
    const name_search = ref('');
    const department_search = ref('');
    const product_type_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getCompanies = async () => {
      await axios.get('companies/opportunities/data').then(response => {
        companies.value = response.data.data.pending_companies;
      });
    };

    watch([per_page], ([new_per_page]) => {
      axios
        .get(base_url + props.bank + '/companies/opportunities/data', {
          params: {
            per_page: new_per_page,
            name: name_search.value,
            deparment: department_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.pending_companies;
        });
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/companies/opportunities/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            deparment: department_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.pending_companies;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      name_search.value = '';
      department_search.value = '';
      product_type_search.value = '';
      axios
        .get(base_url + props.bank + '/companies/opportunities/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          companies.value = response.data.data.pending_companies;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    onMounted(() => {
      getCompanies();
    });

    const changePage = async page => {
      await axios.get(page + '&per_page=' + per_page.value).then(response => {
        companies.value = response.data.data.pending_companies;
      });
    };

    return {
      companies,
      can_create,

      // Search fields
      name_search,
      department_search,
      product_type_search,

      filter,
      refresh,

      // Pagination
      per_page,

      changePage
    };
  }
};
</script>
