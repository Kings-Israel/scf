<template>
  <div class="p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label class="form-label">{{ $t('User Name/Email/Phone Number') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="search"
            placeholder="User Name/User Email/Phone Number"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Roles') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            v-model="role_search"
            placeholder="User Roles"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" v-model="status">
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
          </select>
        </div>
        <div class="mt-auto">
          <button class="btn btn-primary" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="mt-auto">
          <button class="btn btn-primary" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end mt-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
    <pagination
      :from="users.from"
      :to="users.to"
      :links="users.links"
      :next_page="users.next_page_url"
      :prev_page="users.prev_page_url"
      :total_items="users.total"
      :first_page_url="users.first_page_url"
      :last_page_url="users.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('User Name') }}</th>
            <th>{{ $t('Role') }}</th>
            <th>{{ $t('Email') }}</th>
            <th>{{ $t('Phone Number') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!users.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="users.data && users.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr class="text-nowrap" v-for="user in users.data" :key="user.id">
            <td class="">
              {{ user.name }}
              <i
                class="ti ti-info-circle ti-xs text-danger my-auto mx-2"
                v-if="user.has_pending_config"
                title="User Creation/Updation awaiting approval"
              ></i>
              <i
                class="ti ti-info-circle ti-xs text-danger my-auto mx-2"
                v-if="user.changes"
                title="User Creation/Updation awaiting approval"
              ></i>
            </td>
            <td>
              {{ user.roles.length > 0 ? user.roles[0].name : '' }}
            </td>
            <td class="">{{ user.email }}</td>
            <td>
              <span>{{ user.phone_number }}</span>
            </td>
            <td class="d-flex">
              <i
                class="ti ti-clock ti-sm text-primary my-auto mx-2"
                v-if="user.has_pending_config"
                title="User Creation/Updation awaiting approval"
              ></i>
              <i
                class="ti ti-clock ti-sm text-primary my-auto mx-2"
                style="cursor: pointer"
                data-bs-toggle="modal"
                :data-bs-target="'#user-change-' + user.id"
                v-if="user.changes"
                title="User Creation/Updation awaiting approval"
              ></i>
              <a
                :href="'users/' + user.id + '/edit'"
                class=""
                title="Edit User Details"
                v-if="user.can_edit && !user.has_pending_config && !user.changes"
                ><i class="ti ti-pencil text-warning"></i
              ></a>
              <a
                href="#"
                class=""
                title="Approve User Details Change"
                v-if="user.can_approve_changes && user.changes"
                data-bs-toggle="modal"
                :data-bs-target="'#approve-user-change-' + user.id"
                ><i class="ti ti-check text-success"></i
              ></a>
              <a
                href="#"
                class=""
                title="Reject User Details Change"
                v-if="user.can_approve_changes && user.changes"
                data-bs-toggle="modal"
                :data-bs-target="'#reject-user-change-' + user.id"
                ><i class="ti ti-circle-x text-danger mx-1"></i
              ></a>
              <i
                class="ti ti-thumb-up ti-sm text-success my-auto mx-2"
                v-if="!user.is_current_user && !user.has_pending_config && user.banks[0].pivot.active == 1"
                title="Click to Suspend User"
                style="cursor: pointer"
                @click="updateActiveStatus(user.id, 'inactive')"
              ></i>
              <i
                class="ti ti-thumb-down ti-sm text-danger my-auto mx-2"
                v-if="!user.is_current_user && !user.has_pending_config && user.banks[0].pivot.active == 0"
                title="Click to Activate User"
                style="cursor: pointer"
                @click="updateActiveStatus(user.id, 'active')"
              ></i>
              <div class="modal fade" :id="'update-user-' + user.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <form method="post" @submit.prevent="updateUser(user.id)">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Edit User') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="row g-3 p-3">
                        <div class="col-sm-6">
                          <label class="form-label" for="name">{{ $t('Name') }}</label>
                          <input type="text" id="name" name="name" class="form-control" v-model="name" />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label" for="email">{{ $t('Email') }}</label>
                          <input type="email" id="email" name="email" class="form-control" v-model="email" />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label" for="phone-number">{{ $t('Phone Number') }}</label>
                          <input
                            type="tel"
                            id="phone-number"
                            class="form-control"
                            name="phone_number"
                            placeholder="Enter Phone Number"
                            v-model="phone_number"
                            maxlength="12"
                          />
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label" for="role">{{ $t('Role') }}</label>
                          <select class="form-select" v-model="user_role">
                            <option value="">{{ $t("Select User's Role") }}</option>
                            <option
                              v-for="role in roles"
                              :key="role.id"
                              :value="role.id"
                              :selected="role.RoleName == selected_role"
                            >
                              {{ role.RoleName }}
                            </option>
                          </select>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <div class="modal fade" :id="'approve-user-change-' + user.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <form method="post" @submit.prevent="approveChanges(user.id)">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Edit User') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body" v-if="user.changes">
                        <h5>{{ $t('Are you sure you want to approve the user changes') }}?</h5>
                        <div v-for="(change, key) in user.changes.changes" :key="change">
                          <div v-if="key == 'User'">
                            <span v-for="(user_change, user_key) in change" :key="user_change" class="d-flex flex-col"
                              ><span class="m_title">{{ user_key.replaceAll('_', ' ') }}</span
                              >: {{ user_change }}</span
                            >
                          </div>
                          <div v-if="key == 'Role'">
                            <span>{{ $t('Role') }} - {{ change }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ $t('Confirm') }}</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <div class="modal fade" :id="'reject-user-change-' + user.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <form method="post" @submit.prevent="rejectChanges(user.id)">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Edit User') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body" v-if="user.changes">
                        <h5>{{ $t('Are you sure you want to approve the user changes') }}?</h5>
                        <div v-for="(change, key) in user.changes.changes" :key="change">
                          <div v-if="key == 'User'">
                            <span v-for="(user_change, user_key) in change" :key="user_change"
                              ><span class="m_title">{{ user_key }}</span
                              >: {{ user_change }}</span
                            >
                          </div>
                          <div v-if="key == 'Role'">
                            <span>{{ $t('Role') }} - {{ change }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ $t('Confirm') }}</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="user.changes"
                :id="'user-change-' + user.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('User Changes') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" v-if="user.changes">
                      <div v-for="(change, key) in user.changes.changes" :key="change">
                        <div v-if="key == 'User'">
                          <span class="d-flex" v-for="(user_change, user_key) in change" :key="user_change"
                            ><div class="m_title">{{ user_key.replaceAll('_', ' ') }}</div>
                            : {{ user_change }}</span
                          >
                        </div>
                        <div v-if="key == 'Role'">
                          <span>{{ $t('Role') }} - {{ change }}</span>
                        </div>
                      </div>
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
      :from="users.from"
      :to="users.to"
      :links="users.links"
      :next_page="users.next_page_url"
      :prev_page="users.prev_page_url"
      :total_items="users.total"
      :first_page_url="users.first_page_url"
      :last_page_url="users.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { computed, inject, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from '../partials/Pagination.vue';
