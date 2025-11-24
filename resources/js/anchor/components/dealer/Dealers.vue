<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Search" class="form-label">{{ $t('Dealers') }}</label>
            <input
              type="text"
              class="form-control form-search"
              v-model="vendors_search"
              placeholder="Search Dealers/Buyers"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="mx-1 table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="mx-1 table-clear-btn">
            <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex">
          <div>
            <select class="form-select" v-model="per_page">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
            </select>
          </div>
        </div>
      </div>
      <pagination
        v-if="vendors.meta"
        :from="vendors.meta.from"
        :to="vendors.meta.to"
        :links="vendors.meta.links"
        :next_page="vendors.links.next"
        :prev_page="vendors.links.prev"
        :total_items="vendors.meta.total"
        :first_page_url="vendors.links.first"
        :last_page_url="vendors.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive p-2">
        <table class="table">
          <thead>
            <tr>
              <th>{{ $t('Dealer') }}</th>
              <th>{{ $t('Payment/Loan OD Account') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!vendors.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="vendors.data && vendors.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="vendor in vendors.data" :key="vendor.id">
              <td>
                <span v-if="vendor.buyer_name">{{ vendor.buyer_name }}</span>
                <span v-else>{{ vendor.company_name }}</span>
              </td>
              <td class="">{{ vendor.payment_account_number }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="vendors.meta"
        :from="vendors.meta.from"
        :to="vendors.meta.to"
        :links="vendors.meta.links"
        :next_page="vendors.links.next"
        :prev_page="vendors.links.prev"
        :total_items="vendors.meta.total"
        :first_page_url="vendors.links.first"
        :last_page_url="vendors.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'Dealers',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const vendors = ref([]);
    const vendors_search = ref('');
    const per_page = ref(50);

    const getVendors = async () => {
      await axios
        .get(base_url + 'dealer/invoices/dealers/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          vendors.value = response.data.data;
        });
    };

    onMounted(() => {
      getVendors();
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'dealer/invoices/dealers/data', {
          params: {
            vendors: vendors_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          vendors.value = response.data.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      vendors_search.value = '';
      axios
        .get(base_url + 'dealer/invoices/dealers/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          vendors.value = response.data.data;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'dealer/invoices/dealers/data', {
          params: {
            vendors: vendors_search.value,
            per_page: per_page
          }
        })
        .then(response => {
          vendors.value = response.data.data;
        });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          vendors.value = response.data.data;
        });
    };

    return {
      moment,
      vendors,
      changePage,
      per_page,
      vendors_search,
      filter,
      refresh
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
