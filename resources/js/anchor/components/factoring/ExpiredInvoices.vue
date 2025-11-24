<template>
  <div class="card">
    <div class="card-body p-2">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Buyer') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              v-model="buyer_search"
              placeholder="Buyer"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Invoice Number') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              v-model="invoice_number_search"
              placeholder="Invoice No"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Status') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
              <option value="">{{ $t('Status') }}</option>
              <option value="pending">{{ $t('Pending') }}</option>
              <option value="submitted">{{ $t('Submitted') }}</option>
              <option value="approved">{{ $t('Approved') }}</option>
              <option value="denied">{{ $t('Denied') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Financing Status') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="finance_status_search">
              <option value="">{{ $t('Finance Status') }}</option>
              <option value="pending">{{ $t('Pending') }}</option>
              <option value="financed">{{ $t('Financed') }}</option>
              <option value="closed">{{ $t('Closed') }}</option>
              <option value="denied">{{ $t('Denied') }}</option>
            </select>
          </div>
          <div class=" table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class=" table-clear-btn">
            <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex mx-2" style="height: fit-content">
          <select class="form-select mx-2" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
      <pagination
        v-if="invoices.meta"
        :from="invoices.meta.from"
        :to="invoices.meta.to"
        :links="invoices.meta.links"
        :next_page="invoices.links.next"
        :prev_page="invoices.links.prev"
        :total_items="invoices.meta.total"
        :first_page_url="invoices.links.first"
        :last_page_url="invoices.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>{{ $t('Buyer') }}</th>
              <th>{{ $t('Invoice No.') }}</th>
              <th>{{ $t('Invoice Amnt') }}</th>
              <th>{{ $t('Issue Date') }}</th>
              <th>{{ $t('Due Date') }}</th>
              <th>{{ $t('Invoice Status') }}</th>
              <th>{{ $t('Finance Status') }}</th>
              <th>{{ $t('Disbursement Date') }}</th>
              <th>{{ $t('Disb Amnt') }}</th>
              <th>{{ $t('Actions') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!invoices.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="invoices.data && invoices.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-show="invoices.data.length > 0" v-for="invoice in invoices.data" :key="invoice.id">
              <td v-if="invoice.buyer">{{ invoice.buyer }}</td>
              <td v-else>{{ invoice.company }}</td>
              <td
                class="text-primary text-decoration-underline"
                @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
              >
                <a href="javascript:;" class="dropdown-item">{{ invoice.invoice_number }}</a>
              </td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>
                  {{
                    new Intl.NumberFormat().format(
                      invoice.total +
                        invoice.total_invoice_taxes -
                        invoice.total_invoice_fees -
                        invoice.total_invoice_discount
                    )
                  }}
                </span>
              </td>
              <td class="">{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
              <td class="">{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
              <td>
                <span class="badge me-1 m_title" :class="resolveStatus(invoice.approval_stage)">
                  {{ invoice.approval_stage }}
                </span>
              </td>
              <td>
                <span class="badge me-1 m_title" :class="resolveFinancingStatus(invoice.financing_status)">
                  {{ invoice.financing_status }}
                </span>
              </td>
              <td class="">
                {{ invoice.disbursement_date ? moment(invoice.disbursement_date).format('DD MMM YYYY') : '-' }}
              </td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>
                  {{ new Intl.NumberFormat().format(invoice.disbursed_amount) }}
                </span>
              </td>
              <td class="">
                <div class="d-inline-block">
                  <a
                    href="javascript(0)"
                    class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown"
                  >
                    <i class="text-primary ti ti-dots-vertical"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end m-0">
                    <li>
                      <a
                        href="javascript:;"
                        class="dropdown-item badge bg-label-primary"
                        @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
                        >{{ $t('Details') }}</a
                      >
                    </li>
                    <li v-if="invoice.can_edit">
                      <a :href="'invoices/' + invoice.id + '/edit'" class="dropdown-item badge bg-label-info">{{
                        $t('Edit')
                      }}</a>
                    </li>
                    <li v-if="!invoice.buyer">
                      <a :href="'invoices/create/' + invoice.id" class="dropdown-item badge bg-label-warning">{{
                        $t('Replicate')
                      }}</a>
                    </li>
                  </ul>
                </div>
              </td>
              <td>
                <button
                  class="d-none"
                  :id="'show-details-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-pi-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + invoice.id"
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
                          <img src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <invoice-details v-if="invoice_details" :invoice-details="invoice_details" />
                <payment-instruction v-if="invoice_details" :invoice-details="invoice_details" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="invoices.meta"
        :from="invoices.meta.from"
        :to="invoices.meta.to"
        :links="invoices.meta.links"
        :next_page="invoices.links.next"
        :prev_page="invoices.links.prev"
        :total_items="invoices.meta.total"
        :first_page_url="invoices.links.first"
        :last_page_url="invoices.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'ExpiredInvoices',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);
    const eligibility = ref(0);
    const eligibility_amount = ref(0);
    const invoice_details = ref(null);
    const pi_amount = ref(0);
    const business_spread = ref(0);
    const discount_amount = ref(0);

    // Search fields
    const buyer_search = ref('');
    const invoice_number_search = ref('');
    const status_search = ref('');
    const finance_status_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getInvoices = async () => {
      await axios.get(base_url + 'factoring/invoices/expired/data?per_page=' + per_page.value).then(response => {
        invoices.value = response.data.invoices;
      });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
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

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Overdue':
          style = 'bg-label-secondary';
          break;
        case 'Created':
          style = 'bg-label-secondary';
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

    const resolveFinancingStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'financed':
          style = 'bg-label-success';
          break;
        case 'closed':
          style = 'bg-label-secondary';
          break;
        case 'denied':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    const requestApproval = async invoice => {
      await axios
        .get(base_url + 'factoring/invoices/' + invoice.id + '/request/send')
        .then(() => {
          toast.success('Invoice sent successfully');
          getInvoices();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return (tax_amount / invoice_amount) * 100;
    };

    onMounted(() => {
      getInvoices();
    });

    const clearSearch = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      buyer_search.value = '';
      invoice_number_search.value = '';
      status_search.value = '';
      finance_status_search.value = '';
      await axios
        .get(base_url + 'factoring/invoices/expired/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'factoring/invoices/expired/data', {
          params: {
            buyer: buyer_search.value,
            invoice_number: invoice_number_search.value,
            status: status_search.value,
            finance_status: finance_status_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            buyer: buyer_search.value,
            invoice_number: invoice_number_search.value,
            status: status_search.value,
            finance_status: finance_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'factoring/invoices/expired/data', {
          params: {
            buyer: buyer_search.value,
            invoice_number: invoice_number_search.value,
            status: status_search.value,
            finance_status: finance_status_search.value,
            per_page: per_page
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    });

    return {
      moment,
      invoices,
      eligibility,
      eligibility_amount,
      business_spread,
      discount_amount,
      invoice_details,
      pi_amount,

      // Search fields
      buyer_search,
      invoice_number_search,
      status_search,
      finance_status_search,
      // Pagination
      per_page,

      resolveStatus,
      resolveFinancingStatus,
      requestApproval,
      changePage,
      getTaxPercentage,
      showInvoice,

      filter,
      clearSearch
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
