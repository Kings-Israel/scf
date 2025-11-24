<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="vendor_search"
            placeholder="Search Vendors"
            aria-describedby="defaultFormControlHelp"
          />
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
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">50</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary btn-sm" style="height: fit-content" @click="exportData">
          <i class="ti ti-download ti-sm"></i>
        </button>
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
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Unique Identification Number') }}</th>
            <th>{{ $t('Payment OD Account') }}</th>
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
            <td>{{ vendor.company_name }}</td>
            <td>{{ vendor.company_unique_identification_number }}</td>
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
</template>

<script>
import { computed, onMounted, ref, watch, inject } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'Vendors',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const vendors = ref([]);

    const per_page = ref(50);

    const vendor_search = ref('');

    const getVendors = async () => {
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

    watch(per_page, ([per_page]) => {
      axios
        .get(base_url + 'invoices/vendors/data', {
          params: {
            per_page: per_page,
            vendor: vendor_search.value
          }
        })
        .then(response => {
          vendors.value = response.data;
        });
    });

    const exportData = () => {
      axios
        .get(base_url + 'invoices/vendors/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Vendors_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getVendors();
    });

    const changePage = async page => {
      await axios.get(page).then(response => {
        vendors.value = response.data;
      });
    };

    return {
      moment,
      vendors,
      vendor_search,
      per_page,
      changePage,
      filter,
      refresh,
      exportData
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
