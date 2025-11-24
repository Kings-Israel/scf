<template>
  <div class="card p-1">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Buyer') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="buyer_search"
            placeholder="Buyer"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice Number') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="invoice_number_search"
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
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="pending">{{ $t('Pending (Internal Approval)') }}</option>
            <option value="pending_maker">{{ $t('Pending Maker') }}</option>
            <option value="pending_checker">{{ $t('Pending Checker') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
            <option value="past_due">{{ $t('Overdue') }}</option>
            <option value="expired">{{ $t('Expired') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Financing Status" class="form-label">{{ $t('Financing Status') }}</label>
          <select class="form-select form-search" v-model="financing_status_search">
            <option value="">{{ $t('Finanicing Status') }}</option>
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="submitted">{{ $t('Submitted') }}</option>
            <option value="financed">{{ $t('Financed') }}</option>
            <option value="disbursed">{{ $t('Disbursed') }}</option>
            <option value="closed">{{ $t('Closed') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
        <div class="my-auto"></div>
      </div>
      <div class="d-flex align-items-end mt-auto">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }}
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
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Buyer') }}</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Invoice Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Disbursement Date') }}</th>
            <th>{{ $t('Financing Status') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
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
          <tr v-for="invoice in invoices.data" :key="invoice.id">
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
              style="cursor: pointer"
            >
              {{ invoice.invoice_number }}
            </td>
            <td>
              <span class="me-1">{{ invoice.company }}</span>
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
            <td>{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
            <td>{{ invoice.disbursement_date ? moment(invoice.disbursement_date).format('DD MMM YYYY') : '-' }}</td>
            <td>
              <span class="badge me-1 m_title" :class="resolveFinancingStatus(invoice.financing_status)">{{
                invoice.financing_status
              }}</span>
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
import moment from 'moment';
import { ref, watch, onMounted, inject, nextTick } from 'vue';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'AllInvoicesReport',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const invoices = ref([]);
    const per_page = ref(50);
    const base_url = inject('baseURL');
    const invoice_number_search = ref('');
    const buyer_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const status_search = ref('');
    const financing_status_search = ref('');
    const date_search = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const getReport = async () => {
      await axios
        .get(base_url + 'dealer/reports/all-invoices-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
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
      getReport();
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
          style = 'bg-label-success';
          break;
        case 'disbursed':
          style = 'bg-label-success';
          break;
        case 'denied':
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

    watch([per_page], ([per_page]) => {
      axios
        .get(base_url + 'dealer/reports/all-invoices-report', {
          params: {
            per_page: per_page
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
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
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'dealer/reports/all-invoices-report', {
          params: {
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
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
      from_date_search.value = '';
      to_date_search.value = '';
      status_search.value = '';
      financing_status_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      axios
        .get(base_url + 'dealer/reports/all-invoices-report', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
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
        .get(base_url + 'dealer/reports/report/factoring-all-invoices-report/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Invoices_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportPdfReport = () => {
      axios
        .get(base_url + 'reports/dealer-all-invoices-report/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
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
            invoice_number: invoice_number_search.value,
            buyer: buyer_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    return {
      moment,
      invoices,
      per_page,
      invoice_number_search,
      buyer_search,
      from_date_search,
      to_date_search,
      status_search,
      financing_status_search,
      invoice_details,
      invoice_details_key,
      changePage,
      resolveFinancingStatus,
      resolveStatus,
      getTaxPercentage,
      exportReport,
      exportPdfReport,
      filter,
      refresh,
      showInvoice
    };
  }
};
</script>
