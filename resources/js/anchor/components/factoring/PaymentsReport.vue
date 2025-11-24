<template>
  <div class="card p-1">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Buyer" class="form-label">{{ $t('Buyer') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Buyer"
            v-model="buyer_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}.</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Invoice No"
            v-model="invoice_number_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="PO No" class="form-label">{{ $t('PO No') }}.</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="PO No."
            v-model="po_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Due Date') }})</label>
          <input
            type="text"
            id="date_search"
            class="form-control form-search"
            name="daterange"
            placeholder="Select Dates"
          />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Disbursement Date') }})</label>
          <input
            type="text"
            id="disbursement_date_search"
            class="form-control form-search"
            name="daterange"
            placeholder="Select Dates"
          />
        </div>
        <div class="">
          <label for="Financing Status" class="form-label">{{ $t('Financing Status') }}</label>
          <select class="form-select form-search" v-model="financing_status_search">
            <option value="">{{ $t('Financing Status') }}</option>
            <option value="disbursed">{{ $t('Disbursed') }}</option>
            <option value="closed">{{ $t('Closed') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex align-items-end">
        <select class="form-select mx-2" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }}
        </button>
      </div>
    </div>
    <pagination
      v-if="payments.meta"
      :from="payments.meta.from"
      :to="payments.meta.to"
      :links="payments.meta.links"
      :next_page="payments.links.next"
      :prev_page="payments.links.prev"
      :total_items="payments.meta.total"
      :first_page_url="payments.links.first"
      :last_page_url="payments.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Buyer') }}</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('P.I Amount') }}</th>
            <th>{{ $t('Invoice Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Discount Amount') }}</th>
            <th>{{ $t('Fees/Charges') }}</th>
            <th>{{ $t('Total Charges') }}</th>
            <th>{{ $t('Disbursed Amount') }}</th>
            <th>{{ $t('Disbursement Date') }}</th>
            <th>{{ $t('Status') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!payments.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="payments.data && payments.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment in payments.data" :key="payment.id">
            <td
              class="text-primary text-decoration-underline"
              style="cursor: pointer"
              @click="showInvoice(payment.id, 'show-details-btn-' + payment.id)"
            >
              {{ payment.invoice_number }}
            </td>
            <td>{{ payment.buyer }}</td>
            <td class="text-success text-nowrap">
              {{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.total_amount) }}</span>
            </td>
            <td class="text-success text-nowrap">
              {{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.invoice_total_amount) }}</span>
            </td>
            <td>{{ moment(payment.invoice_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(payment.due_date).format('DD MMM YYYY') }}</td>
            <td class="text-success text-nowrap">
              {{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.discount) }}</span>
            </td>
            <td class="text-success text-nowrap">
              {{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.program_fees) }}</span>
            </td>
            <td class="text-success text-nowrap">
              {{ payment.currency }}
              <span>{{ new Intl.NumberFormat().format(payment.discount + payment.program_fees) }}</span>
            </td>
            <td class="text-success text-nowrap">
              {{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.disbursed_amount) }}</span>
            </td>
            <td>{{ moment(payment.disbursement_date).format('DD MMM YYYY') }}</td>
            <td>
              <span class="badge me-1 m_title" :class="resolveFinancingStatus(payment.financing_status)">{{
                payment.financing_status
              }}</span>
              <button
                class="d-none"
                :id="'show-details-btn-' + payment.id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + payment.id"
              ></button>
              <button
                class="d-none"
                :id="'show-pi-btn-' + payment.id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + payment.id"
              ></button>
              <button
                class="d-none"
                :id="'show-purchase-order-btn-' + payment.purchase_order_id"
                data-bs-toggle="modal"
                :data-bs-target="'#purchase-order-' + payment.purchase_order_id"
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
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="show_invoice_key" />
              <payment-instruction v-if="invoice_details" :invoice-details="invoice_details" :key="show_invoice_key" />
              <div
                v-if="purchase_order_details"
                class="modal fade"
                :id="'purchase-order-' + payment.purchase_order_id"
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
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.anchor.name }}</h6>
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
                            <span class="badge m_title" :class="resolveStatus(purchase_order_details.status)">{{
                              purchase_order_details.status
                            }}</span>
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
      v-if="payments.meta"
      :from="payments.meta.from"
      :to="payments.meta.to"
      :links="payments.meta.links"
      :next_page="payments.links.next"
      :prev_page="payments.links.prev"
      :total_items="payments.meta.total"
      :first_page_url="payments.links.first"
      :last_page_url="payments.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, watch, onMounted, inject, nextTick } from 'vue';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import axios from 'axios';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'PaymentsReport',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const payments = ref([]);
    const per_page = ref(50);
    const base_url = inject('baseURL');

    const invoice_number_search = ref('');
    const buyer_search = ref('');
    const po_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const from_disbursement_date_search = ref('');
    const to_disbursement_date_search = ref('');
    const status_search = ref('');
    const financing_status_search = ref('');

    const invoice_details = ref(null);
    const purchase_order_details = ref(null);

    const date_search = ref('');
    const disbursement_date_search = ref('');

    const show_invoice_key = ref(0);

    const getRequests = () => {
      axios
        .get(base_url + 'factoring/reports/payments-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          payments.value = response.data.payments;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          show_invoice_key.value++;
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
        .get(base_url + 'factoring/purchase-orders/' + purchase_order + '/show')
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

    onMounted(() => {
      getRequests();
    });

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'created':
          style = 'bg-label-secondary';
          break;
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'submitted':
          style = 'bg-label-primary';
          break;
        case 'approved':
          style = 'bg-label-secondary';
          break;
        case 'disbursed':
          style = 'bg-label-success';
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    watch(per_page, ([per_page]) => {
      axios
        .get(base_url + 'factoring/reports/payments-report', {
          params: {
            per_page: per_page,
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            po: po_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          payments.value = response.data.payments;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      disbursement_date_search.value = $('#disbursement_date_search').val();
      if (disbursement_date_search.value) {
        from_disbursement_date_search.value = moment(disbursement_date_search.value.split(' - ')[0]).format(
          'YYYY-MM-DD'
        );
        to_disbursement_date_search.value = moment(disbursement_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'factoring/reports/payments-report', {
          params: {
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            po: po_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          payments.value = response.data.payments;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      invoice_number_search.value = '';
      buyer_search.value = '';
      po_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      from_disbursement_date_search.value = '';
      to_disbursement_date_search.value = '';
      status_search.value = '';
      financing_status_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      $('#disbursement_date_search').val('');
      axios
        .get(base_url + 'factoring/reports/payments-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          payments.value = response.data.payments;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const exportReport = () => {
      axios
        .get(base_url + 'factoring/reports/report/factoring-payments-report/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            po: po_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payments_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportPdfReport = () => {
      axios
        .get(base_url + 'reports/factoring-payments-report/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            po: po_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `All_Invoices_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            buyer: buyer_search.value,
            invoice_number: invoice_number_search.value,
            po: po_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          payments.value = response.data.payments;
        });
    };

    return {
      moment,
      payments,
      per_page,
      invoice_number_search,
      buyer_search,
      po_search,
      from_date_search,
      to_date_search,
      from_disbursement_date_search,
      to_disbursement_date_search,
      status_search,
      financing_status_search,
      resolveStatus,
      resolveFinancingStatus,
      getTaxPercentage,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh,

      invoice_details,
      purchase_order_details,
      showInvoice,
      showPurchaseOrder,
      show_invoice_key
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
