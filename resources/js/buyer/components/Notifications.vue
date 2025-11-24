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
            <td v-if="log.type != 'App\\Notifications\\BankUserMapping' && log.type != 'App\\Notifications\\CompanyUserMapping' && log.type != 'App\\Notifications\\BankUserActivation' && log.type != 'App\\Notifications\\PurchaseOrderCreated' && log.type != 'App\\Notifications\\FinanceRequestUpdated'">
              <span v-if="log.type == 'App\\Notifications\\InvoiceCreated'">
                {{ 'Invoice: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\InvoiceUpdated'">
                {{ 'Invoice: '+log.data['notification'] }}
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
            </td>
            <td v-if="log.type != 'App\\Notifications\\BankUserMapping' && log.type != 'App\\Notifications\\CompanyUserMapping' && log.type != 'App\\Notifications\\BankUserActivation' && log.type != 'App\\Notifications\\PurchaseOrderCreated' && log.type != 'App\\Notifications\\FinanceRequestUpdated'">
              {{ moment(log.created_at).format('DD MMM YYYY HH:mm A') }}
            </td>
            <td v-if="log.type != 'App\\Notifications\\BankUserMapping' && log.type != 'App\\Notifications\\CompanyUserMapping' && log.type != 'App\\Notifications\\BankUserActivation' && log.type != 'App\\Notifications\\PurchaseOrderCreated' && log.type != 'App\\Notifications\\FinanceRequestUpdated'">
              <button class="btn btn-sm btn-warning" @click="markAsRead(log)">{{ $t('Mark As Read') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<script>
import { useToast } from 'vue-toastification'
import { computed, watch, onMounted, ref, inject } from 'vue'
import axios from 'axios'
import moment from 'moment'

export default {
  name: 'Notifications',
  setup(props) {
    const toast = useToast()
    const logs = ref([])
    const base_url = inject('baseURL')

    const getLogs = async () => {
      await axios.get(base_url+'notifications/view/data')
        .then(response => {
          logs.value = response.data
        })
    }

    const markAsRead = (notification) => {
      axios.get(base_url+'notifications/'+notification.id+'/read')
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

    return {
      moment,
      logs,
      markAsRead,
      markAllAsRead,
    }
  }
}
</script>
