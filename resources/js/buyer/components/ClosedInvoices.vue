<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice Number" class="form-label mx-1">{{ $t('Invoice No') }}.</label>
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
          <label for="Invoice Number" class="form-label mx-1">{{ $t('Invoice No') }}.</label>
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
      <div class="d-flex justify-content-end gap-1 mt-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary" style="height: fit-content">
          <span class="d-flex" v-if="!downloadingExcel">
            <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
          </span>
          <img :src="spinner" style="width: 1rem" v-else />
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary" style="height: fit-content">
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
            <th>{{ $t('Disb. Date') }}</th>
            <th>{{ $t('Closure Date') }}</th>
            <th>{{ $t('Transaction Ref.') }}</th>
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
          <tr v-for="payment in report.data" :key="payment.id">
            <td class="">{{ payment.anchor }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(payment.id, 'show-details-btn-' + payment.id)"
              style="cursor: pointer"
            >
              {{ payment.invoice_number }}
            </td>
            <td class="text-success text-nowrap">
              {{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.invoice_total_amount) }}</span>
            </td>
            <td>{{ moment(payment.invoice_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(payment.due_date).format('DD MMM YYYY') }}</td>
            <td>{{ payment.disbursement_date ? moment(payment.disbursement_date).format('DD MMM YYYY') : '-' }}</td>
            <td>{{ payment.closure_date ? moment(payment.closure_date).format('DD MMM YYYY') : '-' }}</td>
            <td>
              <span>
                {{ payment.closure_transaction_reference ? payment.closure_transaction_reference : '-' }}
              </span>
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
  name: 'InvoiceAnalysisReport',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const base_url = inject('baseURL');
    const anchor_search = ref('');
    const invoice_number_search = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const from_disbursement_date_search = ref('');
    const to_disbursement_date_search = ref('');
    const invoice_financing_status_search = ref('');
    const per_page = ref(50);
    const type = ref('closed-invoices');
    const report = ref([]);
    const invoice_details = ref(null);

    const date_search = ref('');
    const disbursement_date_search = ref('');

    const invoice_details_key = ref(0);
    const spinner = '../../../../../assets/img/spinner.svg';

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

    const filter = () => {
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
      axios
        .get(base_url + 'reports/report', {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value,
            type: type.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
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
            anchor: anchor_search.value,
            type: type.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
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
      anchor_search.value = '';
      invoice_number_search.value = '';
      from_date.value = '';
      to_date.value = '';
      $('#date_search').val('');
      invoice_financing_status_search.value = '';
      from_disbursement_date_search.value = '';
      to_disbursement_date_search.value = '';
      $('#disbursement_date_search').val('');
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
            type: type.value,
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            financing_status: invoice_financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        })
        .catch(error => {
          console.log(error);
        });
    };

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const exportReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + 'reports/report/' + type.value + '/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            financing_status: invoice_financing_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Closed_Invoices_${moment().format('Do_MMM_YYYY')}.csv`);
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
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            financing_status: invoice_financing_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Invoice_Analysis_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    return {
      moment,
      report,
      per_page,
      anchor_search,
      invoice_number_search,
      from_date,
      to_date,
      invoice_financing_status_search,
      resolveFinancingStatus,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh,
      getTaxPercentage,

      invoice_details,
      showInvoice,

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
