<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div></div>
        <!-- <div class="d-flex">
          <div class="mb-1">
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
          <div class="mx-1">
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
          <div class="mx-1 my-auto">
            <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="mx-1 my-auto">
            <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div> -->
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
              <th>{{ $t('Date') }}</th>
              <th>{{ $t('Particulars') }}</th>
              <th>{{ $t('Amount') }}</th>
              <th>{{ $t('Line Balance') }}</th>
              <th>{{ $t('Discount Balance') }}</th>
              <th>{{ $t('Penal Discount Balance') }}</th>
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
            <tr v-for="cbs_transaction in reports.data" :key="cbs_transaction.id">
              <td>{{ moment(cbs_transaction.created_at).format('DD MMM YYYY') }}</td>
              <td>
                <div v-if="cbs_transaction.transaction_type == 'Payment Disbursement'">
                  <div v-if="cbs_transaction.payment_request.program_type == 'Dealer Financing'">
                    <span>Drawdown against {{ cbs_transaction.payment_request.invoice_number }}</span>
                  </div>
                  <div v-else>
                    <span>Disbursement for {{ cbs_transaction.payment_request.invoice_number }}</span>
                  </div>
                </div>
                <span v-if="cbs_transaction.transaction_type == 'Accrual/Posted Interest'"
                  >Discount Payment for {{ cbs_transaction.payment_request.invoice_number }}</span
                >
                <span v-if="cbs_transaction.transaction_type == 'Fees/Charges'"
                  >Fees/Charges Payment for {{ cbs_transaction.payment_request.invoice_number }}</span
                >
                <span v-if="cbs_transaction.transaction_type == 'Repayment'"
                  >Repayment for {{ cbs_transaction.payment_request.invoice_number }}</span
                >
                <span v-if="cbs_transaction.transaction_type == 'Bank Invoice Payment'"
                  >Repayment for {{ cbs_transaction.payment_request.invoice_number }}</span
                >
                <span v-if="cbs_transaction.transaction_type == 'Overdue Account'"
                  >Penal Payment againt {{ cbs_transaction.payment_request.invoice_number }}</span
                >
              </td>
              <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(cbs_transaction.amount) }}</td>
              <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(cbs_transaction.amount) }}</td>
              <td>-</td>
              <td>-</td>
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
import moment from 'moment';

export default {
  name: 'FactoringOdAccounts',
  components: {
    Pagination
  },
  props: ['program_vendor_configuration'],
  setup(props) {
    const base_url = inject('baseURL');
    const reports = ref([]);

    // Search Fields
    const payment_account_number_search = ref('');
    const dealer_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getOdAccounts = async () => {
      await axios
        .get(
          base_url +
            'factoring/invoices/od-accounts/' +
            props.program_vendor_configuration +
            '/cbs-transactions?per_page=' +
            per_page.value,
          {
            params: {
              payment_account_number: payment_account_number_search.value,
              dealer: dealer_search.value
            }
          }
        )
        .then(response => {
          reports.value = response.data;
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'factoring/invoices/od-accounts/' + props.program_vendor_configuration + '/cbs-transactions', {
          params: {
            per_page: per_page,
            payment_account_number: payment_account_number_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          reports.value = response.data;
        });
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(
          base_url +
            'factoring/invoices/od-accounts/' +
            props.program_vendor_configuration +
            '/cbs-transactions?per_page=' +
            per_page.value,
          {
            params: {
              payment_account_number: payment_account_number_search.value,
              dealer: dealer_search.value
            }
          }
        )
        .then(response => {
          reports.value = response.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      payment_account_number_search.value = '';
      dealer_search.value = '';
      await axios
        .get(
          base_url +
            'factoring/invoices/od-accounts/' +
            props.program_vendor_configuration +
            '/cbs-transactions?per_page=' +
            per_page.value
        )
        .then(response => {
          reports.value = response.data;
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
          reports.value = response.data;
        });
    };

    return {
      reports,
      moment,
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
