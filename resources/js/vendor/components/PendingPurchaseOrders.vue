<template>
  <div class="d-flex flex-column flex-md-row justify-content-between px-2 py-2">
    <div class="d-flex flex-wrap gap-1">
      <div class="">
        <label for="Buyer" class="form-label">{{ $t('Anchor') }}</label>
        <input
          v-on:keyup.enter="filter"
          type="text"
          class="form-control mb-1 form-search"
          id="defaultFormControlInput"
          placeholder="Anchor"
          v-model="anchor_search"
          aria-describedby="defaultFormControlHelp"
        />
      </div>
      <div class="">
        <label for="PO No" class="form-label">{{ $t('PO No') }}</label>
        <input
          v-on:keyup.enter="filter"
          type="text"
          class="form-control mb-1 form-search"
          id="defaultFormControlInput"
          placeholder="P.O No"
          v-model="po_search"
          aria-describedby="defaultFormControlHelp"
        />
      </div>
      <div class="">
        <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Delivery Date') }})</label>
        <input
          type="text"
          id="pending_date_search"
          class="form-control form-search"
          name="pending-daterange"
          placeholder="Select Dates"
        />
      </div>
      <div class="">
        <label for="Status" class="form-label">{{ $t('Status') }}</label>
        <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
          <option value="">{{ $t('Status') }}</option>
          <option value="pending">{{ $t('Pending') }}</option>
          <option value="accepted">{{ $t('Accepted') }}</option>
          <option value="invoiced">{{ $t('Invoiced') }}</option>
          <option value="rejected">{{ $t('Rejected') }}</option>
        </select>
      </div>
      <div class="">
        <label for="Bulk Actions" class="form-label">{{ $t('Bulk Actions') }}</label>
        <select class="form-select form-search" id="exampleFormControlSelect" v-model="bulk_action">
          <option value="">{{ $t('Bulk Actions') }}</option>
          <option value="approve">{{ $t('Accept') }}</option>
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
              <h5 class="modal-title" id="modalCenterTitle">{{ $t('Accept Purchase Orders') }}</h5>
              <button
                type="button"
                class="btn-close"
                id="approve-data-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <form method="post" @submit.prevent="submitBulkAction('approve')">
              <div class="modal-body">
                <h5>{{ $t('Are you sure you want to accept the selected purchase orders') }}?</h5>
              </div>
              <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="mx-1 table-search-btn">
        <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
      </div>
      <div class="mx-1 table-clear-btn">
        <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
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
              <h5 class="modal-title" id="modalCenterTitle">{{ $t('Reject Selected Purchase Orders') }}</h5>
              <button
                type="button"
                class="btn-close"
                id="reject-data-close"
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
    </div>
    <div class="d-flex justify-content-md-end mt-2 mt-md-auto">
      <div class="">
        <select class="form-select" v-model="per_page">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
  </div>
  <pagination
    :from="purchase_orders.from"
    :to="purchase_orders.to"
    :links="purchase_orders.links"
    :next_page="purchase_orders.next_page_url"
    :prev_page="purchase_orders.prev_page_url"
    :total_items="purchase_orders.total"
    :first_page_url="purchase_orders.first_page_url"
    :last_page_url="purchase_orders.last_page_url"
    @change-page="changePage"
  ></pagination>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>
            <div class="form-check">
              <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
            </div>
          </th>
          <th>{{ $t('Anchor') }}</th>
          <th>{{ $t('P.O No') }}.</th>
          <th>{{ $t('Amount') }}</th>
          <th>{{ $t('No. of Items') }}</th>
          <th>{{ $t('Delivery From') }}</th>
          <th>{{ $t('Delivery To') }}</th>
          <th>{{ $t('Status') }}</th>
          <th>{{ $t('Actions') }}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!purchase_orders.data">
          <td colspan="12" class="text-center">
            <span class="text-center">{{ $t('Loading Data') }}...</span>
          </td>
        </tr>
        <tr v-if="purchase_orders.data && purchase_orders.data.length <= 0">
          <td colspan="12" class="text-center">
            <span class="text-center">{{ $t('No Data Available') }}...</span>
          </td>
        </tr>
        <tr
          v-show="purchase_orders.data.length > 0"
          v-for="purchase_order in purchase_orders.data"
          :key="purchase_order.id"
        >
          <td>
            <div class="form-check">
              <input
                v-if="
                  purchase_order.status == 'pending_acceptance' &&
                  purchase_order.user_can_approve &&
                  !purchase_order.user_has_approved
                "
                class="form-check-input border-primary"
                type="checkbox"
                :id="'data-select-' + purchase_order.id"
                @change="updateSelected(purchase_order.id)"
              />
            </div>
          </td>
          <td>{{ purchase_order.anchor.name }}</td>
          <td
            class="text-primary text-decoration-underline"
            data-bs-toggle="modal"
            :data-bs-target="'#purchase-order-' + purchase_order.id"
            style="cursor: pointer"
          >
            {{ purchase_order.purchase_order_number }}
          </td>
          <td class="text-success text-nowrap">{{ new Intl.NumberFormat().format(purchase_order.total_amount) }}</td>
          <td class="text-success text-nowrap">{{ purchase_order.purchase_order_items.length }}</td>
          <td class="">{{ moment(purchase_order.delivery_from).format('DD MMM YYYY') }}</td>
          <td class="">{{ moment(purchase_order.delivery_to).format('DD MMM YYYY') }}</td>
          <td>
            <span class="badge m_title mt-1" :class="resolveStatus(purchase_order.approval_stage)">{{
              purchase_order.approval_stage
            }}</span>
          </td>
          <td class="">
            <div class="d-inline-block">
              <a href="javascript(0)" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="text-primary ti ti-dots-vertical"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end m-0">
                <li
                  v-if="
                    purchase_order.status == 'pending_acceptance' &&
                    purchase_order.user_can_approve &&
                    !purchase_order.user_has_approved
                  "
                  style="cursor: pointer"
                  @click="submitApproval(purchase_order.id, 'accepted')"
                >
                  <span class="dropdown-item badge bg-label-success">{{ $t('Accept') }}</span>
                </li>
                <li
                  v-if="
                    purchase_order.status == 'pending_acceptance' &&
                    purchase_order.user_can_approve &&
                    !purchase_order.user_has_approved
                  "
                >
                  <a
                    href="javascript;"
                    data-bs-toggle="modal"
                    :data-bs-target="'#reject-purchase-order-' + purchase_order.id"
                    class="dropdown-item badge bg-label-danger"
                    >{{ $t('Reject') }}</a
                  >
                </li>
                <li
                  class=""
                  v-if="
                    purchase_order.can_flip &&
                    purchase_order.status == 'accepted' &&
                    purchase_order.approval_stage != 'Invoiced'
                  "
                >
                  <a
                    class="dropdown-item badge bg-primary"
                    :href="'purchase-orders/' + purchase_order.id + '/convert'"
                    >{{ $t('Convert to Invoice') }}</a
                  >
                </li>
                <li class="dropdown-item badge bg-label-secondary" v-if="purchase_order.attachment">
                  <a :href="purchase_order.attachment" target="_blank" class="btn btn-secondary mx-1">{{
                    $t('Attachment')
                  }}</a>
                </li>
              </ul>
            </div>
          </td>
          <td>
            <div class="modal fade" :id="'reject-purchase-order-' + purchase_order.id" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-md" role="document">
                <form @submit.prevent="submitApproval(purchase_order.id, 'rejected')">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Reject Purchase Order') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <label class="form-label">{{ $t('Rejection Reason') }}</label>
                      <div class="form-group">
                        <textarea v-model="rejection_reason" id="" rows="5" class="form-control"></textarea>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">
                        {{ $t('Close') }}
                      </button>
                      <button class="btn btn-primary" type="submit" :disabled="!can_submit">{{ $t('Submit') }}</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <div class="modal fade" :id="'purchase-order-' + purchase_order.id" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">{{ $t('Purchase Order') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="d-flex">
                      <div>
                        <a :href="'purchase-orders/' + purchase_order.id + '/pdf/download'" class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                      <a
                        v-if="purchase_order.attachment"
                        :href="attachment"
                        target="_blank"
                        class="btn btn-secondary mx-1"
                      >
                        <i class="ti ti-paperclip"></i> {{ $t('Attachment') }}</a
                      >
                    </div>
                  </div>
                  <div class="modal-body">
                    <div class="d-flex justify-content-between">
                      <div class="mb-3 w-25">
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">{{ $t('Buyer') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ purchase_order.anchor.name }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ purchase_order.delivery_address }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ purchase_order.remarks }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="fw-light my-auto">{{ $t('Status') }}:</h5>
                          <span class="badge m_title" :class="resolveStatus(purchase_order.approval_stage)">{{
                            purchase_order.approval_stage
                          }}</span>
                        </span>
                        <span class="d-flex justify-content-between" v-if="purchase_order.rejection_reason">
                          <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ purchase_order.rejection_reason }}</h6>
                        </span>
                      </div>
                      <div class="mb-3">
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">{{ $t('PO No') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">{{ purchase_order.purchase_order_number }}</h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">{{ $t('Start Date') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">
                            {{ moment(purchase_order.duration_from).format('DD MMM YYYY') }}
                          </h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">{{ $t('End Date') }}:</h5>
                          <h6 class="fw-bold mx-2 my-auto">
                            {{ moment(purchase_order.duration_to).format('DD MMM YYYY') }}
                          </h6>
                        </span>
                        <span class="d-flex justify-content-between">
                          <h5 class="my-auto fw-light">{{ $t('PO Amount') }}:</h5>
                          <h6 class="fw-bold text-success mx-2 my-auto">
                            Ksh {{ new Intl.NumberFormat().format(purchase_order.total_amount) }}
                          </h6>
                        </span>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>{{ $t('Item') }}</th>
                            <th>{{ $t('Quantity') }}</th>
                            <th>{{ $t('Price Per Quantity') }}</th>
                            <th>{{ $t('Total') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in purchase_order.purchase_order_items" :key="item.id">
                            <td>{{ item.item }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>{{ item.price_per_quantity }}</td>
                            <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="bg-label-secondary px-2">
                      <span class="d-flex justify-content-end">
                        <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                        <h5 class="text-success my-auto py-1">
                          {{ new Intl.NumberFormat().format(purchase_order.total_amount) }}
                        </h5>
                      </span>
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
    :from="purchase_orders.from"
    :to="purchase_orders.to"
    :links="purchase_orders.links"
    :next_page="purchase_orders.next_page_url"
    :prev_page="purchase_orders.prev_page_url"
    :total_items="purchase_orders.total"
    :first_page_url="purchase_orders.first_page_url"
    :last_page_url="purchase_orders.last_page_url"
    @change-page="changePage"
  ></pagination>
</template>

<script>
import { computed, onMounted, ref, watch, inject } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from './partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'PurchaseOrders',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const purchase_orders = ref([]);

    const rejection_reason = ref('');

    const per_page = ref(50);

    const can_submit = ref(true);

    const anchor_search = ref('');
    const po_search = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const status_search = ref('');
    const date_search = ref('');

    const bulk_action = ref('');

    const selected_data = ref([]);

    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);

    const approvePurchaseOrders = ref([]);

    const getPurchaseOrders = async () => {
      await axios
        .get(base_url + 'purchase-orders/pending/data?per_page=' + per_page.value)
        .then(response => {
          purchase_orders.value = response.data.purchase_orders;
        })
        .catch(err => {
          console.log(err.message);
        });
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Pending Checker Approval':
          style = 'bg-label-warning';
          break;
        case 'pending Maker':
          style = 'bg-label-primary';
          break;
        case 'Pending Checker':
          style = 'bg-label-warning';
          break;
        case 'Accepted':
          style = 'bg-label-success';
          break;
        case 'Invoiced':
          style = 'bg-label-secondary';
          break;
        case 'Rejected':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    const submitApproval = async (purchase_order, status) => {
      can_submit.value = false;
      await axios
        .post(`${base_url}purchase-orders/pending/status/update`, {
          purchase_order_id: purchase_order,
          status: status,
          rejection_reason: rejection_reason.value
        })
        .then(() => {
          toast.success('Purchase Order updated successfully');
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        })
        .finally(() => {
          can_submit.value = true;
        });
    };

    onMounted(() => {
      getPurchaseOrders();
    });

    watch([per_page], ([per_page]) => {
      axios
        .get(base_url + 'purchase-orders/pending/data?per_page=' + per_page, {
          params: {
            anchor: anchor_search.value,
            po_number: po_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status_search.value
          }
        })
        .then(response => {
          purchase_orders.value = response.data.purchase_orders;
        })
        .catch(error => {
          console.log(error);
        });
    });

    const filter = () => {
      date_search.value = $('#pending_date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'purchase-orders/pending/data?per_page=' + per_page.value, {
          params: {
            anchor: anchor_search.value,
            po_number: po_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status_search.value
          }
        })
        .then(response => {
          purchase_orders.value = response.data.purchase_orders;
        })
        .catch(error => {
          console.log(error);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      anchor_search.value = '';
      po_search.value = '';
      from_date.value = '';
      to_date.value = '';
      status_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      axios
        .get(base_url + 'purchase-orders/pending/data?per_page=' + per_page.value)
        .then(response => {
          purchase_orders.value = response.data.purchase_orders;
        })
        .catch(error => {
          console.log(error);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    watch(bulk_action, newVal => {
      if (selected_data.value.length > 0) {
        approvePurchaseOrders.value = [];
        if (newVal == 'approve') {
          selected_data.value.forEach(selected => {
            approvePurchaseOrders.value.push(purchase_orders.value.data.filter(request => request.id == selected)[0]);
          });
          showApprovalModal.value.click();
        }
        if (newVal == 'reject') {
          showRejectionModal.value.click();
        }
      } else {
        toast.error('Select POs');
      }
    });

    const selectAll = () => {
      selected_data.value = [];
      if (!document.getElementById('select-all').checked) {
        purchase_orders.value.data.forEach(data => {
          if (data.user_can_approve && !data.user_has_approved) {
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
        purchase_orders.value.data.forEach(data => {
          if (data.user_can_approve && !data.user_has_approved) {
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
        toast.error('Select Purchase orders');
        return;
      }

      const formData = new FormData();
      selected_data.value.forEach(request => {
        formData.append('purchase_orders[]', request);
      });
      if (action == 'approve') {
        formData.append('status', 'accepted');
      }
      if (action == 'reject') {
        formData.append('status', 'rejected');
        formData.append('rejection_reason', rejection_reason.value);
      }

      axios
        .post(base_url + 'purchase-orders/bulk/approve', formData)
        .then(() => {
          toast.success('POs updated');
          bulk_action.value = '';
          rejection_reason.value = '';
          getPurchaseOrders();
          document.getElementById('approve-data-close').click();
          document.getElementById('reject-data-close').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            anchor: anchor_search.value,
            po_number: po_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status_search.value
          }
        })
        .then(response => {
          purchase_orders.value = response.data.purchase_orders;
        })
        .catch(error => {
          console.log(error);
        });
    };

    return {
      moment,
      can_submit,
      purchase_orders,
      rejection_reason,
      per_page,

      anchor_search,
      po_search,
      from_date,
      to_date,
      status_search,

      bulk_action,

      selected_data,

      showRejectionModal,
      showApprovalModal,

      approvePurchaseOrders,

      filter,
      refresh,

      selectAll,
      updateSelected,
      submitBulkAction,

      resolveStatus,
      submitApproval,
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
