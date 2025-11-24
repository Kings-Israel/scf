<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex"></div>
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
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('OD Account') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('OD Expiry Status') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Utilized Limit') }}</th>
            <th>{{ $t('Pipeline Requests') }}</th>
            <!-- <th>Excess Credit</th> -->
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Overdue Days') }}</th>
            <th>{{ $t('Actions') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
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
          <tr v-for="invoice in od_accounts.data" :key="invoice.id">
            <td>{{ invoice.payment_account_number }}</td>
            <td>{{ invoice.anchor_name }}</td>
            <td class="m_title">{{ invoice.status }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(invoice.sanctioned_limit) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(invoice.utilized_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(invoice.pipeline_amount) }}</td>
            <!-- <td class="text-success">0</td> -->
            <td class="text-success">{{ new Intl.NumberFormat().format(invoice.available_amount) }}</td>
            <td class="text-success">{{ invoice.overdue_days }}</td>
            <td class="d-flex">
              <a :href="'od-accounts/' + invoice.id + '/od-details'">
                <i class="ti ti-eye ti-sm text-warning" style="cursor: pointer" title="View"></i>
              </a>
              <i
                class="ti ti-circle-plus ti-sm text-danger mx-1"
                @click="selectOdAccount(invoice)"
                data-bs-toggle="modal"
                :data-bs-target="'#credit-od-account-' + invoice.id"
                style="cursor: pointer"
                :title="$t('Credit to OD Account')"
              ></i>
              <!-- <i class="ti ti-circle-minus ti-sm text-primary" style="cursor: pointer;" title="Debit From OD Account"></i> -->
            </td>
            <td>
              <div class="modal fade" :id="'credit-od-account-' + invoice.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <form class="modal-content" method="POST" @submit.prevent="creditAccount()">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Add Funds to OD Account') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        :id="'credit-account-request-close-modal-' + invoice.program_id"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <div class="form-group">
                          <label for="" class="form-label">{{ $t('Credit To') }}</label>
                          <input type="text" class="form-control" v-model="credit_to" readonly />
                        </div>
                        <div class="form-group">
                          <label for="" class="form-label">{{ $t('Debit From') }}</label>
                          <select name="status" id="" class="form-select" v-model="debit_from">
                            <option value="">{{ $t('Select') }}</option>
                            <option
                              v-for="bank_account in invoice.vendor_bank_details"
                              :key="bank_account.id"
                              :value="bank_account.account_number"
                            >
                              {{ bank_account.account_number }}
                            </option>
                          </select>
                        </div>
                        <div class="form-group my-2">
                          <div class="form-group">
                            <label for="Status" class="form-label">{{ $t('Currency') }}</label>
                            <select name="status" id="" class="form-select" v-model="currency">
                              <option value="">{{ $t('Select') }}</option>
                              <option v-for="currenci in currencies" :key="currenci.id" :value="currenci.code">
                                {{ currenci.code }} ({{ currenci.name }})
                              </option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="Amount" class="form-label">{{ $t('Amount') }}</label>
                          <input
                            type="text"
                            min="1"
                            class="form-control"
                            step=".01"
                            autocomplete="off"
                            id="payment-amount"
                            v-model="amount"
                          />
                        </div>
                        <div class="form-group">
                          <label for="nameWithTitle" class="form-label">{{ $t('Amount in Words') }}</label>
                          <h6 class="">
                            {{ amount_in_words }}
                          </h6>
                        </div>
                        <div class="form-group">
                          <label for="" class="form-label">{{ $t('Credit Date') }}</label>
                          <input
                            type="date"
                            name="pay_date"
                            class="form-control"
                            id="html5-date-input"
                            v-model="credit_date"
                          />
                        </div>
                        <div class="form-group">
                          <label for="Particulars" class="form-label">{{ $t('Particulars') }}</label>
                          <input type="text" min="1" class="form-control" v-model="particulars" />
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ $t('Close') }}
                      </button>
                      <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
                    </div>
                  </form>
                </div>
              </div>
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
</template>
<script>
import moment from 'moment';
import axios from 'axios';
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import { useToast } from 'vue-toastification';

export default {
  name: 'OdDetails',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');

    const od_accounts = ref([]);
    const currencies = ref([]);

    const debit_from = ref('');
    const credit_to = ref('');
    const program_id = ref('');
    const vendor_configuration_id = ref('');
    const particulars = ref('');
    const currency = ref('');
    const credit_date = ref('');
    const amount = ref('');
    const initiate_cbs = ref('');
    const total_outstanding = ref(0);
    const total_balance = ref(0);

    const amount_in_words = ref('');

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
          currencies.value = response.data.currencies;
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
        od_accounts.value = response.data.invoices;
      });
    };

    const creditAccount = () => {
      axios
        .post(base_url + 'accounts/od-details/credit-account', {
          credit_to_account: credit_to.value,
          debit_from_account: debit_from.value,
          program_id: program_id.value,
          amount: String(amount.value).replaceAll(',', ''),
          particulars: particulars.value,
          currency: currency.value,
          credit_date: credit_date.value,
          vendor_configuration_id: vendor_configuration_id.value
        })
        .then(() => {
          toast.success('Request to credit account sent successfully.');
          getPayments();
          document.getElementById('credit-account-request-close-modal-' + program_id.value).click();
          credit_to.value = '';
          debit_from.value = '';
          program_id.value = '';
          amount.value = '';
          particulars.value = '';
          currency.value = '';
          credit_date.value = '';
          amount_in_words.value = '';
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const selectOdAccount = invoice => {
      credit_to.value = invoice.payment_account_number;
      program_id.value = invoice.program_id;
      vendor_configuration_id.value = invoice.id;
    };

    watch(amount, amount => {
      amount_in_words.value = toWords(amount.replaceAll(',', ''));
    });

    var th = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

    var dg = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    var tn = [
      'Ten',
      'Eleven',
      'Twelve',
      'Thirteen',
      'Fourteen',
      'Fifteen',
      'Sixteen',
      'Seventeen',
      'Eighteen',
      'Nineteen'
    ];
    var tw = ['Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    function toWords(s) {
      s = s.toString();
      s = s.replace(/[\, ]/g, '');
      if (s != parseFloat(s)) return 'Not a Number';
      var x = s.indexOf('.');
      if (x == -1) x = s.length;
      if (x > 15) return 'Too Big';
      var n = s.split('');
      var str = '';
      var sk = 0;
      for (var i = 0; i < x; i++) {
        if ((x - i) % 3 == 2) {
          if (n[i] == '1') {
            str += tn[Number(n[i + 1])] + ' ';
            i++;
            sk = 1;
          } else if (n[i] != 0) {
            str += tw[n[i] - 2] + ' ';
            sk = 1;
          }
        } else if (n[i] != 0) {
          str += dg[n[i]] + ' ';
          if ((x - i) % 3 == 0) str += 'Hundred ';
          sk = 1;
        }
        if ((x - i) % 3 == 1) {
          if (sk) str += th[(x - i - 1) / 3] + ' ';
          sk = 0;
        }
      }
      if (x != s.length) {
        var y = s.length;
        str += 'point ';
        for (var i = x + 1; i < y; i++) str += dg[n[i]] + ' ';
      }
      return str.replace(/\s+/g, ' ');
    }

    return {
      moment,
      od_accounts,
      currencies,
      per_page,
      debit_from,
      credit_to,
      particulars,
      currency,
      credit_date,
      amount,
      amount_in_words,
      initiate_cbs,
      total_outstanding,
      total_balance,
      selectOdAccount,
      creditAccount,
      changePage
    };
  }
};
</script>
