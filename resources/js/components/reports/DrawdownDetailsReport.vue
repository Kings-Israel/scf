<template>
  <div class="card p-3">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Invoice Number"
            v-model="invoice_number_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Dealer') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Dealer"
            v-model="vendor_search"
            aria-describedby="defaultFormControlHelp"
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
            v-model="anchor_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Drawdown Date" class="form-label">{{ $t('Drawdown Date') }}</label>
          <input
            type="date"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="payment_request_date"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('DPD Days') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="dpd">
            <option value="">{{ $t('DPD Days') }}</option>
            <option value="1-30">1-30 {{ $t('Days') }}</option>
            <option value="31-60">31-60 {{ $t('Days') }}</option>
            <option value=">60">> 60 {{ $t('Days') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="submitted">{{ $t('Submitted') }}</option>
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="disbursed">{{ $t('Dibsursed') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
            <option value="closed">{{ $t('Closed') }}</option>
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
        <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <download-buttons
          @download-report="downloadReport"
          @download-pdf="downloadPdf"
          :downloading-excel="downloadingExcel"
          :downloading-pdf="downloadingPdf"
        ></download-buttons>
      </div>
    </div>
    <pagination
      v-if="requests.meta"
      :from="requests.meta.from"
      :to="requests.meta.to"
      :links="requests.meta.links"
      :next_page="requests.links.next"
      :prev_page="requests.links.prev"
      :total_items="requests.meta.total"
      :first_page_url="requests.links.first"
      :last_page_url="requests.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th class="">{{ $t('Invoice No') }}.</th>
            <th class="px-2">{{ $t('Anchor') }}</th>
            <th class="px-2">{{ $t('Dealer') }}</th>
            <th class="px-1">{{ $t('Drawdown Date') }}</th>
            <th class="px-1">{{ $t('Drawdown Amount') }}</th>
            <th class="px-1">{{ $t('Principle Balance') }}</th>
            <th class="px-1">{{ $t('Total Outstanding') }}</th>
            <th class="px-1">{{ $t('Principle DPD') }}</th>
            <th class="px-1">{{ $t('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!requests.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="requests.data && requests.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment_request in requests.data" :key="payment_request.id" class="text-nowrap">
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(payment_request.invoice_id, 'show-details-btn-' + payment_request.invoice_id)"
            >
              {{ payment_request.invoice_number }}
            </td>
            <td class="text-primary text-decoration-underline">
              <a :href="'../companies/' + payment_request.anchor_id + '/details'">
                {{ payment_request.anchor_name }}
              </a>
            </td>
            <td class="text-primary text-decoration-underline">
              <a :href="'../companies/' + payment_request.company_id + '/details'">
                {{ payment_request.company_name }}
              </a>
            </td>
            <td class="px-1">{{ moment(payment_request.payment_request_date).format(date_format) }}</td>
            <td class="text-success">
              {{ payment_request.currency }} {{ new Intl.NumberFormat().format(payment_request.amount) }}
            </td>
            <td class="text-success">
              {{ payment_request.currency }}
              {{ new Intl.NumberFormat().format(payment_request.invoice_amount - payment_request.invoice_paid_amount) }}
            </td>
            <td class="text-success">
              {{ payment_request.currency }}
              {{ new Intl.NumberFormat().format(payment_request.invoice_balance) }}
            </td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(payment_request.invoice_days_past_due) }}
            </td>
            <td class="px-1">
              <span class="badge me-1 m_title" :class="resolvePaymentRequestStatus(payment_request.status)">{{
                payment_request.status
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
              <div class="modal fade" :id="'payment-request-' + payment_request.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Payment Request') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex"></div>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <div class="col-6 my-1">{{ $t('Dealer') }}</div>
                        <div class="col-6">
                          <a
                            :href="'../companies/' + payment_request.company_id + '/details'"
                            class="fw-bold my-auto"
                            >{{ payment_request.company_name }}</a
                          >
                        </div>
                        <div class="col-6 my-1">{{ $t('OD Account No') }}:</div>
                        <div class="col-6">
                          {{ payment_request.payment_account_number }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Anchor') }}</div>
                        <div class="col-6">
                          <a :href="'../companies/' + payment_request.anchor_id + '/details'" class="fw-bold my-auto">{{
                            payment_request.anchor_name
                          }}</a>
                        </div>
                        <div class="col-6 my-1">{{ $t('Program Name') }}</div>
                        <div class="col-6">{{ payment_request.program_name }}</div>
                        <div class="col-6 my-1">{{ $t('Invoice / Unique Reference Number') }}</div>
                        <div class="col-6">{{ payment_request.invoice_number }}</div>
                        <div class="col-6 my-1">{{ $t('PI No') }}.:</div>
                        <div
                          class="col-6 text-primary"
                          style="cursor: pointer"
                          @click="showInvoice(payment_request.invoice_id, 'show-pi-btn-' + payment_request.invoice_id)"
                        >
                          {{ payment_request.pi_number }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Invoice Amount') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.invoice_total_amount) }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Eligibility') }} (%)</div>
                        <div class="col-6">{{ payment_request.eligibility }}</div>
                        <div class="col-6 my-1">{{ $t('Eligible Drawdown Amount') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.eligible_for_finance) }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Requested Drawdown Amount') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.drawdown_amount) }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Credit to Account No') }}.:</div>
                        <div class="col-6">{{ payment_request.payment_accounts[0].account }}</div>
                        <div class="col-6 my-1">{{ $t('Request Date') }}</div>
                        <div class="col-6">{{ moment(payment_request.created_at).format(date_format) }}</div>
                        <div class="col-6 my-1">{{ $t('Requested Disbursement Date') }}</div>
                        <div class="col-6">{{ moment(payment_request.payment_request_date).format(date_format) }}</div>
                        <div class="col-6 my-1">{{ $t('Discount Rate') }} (%) ({{ $t('including Base Rate') }})</div>
                        <div class="col-6">{{ payment_request.discount_rate }}</div>
                        <div
                          class="col-12"
                          v-if="payment_request.payment_accounts && payment_request.payment_accounts.length > 0"
                        >
                          <div
                            class="row"
                            v-for="payment_account in payment_request.payment_accounts"
                            :key="payment_account.id"
                          >
                            <div class="col-6 my-1 m_title" v-if="payment_account.type != 'vendor_account'">
                              {{
                                payment_account.title
                                  ? payment_account.title
                                  : payment_account.type.replaceAll('_', ' ')
                              }}
                              <span class="m_title" v-if="payment_account.description"
                                >({{ payment_account.description }})</span
                              >
                            </div>
                            <div class="col-6 text-success" v-if="payment_account.type != 'vendor_account'">
                              {{ new Intl.NumberFormat().format(Number(payment_account.amount).toFixed(2)) }}
                            </div>
                          </div>
                        </div>
                        <div class="col-6 my-1">{{ $t('Net Disbursal Total') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.amount.toFixed(2)) }}
                        </div>
                        <div class="col-6">{{ $t('Status') }}</div>
                        <div class="col-6">
                          <span
                            class="badge me-1 m_title"
                            :class="resolvePaymentRequestStatus(payment_request.status)"
                            >{{ payment_request.status }}</span
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
              <div class="modal fade" :id="'payment-accounts-' + payment_request.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Accounts') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="table-responsive">
                        <table class="table">
                          <thead>
                            <tr>
                              <th>{{ $t('Account') }}</th>
                              <th>{{ $t('Invoice') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="account in payment_request.payment_accounts" :key="account.id">
                              <td>{{ account.account }}</td>
                              <td>{{ account.invoice_number }}</td>
                            </tr>
                          </tbody>
                        </table>
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
      v-if="requests.meta"
      :from="requests.meta.from"
      :to="requests.meta.to"
      :links="requests.meta.links"
      :next_page="requests.links.next"
      :prev_page="requests.links.prev"
      :total_items="requests.meta.total"
      :first_page_url="requests.links.first"
      :last_page_url="requests.links.last"
      @change-page="changePage"
    ></pagination>
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
  name: 'DrawdownDetails',
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
    const requests = ref([]);

    // Search fields
    const payment_reference_number_search = ref('');
    const invoice_number_search = ref('');
    const status_search = ref('');
    const vendor_search = ref('');
    const anchor_search = ref('');
    const payment_request_date = ref('');
    const dpd = ref('');
    const sort_by = ref('');

    // Pagination
    const per_page = ref(50);

    const invoice_details = ref(null);

    const invoice_details_key = ref(0);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'drawdown-details-report'
          }
        })
        .then(response => {
          requests.value = response.data;
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'drawdown-details-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          requests.value = response.data;
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
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            dpd: dpd.value,
            payment_request_date: payment_request_date.value,
            type: 'drawdown-details-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          requests.value = response.data;
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
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      payment_reference_number_search.value = '';
      invoice_number_search.value = '';
      vendor_search.value = '';
      anchor_search.value = '';
      status_search.value = '';
      dpd.value = '';
      payment_request_date.value = '';
      sort_by.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'drawdown-details-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          requests.value = response.data;
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
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            dpd: dpd.value,
            payment_request_date: payment_request_date.value,
            type: 'drawdown-details-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_drawdown_details_Report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: 'drawdown-details-report',
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `DF_drawdown_details_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    onMounted(() => {
      getData();
    });

    const getEligibility = payment_request => {
      let vendor_configurations = payment_request.invoice.vendor_configurations;

      return vendor_configurations.eligibility;
    };

    const getDiscountRate = payment_request => {
      let vendor_discount = payment_request.invoice.vendor_discount_details;

      return vendor_discount.total_roi;
    };

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'created':
          style = 'bg-label-primary';
          break;
        case 'pending approval':
          style = 'bg-label-secondary';
          break;
        case 'approved':
          style = 'bg-label-success';
          break;
        case 'paid':
          style = 'bg-label-success';
          break;
        case 'failed':
          style = 'bg-label-danger';
          break;
        case 'rejected':
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
          style = 'bg-label-primary';
          break;
        case 'disbursed':
          style = 'bg-label-primary';
          break;
        case 'closed':
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'drawdown-details-report',
            per_page: per_page.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            dpd: dpd.value,
            payment_request_date: payment_request_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
        });
    };

    const getTaxPercentage = (invoice_number, invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
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
      requests,

      // Search fields
      payment_reference_number_search,
      invoice_number_search,
      status_search,
      anchor_search,
      vendor_search,
      dpd,
      payment_request_date,
      sort_by,

      // Pagination
      per_page,

      invoice_details,

      showInvoice,

      filter,
      refresh,

      resolvePaymentRequestStatus,
      changePage,
      resolveFinancingStatus,
      getEligibility,
      getDiscountRate,
      downloadReport,
      downloadPdf,
      getTaxPercentage,
      resolveStatus,

      date_format,
      downloadingExcel,
      downloadingPdf,
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
