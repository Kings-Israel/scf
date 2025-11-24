<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Invoice/Unique Ref No."
            v-model="invoice_number"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Anchor"
            v-model="anchor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Vendor"
            v-model="vendor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Payment Date" class="form-label">{{ $t('Payment Date') }}</label>
          <input
            class="form-control form-search"
            type="text"
            id="payment_date_search"
            name="if-payment-details-report-payment-daterange"
            v-on:keyup.enter="filter"
            autocomplete="off"
          />
        </div>
        <div class="">
          <label for="DPD Days" class="form-label">{{ $t('DPD Days') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="dpd">
            <option value="">{{ $t('DPD') }}</option>
            <option value="0">{{ $t('Less than 0 and 0 Days') }}</option>
            <option value="1-5">1 - 5 {{ $t('Days') }}</option>
            <option value="6-10">6 - 10 {{ $t('Days') }}</option>
            <option value="10">{{ $t('More than 10 Days') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="disbursed">{{ $t('Disbursed') }}</option>
            <option value="closed">{{ $t('Closed') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Product Type" class="form-label">{{ $t('Product Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_search">
            <option value="">{{ $t('Product Type') }}</option>
            <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
            <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
            <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
            <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
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
        <div class="">
          <select
            class="form-select"
            id="exampleFormControlSelect1"
            v-model="per_page"
            style="height: fit-content; width: 5rem"
          >
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
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Payment Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('DPD') }} ({{ $t('Days') }})</th>
            <th>{{ $t('Total Outstanding') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Action') }}</th>
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
              @click="showInvoice(report.id, 'show-details-btn-' + report.id)"
              style="cursor: pointer"
            >
              {{ report.invoice_number }}
            </td>
            <td>
              <a v-if="report.buyer" :href="'../companies/' + report.buyer_id + '/details'">{{ report.buyer }}</a>
              <a v-else :href="'../companies/' + report.anchor_id + '/details'">{{ report.anchor }}</a>
            </td>
            <td>
              <a :href="'../companies/' + report.company_id + '/details'">{{ report.company }}</a>
            </td>
            <td>{{ moment(report.disbursement_date).format(date_format) }}</td>
            <td>{{ moment(report.due_date).format(date_format) }}</td>
            <td>{{ report.days_past_due }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.balance) }}</td>
            <td>
              <span class="badge bg-label-success m_title">{{ report.status }}</span>
            </td>
            <td>
              <a :href="'../reports/' + report.id + '/payment-details'" target="_blank" class="text-info"
                ><i class="ti ti-eye ti-sm"></i
              ></a>
            </td>
            <td>
              <button
                class="d-none"
                :id="'show-details-btn-' + report.id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + report.id"
              ></button>
              <button
                class="d-none"
                :id="'show-pi-btn-' + report.id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + report.id"
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
import { useToast } from 'vue-toastification';
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
    const toast = useToast();
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const anchor = ref('');
    const vendor = ref('');
    const invoice_number = ref('');
    const disbursement_date = ref('');
    const dpd = ref('');
    const status_search = ref('');
    const product_search = ref('');
    const sort_by = ref('');
    const from_payment_date_search = ref('');
    const to_payment_date_search = ref('');

    const payment_date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'if-payment-details-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
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

    const filter = async () => {
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'if-payment-details-report',
            per_page: per_page.value,
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            disbursement_date: disbursement_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status_search.value,
            product: product_search.value,
            sort_by: sort_by.value
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
      invoice_number.value = '';
      anchor.value = '';
      vendor.value = '';
      disbursement_date.value = '';
      dpd.value = '';
      status_search.value = '';
      product_search.value = '';
      sort_by.value = '';
      from_payment_date_search.value = '';
      to_payment_date_search.value = '';
      payment_date_search.value = '';
      $('#payment_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'if-payment-details-report',
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'if-payment-details-report',
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            disbursement_date: disbursement_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status_search.value,
            product: product_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `IF_payment_details_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      downloadingPdf.value = true;
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'if-payment-details-report',
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            disbursement_date: disbursement_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status_search.value,
            product: product_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `IF_payment_details_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    const changePage = async page => {
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            type: 'if-payment-details-report',
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            disbursement_date: disbursement_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status_search.value,
            product: product_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
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
      invoice_number,
      disbursement_date,
      dpd,
      status_search,
      product_search,
      sort_by,

      per_page,

      report_data,

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
