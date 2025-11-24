<template>
  <div class="card p-1" id="companies">
    <div class="card-title px-2 my-1">
      <h5>{{ $t('Users') }}</h5>
    </div>
    <div class="d-flex justify-content-between px-2">
      <div class="d-flex flex-wrap gap-1">
        <div class="mr-2">
          <label for="Search" class="form-label">{{ $t('Search') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="User Name/User Email/Phone Number"
            aria-describedby="defaultFormControlHelp"
            v-model="search"
          />
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end mt-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('User Name') }}</th>
            <th>{{ $t('User Email') }}</th>
            <th>{{ $t('User Phone Number') }}</th>
            <th>{{ $t('Role') }}</th>
            <th v-if="is_anchor">{{ $t('VF Group') }}</th>
            <th v-if="is_anchor">{{ $t('DF Group') }}</th>
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
          <tr v-for="user in users.data" :key="user.id">
            <td>
              <div class="d-flex">
                <a
                  class="text-primary text-decoration-underline"
                  :href="'../' + company + '/users/' + user.id + '/edit'"
                >
                  {{ user.name }}
                </a>
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
              </div>
            </td>
            <td class="">{{ user.email }}</td>
            <td>
              <span>{{ user.phone_number }}</span>
            </td>
            <td>
              <div class="d-flex" v-if="user.roles.length > 0">
                <span v-for="role in user.roles" :key="role.id" class="btn btn-xs btn-label-warning mx-1">{{
                  role.name
                }}</span>
              </div>
              <span v-else>-</span>
            </td>
            <td v-if="is_anchor">
              <div v-if="user.authorization_groups.length > 0">
                <span v-for="group in user.authorization_groups" :key="group.id">
                  <span v-if="group.program_type.name == 'Vendor Financing'">{{ group.name }}</span>
                </span>
              </div>
              <div v-else>-</div>
            </td>
            <td v-if="is_anchor">
              <div v-if="user.authorization_groups.length > 0">
                <span v-for="group in user.authorization_groups" :key="group.id">
                  <span v-if="group.program_type.name == 'Dealer Financing'">{{ group.name }}</span>
                </span>
              </div>
              <div v-else>-</div>
            </td>
            <td>
              <div class="d-flex">
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
                  v-if="
                    !user.is_current_user &&
                    !user.has_pending_config &&
                    !user.changes &&
                    user.mapped_companies.length > 0 &&
                    user.mapped_companies[0].pivot.active == 1 &&
                    user.can_activate
                  "
                  title="Click to Suspend User"
                  style="cursor: pointer"
                  @click="updateActiveStatus(user.id, 'inactive')"
                ></i>
                <i
                  class="ti ti-thumb-down ti-sm text-danger my-auto mx-2"
                  v-if="
                    !user.is_current_user &&
                    !user.has_pending_config &&
                    !user.changes &&
                    user.mapped_companies.length > 0 &&
                    user.mapped_companies[0].pivot.active == 0 &&
                    user.can_activate
                  "
                  title="Click to Activate User"
                  style="cursor: pointer"
                  @click="updateActiveStatus(user.id, 'active')"
                ></i>
                <i
                  class="ti ti-circle-check ti-sm text-success my-auto mx-2"
                  v-if="!user.is_current_user && user.has_pending_config && user.can_activate"
                  title="Click to Approve User Status Change"
                  style="cursor: pointer"
                  @click="updateActiveStatus(user.id, 'active')"
                ></i>
                <i
                  class="ti ti-circle-x ti-sm text-danger my-auto mx-2"
                  v-if="!user.is_current_user && user.has_pending_config && user.can_activate"
                  title="Click to Reject User Status Change"
                  style="cursor: pointer"
                  @click="updateActiveStatus(user.id, 'inactive')"
                ></i>
              </div>
              <div
                class="modal fade"
                v-if="user.changes"
                :id="'approve-user-change-' + user.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <form method="post" @submit.prevent="approveChanges(user.id)">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Edit User') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                          :id="'approve-change-' + user.id"
                        ></button>
                      </div>
                      <div class="modal-body" v-if="user.changes">
                        <h5>{{ $t('Are you sure you want to approve the user changes') }}?</h5>
                        <div v-for="(change, key) in user.changes.changes" :key="change">
                          <div v-if="key == 'User'">
                            <span v-for="(user_change, user_key) in change" :key="user_change" class="d-flex flex-col"
                              ><div class="m_title">{{ user_key.replaceAll('_', ' ') }}</div>
                              : {{ user_change }}</span
                            >
                          </div>
                          <div v-if="key == 'Role'">
                            <span>{{ $t('Role') }} - {{ change }}</span>
                          </div>
                          <div v-if="key == 'Authorization Group'">
                            <span>{{ $t('Change Authorization Group to') }} - {{ change.name }}</span>
                          </div>
                          <div v-if="key == 'Dealer Authorization Group'">
                            <span>{{ $t('Change Dealer Authorization Group to') }} - {{ change.name }}</span>
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
                :id="'reject-user-change-' + user.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <form method="post" @submit.prevent="rejectChanges(user.id)">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Edit User') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                          :id="'reject-change-' + user.id"
                        ></button>
                      </div>
                      <div class="modal-body" v-if="user.changes">
                        <h5>{{ $t('Are you sure you want to approve the user changes') }}?</h5>
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
                          <div v-if="key == 'Authorization Group'">
                            <span>{{ $t('Change Authorization Group to') }} - {{ change.name }}</span>
                          </div>
                          <div v-if="key == 'Dealer Authorization Group'">
                            <span>{{ $t('Change Dealer Authorization Group to') }} - {{ change.name }}</span>
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
                        <div v-if="key == 'Authorization Group'">
                          <span>{{ $t('Change Authorization Group to') }} - {{ change.name }}</span>
                        </div>
                        <div v-if="key == 'Dealer Authorization Group'">
                          <span>{{ $t('Change Dealer Authorization Group to') }} - {{ change.name }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- <a :href="'../'+company+'/users/'+user.id+'/edit'" class="" title="Edit User Details" v-if="user.can_edit"><i class="fas fa-pencil text-warning"></i></a>
              <i class='ti ti-thumb-up ti-sm text-success my-auto mx-2' v-if="user.mapped_companies.length > 0 && user.mapped_companies[0].pivot.active == 1 && user.can_activate" title="Click to Suspend User" style="cursor: pointer;" @click="updateActiveStatus(user.id, 'inactive')"></i>
              <i class='ti ti-thumb-down ti-sm text-danger my-auto mx-2' v-if="user.mapped_companies.length > 0 && user.mapped_companies[0].pivot.active == 0 && user.can_activate" title="Click to Activate User" style="cursor: pointer;" @click="updateActiveStatus(user.id, 'active')"></i> -->
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      class="px-2"
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
import { computed, inject, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from './partials/Pagination.vue';
import axios from 'axios';
export default {
  name: 'CompanyUsers',
  props: ['bank', 'company', 'anchor'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const users = ref([]);
    const bank = ref('');
    const company = ref('');
    const is_anchor = props.anchor == 'true' ? ref(true) : ref(false);
    const per_page = ref(10);
    const search = ref('');

    const getUsers = async () => {
      await axios
        .get(base_url + props.bank + '/companies/' + props.company + '/users/data', {
          params: {
            per_page: per_page.value,
            search: search.value
          }
        })
        .then(response => {
          users.value = response.data.users;
        });
    };

    onMounted(() => {
      company.value = props.company;
      getUsers();
    });

    const changePage = async page => {
      await axios.get(page).then(response => {
        users.value = response.data.users;
      });
    };

    const updateActiveStatus = (user, status) => {
      axios
        .get(base_url + props.bank + '/companies/' + company.value + '/users/' + user + '/status/update/' + status)
        .then(() => {
          getUsers();
          toast.success('User status updated');
        })
        .catch(err => {
          console.log(err);
        });
    };

    const approveChanges = user => {
      axios
        .post(base_url + props.bank + '/configurations/users/' + user + '/changes/approve', {
          status: 'approved'
        })
        .then(response => {
          toast.success(response.data.message);
          document.getElementById('approve-change-' + user).click();
          getUsers();
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
          document.getElementById('reject-change-' + user).click();
          getUsers();
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/companies/' + props.company + '/users/data', {
          params: {
            per_page: per_page.value,
            search: search.value
          }
        })
        .then(response => {
          users.value = response.data.users;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      search.value = '';
      await axios
        .get(base_url + props.bank + '/companies/' + props.company + '/users/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          users.value = response.data.users;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/requests/cbs-transactions/data', {
          params: {
            per_page: per_page,
            search: search.value
          }
        })
        .then(response => {
          users.value = response.data.users;
        })
        .catch(err => {
          console.log(err);
        });
    });

    return {
      base_url,
      bank,
      company,
      is_anchor,
      users,
      per_page,
      changePage,
      updateActiveStatus,
      approveChanges,
      rejectChanges,
      search,
      filter,
      refresh
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
