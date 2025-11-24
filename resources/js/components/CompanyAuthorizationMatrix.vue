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
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#addBranch">
          {{ $t('New Rule') }}
        </button>
        <div class="modal modal-center fade modal-lg" id="addBranch" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBranch">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Rule') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-create-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="row">
                  <div class="col-4">
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
                  </div>
                  <div class="col-4">
                    <div class="form-group">
                      <label for="Name" class="form-label">{{ $t('Name') }} <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="name" autocomplete="off" />
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('Status') }} <span class="text-danger">*</span></label>
                      <select name="" id="" class="form-control" v-model="status" required>
                        <option value="active">{{ $t('Active') }}</option>
                        <option value="inactive">{{ $t('Inactive') }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label"
                        >{{ $t('Minimum PI Amount') }} <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        name="number"
                        min="1"
                        id=""
                        class="form-control"
                        v-model="min_pi_amount"
                        autocomplete="off"
                      />
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label"
                        >{{ $t('Maximum PI Amount') }} <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        name="number"
                        min="1"
                        id=""
                        class="form-control"
                        v-model="max_pi_amount"
                        autocomplete="off"
                      />
                    </div>
                  </div>
                </div>
                <br />
                <div class="d-flex justify-content-between">
                  <h6>{{ $t('Rules') }}</h6>
                  <button class="btn btn-label-danger btn-sm" type="button" @click="addCount">
                    {{ $t('Add Rule') }}
                  </button>
                </div>
                <div class="row" v-for="count in main_rules_count" :key="count">
                  <div class="col-12 mt-1" v-if="count > 1">
                    <hr />
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('And/Or') }}</label>
                      <select name="" id="" class="form-control" v-model="rules_operators[count - 1]">
                        <option value="and" selected>{{ $t('And') }}</option>
                        <option value="or">{{ $t('Or') }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('Group') }}</label>
                      <select name="" id="" class="form-control" v-model="rules_groups[count - 1]">
                        <option v-for="group in authorization_groups" :key="group.id" :value="group.id">
                          {{ group.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('Minimum Approval') }}</label>
                      <input
                        type="text"
                        name="number"
                        id=""
                        class="form-control"
                        v-model="rules_min_approval[count - 1]"
                        autocomplete="off"
                      />
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
            <th>{{ $t('Product') }}</th>
            <th>{{ $t('Min PI Amount') }}</th>
            <th>{{ $t('Max PI Amount') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>{{ data.name }}</td>
            <td>{{ data.program_type.name }}</td>
            <td>{{ new Intl.NumberFormat().format(data.min_pi_amount) }}</td>
            <td>{{ new Intl.NumberFormat().format(data.max_pi_amount) }}</td>
            <td class="flex">
              <i
                class="ti ti-pencil ti-sm text-warning mx-1"
                @click="editBranch(data)"
                data-bs-toggle="modal"
                :data-bs-target="'#rate-' + data.id"
                style="cursor: pointer"
              ></i>
              <div class="modal modal-center fade modal-lg" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateBranch">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Rule') }}</h5>
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
                        <div class="col-4">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label"
                              >{{ $t('Product') }} <span class="text-danger">*</span></label
                            >
                            <select name="" id="" class="form-control" v-model="program_type_id" required>
                              <option value="">{{ $t('Select') }}</option>
                              <option
                                v-for="program_type in program_types"
                                :key="program_type.id"
                                :value="program_type.id"
                              >
                                {{ program_type.name }}
                              </option>
                            </select>
                          </div>
                        </div>
                        <div class="col-4">
                          <div class="form-group">
                            <label for="Name" class="form-label"
                              >{{ $t('Name') }} <span class="text-danger">*</span></label
                            >
                            <input type="text" class="form-control" v-model="name" />
                          </div>
                        </div>
                        <div class="col-4">
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
                        <div class="col-6">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label"
                              >{{ $t('Minimum PI Amount') }} <span class="text-danger">*</span></label
                            >
                            <input type="text" name="name" id="" class="form-control" v-model="min_pi_amount" />
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label"
                              >{{ $t('Maximum PI Amount') }} <span class="text-danger">*</span></label
                            >
                            <input type="text" name="name" id="" class="form-control" v-model="max_pi_amount" />
                          </div>
                        </div>
                      </div>
                      <br />
                      <div class="d-flex justify-content-between">
                        <h6>{{ $t('Rules') }}</h6>
                        <button class="btn btn-label-danger btn-sm" type="button" @click="addCount">
                          {{ $t('Add Rule') }}
                        </button>
                      </div>
                      <div class="row" v-for="count in main_rules_count" :key="count">
                        <div class="col-12 mt-1" v-if="count > 1">
                          <hr />
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">And/Or</label>
                            <select name="" id="" class="form-control" v-model="rules_operators[count - 1]">
                              <option value="and" selected>{{ $t('And') }}</option>
                              <option value="or">{{ $t('Or') }}</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">{{ $t('Group') }}</label>
                            <select name="" id="" class="form-control" v-model="rules_groups[count - 1]">
                              <option v-for="group in authorization_groups" :key="group.id" :value="group.id">
                                {{ group.name }}
                              </option>
                            </select>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">{{ $t('Minimum Approval') }}</label>
                            <input
                              type="text"
                              name="number"
                              id=""
                              class="form-control"
                              v-model="rules_min_approval[count - 1]"
                              autocomplete="off"
                            />
                          </div>
                        </div>
                        <div class="col-12" v-if="count > 1">
                          <div class="w-25">
                            <i class="ti ti-trash text-danger" @click="deleteCount(count)"></i>
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
  name: 'AuthorizationMatrix',
  components: {
    Pagination
  },
  props: ['bank', 'company'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');

    const rates = ref([]);
    const program_types = ref([]);
    const authorization_groups = ref([]);
    const per_page = ref(50);

    const name = ref('');
    const program_type_id = ref('');
    const status = ref('');
    const min_pi_amount = ref('');
    const max_pi_amount = ref('');
    const rules = ref([]);
    const rules_operators = ref([]);
    const rules_groups = ref([]);
    const rules_min_approval = ref([]);

    const edit_id = ref('');

    const main_rules_count = ref(1);

    const showEditModal = ref(false);

    watch(program_type_id, new_program_type_id => {
      if (new_program_type_id) {
        axios
          .get(
            base_url +
              props.bank +
              '/companies/' +
              props.company +
              '/authorization-matrices/program-type/' +
              new_program_type_id
          )
          .then(response => {
            authorization_groups.value = response.data;
          })
          .catch(err => {
            console.log(err);
          });
      } else {
        authorization_groups.value = [];
      }
    });

    const getBranches = async () => {
      await axios
        .get(base_url + props.bank + '/companies/' + props.company + '/authorization-matrices/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data.authorization_matrices;
          program_types.value = response.data.program_types;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editBranch = data => {
      edit_id.value = data.id;
      name.value = data.name;
      program_type_id.value = data.program_type_id;
      status.value = data.status;
      min_pi_amount.value = data.min_pi_amount;
      max_pi_amount.value = data.max_pi_amount;
      main_rules_count.value = 0;
      data.rules.forEach(rule => {
        if (rule.operator) {
          rules_operators.value[main_rules_count.value] = rule.operator;
        }
        rules_groups.value[main_rules_count.value] = rule.group_id;
        rules_min_approval.value[main_rules_count.value] = rule.min_approval;
        main_rules_count.value += 1;
      });
      showEditModal.value = true;
    };

    const addNewBranch = () => {
      const formData = new FormData();
      formData.append('name', name.value);
      formData.append('program_type_id', program_type_id.value);
      formData.append('status', status.value);
      formData.append('min_pi_amount', min_pi_amount.value);
      formData.append('max_pi_amount', max_pi_amount.value);
      formData.append('groups', JSON.stringify(rules_groups.value));
      formData.append('rules_min_approvals', JSON.stringify(rules_min_approval.value));
      formData.append('rules_operators', JSON.stringify(rules_operators.value));
      axios
        .post(base_url + props.bank + '/companies/' + props.company + '/authorization-matrices/store', formData)
        .then(() => {
          toast.success('Rule added successfully');
          name.value = '';
          program_type_id.value = '';
          status.value = '';
          min_pi_amount.value = '';
          max_pi_amount.value = '';
          main_rules_count.value = 1;
          rules.value = [];
          rules_operators.value = [];
          rules_groups.value = [];
          rules_min_approval.value = [];
          getBranches();
          document.getElementById('close-create-modal').click();
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
      const formData = new FormData();
      formData.append('matrix_id', edit_id.value);
      formData.append('name', name.value);
      formData.append('program_type_id', program_type_id.value);
      formData.append('status', status.value);
      formData.append('min_pi_amount', min_pi_amount.value);
      formData.append('max_pi_amount', max_pi_amount.value);
      formData.append('groups', JSON.stringify(rules_groups.value));
      formData.append('rules_min_approvals', JSON.stringify(rules_min_approval.value));
      formData.append('rules_operators', JSON.stringify(rules_operators.value));
      axios
        .post(base_url + props.bank + '/companies/' + props.company + '/authorization-matrices/update', formData)
        .then(() => {
          toast.success('Rule updated successfully');
          document.getElementById('close-edit-modal-' + edit_id.value).click();
          edit_id.value = '';
          name.value = '';
          program_type_id.value = '';
          status.value = '';
          min_pi_amount.value = '';
          max_pi_amount.value = '';
          main_rules_count.value = 1;
          rules.value = [];
          rules_operators.value = [];
          rules_groups.value = [];
          rules_min_approval.value = [];
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

    const addCount = () => {
      main_rules_count.value += 1;
      rules_operators.value.length += 1;
      rules_groups.value.length += 1;
      rules_min_approval.value.length += 1;
    };

    const deleteCount = index => {
      main_rules_count.value -= 1;
      let operator_index = rules_operators.value.findIndex(ind => ind == index);
      rules_operators.value.splice(operator_index, -1);
      rules_operators.value.length -= 1;
      let rule_group_index = rules_groups.value.findIndex(ind => ind == index);
      rules_groups.value.splice(rule_group_index, -1);
      rules_groups.value.length -= 1;
      let min_approval_index = rules_min_approval.value.findIndex(ind => ind == index);
      rules_min_approval.value.splice(min_approval_index, -1);
      rules_min_approval.value.length -= 1;
    };

    onMounted(() => {
      getBranches();
      rules_operators.value.length += 1;
      rules_groups.value.length += 1;
      rules_min_approval.value.length += 1;
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
      authorization_groups,
      name,
      min_pi_amount,
      max_pi_amount,
      program_type_id,
      status,
      rules,
      rules_groups,
      rules_min_approval,
      rules_operators,
      main_rules_count,
      showEditModal,
      addNewBranch,
      updateBranch,
      editBranch,
      changePage,
      addCount,
      deleteCount
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
