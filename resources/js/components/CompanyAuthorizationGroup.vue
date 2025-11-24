<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Base Rate Code"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Status"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
      </div>
      <div class="d-flex">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#addBranch">
          {{ $t('New Group') }}
        </button>
        <div class="modal modal-top fade" id="addBranch" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBranch">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Group') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-create-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="form-group">
                  <label for="Name" class="form-label">{{ $t('Name') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" required v-model="name" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Level') }} <span class="text-danger">*</span></label>
                  <input type="text" name="name" id="" required class="form-control" v-model="level" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Product') }} <span class="text-danger">*</span></label>
                  <select name="" id="" class="form-control" v-model="program_type_id" required>
                    <option value="">{{ $t('Select') }}</option>
                    <option v-for="program_type in program_types" :key="program_type.id" :value="program_type.id">
                      {{ program_type.name }}
                    </option>
                  </select>
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Status') }} <span class="text-danger">*</span></label>
                  <select name="" id="" class="form-control" v-model="status" required>
                    <option value="active">{{ $t('Active') }}</option>
                    <option value="inactive">{{ $t('Inactive') }}</option>
                  </select>
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
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Level') }}</th>
            <th>{{ $t('Product') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>{{ data.name }}</td>
            <td>{{ data.level }}</td>
            <td>{{ data.program_type.name }}</td>
            <td>
              <div class="m_title">{{ data.status }}</div>
            </td>
            <td class="flex">
              <i
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
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Group') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'close-edit-modal' + data.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div class="form-group">
                        <label for="Name" class="form-label">{{ $t('Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="name" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label"
                          >{{ $t('Level') }} <span class="text-danger">*</span></label
                        >
                        <input type="text" name="name" id="" class="form-control" v-model="level" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label"
                          >{{ $t('Product') }} <span class="text-danger">*</span></label
                        >
                        <select name="" id="" class="form-control" v-model="program_type_id" required>
                          <option value="">{{ $t('Select') }}</option>
                          <option v-for="program_type in program_types" :key="program_type.id" :value="program_type.id">
                            {{ program_type.name }}
                          </option>
                        </select>
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label"
                          >{{ $t('Status') }} <span class="text-danger">*</span></label
                        >
                        <select name="" id="" class="form-control" v-model="status" required>
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
import Pagination from './partials/Pagination.vue';
export default {
  name: 'AuthorizationGroups',
  components: {
    Pagination
  },
  props: ['bank', 'company'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');

    const rates = ref([]);
    const program_types = ref([]);
    const per_page = ref(50);

    const name = ref('');
    const level = ref('');
    const program_type_id = ref('');
    const status = ref('');

    const edit_id = ref('');

    const showEditModal = ref(false);

    const getBranches = async () => {
      await axios
        .get(base_url + props.bank + '/companies/' + props.company + '/authorization-groups/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data.authorization_groups;
          program_types.value = response.data.program_types;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editBranch = data => {
      edit_id.value = data.id;
      name.value = data.name;
      level.value = data.level;
      program_type_id.value = data.program_type_id;
      status.value = data.status;
      showEditModal.value = true;
    };

    const addNewBranch = () => {
      axios
        .post(base_url + props.bank + '/companies/' + props.company + '/authorization-groups/store', {
          name: name.value,
          level: level.value,
          program_type_id: program_type_id.value,
          status: status.value
        })
        .then(() => {
          toast.success('Group added successfully');
          name.value = '';
          program_type_id.value = '';
          status.value = '';
          level.value = '';
          document.getElementById('close-create-modal').click();
          getBranches();
        })
        .catch(err => {
          if (err.response.data && err.response.data.message) {
            toast.error(err.response.data.message);
          }
          if (Object.keys(err.response.data).length > 0) {
            Object.keys(err.response.data).forEach(message_key => {
              toast.error(err.response.data[message_key][0]);
            });
          }
          if (err.response.data && (!err.response.data.message || Object.keys(err.response.data).length <= 0)) {
            toast.error('An error occurred');
          }
        });
    };

    const updateBranch = () => {
      axios
        .post(base_url + props.bank + '/companies/' + props.company + '/authorization-groups/update', {
          group_id: edit_id.value,
          name: name.value,
          level: level.value,
          program_type_id: program_type_id.value,
          status: status.value
        })
        .then(() => {
          toast.success('Group updated successfully');
          edit_id.value = '';
          name.value = '';
          level.value = '';
          program_type_id.value = '';
          status.value = '';
          showEditModal.value = false;
          document.getElementById('close-edit-modal-' + edit_id.value).click();
          getBranches();
        })
        .catch(err => {
          if (err.response.data && err.response.data.message) {
            toast.error(err.response.data.message);
          }
          if (Object.keys(err.response.data).length > 0) {
            Object.keys(err.response.data).forEach(message_key => {
              toast.error(err.response.data[message_key][0]);
            });
          }
          if (err.response.data && (!err.response.data.message || Object.keys(err.response.data).length <= 0)) {
            toast.error('An error occurred');
          }
        });
    };

    onMounted(() => {
      getBranches();
    });

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
      rates,
      program_types,
      name,
      level,
      program_type_id,
      status,
      showEditModal,
      addNewBranch,
      updateBranch,
      editBranch,
      changePage
    };
  }
};
</script>

<style scoped>
.m_title::first-letter {
  text-transform: capitalize;
}
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
