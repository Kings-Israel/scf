<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="anchor" class="form-label">{{ $t('Buyer') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Buyer"
              v-model="anchor_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="invoice-number" class="form-label">{{ $t('Invoice Number') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Invoice No"
              v-model="invoice_number_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Pi Number" class="form-label">{{ $t('PI No') }}.</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="PI Number"
              v-model="pi_number_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Paid Date" class="form-label">{{ $t('Paid Date') }}</label>
            <input
              v-on:keyup.enter="filter"
              class="form-control form-search"
              type="date"
              value=""
              id="html5-date-input"
              v-model="paid_date_search"
            />
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
          </select>
          <!-- <button type="button" class="btn btn-primary mx-1" style="height: fit-content;" @click="exportInvoices"><i class='ti ti-download ti-sm'></i></button> -->
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
              <th>{{ $t('Invoice No') }}.</th>
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('PI No') }}</th>
              <th>{{ $t('Paid Date') }}</th>
              <th>{{ $t('Paid Amount') }}</th>
              <th>{{ $t('Actions') }}</th>
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
              <td>{{ invoice.buyer }}</td>
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
              <td class="text-primary">{{ invoice.pi_number }}</td>
              <td class="">
                {{ invoice.disbursement_date ? moment(invoice.disbursement_date).format('DD MMM YYYY') : '-' }}
              </td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>
                  {{ new Intl.NumberFormat().format(invoice.disbursed_amount) }}
                </span>
              </td>
              <td>
                <button
                  class="btn btn-sm btn-label-primary"
                  @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
                >
                  {{ $t('Details') }}
                </button>
                <div class="d-inline-block">
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
                    :id="'show-reject-btn-' + invoice.id"
                    data-bs-toggle="modal"
                    :data-bs-target="'#invoice-reject-' + invoice.id"
                  ></button>
                  <button
                    class="d-none"
                    :id="'show-approve-btn-' + invoice.id"
                    data-bs-toggle="modal"
                    :data-bs-target="'#invoice-approval-' + invoice.id"
                  ></button>
                  <button
                    class="d-none"
                    :id="'show-purchase-order-btn-' + invoice.purchase_order_id"
                    data-bs-toggle="modal"
                    :data-bs-target="'#purchase-order-' + invoice.purchase_order_id"
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
                </div>
                <invoice-details v-if="invoice_details" :invoice-details="invoice_details" />
                <payment-instruction v-if="invoice_details" :invoice-details="invoice_details" />
                <div
                  class="modal fade"
                  v-if="purchase_order_details"
                  :id="'purchase-order-' + invoice.purchase_order_id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Purchase Order') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="d-flex">
                          <a
                            v-if="purchase_order_details.attachment"
                            :href="attachment"
                            target="_blank"
                            class="btn btn-secondary mx-1"
                          >
                            <i class="ti ti-paperclip"></i> {{ $t('Attachment') }}</a
                          >
                        </div>
                      </div>
                      <div class="modal-body">
                        <div class="d-flex justify-content-between">
                          <div class="mb-3 w-50">
                            <span class="d-flex justify-content-between">
                              <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.company.name }}</h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.delivery_address }}</h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.remarks }}</h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="fw-light my-auto">{{ $t('Status') }}:</h5>
                              <span
                                class="badge m_title"
                                :class="resolveStatus(purchase_order_details.approval_stage)"
                                >{{ purchase_order_details.approval_stage }}</span
                              >
                            </span>
                            <span class="d-flex justify-content-between" v-if="purchase_order_details.rejection_reason">
                              <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.rejection_reason }}</h6>
                            </span>
                          </div>
                          <div class="mb-3">
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('PO No') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.purchase_order_number }}</h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('Start Date') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">
                                {{ moment(purchase_order_details.duration_from).format('DD MMM YYYY') }}
                              </h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('End Date') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">
                                {{ moment(purchase_order_details.duration_to).format('DD MMM YYYY') }}
                              </h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('PO Amount') }}:</h5>
                              <h6 class="fw-bold text-success mx-2 my-auto">
                                {{ purchase_order_details.currency }}
                                {{ new Intl.NumberFormat().format(purchase_order_details.total_amount) }}
                              </h6>
                            </span>
                          </div>
                        </div>
                        <div class="table-responsive">
                          <table class="table">
                            <thead>
                              <tr>
                                <th>{{ $t('Item') }}</th>
                                <th>{{ $t('Quantity') }}</th>
                                <th>{{ $t('Price Per Quantity') }}</th>
                                <th>{{ $t('Total') }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="item in purchase_order_details.purchase_order_items" :key="item.id">
                                <td>{{ item.item }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ item.price_per_quantity }}</td>
                                <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <div class="bg-label-secondary px-2">
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                            <h5 class="text-success my-auto py-1">
                              {{ new Intl.NumberFormat().format(purchase_order_details.total_amount) }}
                            </h5>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
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
import { ref, onMounted, watch, inject, nextTick } from 'vue';
import moment from 'moment';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'Payments',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);

    // Search Fields
    const anchor_search = ref('');
    const invoice_number_search = ref('');
    const paid_date_search = ref('');
    const pi_number_search = ref('');

    const invoice_details = ref(null);
    const purchase_order_details = ref(null);

    // Pagination
    const per_page = ref(50);

    const getInvoices = async () => {
      await axios
        .get(base_url + 'factoring/invoices/payments/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data;
        });
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending Maker':
          style = 'bg-label-primary';
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

    const showPurchaseOrder = async (purchase_order, modal) => {
      await axios
        .get(base_url + 'purchase-orders/' + purchase_order + '/show')
        .then(response => {
          purchase_order_details.value = response.data;
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

    const exportInvoices = () => {
      axios
        .get('factoring/invoices/payments/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            disbursement_date: paid_search.value,
            pi_number: pi_number_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payments_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getInvoices();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            disbursement_date: paid_search.value,
            pi_number: pi_number_search.value
          }
        })
        .then(response => {
          invoices.value = response.data;
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'factoring/invoices/payments/data', {
          params: {
            per_page: per_page
          }
        })
        .then(response => {
          invoices.value = response.data;
        });
    });

    const clearSearch = () => {
      anchor_search.value = '';
      invoice_number_search.value = '';
      paid_date_search.value = '';
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'factoring/invoices/payments/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'factoring/invoices/payments/data', {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            paid_date: paid_date_search.value,
            pi_number: pi_number_search.value
          }
        })
        .then(response => {
          invoices.value = response.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    return {
      moment,
      invoices,
      invoice_number_search,
      anchor_search,
      paid_date_search,
      pi_number_search,
      per_page,
      resolveStatus,
      resolveFinancingStatus,
      getTaxPercentage,
      exportInvoices,
      changePage,
      clearSearch,
      filter,
      invoice_details,
      purchase_order_details,
      showInvoice,
      showPurchaseOrder
    };
  }
};
</script>
<style scoped></style>
