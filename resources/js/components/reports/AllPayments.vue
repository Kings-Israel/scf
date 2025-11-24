<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="invoice_no"
            id="defaultFormControlInput"
            placeholder="Invoice No"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="PI Number" class="form-label">{{ $t('PI Number') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="pi_number"
            id="defaultFormControlInput"
            placeholder="PI Number"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="anchor"
            id="defaultFormControlInput"
            placeholder="Anchor"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="vendor"
            id="defaultFormControlInput"
            placeholder="Vendor"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Payment Date" class="form-label">{{ $t('Payment Date') }}</label>
          <input
            type="text"
            id="payment_date_search"
            class="form-control form-search"
            name="all-payments-report-payment-daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <div class="">
          <label for="Due Date" class="form-label">{{ $t('Due Date') }}</label>
          <input
            type="text"
            id="due_date_search"
            class="form-control form-search"
            name="all-payments-report-due-daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <div class="">
          <label for="Program Type" class="form-label">{{ $t('Program Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type_search">
            <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
            <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
            <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
            <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="status">
            <option value="">{{ $t('Status') }}</option>
            <option value="Successful">{{ $t('Successful') }}</option>
            <option value="Created">{{ $t('Created') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Sort By" class="form-label">{{ $t('Sort By') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="sort_by">
            <option value="">{{ $t('Sort By') }}</option>
            <option value="invoice_no_asc">{{ $t('Invoice No') }} ({{ $t('Asc') }})</option>
            <option value="invoice_no_desc">{{ $t('Invoice Number') }} ({{ $t('Desc') }})</option>
            <option value="payment_amount_asc">{{ $t('Payment Amount') }} ({{ $t('Asc') }})</option>
            <option value="payment_amount_desc">{{ $t('Payment Amount') }} ({{ $t('Desc') }})</option>
            <option value="anchor_asc">{{ $t('Anchor') }} ({{ $t('Asc') }})</option>
            <option value="anchor_desc">{{ $t('Anchor') }} ({{ $t('Desc') }})</option>
            <option value="vendor_asc">{{ $t('Vendor') }} ({{ $t('Asc') }})</option>
            <option value="vendor_desc">{{ $t('Vendor') }} ({{ $t('Desc') }})</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex gap-1 justify-content-end mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <download-buttons
            @download-report="downloadReport"
            @download-pdf="downloadPdf"
            :downloading-excel="downloadingExcel"
            :downloading-pdf="downloadingPdf"
          ></download-buttons>
          <!-- <button type="button" @click="downloadReport" style="height: fit-content" class="btn btn-primary mx-1">
            <i class="ti ti-download ti-xs px-1"></i> Excel
          </button>
          <button type="button" @click="downloadPdf" style="height: fit-content" class="btn btn-primary">
            <i class="ti ti-download ti-xs px-1"></i> PDF
          </button> -->
        </div>
      </div>
    </div>
    <pagination
      v-if="report_data.meta"
      :from="report_data.meta.from"
      :to="report_data.meta.to"
      :links="report_data.meta.links"
      :next_page="report_data.links.next"
      :prev_page="report_data.links.prev"
      :total_items="report_data.meta.total"
      :first_page_url="report_data.links.first"
      :last_page_url="report_data.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('System ID') }}</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Payment Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Payment Amount') }}</th>
            <th>{{ $t('Eligibility') }}</th>
            <th>{{ $t('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!report_data.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="report_data.data && report_data.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="report in report_data.data" :key="report.id">
            <td>{{ report.id }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="
                showInvoice(report.payment_request.invoice_id, 'show-details-btn-' + report.payment_request.invoice_id)
              "
              style="cursor: pointer"
            >
              {{ report.payment_request.invoice_number }}
            </td>
            <td>
              <a
                v-if="report.payment_request.buyer_id"
                :href="'../companies/' + report.payment_request.anchor_id + '/details'"
                >{{ report.payment_request.anchor_name }}</a
              >
              <a v-else :href="'../companies/' + report.payment_request.company_id + '/details'">{{
                report.payment_request.company_name
              }}</a>
            </td>
            <td>
              <a
                v-if="report.payment_request.buyer_id"
                :href="'../companies/' + report.payment_request.buyer_id + '/details'"
                >{{ report.payment_request.buyer_name }}</a
              >
              <a v-else :href="'../companies/' + report.payment_request.anchor_id + '/details'">{{
                report.payment_request.anchor_name
              }}</a>
            </td>
            <td>{{ moment(report.payment_request.invoice_disbursement_date).format(date_format) }}</td>
            <td>{{ moment(report.payment_request.invoice_due_date).format(date_format) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.payment_request.amount) }}</td>
            <td>{{ report.payment_request.eligibility }}%</td>
            <td>
              <span class="badge bg-label-success m_title">{{ report.status }}</span>
            </td>
            <td>
              <button
                class="d-none"
                :id="'show-details-btn-' + report.payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + report.payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                :id="'show-pi-btn-' + report.payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + report.payment_request.invoice_id"
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
      v-if="report_data.meta"
      :from="report_data.meta.from"
      :to="report_data.meta.to"
      :links="report_data.meta.links"
      :next_page="report_data.links.next"
      :prev_page="report_data.links.prev"
      :total_items="report_data.meta.total"
      :first_page_url="report_data.links.first"
      :last_page_url="report_data.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, watch, onMounted, inject, nextTick } from 'vue';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import axios from 'axios';
import moment from 'moment';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'AllPayments',
  components: {
    Pagination,
    DownloadButtons,
    InvoiceDetails,
    PaymentInstruction
  },
  props: ['bank', 'date_format'],
  setup(props) {
    const date_format = props.date_format;
    const base_url = inject('baseURL');
    const report_data = ref([]);
    const is_loading = ref(false);

    // Search fields
    const anchor = ref('');
    const vendor = ref('');
    const invoice_no = ref('');
    const pi_number = ref('');
    const payment_date = ref('');
    const product_type_search = ref('');
    const from_payment_date_search = ref('');
    const to_payment_date_search = ref('');
    const from_due_date_search = ref('');
    const to_due_date_search = ref('');
    const sort_by = ref('');
    const status = ref('');

    const payment_date_search = ref('');
    const due_date_search = ref('');
    // Pagination
    const per_page = ref(50);

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const getData = async () => {
      is_loading.value = true;
      await axios
        .get(base_url + props.bank + '/reports/data?type=all-payments-report&per_page=' + per_page.value)
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          is_loading.value = false;
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

    const filter = async () => {
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      due_date_search.value = $('#due_date_search').val();
      if (due_date_search.value) {
        from_due_date_search.value = moment(due_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_due_date_search.value = moment(due_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'all-payments-report',
            per_page: per_page.value,
            anchor: anchor.value,
            vendor: vendor.value,
            invoice_no: invoice_no.value,
            pi_number: pi_number.value,
            status: status.value,
            sort_by: sort_by.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            from_due_date: from_due_date_search.value,
            to_due_date: to_due_date_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          report_data.value = response.data;
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
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      anchor.value = '';
      vendor.value = '';
      invoice_no.value = '';
      status.value = '';
      sort_by.value = '';
      payment_date.value = '';
      pi_number.value = '';
      from_payment_date_search.value = '';
      to_payment_date_search.value = '';
      product_type_search.value = '';
      $('#payment_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'all-payments-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    onMounted(() => {
      getData();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'all-payments-report',
            per_page: per_page.value,
            anchor: anchor.value,
            vendor: vendor.value,
            invoice_no: invoice_no.value,
            pi_number: pi_number.value,
            status: status.value,
            sort_by: sort_by.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
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
            type: 'all-payments-report',
            anchor: anchor.value,
            vendor: vendor.value,
            invoice_no: invoice_no.value,
            pi_number: pi_number.value,
            status: status.value,
            sort_by: sort_by.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `All_payments_Report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'all-payments-report',
            anchor: anchor.value,
            vendor: vendor.value,
            invoice_no: invoice_no.value,
            pi_number: pi_number.value,
            status: status.value,
            sort_by: sort_by.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `All_payments_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
      anchor,
      vendor,
      invoice_no,
      pi_number,
      payment_date,
      sort_by,
      status,
      product_type_search,

      per_page,

      report_data,

      is_loading,

      invoice_details,

      filter,
      refresh,

      changePage,
      downloadReport,
      downloadPdf,
      getTaxPercentage,
      resolveStatus,
      showInvoice,

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
  text-transform: capitalize;
}
</style>
