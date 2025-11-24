<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="OD Account" class="form-label">{{ $t('Payment/OD Account') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Payment/OD Account No."
              aria-describedby="defaultFormControlHelp"
              v-model="payment_account_number_search"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Dealer" class="form-label">{{ $t('Dealer') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Dealer"
              aria-describedby="defaultFormControlHelp"
              v-model="dealer_search"
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
        <div class="d-flex justify-content-end">
          <div class="">
            <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
      </div>
      <pagination
        v-if="reports.meta"
        :from="reports.meta.from"
        :to="reports.meta.to"
        :links="reports.meta.links"
        :next_page="reports.links.next"
        :prev_page="reports.links.prev"
        :total_items="reports.meta.total"
        :first_page_url="reports.links.first"
        :last_page_url="reports.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="">
              <th>{{ $t('Payment / OD Acc No') }}</th>
              <th>{{ $t('Dealer') }}</th>
              <th>{{ $t('OD Expiry Status') }}</th>
              <th>{{ $t('Sanctioned Limit') }}</th>
              <th>{{ $t('Utilized Limit') }}</th>
              <th>{{ $t('Pipeline Requests') }}</th>
              <th>{{ $t('Excess Credit') }}</th>
              <th>{{ $t('Available Limit') }}</th>
              <th>{{ $t('Overdue Days') }}</th>
              <th>{{ $t('Actions') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr v-if="!reports.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="reports.data && reports.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="od_account in reports.data" :key="od_account.id">
              <td>
                <div class="d-flex">
                  {{ od_account.payment_account_number }}
                  <i
                    v-if="od_account.overdue_days > 0"
                    class="tf-icons ti ti-info-circle ti-xs text-danger"
                    title="Dealer/Vendor has overdue invoices"
                  ></i>
                </div>
              </td>
              <td>{{ od_account.company_name }}</td>
              <td class="m_title">{{ od_account.status }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(od_account.sanctioned_limit) }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(od_account.utilized_amount) }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(od_account.pipeline_amount) }}</td>
              <td class="text-success">
                <span v-if="od_account.paid_amount > od_account.sanctioned_limit">
                  {{ new Intl.NumberFormat().format(od_account.paid_amount - od_account.sanctioned_limit) }}
                </span>
                <span v-else> 0 </span>
              </td>
              <td class="text-success">{{ new Intl.NumberFormat().format(od_account.available_amount) }}</td>
              <td class="text-success">{{ od_account.overdue_days }}</td>
              <td>
                <div class="d-flex my-auto">
                  <a :href="'od-accounts/' + od_account.id + '/details'">
                    <i class="ti ti-eye ti-sm text-warning" style="cursor: pointer" title="View"></i>
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="reports.meta"
        :from="reports.meta.from"
        :to="reports.meta.to"
        :links="reports.meta.links"
        :next_page="reports.links.next"
        :prev_page="reports.links.prev"
        :total_items="reports.meta.total"
        :first_page_url="reports.links.first"
        :last_page_url="reports.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>
<script>
import { useToast } from 'vue-toastification';
import { watch, onMounted, ref, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import axios from 'axios';

export default {
  name: 'FactoringOdAccounts',
  components: {
    Pagination
  },
  setup() {
    const base_url = inject('baseURL');
    const reports = ref([]);

    // Search Fields
    const payment_account_number_search = ref('');
    const dealer_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getOdAccounts = async () => {
      await axios
        .get(base_url + 'dealer/invoices/od-accounts/data?per_page=' + per_page.value, {
          params: {
            payment_account_number: payment_account_number_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          reports.value = response.data.data;
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'dealer/invoices/od-accounts/data', {
          params: {
            per_page: per_page,
            payment_account_number: payment_account_number_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          reports.value = response.data.data;
        });
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'dealer/invoices/od-accounts/data?per_page=' + per_page.value, {
          params: {
            payment_account_number: payment_account_number_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          reports.value = response.data.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      payment_account_number_search.value = '';
      dealer_search.value = '';
      await axios.get(base_url + 'dealer/invoices/od-accounts/data?per_page=' + per_page.value).then(response => {
        reports.value = response.data.data;
        parent.html('<i class="ti ti-refresh"></i>');
      });
    };

    onMounted(() => {
      getOdAccounts();
    });

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            payment_account_number: payment_account_number_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          reports.value = response.data.data;
        });
    };

    return {
      reports,
      changePage,

      // Search Fields
      payment_account_number_search,
      dealer_search,

      // Pagination
      per_page,

      filter,
      refresh
    };
  }
};
</script>
