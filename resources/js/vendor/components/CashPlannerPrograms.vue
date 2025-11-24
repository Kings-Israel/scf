<template>
  <div class="card">
    <div class="card-header">
      <h6 class="card-title">{{ $t('Limit Details') }}</h6>
    </div>
    <div class="card-body">
      <pagination
        v-if="programs.meta"
        :from="programs.meta.from"
        :to="programs.meta.to"
        :links="programs.meta.links"
        :next_page="programs.meta.next_page_url"
        :prev_page="programs.meta.prev_page_url"
        :total_items="programs.meta.total"
        :first_page_url="programs.meta.first_page_url"
        :last_page_url="programs.meta.last_page_url"
        @change-page="changePage"
      ></pagination>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="text-nowrap">
              <th>{{ $t('Payment/OD Account No.') }}</th>
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
              <td>
                {{ program.payment_account_number }}
              </td>
              <td>
                <span class="fw-medium">{{ program.anchor_name }}</span>
              </td>
              <td class="text-success text-nowrap">
                {{ new Intl.NumberFormat().format(program.sanctioned_limit) }}
              </td>
              <td class="text-success text-nowrap">
                {{ program.utilized_amount < 0 ? 0 : new Intl.NumberFormat().format(program.utilized_amount) }}
              </td>
              <td class="text-success text-nowrap">
                {{ program.pipeline_amount < 0 ? 0 : new Intl.NumberFormat().format(program.pipeline_amount) }}
              </td>
              <td class="text-success text-nowrap">
                {{
                  new Intl.NumberFormat().format(
                    program.sanctioned_limit - program.utilized_amount - program.pipeline_amount
                  )
                }}
              </td>
              <td class="">{{ moment(program.limit_expiry_date).format('DD MMM YYYY') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination
        v-if="programs.meta"
        :from="programs.meta.from"
        :to="programs.meta.to"
        :links="programs.meta.links"
        :next_page="programs.meta.next_page_url"
        :prev_page="programs.meta.prev_page_url"
        :total_items="programs.meta.total"
        :first_page_url="programs.meta.first_page_url"
        :last_page_url="programs.meta.last_page_url"
        @change-page="changePage"
      ></pagination>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, inject } from 'vue';
import axios from 'axios';
import Pagination from './partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'CashPlannerPrograms',
  components: {
    Pagination
  },
  setup() {
    const base_url = inject('baseURL');
    const programs = ref([]);

    const per_page = ref(50);

    const getPrograms = async () => {
      await axios
        .get(base_url + 'cash-planner/programs', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPrograms();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.programs;
        });
    };

    return {
      moment,
      programs,
      changePage,
      per_page
    };
  }
};
</script>
