<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Name" class="form-label">{{ $t('Name') }}</label>
          <input
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Search By Name"
            aria-describedby="defaultFormControlHelp"
            v-model="name_search"
          />
        </div>
        <div class="">
          <label for="Status Search" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-1 mt-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button
          type="button"
          class="btn btn-primary text-nowrap"
          v-if="can_upload"
          data-bs-toggle="modal"
          data-bs-target="#addBaseRate"
        >
          {{ $t('New Tax Rate') }}
        </button>
        <div class="modal modal-top fade" id="addBaseRate" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBaseRate">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Tax Rate') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-add-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="form-group">
                  <label for="Name" class="form-label">{{ $t('Name') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="name" required />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Rate') }}</label>
                  <input
                    type="number"
                    min="0"
                    name="name"
                    id=""
                    class="form-control"
                    v-model="rate"
                    step=".01"
                    required
                  />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Account No') }}</label>
                  <input type="number" min="0" name="name" id="" class="form-control" v-model="account_no" required />
                </div>
                <div class="form-group my-1">
                  <label for="default" class="form-label">{{ $t('Default Rate') }}</label>
                  <div class="d-flex text-nowrap">
                    <div class="form-check form-switch mb-2">
                      <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ $t('No') }}</label>
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="flexSwitchCheckChecked"
                        name="is_default"
                        value="true"
                        v-model="is_default"
                      />
                      <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ $t('Yes') }}</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Rate') }} (%)</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Default') }}</th>
            <th>{{ $t('Account') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>
              {{ data.tax_name }}
              <i
                v-if="data.change || data.proposed_update"
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Tax Rate creation/updation awating approval"
              ></i>
            </td>
            <td>{{ data.value }}</td>
            <td>
              <span v-if="data.status == 'Active'" class="badge bg-label-success">{{ data.status }}</span>
              <span v-if="data.status == 'Inactive'" class="badge bg-label-danger">{{ data.status }}</span>
            </td>
            <td>
              <span>{{ data.is_default ? 'Yes' : 'No' }}</span>
            </td>
            <td>
              {{ data.account_no }}
            </td>
            <td class="flex">
              <i
                class="ti ti-thumb-up ti-sm text-success"
                v-if="!data.change && data.status == 'Active'"
                title="Click to Deactivate Rate"
                style="cursor: pointer"
                @click="updateActiveStatus(data.id, 'inactive')"
              ></i>
              <i
                class="ti ti-thumb-down ti-sm text-danger"
                v-if="!data.change && data.status == 'Inactive'"
                title="Click to Activate Rate"
                style="cursor: pointer"
                @click="updateActiveStatus(data.id, 'active')"
              ></i>
              <i
                v-if="!data.change && !data.proposed_update && can_update"
                class="ti ti-pencil ti-sm text-warning mx-1"
                @click="editBaseRate(data)"
                data-bs-toggle="modal"
                :data-bs-target="'#rate-' + data.id"
                style="cursor: pointer"
              ></i>
              <div class="modal modal-top fade" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateBaseRate">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Tax Rate') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'close-edit-modal-' + data.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div class="form-group">
                        <label for="Name" class="form-label">{{ $t('Name') }}</label>
                        <input type="text" class="form-control" v-model="name" required />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Rate') }}</label>
                        <input type="number" min="0" class="form-control" v-model="rate" step=".01" required />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Account No') }}</label>
                        <input type="number" min="0" class="form-control" v-model="account_no" required />
                      </div>
                      <div class="form-group my-1">
                        <label for="default" class="form-label">{{ $t('Default Rate') }}</label>
                        <div class="d-flex text-nowrap">
                          <div class="form-check form-switch mb-2">
                            <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ $t('No') }}</label>
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="flexSwitchCheckChecked"
                              name="is_default"
                              value="true"
                              v-model="is_default"
                            />
                            <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{
                              $t('Yes')
                            }}</label>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ $t('Close') }}
                      </button>
                      <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
                    </div>
                  </form>
                </div>
              </div>
              <i
                v-if="data.proposed_update && data.proposed_update.can_approve"
                class="ti ti-table ti-sm text-danger mx-1"
                title="View Proposed Changes"
                data-bs-toggle="modal"
                :data-bs-target="'#proposed-change-' + data.id"
                style="cursor: pointer"
              ></i>
              <div
                v-if="data.proposed_update && data.proposed_update.can_approve"
                class="modal modal-top fade"
                :id="'proposed-change-' + data.id"
                tabindex="-1"
              >
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Proposed Holiday Changes') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'close-changes-modal-' + data.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div v-for="(change, key) in data.proposed_update.changes" :key="key" class="row">
                        <div class="col-sm-12 col-md-6 m_title">{{ key }}:</div>
                        <div class="col-sm-12 col-md-6">{{ change }}</div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ $t('Close') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-danger"
                        @click="approveChange('App\\Models\\BankTaxRate', data.id, 'reject')"
                      >
                        {{ $t('Reject') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-primary"
                        @click="approveChange('App\\Models\\BankTaxRate', data.id, 'approve')"
                      >
                        {{ $t('Approve') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="rates.from"
      :to="rates.to"
      :links="rates.links"
      :next_page="rates.next_page_url"
      :prev_page="rates.prev_page_url"
      :total_items="rates.total"
      :first_page_url="rates.first_page_url"
      :last_page_url="rates.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, watch, onMounted, inject } from 'vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
export default {
  name: 'Taxes',
  components: {
    Pagination
  },
  props: ['bank', 'can_update', 'can_upload'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const can_update = props.can_update;
    const can_upload = props.can_upload;
    const name_search = ref('');
    const status_search = ref('');

    const rates = ref([]);

    const per_page = ref(50);

    const name = ref('');
    const rate = ref('');
    const account_no = ref('');
    const is_default = ref(true);

    const edit_id = ref('');

    const showEditModal = ref(false);

    const getRates = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/tax-rates/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editBaseRate = data => {
      edit_id.value = data.id;
      name.value = data.tax_name;
      rate.value = data.value;
      account_no.value = data.account_no;
      is_default.value = data.is_default;
      showEditModal.value = true;
    };

    const addNewBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/tax-rates/store', {
          tax_name: name.value,
          value: rate.value,
          account_no: account_no.value,
          is_default: is_default.value
        })
        .then(res => {
          toast.success(res.data.message);
          name.value = '';
          rate.value = '';
          account_no.value = '';
          is_default.value = false;
          getRates();
          document.getElementById('close-add-modal').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/tax-rates/update', {
          tax_rate_id: edit_id.value,
          tax_name: name.value,
          value: rate.value,
          account_no: account_no.value,
          is_default: is_default.value
        })
        .then(res => {
          toast.success(res.data.message);
          edit_id.value = '';
          name.value = '';
          rate.value = '';
          account_no.value = '';
          is_default.value = false;
          showEditModal.value = false;
          getRates();
          document.getElementById('close-edit-modal-' + edit_id.value);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateActiveStatus = (base_rate, status) => {
      axios
        .get(base_url + props.bank + `/configurations/tax-rates/${base_rate}/status/${status}/update`)
        .then(() => {
          getRates();
          toast.success('Status updated');
        })
        .catch(err => {
          console.log(err);
        });
    };

    const approveChange = (type, id, status) => {
      axios
        .post(base_url + props.bank + '/configurations/change/status/update', {
          type: type,
          id: id,
          status: status
        })
        .then(res => {
          toast.success(res.data.message);
          getRates();
          document.getElementById('close-changes-modal-' + id);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    onMounted(() => {
      getRates();
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/tax-rates/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      name_search.value = '';
      status_search.value = '';
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/tax-rates/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    watch(per_page, per_page => {
      axios
        .get(base_url + props.bank + '/configurations/tax-rates/data', {
          params: {
            per_page: per_page,
            name: name_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        });
    };

    return {
      per_page,
      can_upload,
      can_update,
      rates,
      name,
      rate,
      account_no,
      is_default,
      showEditModal,
      addNewBaseRate,
      updateBaseRate,
      updateActiveStatus,
      editBaseRate,
      approveChange,
      changePage,
      filter,
      refresh,
      name_search,
      status_search
    };
  }
};
</script>

<style scoped>
.no-label {
  margin-left: -60px !important;
}
.form-check {
  margin-left: 30px !important;
}
.yes-label {
  margin-left: 45px !important;
}
</style>
