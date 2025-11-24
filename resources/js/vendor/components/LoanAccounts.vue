<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap"></div>
        <div class="d-flex">
          <div>
            <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
            </select>
          </div>
        </div>
      </div>
      <pagination
        :from="accounts.from"
        :to="accounts.to"
        :links="accounts.links"
        :next_page="accounts.next_page_url"
        :prev_page="accounts.prev_page_url"
        :total_items="accounts.total"
        :first_page_url="accounts.first_page_url"
        :last_page_url="accounts.last_page_url"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive p-2">
        <table class="table">
          <thead>
            <tr>
              <th>{{ $t('Program Name') }}</th>
              <th>{{ $t('Anchor') }}</th>
              <th>{{ $t('My Loan/OD Account') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!accounts.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="accounts.data && accounts.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="account in accounts.data" :key="account.id">
              <td>{{ account.program.name }}</td>
              <td>{{ account.program.anchor.name }}</td>
              <td class="">{{ account.payment_account_number }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        :from="accounts.from"
        :to="accounts.to"
        :links="accounts.links"
        :next_page="accounts.next_page_url"
        :prev_page="accounts.prev_page_url"
        :total_items="accounts.total"
        :first_page_url="accounts.first_page_url"
        :last_page_url="accounts.last_page_url"
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
import Pagination from './partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'LoanAccounts',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const accounts = ref([]);

    const per_page = ref(50);

    const getAccounts = async () => {
      await axios
        .get(base_url + 'invoices/accounts/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          accounts.value = response.data.accounts;
        });
    };

    onMounted(() => {
      getAccounts();
    });

    const changePage = async page => {
      await axios.get(page).then(response => {
        accounts.value = response.data.accounts;
      });
    };

    return {
      per_page,
      moment,
      accounts,
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
