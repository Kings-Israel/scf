<template>
  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
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
        <div class=" table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class=" table-clear-btn">
          <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex align-items-end">
        <div style="margin-right: 10px">
          <select
            class="form-select"
            id="exampleFormControlSelect1"
            aria-label="Default select example"
            v-model="per_page"
          >
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
              <th>{{ $t('Reference Number') }}</th>
              <th>{{ $t('Anchor') }}</th>
              <th>{{ $t('Invoice No') }}.</th>
              <th>{{ $t('PI No') }}.</th>
              <th>{{ $t('Request Date') }}</th>
              <th>{{ $t('Maturity Date') }}</th>
              <th>{{ $t('PI Amount') }}</th>
              <th>{{ $t('Status') }}</th>
              <th>{{ $t('Actions') }}</th>
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
            <tr v-for="payment_request in finance_requests.data" :key="payment_request.id">
              <td
                data-bs-toggle="modal"
                :data-bs-target="'#payment-request-' + payment_request.id"
                class="text-primary text-decoration-underline px-1"
                style="cursor: pointer"
              >
                {{ payment_request.reference_number }}
              </td>
              <td>
                <span>{{ payment_request.anchor_name }}</span>
              </td>
              <td
                class="text-primary text-decoration-underline"
                @click="showInvoice(payment_request.invoice_id, 'show-details-btn-' + payment_request.invoice_id)"
                style="cursor: pointer"
              >
                {{ payment_request.invoice_number }}
              </td>
              <td
                class="text-primary text-decoration-underline"
                @click="showInvoice(payment_request.invoice_id, 'show-pi-btn-' + payment_request.invoice_id)"
                style="cursor: pointer"
              >
                {{ payment_request.invoice_number }}
              </td>
              <td>
                {{ moment(payment_request.payment_request_date).format('DD MMM YYYY') }}
              </td>
              <td>
                {{ moment(payment_request.due_date).format('DD MMM YYYY') }}
              </td>
              <td class="text-success">
                {{ payment_request.currency }}
                <span>
                  {{ new Intl.NumberFormat().format(payment_request.invoice_total_amount) }}
                </span>
              </td>
              <td>
                <span
                  v-if="payment_request.company_approvals.length > 0"
                  class="badge me-1 m_title"
                  :class="resolveRequestStatus(payment_request.company_approvals[0].status)"
                >
                  {{ payment_request.company_approvals[0].status }}
                </span>
                <span v-else class="badge me-1 m_title" :class="resolveRequestStatus(payment_request.approval_stage)">{{
                  payment_request.approval_stage
                }}</span>
              </td>
              <td>
                <button
                  class="d-none"
                  :id="'show-details-btn-' + payment_request.invoice_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-' + payment_request.invoice_id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-pi-btn-' + payment_request.invoice_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + payment_request.invoice_id"
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
                <i
                  class="ti ti-circle-check ti-sm text-success mx-1"
                  v-if="payment_request.requires_company_approval && payment_request.company_user_can_approve"
                  style="cursor: pointer"
                  data-bs-toggle="modal"
                  :data-bs-target="'#approve-payment-request-' + payment_request.id"
                  title="Approve Payment Request"
                ></i>
                <i
                  class="ti ti-square-x ti-sm text-danger mx-1"
                  v-if="payment_request.requires_company_approval && payment_request.company_user_can_approve"
                  style="cursor: pointer"
                  data-bs-toggle="modal"
                  :data-bs-target="'#reject-payment-request-' + payment_request.id"
                  title="Reject/Disapprove Payment Request"
                ></i>
              </td>
              <td>
                <div
                  class="modal fade"
                  :id="'approve-payment-request-' + payment_request.id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Company Status') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'approve-request-close-btn-' + payment_request.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <form @submit.prevent="updateApprovalStatus(payment_request.id, 'approved')" method="post">
                        <div class="modal-body">
                          <h4>{{ $t('Are you sure you want to approve this request') }}?</h4>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <div
                  class="modal fade"
                  :id="'reject-payment-request-' + payment_request.id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Company Status') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'reject-request-close-btn-' + payment_request.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <form @submit.prevent="updateApprovalStatus(payment_request.id, 'rejected')" method="post">
                        <div class="modal-body">
                          <div class="row mb-1">
                            <div class="form-group">
                              <label for="">{{ $t('Enter Rejection Reason') }}</label>
                              <textarea type="text" class="form-control" v-model="rejection_reason"></textarea>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
                <payment-instruction v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
                <div class="modal fade" :id="'payment-request-' + payment_request.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Payment Request') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <div class="col-6 my-1">{{ $t('Anchor') }}</div>
                          <div class="col-6">
                            <span>{{ payment_request.anchor_name }}</span>
                          </div>
                          <div class="col-6 my-1">{{ $t('Program Name') }}</div>
                          <div class="col-6">{{ payment_request.program_name }}</div>
                          <div class="col-6 my-1">{{ $t('Invoice / Unique Reference Number') }}</div>
                          <div class="col-6">{{ payment_request.invoice_number }}</div>
                          <div class="col-6 my-1">{{ $t('PI No') }}.:</div>
                          <div
                            class="col-6 text-primary"
                            style="cursor: pointer"
                            @click="
                              showInvoice(payment_request.invoice_id, 'show-pi-btn-' + payment_request.invoice_id)
                            "
                          >
                            {{ payment_request.pi_number }}
                          </div>
                          <div class="col-6 my-1">{{ $t('PI Amount') }}</div>
                          <div class="col-6 text-success">
                            {{ new Intl.NumberFormat().format(payment_request.invoice_total_amount) }}
                          </div>
                          <div class="col-6 my-1">{{ $t('Eligibility') }} (%)</div>
                          <div class="col-6">{{ payment_request.eligibility }}</div>
                          <div class="col-6 my-1">{{ $t('Eligible Payment Amount') }}</div>
                          <div class="col-6 text-success">
                            {{ new Intl.NumberFormat().format(payment_request.eligible_for_finance) }}
                          </div>
                          <div class="col-6 my-1">{{ $t('Requested Payment Amount') }}</div>
                          <div class="col-6 text-success">
                            {{ new Intl.NumberFormat().format(payment_request.drawdown_amount) }}
                          </div>
                          <div class="col-6 my-1">{{ $t('Credit to Account No') }}.:</div>
                          <div class="col-6">{{ payment_request.payment_accounts[0].account }}</div>
                          <div class="col-6 my-1">{{ $t('Request Date') }}</div>
                          <div class="col-6">{{ moment(payment_request.created_at).format('DD MMM YYYY') }}</div>
                          <div class="col-6 my-1">{{ $t('Requested Disbursement Date') }}</div>
                          <div class="col-6">
                            {{ moment(payment_request.payment_request_date).format('DD MMM YYYY') }}
                          </div>
                          <div class="col-6 my-1">{{ $t('Due Date') }}</div>
                          <div class="col-6">{{ moment(payment_request.due_date).format('DD MMM YYYY') }}</div>
                          <div class="col-6 my-1">{{ $t('Discount Rate') }} (%) ({{ $t('including Base Rate') }})</div>
                          <div class="col-6">{{ payment_request.discount_rate }}</div>
                          <div
                            class="col-12"
                            v-if="payment_request.payment_accounts && payment_request.payment_accounts.length > 0"
                          >
                            <div v-for="payment_account in payment_request.payment_accounts" :key="payment_account.id">
                              <div class="row" v-if="payment_request.discount_charge_type == 'Front Ended'">
                                <div class="col-6 my-1 d-flex" v-if="payment_account.type != 'Vendor Account'">
                                  <div class="">
                                    {{
                                      payment_account.title
                                        ? payment_account.title
                                        : payment_account.type.replaceAll('_', ' ')
                                    }}
                                  </div>
                                  <div class="" v-if="payment_account.description">
                                    ({{ payment_account.description }})
                                  </div>
                                </div>
                                <div class="col-6 text-success" v-if="payment_account.type != 'Vendor Account'">
                                  {{ new Intl.NumberFormat().format(Number(payment_account.amount).toFixed(2)) }}
                                </div>
                              </div>
                              <div class="row" v-else>
                                <div
                                  class="col-6 my-1 d-flex"
                                  v-if="
                                    payment_account.type != 'Vendor Account' &&
                                    !payment_request.discount_transactions.includes(payment_account.type)
                                  "
                                >
                                  <div class="">
                                    {{
                                      payment_account.title
                                        ? payment_account.title
                                        : payment_account.type.replaceAll('_', ' ')
                                    }}
                                  </div>
                                  <div class="" v-if="payment_account.description">
                                    ({{ payment_account.description }})
                                  </div>
                                </div>
                                <div
                                  class="col-6 text-success"
                                  v-if="
                                    payment_account.type != 'Vendor Account' &&
                                    !payment_request.discount_transactions.includes(payment_account.type)
                                  "
                                >
                                  {{ new Intl.NumberFormat().format(Number(payment_account.amount).toFixed(2)) }}
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-12" v-if="payment_request.fees && payment_request.fees.length > 0">
                            <div class="row" v-for="(fee, index) in payment_request.fees" :key="index">
                              <div class="col-6 my-1 m_title">
                                {{ fee.name.replaceAll('_', ' ') }}
                              </div>
                              <div class="col-6 text-success">
                                {{ new Intl.NumberFormat().format(Number(fee.value).toFixed(2)) }}
                              </div>
                            </div>
                          </div>
                          <div class="col-6 my-1">{{ $t('Net Disbursal Total') }}</div>
                          <div class="col-6 text-success">
                            {{ new Intl.NumberFormat().format(payment_request.amount.toFixed(2)) }}
                          </div>
                          <div class="col-6 my-1">{{ $t('Status') }}</div>
                          <div class="col-6">
                            <span
                              class="badge me-1 m_title"
                              :class="resolvePaymentRequestStatus(payment_request.approval_stage)"
                              >{{ payment_request.approval_stage }}</span
                            >
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                          {{ $t('Close') }}
                        </button>
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
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

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
    const eligibility_amount = ref(0);
    const invoice_details = ref(null);
    const eligibility = ref(0);
    const discount_amount = ref(0);
    const business_spread = ref(0);
    const toast = useToast();

    // Search Fields
    const anchor_search = ref('');
    const invoice_search = ref('');
    const status_search = ref('');

    // Pagination
    const per_page = ref(50);

    const status = ref('');
    const rejection_reason = ref('');

    const invoice_details_key = ref(0)

    const getFinanceRequests = async () => {
      await axios
        .get(base_url + 'planner/financing-requests/data', {
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
        case 'Pending Maker':
          style = 'bg-label-warning';
          break;
        case 'Pending Checker':
          style = 'bg-label-warning';
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
        default:
          style = 'bg-label-primary';
          break;
      }

      return style;
    };

    const getTotalAmount = invoice_items => {
      let amount = 0;
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity;
      });
      return amount;
    };

    const calculatePiAmount = invoice => {
      let deducts = 0;
      if (invoice.invoice_fees.length > 0) {
        invoice.invoice_fees.forEach(item => {
          deducts += item.amount;
        });
      }
      return deducts;
    };

    const showModal = invoice => {
      calculateEligibility(invoice);
      calculatePiAmount(invoice);
      calculateDiscount(invoice);
    };

    const getEligibility = payment_request => {
      let vendor_configurations = payment_request.vendor_configurations;

      return vendor_configurations.eligibility;
    };

    const calculateEligibility = invoice => {
      let eligibility = invoice.vendor_configurations.eligibility;
      let invoice_amount = getTotalAmount(invoice.invoice_items);
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });
      invoice_amount = invoice_amount + taxes;
      let invoice_discounts = 0;
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discounts += (item.value / 100) * invoice_amount;
        } else {
          invoice_discounts += item.value;
        }
      });
      invoice_amount = invoice_amount - invoice_discounts;
      eligibility_amount.value = (eligibility / 100) * (invoice_amount - deducts);
      return eligibility_amount.value.toFixed(2);
    };

    const calculateDiscount = invoice => {
      invoice_details.value = invoice;
      eligibility.value = invoice.vendor_configurations.eligibility;
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });

      let invoice_amount = getTotalAmount(invoice.invoice_items) + taxes;

      let invoice_discounts = 0;
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discounts += (item.value / 100) * invoice_amount;
        } else {
          invoice_discounts += item.value;
        }
      });
      invoice_amount = invoice_amount - invoice_discounts;
      invoice_details.value = invoice;
      let total_amount = invoice_amount - deducts;
      business_spread.value = invoice.vendor_discount_details.total_roi;

      discount_amount.value =
        (eligibility.value / 100) *
        total_amount *
        (invoice.vendor_discount_details.total_spread / 100) *
        ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365);
      return discount_amount.value.toFixed(2);
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          invoice_details_key.value++
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
        .get(base_url + 'planner/financing-requests/data', {
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
        .get(base_url + 'planner/financing-requests/data', {
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
        .get(page, {
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'planner/financing-requests/data', {
          params: {
            per_page: per_page
          }
        })
        .then(response => {
          finance_requests.value = response.data.finance_requests;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const updateApprovalStatus = (request, status) => {
      const formData = new FormData();
      formData.append('status', status);
      formData.append('rejection_reason', rejection_reason.value);
      axios.post(base_url + 'planner/financing-requests/' + request + '/status/update', formData).then(() => {
        toast.success('Request updated successfully');
        getFinanceRequests();
        if (status == 'rejected') {
          document.getElementById('reject-request-close-btn-' + request).click();
        } else {
          document.getElementById('approve-request-close-btn-' + request).click();
        }
      });
    };

    const getDiscountRate = payment_request => {
      let vendor_discount = payment_request.vendor_discount_details;

      return vendor_discount.total_roi;
    };

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
      showModal,
      clearSearch,
      filter,
      updateApprovalStatus,
      status,
      rejection_reason,
      showInvoice,
      getEligibility,
      getDiscountRate,
      resolvePaymentRequestStatus,
      invoice_details_key,
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
