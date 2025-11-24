<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex">
        <div class="">
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="OD Account"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="mx-1">
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Initiated By"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="mx-1">
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Approved By"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="mx-1">
          <input class="form-control form-search" type="date" value="2021-06-18" id="html5-date-input" />
        </div>
      </div>
      <div class="d-flex justify-content-end">
        <select class="form-select mx-1" id="exampleFormControlSelect1" v-model="per_page">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
    <pagination
      v-if="repayments.meta"
      :from="repayments.meta.from"
      :to="repayments.meta.to"
      :links="repayments.meta.links"
      :next_page="repayments.links.next"
      :prev_page="repayments.links.prev"
      :total_items="repayments.meta.total"
      :first_page_url="repayments.links.first"
      :last_page_url="repayments.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('OD Account') }}</th>
            <th>{{ $t('Credit Date') }}</th>
            <th>{{ $t('Amount') }}</th>
            <th>{{ $t('Initiated By') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!repayments.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="repayments.data && repayments.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment in repayments.data" :key="payment.id">
            <td class="">{{ payment.payment_account_number }}</td>
            <td>{{ moment(payment.updated_at).format('DD MMM YYYY') }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(payment.invoice_total_amount) }}</td>
            <td>CBS</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="repayments.meta"
      :from="repayments.meta.from"
      :to="repayments.meta.to"
      :links="repayments.meta.links"
      :next_page="repayments.links.next"
      :prev_page="repayments.links.prev"
      :total_items="repayments.meta.total"
      :first_page_url="repayments.links.first"
      :last_page_url="repayments.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import moment from 'moment';
import axios from 'axios';
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'OdRepayments',
  components: {
    Pagination
  },
  setup() {
    const base_url = inject('baseURL');
    const repayments = ref([]);
    const per_page = ref(50);

    const getPayments = () => {
      axios
        .get(base_url + 'accounts/od-repayments/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          repayments.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPayments();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          repayments.value = response.data.data;
        });
    };

    return {
      moment,
      repayments,
      per_page,
      changePage
    };
  }
};
</script>
