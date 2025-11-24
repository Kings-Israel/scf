<template>
  <div class="">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <h5>{{ $t('Payment Requests List') }}(PR)</h5>
    </div>
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <form @submit.prevent="filter">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Debit From" class="form-label">{{ $t('Debit From') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Debit From"
              aria-describedby="defaultFormControlHelp"
              v-model="debit_from"
            />
          </div>
          <div class="">
            <label for="Company Name" class="form-label">{{ $t('Company Name') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Company Name"
              aria-describedby="defaultFormControlHelp"
              v-model="company_name"
            />
          </div>
          <div class="">
            <label for="Invoice No." class="form-label">{{ $t('Invoice No') }}.</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Invoice No."
              aria-describedby="defaultFormControlHelp"
              v-model="invoice_number_search"
            />
          </div>
          <div class="">
            <label for="PI Number" class="form-label">{{ $t('PI Number') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="PI Number"
              aria-describedby="defaultFormControlHelp"
              v-model="pi_number_search"
            />
          </div>
          <div class="">
            <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Pay Date') }})</label>
            <input
              type="text"
              id="date_search"
              class="form-control form-search"
              name="daterange"
              placeholder="Select Dates"
              autocomplete="off"
            />
          </div>
          <!-- <div class="mx-1">
            <label for="From Date" class="form-label mx-1">{{ $t('From Date') }} ({{ $t('Pay Date') }})</label>
            <input class="form-control form-search" type="date" id="" v-model="from_date" />
          </div>
          <div class="mx-1">
            <label for="To Date" class="form-label mx-1">{{ $t('To Date') }} ({{ $t('Pay Date') }})</label>
            <input class="form-control form-search" type="date" id="" v-model="to_date" />
          </div> -->
          <div class="">
            <label for="Status" class="form-label">{{ $t('Status') }}</label>
            <select class="select2 form-select form-search" id="status-select" multiple v-model="status">
              <option value="">{{ $t('Status') }}</option>
              <option value="approved">{{ $t('Created') }}</option>
              <option value="paid">{{ $t('Paid') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Product Type" class="form-label">{{ $t('Product Type') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type_search">
              <option value="">{{ $t('Product Type') }}</option>
              <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
              <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
              <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
              <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Sort by" class="form-label">{{ $t('Sort By') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="sort_by">
              <option value="">{{ $t('Sort By') }}</option>
              <option value="pi_no_asc">{{ $t('PI No') }}. ({{ $t('Asc') }})</option>
              <option value="pi_no_desc">{{ $t('PI No') }}. ({{ $t('Desc') }})</option>
              <option value="debit_from_asc">{{ $t('Debit From') }} ({{ $t('Asc') }})</option>
              <option value="debit_from_desc">{{ $t('Debit From') }}. ({{ $t('Desc') }})</option>
              <option value="id_asc">{{ $t('CBS Payment ID') }} ({{ $t('Asc') }})</option>
              <option value="id_desc">{{ $t('CBS Payment ID') }} ({{ $t('Desc') }})</option>
              <option value="amount_asc">{{ $t('Amount') }} ({{ $t('Asc') }})</option>
              <option value="amount_desc">{{ $t('Amount') }} ({{ $t('Desc') }})</option>
            </select>
          </div>
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" type="submit"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" type="button" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
      </form>
      <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <button
            type="button"
            class="btn btn-primary"
            @click="exprotPaymentRequests"
            style="height: fit-content"
            title="Download Excel"
          >
            <span class="d-flex">
              <i class="ti ti-download ti-xs px-1"></i>
              {{ $t('Excel') }}
            </span>
          </button>
        </div>
      </div>
    </div>
    <pagination
      v-if="requests.meta"
      class="mx-2"
      :from="requests.meta.from"
      :to="requests.meta.to"
      :links="requests.meta.links"
      :next_page="requests.links.next"
      :prev_page="requests.links.prev"
      :total_items="requests.meta.total"
      :first_page_url="requests.links.first"
      :last_page_url="requests.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>{{ $t('PR ID') }}</th>
            <th>{{ $t('Debit From') }}</th>
            <th>{{ $t('Credit To') }}</th>
            <th>{{ $t('Company Name') }}</th>
            <th>{{ $t('Product Code') }}</th>
            <th>{{ $t('Invoice/Unique Ref No') }}.</th>
            <th>{{ $t('PO No') }}.</th>
            <th>{{ $t('Amount') }}</th>
            <th>{{ $t('PI No') }}.</th>
            <th>{{ $t('Pay Date') }}</th>
            <th>{{ $t('Paid Date') }}</th>
            <th>{{ $t('Status') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!requests.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="requests.data && requests.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment_request in requests.data" :key="payment_request.id">
            <td>
              {{ payment_request.pr_id }}
            </td>
            <td>
              {{ payment_request.debit_from_account }}
            </td>
            <td
              class="text-primary text-decoration-underline"
              data-bs-toggle="modal"
              :data-bs-target="'#payment-accounts-' + payment_request.id"
              style="cursor: pointer"
            >
              {{ payment_request.total_accounts }} {{ $t('Account') }}(s)
            </td>
            <td class="text-primary text-decoration-underline">
              <a
                v-if="payment_request.can_view_company"
                href="#"
                @click="
                  showCompany(
                    payment_request.company_id,
                    payment_request.program_vendor_configuration_id,
                    'show-vendor-details-btn-' + payment_request.company_id
                  )
                "
              >
                {{ payment_request.company_name }}
              </a>
              <span v-else>{{ payment_request.company_name }}</span>
            </td>
            <td>{{ payment_request.program_type }}</td>
            <td class="">{{ payment_request.invoice_number }}</td>
            <td
              v-if="payment_request.purchase_order_number"
              class="text-primary text-decoration-underline"
              data-bs-toggle="modal"
              :data-bs-target="'#purchase-order-' + payment_request.purchase_order_id"
              style="cursor: pointer"
            >
              {{ payment_request.purchase_order_number }}
            </td>
            <td v-else>-</td>
            <td class="text-success text-nowrap">
              {{ new Intl.NumberFormat().format(payment_request.payment_request_amount) }}
            </td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(payment_request.invoice_id, 'show-pi-btn-' + payment_request.invoice_id)"
              style="cursor: pointer"
            >
              {{ payment_request.pi_number }}
            </td>
            <td class="">{{ moment(payment_request.payment_request_date).format(date_format) }}</td>
            <td class="">
              <span v-if="payment_request.pay_date">{{ moment(payment_request.pay_date).format(date_format) }}</span>
            </td>
            <td>
              <span
                v-if="payment_request.status == 'approved'"
                class="badge me-1 m_title"
                :class="resolvePaymentRequestStatus(payment_request.status)"
                >{{ 'Created' }}</span
              >
              <span v-else class="badge me-1 m_title" :class="resolvePaymentRequestStatus(payment_request.status)">{{
                payment_request.status
              }}</span>
              <button
                class="d-none"
                :id="'show-details-btn-' + payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                :id="'show-pi-btn-' + payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                :id="'show-vendor-details-btn-' + payment_request.company_id"
                data-bs-toggle="modal"
                :data-bs-target="'#company-details'"
              ></button>
              <button
                class="d-none"
                id="loading-modal-btn"
                data-bs-toggle="modal"
                data-bs-target="#loading-modal"
              ></button>
              <div class="modal fade" id="loading-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                  <div class="modal-content">
                    <div class="modal-body">
                      <div class="d-flex justify-content-center">
                        <span>{{ $t('Loading') }}...</span>
                        <img src="../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
              <payment-instruction
                v-if="invoice_details"
                :invoice-details="invoice_details"
                :key="invoice_details_key"
              />
              <div class="modal fade" :id="'payment-request-' + payment_request.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Payment Request') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="updateStatus(payment_request, 'rejected')" method="post">
                      <div class="modal-body">
                        <div class="row mb-1">
                          <div class="form-group">
                            <label for="">{{ $t('Rejection Reason') }}</label>
                            <textarea
                              type="text"
                              class="form-control"
                              v-model="updateRequestRejectionReason"
                            ></textarea>
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
              <div class="modal fade" :id="'payment-accounts-' + payment_request.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Accounts') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="table-responsive">
                        <table class="table">
                          <thead>
                            <tr>
                              <th>{{ $t('Account') }}</th>
                              <th>{{ $t('Amount') }}</th>
                              <th>{{ $t('Description') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="account in payment_request.payment_accounts" :key="account.id">
                              <td v-if="account.type == 'Vendor Account'">{{ account.account }}</td>
                              <td v-if="account.type == 'Vendor Account'" class="text-success">
                                {{ new Intl.NumberFormat().format(account.amount) }}
                              </td>
                              <td v-if="account.type == 'Vendor Account'">
                                <span v-if="account.type == 'Vendor Account'">{{ $t("Vendor's/Seller's A/C") }}</span>
                              </td>
                            </tr>
                            <tr v-for="account in payment_request.payment_accounts" :key="account.id">
                              <td v-if="account.can_show">{{ account.account }}</td>
                              <td v-if="account.can_show" class="text-success">
                                {{ new Intl.NumberFormat().format(account.amount) }}
                              </td>
                              <td v-if="account.can_show">
                                <span v-if="account.description">{{ account.description }}</span>
                                <span v-else-if="account.type == 'Discount'">{{ $t('Bank Discount A/C') }}</span>
                                <span v-else-if="account.type == 'Program Fees'">{{
                                  $t('Program Fees (Bank A/C)')
                                }}</span>
                                <span v-else-if="account.type == 'Program Fees Taxes'">{{
                                  $t('Program Fees Taxes (Bank A/C)')
                                }}</span>
                                <span v-else-if="account.type == 'Tax On Discount'">{{
                                  $t('Tax on Discount (Bank A/C)')
                                }}</span>
                                <span v-else-if="account.type == 'Tax On Fees'">{{
                                  $t('Tax on Fees (Bank A/C)')
                                }}</span>
                                <!-- <span v-else-if="account.description">{{ account.description }}</span> -->
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div
                v-if="purchase_order_details"
                class="modal fade"
                :id="'purchase-order-' + purchase_order_details.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Purchase Order') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex">
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
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Buyer') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ purchase_order_details.anchor.name }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ purchase_order_details.delivery_address }}
                            </h6>
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
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ purchase_order_details.rejection_reason }}
                            </h6>
                          </span>
                        </div>
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO No') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ purchase_order_details.purchase_order_number }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Start Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(purchase_order_details.duration_from).format(date_format) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('End Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(purchase_order_details.duration_to).format(date_format) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">
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
              <div v-if="company_details" class="modal fade" :id="'company-details'" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Company Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Name') }}:</div>
                          <div class="">
                            {{ company_details.name }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('KRA PIN') }}:</div>
                          <div class="">
                            {{ company_details.kra_pin }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Unique Identification No.') }}:</div>
                          <div class="">
                            {{ company_details.unique_identification_number }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Business Identification No.') }}:</div>
                          <div class="">
                            {{ company_details.business_identification_number }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Type') }}:</div>
                          <div class="">
                            {{ company_details.organization_type }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Branch Code') }}:</div>
                          <div class="">
                            {{ company_details.branch_code }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('City') }}:</div>
                          <div class="">
                            {{ company_details.city }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Postal Code') }}:</div>
                          <div class="">
                            {{ company_details.postal_code }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Address') }}:</div>
                          <div class="">
                            {{ company_details.address }}
                          </div>
                        </div>
                        <div class="col-12 col-md-5 d-flex">
                          <div class="">{{ $t('Customer Type') }}:</div>
                          <div class="">
                            {{ company_details.customer_type }}
                          </div>
                        </div>
                      </div>
                      <br />
                      <h5>{{ $t('Relationship Managers') }}</h5>
                      <div
                        class="row my-1"
                        v-for="relationship_manager in company_details.relationship_managers"
                        :key="relationship_manager.id"
                      >
                        <div class="col-12 col-md-4">
                          <div class="d-flex">
                            <div class="">{{ $t('Name') }}:</div>
                            <div class="">
                              {{ relationship_manager.name }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-4">
                          <div class="d-flex">
                            <div class="">{{ $t('Email') }}:</div>
                            <div class="">
                              {{ relationship_manager.email }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-4">
                          <div class="d-flex">
                            <div class="">{{ $t('Phone') }}:</div>
                            <div class="">
                              {{ relationship_manager.phone_number }}
                            </div>
                          </div>
                        </div>
                      </div>
                      <br />
                      <h5>{{ $t('Program Details') }}</h5>
                      <div class="row">
                        <div class="col-12 col-md-6">
                          <div class="d-flex">
                            <div class="">{{ $t('Program Name') }}:</div>
                            <div class="">
                              {{ program_details.program.name }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="d-flex">
                            <div class="">{{ $t('Program Type') }}:</div>
                            <div class="">
                              {{ program_details.program.program_type.name }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="d-flex">
                            <div class="">{{ $t('Sanctioned Limit') }}:</div>
                            <div class="">
                              {{ new Intl.NumberFormat().format(program_details.sanctioned_limit) }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="d-flex">
                            <div class="">{{ $t('Utilized Limit') }}:</div>
                            <div class="">
                              {{ new Intl.NumberFormat().format(program_details.utilized_amount) }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="d-flex">
                            <div class="">{{ $t('Pipeline Limit') }}:</div>
                            <div class="">
                              {{ new Intl.NumberFormat().format(program_details.pipeline_amount) }}
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="d-flex">
                            <div class="">{{ $t('Available Limit') }}:</div>
                            <div class="">
                              {{
                                new Intl.NumberFormat().format(
                                  program_details.sanctioned_limit -
                                    (program_details.utilized_amount + program_details.pipeline_amount)
                                )
                              }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                        {{ $t(' Close') }}
                      </button>
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
      v-if="requests.meta"
      class="mx-2"
      :from="requests.meta.from"
      :to="requests.meta.to"
      :links="requests.meta.links"
      :next_page="requests.links.next"
      :prev_page="requests.links.prev"
      :total_items="requests.meta.total"
      :first_page_url="requests.links.first"
      :last_page_url="requests.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { computed, inject, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from './partials/Pagination.vue';
// import '../../../public/assets/vendor/libs/select2/select2.js'
import 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js';
import '../../../public/assets/vendor/libs/select2/select2.css';
import '../../../public/assets/js/forms-selects.js';
import InvoiceDetails from '../InvoiceDetails.vue';
import PaymentInstruction from '../PaymentInstruction.vue';

export default {
  name: 'PaymentRequests',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  props: ['bank', 'date_format'],
  setup(props, context) {
    const date_format = props.date_format;
    const toast = useToast();
    const base_url = inject('baseURL');
    const requests = ref([]);
    const updateRequestStatus = ref('');
    const updateRequestRejectionReason = ref('');

    const per_page = ref(50);

    // Filters
    const pi_number_search = ref('');
    const debit_from = ref('');
    const company_name = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const status = ref([]);
    const sort_by = ref('');
    const invoice_number_search = ref('');
    const pay_date_search = ref('');
    const transaction_type = ref('');
    const product_type_search = ref('');
    const date_search = ref('');

    const invoice_details = ref(null);
    const invoice_details_key = ref(0);
    const purchase_order_details = ref(null);

    const company_details = ref(null);
    const program_details = ref(null);

    const getRequests = async () => {
      await axios
        .get(base_url + props.bank + '/requests/payment-requests/data', {
          params: {
            per_page: per_page.value,
            pi_number: pi_number_search.value,
            debit_from: debit_from.value,
            company_name: company_name.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status.value,
            sort_by: sort_by.value,
            invoice_number: invoice_number_search.value,
            pay_date: pay_date_search.value,
            transaction_type: transaction_type.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
        })
        .catch(err => {
          console.log(err);
        });
    };

    context.expose({ getRequests });

    onMounted(() => {
      // Get values of status filter
      $('#status-select').on('change', function () {
        var ids = $('#status-select').val();
        status.value = ids;
      });
    });

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return Math.round((tax_amount / invoice_amount) * 100);
    };

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'created':
          style = 'bg-label-primary';
          break;
        case 'approved':
          style = 'bg-label-primary';
          break;
        case 'paid':
          style = 'bg-label-success';
          break;
        case 'failed':
          style = 'bg-label-danger';
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
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'sent':
          style = 'bg-label-secondary';
          break;
        case 'approved':
          style = 'bg-label-success';
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

    const getTotalAmount = invoice_items => {
      let amount = 0;
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity;
      });
      return amount;
    };

    const getTaxesAmount = invoice_taxes => {
      let amount = 0;
      invoice_taxes.forEach(item => {
        amount += item.value;
      });
      return amount;
    };

    const getFeesAmount = invoice_fees => {
      let amount = 0;
      invoice_fees.forEach(item => {
        amount += item.amount;
      });
      return amount;
    };

    const resolveFinancingStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'financed':
          style = 'bg-label-primary';
          break;
        case 'disbursed':
          style = 'bg-label-primary';
          break;
        case 'closed':
          style = 'bg-label-success';
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

    const filter = async () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/requests/payment-requests/data', {
          params: {
            per_page: per_page.value,
            pi_number: pi_number_search.value,
            debit_from: debit_from.value,
            company_name: company_name.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status.value,
            sort_by: sort_by.value,
            invoice_number: invoice_number_search.value,
            pay_date: pay_date_search.value,
            transaction_type: transaction_type.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      pi_number_search.value = '';
      debit_from.value = '';
      company_name.value = '';
      from_date.value = '';
      to_date.value = '';
      status.value = [];
      sort_by.value = '';
      invoice_number_search.value = '';
      pay_date_search.value = '';
      transaction_type.value = '';
      product_type_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      $('#status-select').val('').trigger('change');
      await axios
        .get(base_url + props.bank + '/requests/payment-requests/data', {
          params: {
            per_page: per_page.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const exprotPaymentRequests = () => {
      let parent = $('.ti-download').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/requests/payment-requests/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            debit_from: debit_from.value,
            company_name: company_name.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status.value,
            sort_by: sort_by.value,
            invoice_number: invoice_number_search.value,
            pay_date: pay_date_search.value,
            transaction_type: transaction_type.value,
            product_type: product_type_search.value,
            pi_number: pi_number_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payment_requests_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          if (err.response.status == 422) {
            toast.error('You can only export 3 months of data at a time. Kindly select a date range.', {
              timeout: 12000
            });
          } else {
            toast.error('Something went wrong while exporting the data');
          }
        })
        .finally(() => {
          parent.html('<i class="ti ti-download"></i> Excel');
        });
    };

    const changePage = async page => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            debit_from: debit_from.value,
            company_name: company_name.value,
            from_date: from_date.value,
            to_date: to_date.value,
            status: status.value,
            sort_by: sort_by.value,
            invoice_number: invoice_number_search.value,
            pay_date: pay_date_search.value,
            transaction_type: transaction_type.value,
            product_type: product_type_search.value,
            pi_number: pi_number_search.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/requests/invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          invoice_details_key.value++;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showPurchaseOrder = async (purchase_order, modal) => {
      await axios
        .get(base_url + props.bank + '/requests/invoices/purchase-order/' + purchase_order + '/details')
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

    const updateStatus = (payment_request, status) => {
      const formData = new FormData();
      formData.append('payment_request_id', payment_request.id);
      formData.append('status', status);
      formData.append('rejection_reason', updateRequestRejectionReason.value);
      axios
        .post(base_url + props.bank + '/requests/payment-requests/update', formData)
        .then(() => {
          toast.success('Payment Request updated');
          getRequests();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const showCompany = async (company, program, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/companies/' + company + '/details/' + program)
        .then(response => {
          company_details.value = response.data.company;
          program_details.value = response.data.program_vendor_configuration;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      requests,
      per_page,
      debit_from,
      company_name,
      from_date,
      to_date,
      status,
      sort_by,
      product_type_search,
      invoice_number_search,
      pay_date_search,
      transaction_type,
      pi_number_search,
      filter,
      refresh,
      resolvePaymentRequestStatus,
      resolveStatus,
      changePage,
      invoice_details,
      purchase_order_details,
      getTotalAmount,
      getTaxesAmount,
      getFeesAmount,
      resolveFinancingStatus,
      updateRequestStatus,
      updateRequestRejectionReason,
      updateStatus,
      getTaxPercentage,
      exprotPaymentRequests,
      showInvoice,
      showPurchaseOrder,
      date_format,
      company_details,
      program_details,
      showCompany,
      invoice_details_key
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
.line-clamp {
  text-overflow: ellipsis;
  /* Needed to make it work */
  overflow: hidden;
  white-space: nowrap;
}
.select2-hidden-accessible {
  min-width: 220px !important;
  max-width: 220px !important;
}
.select2-selection__rendered {
  min-width: 220px !important;
  max-width: 220px !important;
}
</style>
