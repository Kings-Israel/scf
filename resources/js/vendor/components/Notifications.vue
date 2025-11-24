<template>
  <div class="card p-2">
    <button v-if="logs.length > 0" class="btn btn-sm btn-primary w-25" type="button" @click="markAllAsRead">{{ $t('Mark All As Read') }}</button>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Notification') }}</th>
            <th>{{ $t('Date') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!logs">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="logs && logs.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="log in logs" :key="log.id">
            <td v-if="log.type != 'App\\Notifications\\BankUserMapping' && log.type != 'App\\Notifications\\CompanyUserMapping' && log.type != 'App\\Notifications\\BankUserActivation'">
              <span v-if="log.type == 'App\\Notifications\\PurchaseOrderCreated'">
                {{ 'Purchase Order: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\PaymentRequestUpdated'">
                {{ 'Payment Request: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\FinanceRequestUpdated'">
                {{ 'Payment Request: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\InvoiceUpdated'">
                {{ 'Invoice Update: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\InvoiceCreated'">
                {{ 'Invoice Creation: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\ProgramLimitDepletion'">
                {{ 'Program Limit Depletion: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\NewProgramMapping'">
                {{ 'Program Mapping: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\ProgramLimitExpiry'">
                {{ 'Program Limit Expiry: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\ConfigurationsApproval'">
                {{ 'Configurations: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\CompanyUpdation'">
                {{ 'Company Updated: '+log.data['notification'] }}
              </span>
            </td>
            <td v-if="log.type != 'App\\Notifications\\BankUserMapping' && log.type != 'App\\Notifications\\CompanyUserMapping' && log.type != 'App\\Notifications\\BankUserActivation'">
              {{ moment(log.created_at).format('DD MMM YYYY HH:mm A') }}
            </td>
            <td v-if="log.type != 'App\\Notifications\\BankUserMapping' && log.type != 'App\\Notifications\\CompanyUserMapping' && log.type != 'App\\Notifications\\BankUserActivation'">
              <button class="btn btn-sm btn-warning" @click="markAsRead(log)">{{ $t('Mark As Read') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- <pagination :from="logs.from" :to="logs.to" :links="logs.links" :next_page="logs.next_page_url" :prev_page="logs.prev_page_url" :total_items="logs.total" :first_page_url="logs.first_page_url" :last_page_url="logs.last_page_url" @change-page="changePage"></pagination> -->
  </div>
</template>
<script>
import { useToast } from 'vue-toastification'
import { computed, watch, onMounted, ref, inject } from 'vue'
import Pagination from './partials/Pagination.vue'
import axios from 'axios'
import moment from 'moment'

export default {
  name: 'Notifications',
  components: {
    Pagination
  },
  setup(props) {
    const toast = useToast()
    const base_url = inject('baseURL')
    const logs = ref([])

    const per_page = ref(50)

    const getLogs = async () => {
      await axios.get(base_url+'notifications/view/data', {
        params: {
          per_page: per_page.value
        }
      })
        .then(response => {
          logs.value = response.data
        })
    }

    const markAsRead = (notification) => {
      axios.get(`${base_url}notifications/${notification.id}/read`)
        .then(() => {
          getLogs()
          toast.success('Notifications updated')
        })
        .catch(() => {
          toast.error('Something went wrong')
        })
    }

    const markAllAsRead = () => {
      axios.get(base_url+'notifications/all/update/read')
        .then(() => {
          getLogs()
          toast.success('Notifications updated')
        })
        .catch(() => {
          toast.error('Something went wrong')
        })
    }

    onMounted(() => {
      getLogs()
    })

    const changePage = async (page) => {
      await axios.get(page)
        .then(response => {
          logs.value = response.data.data
        })
    }

    return {
      moment,
      logs,
      per_page,
      markAsRead,
      markAllAsRead,
      changePage,
    }
  }
}
</script>
