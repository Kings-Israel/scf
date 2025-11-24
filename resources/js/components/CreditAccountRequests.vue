<template>
  <div class="">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap">
        <div class="mx-1">
          <label for="Debit From" class="form-label">{{ $t('Debit From') }}</label>
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="Debit From" aria-describedby="defaultFormControlHelp" v-model="debit_from" v-on:keyup.enter="filter" />
        </div>
        <div class="mx-1">
          <label for="Company Name" class="form-label">{{ $t('Company Name') }}</label>
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="Company Name" aria-describedby="defaultFormControlHelp" v-model="company_name" v-on:keyup.enter="filter" />
        </div>
        <div class="mx-1">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Pay Date') }})</label>
          <input type="text" id="car_date_search" class="form-control form-search" name="car_daterange" placeholder="Select Dates" autocomplete="off" />
        </div>
        <!-- <div class="mx-1">
          <label for="From Date" class="form-label mx-1">{{ $t('From Date') }}</label>
          <input class="form-control form-search" type="date" id="" v-model="from_date" v-on:keyup.enter="filter" />
        </div>
        <div class="mx-1">
          <label for="To Date" class="form-label mx-1">{{ $t('To Date') }}</label>
          <input class="form-control form-search" type="date" id="" v-model="to_date" v-on:keyup.enter="filter" />
        </div> -->
        <div class="mx-1 d-none">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="status">
            <option value="">{{ $t('Status') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="paid">{{ $t('Paid') }}</option>
          </select>
        </div>
        <div class="mx-1">
          <label for="Sort by" class="form-label">{{ $t('Sort By') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="sort_by">
            <option value="">{{ $t('Sort By') }}</option>
            <option value="asc">{{ $t('Ascending') }}</option>
            <option value="desc">{{ $t('Descending') }}</option>
          </select>
        </div>
        <div class="mx-1 my-auto">
          <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mx-1 my-auto">
          <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="mx-1">
          <button type="button" class="btn btn-primary" @click="exprotPaymentRequests" style="height: fit-content"><i class='ti ti-download ti-sm'></i></button>
        </div>
      </div>
    </div>
    <pagination :from="requests.from" :to="requests.to" :links="requests.links" :next_page="requests.next_page_url" :prev_page="requests.prev_page_url" :total_items="requests.total" :first_page_url="requests.first_page_url" :last_page_url="requests.last_page_url" @change-page="changePage"></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Debit From') }}.</th>
            <th>{{ $t('Credit To') }}.</th>
            <th class="px-1">{{ $t('Company Name') }}</th>
            <th>{{ $t('Product Code') }}</th>
            <th class="px-1">{{ $t('Amount') }}</th>
            <th class="px-1">{{ $t('Pay Date') }}</th>
            <th class="px-1">{{ $t('Paid Date') }}</th>
            <th class="px-1">{{ $t('Status') }}</th>
            <!-- <th>Actions</th> -->
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!requests.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="requests.data && requests.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment_request in requests.data" :key="payment_request.id">
            <td>{{ payment_request.program.bank_details[0].account_number }}</td>
            <td class="text-primary text-decoration-underline" data-bs-toggle="modal" :data-bs-target="'#payment-accounts-'+payment_request.id" style="cursor: pointer;">{{ payment_request.payment_accounts.length }} {{ $t('Account(s)') }}</td>
            <td class="text-primary text-decoration-underline px-1">
              <a :href="'../companies/'+payment_request.company.id+'/details'">
                {{ payment_request.company.name }}
              </a>
            </td>
            <td>{{ payment_request.program.program_type.name }}</td>
            <td class="text-success px-1">{{ payment_request.currency }} {{ new Intl.NumberFormat().format(payment_request.amount) }}</td>
            <td class="px-1">{{ moment(payment_request.credit_date).format('DD MMM YYYY') }}</td>
            <td class="px-1">
              <span v-if="isPaid(payment_request)">{{ moment(isPaid(payment_request)).format('DD MMM YYYY') }}</span>
            </td>
            <td><span class="badge me-1 m_title px-1" :class="resolvePaymentRequestStatus(payment_request.status)">{{ payment_request.status }}</span></td>
            <td>
              <div class="modal fade" :id="'payment-accounts-'+payment_request.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Accounts') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="table-responsive">
                        <table class="table">
                          <thead>
                            <tr>
                              <th>{{ $t('Account') }}</th>
                              <th>{{ $t('Amount') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="account in payment_request.payment_accounts" :key="account.id">
                              <td>{{ account.account }}</td>
                              <td class="text-success">{{ new Intl.NumberFormat().format(account.amount) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination :from="requests.from" :to="requests.to" :links="requests.links" :next_page="requests.next_page_url" :prev_page="requests.prev_page_url" :total_items="requests.total" :first_page_url="requests.first_page_url" :last_page_url="requests.last_page_url" @change-page="changePage"></pagination>
  </div>
</template>
<script>
import { computed, inject, onMounted, ref, watch } from 'vue'
import axios from 'axios'
// Notification
import { useToast } from 'vue-toastification'
import moment from 'moment'
import Pagination from './partials/Pagination.vue'

export default {
  name: 'CreditAccountRequests',
  components: {
    Pagination
  },
  props: ['bank'],
  setup(props, context) {
    const toast = useToast()
    const base_url = inject('baseURL')
    const requests = ref([])
    const updateRequestStatus = ref('')
    const updateRequestRejectionReason = ref('')

    const per_page = ref(50)

    // Filters
    const debit_from = ref('')
    const company_name = ref('')
    const from_date = ref('')
    const to_date = ref('')
    const status = ref('')
    const sort_by = ref('desc')
    const date_search = ref('')

    const getRequests = async () => {
      await axios.get(base_url+props.bank+'/requests/credit-account-requests/data', {
        params: {
          per_page: per_page.value,
          sort_by: sort_by.value,
        }
      })
        .then(response => {
          requests.value = response.data.payment_requests
        })
        .catch(err => {
          console.log(err)
        })
    }

    context.expose({ getRequests })

    onMounted(() => {
      // getRequests()
    })

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return Math.round((tax_amount / invoice_amount) * 100)
    }

    const resolvePaymentRequestStatus = (status) => {
      let style = ''
      switch (status) {
        case 'created':
          style = 'bg-label-primary'
          break;
        case 'approved':
          style = 'bg-label-success'
          break;
        case 'paid':
          style = 'bg-label-success'
          break;
        case 'failed':
          style = 'bg-label-danger'
          break;
        case 'rejected':
          style = 'bg-label-danger'
          break;
        default:
          style = 'bg-label-primary'
          break;
      }

      return style
    }

    const resolveStatus = (status) => {
      let style = ''
      switch (status) {
        case 'pending':
          style = 'bg-label-primary'
          break;
        case 'sent':
          style = 'bg-label-secondary'
          break;
        case 'approved':
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

    const getTotalAmount = (invoice_items) => {
      let amount = 0
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity
      });
      return amount
    }

    const getTaxesAmount = (invoice_taxes) => {
      let amount = 0
      invoice_taxes.forEach(item => {
        amount += item.value
      })
      return amount
    }

    const getFeesAmount = (invoice_fees) => {
      let amount = 0
      invoice_fees.forEach(item => {
        amount += item.amount
      })
      return amount
    }

    const resolveFinancingStatus = (status) => {
      let style = ''
      switch (status) {
        case 'pending':
          style = 'bg-label-primary'
          break;
        case 'financed':
          style = 'bg-label-primary'
          break;
        case 'closed':
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

    const filter = async () => {
      date_search.value = $('#car_date_search').val()
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD')
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD')
      }
      console.log(date_search.value)
      let parent = $('.ti-search').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      await axios.get(base_url+props.bank+'/requests/credit-account-requests/data', {
        params: {
          per_page: per_page.value,
          debit_from: debit_from.value,
          company_name: company_name.value,
          from_date: from_date.value,
          to_date: to_date.value,
          status: status.value,
          sort_by: sort_by.value,
        }
      })
        .then(response => {
          requests.value = response.data.payment_requests
          parent.html('<i class="ti ti-search"></i>')
        })
    }

    const refresh = async () => {
      debit_from.value = ''
      company_name.value = ''
      from_date.value = ''
      to_date.value = ''
      status.value = ''
      sort_by.value = ''
      date_search.value = ''
      $('#car_date_search').val('')
      let parent = $('.ti-refresh').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      await axios.get(base_url+props.bank+'/requests/credit-account-requests/data', {
        params: {
          per_page: per_page.value
        }
      })
        .then(response => {
          requests.value = response.data.payment_requests
          parent.html('<i class="ti ti-refresh"></i>')
        })
    }

    const exprotPaymentRequests = () => {
      axios.get(base_url+props.bank+'/requests/credit-account-requests/data/export',
          {
            responseType: 'arraybuffer',
            method: 'GET',
            params: {
              debit_from: debit_from.value,
              company_name: company_name.value,
              from_date: from_date.value,
              to_date: to_date.value,
              status: status.value,
              sort_by: sort_by.value,
            }
          }
      )
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]))
          const fileLink = document.createElement('a')

          fileLink.href = fileURL
          fileLink.setAttribute(
            'download',
            `Credit_requests_${moment().format('Do_MMM_YYYY')}.xlsx`,
          )
          document.body.appendChild(fileLink)

          fileLink.click()
        })
        .catch(err => {
          console.log(err)
        })
    }

    const changePage = async (page) => {
      await axios.get(page, {
        params: {
          per_page: per_page.value,
          debit_from: debit_from.value,
          company_name: company_name.value,
          from_date: from_date.value,
          to_date: to_date.value,
          status: status.value,
          sort_by: sort_by.value,
        }
      })
        .then(response => {
          requests.value = response.data.payment_requests
        })
    }

    const isPaid = (payment_request) => {
      let pay_date = ''
      if (payment_request.cbs_transactions.length > 0 && payment_request.cbs_transactions[0].pay_date != null && payment_request.cbs_transactions[0].pay_date != '') {
        pay_date = payment_request.cbs_transactions[0].pay_date
      } else {
        pay_date = false
      }
      return pay_date
    }

    const updateStatus = (payment_request, status) => {
      const formData = new FormData
      formData.append('payment_request_id', payment_request.id)
      formData.append('status', status)
      formData.append('rejection_reason', updateRequestRejectionReason.value)
      axios.post(base_url+props.bank+'/requests/credit-account-requests/update', formData)
        .then(() => {
          toast.success('Payment Request updated');
          getRequests();
        })
        .catch(err => {
          console.log(err)
          toast.error('An error occurred')
        })
    }

    return {
      moment,
      requests,
      per_page,
      debit_from,
      company_name,
      from_date,
      to_date,
      status,
      sort_by,
      filter,
      refresh,
      resolvePaymentRequestStatus,
      resolveStatus,
      changePage,
      isPaid,
      getTotalAmount,
      getTaxesAmount,
      getFeesAmount,
      resolveFinancingStatus,
      updateRequestStatus,
      updateRequestRejectionReason,
      updateStatus,
      getTaxPercentage,
      exprotPaymentRequests,
    }
  }
}
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
.line-clamp {
  text-overflow: ellipsis;

  /* Needed to make it work */
  overflow: hidden;
  white-space: nowrap;
}
</style>
