<template>
  <div class="card">
    <div class="card-header">
      <h6 class="card-title">{{ $t('Limit Details') }}</h6>
    </div>
    <div class="card-body">
      <pagination :from="programs.from" :to="programs.to" :links="programs.links" :next_page="programs.next_page_url" :prev_page="programs.prev_page_url" :total_items="programs.total" :first_page_url="programs.first_page_url" :last_page_url="programs.last_page_url" @change-page="changePage"></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="text-nowrap">
              <th>{{ $t('Anchor') }}</th>
              <th>{{ $t('Sanctioned Limit') }}</th>
              <th>{{ $t('Utilized Limit') }}</th>
              <th>{{ $t('Pipeline Requests') }}</th>
              <th>{{ $t('Available Limit') }}</th>
              <th>{{ $t('Expiry Date') }}</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            <tr v-if="!programs.data">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('Loading Data') }}...</span>
              </td>
            </tr>
            <tr v-if="programs.data && programs.data.length <= 0">
              <td colspan="12" class="text-center">
                <span class="text-center">{{ $t('No Data Available') }}...</span>
              </td>
            </tr>
            <tr v-for="program in programs.data" :key="program.id">
              <td><span class="fw-medium">{{ program.program.anchor.name }}</span></td>
              <td class="text-success">{{ new Intl.NumberFormat().format(program.sanctioned_limit) }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(program.utilized) }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(program.pipeline) }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(program.sanctioned_limit - program.utilized - program.pipeline) }}</td>
              <td class="">{{ moment(program.limit_expiry_date).format('DD MMM YYYY') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination :from="programs.from" :to="programs.to" :links="programs.links" :next_page="programs.next_page_url" :prev_page="programs.prev_page_url" :total_items="programs.total" :first_page_url="programs.first_page_url" :last_page_url="programs.last_page_url" @change-page="changePage"></pagination>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, inject } from 'vue'
import axios from 'axios'
import Pagination from '../partials/Pagination.vue'
import moment from 'moment'

export default {
  name: "CashPlannerPrograms",
  components: {
    Pagination
  },
  setup() {
    const base_url = inject('baseURL')
    const programs = ref([])

    const per_page = ref(50)

    const getPrograms = async () => {
      await axios.get(base_url+'planner/programs/data', {
        params: {
          per_page: per_page.value
        }
      })
        .then(response => {
          programs.value = response.data.programs
        })
        .catch(err => {
          console.log(err)
        })
    }

    onMounted(() => {
      getPrograms()
    })

    const changePage = async (page) => {
      await axios.get(page, {
        params: {
          per_page: per_page.value
        }
      })
        .then(response => {
          programs.value = response.data.pending_programs
        })
    }

    return {
      moment,
      programs,
      changePage,
    }
  }
}
</script>
