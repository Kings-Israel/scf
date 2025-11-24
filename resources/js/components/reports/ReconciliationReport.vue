<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Account No" class="form-label">{{ $t('Account No') }}.</label>
          <input type="text" class="form-control" placeholder="Account No" />
        </div>
        <div class="">
          <label for="Date" class="form-label">{{ $t('Date') }}</label>
          <input class="form-control" type="date" id="html5-date-input" />
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-1 justify-content-md-end mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="mx-2">
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
            <th>Payment/OD Account No.</th>
            <th>OD Expiry Date</th>
            <th>Sanctioned Limit</th>
            <th>Utilized Limit</th>
            <th>CBS Sanction Limit</th>
            <th>CBS Limit Utilized</th>
            <th>Difference Sanction Limit</th>
            <th>Type</th>
            <th>Created At</th>
          </tr>
        </thead>
        <tbody>
          <!-- <tr v-for="report in report_data.data" :key="report.id">
            <td>{{ report.id }}</td>
            <td class="text-primary text-decoration-underline" data-bs-toggle="modal" :data-bs-target="'#invoice-'+report.payment_request.invoice.id" style="cursor: pointer;">{{ report.payment_request.invoice.invoice_number }}</td>
            <td>
              <a v-if="report.payment_request.invoice.buyer" :href="'../companies/'+report.payment_request.invoice.buyer.id+'/details'">{{ report.payment_request.invoice.buyer.name }}</a>
              <a v-else :href="'../companies/'+report.payment_request.invoice.company.id+'/details'">{{ report.payment_request.invoice.company.name }}</a>
            </td>
            <td>
              <a :href="'../companies/'+report.payment_request.invoice.program.anchor.id+'/details'">{{ report.payment_request.invoice.program.anchor.name }}</a>
            </td>
            <td>{{ moment(report.payment_request.invoice.disbursement_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(report.payment_request.invoice.due_date).format('DD MMM YYYY') }}</td>
            <td class="text-success">{{ new Intl.NumberFormat().format(report.payment_request.amount) }}</td>
            <td>{{ report.payment_request.invoice.program.eligibility }}%</td>
            <td>
              <span class="badge bg-label-success m_title">{{ report.status }}</span>
            </td>
            <div class="modal fade" :id="'invoice-'+report.payment_request.invoice.id" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="d-flex">
                      <button class="btn btn-primary mx-1 d-none"> <i class="ti ti-printer"></i> Print</button>
                      <a v-if="report.payment_request.invoice.attachment" :href="report.payment_request.invoice.attachment" target="_blank" class="btn btn-secondary mx-1"> <i class="ti ti-paperclip"></i> Attachment</a>
                    </div>
                  </div>
                  <div class="modal-body">
                    <div class="d-flex justify-content-between">
                      <div class="mb-3 w-25">
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">Vendor:</h5>
                          <h6 class="fw-bold mx-2 my-auto" v-if="report.payment_request.invoice.buyer">{{ report.payment_request.invoice.buyer.name }}</h6>
                          <h6 class="fw-bold mx-2 my-auto" v-else>{{ report.payment_request.invoice.company.name }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">Anchor:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ report.payment_request.invoice.program.anchor.name }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">Credit To:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ report.payment_request.payment_accounts[0].account }}</h6>
                        </span>
                      </div>
                      <div class="mb-3">
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">PI No:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ report.payment_request.invoice.pi_number }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">Invoice/Unique Ref No:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ report.payment_request.invoice.invoice_number }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">Invoice Amount:</h5>
                          <h6 class="fw-bold text-success mx-2 my-auto">Ksh {{ new Intl.NumberFormat().format(report.payment_request.invoice.total) }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">Invoice Due Date:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ moment(report.payment_request.invoice.due_date).format('DD MMM YYYY') }}</h6>
                        </span>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price Per Quantity</th>
                            <th>Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in report.payment_request.invoice.invoice_items" :key="item.id">
                            <td>{{ item.item }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>{{ item.unit }}</td>
                            <td>{{ item.price_per_quantity }}</td>
                            <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                          </tr>
                        </tbody>
                      </table>
                      <span class="d-flex justify-content-end px-2">
                        <h6 class="mx-2 my-auto py-1">Discount</h6>
                        <h5 class="text-success my-auto py-1">{{ new Intl.NumberFormat().format(report.payment_request.invoice.total_invoice_discount) }}</h5>
                      </span>
                      <div v-if="report.payment_request.invoice.invoice_taxes.length" class="px-2">
                        <span v-for="tax in report.payment_request.invoice.invoice_taxes" :key="tax.id" class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ tax.name }}</h6>
                          <h5 class="text-success my-auto py-1">{{ new Intl.NumberFormat().format(tax.value) }}</h5>
                        </span>
                      </div>
                      <div v-if="report.payment_request.invoice.invoice_fees.length > 0" class="px-2">
                        <span v-for="fee in report.payment_request.invoice.invoice_fees" :key="fee.id" class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                          <h5 class="text-success my-auto py-1" v-if="fee.name != 'Credit Note Amount' && fee.name != 'Credit Note'">{{ new Intl.NumberFormat().format(fee.amount.toFixed(2)) }}</h5>
                          <h5 class="text-success my-auto py-1" v-else>{{ new Intl.NumberFormat().format(fee.amount) }}</h5>
                        </span>
                      </div>
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">Total</h6>
                          <h5 class="text-success my-auto py-1">{{ new Intl.NumberFormat().format(report.payment_request.invoice.total + report.payment_request.invoice.total_invoice_taxes - report.payment_request.invoice.total_invoice_discount - report.payment_request.invoice.total_invoice_fees) }}</h5>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </tr> -->
        </tbody>
      </table>
    </div>
    <!-- <pagination :from="report_data.from" :to="report_data.to" :links="report_data.links" :next_page="report_data.next_page_url" :prev_page="report_data.prev_page_url" :total_items="report_data.total" :first_page_url="report_data.first_page_url" :last_page_url="report_data.last_page_url" @change-page="changePage"></pagination> -->
  </div>
</template>
<script>
import { ref, watch, onMounted, inject } from 'vue';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import moment from 'moment';

export default {
  name: 'AllPayments',
  components: {
    Pagination,
    DownloadButtons
  },
  props: ['bank'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const report_data = ref([]);

    // Search fields
    const anchor = ref('');
    const vendor = ref('');

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

    watch([anchor, vendor, per_page], async ([anchor, vendor, per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'maturing-payments-report',
            per_page: per_page,
            anchor: anchor,
            vendor: vendor
          }
        })
        .then(response => {
          report_data.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    onMounted(() => {
      // getData()
    });

    const changePage = async page => {
      await axios
        .get(page, {
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
        });
    };

    return {
      moment,
      anchor,
      vendor,

      per_page,

      report_data,

      changePage
    };
  }
};
</script>
<style>
.m_title::first-letter {
  text-transform: capitalize;
}
</style>
