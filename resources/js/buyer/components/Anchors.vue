<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              v-model="vendor_search"
              placeholder="Search Anchors"
              aria-describedby="defaultFormControlHelp"
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
            <select class="form-select" id="exampleFormControlSelect1">
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
              <th>{{ $t('Anchor') }}</th>
              <th>{{ $t('My Payment OD Account') }}</th>
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
              <td>{{ vendor.anchor_name }}</td>
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
  name: 'Anchors',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const vendors = ref([]);
    const vendor_search = ref('');
    const per_page = ref(50);

    const getVendors = async () => {
      await axios.get(base_url + 'invoices/anchors/data').then(response => {
        vendors.value = response.data;
      });
    };

    onMounted(() => {
      getVendors();
    });

    const filter = async () => {
      await axios
        .get(base_url + 'invoices/vendors/data', {
          params: {
            per_page: per_page.value,
            vendor: vendor_search.value
          }
        })
        .then(response => {
          vendors.value = response.data;
        });
    };

    const refresh = async () => {
      vendor_search.value = '';
      await axios
        .get(base_url + 'invoices/vendors/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          vendors.value = response.data;
        });
    };

    const changePage = async page => {
      await axios.get(page).then(response => {
        vendors.value = response.data;
      });
    };

    return {
      moment,
      vendors,
      filter,
      refresh,
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
