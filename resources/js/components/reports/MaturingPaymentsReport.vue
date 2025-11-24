<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="anchor"
            placeholder="Anchor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Vendor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="vendor"
            placeholder="Vendor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Payment Date') }}</label>
          <input
            class="form-control form-search"
            type="text"
            id="payment_date_search"
            name="maturing-report-payment-daterange"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Due Date') }}</label>
          <input
            class="form-control form-search"
            type="text"
            id="due_date_search"
            name="maturing-report-due-daterange"
            v-on:keyup.enter="filter"
          />
        </div>
        <!-- <div class="">
          <label class="form-label">{{ $t('From Date') }}</label>
          <input
            class="form-control form-search"
            type="date"
            id="html5-date-input"
            v-model="from_date"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('To Date') }}</label>
          <input
            class="form-control form-search"
            type="date"
            id="html5-date-input"
            v-model="to_date"
            v-on:keyup.enter="filter"
          />
        </div> -->
        <div class="">
          <label class="form-label">{{ $t('Maturing In') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="maturing_in">
            <option value="">{{ $t('Maturing In') }}</option>
            <option value="2">2 {{ $t('Days') }}</option>
            <option value="5">5 {{ $t('Days') }}</option>
            <option value="7">7 {{ $t('Days') }}</option>
            <option value="15">15 {{ $t('Days') }}</option>
            <option value="30">30 {{ $t('Days') }}</option>
            <option value="60">60 {{ $t('Days') }}</option>
            <option value="90">90 {{ $t('Days') }}</option>
            <option value="more than 90">{{ $t('Greater than') }} 90 {{ $t('Days') }}</option>
          </select>
        </div>
        <div class="">
          <label class="form-label">{{ $t('DPD') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="dpd">
            <option value="">{{ $t('Select') }}</option>
            <option value="0">{{ $t('Due Today') }}</option>
            <option value="0">> 0 {{ $t('Days') }}</option>
            <option value="5">> 5 {{ $t('Days') }}</option>
            <option value="10">> 10 {{ $t('Days') }}</option>
            <option value="30">> 30 {{ $t('Days') }}</option>
          </select>
        </div>
        <div class="">
          <label class="form-label">{{ $t('Product Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type">
            <option value="">{{ $t('Product Type') }}</option>
            <option value="1">{{ $t('Vendor Financing') }}</option>
            <option value="2">{{ $t('Dealer Financing') }}</option>
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
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
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
            <th>{{ $t('Payment Reference No.') }}</th>
            <th>{{ $t('Invoice No.') }}</th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Payment Date') }}</th>
            <th>{{ $t('PI Amount') }}</th>
            <th>{{ $t('Payment Amount') }}</th>
            <th>{{ $t('Total Outstanding') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="text-nowrap">
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
            <td>{{ report.transaction_ref }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(report.id, 'show-details-btn-' + report.id)"
              style="cursor: pointer"
            >
              {{ report.invoice_number }}
            </td>
            <td>
              <a v-if="report.buyer" :href="'../companies/' + report.buyer_id + '/details'">{{ report.buyer }}</a>
              <a v-else :href="'../companies/' + report.company_id + '/details'">{{ report.company }}</a>
            </td>
            <td>
              <a :href="'../companies/' + report.anchor_id + '/details'">{{ report.anchor }}</a>
            </td>
            <td>{{ moment(report.disbursement_date).format(date_format) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.invoice_total_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.disbursed_amount) }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(report.invoice_total_amount - report.paid_amount) }}
            </td>
            <td>{{ moment(report.due_date).format(date_format) }}</td>
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
    const product_type = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const from_payment_date_search = ref('');
    const to_payment_date_search = ref('');
    const maturing_in = ref('');
    const payment_date = ref('');
    const dpd = ref('');
    const sort_by = ref('');
    const due_date_search = ref('');
    const payment_date_search = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'maturing-payments-report'
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'maturing-payments-report',
            per_page: per_page,
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            payment_date: payment_date.value,
            maturing_in: maturing_in.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      due_date_search.value = $('#due_date_search').val();
      if (due_date_search.value) {
        from_date.value = moment(due_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(due_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
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
            type: 'maturing-payments-report',
            per_page: per_page.value,
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            maturing_in: maturing_in.value,
            dpd: dpd.value,
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
      anchor.value = '';
      vendor.value = '';
      product_type.value = '';
      from_date.value = '';
      to_date.value = '';
      from_payment_date_search.value = '';
      to_payment_date_search.value = '';
      payment_date.value = '';
      maturing_in.value = '';
      dpd.value = '';
      sort_by.value = '';
      due_date_search.value = '';
      $('#due_date_search').val('');
      $('#payment_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'maturing-payments-report',
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
      downloadingExcel.value = true;
      due_date_search.value = $('#due_date_search').val();
      if (due_date_search.value) {
        from_date.value = moment(due_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(due_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'maturing-payments-report',
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            payment_date: payment_date.value,
            maturing_in: maturing_in.value,
            dpd: dpd.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Maturing_payment_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
      due_date_search.value = $('#due_date_search').val();
      if (due_date_search.value) {
        from_date.value = moment(due_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(due_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'maturing-payments-report',
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            maturing_in: maturing_in.value,
            dpd: dpd.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Maturing_payments_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
      due_date_search.value = $('#due_date_search').val();
      if (due_date_search.value) {
        from_date.value = moment(due_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(due_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            type: 'maturing-payments-report',
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            from_date: from_date.value,
            to_date: to_date.value,
            payment_date: payment_date.value,
            from_payment_date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            maturing_in: maturing_in.value,
            dpd: dpd.value,
            sort_by: sort_by.value,
            per_page: per_page.value
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
      product_type,
      from_date,
      to_date,
      payment_date,
      maturing_in,
      dpd,
      sort_by,
      due_date_search,

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
