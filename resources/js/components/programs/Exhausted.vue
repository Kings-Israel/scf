<template>
  <div class="p-2">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Name" class="form-label">{{ $t('Program Name') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="program_name_search"
            placeholder="Program Name"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Name" class="form-label">{{ $t('Anchor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="anchor_search"
            placeholder="Anchor"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Program Type" class="form-label">{{ $t('Program Type') }}</label>
          <select class="form-select form-search" v-model="type_search">
            <option value="">{{ $t('Program Type') }}</option>
            <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
            <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
            <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
            <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
    <pagination
      v-if="programs.meta"
      class="mx-2"
      :from="programs.meta.from"
      :to="programs.meta.to"
      :links="programs.meta.links"
      :next_page="programs.links.next"
      :prev_page="programs.links.prev"
      :total_items="programs.meta.total"
      :first_page_url="programs.links.first"
      :last_page_url="programs.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Program Name') }}</th>
            <th>{{ $t('Anchor Name') }}</th>
            <th>{{ $t('Product Type') }}</th>
            <th>{{ $t('Product Code') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Total Program Limit') }}</th>
            <th>{{ $t('Utilized Limit') }}</th>
            <th>{{ $t('Actions') }}</th>
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
          <tr class="text-nowrap" v-for="program in programs.data" :key="program.id">
            <td class="text-primary">
              <a v-if="program.can_view" :href="'programs/' + program.id + '/details'">
                {{ program.name }}
              </a>
              <span v-else>{{ program.name }}</span>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Program Limit is almost depleted."
                v-if="program.utilized_percentage_ratio > 90"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Pending Program Changes awating approval"
                v-if="program.proposed_update > 0"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Program Creation/Updation Awaiting Approval."
                v-if="program.account_status == 'pending'"
              ></i>
            </td>
            <td class="text-primary text-decoration-underline">
              <a v-if="program.anchor.can_view" :href="'companies/' + program.anchor.id + '/details'">{{
                program.anchor.name
              }}</a>
              <span v-else>{{ program.anchor.name }}</span>
            </td>
            <td class="">{{ program.program_type.name }}</td>
            <td>{{ program.program_code ? program.program_code.abbrev : '-' }}</td>
            <td>
              <span class="badge me-1 m_title" :class="resolveProgramStatus(program.account_status)">{{
                program.account_status
              }}</span>
            </td>
            <td class="text-success">{{ program.bank.default_currency }} {{ NumFormatter(program.program_limit) }}</td>
            <td class="text-success">
              {{ program.bank.default_currency }} {{ new Intl.NumberFormat().format(program.utilized_amount) }}
            </td>
            <td class="">
              <div class="d-flex">
                <a
                  v-if="program.can_view"
                  :href="'programs/' + program.id + '/details'"
                  class="badge bg-label-primary rounded-pill p-1 mx-1"
                  :title="$t('View Program Details')"
                  ><i class="ti ti-eye ti-sm"></i
                ></a>
                <a
                  v-if="program.can_edit"
                  :href="'programs/' + program.id + '/edit'"
                  class="badge bg-label-warning rounded-pill p-1 mx-1"
                  :title="$t('Edit Program')"
                  ><i class="ti ti-pencil ti-sm"></i
                ></a>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="programs.meta"
      class="mx-2"
      :from="programs.meta.from"
      :to="programs.meta.to"
      :links="programs.meta.links"
      :next_page="programs.links.next"
      :prev_page="programs.links.prev"
      :total_items="programs.meta.total"
      :first_page_url="programs.links.first"
      :last_page_url="programs.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { onMounted, ref, watch, inject } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'Programs',
  props: ['bank', 'can_add'],
  components: {
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    const programs = ref([]);
    const can_add = props.can_add;

    // Search fields
    const program_name_search = ref('');
    const anchor_search = ref('');
    const status_search = ref('');
    const type_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getPrograms = async () => {
      axios
        .get('programs/exhausted/data?per_page=' + per_page.value)
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPrograms();
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

    const updateActiveStatus = (program, status) => {
      axios
        .get(`programs/${program}/update/status/${status}`)
        .then(() => {
          getPrograms();
          toast.success('Program status updated');
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get('programs/exhausted/data', {
          params: {
            per_page: per_page,
            name: program_name_search.value,
            anchor: anchor_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        });
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get('programs/data', {
          params: {
            per_page: per_page.value,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      program_name_search.value = '';
      anchor_search.value = '';
      status_search.value = '';
      type_search.value = '';
      await axios
        .get('programs/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    return {
      moment,
      can_add,
      programs,
      // Search fields
      program_name_search,
      anchor_search,
      type_search,

      // Pagination
      per_page,

      filter,
      refresh,

      resolveProgramStatus,
      NumFormatter,
      updateActiveStatus,
      changePage
    };
  }
};
</script>
