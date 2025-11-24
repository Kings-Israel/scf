<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
          <input
            type="text"
            class="form-control"
            placeholder="Invoice/Unique Ref No."
            v-model="invoice_number"
            v-on:keyup.enter="filter"
          />
        </div>
        <!-- <div class="">
          <label for="Anchor" class="form-label">Anchor</label>
          <input type="text" class="form-control" placeholder="Anchor" v-model="anchor" />
        </div> -->
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Dealer') }}</label>
          <input type="text" class="form-control" placeholder="Dealer" v-model="vendor" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Disbursement Date" class="form-label">{{ $t('Payment Date') }}</label>
          <input
            type="text"
            id="payment_date_search"
            class="form-control form-search"
            name="df-income-report-payment-daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select" id="exampleFormControlSelect1" v-model="status">
            <option value="">{{ $t('Status') }}</option>
            <option value="disbursed">{{ $t('Overdue') }}</option>
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
      <div class="d-flex justify-content-mt-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="5">5</option>
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
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Dealer') }}</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Drawdown Date') }}</th>
            <th>{{ $t('Drawdown Amount') }}</th>
            <th>{{ $t('Total Posted Discount') }}</th>
            <th>{{ $t("Anchor's Discount Share") }}</th>
            <th>{{ $t('Fees/Charges') }}</th>
            <th>{{ $t("Anchor's Fees Share") }}</th>
            <th>{{ $t('Status') }}</th>
            <!-- <th>Action</th> -->
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
            <td>
              <a :href="'../companies/' + report.company.id + '/details'">{{ report.company.name }}</a>
            </td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(report.id, 'show-details-btn-' + report.id)"
              style="cursor: pointer"
            >
              {{ report.invoice_number }}
            </td>
            <td>{{ moment(report.disbursement_date).format(date_format) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.drawdown_amount) }}</td>
            <td class="text-success">
              {{ new Intl.NumberFormat().format(report.discount) }}
            </td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.anchor_bearing_discount_amount) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.fees) }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.anchor_fee_bearing_amount) }}</td>
            <td>
              <span class="badge bg-label-success m_title">{{ report.financing_status }}</span>
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
      :from="report_data.from"
      :to="report_data.to"
      :links="report_data.links"
      :next_page="report_data.next_page_url"
      :prev_page="report_data.prev_page_url"
      :total_items="report_data.total"
      :first_page_url="report_data.first_page_url"
      :last_page_url="report_data.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import moment from 'moment';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'DFOverdueInvoices',
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
    const payment_date = ref('');
    const from_payment_date_search = ref('');
    const to_payment_date_search = ref('');
    const dpd = ref('');
    const status = ref('');
    const sort_by = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const payment_date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-fees-and-interest-sharing-report'
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-fees-and-interest-sharing-report',
            per_page: per_page,
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            payment_date: payment_date.value,
            from_payment_Date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status.value
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
      payment_date_search.value = $('#payment_date_search').val();
      if (payment_date_search.value) {
        from_payment_date_search.value = moment(payment_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_payment_date_search.value = moment(payment_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-fees-and-interest-sharing-report',
            per_page: per_page.value,
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            payment_date: payment_date.value,
            from_payment_Date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status.value,
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

    const refresh = async () => {
      invoice_number.value = '';
      anchor.value = '';
      vendor.value = '';
      payment_date.value = '';
      from_payment_date_search.value = '';
      to_payment_date_search.value = '';
      dpd.value = '';
      status.value = '';
      sort_by.value = '';
      $('#payment_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'df-fees-and-interest-sharing-report',
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

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'df-fees-and-interest-sharing-report',
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            payment_date: payment_date.value,
            from_payment_Date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `DF_fees_and_interest_sharing_report_${moment().format('Do_MMM_YYYY')}.csv`
          );
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
            type: 'df-fees-and-interest-sharing-report',
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            payment_date: payment_date.value,
            from_payment_Date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status.value,
            sort_by: sort_by.value
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

    onMounted(() => {
      getData();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'df-fees-and-interest-sharing-report',
            per_page: per_page.value,
            invoice_number: invoice_number.value,
            anchor: anchor.value,
            vendor: vendor.value,
            payment_date: payment_date.value,
            from_payment_Date: from_payment_date_search.value,
            to_payment_date: to_payment_date_search.value,
            dpd: dpd.value,
            status: status.value,
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

    return {
      moment,

      anchor,
      vendor,
      invoice_number,
      payment_date,
      dpd,
      status,

      per_page,

      report_data,

      invoice_details,

      showInvoice,

      filter,
      refresh,

      changePage,
      downloadReport,
      downloadPdf,

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
