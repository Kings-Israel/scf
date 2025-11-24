<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}</label>
          <input
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Invoice No"
            v-model="invoice_number_search"
            aria-describedby="defaultFormControlHelp"
            style="min-width: 216px"
          />
        </div>
        <div class="">
          <label for="From Date" class="form-label">{{ $t('Uploaded Date') }}</label>
          <input
            class="form-control"
            type="date"
            value=""
            id="html5-date-input"
            v-model="uploaded_date_search"
            style="min-width: 216px"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select" id="exampleFormControlSelect" v-model="status" style="min-width: 216px">
            <option value="">{{ $t('Select Status') }}</option>
            <option value="Successful">{{ $t('Successful') }}</option>
            <option value="Failed">{{ $t('Failed') }}</option>
          </select>
        </div>
        <div class="mt-auto">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mt-auto">
          <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex gap-1 justify-content-end mt-auto">
        <div>
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary btn-md" style="height: fit-content" @click="exportInvoices">
          <i class="ti ti-file-spreadsheet ti-sm"></i>
        </button>
      </div>
    </div>
    <pagination
      :from="invoices.from"
      :to="invoices.to"
      :links="invoices.links"
      :next_page="invoices.next_page_url"
      :prev_page="invoices.prev_page_url"
      :total_items="invoices.total"
      :first_page_url="invoices.first_page_url"
      :last_page_url="invoices.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive p-2">
      <table class="table dt-invoices">
        <thead>
          <tr>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Remarks') }}</th>
            <th>{{ $t('Created On') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!invoices.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="invoices.data && invoices.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-show="invoices.data.length > 0" v-for="invoice in invoices.data" :key="invoice.id">
            <td>
              <span>{{ invoice.invoice_number }}</span>
            </td>
            <td>
              <span v-if="invoice.status == 'Successful'" class="badge bg-label-success me-1">
                {{ invoice.status }}
              </span>
              <span v-if="invoice.status == 'Failed'" class="badge bg-label-danger me-1">
                {{ invoice.status }}
              </span>
            </td>
            <td class="">
              {{ invoice.description }}
            </td>
            <td>
              {{ moment(invoice.created_at).format('DD MMM YYYY') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="invoices.from"
      :to="invoices.to"
      :links="invoices.links"
      :next_page="invoices.next_page_url"
      :prev_page="invoices.prev_page_url"
      :total_items="invoices.total"
      :first_page_url="invoices.first_page_url"
      :last_page_url="invoices.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'UploadedInvoices',
  components: {
    Pagination
  },
  setup(props, context) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);

    const per_page = ref(50);

    const invoice_number_search = ref('');
    const uploaded_date_search = ref('');
    const status = ref('');

    const getInvoices = async () => {
      await axios
        .get(base_url + 'factoring/invoices/uploaded/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        })
        .catch(err => {
          console.log(err.message);
        });
    };

    onMounted(() => {
      getInvoices();
    });

    const exportInvoices = () => {
      axios
        .get(base_url + 'factoring/invoices/uploaded/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            uploaded_date: uploaded_date_search.value,
            status: status.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Uploaded_Invoices_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            uploaded_date: uploaded_date_search.value,
            status: status.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        })
        .catch(error => {
          console.log(error);
        });
    };

    const clearSearch = async () => {
      invoice_number_search.value = '';
      uploaded_date_search.value = '';
      status.value = '';
      await axios
        .get(base_url + 'factoring/invoices/uploaded/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    const filter = async () => {
      await axios
        .get(base_url + 'factoring/invoices/uploaded/data', {
          params: {
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            uploaded_date: uploaded_date_search.value,
            status: status.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    return {
      moment,
      per_page,
      invoices,

      // Search Params
      invoice_number_search,
      uploaded_date_search,
      status,

      changePage,
      clearSearch,
      filter,
      exportInvoices
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
