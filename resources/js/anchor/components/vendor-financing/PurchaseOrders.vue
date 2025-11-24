<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex flex-column flex-md-row justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="PO Number" class="form-label">{{ $t('Vendor') }}</label>
            <input type="text" class="form-control mb-1 form-search" id="defaultFormControlInput" placeholder="Vendor" v-model="vendor_search" aria-describedby="defaultFormControlHelp" v-on:keyup.enter="filter" />
          </div>
          <div class="">
            <label for="PO Number" class="form-label">{{ $t('PO Number') }}</label>
            <input type="text" class="form-control mb-1 form-search" id="defaultFormControlInput" placeholder="PO No" v-model="po_search" aria-describedby="defaultFormControlHelp" v-on:keyup.enter="filter" />
          </div>
          <div class="">
            <label for="Delivery From" class="form-label">{{ $t('Delivery From') }}</label>
            <input class="form-control form-search" type="date" id="html5-date-input" v-model="from_date" v-on:keyup.enter="filter" />
          </div>
          <div class="">
            <label for="Delivery To" class="form-label">{{ $t('Delivery To') }}</label>
            <input class="form-control form-search" type="date" id="html5-date-input" v-model="to_date" v-on:keyup.enter="filter" />
          </div>
          <div class="">
            <label for="Status" class="form-label">{{ $t('Status') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
              <option value="">{{ $t('Status') }}</option>
              <option value="pending">{{ $t('Pending') }}</option>
              <option value="approved">{{ $t('Approved') }}</option>
              <option value="invoiced">{{ $t('Invoiced') }}</option>
              <option value="rejected">{{ $t('Rejected') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Bulk Actions" class="form-label">{{ $t('Bulk Actions') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="bulk_action">
              <option value="">{{ $t('Select Bulk Action') }}</option>
              <option value="approve">{{ $t('Approve') }}</option>
              <option value="reject">{{ $t('Reject') }}</option>
            </select>
          </div>
          <div class="table-search-btn">
            <button class="btn btn-primary btn-sm" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-sm" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
          <button class="d-none" ref="showApprovalModal" data-bs-toggle="modal" :data-bs-target="'#bulk-approval-modal'"></button>
          <div class="modal fade" id="bulk-approval-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Purchase Orders') }}</h5>
                  <button type="button" class="btn-close" id="approve-data-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" @submit.prevent="submitBulkAction('approve')">
                  <div class="modal-body">
                    <h5>{{ $t('Are you sure you want to approve the selected purchase orders') }}?</h5>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <button class="d-none" ref="showRejectionModal" data-bs-toggle="modal" :data-bs-target="'#bulk-rejection-modal'"></button>
          <div class="modal fade" id="bulk-rejection-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalCenterTitle">{{ $t('Reject Selected Purchase Orders') }}</h5>
                  <button type="button" class="btn-close" id="reject-data-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
        <div class="d-flex gap-1 justify-content-md-end mt-2 mt-md-auto">
          <div class="">
            <select class="form-select" v-model="per_page" style="width: 5rem;">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
      </div>
      <pagination :from="purchase_orders.from" :to="purchase_orders.to" :links="purchase_orders.links" :next_page="purchase_orders.next_page_url" :prev_page="purchase_orders.prev_page_url" :total_items="purchase_orders.total" :first_page_url="purchase_orders.first_page_url" :last_page_url="purchase_orders.last_page_url" @change-page="changePage"></pagination>
      <div class="table-responsive p-2">
        <table class="table dt-invoices">
          <thead>
            <tr>
              <th>
                <div class="form-check">
                  <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
                </div>
              </th>
              <th>{{ $t('Vendor') }}</th>
              <th>{{ $t('PO No') }}.</th>
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
            <tr v-show="purchase_orders.data.length > 0" v-for="purchase_order in purchase_orders.data" :key="purchase_order.id">
              <td>
                <div class="form-check">
                  <input v-if="purchase_order.status == 'pending' && purchase_order.user_can_approve && !purchase_order.user_has_approved" class="form-check-input border-primary" type="checkbox" :id="'data-select-'+purchase_order.id" @change="updateSelected(purchase_order.id)" />
                </div>
              </td>
              <td>{{ purchase_order.company.name }}</td>
              <td class="text-primary text-decoration-underline" data-bs-toggle="modal" :data-bs-target="'#purchase-order-'+purchase_order.id" style="cursor: pointer">{{ purchase_order.purchase_order_number }}</td>
              <td class="text-success">{{ new Intl.NumberFormat().format(purchase_order.total_amount) }}</td>
              <td class="text-success">{{ purchase_order.purchase_order_items.length }}</td>
              <td class="">{{ moment(purchase_order.delivery_from).format('DD MMM YYYY') }}</td>
              <td class="">{{ moment(purchase_order.delivery_to).format('DD MMM YYYY') }}</td>
              <td><span class="badge py-2 mt-1" :class="resolveStatus(purchase_order.approval_stage)">{{ purchase_order.approval_stage }}</span></td>
              <td class="">
                <div class="d-inline-block">
                  <a href="javascript(0)" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="text-primary ti ti-dots-vertical"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end m-0">
                    <li v-if="purchase_order.status == 'pending' && purchase_order.user_can_approve && !purchase_order.user_has_approved" data-bs-toggle="modal" style="cursor: pointer" @click="submitApproval(purchase_order, 'approve')"><span class="dropdown-item badge bg-label-success pt-1">{{ $t('Approve') }}</span></li>
                    <li v-if="purchase_order.status == 'pending' && purchase_order.user_can_approve && !purchase_order.user_has_approved"><a :href="'purchase-orders/'+purchase_order.id+'/reject'" class="dropdown-item badge bg-label-danger pb-1">{{ $t('Reject') }}</a></li>
                    <li class="dropdown-item pb-1" v-if="purchase_order.attachment">
                      <a :href="purchase_order.attachment" target="_blank" class="badge bg-label-secondary"> {{ $t('View Attachment') }}</a>
                    </li>
                  </ul>
                </div>
              </td>
              <td>
                <div class="modal fade" :id="'purchase-order-'+purchase_order.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Purchase Order') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="d-flex">
                          <!-- <button class="btn btn-label-warning mx-1"> <i class="ti ti-pencil"></i> Replicate</button>
                          <button class="btn btn-label-primary mx-1"> <i class="ti ti-printer"></i> Print</button> -->
                          <a v-if="purchase_order.attachment" :href="attachment" target="_blank" class="btn btn-secondary mx-1"> <i class="ti ti-paperclip"></i> {{ $t('Attachment') }}</a>
                          <div>
                            <a :href="'purchase-orders/' + purchase_order.id + '/pdf/download'" class="btn btn-primary"
                              ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                            >
                          </div>
                        </div>
                      </div>
                      <div class="modal-body">
                        <div class="d-flex justify-content-between">
                        <div class="mb-3 w-50">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order.company.name }}</h6>
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
                            <span class="badge m_title" :class="resolveStatus(purchase_order.approval_stage)">{{ purchase_order.approval_stage }}</span>
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
                            <h6 class="fw-bold mx-2 my-auto">{{ moment(purchase_order.duration_from).format('DD MMM YYYY') }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('End Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ moment(purchase_order.duration_to).format('DD MMM YYYY') }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">{{ purchase_order.currency }} {{ new Intl.NumberFormat().format(purchase_order.total_amount) }}</h6>
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
                          <h5 class="text-success my-auto py-1">{{ new Intl.NumberFormat().format(purchase_order.total_amount) }}</h5>
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
      <pagination :from="purchase_orders.from" :to="purchase_orders.to" :links="purchase_orders.links" :next_page="purchase_orders.next_page_url" :prev_page="purchase_orders.prev_page_url" :total_items="purchase_orders.total" :first_page_url="purchase_orders.first_page_url" :last_page_url="purchase_orders.last_page_url" @change-page="changePage"></pagination>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject } from 'vue'
import axios from 'axios'
// Notification
import { useToast } from 'vue-toastification'
import Pagination from '../partials/Pagination.vue'
import moment from 'moment'

export default {
  name: "PurchaseOrders",
  components: {
    Pagination
  },
  setup() {
    const toast = useToast()
    const base_url = inject('baseURL')
    const purchase_orders = ref([])

    const rejection_reason = ref('')

    const vendor_search = ref('')
    const po_search = ref('')
    const from_date = ref('')
    const to_date = ref('')
    const status_search = ref('')

    const per_page = ref(50)

    const bulk_action = ref('')

    const selected_data = ref([])

    const showRejectionModal = ref(null)
    const showApprovalModal = ref(null)

    const approvePurchaseOrders = ref([])

    const getPurchaseOrders = async () => {
      await axios.get(base_url+'purchase-orders/data?per_page='+per_page.value)
        .then(response => {
          purchase_orders.value = response.data.purchase_orders
        })
        .catch(err => {
          console.log(err.message)
        })
    }

    const resolveStatus = (status) => {
      let style = ''
      switch (status) {
        case 'Pending Checker Approval':
          style = 'bg-label-warning'
          break;
        case 'Pending Maker':
          style = 'bg-label-primary'
          break;
        case 'Pending Checker':
          style = 'bg-label-warning'
          break;
        case 'Accepted':
          style = 'bg-label-success'
          break;
        case 'Rejected':
          style = 'bg-label-danger'
          break;
        default:
          style = 'bg-label-primary'
          break;
      }
      return style
    }

    const submitApproval = async (purchase_order, status) => {
      await axios.post(`${base_url}purchase-orders/approve`, {
        purchase_order_id: purchase_order.id,
        status: status,
        rejection_reason: rejection_reason.value
      })
        .then(() => {
          toast.success('Purchase Order updated successfully')
          setTimeout(() => {
            window.location.reload()
          }, 3000);
        })
        .catch(err => {
          console.log(err)
          toast.error('An error occurred')
        })
    }

    onMounted(() => {
      getPurchaseOrders()
    })

    watch([per_page], ([per_page]) => {
      axios.get(base_url+'purchase-orders/data?per_page='+per_page, {
        params: {
          vendor: vendor_search.value,
          po_number: po_search.value,
          from_date: from_date.value,
          to_date: to_date.value,
          status: status_search.value,
        }
      })
        .then(response => {
          purchase_orders.value = response.data.purchase_orders
        })
        .catch(error => {
          console.log(error)
        })
    })

    const filter = () => {
      let parent = $('.ti-search').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      axios.get(base_url+'purchase-orders/data?per_page='+per_page.value, {
        params: {
          vendor: vendor_search.value,
          po_number: po_search.value,
          from_date: from_date.value,
          to_date: to_date.value,
          status: status_search.value,
        }
      })
        .then(response => {
          purchase_orders.value = response.data.purchase_orders
        })
        .catch(error => {
          console.log(error)
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>')
        })
    }

    const refresh = () => {
      let parent = $('.ti-refresh').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      vendor_search.value = ''
      po_search.value = ''
      from_date.value = ''
      to_date.value = ''
      status_search.value = ''
      axios.get(base_url+'purchase-orders/data?per_page='+per_page.value)
        .then(response => {
          purchase_orders.value = response.data.purchase_orders
        })
        .catch(error => {
          console.log(error)
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>')
        })
    }

    watch(bulk_action, (newVal) => {
      if (selected_data.value.length > 0) {
        approvePurchaseOrders.value = []
        if (newVal == 'approve') {
          selected_data.value.forEach(selected => {
            approvePurchaseOrders.value.push(purchase_orders.value.data.filter(request => request.id == selected)[0])
          })
          showApprovalModal.value.click()
        }
        if (newVal == 'reject') {
          showRejectionModal.value.click()
        }
      } else {
        toast.error('Select POs')
      }
    })

    const selectAll = () => {
      selected_data.value = []
      if (!document.getElementById('select-all').checked) {
        purchase_orders.value.data.forEach(data => {
          if (data.user_can_approve && !data.user_has_approved) {
            document.getElementById('data-select-'+data.id).checked = false
            let f;
            let index = selected_data.value.filter(function (id, index) {f = index; id == data.id})
            if (!index) {
              return false
            }
            selected_data.value.splice(f, 1)
          }
        })
      } else {
        purchase_orders.value.data.forEach(data => {
          if (data.user_can_approve && !data.user_has_approved) {
            document.getElementById('data-select-'+data.id).checked = true
            selected_data.value.push(data.id)
          }
        })
      }
    }

    const updateSelected = (id) => {
      if (selected_data.value.includes(id)) {
        const index = selected_data.value.indexOf(id)
        selected_data.value.splice(index, 1)
      } else {
        selected_data.value.push(id)
      }
    }

    const submitBulkAction = (action) => {
      if (selected_data.value.length <= 0) {
        toast.error('Select Purchase orders')
        return
      }

      const formData = new FormData
      selected_data.value.forEach(request => {
        formData.append('purchase_orders[]', request)
      })
      if (action == 'approve') {
        formData.append('status', 'approved')
      }
      if (action == 'reject') {
        formData.append('status', 'rejected')
        formData.append('rejection_reason', rejection_reason.value)
      }

      axios.post(base_url+'purchase-orders/bulk/approve', formData)
        .then(() => {
          toast.success('POs updated');
          bulk_action.value = ''
          rejection_reason.value = ''
          getPurchaseOrders()
          document.getElementById('approve-data-close').click()
          document.getElementById('reject-data-close').click()
        })
        .catch(err => {
          console.log(err)
          toast.error('An error occurred')
        })
    }

    const changePage = async (page) => {
      await axios.get(page+'&per_page='+per_page.value, {
        params: {
          vendor: vendor_search.value,
          po_number: po_search.value,
          from_date: from_date.value,
          to_date: to_date.value,
          status: status_search.value,
        }
      })
        .then(response => {
          purchase_orders.value = response.data.purchase_orders
        })
        .catch(error => {
          console.log(error)
        })
    }

    return {
      moment,
      purchase_orders,
      rejection_reason,

      vendor_search,
      po_search,
      from_date,
      to_date,
      status_search,

      per_page,

      bulk_action,

      filter,
      refresh,

      bulk_action,
      showRejectionModal,
      showApprovalModal,
      selectAll,
      updateSelected,
      submitBulkAction,

      resolveStatus,
      submitApproval,
      changePage,
    }
  }
}
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
