<template>
  <div class="card p-1" id="companies">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row justify-content-between">
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
            <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Anchor"
              aria-describedby="defaultFormControlHelp"
              v-model="anchor_search"
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
        <div class="d-flex justify-content-end mt-2 mt-md-auto">
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
              <th>{{ $t('Payment / OD Acc No') }}</th>
              <th>{{ $t('Anchor') }}</th>
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
            <tr v-for="od_account in od_accounts.data" :key="od_account.id">
              <td>{{ od_account.payment_account_number }}</td>
              <td>{{ od_account.anchor_name }}</td>
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
                  <i
                    v-if="can_update"
                    class="ti ti-circle-plus ti-sm text-primary mx-1"
                    @click="selectOdAccount(od_account)"
                    data-bs-toggle="modal"
                    :data-bs-target="'#credit-od-account-' + od_account.id"
                    style="cursor: pointer"
                    title="Credit to OD Account"
                  ></i>
                  <i
                    v-if="can_update"
                    class="ti ti-circle-minus ti-sm text-primary"
                    @click="selectDebitOdAccount(od_account)"
                    data-bs-toggle="modal"
                    :data-bs-target="'#debit-od-account-' + od_account.id"
                    style="cursor: pointer"
                    title="Debit From OD Account"
                  ></i>
                  <i
                    v-if="can_update"
                    class="ti ti-rotate ti-sm text-primary"
                    @click="selectReverseOdAccount(od_account)"
                    data-bs-toggle="modal"
                    :data-bs-target="'#reverse-od-account-' + od_account.id"
                    style="cursor: pointer"
                    title="Reversal"
                  ></i>
                </div>
              </td>
              <td>
                <div class="modal fade" :id="'debit-od-account-' + od_account.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <form class="modal-content" method="POST" @submit.prevent="debitAccount()">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalTopTitle">{{ $t('Add Debit to OD Account') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <div class="form-group">
                            <label for="" class="form-label">{{ $t('Type') }}</label>
                            <select name="status" id="" class="form-select" v-model="od_debit_type">
                              <option value="">{{ $t('Select') }}</option>
                              <option v-for="debit_type in debit_types" :key="debit_type" :value="debit_type">
                                {{ debit_type }}
                              </option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label">{{ $t('Debit From') }}</label>
                            <input type="text" class="form-control" v-model="debit_from" readonly />
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label"
                              >{{ $t('Credit To') }} <span class="text-danger">*</span></label
                            >
                            <select name="status" id="" class="form-select" v-model="credit_to">
                              <option value="">Select</option>
                              <option
                                v-for="credit_to_account in credit_to_accounts"
                                :key="credit_to_account"
                                :value="credit_to_account"
                              >
                                {{ credit_to_account }}
                              </option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="Amount" class="form-label d-flex">
                              <span>{{ $t('Amount') }}</span>
                              <span class="text-danger">*</span>
                            </label>
                            <input type="text" min="1" class="form-control" id="payment-amount" v-model="amount" />
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label d-flex"
                              ><span>{{ $t('Effective Date') }}</span> <span class="text-danger">*</span></label
                            >
                            <input
                              type="date"
                              name="pay_date"
                              class="form-control"
                              id="html5-date-input"
                              v-model="credit_date"
                            />
                          </div>
                          <div class="form-group">
                            <label for="Status" class="form-label">{{ $t('Initiate CBS') }}</label>
                            <select name="status" id="" class="form-select" v-model="initiate_cbs">
                              <option value="Yes">{{ $t('Yes') }}</option>
                              <option value="No">{{ $t('No') }}</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="Amount" class="form-label d-flex">
                              <span>{{ $t('Transaction Remark') }}</span>
                              <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="transaction-remark" />
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
                <div class="modal fade" :id="'reverse-od-account-' + od_account.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <form class="modal-content" method="POST" @submit.prevent="debitAccount()">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalTopTitle">{{ $t('Fees Reversal') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <div class="form-group">
                            <label for="" class="form-label"
                              >{{ $t('Reversal Type') }} <span class="text-danger">*</span></label
                            >
                            <select name="status" id="" class="form-select" v-model="od_debit_type">
                              <option value="">{{ $t('Select') }}</option>
                              <option v-for="debit_type in reversal_types" :key="debit_type" :value="debit_type">
                                {{ debit_type }}
                              </option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label"
                              >{{ $t('Entity to be reversed') }} <span class="text-danger">*</span></label
                            >
                            <select name="status" id="" class="form-select" v-model="od_debit_type">
                              <option value="">{{ $t('Select') }}</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label">{{ $t('Debit From') }}</label>
                            <input type="text" class="form-control" v-model="debit_from" readonly />
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label">{{ $t('Credit To') }}</label>
                            <input type="text" class="form-control" v-model="credit_to" readonly />
                          </div>
                          <div class="form-group">
                            <label for="Amount" class="form-label d-flex">
                              <span>{{ $t('Amount') }}</span>
                              <span class="text-danger">*</span>
                            </label>
                            <input type="text" min="1" class="form-control" id="payment-amount" v-model="amount" />
                          </div>
                          <div class="form-group">
                            <label for="" class="form-label d-flex"
                              ><span>{{ $t('Credit Date') }}</span> <span class="text-danger">*</span></label
                            >
                            <input
                              type="date"
                              name="pay_date"
                              class="form-control"
                              id="html5-date-input"
                              v-model="credit_date"
                            />
                          </div>
                          <div class="form-group">
                            <label for="Amount" class="form-label d-flex">
                              <span>{{ $t('Particulars') }}</span>
                              <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="transaction-remark" />
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                          {{ $t('Close') }}
                        </button>
                        <button type="submit" class="btn btn-primary" disabled>{{ $t('Submit') }}</button>
                      </div>
                    </form>
                  </div>
                </div>
                <div class="modal fade" :id="'credit-od-account-' + od_account.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <form class="modal-content" method="POST" @submit.prevent="creditAccount(od_account.id)">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalTopTitle">{{ $t('Add Funds to OD Account') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'credit-od-account-close-btn-' + od_account.id"
                          data-bs-dismiss="modal"
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
                                v-for="bank_account in od_account.vendor_bank_details"
                                :key="bank_account.id"
                                :value="bank_account.account_number"
                              >
                                {{ bank_account.account_number }}
                              </option>
                            </select>
                          </div>
                          <div class="form-group my-2">
                            <label for="Status" class="form-label">{{ $t('Repayment Mode') }}</label>
                            <div class="d-flex">
                              <div class="form-radio-group">
                                <input
                                  type="radio"
                                  name="repayment_mode"
                                  value="OD Level"
                                  class="mx-1"
                                  id="od-level-radio"
                                  v-model="repayment_mode"
                                />
                                <label for="OD Level" class="form-label">{{ $t('OD Level') }}</label>
                              </div>
                              <div class="form-radio-group mx-4">
                                <input
                                  type="radio"
                                  name="repayment_mode"
                                  value="Drawdown Level"
                                  id="drawdown-level-radio"
                                  class="form-radio-input mx-1"
                                  v-model="repayment_mode"
                                />
                                <label for="Drawdown Level" class="form-label">{{ $t('Drawdown Level') }}</label>
                              </div>
                            </div>
                            <div class="form-group" v-if="repayment_mode == 'Drawdown Level'">
                              <label for="Status" class="form-label">{{ $t('Repayment Against') }}</label>
                              <select name="status" id="" class="form-select" v-model="repayment_against">
                                <option value="">{{ $t('Select') }}</option>
                                <option v-for="invoice in od_account.invoices" :key="invoice.id" :value="invoice.id">
                                  {{ invoice.invoice_number }}
                                </option>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="Amount" class="form-label">{{ $t('Amount') }}</label>
                            <input type="text" min="1" class="form-control" id="payment-amount" v-model="amount" />
                          </div>
                          <div class="form-group">
                            <label for="Total Outstanding" class="form-label">{{ $t('Total Outstanding') }}</label>
                            <input type="text" class="form-control" readonly v-model="total_outstanding" />
                          </div>
                          <div class="form-group">
                            <label for="Total Outstanding" class="form-label">{{ $t('Total Balance') }}</label>
                            <input type="text" class="form-control" readonly v-model="total_balance" />
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
                            <label for="Status" class="form-label">{{ $t('Initiate CBS') }}</label>
                            <select name="status" id="" class="form-select" v-model="initiate_cbs">
                              <option value="Yes">{{ $t('Yes') }}</option>
                              <option value="No">{{ $t('No') }}</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button
                          type="button"
                          class="btn btn-label-secondary"
                          data-bs-dismiss="modal"
                          :disabled="!can_credit_account"
                        >
                          {{ $t('Close') }}
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="!can_credit_account">
                          {{ can_credit_account ? $t('Submit') : $t('Processing') }}
                        </button>
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
  </div>
</template>
<script>
import { useToast } from 'vue-toastification';
import { computed, watch, onMounted, ref, inject } from 'vue';
import Pagination from './partials/Pagination.vue';
import axios from 'axios';

export default {
  name: 'OdAccounts',
  props: ['bank'],
  components: {
    Pagination
  },
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const od_accounts = ref([]);
    const bank = ref('');
    const debit_from = ref('');
    const credit_to = ref('');
    const repayment_mode = ref('OD Level');
    const repayment_against = ref('');
    const credit_date = ref('');
    const amount = ref('');
    const initiate_cbs = ref('');
    const total_outstanding = ref(0);
    const total_balance = ref(0);
    const selected_program_configuration = ref(null);
    const od_debit_type = ref('');
    const selected_od_account = ref(null);

    const debit_types = ['Refund'];

    const reversal_types = ['Fees'];

    const credit_to_accounts = ref([]);

    // Search Fields
    const payment_account_number_search = ref('');
    const anchor_search = ref('');
    const dealer_search = ref('');

    // Pagination
    const per_page = ref(50);

    const can_update = ref(false);

    const can_credit_account = ref(true);

    const getOdAccounts = async () => {
      await axios
        .get(base_url + props.bank + '/companies/od-accounts/data?per_page=' + per_page.value, {
          params: {
            payment_account_number: payment_account_number_search.value,
            anchor: anchor_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          (od_accounts.value = response.data.programs), (can_update.value = response.data.can_update);
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/companies/od-accounts/data', {
          params: {
            per_page: per_page,
            payment_account_number: payment_account_number_search.value,
            anchor: anchor_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          od_accounts.value = response.data.programs;
        });
    });

    watch([repayment_mode, repayment_against], ([repayment_mode, repayment_against]) => {
      let selected_invoice = null;
      if (repayment_mode == 'Drawdown Level') {
        if (repayment_against != '') {
          od_accounts.value.data.forEach(od_account => {
            od_account.invoices.forEach(invoice => {
              if (invoice.id == repayment_against) {
                selected_invoice = invoice;
                total_outstanding.value = Number(selected_invoice.balance).toLocaleString();
              }
            });
          });
        }
      } else {
        total_outstanding.value = Number(selected_od_account.value.utilized_amount).toLocaleString();
      }
    });

    watch([od_debit_type], ([od_debit_type]) => {
      switch (od_debit_type) {
        case 'Refund':
          axios
            .get(
              base_url +
                props.bank +
                '/companies/od-accounts/' +
                selected_program_configuration.value +
                '/Refund/accounts'
            )
            .then(response => {
              credit_to_accounts.value = response.data.accounts;
            })
            .catch(err => {
              console.log(err);
            });
          break;
        case 'Fee':
          axios
            .get(
              base_url + props.bank + '/companies/od-accounts/' + selected_program_configuration.value + '/Fee/accounts'
            )
            .then(response => {
              credit_to_accounts.value = response.data.accounts;
            })
            .catch(err => {
              console.log(err);
            });
          break;

        default:
          break;
      }
    });

    watch(amount, newVal => {
      let remove_commas = String(newVal).replaceAll(',', '');
      let total_outstanding_number = String(total_outstanding.value).replaceAll(',', '');
      total_balance.value = Number(total_outstanding_number - remove_commas).toLocaleString();
      amount.value = Number(String(remove_commas).replaceAll(/\D/g, '')).toLocaleString();
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/companies/od-accounts/data?per_page=' + per_page.value, {
          params: {
            payment_account_number: payment_account_number_search.value,
            anchor: anchor_search.value,
            dealer: dealer_search.value
          }
        })
        .then(response => {
          od_accounts.value = response.data.programs;
          can_update.value = response.data.can_update;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      payment_account_number_search.value = '';
      anchor_search.value = '';
      dealer_search.value = '';
      await axios
        .get(base_url + props.bank + '/companies/od-accounts/data?per_page=' + per_page.value)
        .then(response => {
          od_accounts.value = response.data.programs;
          can_update.value = response.data.can_update;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    onMounted(() => {
      getOdAccounts();
    });

    const selectOdAccount = od_account => {
      selected_od_account.value = od_account;
      credit_to.value = od_account.payment_account_number;
      total_outstanding.value = Number(od_account.utilized_amount).toLocaleString();
    };

    const selectDebitOdAccount = od_account => {
      selected_program_configuration.value = od_account.id;
      debit_from.value = od_account.payment_account_number;
    };

    const selectReverseOdAccount = od_account => {
      debit_from.value = od_account.vendor_bank_details[0].account_number;
      credit_to.value = od_account.payment_account_number;
    };

    const getUtlizedLimit = invoice => {
      let total = 0;
      let paid = 0;

      total = invoice.drawdown_amount;

      invoice.payments.forEach(payment => {
        paid += payment.amount;
      });

      return total - paid;
    };

    const getAvailableLimit = invoice => {
      let total = 0;
      let paid = 0;

      total = invoice.drawdown_amount;

      invoice.payments.forEach(payment => {
        paid += payment.amount;
      });

      let sanctioned_limit = invoice.vendor_configuration.sanctioned_limit;

      return sanctioned_limit - (total - paid);
    };

    const creditAccount = od_acccount => {
      can_credit_account.value = false;
      axios
        .post(base_url + props.bank + '/companies/od-accounts/credit-account', {
          credit_to_account: credit_to.value,
          debit_from_account: debit_from.value,
          amount: String(amount.value).replaceAll(',', ''),
          repayment_mode: repayment_mode.value,
          initiate_cbs: initiate_cbs.value,
          invoice_id: repayment_against.value,
          credit_date: credit_date.value
        })
        .then(() => {
          toast.success('Credit Account Request sent successfully');
          document.getElementById('credit-od-account-close-btn-' + od_acccount).click();
          getOdAccounts();
          credit_to.value = '';
          debit_from.value = '';
          amount.value = 0;
          repayment_mode.value = 'OD Level';
          initiate_cbs.value = '';
          repayment_against.value = '';
          credit_date.value = '';
          total_outstanding.value = 0;
          total_balance.value = 0;
        })
        .catch(err => {
          let error = '';
          Object.values(err.response.data).forEach(message => {
            message.forEach((data, key) => {
              console.log(data);
              error += data + ',';
            });
          });
          toast.error(error);
        })
        .finally(() => {
          can_credit_account.value = true;
        });
    };

    const debitAccount = () => {
      axios
        .post(base_url + props.bank + '/companies/od-accounts/debit-account', {
          credit_to_account: credit_to.value,
          debit_from_account: debit_from.value,
          amount: String(amount.value).replaceAll(',', ''),
          initiate_cbs: initiate_cbs.value,
          credit_date: credit_date.value
        })
        .then(() => {
          toast.success('Account debited successfully');
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          toast.error(err.response.data.message);
          console.log(err);
        });
    };

    const reversal = () => {
      axios
        .post(base_url + props.bank + '/companies/od-accounts/reversal', {
          credit_to_account: credit_to.value,
          debit_from_account: debit_from.value,
          amount: String(amount.value).replaceAll(',', ''),
          initiate_cbs: initiate_cbs.value,
          credit_date: credit_date.value
        })
        .then(() => {
          toast.success('Reversal transaction added successfully');
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          toast.error(err.response.data.message);
          console.log(err);
        });
    };

    const changePage = async page => {
      await axios.get(page + '&per_page=' + per_page.value).then(response => {
        od_accounts.value = response.data.programs;
      });
    };

    return {
      od_accounts,
      changePage,
      debit_from,
      credit_to,
      repayment_mode,
      repayment_against,
      credit_date,
      amount,
      initiate_cbs,
      total_outstanding,
      total_balance,

      debit_types,
      od_debit_type,
      credit_to_accounts,

      reversal_types,

      // Search Fields
      payment_account_number_search,
      anchor_search,
      dealer_search,

      // Pagination
      per_page,

      can_update,

      can_credit_account,

      creditAccount,
      debitAccount,
      reversal,
      selectOdAccount,
      selectDebitOdAccount,
      selectReverseOdAccount,
      getUtlizedLimit,
      getAvailableLimit,
      filter,
      refresh
    };
  }
};
</script>
