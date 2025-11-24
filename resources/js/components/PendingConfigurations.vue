<template>
  <div class="card p-2" id="companies">
    <div class="d-flex justify-content-between">
      <div class="w-75 row">
        <div class="col-3">
          <select class="form-select" id="exampleFormControlSelect" v-model="bulk_action">
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
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Configurations') }}</h5>
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
                  <h5>{{ $t('Are you sure you want to approve the selected configurations?') }}</h5>
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
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Reject Selected Configurations') }}</h5>
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
                  <h5>{{ $t('Are you sure you want to reject the selected configurations?') }}</h5>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-end">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
    </div>
    <pagination
      :from="proposed_configs.from"
      :to="proposed_configs.to"
      :links="proposed_configs.links"
      :next_page="proposed_configs.next_page_url"
      :prev_page="proposed_configs.prev_page_url"
      :total_items="proposed_configs.total"
      :first_page_url="proposed_configs.first_page_url"
      :last_page_url="proposed_configs.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th class="">
              <div class="form-check">
                <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
              </div>
            </th>
            <th>{{ $t('Particulars') }}</th>
            <th>{{ $t('Date Created') }}</th>
            <th>{{ $t('Proposed By') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!proposed_configs.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="proposed_configs.data && proposed_configs.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="config in proposed_configs.data" :key="config.id">
            <td>
              <div class="form-check">
                <input
                  v-if="config.can_approve"
                  class="form-check-input border-primary"
                  type="checkbox"
                  :id="'data-select-' + config.id"
                  @change="updateSelected(config.id)"
                />
              </div>
            </td>
            <td>
              <div class="">
                <p v-if="config.field != 'logo' && config.field != 'favicon'">{{ config.description }}</p>
                <p v-else v-html="config.description"></p>
              </div>
            </td>
            <td>
              <div class="">
                {{ moment(config.created_at).format(date_format) }}
              </div>
            </td>
            <td>
              <div class="">
                {{ config.user.name }}
              </div>
            </td>
            <td>
              <div class="d-flex">
                <span v-if="!config.can_approve" class="badge bg-label-warning p-2">{{
                  $t('Awaiting Checker Approval')
                }}</span>
                <a
                  href="#"
                  v-if="config.can_approve"
                  data-bs-toggle="modal"
                  :data-bs-target="'#approve-config-' + config.id"
                >
                  <i class="ti ti-circle-check ti-sm text-primary" title="Approve"></i>
                </a>
                <a
                  href="#"
                  v-if="config.can_approve"
                  data-bs-toggle="modal"
                  :data-bs-target="'#reject-config-' + config.id"
                >
                  <i class="ti ti-circle-dot ti-sm mx-1 text-danger" title="Reject"></i>
                </a>
                <div class="modal fade" :id="'approve-config-' + config.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Configuration Change') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          :id="'approve-config-close-btn-' + config.id"
                          aria-label="Close"
                        ></button>
                      </div>
                      <form @submit.prevent="updateConfig(config.id, 'approve')" method="post">
                        <div class="modal-body">
                          <h5>{{ $t('Are you sure you want to approve the configuration change?') }}</h5>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary" type="submit">{{ $t('Approve') }}</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'reject-config-' + config.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Reject Configuration Change') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          :id="'reject-config-close-btn-' + config.id"
                          aria-label="Close"
                        ></button>
                      </div>
                      <form @submit.prevent="updateConfig(config.id, 'reject')" method="post">
                        <div class="modal-body">
                          <h5>{{ $t('Are you sure you want to reject the configuration change?') }}</h5>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary" type="submit">{{ $t('Reject') }}</button>
                        </div>
                      </form>
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
      :from="proposed_configs.from"
      :to="proposed_configs.to"
      :links="proposed_configs.links"
      :next_page="proposed_configs.next_page_url"
      :prev_page="proposed_configs.prev_page_url"
      :total_items="proposed_configs.total"
      :first_page_url="proposed_configs.first_page_url"
      :last_page_url="proposed_configs.last_page_url"
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
import moment from 'moment/moment';
export default {
  name: 'PendingConfigurations',
  props: ['bank', 'date_format'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const date_format = props.date_format
    const base_url = inject('baseURL');
    const toast = useToast();
    const proposed_configs = ref([]);
    const bank = ref('');

    // Pagination
    const per_page = ref(50);

    const updateRequestRejectionReason = ref('');

    const approveConfigs = ref([]);

    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);

    const selected_data = ref([]);

    const bulk_action = ref('');

    const getConfigs = () => {
      axios
        .get(base_url + props.bank + '/configurations/pending/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          proposed_configs.value = response.data.data;
        });
    };

    watch(bulk_action, newVal => {
      if (selected_data.value.length > 0) {
        approveConfigs.value = [];
        if (newVal == 'approve') {
          selected_data.value.forEach(selected => {
            approveConfigs.value.push(proposed_configs.value.data.filter(request => request.id == selected)[0]);
          });
          showApprovalModal.value.click();
        }
        if (newVal == 'reject') {
          showRejectionModal.value.click();
        }
      } else {
        toast.error('Select Configurations');
      }
    });

    const selectAll = () => {
      selected_data.value = [];
      if (!document.getElementById('select-all').checked) {
        proposed_configs.value.data.forEach(data => {
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
        proposed_configs.value.data.forEach(data => {
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
        toast.error('Select configurations');
        return;
      }

      const formData = new FormData();
      selected_data.value.forEach(request => {
        formData.append('configurations[]', request);
      });
      if (action == 'approve') {
        formData.append('status', 'approve');
      }
      if (action == 'reject') {
        formData.append('status', 'reject');
      }

      axios
        .post(base_url + props.bank + '/configurations/pending/bulk/update', formData)
        .then(() => {
          toast.success('Configurations updated');
          bulk_action.value = '';
          getConfigs();
          document.getElementById('approve-companies-close').click();
          document.getElementById('reject-companies-close').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('Something went wrong');
        });
    };

    const updateConfig = (config, status) => {
      axios
        .get(base_url + props.bank + '/configurations/config/' + config + '/status/update/' + status)
        .then(() => {
          getConfigs();
          toast.success('Configuration status updated');
          document.getElementById('approve-config-close-btn-' + config).click();
          document.getElementById('reject-config-close-btn-' + config).click();
          document.getElementById('select-all').checked = false;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getConfigs();
    });

    const changePage = async page => {
      await axios.get(page + '&per_page=' + per_page.value).then(response => {
        proposed_configs.value = response.data.data;
      });
    };

    return {
      moment,
      base_url,
      bank,
      proposed_configs,

      // Pagination
      per_page,

      updateRequestRejectionReason,
      showRejectionModal,
      showApprovalModal,

      selected_data,
      bulk_action,

      selectAll,
      updateSelected,
      submitBulkAction,

      updateConfig,
      changePage,
      date_format,
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
