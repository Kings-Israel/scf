<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="anchor"
            placeholder="Anchor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="vendor"
            placeholder="Vendor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Product Code" class="form-label">{{ $t('Product Type') }}</label>
          <select
            class="form-select form-search"
            id="exampleFormControlSelect1"
            v-model="product_type"
            style="height: fit-content"
          >
            <option value="">{{ $t('Product Type') }}</option>
            <option value="VFR">VFR</option>
            <option value="FR">FR</option>
            <option value="FWR">FWR</option>
          </select>
        </div>
        <div class="">
          <label for="From" class="form-label">{{ $t('From') }} ({{ $t('Original Date') }})</label>
          <input type="date" class="form-control form-search" v-model="from_original_date" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="To" class="form-label">{{ $t('To') }} ({{ $t('Original Date') }})</label>
          <input type="date" class="form-control form-search" v-model="to_original_date" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="From" class="form-label">{{ $t('From') }} ({{ $t('Changed Date') }})</label>
          <input type="date" class="form-control form-search" v-model="from_changed_date" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="To" class="form-label">{{ $t('To') }} ({{ $t('Changed Date') }})</label>
          <input type="date" class="form-control form-search" v-model="to_changed_date" v-on:keyup.enter="filter" />
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
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
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
            <th>{{ $t('Vendor Financing Payment Account No. (CBS)') }}</th>
            <th>{{ $t('Invoice No.') }}</th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Original Date') }}</th>
            <th>{{ $t('Changed Due Date') }}</th>
            <th>{{ $t('Product Code') }}</th>
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
            <td>{{ report.payment_account_number }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(report.id, 'show-details-btn-' + report.id)"
              style="cursor: pointer"
            >
              {{ report.invoice_number }}
            </td>
            <td>
              <a :href="'../companies/' + report.company_id + '/details'">{{ report.company }}</a>
            </td>
            <td>
              <a v-if="report.buyer_id" :href="'../companies/' + report.buyer_id + '/details'">{{ report.buyer }}</a>
              <a v-else :href="'../companies/' + report.anchor_id + '/details'">{{ report.anchor }}</a>
            </td>
            <td>{{ moment(report.old_due_date).format(date_format) }}</td>
            <td>{{ moment(report.due_date).format(date_format) }}</td>
            <td>{{ report.program_type }}</td>
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
    const sort_by = ref('');
    const from_original_date = ref('');
    const to_original_date = ref('');
    const from_changed_date = ref('');
    const to_changed_date = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    // Pagination
    const per_page = ref(50);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'maturity-extended-report'
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
            type: 'maturity-extended-report',
            per_page: per_page,
            anchor: anchor.value,
            vendor: vendor.value,
            from_original_date: from_original_date.value,
            to_original_date: to_original_date.value,
            from_changed_date: from_changed_date.value,
            to_changed_date: to_changed_date.value
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
      getData();
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'maturity-extended-report',
            per_page: per_page.value,
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            sort_by: sort_by.value,
            from_original_date: from_original_date.value,
            to_original_date: to_original_date.value,
            from_changed_date: from_changed_date.value,
            to_changed_date: to_changed_date.value
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
      sort_by.value = '';
      from_original_date.value = '';
      to_original_date.value = '';
      from_changed_date.value = '';
      to_changed_date.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'maturity-extended-report'
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
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'maturity-extended-report',
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            sort_by: sort_by.value,
            from_original_date: from_original_date.value,
            to_original_date: to_original_date.value,
            from_changed_date: from_changed_date.value,
            to_changed_date: to_changed_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Maturity_extended_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            type: 'maturity-extended-report',
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            sort_by: sort_by.value,
            from_original_date: from_original_date.value,
            to_original_date: to_original_date.value,
            from_changed_date: from_changed_date.value,
            to_changed_date: to_changed_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Maturity_extended_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            type: 'maturity-extended-report',
            per_page: per_page.value,
            sort_by: sort_by.value,
            anchor: anchor.value,
            vendor: vendor.value,
            product_type: product_type.value,
            from_original_date: from_original_date.value,
            to_original_date: to_original_date.value,
            from_changed_date: from_changed_date.value,
            to_changed_date: to_changed_date.value
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
      sort_by,
      from_changed_date,
      from_original_date,
      to_changed_date,
      to_original_date,

      per_page,

      report_data,

      invoice_details,

      filter,
      refresh,
      downloadPdf,
      downloadReport,

      changePage,
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
