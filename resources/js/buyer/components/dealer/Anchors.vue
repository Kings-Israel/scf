<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Search" class="form-label">{{ $t('Anchors') }}</label>
            <input
              type="text"
              class="form-control form-search"
              v-model="anchors_search"
              placeholder="Search Anchors"
              aria-describedby="defaultFormControlHelp"
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
        v-if="od_accounts.meta"
        :from="od_accounts.meta.from"
        :to="od_accounts.meta.to"
        :links="od_accounts.meta.links"
        :next_page="od_accounts.links.next"
        :prev_page="od_accounts.links.prev"
        :total_items="od_accounts.meta.total"
        :first_page_url="od_accounts.links.first"
        :last_page_url="od_accounts.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive p-2">
        <table class="table">
          <thead>
            <tr>
              <th>{{ $t('Anchor') }}</th>
              <th>{{ $t('Unique Identification Number') }}</th>
              <th>{{ $t('Payment/Loan OD Account') }}</th>
              <th>{{ $t('Credit To') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!od_accounts.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="od_accounts.data && od_accounts.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="vendor in od_accounts.data" :key="vendor.id">
              <td>
                <span>{{ vendor.anchor_name }}</span>
              </td>
              <td>
                <span>{{ vendor.anchor_unique_identification_number }}</span>
              </td>
              <td class="">{{ vendor.payment_account_number }}</td>
              <td>
                <span>{{ vendor.vendor_bank_details[0].account_number }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="od_accounts.meta"
        :from="od_accounts.meta.from"
        :to="od_accounts.meta.to"
        :links="od_accounts.meta.links"
        :next_page="od_accounts.links.next"
        :prev_page="od_accounts.links.prev"
        :total_items="od_accounts.meta.total"
        :first_page_url="od_accounts.links.first"
        :last_page_url="od_accounts.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>

<script>
import { onMounted, ref, watch, inject } from 'vue';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'Dealers',
  components: {
    Pagination
  },
  setup() {
    const base_url = inject('baseURL');

    const od_accounts = ref([]);
    const anchors_search = ref([]);

    const per_page = ref(50);

    const getPayments = () => {
      axios
        .get(base_url + 'accounts/od-details/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          od_accounts.value = response.data.invoices;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPayments();
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'accounts/od-details/data', {
          params: {
            anchor: anchors_search.value,
            per_page: per_page
          }
        })
        .then(response => {
          od_accounts.value = response.data.invoices;
        });
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'invoices/data', {
          params: {
            anchor: anchors_search.value
          }
        })
        .then(response => {
          od_accounts.value = response.data.invoices;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      anchors_search.value = '';
      $('#date_search').val('');
      axios
        .get(base_url + 'invoices/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          od_accounts.value = response.data.invoices;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            anchor: anchors_search.value
          }
        })
        .then(response => {
          od_accounts.value = response.data.invoices;
        });
    };

    return {
      moment,
      od_accounts,
      changePage,
      per_page,
      anchors_search,
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
