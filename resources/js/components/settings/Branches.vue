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
            placeholder="Search Branch Name"
            aria-describedby="defaultFormControlHelp"
            v-model="name_search"
          />
        </div>
        <div class="">
          <label for="Status Search" class="form-label">{{ $t('Status Search') }}</label>
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
          data-bs-target="#addBranch"
        >
          {{ $t('New Branch') }}
        </button>
        <div class="modal modal-top fade" id="addBranch" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBranch">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Branch') }}</h5>
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
                  <input type="text" name="name" id="" class="form-control" v-model="name" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Code') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="code" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Location') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="location" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('City') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="city" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Address') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="address" />
                </div>
                <!-- <div class="form-group my-1">
                <label for="Name" class="form-label">Status</label>
                <select name="status" id="" class="form-control my-1" v-model="status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div> -->
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
            <th>{{ $t('Code') }}</th>
            <th>{{ $t('Location') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>
              {{ data.name }}
              <i
                v-if="data.change || data.propsed_update"
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Tax Rate creation/updation awating approval"
              ></i>
            </td>
            <td>{{ data.code }}</td>
            <td>{{ data.location }}</td>
            <td>
              <span v-if="data.status == 'Active'" class="badge bg-label-success">{{ data.status }}</span>
              <span v-if="data.status == 'Inactive'" class="badge bg-label-danger">{{ data.status }}</span>
            </td>
            <td class="flex">
              <i
                v-if="!data.change && !data.propsed_update && can_update"
                class="ti ti-pencil ti-sm text-warning mx-1"
                @click="editBranch(data)"
                data-bs-toggle="modal"
                :data-bs-target="'#rate-' + data.id"
                style="cursor: pointer"
              ></i>
              <div class="modal modal-top fade" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateBranch">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Holiday') }}</h5>
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
                        <input type="text" class="form-control" v-model="name" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Code') }}</label>
                        <input type="text" name="name" id="" class="form-control" v-model="code" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Location') }}</label>
                        <input type="text" name="name" id="" class="form-control" v-model="location" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('City') }}</label>
                        <input type="text" name="name" id="" class="form-control" v-model="city" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Address') }}</label>
                        <input type="text" name="name" id="" class="form-control" v-model="address" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Status') }}</label>
                        <select name="status" id="" class="form-control my-1" v-model="status">
                          <option value="active">{{ $t('Active') }}</option>
                          <option value="inactive">{{ $t('Inactive') }}</option>
                        </select>
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
                        @click="approveChange('App\\Models\\BankBranch', data.id, 'reject')"
                      >
                        {{ $t('Reject') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-primary"
                        @click="approveChange('App\\Models\\BankBranch', data.id, 'approve')"
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
  name: 'Branches',
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
    const code = ref('');
    const location = ref('');
    const status = ref('');
    const city = ref('');
    const address = ref('');

    const edit_id = ref('');

    const showEditModal = ref(false);

    const getBranches = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/branches/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editBranch = data => {
      edit_id.value = data.id;
      name.value = data.name;
      code.value = data.code;
      location.value = data.location;
      status.value = data.status == 'Active' ? 'active' : 'inactive';
      address.value = data.address;
      city.value = data.address;
      showEditModal.value = true;
    };

    const addNewBranch = () => {
      axios
        .post(base_url + props.bank + '/configurations/branches/store', {
          name: name.value,
          code: code.value,
          location: location.value,
          city: city.value,
          address: address.value,
          status: status.value
        })
        .then(res => {
          toast.success(res.data.message);
          name.value = '';
          code.value = '';
          location.value = '';
          address.value = '';
          city.value = '';
          status.value = 'active';
          getBranches();
          document.getElementById('close-add-modal').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateBranch = () => {
      axios
        .post(base_url + props.bank + '/configurations/branches/update', {
          branch_id: edit_id.value,
          name: name.value,
          code: code.value,
          location: location.value,
          city: city.value,
          address: address.value,
          status: status.value
        })
        .then(res => {
          toast.success(res.data.message);
          edit_id.value = '';
          name.value = '';
          code.value = '';
          location.value = '';
          city.value = '';
          address.value = '';
          status.value = 'active';
          showEditModal.value = false;
          getBranches();
          document.getElementById('clode-edit-modal-' + edit_id.value);
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
          getBranches();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    onMounted(() => {
      getBranches();
    });

    watch(per_page, per_page => {
      axios
        .get(base_url + props.bank + '/configurations/branches/data', {
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

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/branches/data', {
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
        .get(base_url + props.bank + '/configurations/branches/data', {
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value
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
      code,
      location,
      address,
      city,
      status,
      showEditModal,
      addNewBranch,
      updateBranch,
      editBranch,
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
