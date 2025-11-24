<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="form-group">
          <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Invoice No"
            v-model="invoice_number"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="form-group">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Vendor"
            v-model="vendor"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Due Date') }})</label>
          <input
            type="text"
            id="date_search"
            class="form-control form-search"
            name="overdue_daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Disbursement Date') }})</label>
          <input
            type="text"
            id="disbursement_date_search"
            class="form-control form-search"
            name="disbursement_daterange"
            placeholder="Select Dates"
          />
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary">
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
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Invoice Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Disbursement Date') }}</th>
            <th>{{ $t('Overdue Amount') }}</th>
            <th>{{ $t('DPD') }}</th>
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
            <td class="">{{ invoice.company }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
              style="cursor: pointer"
            >
              {{ invoice.invoice_number }}
            </td>
            <td class="text-success">
              {{ invoice.currency }}
              <span>
                {{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}
              </span>
            </td>
            <td>{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(invoice.disbursement_date).format('DD MMM YYYY') }}</td>
            <td class="text-success">
              {{ invoice.currency }}
              <span>
                {{ new Intl.NumberFormat().format(invoice.balance) }}
              </span>
            </td>
            <td>{{ invoice.days_past_due }}</td>
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
import { ref, onMounted, inject, nextTick } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'PaidInvoicesReport',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const base_url = inject('baseURL');
    const per_page = ref(50);
    const type = ref('overdue-invoices');
    const report = ref([]);
    const invoice_number = ref('');
    const vendor = ref('');
    const status = ref('');
    const financing_status = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const from_disbursement_date_search = ref('');
    const to_disbursement_date_search = ref('');
    const date_search = ref('');
    const disbursement_date_search = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(null);

    const spinner = '../../../../../assets/img/spinner.svg';

    const getReport = () => {
      axios
        .get('report', {
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
        .get('../invoices/' + invoice + '/details')
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

    const resolveStatus = invoice => {
      let style = '';
      if (invoice.days_past_due > 0) {
        style = 'bg-label-danger';
      } else {
        switch (invoice.status) {
          case 'pending':
            style = 'bg-label-primary';
            break;
          case 'sent':
            style = 'bg-label-secondary';
            break;
          case 'approved':
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

    const filter = async () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
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
      await axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value,
            vendor: vendor.value,
            invoice_no: invoice_number.value,
            financing_status: financing_status.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value
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

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      vendor.value = '';
      invoice_number.value = '';
      financing_status.value = '';
      from_date.value = '';
      to_date.value = '';
      date_search.value = '';
      $('#date_search').val('');
      from_disbursement_date_search.value = '';
      to_disbursement_date_search.value = '';
      $('#disbursement_date_search').val('');
      await axios
        .get('report', {
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const exportReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + 'reports/report/overdue-invoices/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor.value,
            invoice_no: invoice_number.value,
            financing_status: financing_status.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Overdue_invoice_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
        .get(base_url + 'reports/' + type.value + '/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor.value,
            invoice_no: invoice_number.value,
            financing_status: financing_status.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Overdue_Invoices_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: type.value,
            vendor: vendor.value,
            invoice_no: invoice_number.value,
            financing_status: financing_status.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value
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
      invoice_number,
      financing_status,
      status,
      vendor,
      from_date,
      to_date,
      invoice_details,
      filter,
      refresh,
      resolveStatus,
      resolveFinancingStatus,
      exportReport,
      exportPdfReport,
      changePage,
      showInvoice,
      getTaxPercentage,

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