import axios from 'axios';
export default {
  name: 'CompanyUsers',
  props: ['bank'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const users = ref([]);
    const roles = ref([]);
    const bank = ref('');
    const name = ref('');
    const email = ref('');
    const phone_number = ref('');
    const user_role = ref('');
    const selected_role = ref('');
    const receive_notifications = ref('');
    const notification_channels = ref([]);
    const reporting_manager = ref('');
    const record_visibility = ref('');
    const record_visibilities = ref([]);
    const location_type = ref('');
    const location_types = ref([]);
    const location = ref('');
    const locations = ref([]);
    const applicable_product = ref('');
    const applicable_products = ref([]);

    const search = ref('');
    const role_search = ref('');
    const status = ref('');

    const per_page = ref(50);

    const getUsers = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/users/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          users.value = response.data.users;
          roles.value = response.data.roles;
        });
    };

    onMounted(() => {
      getUsers();
    });

    const updateUser = user => {
      axios
        .post(base_url + props.bank + '/configurations/users/' + user + '/update', {
          name: name.value,
          email: email.value,
          phone_number: phone_number.value,
          role: user_role.value
        })
        .then(response => {
          toast.success(response.data.message);
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const approveChanges = user => {
      axios
        .post(base_url + props.bank + '/configurations/users/' + user + '/changes/approve', {
          status: 'approved'
        })
        .then(response => {
          toast.success(response.data.message);
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const rejectChanges = user => {
      axios
        .post(base_url + props.bank + '/configurations/users/' + user + '/changes/approve', {
          status: 'rejected'
        })
        .then(response => {
          toast.success(response.data.message);
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/users/data', {
          params: {
            per_page: per_page.value,
            search: search.value,
            role: role_search.value,
            status: status.value
          }
        })
        .then(response => {
          users.value = response.data.users;
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      search.value = '';
      role_search.value = '';
      status.value = '';
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/users/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          users.value = response.data.users;
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
          users.value = response.data.users;
        });
    };

    const updateActiveStatus = (user, status) => {
      axios
        .get(base_url + props.bank + '/configurations/users/' + user + '/status/update/' + status)
        .then(response => {
          getUsers();
          toast.success(response.data.message);
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      base_url,
      per_page,
      bank,
      users,
      roles,
      name,
      email,
      phone_number,
      user_role,
      selected_role,
      receive_notifications,
      notification_channels,
      reporting_manager,
      record_visibility,
      record_visibilities,
      location_type,
      location_types,
      location,
      locations,
      applicable_product,
      applicable_products,
      search,
      role_search,
      status,
      filter,
      refresh,
      approveChanges,
      rejectChanges,
      updateUser,
      changePage,
      updateActiveStatus
    };
  }
};
</script>

<style scoped>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
