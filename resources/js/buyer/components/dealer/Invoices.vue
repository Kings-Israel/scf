<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              v-model="anchor"
              placeholder="Anchor"
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
              v-model="invoice_number"
              placeholder="Invoice No"
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
            />
          </div>
          <div class="">
            <label for="Status" class="form-label">{{ $t('Invoice Status') }}</label>
            <select class="form-select form-search select2" id="invoice-status-select" multiple v-model="status">
              <option value="approved">{{ $t('Approved') }}</option>
              <option value="past_due">{{ $t('Overdue') }}</option>
              <option value="expired">{{ $t('Expired') }}</option>
              <option value="rejected">{{ $t('Rejected') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Financing Status" class="form-label">{{ $t('Financing Status') }}</label>
            <select
              class="form-select form-search select2"
              id="financing-status-select"
              multiple
              v-model="financing_status"
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
              <option value="due_date_asc">{{ $t('Due Date. (ASC)') }}</option>
              <option value="due_date_desc">{{ $t('Due Date. (DESC)') }}</option>
              <option value="invoice_amount_asc">{{ $t('Invoice Amount. (ASC)') }}</option>
              <option value="invoice_amount_desc">{{ $t('Invoice Amount. (DESC)') }}</option>
            </select>
          </div>
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex mx-2" style="height: fit-content">
          <select class="form-select mx-2" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
          <button type="button" @click="exportReport" style="height: fit-content" class="btn btn-primary mx-1">
            <i class="ti ti-download ti-xs px-1"></i> Excel
          </button>
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
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('Accrued Amount') }}</th>
              <th>{{ $t('Invoice Date') }}</th>
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
              <td class="text-primary text-decoration-underline">
                <a href="javascript:;" @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)">{{
                  invoice.invoice_number
                }}</a>
              </td>
              <td class="text-success">
                {{ invoice.currency }} {{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}
              </td>
              <td class="text-success">
                {{ invoice.currency }} {{ new Intl.NumberFormat().format(invoice.discount) }}
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
                {{ invoice.currency }}.{{ new Intl.NumberFormat().format(invoice.disbursed_amount) }}
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
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a
                        href="javascript:;"
                        @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
                        class="dropdown-item btn btn-sm btn-label-primary mt-1"
                        >{{ $t('Details') }}</a
                      >
                    </li>
                    <li
                      v-if="
                        invoice.status == 'approved' && invoice.payment_requests <= 0 && invoice.eligible_for_financing
                      "
                    >
                      <a
                        :href="'invoices/initiate-drawdown/' + invoice.id"
                        class="dropdown-item btn btn-sm btn-label-success mb-1"
                        >{{ $t('Request Finance') }}</a
                      >
                    </li>
                    <li v-if="invoice.status == 'pending' || invoice.status == 'created'">
                      <a href="javascript:;" @click="requestApproval(invoice)" class="dropdown-item mb-1">{{
                        $t('Request Approval')
                      }}</a>
                    </li>
                  </ul>
                </div>
              </td>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
              <payment-instruction
                v-if="invoice_details"
                :invoice-details="invoice_details"
                :key="invoice_details_key"
              />
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
import { computed, onMounted, ref, inject, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'AllInvoices',
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

    const anchor = ref('');
    const invoice_number = ref('');
    const status = ref([]);
    const financing_status = ref([]);
    const invoice_date = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const date_search = ref('');

    const per_page = ref(50);

    const invoice_details_key = ref(0);

    const getInvoices = async () => {
      await axios
        .get(base_url + 'invoices/data', {
          params: {
            anchor: anchor.value,
            invoice_number: invoice_number.value,
            status: status.value,
            finance_status: financing_status.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
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

    const requestApproval = async invoice => {
      await axios
        .get(base_url + 'invoices/' + invoice.id + '/request/send')
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'invoices/data', {
          params: {
            anchor: anchor.value,
            invoice_number: invoice_number.value,
            status: status.value,
            finance_status: financing_status.value,
            per_page: per_page,
            from_date: from_date_search.value,
            to_date: to_date_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    });

    const filter = () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'invoices/data', {
          params: {
            anchor: anchor.value,
            invoice_number: invoice_number.value,
            status: status.value,
            finance_status: financing_status.value,
            per_page: per_page.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      anchor.value = '';
      invoice_number.value = '';
      status.value = [];
      financing_status.value = [];
      from_date_search.value = '';
      to_date_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      $('#invoices-status-select').val('');
      $('#invoices-status-select').trigger('change');
      $('#financing-status-select').val('');
      $('#financing-status-select').trigger('change');
      axios
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

    onMounted(() => {
      getInvoices();
      $('#invoice-status-select').on('change', function () {
        var ids = $('#invoice-status-select').val();
        status.value = ids;
      });
      $('#financing-status-select').on('change', function () {
        var ids = $('#financing-status-select').val();
        financing_status.value = ids;
      });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            anchor: anchor.value,
            invoice_number: invoice_number.value,
            status: status.value,
            finance_status: financing_status.value,
            per_page: per_page.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    const exportReport = () => {
      let parent = $('.ti-download').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + 'invoices/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor.value,
            invoice_number: invoice_number.value,
            status: status.value,
            finance_status: financing_status.value,
            per_page: per_page.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value
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
        })
        .finally(() => {
          parent.html('<i class="ti ti-download"></i>');
        });
    };

    return {
      moment,
      invoices,
      eligibility,
      eligibility_amount,
      business_spread,
      discount_amount,
      invoice_details,
      pi_amount,

      per_page,
      anchor,
      invoice_number,
      status,
      financing_status,
      invoice_date,
      from_date_search,
      to_date_search,

      filter,
      refresh,

      resolveStatus,
      resolveFinancingStatus,
      requestApproval,
      changePage,
      getTaxPercentage,
      showInvoice,
      exportReport
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
