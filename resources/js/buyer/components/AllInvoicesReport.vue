<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}.</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Invoice No"
            v-model="invoice_number_search"
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
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" v-model="invoice_status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
            <option value="submitted">{{ $t('Submitted') }}</option>
            <option value="overdue">{{ $t('Overdue') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Financing Status" class="form-label">{{ $t('Financing Status') }}</label>
          <select class="form-select form-search" v-model="invoice_financing_status_search">
            <option value="">{{ $t('Financing Status') }}</option>
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="pending">{{ $t('Submitted') }}</option>
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
      </div>
      <div class="d-flex align-items-end">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <span class="d-flex" v-if="!downloadingExcel">
            <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
          </span>
          <img :src="spinner" style="width: 1rem" v-else />
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <span class="d-flex" v-if="!downloadingPdf"> <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }} </span>
          <img :src="spinner" style="width: 1rem" v-else />
        </button>
      </div>
    </div>
    <pagination
      v-if="report.meta"
      :from="report.meta.from"
      :to="report.meta.to"
      :links="report.meta.links"
      :next_page="report.links.next"
      :prev_page="report.links.prev"
      :total_items="report.meta.total"
      :first_page_url="report.links.first"
      :last_page_url="report.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Invoice Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Invoice Status') }}</th>
            <th>{{ $t('Finance Status') }}</th>
            <th>{{ $t('Disbursement Date') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!report.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="report.data && report.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="invoice in report.data" :key="invoice.id">
            <td class="text-primary">{{ invoice.anchor }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
              style="cursor: pointer"
            >
              {{ invoice.invoice_number }}
            </td>
            <td class="text-success text-nowrap">
              {{ invoice.currency }}
              <span>{{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}</span>
            </td>
            <td>{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
            <td>
              <span class="badge me-1 m_title" :class="resolveStatus(invoice.status)">{{ invoice.status }}</span>
            </td>
            <td>
              <span class="badge me-1 m_title" :class="resolveFinancingStatus(invoice.financing_status)">{{
                invoice.financing_status
              }}</span>
            </td>
            <td v-if="invoice.disbursement_date">{{ moment(invoice.disbursement_date).format('DD MMM YYYY') }}</td>
            <td v-else>-</td>
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
      v-if="report.meta"
      :from="report.meta.from"
      :to="report.meta.to"
      :links="report.meta.links"
      :next_page="report.links.next"
      :prev_page="report.links.prev"
      :total_items="report.meta.total"
      :first_page_url="report.links.first"
      :last_page_url="report.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, onMounted, watch, inject, nextTick } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'AllInvoicesReport',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const base_url = inject('baseURL');
    const per_page = ref(50);
    const invoice_number_search = ref('');
    const invoice_date_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const invoice_status_search = ref('');
    const invoice_financing_status_search = ref('');
    const date_search = ref('');

    const invoice_details = ref(null);

    const spinner = '../../../../../assets/img/spinner.svg';

    const type = ref('all-invoices');
    const report = ref([]);

    const invoice_details_key = ref(0);

    const getReport = () => {
      axios
        .get(base_url + 'reports/report', {
          params: {
            per_page: per_page.value,
            type: type.value
          }
        })
        .then(response => {
          report.value = response.data;
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    onMounted(() => {
      getReport();
    });

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'sent':
          style = 'bg-label-secondary';
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const exportReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + 'reports/report/all-invoices/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            invoice_date: invoice_date_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: invoice_status_search.value,
            financing_status: invoice_financing_status_search.value
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
          downloadingExcel.value = false;
        });
    };

    const exportPdfReport = () => {
      downloadingPdf.value = true;
      axios
        .get(base_url + 'report/' + type.value + '/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            invoice_date: invoice_date_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: invoice_status_search.value,
            financing_status: invoice_financing_status_search.value
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
        })
        .finally(() => {
          downloadingPdf.value = false;
        });
    };

    const filter = () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'reports/report', {
          params: {
            per_page: per_page.value,
            type: type.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: invoice_status_search.value,
            financing_status: invoice_financing_status_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    watch([per_page], ([per_page]) => {
      axios
        .get(base_url + 'reports/report', {
          params: {
            per_page: per_page,
            type: type.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: invoice_status_search.value,
            financing_status: invoice_financing_status_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      invoice_number_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      invoice_financing_status_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      axios
        .get(base_url + 'reports/report', {
          params: {
            type: type.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: type.value,
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            invoice_date: invoice_date_search.value,
            due_date: invoice_due_date_search.value,
            status: invoice_status_search.value,
            financing_status: invoice_financing_status_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(error => {
          console.log(error);
        });
    };

    return {
      moment,
      report,
      per_page,
      invoice_number_search,
      invoice_date_search,
      invoice_status_search,
      invoice_financing_status_search,
      resolveStatus,
      resolveFinancingStatus,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh,
      showInvoice,
      getTaxPercentage,
      invoice_details,

      downloadingExcel,
      downloadingPdf,
      spinner,
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
