<template>
  <div class="p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between p-0">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            placeholder="Anchor"
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
            placeholder="Invoice No"
            v-model="invoice_number_search"
            aria-describedby="defaultFormControlHelp"
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
            autocomplete="off"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Invoice Status') }}</label>
          <select class="form-select form-search select2" id="invoices-status-select" multiple v-model="status_search">
            <option value="pending_maker">{{ $t('Pending') }} ({{ $t('Maker') }})</option>
            <option value="pending_checker">{{ $t('Pending') }} ({{ $t('Checker') }})</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
            <option value="internal_reject">{{ $t('Internal Rejected') }}</option>
            <option value="past_due">{{ $t('Overdue') }}</option>
            <option value="expired">{{ $t('Expired') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Financing Status" class="form-label">{{ $t('Financing Status') }}</label>
          <select
            class="form-select form-search select2"
            id="financing-status-select"
            multiple
            v-model="financing_status_search"
          >
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="submitted">{{ $t('Submitted') }}</option>
            <option value="financed">{{ $t('Financed') }}</option>
            <option value="disbursed">{{ $t('Disbursed') }}</option>
            <option value="closed">{{ $t('Closed') }}</option>
            <option value="denied">{{ $t('Rejected') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Financing Status" class="form-label">{{ $t('Sort By') }}</label>
          <select class="form-select form-search" v-model="sort_by">
            <option value="">{{ $t('Sort By') }}</option>
            <option value="po_asc">{{ $t('PO No. (ASC)') }}</option>
            <option value="po_desc">{{ $t('PO No. (DESC)') }}</option>
            <option value="invoice_no_asc">{{ $t('Invoice No. (ASC)') }}</option>
            <option value="invoice_no_desc">{{ $t('Invoice No. (DESC)') }}</option>
            <option value="invoice_amount_asc">{{ $t('Invoice Amount. (ASC)') }}</option>
            <option value="invoice_amount_desc">{{ $t('Invoice Amount. (DESC)') }}</option>
            <option value="due_date_asc">{{ $t('Due Date. (ASC)') }}</option>
            <option value="due_date_desc">{{ $t('Due Date. (DESC)') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto" style="height: fit-content">
        <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button type="button" @click="exportInvoices" class="btn btn-primary">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
        </button>
        <a :href="'invoices/create'">
          <button type="button" class="btn btn-primary text-nowrap">
            <i class="ti ti-plus ti-xs"></i>{{ $t('Create Invoice') }}
          </button>
        </a>
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
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('PO') }}.</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Issue Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Invoice Status') }}</th>
            <th>{{ $t('Finance Status') }}</th>
            <th>{{ $t('Disb. Date') }}</th>
            <th>{{ $t('Disb. Amount') }}</th>
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
            <td>{{ invoice.anchor }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
            >
              <a href="javascript:;" class="dropdown-item">{{ invoice.invoice_number }}</a>
            </td>
            <td v-if="invoice.purchase_order_number" class="text-primary text-decoration-underline">
              <a
                href="javascript:;"
                @click="
                  showPurchaseOrder(invoice.purchase_order_id, 'show-purchase-order-btn-' + invoice.purchase_order_id)
                "
                >{{ invoice.purchase_order_number }}</a
              >
            </td>
            <td v-else>
              <span>-</span>
            </td>
            <td class="text-success text-nowrap">
              {{ invoice.currency }}
              <span v-if="invoice.status != 'created' && invoice.status != 'pending' && invoice.status != 'submitted'">
                {{
                  new Intl.NumberFormat().format(
                    invoice.total +
                      invoice.total_invoice_taxes -
                      invoice.total_invoice_fees -
                      invoice.total_invoice_discount
                  )
                }}
              </span>
              <span v-else>
                {{
                  new Intl.NumberFormat().format(
                    invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount
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
            <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(invoice.disbursed_amount) }}</td>
            <td class="">
              <div class="d-inline-block">
                <a
                  href="javascript(0)"
                  class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown"
                >
                  <i class="text-primary ti ti-dots-vertical"></i>
                </a>
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
                          <img src="../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
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
                  <li v-if="invoice.can_replicate">
                    <a :href="'invoices/create/' + invoice.id" class="dropdown-item badge bg-label-warning">{{
                      $t('Replicate')
                    }}</a>
                  </li>
                  <li v-if="!invoice.user_has_approved && (invoice.status == 'pending' || invoice.status == 'created')">
                    <a
                      href="javascript:;"
                      @click="requestApproval(invoice, 'approve')"
                      class="dropdown-item badge bg-label-success"
                      >{{ $t('Approve') }}</a
                    >
                  </li>
                  <li v-if="!invoice.user_has_approved && (invoice.status == 'pending' || invoice.status == 'created')">
                    <button
                      data-bs-toggle="modal"
                      :data-bs-target="'#invoice-reject-' + invoice.id"
                      class="dropdown-item badge bg-label-danger"
                    >
                      {{ $t('Reject') }}
                    </button>
                  </li>
                  <li v-if="invoice.can_delete">
                    <button
                      class="dropdown-item btn btn-sm badge bg-label-warning"
                      data-bs-toggle="modal"
                      :data-bs-target="'#delete-invoice-' + invoice.id"
                    >
                      {{ $t('Delete') }}
                    </button>
                  </li>
                </ul>
              </div>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
              <payment-instruction
                v-if="invoice_details"
                :invoice-details="invoice_details"
                :key="invoice_details_key"
              />
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'delete-invoice-' + invoice_details.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Delete Invoice') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        :id="'close-delete-invoice-modal-' + invoice_details.id"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex justify-content-between">
                        <h5>
                          {{ $t('Are you sure you want to delete the invoice') }} {{ invoice_details.invoice_number }}?
                        </h5>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button @click="deleteInvoice(invoice_details)" class="btn btn-danger">{{ $t('Delete') }}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal fade" :id="'invoice-reject-' + invoice.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Confirm Rejection') }}</h5>
                      <button
                        type="button"
                        :id="'close-reject-modal-' + invoice.id"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <form @submit.prevent="requestApproval(invoice, 'reject')">
                      <div class="modal-body">
                        <h6>{{ $t('Are you sure you want to reject the selected invoice') }}?</h6>
                        <label for="reason" class="form-label">{{ $t('Reason') }}</label>
                        <textarea
                          name=""
                          id=""
                          cols="3"
                          v-model="rejected_reason"
                          placeholder="Enter Rejection Reason"
                          class="form-control"
                        ></textarea>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-danger" type="submit">{{ $t('Confirm') }}</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
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
                        <div>
                          <a
                            :href="'purchase-orders/' + purchase_order_details.id + '/pdf/download'"
                            class="btn btn-primary"
                            ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                          >
                        </div>
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
                            <span class="badge m_title" :class="resolveStatus(purchase_order_details.approval_stage)">{{
                              purchase_order_details.approval_stage
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
</template>

<script>
import { computed, onMounted, ref, watch, inject, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from './partials/Pagination.vue';
import moment from 'moment';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'AllInvoices',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  props: ['can_replicate'],
  setup(props) {
    const can_replicate = props.can_replicate;
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);
    const eligibility = ref(0);
    const eligibility_amount = ref(0);
    const invoice_details = ref(null);
    const purchase_order_details = ref(null);
    const pi_amount = ref(0);
    const business_spread = ref(0);
    const discount_amount = ref(0);

    // Search Fields
    const anchor_search = ref('');
    const invoice_number_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const status_search = ref([]);
    const financing_status_search = ref([]);
    const date_search = ref('');
    const sort_by = ref('');

    const rejected_reason = ref('');

    const invoice_details_key = ref(0);

    // Pagination
    const per_page = ref(50);

    const getInvoices = async () => {
      await axios
        .get(base_url + 'invoices/data?per_page=' + per_page.value, {
          params: {
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'invoices/data', {
          params: {
            per_page: per_page,
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    });

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

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Overdue':
          style = 'bg-label-danger';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending Maker':
          style = 'bg-label-primary';
          break;
        case 'Pending Checker':
          style = 'bg-label-danger';
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
        case 'Rejected':
          style = 'bg-label-danger';
          break;
        case 'Internal Reject':
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
        case 'submitted':
          style = 'bg-label-warning';
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

    const requestApproval = async (invoice, status) => {
      const formData = new FormData();
      formData.append('status', status);
      formData.append('rejection_reason', rejected_reason.value);
      await axios
        .post(base_url + 'invoices/' + invoice.id + '/request/send', formData)
        .then(() => {
          toast.success('Invoice updated successfully');
          getInvoices();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    const exportInvoices = () => {
      axios
        .get(base_url + 'invoices/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Invoices_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const deleteInvoice = invoice => {
      axios
        .delete(base_url + 'invoices/' + invoice.id + '/delete')
        .then(response => {
          document.getElementById('close-delete-invoice-modal-' + invoice.id).click();
          toast.success(response.data.message);
          getInvoices();
        })
        .catch(err => {
          console.log(err);
          toast.error(err.response.data.message);
        });
    };

    onMounted(() => {
      getInvoices();
      $('#invoices-status-select').on('change', function () {
        var ids = $('#invoices-status-select').val();
        status_search.value = ids;
      });
      $('#financing-status-select').on('change', function () {
        var ids = $('#financing-status-select').val();
        financing_status_search.value = ids;
      });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    const clearSearch = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      anchor_search.value = '';
      invoice_number_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      status_search.value = '';
      financing_status_search.value = '';
      date_search.value = '';
      sort_by.value = '';
      $('#date_search').val('');
      $('#invoices-status-select').val('');
      $('#invoices-status-select').trigger('change');
      $('#financing-status-select').val('');
      $('#financing-status-select').trigger('change');
      await axios
        .get(base_url + 'invoices/data', {
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
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'invoices/data', {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    return {
      can_replicate,
      moment,
      invoices,
      eligibility,
      eligibility_amount,
      business_spread,
      discount_amount,
      invoice_details,
      pi_amount,
      anchor_search,
      invoice_number_search,
      from_date_search,
      to_date_search,
      status_search,
      financing_status_search,
      sort_by,
      per_page,
      rejected_reason,
      purchase_order_details,
      deleteInvoice,
      resolveStatus,
      resolveFinancingStatus,
      requestApproval,
      changePage,
      getTaxPercentage,
      exportInvoices,
      clearSearch,
      filter,
      showInvoice,
      showPurchaseOrder,
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
