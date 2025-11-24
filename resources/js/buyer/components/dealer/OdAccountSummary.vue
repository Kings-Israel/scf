<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex">
        <div class="mx-1">
          <label for="" class="form-label">{{ $t('Date') }}</label>
          <input class="form-control form-search" type="date" id="html5-date-input" />
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
      :from="repayments.from"
      :to="repayments.to"
      :links="repayments.links"
      :next_page="repayments.next_page_url"
      :prev_page="repayments.prev_page_url"
      :total_items="repayments.total"
      :first_page_url="repayments.first_page_url"
      :last_page_url="repayments.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Date') }}</th>
            <th>{{ $t('Particulars') }}</th>
            <th>{{ $t('Debit') }}</th>
            <th>{{ $t('Credit') }}</th>
            <th>{{ $t('Line Balance') }}</th>
            <th>{{ $t('Discount Balance') }}</th>
            <th>{{ $t('Penal Discount Balance') }}</th>
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
          <tr v-for="data in repayments.data" :key="data.id">
            <td>
              <span>{{ moment(data.transaction_created_date).format('Do MMM YYYY') }}</span>
            </td>
            <td>-</td>
            <td>
              <span>
                {{ new Intl.NumberFormat().format(data.amount) }}
              </span>
            </td>
            <td>
              <span>-</span>
            </td>
            <td>
              <span>-</span>
            </td>
            <td>-</td>
            <td>-</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="repayments.from"
      :to="repayments.to"
      :links="repayments.links"
      :next_page="repayments.next_page_url"
      :prev_page="repayments.prev_page_url"
      :total_items="repayments.total"
      :first_page_url="repayments.first_page_url"
      :last_page_url="repayments.last_page_url"
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
  name: 'OdAccountSummary',
  components: {
    Pagination
  },
  props: ['od_account'],
  setup(props) {
    const base_url = inject('baseURL');
    const repayments = ref([]);
    const per_page = ref(50);

    const getPayments = () => {
      axios
        .get(base_url + 'dealer/accounts/od-accounts/' + props.od_account + '/payments/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          repayments.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPayments();
    });

    const changePage = async page => {
      await axios.get(page).then(response => {
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
