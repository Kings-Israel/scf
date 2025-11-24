<template>
  <div class="card p-2">
    <div class="card-body p-0">
      <div class="d-flex flex-column flex-md-row justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Anchor"
              v-model="anchor_search"
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
            <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}</label>
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
          <div class="">
            <label for="Failed Date" class="form-label">{{ $t('Failed Date') }}</label>
            <input
              class="form-control form-search"
              type="date"
              id="html5-date-input"
              v-model="failed_date"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Product Type" class="form-label">{{ $t('Product Type') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type_search">
              <option value="">{{ $t('Product Type') }}</option>
              <option value="Vendor Financing">{{ $t('Vendor Financing') }}</option>
              <option value="Dealer Financing">{{ $t('Dealer Financing') }}</option>
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
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
          <div class="">
            <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
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
              <!-- <th class="px-1">PR ID</th> -->
              <th class="px-1">{{ $t('Anchor') }}</th>
              <th class="px-1">{{ $t('Vendor') }}</th>
              <th class="px-1">{{ $t('Invoice / Unique Ref No') }}</th>
              <th class="px-1">{{ $t('Debit From') }}</th>
              <th class="px-1">{{ $t('Credit To') }}</th>
              <th class="px-1">{{ $t('Amount') }}</th>
              <th class="px-1">{{ $t('Payment Date') }}</th>
              <th class="px-1">{{ $t('Transaction Failed Date') }}</th>
              <th class="px-1">{{ $t('Product Type') }}</th>
              <th>{{ $t('Actions') }}</th>
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
                <span class="fw-medium">{{ transaction.cbs_id }}</span>
              </td>
              <td class="px-1" v-if="transaction.payment_request">
                {{
                  transaction.payment_request.buyer_name
                    ? transaction.payment_request.buyer_name
                    : transaction.payment_request.anchor_name
                }}
              </td>
              <td v-else>-</td>
              <td class="px-1" v-if="transaction.payment_request">{{ transaction.payment_request.company_name }}</td>
              <td v-else>-</td>
              <td
                class="text-primary text-decoration-underline px-1"
                v-if="transaction.payment_request"
                @click="
                  showInvoice(
                    transaction.payment_request.invoice_id,
                    'show-details-btn-' + transaction.payment_request.invoice_id
                  )
                "
                style="cursor: pointer; max-width: 220px; overflow-x: clip"
              >
                {{ transaction.payment_request.invoice_number }}
              </td>
              <td v-else>-</td>
              <td class="px-1">
                <span style="max-width: 110px; overflow-x: clip; display: block">{{
                  transaction.debit_from_account
                }}</span>
              </td>
              <td class="px-1">
                <span style="max-width: 110px; overflow-x: clip; display: block">{{
                  transaction.credit_to_account
                }}</span>
              </td>
              <td class="text-success px-1">{{ new Intl.NumberFormat().format(transaction.amount) }}</td>
              <td class="px-1">{{ moment(transaction.transaction_created_date).format('DD MMM YYYY') }}</td>
              <td v-if="transaction.pay_date" class="px-1">{{ moment(transaction.pay_date).format('DD MMM YYYY') }}</td>
              <td v-else class="px-1">-</td>
              <td class="m_title px-1">{{ transaction.product }}</td>
              <td>
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
                                <td>{{ moment(transaction.transaction_date).format('DD MMM YYYY') }}</td>
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
                <invoice-details v-if="invoice_details" :invoice-details="invoice_details" />
                <payment-instruction v-if="invoice_details" :invoice-details="invoice_details" />
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
  props: ['bank'],
  setup(props, context) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const transactions = ref([]);
    const cbs_transactions = ref(null);

    const cbs_transaction_statuses = ref(['Successful', 'Created', 'Failed', 'Permanently Failed']);

    // Search fields
    const cbs_id_search = ref('');
    const invoice_number_search = ref('');
    const anchor_search = ref('');
    const vendor_search = ref('');
    const status_search = ref('');
    const failed_date = ref('');
    const product_type_search = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const invoice_details = ref(null);

    const getRequests = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'final-rtr-report',
            product_type: product_type_search.value
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
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    context.expose({ getRequests });

    onMounted(() => {
      getRequests();
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page,
            type: 'final-rtr-report',
            status: status_search.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            anchor: anchor_search.value,
            failed_date: failed_date.value,
            product_type: product_type_search.value,
            sort_by: sort_by.value
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
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'final-rtr-report',
            status: status_search.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            anchor: anchor_search.value,
            failed_date: failed_date.value,
            product_type: product_type_search.value,
            sort_by: sort_by.value
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
      status_search.value = '';
      vendor_search.value = '';
      invoice_number_search.value = '';
      anchor_search.value = '';
      failed_date.value = '';
      product_type_search.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'final-rtr-report'
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return (tax_amount / invoice_amount) * 100;
    };

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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'final-rtr-report',
            status: status_search.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            anchor: anchor_search.value,
            failed_date: failed_date.value,
            product_type: product_type_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Final_rtr_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'final-rtr-report',
            status: status_search.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            anchor: anchor_search.value,
            failed_date: failed_date.value,
            product_type: product_type_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Final_rtr_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            type: 'final-rtr-report',
            status: status_search.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            anchor: anchor_search.value,
            failed_date: failed_date.value,
            product_type: product_type_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          transactions.value = response.data.transactions;
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
      cbs_transaction_statuses,
      status_search,
      anchor_search,
      vendor_search,
      failed_date,
      invoice_number_search,
      product_type_search,
      per_page,
      sort_by,

      invoice_details,

      showInvoice,

      filter,
      refresh,

      resolvePaymentRequestStatus,
      changePage,

      getTaxPercentage,
      downloadReport,
      downloadPdf,
      resolveStatus,

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
