<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Payment/OD Account" class="form-label">Payment/OD Account</label>
          <input
            type="text"
            class="form-control form-search"
            placeholder="Payment/OD Account No."
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="mx-1">
          <label for="Anchor" class="form-label">Anchor</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="anchor_search"
            placeholder="Anchor"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="mx-1">
          <label for="OD Transaction Date" class="form-label">Transactions date</label>
          <input
            type="date"
            class="form-control form-search"
            placeholder="OD Transaction Date"
            id="html5-date-input"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="mx-1 table-search-btn">
          <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mx-1 table-clear-btn">
          <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex flex-wrap justify-content-end gap-1">
        <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">50</option>
        </select>
        <button type="button" class="btn btn-primary py-2"><i class="ti ti-download ti-sm"></i></button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>Payment / OD Account No.</th>
            <th>Anchor</th>
            <th>Dealer</th>
            <th>Cash Collateral</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
            <th>OD Transaction Date</th>
            <th>OD Expiry</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0"></tbody>
      </table>
    </div>
    <!-- <pagination class="mx-2" :from="programs.from" :to="programs.to" :links="programs.links" :next_page="programs.next_page_url" :prev_page="programs.prev_page_url" :total_items="programs.total" :first_page_url="programs.first_page_url" :last_page_url="programs.last_page_url" @change-page="changePage"></pagination> -->
  </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { inject, onMounted, ref, watch } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'CashCollateralReport',
  props: ['bank'],
  components: {
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    const programs = ref([]);

    // Search fields
    const program_name_search = ref('');
    const anchor_search = ref('');
    const status_search = ref('');
    const type_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getPrograms = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            per_page: per_page.value,
            type: 'df-anchorwise-dealer-report'
          }
        })
        .then(response => {
          programs.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      // getPrograms()
    });

    const resolveProgramStatus = status => {
      switch (status) {
        case 'active':
          return 'bg-label-success';
          break;
        case 'pending':
          return 'bg-label-primary';
          break;
        case 'suspended':
          return 'bg-label-danger';
          break;
        default:
          break;
      }
    };

    const NumFormatter = data => {
      return parseFloat(data).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    };

    watch(
      [program_name_search, anchor_search, status_search, per_page],
      async ([program_name_search, anchor_search, status_search, per_page]) => {
        await axios
          .get(base_url + props.bank + '/reports/data', {
            params: {
              per_page: per_page,
              anchor: anchor_search,
              type: 'df-anchorwise-dealer-report'
            }
          })
          .then(response => {
            programs.value = response.data;
          })
          .catch(err => {
            console.log(err);
          });
      }
    );

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            type: 'df-anchorwise-dealer-report'
          }
        })
        .then(response => {
          programs.value = response.data;
        });
    };

    return {
      moment,
      programs,
      // Search fields
      anchor_search,

      // Pagination
      per_page,

      resolveProgramStatus,
      NumFormatter,
      changePage
    };
  }
};
</script>
