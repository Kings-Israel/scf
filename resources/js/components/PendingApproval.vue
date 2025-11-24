<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Company Name" class="form-label">{{ $t('Company Name') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="name_search"
            id="defaultFormControlInput"
            :placeholder="$t('Company Name')"
            aria-describedby="defaultFormControlHelp"
          />
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
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Branch Code" class="form-label">{{ $t('Branch Code') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="branch_code"
            id="defaultFormControlInput"
            :placeholder="$t('Branch Code')"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
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
                  id="pending-approve-companies-close"
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
                  id="pending-reject-companies-close"
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
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <button type="button" class="btn btn-primary d-none" style="height: fit-content">
            <i class="ti ti-download ti-sm"></i>
          </button>
        </div>
        <div class="" v-if="can_create">
          <a :href="'companies/create'">
            <button type="button" class="btn btn-primary btn-md text-nowrap">
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
            <th>{{ $t('Company Name') }}</th>
            <th>{{ $t('Company Type') }}</th>
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
            <td class="text-primary text-decoration-underline">
              <a v-if="company.can_view" :href="'companies/' + company.id + '/details'">
                {{ company.name }}
              </a>
              <span v-else>{{ company.name }}</span>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Pending Company Changes awating approval"
                v-if="company.proposed_update_count > 0"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Company Creation/Updation Awaiting Approval."
                v-if="company.approval_status == 'pending'"
              ></i>
            </td>
            <td class="">{{ company.organization_type }}</td>
            <td>
              <span class="badge me-1 m_title" :class="resolveApprovalStatus(company.approval_status)">{{
                company.approval_status
              }}</span>
            </td>
            <td>
              <span class="badge me-1 m_title" :class="resolveStatus(company.status)">{{ company.status }}</span>
            </td>
            <td>
              <span v-if="company.pipeline">Bank/CRM Created</span>
              <span v-else>YoFinvoice</span>
            </td>
            <td>
              <span>{{ company.branch_code }}</span>
            </td>
            <td class="d-flex">
              <i
                class="ti ti-thumb-up ti-sm text-success"
                v-if="company.approval_status == 'approved' && company.status == 'active' && company.can_activate"
                title="Click to Deactivate Company"
                style="cursor: pointer"
                @click="updateActiveStatus(company.id, 'inactive')"
              ></i>
              <i
                class="ti ti-thumb-down ti-sm text-danger"
                v-if="company.approval_status == 'approved' && company.status == 'inactive' && company.can_activate"
                title="Click to Activate Company"
                style="cursor: pointer"
                @click="updateActiveStatus(company.id, 'active')"
              ></i>
              <i
                class="ti ti-circle-check ti-sm text-success mx-1"
                v-if="company.can_approve"
                style="cursor: pointer"
                @click="updateApprovalStatus(company.id, 'approved')"
                title="Approve Company"
              ></i>
              <i
                class="ti ti-square-x ti-sm text-danger mx-1"
                v-if="company.can_approve"
                style="cursor: pointer"
                data-bs-toggle="modal"
                :data-bs-target="'#update-status-' + company.id"
                title="Reject/Disapprove Company"
              ></i>
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
import { computed, onMounted, ref, watch, inject } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from './partials/Pagination.vue';
import axios from 'axios';
export default {
  name: 'Companies',
  props: ['bank', 'can_create'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    const can_create = props.can_create == '1' ? ref(true) : ref(false);
    // const base_url = process.env.NODE_ENV == 'development' ? '/' : '/bank/'
    const companies = ref([]);
    const rejection_reason = ref('');
    const bank = ref('');

    // Search fields
    const name_search = ref('');
    const status_search = ref('');
    const branch_code = ref('');
    const approval_status_search = ref('');

    // Pagination
    const per_page = ref(50);

    const updateRequestRejectionReason = ref('');

    const approveCompanies = ref([]);

    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);

    const selected_data = ref([]);

    const bulk_action = ref('');

    const getCompanies = async () => {
      await axios.get(base_url + props.bank + '/companies/pending/data?per_page=' + per_page.value).then(response => {
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

    watch([per_page], ([new_per_page]) => {
      axios
        .get(base_url + props.bank + '/companies/pending/data', {
          params: {
            per_page: new_per_page,
            name: name_search.value,
            approval_status: approval_status_search.value,
            branch_code: branch_code.value,
            status: status_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
        });
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/companies/pending/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            approval_status: approval_status_search.value,
            branch_code: branch_code.value,
            status: status_search.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      name_search.value = '';
      approval_status_search.value = '';
      (branch_code.value = ''), (status_search.value = '');
      axios
        .get(base_url + props.bank + '/companies/pending/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          companies.value = response.data.data.companies;
          parent.html('<i class="ti ti-refresh"></i>');
        });
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
          document.getElementById('pending-approve-companies-close').click();
          document.getElementById('pending-reject-companies-close').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateActiveStatus = (company, status) => {
      axios
        .get(`companies/${company}/activity/status/update/${status}`)
        .then(() => {
          getCompanies();
          toast.success('Company status updated');
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
          getCompanies();
          toast.success('Company status updated');
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getCompanies();
    });

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            name: name_search.value,
            approval_status: approval_status_search.value,
            branch_code: branch_code.value,
            status: status_search.value
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

      // Search fields
      name_search,
      status_search,
      branch_code,
      approval_status_search,

      // Pagination
      per_page,

      filter,
      refresh,

      updateRequestRejectionReason,
      showRejectionModal,
      showApprovalModal,

      selected_data,
      bulk_action,

      selectAll,
      updateSelected,
      submitBulkAction,

      resolveApprovalStatus,
      resolveStatus,
      updateActiveStatus,
      updateApprovalStatus,
      changePage
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
