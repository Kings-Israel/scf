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
            <td>
              <span v-if="log.type == 'App\\Notifications\\InvoiceCreated'">
                {{ 'Invoice: '+log.data['notification'] }}
              </span>
              <span v-if="log.type == 'App\\Notifications\\InvoiceUpdated'">
                {{ 'Invoice: '+log.data['notification'] }}
              </span>
            </td>
            <td>
              {{ moment(log.created_at).format('DD MMM YYYY HH:mm A') }}
            </td>
            <td>
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
      await axios.get(base_url+'factoring/notifications/view/data')
        .then(response => {
          logs.value = response.data
        })
    }

    const markAsRead = (notification) => {
      axios.get(base_url+'factoring/notifications/'+notification.id+'/read')
        .then(() => {
          getLogs()
          toast.success('Notifications updated')
        })
        .catch(() => {
          toast.error('Something went wrong')
        })
    }

    const markAllAsRead = () => {
      axios.get(base_url+'factoring/notifications/all/update/read')
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
