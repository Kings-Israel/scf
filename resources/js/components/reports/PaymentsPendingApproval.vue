<template>
  <div class="card p-2">
    <div class="card-body p-0">
      <div class="d-flex flex-column flex-md-row justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
            <input
              type="text"
              class="form-control"
              id="defaultFormControlInput"
              placeholder="Anchor"
              v-model="anchor_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
            <input
              type="text"
              class="form-control"
              id="defaultFormControlInput"
              placeholder="Vendor"
              v-model="vendor_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
            <input
              type="text"
              class="form-control"
              id="defaultFormControlInput"
              placeholder="Invoice/Unique Ref"
              v-model="invoice_number_search"
              aria-describedby="defaultFormControlHelp"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label class="form-label">{{ $t('Requested Disbursement Date') }}</label>
            <input
              class="form-control form-search"
              type="text"
              id="requested_payment_date_search"
              name="payments-pending-approval-report-requested-payment-daterange"
              v-on:keyup.enter="filter"
            />
          </div>
          <div class="">
            <label for="Status" class="form-label">{{ $t('Program Type') }}</label>
            <select class="form-select form-search" v-model="program_type_search">
              <option value="">{{ $t('Select Program Type') }}</option>
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
            <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
              <option value="100">50</option>
            </select>
          </div>
          <download-buttons
            @download-report="downloadReport"
            @download-pdf="downloadPdf"
            :downloading-excel="downloadingExcel"
            :downloading-pdf="downloadingPdf"
          ></download-buttons>
        </div>
      </div>
      <pagination
        v-if="transactions.meta"
        :from="transactions.meta.from"
        :to="transactions.meta.to"
        :links="transactions.meta.links"
        :next_page="transactions.links.next"
        :prev_page="transactions.links.prev"
        :total_items="transactions.meta.total"
        :first_page_url="transactions.links.first"
        :last_page_url="transactions.links.last"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="">
              <th class="px-1">{{ $t('Invoice No') }}.</th>
              <th class="px-1">{{ $t('Anchor') }}</th>
              <th class="px-1">{{ $t('Vendor') }}</th>
              <th class="px-1">{{ $t('Request Date') }}</th>
              <th class="">{{ $t('Requested Disbursement Date') }}</th>
              <th class="px-1">{{ $t('PI Amount') }}</th>
              <th class="px-1">{{ $t('Requested Payment Amount') }}</th>
              <th class="px-1">{{ $t('Due Date') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr v-if="!transactions.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="transactions.data && transactions.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="transaction in transactions.data" :key="transaction" class="text-nowrap">
              <td
                class="text-primary text-decoration-underline px-1"
                @click="showInvoice(transaction.invoice_id, 'show-details-btn-' + transaction.invoice_id)"
                style="cursor: pointer; max-width: 220px; overflow-x: clip"
              >
                {{ transaction.invoice_number }}
              </td>
              <td class="px-1">
                <a
                  v-if="transaction.buyer_id"
                  :href="'../companies/' + transaction.buyer_id + '/details'"
                  class="text-primary text-decoration-underline"
                  >{{ transaction.buyer_name }}</a
                >
                <a
                  v-else
                  :href="'../companies/' + transaction.anchor_id + '/details'"
                  class="text-primary text-decoration-underline"
                  >{{ transaction.anchor_name }}</a
                >
              </td>
              <td class="px-1">
                <a
                  :href="'../companies/' + transaction.company_id + '/details'"
                  class="text-primary text-decoration-underline"
                >
                  {{ transaction.company_name }}
                </a>
              </td>
              <td class="px-1">{{ moment(transaction.created_date).format(date_format) }}</td>
              <td v-if="transaction.payment_request_date" class="">
                {{ moment(transaction.payment_request_date).format(date_format) }}
              </td>
              <td v-else class="">-</td>
              <td class="text-success px-1">{{ new Intl.NumberFormat().format(transaction.invoice_total_amount) }}</td>
              <td class="text-success px-1">{{ new Intl.NumberFormat().format(transaction.amount) }}</td>
              <td class="px-1">{{ moment(transaction.invoice_due_date).format(date_format) }}</td>
              <td>
                <button
                  class="d-none"
                  :id="'show-details-btn-' + transaction.invoice_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-' + transaction.invoice_id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-pi-btn-' + transaction.invoice_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + transaction.invoice_id"
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
        v-if="transactions.meta"
        :from="transactions.meta.from"
        :to="transactions.meta.to"
        :links="transactions.meta.links"
        :next_page="transactions.links.next"
        :prev_page="transactions.links.prev"
        :total_items="transactions.meta.total"
        :first_page_url="transactions.links.first"
        :last_page_url="transactions.links.last"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>
<script>
import { computed, inject, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';
import InvoiceDetails from '../../InvoiceDetails.vue';
import PaymentInstruction from '../../PaymentInstruction.vue';

export default {
  name: 'LoansPendingApproval',
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
    const transactions = ref([]);
    const cbs_transactions = ref(null);

    // Search fields
    const anchor_search = ref('');
    const vendor_search = ref('');
    const invoice_number_search = ref('');
    const requested_payment_date = ref('');
    const sort_by = ref('');
    const program_type_search = ref('');
    const from_requested_disbursement_date = ref('');
    const to_requested_disbursement_date = ref('');

    const requested_payment_date_search = ref('');

    // Pagination
    const per_page = ref(50);

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'loans-pending-approval-report'
          }
        })
        .then(response => {
          transactions.value = response.data;
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
            per_page: per_page,
            requested_payment_date: requested_payment_date.value,
            invoice_number: invoice_number_search.value,
            anchor: anchor_search.value,
            type: 'loans-pending-approval-report',
            sort_by: sort_by.value,
            program_type: program_type_search.value,
            from_requested_disbursement_date: from_requested_disbursement_date.value,
            to_requested_disbursement_date: to_requested_disbursement_date.value
          }
        })
        .then(response => {
          transactions.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      requested_payment_date_search.value = $('#requested_payment_date_search').val();
      if (requested_payment_date_search.value) {
        from_requested_disbursement_date.value = moment(requested_payment_date_search.value.split(' - ')[0]).format(
          'YYYY-MM-DD'
        );
        to_requested_disbursement_date.value = moment(requested_payment_date_search.value.split(' - ')[1]).format(
          'YYYY-MM-DD'
        );
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            anchor: anchor_search.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            requested_payment_date: requested_payment_date.value,
            type: 'loans-pending-approval-report',
            sort_by: sort_by.value,
            program_type: program_type_search.value,
            from_requested_disbursement_date: from_requested_disbursement_date.value,
            to_requested_disbursement_date: to_requested_disbursement_date.value
          }
        })
        .then(response => {
          transactions.value = response.data;
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
      anchor_search.value = '';
      vendor_search.value = '';
      invoice_number_search.value = '';
      requested_payment_date.value = '';
      sort_by.value = '';
      program_type_search.value = '';
      from_requested_disbursement_date.value = '';
      to_requested_disbursement_date.value = '';
      $('#requested_payment_date_search').val('');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'loans-pending-approval-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          transactions.value = response.data;
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
            anchor_search: anchor_search.value,
            invoice_number: invoice_number_search.value,
            requested_payment_date: requested_payment_date.value,
            account: vendor_search.value,
            type: 'loans-pending-approval-report',
            sort_by: sort_by.value,
            program_type: program_type_search.value,
            from_requested_disbursement_date: from_requested_disbursement_date.value,
            to_requested_disbursement_date: to_requested_disbursement_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payments_pending_approval_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            anchor_search: anchor_search.value,
            invoice_number: invoice_number_search.value,
            requested_payment_date: requested_payment_date.value,
            account: vendor_search.value,
            type: 'loans-pending-approval-report',
            sort_by: sort_by.value,
            program_type: program_type_search.value,
            from_requested_disbursement_date: from_requested_disbursement_date.value,
            to_requested_disbursement_date: to_requested_disbursement_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payments_pending_approval_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
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

    const getTotalAmount = invoice_items => {
      let amount = 0;
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity;
      });
      return amount;
    };

    const getTaxesAmount = invoice_taxes => {
      let amount = 0;
      invoice_taxes.forEach(item => {
        amount += item.value;
      });
      return amount;
    };

    onMounted(() => {
      getData();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            type: 'loans-pending-approval-report',
            anchor_search: anchor_search.value,
            invoice_number: invoice_number_search.value,
            requested_payment_date: requested_payment_date.value,
            account: vendor_search.value,
            sort_by: sort_by.value,
            program_type: program_type_search.value
          }
        })
        .then(response => {
          transactions.value = response.data;
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
      transactions,
      cbs_transactions,

      anchor_search,
      invoice_number_search,
      requested_payment_date,
      vendor_search,
      sort_by,
      program_type_search,
      per_page,

      invoice_details,

      changePage,

      filter,
      refresh,

      getTotalAmount,
      getTaxesAmount,
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
[data-title]:hover:after {
  opacity: 1;
  transition: all 0.1s ease 0.5s;
  visibility: visible;
}

[data-title]:after {
  content: attr(data-title);
  background-color: #0b0b0b;
  color: #f9f9f9;
  font-size: 16px;
  position: absolute;
  padding: 1px 5px 2px 5px;
  bottom: -1.6em;
  left: 100%;
  box-shadow: 1px 1px 3px #222222;
  opacity: 0;
  border: 1px solid #111111;
  z-index: 99999;
  visibility: hidden;
  border-radius: 5px;
  min-width: 250px;
  max-width: 550px;
}

[data-title] {
  position: relative;
}

input[readonly] {
  background-color: #e8e8e8;
}
</style>
