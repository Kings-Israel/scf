<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex">
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}.</label>
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="Invoice No" v-model="invoice_number_search" aria-describedby="defaultFormControlHelp" v-on:keyup.enter="filter" />
        </div>
        <div class="mx-1">
          <label for="PO No" class="form-label">{{ $t('PO No') }}.</label>
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="PO No." v-model="po_search" aria-describedby="defaultFormControlHelp" v-on:keyup.enter="filter" />
        </div>
        <div class="mx-1">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="invoice_status_search">
            <option value="">{{ $t('Invoice Status') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="disbursed">{{ $t('Disbursed') }}</option>
          </select>
        </div>
        <div class="mx-1">
          <label for="Financing Status" class="form-label">{{ $t('Financing Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="financing_status_search">
            <option value="">{{ $t('Financing Status') }}</option>
            <option value="financed">{{ $t('Financed') }}</option>
            <option value="closed">{{ $t('Closed') }}</option>
          </select>
        </div>
        <div class="mx-1 table-search-btn">
          <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mx-1 table-clear-btn">
          <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex align-items-end">
        <select class="form-select mx-1" id="exampleFormControlSelect1" v-model="per_page">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <i class="ti ti-download ti-xs px-1"></i> Excel
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <i class="ti ti-download ti-xs px-1"></i> PDF
        </button>
      </div>
    </div>
    <pagination
      v-if="payments.meta"
      :from="payments.meta.from"
      :to="payments.meta.to"
      :links="payments.meta.links"
      :next_page="payments.links.next"
      :prev_page="payments.links.prev"
      :total_items="payments.meta.total"
      :first_page_url="payments.links.first"
      :last_page_url="payments.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Discount Value') }}</th>
            <th>{{ $t('Invoice Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Invoice Status') }}</th>
            <th>{{ $t('Financing Status') }}</th>
            <th>{{ $t('Disbursement Date') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!payments.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="payments.data && payments.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment in payments.data" :key="payment.id">
            <td class="text-primary text-decoration-underline" style="cursor: pointer" @click="showInvoice(payment.invoice_id, 'show-details-btn-' + payment.invoice_id)">{{ payment.invoice_number }}</td>
            <td>{{ payment.anchor_name }}</td>
            <td class="text-success">{{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.invoice_total_amount) }}</span></td>
            <td class="text-success">{{ payment.currency }} <span>{{ new Intl.NumberFormat().format(payment.discount) }}</span></td>
            <td>{{ moment(payment.invoice_date).format('DD MMM YYYY') }}</td>
            <td>{{ moment(payment.due_date).format('DD MMM YYYY') }}</td>
            <td><span class="badge me-1 m_title" :class="resolveStatus(payment.invoice_status)">{{ payment.invoice_status }}</span></td>
            <td><span class="badge me-1 m_title" :class="resolveFinancingStatus(payment.invoice_financing_status)">{{ payment.invoice_financing_status }}</span></td>
            <td>{{ moment(payment.disbursement_date).format('DD MMM YYYY') }}</td>
            <td>
              <button
                class="d-none"
                :id="'show-details-btn-'+payment.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + payment.invoice_id">
              </button>
              <button
                class="d-none"
                :id="'show-pi-btn-'+payment.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + payment.invoice_id">
              </button>
              <button
                class="d-none"
                :id="'show-purchase-order-btn-'+payment.purchase_order_id"
                data-bs-toggle="modal"
                :data-bs-target="'#purchase-order-' + payment.purchase_order_id">
              </button>
              <button
                class="d-none"
                id="loading-modal-btn"
                data-bs-toggle="modal"
                data-bs-target="#loading-modal">
              </button>
              <div class="modal fade" id="loading-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                  <div class="modal-content">
                    <div class="modal-body">
                      <div class="d-flex justify-content-center">
                        <span>{{ $t('Loading') }}...</span>
                        <img src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" />
              <payment-instruction v-if="invoice_details" :invoice-details="invoice_details" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="payments.meta"
      :from="payments.meta.from"
      :to="payments.meta.to"
      :links="payments.meta.links"
      :next_page="payments.links.next"
      :prev_page="payments.links.prev"
      :total_items="payments.meta.total"
      :first_page_url="payments.links.first"
      :last_page_url="payments.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { inject, onMounted, ref, watch, nextTick } from 'vue'
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import axios from 'axios';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: "PaymentsReport",
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction,
  },
  setup(props, context) {
    const base_url = inject('baseURL')
    const payments = ref([])
    const financing_status_search = ref('')
    const invoice_number_search = ref('')
    const po_search = ref('')
    const invoice_status_search = ref('')
    const per_page = ref(50)

    const invoice_details = ref(null)

    const getRequests = () => {
      axios.get(base_url + 'reports/payments-report', {
        params: {
          per_page: per_page.value
        }
      })
      .then(response => {
        payments.value = response.data.payments
      })
      .catch(err => {
        console.log(err)
      })
    }

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click()
      await axios.get(base_url + 'invoices/'+invoice+'/details')
        .then(response => {
          invoice_details.value = response.data;
          nextTick(() => {
            document.getElementById(modal).click()
          })
        })
        .catch(err => {
          console.log(err)
        })
    }

    onMounted(() => {
      getRequests()
    })

    const resolveStatus = (status) => {
      let style = ''
      switch (status) {
        case 'created':
          style = 'bg-label-secondary'
          break;
        case 'pending':
          style = 'bg-label-primary'
          break;
        case 'submitted':
          style = 'bg-label-primary'
          break;
        case 'approved':
          style = 'bg-label-secondary'
          break;
        case 'disbursed':
          style = 'bg-label-success'
          break;
        case 'denied':
          style = 'bg-label-danger'
          break;
        default:
          style = 'bg-label-primary'
          break;
      }
      return style
    }

    const resolveFinancingStatus = (status) => {
      let style = ''
      switch (status) {
        case 'pending':
          style = 'bg-label-primary'
          break;
        case 'submitted':
          style = 'bg-label-primary';
          break;
        case 'financed':
          style = 'bg-label-success'
          break;
        case 'closed':
          style = 'bg-label-secondary'
          break;
        case 'denied':
          style = 'bg-label-danger'
          break;
        default:
          style = 'bg-label-primary'
          break;
      }
      return style
    }

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2)
    }

    const exportReport = () => {
      axios.get(base_url + 'report/dealer-payments-report/export',
          {
            responseType: 'arraybuffer',
            method: 'GET',
            params: {
              invoice_number: invoice_number_search.value,
              financing_status: financing_status_search.value,
              po: po_search.value,
              invoice_status: invoice_status_search.value
            }
          }
      )
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]))
          const fileLink = document.createElement('a')

          fileLink.href = fileURL
          fileLink.setAttribute(
            'download',
            `Payments_${moment().format('Do_MMM_YYYY')}.csv`,
          )
          document.body.appendChild(fileLink)

          fileLink.click()
        })
        .catch(err => {
          console.log(err)
        })
    }

    const exportPdfReport = () => {
      axios
        .get(base_url + 'report/dealer-payments-report/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            financing_status: financing_status_search.value,
            po: po_search.value,
            invoice_status: invoice_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Dealer_Payments_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], ([per_page]) => {
      axios.get(base_url + 'reports/payments-report', {
        params: {
          per_page: per_page,
          invoice_number: invoice_number_search.value,
          financing_status: financing_status_search.value,
          po: po_search.value,
          invoice_status: invoice_status_search.value
        }
      })
      .then(response => {
        payments.value = response.data.payments
      })
      .catch(err => {
        console.log(err)
      })
    })

    const filter = () => {
      let parent = $('.ti-search').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      axios.get(base_url + 'reports/payments-report', {
        params: {
          per_page: per_page.value,
          invoice_number: invoice_number_search.value,
          financing_status: financing_status_search.value,
          po: po_search.value,
          invoice_status: invoice_status_search.value
        }
      })
      .then(response => {
        report.value = response.data
      })
      .catch(err => {
        console.log(err)
      })
      .finally(() => {
        parent.html('<i class="ti ti-search"></i>')
      })
    }

    const refresh = () => {
      let parent = $('.ti-refresh').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      invoice_number_search.value = ''
      financing_status_search.value = ''
      po_search.value = ''
      invoice_status_search.value = ''
      axios.get(base_url + 'reports/payments-report', {
        params: {
          per_page: per_page.value,
          type: type.value
        }
      })
      .then(response => {
        report.value = response.data
      })
      .catch(err => {
        console.log(err)
      })
      .finally(() => {
        parent.html('<i class="ti ti-search"></i>')
      })
    }

    const changePage = async (page) => {
      await axios.get(page, {
        params: {
          per_page: per_page.value,
          invoice_number: invoice_number_search.value,
          financing_status: financing_status_search.value,
          po: po_search.value,
          invoice_status: invoice_status_search.value
        }
      })
        .then(response => {
          payments.value = response.data.payments
        })
    }

    return {
      moment,
      payments,
      per_page,
      invoice_number_search,
      invoice_status_search,
      po_search,
      financing_status_search,
      resolveStatus,
      resolveFinancingStatus,
      getTaxPercentage,
      changePage,
      exportReport,
      exportPdfReport,
      filter,
      refresh,

      invoice_details,
      showInvoice,
    }
  }
}
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
