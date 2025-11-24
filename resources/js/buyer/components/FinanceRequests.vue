<template>
  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Vendor"
            v-model="anchor_search"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Invoice No"
            v-model="invoice_search"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select
            class="form-select form-search"
            v-model="status_search"
            id="exampleFormControlSelect1"
            aria-label="Default select example"
          >
            <option value="">{{ $t('Status') }}</option>
            <option value="created">{{ $t('Created') }}</option>
            <option value="pending">{{ $t('Submitted') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="paid">{{ $t('Paid') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-1 justify-content-end mt-auto">
        <div>
          <select class="form-select" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-body">
      <pagination
        v-if="finance_requests.meta"
        class="mx-2"
        :from="finance_requests.meta.from"
        :to="finance_requests.meta.to"
        :links="finance_requests.meta.links"
        :next_page="finance_requests.links.next"
        :prev_page="finance_requests.links.prev"
        :total_items="finance_requests.meta.total"
        :first_page_url="finance_requests.links.first"
        :last_page_url="finance_requests.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="">
              <th>{{ $t('Vendor') }}</th>
              <th>{{ $t('PI No') }}.</th>
              <th>{{ $t('Invoice No') }}.</th>
              <th>{{ $t('Requested Payment Date') }}</th>
              <th>{{ $t('Due Date') }}</th>
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('PI Amount') }}</th>
              <th>{{ $t('Status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr v-if="!finance_requests.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="finance_requests.data && finance_requests.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="invoice in finance_requests.data" :key="invoice.id">
              <td>
                <span>{{ invoice.anchor }}</span>
              </td>
              <td
                class="text-primary text-decoration-underline"
                @click="showInvoice(invoice.id, 'show-pi-btn-' + invoice.id)"
                style="cursor: pointer"
              >
                {{ invoice.pi_number }}
              </td>
              <td
                class="text-primary text-decoration-underline"
                @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
                style="cursor: pointer"
              >
                {{ invoice.invoice_number }}
              </td>
              <td>
                {{ moment(invoice.payment_request_date).format('DD MMM YYYY') }}
              </td>
              <td>
                {{ moment(invoice.due_date).format('DD MMM YYYY') }}
              </td>
              <td class="text-success text-nowrap">
                {{ invoice.currency }}
                <span>
                  {{ new Intl.NumberFormat().format(invoice.total) }}
                </span>
              </td>
              <td class="text-success text-nowrap">
                {{ invoice.currency }}
                <span>
                  {{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}
                </span>
              </td>
              <td>
                <span class="badge me-1 m_title" :class="resolveRequestStatus(invoice.financing_status)">
                  {{ invoice.financing_status }}
                </span>
              </td>
              <td>
                <button
                  class="d-none"
                  :id="'show-pi-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-details-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-' + invoice.id"
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
        v-if="finance_requests.meta"
        class="mx-2"
        :from="finance_requests.meta.from"
        :to="finance_requests.meta.to"
        :links="finance_requests.meta.links"
        :next_page="finance_requests.links.next"
        :prev_page="finance_requests.links.prev"
        :total_items="finance_requests.meta.total"
        :first_page_url="finance_requests.links.first"
        :last_page_url="finance_requests.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>
<script>
import { computed, onMounted, ref, watch, inject, nextTick } from 'vue';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import { useToast } from 'vue-toastification';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'FinanceRequests',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup() {
    const base_url = inject('baseURL');
    const finance_requests = ref([]);
    const invoice_details = ref(null);

    // Search Fields
    const anchor_search = ref('');
    const invoice_search = ref('');
    const status_search = ref('');

    // Pagination
    const per_page = ref(50);

    const status = ref('');
    const rejection_reason = ref('');

    const invoice_details_key = ref(0);

    const getFinanceRequests = async () => {
      await axios
        .get(base_url + 'payment-instructions/data', {
          params: {
            anchor: anchor_search.value,
            invoice: invoice_search.value,
            status: status_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          finance_requests.value = response.data.finance_requests;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getFinanceRequests();
    });

    const resolveRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'submitted':
          style = 'bg-label-secondary';
          break;
        case 'financed':
          style = 'bg-label-success';
          break;
        case 'denied':
          style = 'bg-label-danger';
          break;
        case 'Pending Maker':
          style = 'bg-label-primary';
          break;
        case 'Pending Checker':
          style = 'bg-label-danger';
          break;
        case 'Overdue':
          style = 'bg-label-danger';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending':
          style = 'bg-label-secondary';
          break;
        case 'Approved':
          style = 'bg-label-success';
          break;
        case 'Paid':
          style = 'bg-label-success';
          break;
        case 'Failed':
          style = 'bg-label-danger';
          break;
        case 'Rejected':
          style = 'bg-label-danger';
          break;
        case 'Past Due':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }

      return style;
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    const clearSearch = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      anchor_search.value = '';
      invoice_search.value = '';
      status_search.value = '';
      await axios
        .get(base_url + 'payment-instructions/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          finance_requests.value = response.data.finance_requests;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'payment-instructions/data', {
          params: {
            anchor: anchor_search.value,
            invoice: invoice_search.value,
            status: status_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          finance_requests.value = response.data.finance_requests;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            anchor: anchor_search.value,
            invoice: invoice_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          finance_requests.value = response.data.finance_requests;
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'payment-instructions/data', {
          params: {
            per_page: per_page,
            anchor: anchor_search.value,
            invoice: invoice_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          finance_requests.value = response.data.finance_requests;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'Pending Maker':
          style = 'bg-label-primary';
          break;
        case 'Pending Checker':
          style = 'bg-label-warning';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending':
          style = 'bg-label-secondary';
          break;
        case 'Approved':
          style = 'bg-label-success';
          break;
        case 'Paid':
          style = 'bg-label-success';
          break;
        case 'Failed':
          style = 'bg-label-danger';
          break;
        case 'Rejected':
          style = 'bg-label-danger';
          break;
        case 'Past Due':
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
      finance_requests,

      // Search fields
      anchor_search,
      invoice_search,
      status_search,

      // Pagination
      per_page,

      getTaxPercentage,
      invoice_details,
      resolveRequestStatus,
      changePage,
      clearSearch,
      filter,
      status,
      rejection_reason,
      showInvoice,
      resolvePaymentRequestStatus,
      invoice_details_key
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
