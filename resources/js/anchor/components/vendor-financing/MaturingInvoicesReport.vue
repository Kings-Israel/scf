<template>
  <div class="card p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
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
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-md-end flex-wrap gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="downloadReport" class="btn btn-primary" style="height: fit-content">
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
            <th>{{ $t('Total Due') }}</th>
            <th>{{ $t('Due <0 Days') }}</th>
            <th>{{ $t('Due 1-7 Days') }}</th>
            <th>{{ $t('Due 8-14 Days') }}</th>
            <th>{{ $t('Due 15-21 Days') }}</th>
            <th>{{ $t('Due 22-30 Days') }}</th>
            <th>{{ $t('Due 31-45 Days') }}</th>
            <th>{{ $t('Due 46-60 Days') }}</th>
            <th>{{ $t('Due 61-75 Days') }}</th>
            <th>{{ $t('Due 76-90 Days') }}</th>
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
            <td>{{ payment.company.name }} ({{ payment.payment_account_number }})</td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.total_due) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_today) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_seven_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_fourteen_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_twenty_one_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_thirty_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_fourty_five_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_sixty_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_seventy_five_days) }}
              </span>
            </td>
            <td>
              <span class="text-success">
                {{ new Intl.NumberFormat().format(payment.due_ninety_days) }}
              </span>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="">
            <th>{{ $t('Total') }}</th>
            <th class="text-success" style="font-weight: bolder">{{ new Intl.NumberFormat().format(due_total) }}</th>
            <th class="text-success" style="font-weight: bolder">{{ new Intl.NumberFormat().format(today_total) }}</th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(seven_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(fourteen_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(twenty_one_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(thirty_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(fourty_five_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(sixty_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(seventy_five_days_total) }}
            </th>
            <th class="text-success" style="font-weight: bolder">
              {{ new Intl.NumberFormat().format(ninety_days_total) }}
            </th>
          </tr>
        </tfoot>
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
  name: 'MaturingInvoicesReport',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const base_url = inject('baseURL');
    const per_page = ref(50);
    const type = ref('maturing-invoices');
    const report = ref([]);
    const invoice_number = ref('');
    const vendor = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const date_search = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);

    const due_total = ref(0);
    const today_total = ref(0);
    const seven_days_total = ref(0);
    const fourteen_days_total = ref(0);
    const twenty_one_days_total = ref(0);
    const thirty_days_total = ref(0);
    const fourty_five_days_total = ref(0);
    const sixty_days_total = ref(0);
    const seventy_five_days_total = ref(0);
    const ninety_days_total = ref(0);

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
          due_total.value = 0;
          today_total.value = 0;
          seven_days_total.value = 0;
          fourteen_days_total.value = 0;
          twenty_one_days_total.value = 0;
          thirty_days_total.value = 0;
          fourty_five_days_total.value = 0;
          sixty_days_total.value = 0;
          seventy_five_days_total.value = 0;
          ninety_days_total.value = 0;
          report.value = response.data;
          report.value.data.forEach(data => {
            due_total.value += data.total_due;
            today_total.value += data.due_today;
            seven_days_total.value += data.due_seven_days;
            fourteen_days_total.value += data.due_fourteen_days;
            twenty_one_days_total.value += data.due_twenty_one_days;
            thirty_days_total.value += data.due_thirty_days;
            fourty_five_days_total.value += data.due_fourty_five_days;
            sixty_days_total.value += data.due_sixty_days;
            seventy_five_days_total.value += data.due_seventy_five_days;
            ninety_days_total.value += data.due_ninety_days;
          });
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

    const filter = async () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value,
            vendor: vendor.value,
            invoice_no: invoice_number.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          due_total.value = 0;
          today_total.value = 0;
          seven_days_total.value = 0;
          fourteen_days_total.value = 0;
          twenty_one_days_total.value = 0;
          thirty_days_total.value = 0;
          fourty_five_days_total.value = 0;
          sixty_days_total.value = 0;
          seventy_five_days_total.value = 0;
          ninety_days_total.value = 0;
          report.value = response.data;
          report.value.data.forEach(data => {
            due_total.value += data.total_due;
            today_total.value += data.due_today;
            seven_days_total.value += data.due_seven_days;
            fourteen_days_total.value += data.due_fourteen_days;
            twenty_one_days_total.value += data.due_twenty_one_days;
            thirty_days_total.value += data.due_thirty_days;
            fourty_five_days_total.value += data.due_fourty_five_days;
            sixty_days_total.value += data.due_sixty_days;
            seventy_five_days_total.value += data.due_seventy_five_days;
            ninety_days_total.value += data.due_ninety_days;
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const refresh = async () => {
      vendor.value = '';
      invoice_number.value = '';
      from_date.value = '';
      to_date.value = '';
      date_search.value = '';
      $('#date_search').val('');
      await axios
        .get('report', {
          params: {
            type: type.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          due_total.value = 0;
          today_total.value = 0;
          seven_days_total.value = 0;
          fourteen_days_total.value = 0;
          twenty_one_days_total.value = 0;
          thirty_days_total.value = 0;
          fourty_five_days_total.value = 0;
          sixty_days_total.value = 0;
          seventy_five_days_total.value = 0;
          ninety_days_total.value = 0;
          report.value = response.data;
          report.value.data.forEach(data => {
            due_total.value += data.total_due;
            today_total.value += data.due_today;
            seven_days_total.value += data.due_seven_days;
            fourteen_days_total.value += data.due_fourteen_days;
            twenty_one_days_total.value += data.due_twenty_one_days;
            thirty_days_total.value += data.due_thirty_days;
            fourty_five_days_total.value += data.due_fourty_five_days;
            sixty_days_total.value += data.due_sixty_days;
            seventy_five_days_total.value += data.due_seventy_five_days;
            ninety_days_total.value += data.due_ninety_days;
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
        .get(base_url + 'reports/report/' + type.value + '/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Maturing_payments_report_${moment().format('Do_MMM_YYYY')}.csv`);
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
            vendor: vendor.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Maturing_payments_${moment().format('Do_MMM_YYYY')}.pdf`);
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
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          report.value = response.data;
          due_total.value = 0;
          today_total.value = 0;
          seven_days_total.value = 0;
          fourteen_days_total.value = 0;
          twenty_one_days_total.value = 0;
          thirty_days_total.value = 0;
          fourty_five_days_total.value = 0;
          sixty_days_total.value = 0;
          seventy_five_days_total.value = 0;
          ninety_days_total.value = 0;
          report.value.data.forEach(data => {
            due_total.value += data.total_due;
            today_total.value += data.due_today;
            seven_days_total.value += data.due_seven_days;
            fourteen_days_total.value += data.due_fourteen_days;
            twenty_one_days_total.value += data.due_twenty_one_days;
            thirty_days_total.value += data.due_thirty_days;
            fourty_five_days_total.value += data.due_fourty_five_days;
            sixty_days_total.value += data.due_sixty_days;
            seventy_five_days_total.value += data.due_seventy_five_days;
            ninety_days_total.value += data.due_ninety_days;
          });
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
      vendor,
      from_date,
      to_date,
      resolveFinancingStatus,
      downloadReport,
      exportPdfReport,
      filter,
      refresh,
      changePage,
      invoice_details,
      invoice_details_key,
      showInvoice,
      getTaxPercentage,

      downloadingExcel,
      downloadingPdf,
      spinner,

      due_total,
      today_total,
      seven_days_total,
      fourteen_days_total,
      twenty_one_days_total,
      thirty_days_total,
      fourty_five_days_total,
      sixty_days_total,
      seventy_five_days_total,
      ninety_days_total
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
