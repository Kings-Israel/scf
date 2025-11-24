use id;
<template>
  <div class="card p-2">
    <div class="card-body p-0">
      <div class="d-flex flex-column flex-md-row justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="d-none">
            <label for="CBS ID" class="form-label">{{ $t('CBS ID') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="CBS/PR ID"
              v-model="cbs_id_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Account" class="form-label">{{ $t('Account') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Debit From/Credit To"
              v-model="account_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Vendor"
              v-model="vendor_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Invoice/Unique Ref"
              v-model="invoice_number_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="form-group">
            <label for="Status" class="form-label">{{ $t('Transaction Type') }}</label>
            <select class="form-select select2" multiple id="transaction-type-select" v-model="transaction_type">
              <option value="">{{ $t('Select Transaction Type') }}</option>
              <option value="Platform Charges">{{ $t('Platform Charges') }}</option>
              <option value="Discount Charge">{{ $t('Discount Charge') }}</option>
              <option value="OD Drawdown">{{ $t('OD Drawdown') }}</option>
              <option value="Fees/Charges">{{ $t('Fees/Charges') }}</option>
              <option value="Repayment">{{ $t('Repayment') }}</option>
              <option value="Payment Disbursement">{{ $t('Payment Disbursement') }}</option>
              <option value="Balance DF Invoice Payment">{{ $t('Balance DF Invoice Payment') }}</option>
              <option value="Bank Invoice Payment">{{ $t('Bank Invoice Payment') }}</option>
              <option value="OD Account Debit">{{ $t('OD Account Debit') }}</option>
              <option value="Overdue Account">{{ $t('Overdue Account') }}</option>
              <option value="Funds Transfer">{{ $t('Funds Transfer') }}</option>
              <!-- <option value="Advanced Discount Settlement">{{ $t('Advanced Discount Settlement') }}</option>
              <option value="Unrealized Discount Settlement">{{ $t('Unrealized Discount Settlement') }}</option> -->
            </select>
          </div>
          <div class="">
            <label for="Status" class="form-label">{{ $t('Sort By') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect1" v-model="sort_by">
              <option value="">{{ $t('Sort By') }}</option>
              <option value="ASC">{{ $t('Ascending') }}</option>
              <option value="DESC">{{ $t('Descending') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Product Type" class="form-label">{{ $t('Product Type') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type_search">
              <option value="">{{ $t('Product Type') }}</option>
              <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
              <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
              <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
              <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
            </select>
          </div>
          <div class="">
            <label class="form-label">{{ $t('Transaction Date') }}</label>
            <input
              class="form-control form-search"
              type="text"
              id="transaction_date_search"
              name="payments-report-transaction-daterange"
              v-on:keyup.enter="filter"
            />
          </div>
          <!-- <div class="">
            <label for="From" class="form-label">{{ $t('From') }} ({{ $t('Transaction Date') }})</label>
            <input type="date" class="form-control form-search" v-model="from_date" v-on:keyup.enter="filter" />
          </div>
          <div class="">
            <label for="To" class="form-label">{{ $t('To') }} ({{ $t('Transaction Date') }})</label>
            <input type="date" class="form-control form-search" v-model="to_date" v-on:keyup.enter="filter" />
          </div> -->
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex gap-1 justify-content-md-end mt-2 mt-md-auto">
          <div class="">
            <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
              <option value="100">50</option>
            </select>
          </div>
          <div class="">
            <download-buttons
              @download-report="downloadReport"
              @download-pdf="downloadPdf"
              :downloading-excel="downloadingExcel"
              :downloading-pdf="downloadingPdf"
            ></download-buttons>
          </div>
        </div>
      </div>
      <pagination
        v-if="transactions.meta"
        :from="transactions.meta.from"
        :to="transactions.meta.to"
        :links="transactions.meta.links"
        :next_page="transactions.links.next"
        :prev_page="transactions.links.prev"
        :total_items="transactions.meta.total"
        :first_page_url="transactions.links.first"
        :last_page_url="transactions.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="">
              <th class="px-1">{{ $t('CBS ID') }}</th>
              <th class="">{{ $t('Debit From') }}</th>
              <th class="">{{ $t('Credit To') }}</th>
              <th class="">{{ $t('Amount') }}</th>
              <th class="">{{ $t('Invoice / Unique Ref No') }}</th>
              <th class="">{{ $t('Vendor') }}</th>
              <th class="">{{ $t('Pay Date') }}</th>
              <th class="">{{ $t('Transaction Date') }}</th>
              <th class="">{{ $t('Transaction Ref') }}</th>
              <th class="">{{ $t('Product Type') }}</th>
              <th class="">{{ $t('Payment Service') }}</th>
              <th class="">{{ $t('Status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr v-if="!transactions.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="transactions.data && transactions.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="transaction in transactions.data" :key="transaction" class="text-nowrap">
              <td class="px-1">
                <span>{{ transaction.cbs_id }}</span>
              </td>
              <td class="">
                <span style="max-width: 110px; overflow-x: clip; display: block">{{
                  transaction.debit_from_account
                }}</span>
              </td>
              <td class="">
                <span style="max-width: 110px; overflow-x: clip; display: block">{{
                  transaction.credit_to_account
                }}</span>
              </td>
              <td class="text-success">
                {{ transaction.payment_request ? transaction.payment_request.invoice_currency : '' }}
                {{ new Intl.NumberFormat().format(transaction.amount) }}
              </td>
              <td
                class="text-primary text-decoration-underline text-wrap"
                v-if="transaction.payment_request"
                @click="
                  showInvoice(
                    transaction.payment_request.invoice_id,
                    'show-details-btn-' + transaction.payment_request.invoice_id
                  )
                "
                style="cursor: pointer"
              >
                {{ transaction.payment_request.invoice_number }}
              </td>
              <td v-else>-</td>
              <td class="">{{ transaction.payment_request ? transaction.payment_request.company_name : '' }}</td>
              <td v-if="transaction.pay_date" class="">{{ moment(transaction.pay_date).format(date_format) }}</td>
              <td v-else class="">-</td>
              <td class="">{{ moment(transaction.transaction_created_date).format(date_format) }}</td>
              <td class="">{{ transaction.transaction_reference }}</td>
              <td class="m_title">{{ transaction.product }}</td>
              <td class="">{{ transaction.transaction_type }}</td>
              <td class="">
                <span class="badge me-1" :class="resolvePaymentRequestStatus(transaction.status)">{{
                  transaction.status
                }}</span>
              </td>
              <td class="d-none">
                <div class="d-flex">
                  <i
                    class="ti ti-eye ti-sm text-primary"
                    data-bs-toggle="modal"
                    :data-bs-target="'#cbs-transactions-details-' + transaction.id"
                    style="cursor: pointer"
                  ></i>
                </div>
              </td>
              <td>
                <button
                  v-if="transaction.payment_request"
                  class="d-none"
                  :id="'show-details-btn-' + transaction.payment_request.invoice_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-' + transaction.payment_request.invoice_id"
                ></button>
                <button
                  v-if="transaction.payment_request"
                  class="d-none"
                  :id="'show-pi-btn-' + transaction.payment_request.invoice_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + transaction.payment_request.invoice_id"
                ></button>
                <button
                  class="d-none"
                  id="loading-modal-btn"
                  data-bs-toggle="modal"
                  data-bs-target="#loading-modal"
                ></button>
                <div class="modal fade" id="loading-modal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                      <div class="modal-body">
                        <div class="d-flex justify-content-center">
                          <span>{{ $t('Loading') }}...</span>
                          <img src="../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div
                  class="modal fade"
                  :id="'cbs-transactions-details-' + transaction.id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('CBS Transaction') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="table-responsive">
                          <table class="table">
                            <thead style="background: #f0f0f0">
                              <tr>
                                <th>{{ $t('Date Time') }}</th>
                                <th>{{ $t('Code') }}</th>
                                <th>{{ $t('Reference Code') }}</th>
                                <th>{{ $t('Message') }}</th>
                                <th>{{ $t('Transaction Type') }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td>{{ moment(transaction.transaction_date).format(date_format) }}</td>
                                <td>{{ transaction.product == 'Vendor Financing' ? 'VF' : 'DF' }}</td>
                                <td>
                                  {{ transaction.transaction_reference ? transaction.transaction_reference : '-' }}
                                </td>
                                <td>-</td>
                                <td>{{ transaction.transaction_type }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
                <payment-instruction
                  v-if="invoice_details"
                  :invoice-details="invoice_details"
                  :key="invoice_details_key"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="transactions.meta"
        :from="transactions.meta.from"
        :to="transactions.meta.to"
        :links="transactions.meta.links"
        :next_page="transactions.links.next"
        :prev_page="transactions.links.prev"
        :total_items="transactions.meta.total"
        :first_page_url="transactions.links.first"
        :last_page_url="transactions.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>
<script>
import { computed, inject, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'CbsTransactions',
  components: {
    Pagination,
    DownloadButtons,
    InvoiceDetails,
    PaymentInstruction
  },
  props: ['bank', 'date_format'],
  setup(props) {
    const date_format = props.date_format;
    const toast = useToast();
    const base_url = inject('baseURL');
    const transactions = ref([]);
    const cbs_transactions = ref(null);

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const cbs_transaction_statuses = ref(['Successful', 'Created', 'Failed', 'Permanently Failed']);

    const cbsTransaction = ref(null);

    // Search fields
    const cbs_id_search = ref('');
    const invoice_number_search = ref('');
    const account_search = ref('');
    const vendor_search = ref('');
    const transaction_ref_search = ref('');
    const status_search = ref('');
    const product_type_search = ref('');
    const transaction_type = ref([]);
    const sort_by = ref('');
    const from_date = ref('');
    const to_date = ref('');

    const transaction_date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'payments-report'
          }
        })
        .then(response => {
          transactions.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/requests/invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          invoice_details_key.value++;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      // // Get values of status filter
      // $('#cbs-status-select').on('change', function () {
      //   var ids = $('#cbs-status-select').val();
      //   status_search.value = ids;
      // });
      $('#transaction-type-select').on('change', function () {
        var ids = $('#transaction-type-select').val();
        transaction_type.value = ids;
      });
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page,
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            transaction_type: transaction_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            type: 'payments-report'
          }
        })
        .then(response => {
          transactions.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      transaction_date_search.value = $('#transaction_date_search').val();
      if (transaction_date_search.value) {
        from_date.value = moment(transaction_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(transaction_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            type: 'payments-report',
            sort_by: sort_by.value,
            transaction_type: transaction_type.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          transactions.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      cbs_id_search.value = '';
      transaction_ref_search.value = '';
      invoice_number_search.value = '';
      account_search.value = '';
      status_search.value = '';
      product_type_search.value = '';
      sort_by.value = '';
      transaction_type.value = [];
      from_date.value = '';
      to_date.value = '';
      vendor_search.value = '';
      $('#transaction_date_search').val();
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'payments-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          transactions.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            type: 'payments-report',
            sort_by: sort_by.value,
            transaction_type: transaction_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            vendor: vendor_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payments_Report_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          downloadingExcel.value = false;
        });
    };

    const downloadPdf = () => {
      downloadingPdf.value = true;
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            type: 'payments-report',
            sort_by: sort_by.value,
            transaction_type: transaction_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            vendor: vendor_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payments_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          downloadingPdf.value = false;
        });
    };

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return (tax_amount / invoice_amount) * 100;
    };

    const getTotalAmount = invoice_items => {
      let amount = 0;
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity;
      });
      return amount;
    };

    const getTaxesAmount = invoice_taxes => {
      let amount = 0;
      invoice_taxes.forEach(item => {
        amount += item.value;
      });
      return amount;
    };

    const getFeesAmount = invoice_fees => {
      let amount = 0;
      invoice_fees.forEach(item => {
        amount += item.amount;
      });
      return amount;
    };

    onMounted(() => {
      getData();
    });

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status.toLowerCase()) {
        case 'created':
          style = 'bg-label-primary';
          break;
        case 'successful':
          style = 'bg-label-success';
          break;
        case 'failed':
          style = 'bg-label-danger';
          break;
        case 'permanently failed':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }

      return style;
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            type: 'payments-report',
            sort_by: sort_by.value,
            transaction_type: transaction_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            vendor: vendor_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          transactions.value = response.data;
        });
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Overdue':
          style = 'bg-label-danger';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending Approval':
          style = 'bg-label-warning';
          break;
        case 'Pending Maker':
          style = 'bg-label-warning';
          break;
        case 'Pending Checker':
          style = 'bg-label-warning';
          break;
        case 'Submitted':
          style = 'bg-label-primary';
          break;
        case 'Approved':
          style = 'bg-label-success';
          break;
        case 'Disbursed':
          style = 'bg-label-success';
          break;
        case 'Denied':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    return {
      moment,
      transactions,
      cbs_transactions,
      cbsTransaction,

      invoice_details,

      cbs_transaction_statuses,
      cbs_id_search,
      invoice_number_search,
      account_search,
      transaction_ref_search,
      status_search,
      product_type_search,
      per_page,
      sort_by,
      from_date,
      to_date,
      transaction_type,
      vendor_search,

      filter,
      refresh,

      resolvePaymentRequestStatus,
      changePage,

      getTotalAmount,
      getTaxesAmount,
      getFeesAmount,
      getTaxPercentage,
      downloadReport,
      downloadPdf,
      resolveStatus,
      showInvoice,

      date_format,
      downloadingExcel,
      downloadingPdf
    };
  }
};
</script>
<style>
[data-title]:hover:after {
  opacity: 1;
  transition: all 0.1s ease 0.5s;
  visibility: visible;
}

[data-title]:after {
  content: attr(data-title);
  background-color: #0b0b0b;
  color: #f9f9f9;
  font-size: 16px;
  position: absolute;
  padding: 1px 5px 2px 5px;
  bottom: -1.6em;
  left: 100%;
  box-shadow: 1px 1px 3px #222222;
  opacity: 0;
  border: 1px solid #111111;
  z-index: 99999;
  visibility: hidden;
  border-radius: 5px;
  min-width: 250px;
  max-width: 550px;
}

[data-title] {
  position: relative;
}

input[readonly] {
  background-color: #e8e8e8;
}
</style>
