<template>
  <div class="">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Vendor"
            v-model="vendor_search"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Invoice No"
            v-model="invoice_number_search"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Due Date') }})</label>
          <input
            type="text"
            id="pending_date_search"
            class="form-control form-search"
            name="pending_daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <!-- <div class="">
          <label for="From Date" class="form-label">{{ $t('From Date') }}</label>
          <input
            v-on:keyup.enter="filter"
            class="form-control form-search"
            type="date"
            value=""
            id="html5-date-input"
            v-model="from_date_search"
          />
        </div>
        <div class="">
          <label for="To Date" class="form-label">{{ $t('To Date') }}</label>
          <input class="form-control form-search" type="date" value="" id="html5-date-input" v-model="to_date_search" v-on:keyup.enter="filter" />
        </div> -->
        <div class="">
          <label for="Status" class="form-label">{{ $t('Invoice Status') }}</label>
          <select
            class="form-select form-search select2"
            id="pending-invoice-status-select"
            multiple
            v-model="status_search"
          >
            <option value="">{{ $t('Status') }}</option>
            <option value="pending_maker">{{ $t('Pending') }} ({{ $t('Maker') }})</option>
            <option value="pending_checker">{{ $t('Pending') }} ({{ $t('Checker') }})</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="rejected">{{ $t('Rejected') }}</option>
            <option value="past_due">{{ $t('Overdue') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Bulk Actions" class="form-label">{{ $t('Bulk Actions') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="bulk_action">
            <option value="">{{ $t('Bulk Actions') }}</option>
            <option value="approve">{{ $t('Approve') }}</option>
            <option value="reject">{{ $t('Reject') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
        </div>
        <button
          class="d-none"
          id="pending-show-bulk-approval-modal"
          data-bs-toggle="modal"
          data-bs-target="#pending-bulk-invoice-approval"
        >
          {{ $t('Approve') }}
        </button>
        <div class="modal fade" id="pending-bulk-invoice-approval" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Confirm Approval') }}</h5>
                <button
                  type="button"
                  id="pending-close-modal"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <template v-if="invoice_fees_processing">
                <p class="text-center my-4">{{ $t('Processing') }}...</p>
              </template>
              <form v-if="selected_invoices_taxes.length > 0" @submit.prevent="submitBulkApproval()">
                <div class="modal-body">
                  <table class="table">
                    <thead>
                      <tr>
                        <td>{{ $t('Invoice No') }}.</td>
                        <td>{{ $t('Subtotal') }}</td>
                        <td>{{ $t('Withholding Tax') }}(%)</td>
                        <td>{{ $t('Withholding VAT') }}(%)</td>
                        <td>{{ $t('Credit Note Amount') }}</td>
                        <td>{{ $t('Final Payable Amount') }}</td>
                      </tr>
                    </thead>
                    <tbody v-if="!invoice_fees_processing">
                      <tr v-for="invoice in invoices.data" :key="invoice.id">
                        <td v-if="selected_invoices.includes(invoice.id)">
                          <span>{{ invoice.invoice_number }}</span>
                        </td>
                        <td v-if="selected_invoices.includes(invoice.id)">
                          <span :id="'pending-invoice-amount-' + invoice.id">
                            {{
                              new Intl.NumberFormat().format(
                                invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount
                              )
                            }}
                          </span>
                        </td>
                        <td v-if="selected_invoices.includes(invoice.id)">
                          <div v-for="(invoice_tax, key) in selected_invoices_taxes" :key="key">
                            <div v-for="(tax_details, id) in invoice_tax" :key="id">
                              <div v-if="id == invoice.id">
                                <input
                                  :readonly="!invoice.can_edit_fees"
                                  class="form-control"
                                  v-model="invoice_tax[invoice.id]['Withholding Tax-' + invoice.id]"
                                  type="number"
                                  step=".01"
                                  min="0"
                                  max="100"
                                  :id="'pending-withholding-tax-' + invoice.id"
                                  @input="calculatePayable(invoice.id)"
                                />
                              </div>
                            </div>
                          </div>
                        </td>
                        <td v-if="selected_invoices.includes(invoice.id)">
                          <div v-for="(invoice_tax, key) in selected_invoices_taxes" :key="key">
                            <div v-for="(tax_details, id) in invoice_tax" :key="id">
                              <div v-if="id == invoice.id">
                                <input
                                  :readonly="!invoice.can_edit_fees"
                                  class="form-control"
                                  v-model="invoice_tax[invoice.id]['Withholding VAT-' + invoice.id]"
                                  type="number"
                                  step=".01"
                                  min="0"
                                  max="100"
                                  :id="'pending-withholding-vat-' + invoice.id"
                                  @input="calculatePayable(invoice.id)"
                                />
                              </div>
                            </div>
                          </div>
                        </td>
                        <td v-if="selected_invoices.includes(invoice.id)">
                          <div v-for="(invoice_tax, key) in selected_invoices_taxes" :key="key">
                            <div v-for="(tax_details, id) in invoice_tax" :key="id">
                              <div v-if="id == invoice.id">
                                <input
                                  :readonly="!invoice.can_edit_fees"
                                  class="form-control"
                                  v-model="invoice_tax[invoice.id]['Credit Note Amount-' + invoice.id]"
                                  type="number"
                                  step=".01"
                                  min="0"
                                  :id="'pending-credit-note-amount-' + invoice.id"
                                  @input="calculatePayable(invoice.id)"
                                />
                              </div>
                            </div>
                          </div>
                        </td>
                        <td v-if="selected_invoices.includes(invoice.id)">
                          <div v-for="(invoice_tax, key) in selected_invoices_taxes" :key="key">
                            <div v-for="(tax_details, id) in invoice_tax" :key="id">
                              <div v-if="id == invoice.id">
                                <span
                                  class="form-control"
                                  :id="'pending-invoice-total-payable-' + invoice.id"
                                  style="background: #f8f7fa"
                                >
                                  {{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit" :disabled="!can_bulk_approve">
                    {{ can_bulk_approve ? $t('Approve') : $t('Processing') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <button
          class="d-none"
          id="pending-show-bulk-reject-modal"
          data-bs-toggle="modal"
          data-bs-target="#pending-bulk-invoice-reject"
        >
          {{ $t('Reject') }}
        </button>
        <div class="modal fade" id="pending-bulk-invoice-reject" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Confirm Rejection') }}</h5>
                <button
                  type="button"
                  id="close-reject-modal"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form @submit.prevent="submitBulkRejection()">
                <div class="modal-body">
                  <h6>{{ $t('Are you sure you want to reject the selected invoices') }}?</h6>
                  <label for="reason" class="form-label">{{ $t('Reason') }}</label>
                  <textarea
                    name=""
                    id=""
                    cols="3"
                    v-model="rejected_reason"
                    placeholder="Enter Rejection Reason"
                    class="form-control"
                  ></textarea>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-danger" type="submit" :disabled="!can_bulk_approve">
                    {{ can_bulk_approve ? $t('Confirm') : $t('Processing') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
        <div>
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary" @click="exportInvoices">
          <i class="ti ti-download ti-sm"></i> {{ $t('Excel') }}
        </button>
      </div>
    </div>
    <pagination
      v-if="invoices.meta"
      :from="invoices.meta.from"
      :to="invoices.meta.to"
      :links="invoices.meta.links"
      :next_page="invoices.links.next"
      :prev_page="invoices.links.prev"
      :total_items="invoices.meta.total"
      :first_page_url="invoices.links.first"
      :last_page_url="invoices.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive p-1">
      <table class="table dt-invoices">
        <thead>
          <tr>
            <th class="px-1">
              <div class="form-check">
                <input
                  class="form-check-input border-primary"
                  type="checkbox"
                  id="pending-select-all"
                  @change="pendingSelectAll()"
                />
              </div>
            </th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('PO No') }}.</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Invoice Date') }}</th>
            <th>{{ $t('Due Date') }}</th>
            <th>{{ $t('Invoice Status') }}</th>
            <th>{{ $t('Actions') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!invoices.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="invoices.data && invoices.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-show="invoices.data.length > 0" v-for="invoice in invoices.data" :key="invoice.id">
            <td class="px-1">
              <div
                v-if="
                  invoice.status === 'submitted' &&
                  invoice.user_can_approve &&
                  !invoice.user_has_approved &&
                  moment(invoice.due_date).isSameOrAfter(moment())
                "
                class="form-check"
              >
                <input
                  class="form-check-input border-primary"
                  type="checkbox"
                  :id="'pending-data-select-' + invoice.id"
                  @change="updateSelected(invoice.id)"
                />
              </div>
            </td>
            <td>{{ invoice.company }}</td>
            <td v-if="invoice.purchase_order_number" class="text-primary text-decoration-underline">
              <a
                href="javascript:;"
                @click="
                  showPurchaseOrder(invoice.purchase_order_id, 'show-purchase-order-btn-' + invoice.purchase_order_id)
                "
                >{{ invoice.purchase_order_number }}</a
              >
            </td>
            <td v-else class="text-primary">-</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
            >
              <a href="javascript:;">{{ invoice.invoice_number }}</a>
            </td>
            <td class="text-success">
              {{ invoice.currency }}
              <span>
                {{
                  new Intl.NumberFormat().format(
                    invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount
                  )
                }}
              </span>
            </td>
            <td class="">{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
            <td class="">{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
            <td>
              <span class="badge me-1 m_title" :class="resolveStatus(invoice.approval_stage)">
                {{ invoice.approval_stage }}
              </span>
            </td>
            <td class="">
              <div class="d-inline-block">
                <a
                  href="javascript(0)"
                  class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown"
                >
                  <i class="text-primary ti ti-dots-vertical"></i>
                </a>
                <button
                  class="d-none"
                  :id="'show-details-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-pi-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-approvals-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#checker-approvals-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'pending-show-edit-fees-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pending-edit-fees-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-reject-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#invoice-reject-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'pending-show-approve-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pending-invoice-approval-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-authorization-matrix-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#authorization-matrix-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-purchase-order-btn-' + invoice.purchase_order_id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#purchase-order-' + invoice.purchase_order_id"
                ></button>
                <button
                  class="d-none"
                  id="pending-loading-modal-btn"
                  data-bs-toggle="modal"
                  data-bs-target="#pending-loading-modal"
                ></button>
                <div class="modal fade" id="pending-loading-modal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                      <div class="modal-body">
                        <div class="d-flex justify-content-center">
                          <span>{{ $t('Loading') }}...</span>
                          <img src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a
                      href="javascript:;"
                      class="dropdown-item badge bg-label-primary mt-2"
                      @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
                      >{{ $t('Details') }}</a
                    >
                  </li>
                  <li v-if="invoice.can_edit">
                    <a :href="'invoices/' + invoice.id + '/edit'" class="dropdown-item badge bg-label-info">{{
                      $t('Edit')
                    }}</a>
                  </li>
                  <li
                    v-if="
                      invoice.status === 'submitted' &&
                      invoice.user_can_approve &&
                      !invoice.user_has_approved &&
                      moment(invoice.due_date).isSameOrAfter(moment())
                    "
                    style="cursor: pointer"
                    @click="showInvoice(invoice.id, 'pending-show-edit-fees-' + invoice.id)"
                  >
                    <span class="dropdown-item badge bg-label-success">{{ $t('Approve') }}</span>
                  </li>
                  <li
                    v-if="
                      invoice.status === 'submitted' &&
                      invoice.user_can_approve &&
                      !invoice.user_has_approved &&
                      moment(invoice.due_date).isSameOrAfter(moment())
                    "
                  >
                    <a
                      href="#"
                      style="cursor: pointer"
                      class="dropdown-item badge bg-label-danger"
                      @click="showInvoice(invoice.id, 'show-reject-btn-' + invoice.id)"
                      >{{ $t('Reject') }}</a
                    >
                  </li>
                  <li style="cursor: pointer" @click="showInvoice(invoice.id, 'show-approvals-btn-' + invoice.id)">
                    <span class="dropdown-item badge bg-label-info">{{ $t('Checker Approvals') }}</span>
                  </li>
                  <li
                    v-if="invoice.authorization_group"
                    style="cursor: pointer"
                    @click="showInvoice(invoice.id, 'show-authorization-matrix-btn-' + invoice.id)"
                    class="mb-2"
                  >
                    <span class="dropdown-item badge bg-label-warning">{{ $t('Authorization Matrix') }}</span>
                  </li>
                </ul>
              </div>
            </td>
            <td>
              <div class="modal fade" :id="'invoice-reject-' + invoice.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Confirm Rejection') }}</h5>
                      <button
                        type="button"
                        :id="'close-reject-modal-' + invoice.id"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <form @submit.prevent="submitRejection(invoice.id)">
                      <div class="modal-body">
                        <h6>{{ $t('Are you sure you want to reject the selected invoices') }}?</h6>
                        <label for="reason" class="form-label">{{ $t('Reason') }}</label>
                        <textarea
                          name=""
                          id=""
                          cols="3"
                          v-model="rejected_reason"
                          placeholder="Enter Rejection Reason"
                          class="form-control"
                        ></textarea>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-danger" type="submit">{{ $t('Confirm') }}</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
              <payment-instruction
                v-if="invoice_details"
                :invoice-details="invoice_details"
                :key="invoice_details_key"
              />
              <div
                class="modal fade"
                v-if="purchase_order_details"
                :id="'purchase-order-' + invoice.purchase_order_id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Purchase Order') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex">
                        <div>
                          <a
                            :href="'purchase-orders/' + purchase_order_details.id + '/pdf/download'"
                            class="btn btn-primary"
                            ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                          >
                        </div>
                        <a
                          v-if="purchase_order_details.attachment"
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
                        <div class="mb-3 w-50">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.company.name }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.delivery_address }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.remarks }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Status') }}:</h5>
                            <span class="badge m_title" :class="resolveStatus(purchase_order_details.approval_stage)">{{
                              purchase_order_details.approval_stage
                            }}</span>
                          </span>
                          <span class="d-flex justify-content-between" v-if="purchase_order_details.rejection_reason">
                            <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.rejection_reason }}</h6>
                          </span>
                        </div>
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO No') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ purchase_order_details.purchase_order_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Start Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(purchase_order_details.duration_from).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('End Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(purchase_order_details.duration_to).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">
                              {{ purchase_order_details.currency }}
                              {{ new Intl.NumberFormat().format(purchase_order_details.total_amount) }}
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
                            <tr v-for="item in purchase_order_details.purchase_order_items" :key="item.id">
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
                            {{ new Intl.NumberFormat().format(purchase_order_details.total_amount) }}
                          </h5>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'pending-invoice-approval-' + invoice_details.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">
                        {{ $t('Confirm Approval for ' + invoice_details.invoice_number) }}
                      </h5>
                      <button
                        type="button"
                        :id="'pending-close-modal-' + invoice_details.id"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <!-- <form
                      @submit.prevent="approveFees(invoice_details)"
                      :id="'pending-view-fees-' + invoice_details.id"
                    >
                      <div v-if="invoice_details && invoice_details.invoice_fees.length > 0" class="modal-body">
                        <div class="d-flex justify-content-between">
                          <div></div>
                          <button
                            class="btn btn-primary btn-sm"
                            style="cursor: pointer"
                            type="button"
                            data-bs-toggle="modal"
                            :data-bs-target="'#pending-edit-fees-' + invoice.id"
                          >
                            {{ $t('Edit Fees') }}
                          </button>
                        </div>
                        <div class="form-group">
                          <label for="Sub total" class="form-label">{{ $t('Sub Total') }}</label>
                          <input
                            type="text"
                            class="form-control"
                            name="sub_total"
                            id=""
                            v-model="invoice_subtotal"
                            readonly
                          />
                        </div>
                        <div class="form-group" v-for="fee in invoice_details.invoice_fees" :key="fee.id">
                          <label class="form-label">{{ fee.name }}</label>
                          <input
                            class="form-control"
                            readonly
                            v-if="fee.name != 'Credit Note Amount'"
                            :value="
                              getTaxPercentage(
                                invoice_details.invoice_number,
                                invoice_details.total + invoice_details.total_invoice_taxes - invoice_details.total_invoice_discount,
                                fee.amount
                              )
                            "
                          />
                          <input class="form-control" readonly v-else :value="fee.amount" />
                        </div>
                        <div class="form-group">
                          <label for="Sub total" class="form-label">{{ $t('Final Payable Amount') }}</label>
                          <input
                            type="text"
                            class="form-control"
                            name="sub_total"
                            id=""
                            :value="new Intl.NumberFormat().format(invoice_details.invoice_total_amount)"
                            readonly
                          />
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">{{ $t('Approve Fees') }}</button>
                      </div>
                    </form> -->
                    <form
                      @submit.prevent="approveFees(invoice_details)"
                      :id="'pending-view-fees-' + invoice_details.id"
                    >
                      <div class="modal-body">
                        <div v-if="invoice_details.invoice_fees.length > 0">
                          <div class="d-flex justify-content-between">
                            <div></div>
                            <button
                              class="btn btn-primary btn-sm"
                              :id="'pending-edit-fees-btn-' + invoice_details.id"
                              style="cursor: pointer"
                              type="button"
                              data-bs-toggle="modal"
                              :data-bs-target="'#pending-edit-fees-' + invoice.id"
                            >
                              {{ $t('Edit Fees') }}
                            </button>
                          </div>
                          <div class="form-group">
                            <label for="Sub total" class="form-label">{{ $t('Sub Total') }}</label>
                            <input
                              type="text"
                              class="form-control"
                              name="sub_total"
                              id=""
                              v-model="invoice_subtotal"
                              readonly
                            />
                          </div>
                          <div class="form-group" v-for="fee in invoice_details.invoice_fees" :key="fee.id">
                            <label class="form-label">{{ fee.name }}</label>
                            <input
                              class="form-control"
                              readonly
                              v-if="fee.name != 'Credit Note Amount'"
                              :value="
                                getTaxPercentage(
                                  invoice_details.invoice_number,
                                  invoice_details.total +
                                    invoice_details.total_invoice_taxes -
                                    invoice_details.total_invoice_discount,
                                  fee.amount
                                )
                              "
                            />
                            <input class="form-control" readonly v-else :value="fee.amount" />
                          </div>
                          <div class="form-group">
                            <label for="Sub total" class="form-label">{{ $t('Final Payable Amount') }}</label>
                            <input
                              type="text"
                              class="form-control"
                              name="sub_total"
                              id=""
                              :value="new Intl.NumberFormat().format(invoice_details.invoice_total_amount)"
                              readonly
                            />
                          </div>
                        </div>
                        <div v-else>
                          <div class="d-flex justify-content-between">
                            <div>
                              <span>{{ $t('Fees not set for this invoice') }}</span>
                            </div>
                            <button
                              class="btn btn-primary btn-sm"
                              :id="'pending-edit-fees-btn-' + invoice_details.id"
                              style="cursor: pointer"
                              type="button"
                              data-bs-toggle="modal"
                              :data-bs-target="'#pending-edit-fees-' + invoice.id"
                            >
                              {{ $t('Click to add fees') }}
                            </button>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">
                          {{ invoice_details.invoice_fees.length ? $t('Approve Fees') : $t('Approve') }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'pending-edit-fees-' + invoice.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">{{ $t('Approve ' + invoice_details.invoice_number) }}</h5>
                      <button
                        class="btn-close"
                        type="button"
                        :id="'pending-edit-fees-close-btn-' + invoice.id"
                        data-bs-dismiss="modal"
                        aria-label="close"
                      ></button>
                    </div>
                    <form
                      @submit.prevent="submitApproval(invoice_details)"
                      :id="'anchor-edit-fees-' + invoice_details.id"
                    >
                      <div class="modal-body">
                        <div class="form-group">
                          <label for="Sub total" class="form-label">{{ $t('Sub Total') }}</label>
                          <input
                            type="text"
                            class="form-control"
                            name="sub_total"
                            id=""
                            v-model="invoice_subtotal"
                            readonly
                          />
                        </div>
                        <div class="form-group" v-for="(type, taxes) in anchor_taxes" :key="taxes">
                          <div v-if="type == 'percentage'">
                            <label for="">{{ taxes }} %</label>
                            <input
                              type="number"
                              min="0"
                              max="100"
                              step=".01"
                              class="form-control"
                              v-model="invoice_taxes[taxes]"
                              @blur="updateInvoiceTotal"
                            />
                          </div>
                          <div v-if="type == 'amount'">
                            <label for="">{{ taxes }}</label>
                            <input
                              type="number"
                              min="0"
                              class="form-control"
                              step=".01"
                              v-model="invoice_taxes[taxes]"
                              @blur="updateInvoiceTotal"
                            />
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="Sub total" class="form-label">{{ $t('Final Payable Amount') }}</label>
                          <input
                            type="text"
                            class="form-control"
                            name="sub_total"
                            id=""
                            v-model="invoice_total"
                            readonly
                          />
                        </div>
                        <div v-if="invoice_details && invoice_details.invoice_fees.length > 0" class="mt-2">
                          <h5 class="text-sm">{{ $t('Rates set by maker') }}:</h5>
                          <span v-for="fee in invoice_details.invoice_fees" :key="fee.id" class="d-flex">
                            <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                            <h5 class="text-success my-auto py-1" v-if="fee.name != 'Credit Note Amount'">
                              ({{
                                getTaxPercentage(
                                  invoice_details.invoice_number,
                                  invoice_details.total +
                                    invoice_details.total_invoice_taxes -
                                    invoice_details.total_invoice_discount,
                                  fee.amount
                                )
                              }}%) {{ new Intl.NumberFormat().format(fee.amount.toFixed(2)) }}
                            </h5>
                            <h5 class="text-success my-auto py-1" v-else>
                              {{ invoice_details.currency }} {{ fee.amount }}
                            </h5>
                          </span>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'checker-approvals-' + invoice.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Checker Approval(s)') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>{{ $t('Approved By') }}</th>
                            <th>{{ $t('Approved On') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="user in users" :key="user.id">
                            <template v-if="userHasApproved(invoice_details, user)">
                              <td class="">
                                {{ user.name }}
                              </td>
                              <td>
                                {{
                                  approvalDetails(invoice_details, user)
                                    ? moment(approvalDetails(invoice_details, user).created_at).format('DD MMM YYYY')
                                    : '-'
                                }}
                              </td>
                            </template>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="invoice_details && invoice_details.authorization_group"
                :id="'authorization-matrix-' + invoice.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">
                        {{ $t('Authorization Matrix') }} - {{ invoice_details.invoice_number }}
                      </h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <h6>{{ $t('Maker Approval(s)') }}</h6>
                      <div v-for="user in invoice_details.authorization_group.authorization_users" :key="user.id">
                        <div v-if="userHasApproved(invoice_details, user.user)" class="d-flex">
                          <span class="text-success" style="font-weight: 800">
                            {{ user.user.name }}
                          </span>
                          <span>
                            -
                            {{
                              approvalDetails(invoice_details, user.user)
                                ? moment(approvalDetails(invoice_details, user.user).created_at).format('DD MMM YYYY')
                                : '-'
                            }}
                          </span>
                        </div>
                      </div>
                      <hr />
                      <h6>
                        {{ invoice_details.authorization_group.name }}
                        <span class="text-danger" style="font-weight: 800"
                          >({{ invoice_details.authorization_group.level }})</span
                        >
                      </h6>
                      <div v-for="user in invoice_details.authorization_group.authorization_users" :key="user.id">
                        <div v-if="userHasApproved(invoice_details, user.user)" class="d-flex">
                          <span class="text-success" style="font-weight: 800">
                            {{ user.user.name }}
                          </span>
                          <span>
                            -
                            {{
                              approvalDetails(invoice_details, user.user)
                                ? moment(approvalDetails(invoice_details, user.user).created_at).format('DD MMM YYYY')
                                : '-'
                            }}
                          </span>
                        </div>
                        <span class="" v-else>
                          {{ user.user.name }}
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
      v-if="invoices.meta"
      :from="invoices.meta.from"
      :to="invoices.meta.to"
      :links="invoices.meta.links"
      :next_page="invoices.links.next"
      :prev_page="invoices.links.prev"
      :total_items="invoices.meta.total"
      :first_page_url="invoices.links.first"
      :last_page_url="invoices.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'Invoices',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup(props, context) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);
    const eligibility = ref(0);
    const eligibility_amount = ref(0);
    const invoice_details = ref(null);
    const invoice_details_key = ref(null);
    const purchase_order_details = ref(null);
    const pi_amount = ref(0);
    const business_spread = ref(0);
    const discount_amount = ref(0);
    const anchor_taxes = ref([]);
    const users = ref([]);
    const invoice_taxes = ref([]);
    const invoice_subtotal = ref(0);
    const invoice_total = ref(0);
    const purchase_order_total = ref(0);

    const per_page = ref(50);

    const vendor_search = ref('');
    const invoice_number_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const status_search = ref([]);
    const financing_status_search = ref([]);
    const date_search = ref('');

    const selected_invoices = ref([]);

    const rejected_reason = ref('');

    const bulk_action = ref('');

    const selected_invoices_taxes = ref([]);

    const invoice_fees_processing = ref(false);

    const can_bulk_approve = ref(true);

    const showPurchaseOrder = async (purchase_order, modal) => {
      await axios
        .get(base_url + 'purchase-orders/' + purchase_order + '/show')
        .then(response => {
          purchase_order_details.value = response.data;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editFees = invoice => {
      document.getElementById('pending-edit-fees-' + invoice.id).classList.remove('d-none');
      document.getElementById('pending-view-fees-' + invoice.id).classList.add('d-none');
    };

    const getInvoices = async () => {
      await axios
        .get(base_url + 'invoices/pending/data', {
          params: {
            per_page: per_page.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          anchor_taxes.value = response.data.taxes;
          users.value = response.data.users;
        })
        .catch(err => {
          console.log(err.message);
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('pending-loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          invoice_subtotal.value = new Intl.NumberFormat().format(
            invoice_details.value.total +
              invoice_details.value.total_invoice_taxes -
              invoice_details.value.total_invoice_discount
          );
          invoice_total.value = new Intl.NumberFormat().format(
            invoice_details.value.total +
              invoice_details.value.total_invoice_taxes -
              invoice_details.value.total_invoice_discount
          );
          invoice_details_key.value++;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const getTaxPercentage = (invoice_number, invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Overdue':
          style = 'bg-label-danger';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending Approval':
          style = 'bg-label-warning';
          break;
        case 'Pending Maker':
          style = 'bg-label-primary';
          break;
        case 'Pending Checker':
          style = 'bg-label-danger';
          break;
        case 'Submitted':
          style = 'bg-label-primary';
          break;
        case 'Approved':
          style = 'bg-label-success';
          break;
        case 'Disbursed':
          style = 'bg-label-success';
          break;
        case 'Denied':
          style = 'bg-label-danger';
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

    const resolveFinancingStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'financed':
          style = 'bg-label-success';
          break;
        case 'closed':
          style = 'bg-label-secondary';
          break;
        case 'denied':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    const updateInvoiceTotal = () => {
      let total_percentage = 0;
      let total_amount = 0;
      let keys = Object.keys(invoice_taxes.value);
      let values = Object.values(invoice_taxes.value);
      values.forEach((element, key) => {
        if (keys[key] == 'Credit Note Amount') {
          if (element > 0) {
            total_amount = element;
          }
        }
        if (keys[key] != 'Credit Note Amount') {
          if (element > 0) {
            total_percentage += element;
          } else {
            total_percentage -= element;
          }
        }
      });

      if (total_percentage > 0) {
        invoice_total.value = new Intl.NumberFormat().format(
          invoice_subtotal.value.replaceAll(',', '') -
            (total_percentage / 100) * invoice_subtotal.value.replaceAll(',', '')
        );
      }

      if (total_amount > 0) {
        invoice_total.value = new Intl.NumberFormat().format(invoice_total.value.replaceAll(',', '') - total_amount);
      }

      if (total_amount == 0 && total_percentage == 0) {
        invoice_total.value = new Intl.NumberFormat.format(invoice_subtotal.value.replaceAll(',', ''));
      }
    };

    const submitApproval = async invoice => {
      const formData = new FormData();
      for (const item in invoice_taxes.value) {
        if (item == 'Credit Note Amount') {
          formData.append('taxes[' + item + ']', invoice_taxes.value[item]);
        }
        if (item != 'Credit Note Amount') {
          formData.append(
            'taxes[' + item + ']',
            (invoice_taxes.value[item] / 100) * invoice_subtotal.value.replaceAll(/\D/g, '').replaceAll(',', '')
          );
        }
      }
      await axios
        .post(`${base_url}invoices/${invoice.id}/approve`, formData)
        .then(() => {
          getInvoices();
          toast.success('Invoice updated successfully');
          document.getElementById('pending-edit-fees-close-btn-' + invoice.id).click();
          for (const item in invoice_taxes.value) {
            invoice_taxes.value = [];
          }
        })
        .catch(err => {
          console.log(err);
          if (err.response.status == 422) {
            toast.error('Enter applied taxes to approve');
            return;
          }
          toast.error('An error occurred');
        });
    };

    const approveFees = async invoice => {
      await axios
        .get(`${base_url}invoices/${invoice.id}/fees/approve`)
        .then(() => {
          getInvoices();
          toast.success('Invoice updated successfully');
          document.getElementById('pending-close-modal-' + invoice.id).click();
          for (const item in invoice_taxes.value) {
            invoice_taxes.value[item] = '';
          }
        })
        .catch(err => {
          console.log(err);
          if (err.response.status == 422) {
            toast.error('Enter applied taxes to approve');
            return;
          }
          toast.error('An error occurred');
        });
    };

    const pendingSelectAll = () => {
      selected_invoices.value = [];
      invoice_fees_processing.value = true;
      if (!document.getElementById('pending-select-all').checked) {
        invoices.value.data.forEach(data => {
          if (data.status == 'submitted' && data.user_can_approve && !data.user_has_approved) {
            document.getElementById('pending-data-select-' + data.id).checked = false;
            let f;
            let index = selected_invoices.value.filter(function (id, index) {
              f = index;
              id == data.id;
            });
            if (!index) {
              return false;
            }
            selected_invoices.value.splice(f, 1);
          }
        });
        invoice_fees_processing.value = false;
      } else {
        invoices.value.data.forEach(async data => {
          if (data.status == 'submitted' && data.user_can_approve && !data.user_has_approved) {
            document.getElementById('pending-data-select-' + data.id).checked = true;
            selected_invoices.value.push(data.id);
          }
        });
      }

      if (selected_invoices.value.length > 0) {
        let taxes = {};
        let invoice_fee_details = {};

        const formData = new FormData();
        formData.append('invoices', selected_invoices.value);

        axios.post(base_url + 'invoices/bulk/details', formData).then(response => {
          selected_invoices_taxes.value = [];
          response.data.data.forEach(invoice_data => {
            invoice_fee_details[invoice_data.id] = [];
            if (invoice_data.invoice_fees.length > 0) {
              let amount = 0;
              taxes = {};
              invoice_data.invoice_fees.forEach(invoice_fee => {
                if (invoice_fee.name != 'Credit Note Amount') {
                  amount = getTaxPercentage(
                    invoice_data.invoice_number,
                    invoice_data.total + invoice_data.total_invoice_taxes - invoice_data.total_invoice_discount,
                    invoice_fee.amount
                  );
                } else {
                  amount = invoice_fee.amount;
                }

                let exists =
                  Object.keys(taxes + '-' + invoice_data.id).filter(i => i == invoice_fee.name + '-' + invoice_data.id)
                    .length > 0;

                if (!exists) {
                  taxes[invoice_fee.name + '-' + invoice_data.id] = amount;
                }
              });
            } else {
              taxes = {};
              Object.keys(anchor_taxes.value).forEach(anchor_tax => {
                taxes[anchor_tax + '-' + invoice_data.id] = 0;
              });
            }
            invoice_fee_details[invoice_data.id] = taxes;
          });
          selected_invoices_taxes.value.push(invoice_fee_details);
          invoice_fees_processing.value = false;
        });
      }
    };

    const updateSelected = id => {
      if (selected_invoices.value.includes(id)) {
        const index = selected_invoices.value.indexOf(id);
        selected_invoices.value.splice(index, 1);
      } else {
        selected_invoices.value.push(id);
      }

      if (selected_invoices.value.length > 0) {
        let taxes = {};
        let invoice_fee_details = {};

        const formData = new FormData();
        formData.append('invoices', selected_invoices.value);

        axios.post(base_url + 'invoices/bulk/details', formData).then(response => {
          selected_invoices_taxes.value = [];
          response.data.data.forEach(invoice_data => {
            invoice_fee_details[invoice_data.id] = [];
            if (invoice_data.invoice_fees.length > 0) {
              let amount = 0;
              taxes = {};
              invoice_data.invoice_fees.forEach(invoice_fee => {
                if (invoice_fee.name != 'Credit Note Amount') {
                  amount = getTaxPercentage(
                    invoice_data.invoice_number,
                    invoice_data.total + invoice_data.total_invoice_taxes - invoice_data.total_invoice_discount,
                    invoice_fee.amount
                  );
                } else {
                  amount = invoice_fee.amount;
                }

                let exists =
                  Object.keys(taxes + '-' + invoice_data.id).filter(i => i == invoice_fee.name + '-' + invoice_data.id)
                    .length > 0;

                if (!exists) {
                  taxes[invoice_fee.name + '-' + invoice_data.id] = amount;
                }
              });
            } else {
              taxes = {};
              Object.keys(anchor_taxes.value).forEach(anchor_tax => {
                taxes[anchor_tax + '-' + invoice_data.id] = 0;
              });
            }
            invoice_fee_details[invoice_data.id] = taxes;
          });
          selected_invoices_taxes.value.push(invoice_fee_details);
          invoice_fees_processing.value = false;
        });
      }
    };

    const bulkAction = action => {
      if (selected_invoices.value.length <= 0) {
        toast.error('Select invoices');
        return;
      }
    };

    watch(bulk_action, newVal => {
      if (newVal == 'approve' || newVal == 'reject') {
        if (selected_invoices.value.length > 0) {
          if (newVal == 'reject') {
            document.getElementById('pending-show-bulk-reject-modal').click();
          }
          if (newVal == 'approve') {
            document.getElementById('pending-show-bulk-approval-modal').click();
          }
          bulk_action.value = '';
        } else {
          toast.error('Select Invoices');
        }
      }
    });

    const submitBulkApproval = () => {
      const formData = new FormData();
      selected_invoices.value.forEach((invoice, index) => {
        formData.append('invoice[' + index + ']', invoice);
      });

      formData.append('invoice_taxes', JSON.stringify(selected_invoices_taxes.value));

      formData.append('action', bulk_action.value);

      can_bulk_approve.value = false;

      axios
        .post(base_url + 'invoices/bulk/update/approve', formData)
        .then(() => {
          getInvoices();
          toast.success('Invoices updated successfully');
          document.getElementById('pending-close-modal').click();
          document.getElementById('select-all').checked = false;
          for (const item in invoice_taxes.value) {
            invoice_taxes.value[item] = '';
          }
          selected_invoices.value = [];
          selected_invoices_taxes.value = [];
        })
        .catch(err => {
          console.log(err);
          if (err.response.status == 422) {
            toast.error('Enter applied taxes to approve');
            return;
          }
          toast.error('An error occurred');
        })
        .finally(() => {
          bulk_action.value = '';
          can_bulk_approve.value = true;
        });
    };

    const submitBulkRejection = () => {
      const formData = new FormData();
      formData.append('rejected_reason', rejected_reason.value);
      selected_invoices.value.forEach((invoice, index) => {
        formData.append('invoice[' + index + ']', invoice);
      });

      formData.append('action', bulk_action.value);

      can_bulk_approve.value = false;

      axios
        .post(base_url + 'invoices/bulk/update/reject', formData)
        .then(() => {
          getInvoices();
          toast.success('Invoices updated successfully');
          document.getElementById('close-reject-modal').click();
          document.getElementById('select-all').checked = false;
          rejected_reason.value = '';
          selected_invoices.value = [];
          selected_invoices_taxes.value = [];
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        })
        .finally(() => {
          can_bulk_approve.value = true;
          bulk_action.value = '';
        });
    };

    const submitRejection = id => {
      const formData = new FormData();
      formData.append('rejected_reason', rejected_reason.value);

      formData.append('action', bulk_action.value);

      axios
        .post(base_url + 'invoices/' + id + '/reject', formData)
        .then(() => {
          getInvoices();
          toast.success('Invoices updated successfully');
          document.getElementById('close-reject-modal-' + id).click();
          bulk_action.value = '';
          rejected_reason.value = '';
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    context.expose({ getInvoices });

    onMounted(() => {
      $('#pending-invoice-status-select').on('change', function () {
        var ids = $('#pending-invoice-status-select').val();
        status_search.value = ids;
      });
      $('#pending-financing-status-select').on('change', function () {
        var ids = $('#pending-financing-status-select').val();
        financing_status_search.value = ids;
      });
    });

    watch([per_page], async ([per_page]) => {
      axios
        .get(base_url + 'invoices/pending/data', {
          params: {
            per_page: per_page,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    });

    const download = invoice => {
      axios
        .get(base_url + 'invoices/' + invoice.id + '/download', {
          responseType: 'arraybuffer',
          method: 'GET'
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Invoice_${invoice.invoice_number}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportInvoices = () => {
      axios
        .get(base_url + 'invoices/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Invoices_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        })
        .catch(error => {
          console.log(error);
        });
    };

    const clearSearch = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      vendor_search.value = '';
      invoice_number_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      status_search.value = [];
      financing_status_search.value = [];
      date_search.value = '';
      $('#date_search').val('');
      await axios
        .get(base_url + 'invoices/pending/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          anchor_taxes.value = response.data.taxes;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const filter = async () => {
      date_search.value = $('#pending_date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'invoices/pending/data', {
          params: {
            per_page: per_page.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          anchor_taxes.value = response.data.taxes;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const userHasApproved = (invoice, user) => {
      let has_approved = false;
      invoice.approvals.forEach(approval => {
        if (user.id == approval.user_id) {
          has_approved = true;
        }
      });

      return has_approved;
    };

    const approvalDetails = (invoice, user) => {
      let approval = null;
      approval =
        invoice.approvals.filter(approval => approval.user_id == user.id).length > 0
          ? invoice.approvals.filter(approval => approval.user_id == user.id)[0]
          : null;
      return approval;
    };

    const calculatePayable = invoice_id => {
      let invoice_total = $('#pending-invoice-amount-' + invoice_id)
        .text()
        .replaceAll(',', '');
      let withholding_tax = $('#pending-withholding-tax-' + invoice_id).val();
      let withholding_vat = $('#pending-withholding-vat-' + invoice_id).val();
      let credit_note_amount = $('#pending-credit-note-amount-' + invoice_id).val();
      let withholding_tax_amount = (withholding_tax / 100) * invoice_total;
      let withholding_vat_amount = (withholding_vat / 100) * invoice_total;
      let payable_amount = invoice_total - withholding_tax_amount - withholding_vat_amount - credit_note_amount;
      $('#pending-invoice-total-payable-' + invoice_id).text(new Intl.NumberFormat().format(payable_amount));
    };

    return {
      moment,
      per_page,
      invoices,
      eligibility,
      eligibility_amount,
      business_spread,
      discount_amount,
      invoice_details,
      pi_amount,
      anchor_taxes,
      users,
      invoice_taxes,
      invoice_subtotal,
      invoice_total,
      purchase_order_total,

      selected_invoices,
      selected_invoices_taxes,

      // Search Params
      vendor_search,
      invoice_number_search,
      status_search,
      financing_status_search,
      from_date_search,
      to_date_search,

      bulk_action,

      invoice_fees_processing,

      rejected_reason,

      can_bulk_approve,

      purchase_order_details,

      invoice_details_key,

      showInvoice,

      calculatePayable,

      filter,

      updateSelected,
      pendingSelectAll,
      bulkAction,

      resolveStatus,
      resolveFinancingStatus,
      changePage,
      submitApproval,
      submitBulkApproval,
      submitBulkRejection,
      updateInvoiceTotal,
      getTaxPercentage,
      showPurchaseOrder,
      clearSearch,
      exportInvoices,
      submitRejection,
      download,
      editFees,
      approveFees,
      userHasApproved,
      approvalDetails
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
