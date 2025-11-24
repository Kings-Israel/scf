<template>
  <div class="card p-2" id="companies">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Company Name" class="form-label">{{ $t('Company Name') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="name_search"
            id="defaultFormControlInput"
            :placeholder="$t('Company Name')"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Company Type" class="form-label">{{ $t('Company Type') }}</label>
          <select
            class="form-select form-search select2"
            id="company-type-select"
            multiple
            v-model="company_type_search"
          >
            <option value="">{{ $t('Company Type') }}</option>
            <option value="anchor">{{ $t('Anchor') }}</option>
            <option value="vendor">{{ $t('Vendor') }}</option>
            <option value="dealer">{{ $t('Dealer') }}</option>
            <option value="buyer">{{ $t('Buyer') }}</option>
            <option value="unassigned">{{ $t('Unassigned') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Approval Status" class="form-label">{{ $t('Approval Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="approval_status_search">
            <option value="">{{ $t('Approval Status') }}</option>
            <option value="pending">{{ $t('Pending') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search select2" v-model="status_search" id="status-select" multiple>
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
            <option value="blocked">{{ $t('Blocked') }}</option>
            <option value="unblocked">{{ $t('Unblocked') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Branch Code" class="form-label">{{ $t('Branch Code') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="branch_code"
            id="defaultFormControlInput"
            placeholder="Branch Code"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <!-- <div class="mx-1">
          <label for="Company Name" class="form-label">{{ $t('Company Type') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            v-model="type_search"
            id="defaultFormControlInput"
            :placeholder="$t('Company Type')"
            aria-describedby="defaultFormControlHelp"
          />
        </div> -->
        <div class="">
          <label for="Bulk Actions" class="form-label">{{ $t('Bulk Actions') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="bulk_action">
            <option value="">{{ $t('Bulk Actions') }}</option>
            <option value="approve">{{ $t('Approve') }}</option>
            <option value="reject">{{ $t('Reject') }}</option>
          </select>
        </div>
        <button
          class="d-none"
          ref="showApprovalModal"
          data-bs-toggle="modal"
          :data-bs-target="'#bulk-approval-modal'"
        ></button>
        <div class="modal fade" id="bulk-approval-modal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Companies') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="approve-companies-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form method="post" @submit.prevent="submitBulkAction('approve')">
                <div class="modal-body">
                  <h5>{{ $t('Are you sure you want to approve the selected companies') }}?</h5>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <button
          class="d-none"
          ref="showRejectionModal"
          data-bs-toggle="modal"
          :data-bs-target="'#bulk-rejection-modal'"
        ></button>
        <div class="modal fade" id="bulk-rejection-modal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Selected Companies') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="reject-companies-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form method="post" @submit.prevent="submitBulkAction('reject')">
                <div class="modal-body">
                  <div class="row mb-1">
                    <div class="form-group">
                      <label for="">{{ $t('Rejection Reason') }}</label>
                      <textarea type="text" class="form-control" v-model="rejection_reason"></textarea>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                </div>
              </form>
            </div>
          </div>
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
          <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="" v-if="can_create">
          <a :href="'companies/create'">
            <button type="button" class="btn btn-primary text-nowrap btn-md">
              <i class="ti ti-plus ti-xs"></i>{{ $t('Add Company') }}
            </button>
          </a>
        </div>
      </div>
    </div>
    <pagination
      v-if="companies.meta"
      class="mx-2"
      :from="companies.meta.from"
      :to="companies.meta.to"
      :links="companies.meta.links"
      :next_page="companies.links.next"
      :prev_page="companies.links.prev"
      :total_items="companies.meta.total"
      :first_page_url="companies.links.first"
      :last_page_url="companies.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>
              <div class="form-check">
                <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
              </div>
            </th>
            <th>{{ $t('ID') }}</th>
            <th>{{ $t('Company Name') }}</th>
            <th>{{ $t('Company Type') }}</th>
            <th>{{ $t('Organization Type') }}</th>
            <th>{{ $t('Approval Status') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Source') }}</th>
            <th>{{ $t('Branch Code') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!companies.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="companies.data && companies.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="company in companies.data" :key="company.id">
            <td>
              <div class="form-check">
                <input
                  v-if="company.can_approve"
                  class="form-check-input border-primary"
                  type="checkbox"
                  :id="'data-select-' + company.id"
                  @change="updateSelected(company.id)"
                />
              </div>
            </td>
            <td class="">
              <span>
                {{ company.company_bank_id }}
              </span>
            </td>
            <td class="text-primary text-decoration-underline">
              <span>
                <span v-if="company.proposed_update || company.approval_status === 'pending' || company.user_changes">
                  <a v-if="company.can_view" :href="'companies/' + company.id + '/details'">
                    {{ company.name }}
                  </a>
                  <span v-else>{{ company.name }}</span>
                </span>
                <span v-else>
                  <a v-if="company.can_view" :href="'companies/' + company.id + '/details'">
                    {{ company.name }}
                  </a>
                  <span v-else>{{ company.name }}</span>
                </span>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-info"
                  title="Pending Company Changes awating approval"
                  v-if="company.proposed_update"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-info"
                  title="Pending Company User Changes awating approval"
                  v-if="company.user_changes"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-secondary"
                  title="Company Limit is almost depleted."
                  v-if="company.utilized > 90"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-warning"
                  title="Company Creation/Updation Awaiting Approval."
                  v-if="company.approval_status == 'pending'"
                ></i>
                <i
                  class="tf-icons ti ti-registered ti-xs text-danger"
                  :title="company.rejection_reason"
                  v-if="company.approval_status == 'rejected'"
                ></i>
              </span>
            </td>
            <td v-if="company.roles.length > 0">
              <div v-for="role in company.roles" :key="role.id">
                <span class="badge bg-label-primary me-1 m_title mx-1">{{ role.name }}</span>
              </div>
            </td>
            <td v-else>
              <span>-</span>
            </td>
            <td class="">
              <span>
                {{ company.organization_type }}
              </span>
            </td>
            <td>
              <span class="badge me-1 m_title" :class="resolveApprovalStatus(company.approval_status)">{{
                $t(company.approval_status)
              }}</span>
            </td>
            <td>
              <span class="badge me-1 m_title" :class="resolveStatus(company.status)">{{ $t(company.status) }}</span>
            </td>
            <td>
              <span v-if="company.pipeline">Bank/CRM Created</span>
              <span v-else>YoFinvoice</span>
            </td>
            <td>
              <span>{{ company.branch_code }}</span>
            </td>
            <td>
              <div class="d-flex">
                <div class="d-flex gap-1">
                  <a v-if="company.can_view" :href="'companies/' + company.id + '/details'">
                    <i class="ti ti-eye ti-sm text-primary" style="cursor: pointer"></i>
                  </a>
                  <a v-if="company.can_view" :href="'companies/' + company.id + '/details'">
                    <i
                      class="ti ti-clock ti-sm text-primary"
                      v-if="company.approval_status == 'pending'"
                      title="Company Creation/Updation Awaiting approval"
                      style="cursor: pointer; font-weight: bolder"
                    ></i>
                  </a>
                  <i
                    class="ti ti-clock ti-sm text-primary"
                    v-if="company.proposed_update"
                    title="Company has pending changes"
                    style="cursor: pointer; font-weight: bolder"
                    data-bs-toggle="modal"
                    :data-bs-target="'#changes-details-' + company.id"
                  ></i>
                  <i
                    class="ti ti-thumb-up ti-sm text-success"
                    v-if="
                      !company.proposed_update &&
                      company.approval_status == 'approved' &&
                      company.status == 'active' &&
                      company.can_activate
                    "
                    title="Click to Deactivate Company"
                    style="cursor: pointer"
                    data-bs-toggle="modal"
                    :data-bs-target="'#update-active-status-' + company.id"
                  ></i>
                  <i
                    class="ti ti-thumb-down ti-sm text-danger"
                    v-if="
                      !company.proposed_update &&
                      company.approval_status == 'approved' &&
                      company.status == 'inactive' &&
                      company.can_activate
                    "
                    title="Click to Activate Company"
                    style="cursor: pointer"
                    data-bs-toggle="modal"
                    :data-bs-target="'#update-inactive-status-' + company.id"
                  ></i>
                  <i
                    class="ti ti-ban ti-sm text-danger"
                    v-if="company.approval_status == 'rejected'"
                    :title="company.rejection_reason"
                  ></i>
                  <!-- Allow editing after rejection -->
                  <a
                    :href="'companies/' + company.id + '/edit'"
                    v-if="
                      !company.proposed_update &&
                      company.approval_status == 'rejected' &&
                      company.can_edit_after_rejection
                    "
                    class=""
                    ><i class="ti ti-pencil ti-sm text-warning" title="Edit"></i
                  ></a>
                  <a
                    :href="'companies/' + company.id + '/edit'"
                    v-if="!company.proposed_update && company.approval_status == 'approved'"
                    class=""
                    ><i class="ti ti-pencil ti-sm text-warning" title="Edit"></i
                  ></a>
                  <i
                    class="ti ti-circle-check ti-sm text-success"
                    v-if="company.can_approve"
                    style="cursor: pointer; font-weight: bolder"
                    @click="updateApprovalStatus(company.id, 'approved')"
                    title="Approve Company"
                  ></i>
                  <i
                    class="ti ti-square-x ti-sm text-danger"
                    v-if="company.can_approve"
                    style="cursor: pointer; font-weight: bolder"
                    data-bs-toggle="modal"
                    :data-bs-target="'#update-status-' + company.id"
                    title="Reject/Disapprove Company"
                  ></i>
                  <a
                    v-if="!company.proposed_update && can_manage_authorization_group"
                    :href="'companies/' + company.id + '/authorization-groups'"
                    class=""
                    ><i
                      class="ti ti-list-details ti-sm text-primary"
                      v-if="hasAnchorRole(company.roles)"
                      title="View Authorization Groups"
                    ></i
                  ></a>
                  <a
                    v-if="!company.proposed_update && can_manage_authorization_matrix"
                    :href="'companies/' + company.id + '/authorization-matrices'"
                    class=""
                    ><i
                      class="ti ti-list-check ti-sm text-primary"
                      v-if="hasAnchorRole(company.roles)"
                      title="View Authorization Matrix"
                    ></i
                  ></a>
                  <i
                    class="ti ti-hand-stop ti-sm text-success"
                    v-if="
                      company.approval_status == 'approved' &&
                      company.can_block &&
                      !company.proposed_update &&
                      !company.is_blocked
                    "
                    style="cursor: pointer"
                    data-bs-toggle="modal"
                    :data-bs-target="'#update-block-status-' + company.id"
                    title="Block Company from making finance requests"
                  ></i>
                  <i
                    class="ti ti-hand-off ti-sm text-danger"
                    v-if="
                      company.approval_status == 'approved' &&
                      company.can_block &&
                      !company.proposed_update &&
                      company.is_blocked
                    "
                    style="cursor: pointer"
                    data-bs-toggle="modal"
                    :data-bs-target="'#update-block-status-' + company.id"
                    title="Unblock Company from making finance requests"
                  ></i>
                </div>
                <div class="modal fade" :id="'update-status-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Company Status') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form @submit.prevent="updateApprovalStatus(company.id, 'rejected')" method="post">
                        <div class="modal-body">
                          <div class="row mb-1">
                            <div class="form-group">
                              <label for="">{{ $t('Enter Rejection Reason') }}</label>
                              <textarea type="text" class="form-control" v-model="rejection_reason"></textarea>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'update-block-status-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Company Status') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form @submit.prevent="updateBlockStatus(company)" method="post">
                        <div class="modal-body">
                          <h4 class="text-wrap" v-if="!company.is_blocked">
                            {{ $t('Are you sure you want to block the company') }}, {{ company.name }}
                            {{ $t('from making finance requests') }}?
                          </h4>
                          <h4 class="text-wrap" v-else>
                            {{ $t('Are you sure you want to unblock the company') }}, {{ company.name }}?
                          </h4>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'update-active-status-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Company Active Status') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'update-active-status-close' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <h5 class="text-wrap">
                            {{ $t('Are you sure you want to update the status of') }} {{ company.name }}
                            {{ $t('to inactive') }}?
                          </h5>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button
                          class="btn btn-primary"
                          type="button"
                          @click="updateActiveStatus(company.id, 'inactive')"
                        >
                          {{ $t('Confirm') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'update-inactive-status-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Company Active Status') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'update-inactive-status-close' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <h5 class="text-wrap">
                            {{ $t('Are you sure you want to update the status of') }} {{ company.name }}
                            {{ $t('to active') }}?
                          </h5>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="button" @click="updateActiveStatus(company.id, 'active')">
                          {{ $t('Confirm') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div
                  v-if="company.update"
                  class="modal modal-top fade modal-xl"
                  :id="'changes-details-' + company.id"
                  tabindex="-1"
                >
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5>{{ $t('Company Changes Details') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          :id="'changes-details-close-' + company.id"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-6">
                            <div
                              class="d-flex flex-column"
                              v-for="(section, update) in company.update.changes"
                              :key="section"
                            >
                              <template v-if="update != 'Relationship Manager'">
                                <h5>
                                  {{ $t('Company Details') }}
                                </h5>
                                <div class="d-flex flex-column" v-for="(setting, column) in section" :key="column">
                                  <span class="m_title" v-if="column == 'gst_number'">KRA PIN: {{ setting }}</span>
                                  <span
                                    class="m_title"
                                    v-if="setting != '' && setting != null && column != 'gst_number'"
                                    >{{ column.replaceAll('_', ' ') }}: {{ setting }}</span
                                  >
                                </div>
                              </template>
                              <template v-else>
                                <h5 class="mt-2">
                                  {{ $t('Relationship Managers') }}
                                </h5>
                                <div class="d-flex flex-column" v-for="(user, change_id) in section" :key="change_id">
                                  <div
                                    class="d-flex flex-column"
                                    v-for="(user_details, user_key) in user"
                                    :key="user_details"
                                  >
                                    <span class="m_title" v-if="user_details != '' && user_details != null"
                                      >{{ user_key.replaceAll('_', ' ') }}: {{ user_details }}</span
                                    >
                                  </div>
                                  <hr />
                                </div>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer" v-if="company.update && company.update.can_approve">
                        <button class="btn btn-sm btn-danger" @click="approveChange(company.id, 'reject')">
                          {{ $t('Reject') }}
                        </button>
                        <button class="btn btn-sm btn-primary" @click="approveChange(company.id, 'approve')">
                          {{ $t('Approve') }}
                        </button>
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
      v-if="companies.meta"
      class="mx-2"
      :from="companies.meta.from"
      :to="companies.meta.to"
      :links="companies.meta.links"
      :next_page="companies.links.next"
      :prev_page="companies.links.prev"
      :total_items="companies.meta.total"
      :first_page_url="companies.links.first"
      :last_page_url="companies.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { onMounted, ref, watch, inject } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from './partials/Pagination.vue';
import axios from 'axios';
export default {
  name: 'Companies',
  props: ['bank', 'can_create', 'can_manage_authorization_group', 'can_manage_authorization_matrix'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const can_create = props.can_create == '1' ? ref(true) : ref(false);
    const can_manage_authorization_group = props.can_manage_authorization_group == '1' ? ref(true) : ref(false);
    const can_manage_authorization_matrix = props.can_manage_authorization_matrix == '1' ? ref(true) : ref(false);
    const toast = useToast();
    // const base_url = process.env.NODE_ENV == 'development' ? '/' : '/bank/'
    const companies = ref([]);
    const rejection_reason = ref('');
    const bank = ref('');

    // Search fields
    const name_search = ref('');
    const status_search = ref([]);
    const branch_code = ref('');
    const approval_status_search = ref('');
    const type_search = ref('');
    const company_type_search = ref([]);

    // Pagination
    const per_page = ref(50);

    const updateRequestRejectionReason = ref('');

    const approveCompanies = ref([]);

    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);

    const selected_data = ref([]);

    const bulk_action = ref('');

    const getCompanies = async () => {
      await axios.get(base_url + props.bank + '/companies/data?per_page=' + per_page.value).then(response => {
        companies.value = response.data.data.companies;
      });
    };

    const resolveApprovalStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'approved':
          style = 'bg-label-success';
          break;
        case 'rejected':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'active':
          style = 'bg-label-primary';
          break;
        case 'inactive':
          style = 'bg-label-secondary';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    watch(bulk_action, newVal => {
      if (selected_data.value.length > 0) {
        approveCompanies.value = [];
        if (newVal == 'approve') {
          selected_data.value.forEach(selected => {
            approveCompanies.value.push(companies.value.data.filter(request => request.id == selected)[0]);
          });
          showApprovalModal.value.click();
        }
        if (newVal == 'reject') {
          showRejectionModal.value.click();
        }
      } else {
        toast.error('Select Programs');
      }
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/companies/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            approval_status: approval_status_search.value,
            branch_code: branch_code.value,
            status: status_search.value,
            type: type_search.value,
            program_role_type: company_type_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      name_search.value = '';
      approval_status_search.value = '';
      branch_code.value = '';
      status_search.value = [];
      type_search.value = '';
      company_type_search.value = [];
      await axios
        .get(base_url + props.bank + '/companies/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const selectAll = () => {
      selected_data.value = [];
      if (!document.getElementById('select-all').checked) {
        companies.value.data.forEach(data => {
          if (data.can_approve) {
            document.getElementById('data-select-' + data.id).checked = false;
            let f;
            let index = selected_data.value.filter(function (id, index) {
              f = index;
              id == data.id;
            });
            if (!index) {
              return false;
            }
            selected_data.value.splice(f, 1);
          }
        });
      } else {
        companies.value.data.forEach(data => {
          if (data.can_approve) {
            document.getElementById('data-select-' + data.id).checked = true;
            selected_data.value.push(data.id);
          }
        });
      }
    };

    const updateSelected = id => {
      if (selected_data.value.includes(id)) {
        const index = selected_data.value.indexOf(id);
        selected_data.value.splice(index, 1);
      } else {
        selected_data.value.push(id);
      }
    };

    const submitBulkAction = action => {
      if (selected_data.value.length <= 0) {
        toast.error('Select companies');
        return;
      }

      const formData = new FormData();
      selected_data.value.forEach(request => {
        formData.append('companies[]', request);
      });
      if (action == 'approve') {
        formData.append('status', 'approved');
      }
      if (action == 'reject') {
        formData.append('status', 'rejected');
        formData.append('rejection_reason', rejection_reason.value);
      }

      axios
        .post(base_url + props.bank + '/companies/bulk/approval-status/status/update', formData)
        .then(() => {
          toast.success('Companies updated');
          bulk_action.value = '';
          rejection_reason.value = '';
          getCompanies();
          document.getElementById('approve-companies-close').click();
          document.getElementById('reject-companies-close').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateActiveStatus = (company, status) => {
      axios
        .get(`companies/${company}/activity/status/update/${status}`)
        .then(response => {
          getCompanies();
          toast.success(response.data.message);
          document.getElementById('update-inactive-status-close' + company).click();
          document.getElementById('update-active-status-close' + company).click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateApprovalStatus = (company, status) => {
      axios
        .post(`companies/${company}/status/update`, {
          status: status,
          rejection_reason: rejection_reason.value
        })
        .then(() => {
          toast.success('Company status updated');
          getCompanies();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateBlockStatus = company => {
      axios.get('companies/' + company.id + '/block-status/update').then(() => {
        if (company.is_blocked) {
          toast.success('Company has been unblocked');
        } else {
          toast.success('Company has been blocked');
        }
        setTimeout(() => {
          window.location.reload();
        }, 3000);
      });
    };

    const approveChange = (company, status) => {
      axios.get('companies/' + company + '/updates/' + status + '/approve').then(() => {
        if (status == 'approve') {
          toast.success('Company Changed have been applied');
        } else {
          toast.success('Company changed have been discarded');
        }
        document.getElementById('changes-details-close-' + company).click();
        getCompanies();
      });
    };

    const hasAnchorRole = roles => {
      let company_roles = [];
      roles.forEach(role => {
        company_roles.push(role.name);
      });

      if (company_roles.length > 0 && (company_roles.includes('anchor') || company_roles.includes('buyer'))) {
        return true;
      }
      return false;
    };

    onMounted(() => {
      getCompanies();
      $('#company-type-select').on('change', function () {
        var ids = $('#company-type-select').val();
        company_type_search.value = ids;
      });
      $('#status-select').on('change', function () {
        var ids = $('#status-select').val();
        status_search.value = ids;
      });
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/companies/data', {
          params: {
            per_page: per_page,
            name: name_search.value,
            approval_status: approval_status_search.value,
            branch_code: branch_code.value,
            status: status_search.value,
            program_role_type: company_type_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            name: name_search.value,
            approval_status: approval_status_search.value,
            branch_code: branch_code.value,
            status: status_search.value,
            program_role_type: company_type_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
        });
    };

    return {
      base_url,
      bank,
      companies,
      rejection_reason,
      can_create,
      can_manage_authorization_group,
      can_manage_authorization_matrix,

      // Search fields
      name_search,
      status_search,
      branch_code,
      approval_status_search,
      type_search,
      company_type_search,

      // Pagination
      per_page,

      updateRequestRejectionReason,
      showRejectionModal,
      showApprovalModal,

      selected_data,
      bulk_action,

      filter,
      refresh,

      selectAll,
      updateSelected,
      submitBulkAction,
      updateBlockStatus,
      resolveApprovalStatus,
      resolveStatus,
      updateActiveStatus,
      updateApprovalStatus,
      changePage,
      approveChange,
      hasAnchorRole
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
