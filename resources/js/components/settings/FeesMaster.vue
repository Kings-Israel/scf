<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Name Search" class="form-label">{{ $t('Name') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Search By Name"
            aria-describedby="defaultFormControlHelp"
            v-model="name_search"
          />
        </div>
        <div class="">
          <label for="Account Number" class="form-label">{{ $t('Account Number') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Search By Account Number"
            aria-describedby="defaultFormControlHelp"
            v-model="account_number_search"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="status_search">
            <option value="">{{ $t('Search by Status') }}</option>
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
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
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
          {{ $t('Add Fee') }}
        </button>
        <div class="modal modal-top fade" id="addBaseRate" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <form class="modal-content" method="POST" @submit.prevent="addNewBaseRate">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Fee') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-add-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="row">
                  <div class="col-sm-12 col-md-6 form-group">
                    <label for="Name" class="form-label">{{ $t('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="name" required />
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label"
                      >{{ $t('Account Number') }} <span class="text-danger">*</span></label
                    >
                    <input type="text" class="form-control" v-model="account_number" required />
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label"
                      >{{ $t('Fee Account Branch Specific') }} <span class="text-danger">*</span></label
                    >
                    <select name="" id="" class="form-control" v-model="fees_account_branch_specific" required>
                      <option value="yes">{{ $t('Yes') }}</option>
                      <option value="no">{{ $t('No') }}</option>
                    </select>
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label"
                      >{{ $t('Discount Bearing') }} <span class="text-danger">*</span></label
                    >
                    <select name="" id="" class="form-control" v-model="discount_bearing" required>
                      <option value="yes">{{ $t('Yes') }}</option>
                      <option value="no">{{ $t('No') }}</option>
                    </select>
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label">{{ $t('Tax Name') }}</label>
                    <input type="text" min="0" class="form-control" v-model="tax_name" />
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label">{{ $t('Tax Percent') }}</label>
                    <input type="number" step=".01" max="100" min="0" class="form-control" v-model="tax_percent" />
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label">{{ $t('Tax Account Number') }}</label>
                    <input type="text" class="form-control" v-model="tax_account_number" />
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label">{{ $t('Tax Account Branch Specific') }}</label>
                    <select name="" id="" class="form-control" v-model="tax_account_branch_specific">
                      <option value="yes">{{ $t('Yes') }}</option>
                      <option value="no">{{ $t('No') }}</option>
                    </select>
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label">{{ $t('Service Code') }}</label>
                    <input type="text" class="form-control" v-model="service_code" />
                  </div>
                  <div class="col-sm-12 col-md-6 form-group my-1">
                    <label for="Name" class="form-label">{{ $t('SAC') }}</label>
                    <input type="text" class="form-control" v-model="sac" />
                  </div>
                  <!-- <div class="col-sm-12 col-md-6 form-group my-1">
                  <label for="Name" class="form-label">Status</label>
                  <select name="" id="" class="form-control" v-model="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div> -->
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
            <th>{{ $t('Account Number') }}</th>
            <th>{{ $t('Fee A/C Branch Specific') }}</th>
            <th>{{ $t('Discount Bearing') }}</th>
            <th>{{ $t('Tax Name') }}</th>
            <th>{{ $t('Tax Percent') }}</th>
            <th>{{ $t('Tax Account Number') }}</th>
            <th>{{ $t('Tax A/C Branch Specific') }}</th>
            <th>{{ $t('Service Code') }}</th>
            <th>{{ $t('SAC') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>
              {{ data.name }}
              <i
                v-if="data.change || data.proposed_update"
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Fee creation/updation awating approval"
              ></i>
            </td>
            <td>{{ data.account_number }}</td>
            <td>{{ data.fee_account_branch_specific ? 'Yes' : 'No' }}</td>
            <td>{{ data.discount_bearing ? 'Yes' : 'No' }}</td>
            <td>{{ data.tax_name }}</td>
            <td>{{ data.tax_percent }}</td>
            <td>{{ data.tax_account_number }}</td>
            <td>{{ data.tax_account_branch_specific ? 'Yes' : 'No' }}</td>
            <td>{{ data.service_code }}</td>
            <td>{{ data.sac }}</td>
            <td>
              <span v-if="data.status == 'Active'" class="badge bg-label-success">{{ data.status }}</span>
              <span v-if="data.status == 'Inactive'" class="badge bg-label-danger">{{ data.status }}</span>
            </td>
            <td class="flex">
              <i
                v-if="!data.change && !data.proposed_update && can_update"
                class="ti ti-pencil ti-sm text-warning mx-1"
                @click="editBaseRate(data)"
                data-bs-toggle="modal"
                :data-bs-target="'#rate-' + data.id"
                style="cursor: pointer"
              ></i>
              <div class="modal modal-top fade" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <form class="modal-content" method="POST" @submit.prevent="updateBaseRate">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Fee') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'close-edit-modal-' + data.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div class="row">
                        <div class="col-sm-12 col-md-6 form-group">
                          <label for="Name" class="form-label">{{ $t('Name') }}</label>
                          <input type="text" class="form-control" v-model="name" required />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Account Number') }}</label>
                          <input type="text" class="form-control" v-model="account_number" required />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Fee Account Branch Specific') }}</label>
                          <select name="" id="" class="form-control" v-model="fees_account_branch_specific" required>
                            <option value="yes">{{ $t('Yes') }}</option>
                            <option value="no">{{ $t('No') }}</option>
                          </select>
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Discount Bearing') }}</label>
                          <select name="" id="" class="form-control" v-model="discount_bearing" required>
                            <option value="yes">{{ $t('Yes') }}</option>
                            <option value="no">{{ $t('No') }}</option>
                          </select>
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Tax Name') }}</label>
                          <input type="text" min="0" class="form-control" v-model="tax_name" />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Tax Percent') }}</label>
                          <input
                            type="number"
                            step=".01"
                            max="100"
                            min="0"
                            class="form-control"
                            v-model="tax_percent"
                          />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Tax Account Number') }}</label>
                          <input type="text" class="form-control" v-model="tax_account_number" />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Tax Account Branch Specific') }}</label>
                          <select name="" id="" class="form-control" v-model="tax_account_branch_specific">
                            <option value="yes">{{ $t('Yes') }}</option>
                            <option value="no">{{ $t('No') }}</option>
                          </select>
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Service Code') }}</label>
                          <input type="text" class="form-control" v-model="service_code" />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('SAC') }}</label>
                          <input type="text" class="form-control" v-model="sac" />
                        </div>
                        <div class="col-sm-12 col-md-6 form-group my-1">
                          <label for="Name" class="form-label">{{ $t('Status') }}</label>
                          <select name="" id="" class="form-control" v-model="status">
                            <option value="active">{{ $t('Active') }}</option>
                            <option value="inactive">{{ $t('Inactive') }}</option>
                          </select>
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
                        id="close-changes-modal"
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
                        @click="approveChange('App\\Models\\BankFeesMaster', data.id, 'reject')"
                      >
                        {{ $t('Reject') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-primary"
                        @click="approveChange('App\\Models\\BankFeesMaster', data.id, 'approve')"
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
  name: 'FeesMaster',
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
    const account_number_search = ref('');

    const rates = ref([]);

    const per_page = ref(50);

    const name = ref('');
    const account_number = ref('');
    const fees_account_branch_specific = ref('');
    const discount_bearing = ref('');
    const tax_name = ref('');
    const tax_percent = ref('');
    const tax_account_number = ref('');
    const tax_account_branch_specific = ref('');
    const service_code = ref('');
    const sac = ref('');
    const status = ref('active');

    const edit_id = ref('');

    const showEditModal = ref(false);

    const getData = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/fees-master/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value,
            account_number: account_number_search.value
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
      name.value = data.name;
      account_number.value = data.account_number;
      fees_account_branch_specific.value = data.fees_account_branch_specific ? 'yes' : 'no';
      discount_bearing.value = data.discount_bearing ? 'yes' : 'no';
      tax_name.value = data.tax_name;
      tax_percent.value = data.tax_percent;
      tax_account_number.value = data.tax_account_number;
      tax_account_branch_specific.value = data.tax_account_branch_specific ? 'yes' : 'no';
      service_code.value = data.service_code;
      sac.value = data.sac;
      status.value = data.status == 'Active' ? 'active' : 'inactive';
      showEditModal.value = true;
    };

    const addNewBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/fees-master/store', {
          name: name.value,
          account_number: account_number.value,
          fees_account_branch_specific: fees_account_branch_specific.value,
          discount_bearing: discount_bearing.value,
          tax_name: tax_name.value,
          tax_percent: tax_percent.value,
          tax_account_number: tax_account_number.value,
          tax_account_branch_specific: tax_account_branch_specific.value,
          service_code: service_code.value,
          sac: sac.value,
          status: status.value
        })
        .then(res => {
          toast.success(res.data.message);
          name.value = '';
          account_number.value = '';
          fees_account_branch_specific.value = '';
          discount_bearing.value = '';
          tax_name.value = '';
          tax_percent.value = '';
          tax_account_number.value = '';
          tax_account_branch_specific.value = '';
          service_code.value = '';
          sac.value = '';
          status.value = '';
          getData();
          document.getElementById('close-add-modal').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/fees-master/update', {
          fees_master_id: edit_id.value,
          name: name.value,
          account_number: account_number.value,
          fees_account_branch_specific: fees_account_branch_specific.value,
          discount_bearing: discount_bearing.value,
          tax_name: tax_name.value,
          tax_percent: tax_percent.value,
          tax_account_number: tax_account_number.value,
          tax_account_branch_specific: tax_account_branch_specific.value,
          service_code: service_code.value,
          sac: sac.value,
          status: status.value
        })
        .then(res => {
          toast.success(res.data.message);
          edit_id.value = '';
          name.value = '';
          account_number.value = '';
          fees_account_branch_specific.value = '';
          discount_bearing.value = '';
          tax_name.value = '';
          tax_percent.value = '';
          tax_account_number.value = '';
          tax_account_branch_specific.value = '';
          service_code.value = '';
          sac.value = '';
          status.value = '';
          showEditModal.value = false;
          document.getElementById('close-edit-modal-' + edit_id.value).click();
          getData();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
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
          getData();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    onMounted(() => {
      getData();
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/fees-master/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value,
            account_number: account_number_search.value
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
      account_number.value = '';
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/fees-master/data', {
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
        .get(base_url + props.bank + '/configurations/fees-master/data', {
          params: {
            per_page: per_page,
            name: name_search.value,
            status: status_search.value,
            account_number: account_number_search.value
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
            status: status_search.value,
            account_number: account_number_search.value
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
      account_number,
      fees_account_branch_specific,
      discount_bearing,
      tax_name,
      tax_percent,
      tax_account_number,
      tax_account_branch_specific,
      service_code,
      sac,
      status,
      showEditModal,
      addNewBaseRate,
      updateBaseRate,
      editBaseRate,
      approveChange,
      changePage,
      filter,
      refresh,
      name_search,
      status_search,
      account_number_search
    };
  }
};
</script>

<style scoped>
.m_title::first-letter {
  text-transform: capitalize;
}
</style>
