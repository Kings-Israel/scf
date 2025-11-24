<template>
  <div class="card">
    <div class="card-header" v-if="!is_dashboard">
      <h6>{{ $t('Payment Instructions eligible for Financing (from YoFinvoice)') }}</h6>
    </div>
    <div class="card-body">
      <div class="d-flex justify-content-between" v-if="!is_dashboard">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Buyer" class="form-label">{{ $t('Buyer') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              v-model="buyer_search"
              placeholder="Buyer"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Invoice No." class="form-label">{{ $t('Invoice No') }}.</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              v-model="invoice_number_search"
              placeholder="Invoice No"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Due Date') }})</label>
            <input
              type="text"
              id="date_search"
              class="form-control form-search"
              name="daterange"
              placeholder="Select Dates"
              autocomplete="off"
            />
          </div>
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex mx-2" style="height: fit-content">
          <select class="form-select mx-2" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
      <pagination
        v-if="!is_dashboard && invoices.meta"
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
      <div class="table-responsive">
        <table class="table invoices-table">
          <thead>
            <tr class="">
              <th v-if="!is_dashboard && can_request">
                <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
              </th>
              <th v-if="!is_dashboard && can_request">
                <i
                  class="ti ti-coin ti-sm"
                  :class="selected_invoices.length > 0 ? 'text-success' : 'text-secondary'"
                  style="cursor: pointer"
                  @click="showMultipleRequestModal()"
                ></i>
              </th>
              <th>{{ $t('Invoice No') }}.</th>
              <th>{{ $t('Invoice Date') }}</th>
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('Due Date') }}</th>
              <th>{{ $t('PI Amount') }}</th>
              <th>{{ $t('Actual Remittance Amount') }}</th>
              <th v-if="can_request">{{ $t('Pay Me Early') }}</th>
            </tr>
          </thead>
          <tbody class="invoices-table-content">
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
            <tr v-for="invoice in invoices.data" :key="invoice.id">
              <td v-if="!is_dashboard">
                <div v-if="can_request && invoice.payment_requests <= 0" class="form-check">
                  <input
                    class="form-check-input border-primary"
                    type="checkbox"
                    :id="'invoice-' + invoice.id"
                    @change="updateSelected(invoice)"
                  />
                </div>
              </td>
              <td v-if="!is_dashboard && can_request"></td>
              <td>
                <a
                  href="javascript:;"
                  class="text-decoration-underline text-primary"
                  @click="showInvoice(invoice.id, 'show-planner-pi-' + invoice.id)"
                >
                  {{ invoice.invoice_number }}
                </a>
                <!-- Show Invoice Requesting Period -->
                <i
                  v-if="!invoice.can_request_today"
                  class="tf-icons ti ti-info-circle ti-xs text-secondary"
                  :title="invoice.financing_request_window"
                ></i>
              </td>
              <td class="">{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
              <td class="text-success">
                {{ invoice.currency }}
                {{
                  new Intl.NumberFormat().format(
                    invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount
                  )
                }}
              </td>
              <td class="">{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>
                  {{
                    new Intl.NumberFormat().format(
                      (
                        invoice.total +
                        invoice.total_invoice_taxes -
                        invoice.total_invoice_fees -
                        invoice.total_invoice_discount
                      ).toFixed(2)
                    )
                  }}
                </span>
              </td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>{{ new Intl.NumberFormat().format(invoice.actual_remittance_amount.toFixed(2)) }}</span>
              </td>
              <td v-if="can_request" class="text-success">
                <i
                  v-if="invoice.payment_requests <= 0"
                  class="ti ti-coin ti-sm"
                  style="cursor: pointer"
                  @click="showModal(invoice.id, 'show-request-financing-btn-' + invoice.id)"
                ></i>
                <button
                  class="d-none"
                  :id="'show-planner-pi-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#cash-planner-pi-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'show-request-financing-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#requestFinancing-' + invoice.id"
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
                          <img src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- <i v-if="!invoice.payment_request" class='ti ti-coin ti-sm' style="cursor: pointer" @click="showModal(invoice)"></i> -->
                <div
                  class="modal fade"
                  v-if="invoice_details"
                  :id="'requestFinancing-' + invoice.id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">
                          {{ $t('Request Finance for ' + invoice_details.invoice_number) }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <h5>{{ $t('Step 1') }}: {{ $t('Review Invoice Details') }}</h5>
                        <div class="row">
                          <div class="col-6">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Invoice Amount') }}</label>
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                <span>
                                  {{ new Intl.NumberFormat().format(invoice_details.total) }}
                                </span>
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('PI Amount') }}(A)</label>
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                <span>
                                  {{ new Intl.NumberFormat().format(invoice_total_amount) }}
                                </span>
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Eligible Amount') }}</label>
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                <span>
                                  {{ new Intl.NumberFormat().format(invoice_eligibility_amount) }}
                                </span>
                              </h6>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Due Date') }}</label>
                              <h6 class="" v-if="invoice_details">
                                {{ moment(invoice_due_date).format('DD MMM YYYY') }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Eligibility') }}</label>
                              <h6 class="" v-if="invoice_details">{{ invoice_eligibility_percentage }}%</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Eligible For Finance') }}</label>
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(invoice_eligibility_for_finance) }}
                              </h6>
                            </div>
                          </div>
                        </div>
                        <h5 class="mt-1">{{ $t('Step 2') }}: {{ $t('Select Early Payment Date') }}</h5>
                        <div class="row">
                          <div
                            class="col-6"
                            v-if="invoice_details.program.discount_details[0].discount_type == 'Front Ended'"
                          >
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Early Payment Date') }}</label>
                              <h6 class="">{{ payment_date }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Days to Payment') }}</label>
                              <h6 class="">{{ invoice_days_to_payment }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Discount Charge') }}(%)</label>
                              <h6 class="" v-if="invoice_details">{{ invoice_business_spread }}%</h6>
                            </div>
                            <div
                              class="border-bottom d-flex justify-content-between"
                              v-for="fee in vendor_fee_details"
                              :key="fee"
                            >
                              <label :for="fee[0]" class="form-label">{{ fee[0] }}</label>
                              <h6 class="text-success">{{ fee[1] }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Total Fees') }} <strong>(C)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(total_fees.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Tax On Fees ') }} <strong>(D)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(total_tax_on_fees.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Discount Amount') }} <strong>(E)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(Math.abs(invoice_discount_amount).toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Tax On Discount') }} <span class="text-success">{{ tax_on_discount }}%</span
                                ><strong>(F)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(tax_on_discount_amount.toFixed(2)) }}
                              </h6>
                            </div>
                          </div>
                          <div class="col-6" v-else>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Early Payment Date') }}</label>
                              <h6 class="">{{ payment_date }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Days to Payment') }}</label>
                              <h6 class="">{{ invoice_days_to_payment }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Discount Charge') }}</label>
                              <h6 class="" v-if="invoice_details">{{ invoice_business_spread }}%</h6>
                            </div>
                            <div
                              class="border-bottom d-flex justify-content-between"
                              v-for="fee in vendor_fee_details"
                              :key="fee"
                            >
                              <label :for="fee[0]" class="form-label">{{ fee[0] }}</label>
                              <h6 class="text-success">{{ fee[1] }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Total Fees') }} <strong>(C)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(total_fees.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Tax On Fees ') }} <strong>(D)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(total_tax_on_fees.toFixed(2)) }}
                              </h6>
                            </div>
                          </div>
                          <div class="col-6">
                            <flat-pickr
                              v-model="request_payment_date"
                              class="form-control"
                              @on-change="updateDiscount"
                              :config="config"
                            />
                            <!-- <DatePicker v-model="request_payment_date" inline showWeek class="w-full sm:w-[30rem]" @date-select="updateDiscount" :minDate="prime_min_date" :maxDate="prime_max_date" :disabledDates="prime_disabled_dates" :disabledDays="prime_disabled_days" /> -->
                          </div>
                        </div>
                        <h5 class="mt-1">{{ $t('Step 3') }}: {{ $t('Review Offer and Submit Request') }}</h5>
                        <div class="row">
                          <div class="col-12">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Early Payment Date') }}</label>
                              <h6 class="" id="payment_date">{{ payment_date }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Eligible Amount') }}</label>
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(invoice_eligibility_amount.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Request Amount') }} <strong>(B)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(invoice_eligibility_for_finance.toFixed(2)) }}
                              </h6>
                            </div>
                            <div
                              v-if="invoice_details.program.discount_details[0].discount_type == 'Front Ended'"
                              class="border-bottom d-flex justify-content-between"
                            >
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Actual Remittance Amount') }} <strong>(B-C-D-E-F)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(invoice_total_remmitance.toFixed(2)) }}
                              </h6>
                            </div>
                            <div v-else class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >{{ $t('Actual Remittance Amount') }} <strong>(B-C-D)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(invoice_total_remmitance.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">{{ $t('Credit To') }}</label>
                              <select
                                v-if="invoice_details"
                                class="form-select w-50 my-2"
                                :class="payment_account ? 'border-danger' : ''"
                                v-model="credit_to"
                                @change="credit_to != '' ? (can_submit = true) : (can_submit = false)"
                                id="account_number"
                                name="account_number"
                              >
                                <option value="">{{ $t('Select Payment Account') }}</option>
                                <option
                                  v-for="account in invoice_details.bank_details"
                                  :key="account.id"
                                  :value="account.id"
                                >
                                  {{ account.account_number }} - ({{ account.bank_name }})
                                </option>
                              </select>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                          {{ $t('Cancel') }}
                        </button>
                        <button
                          type="button"
                          class="btn btn-primary"
                          :disabled="!can_submit"
                          data-bs-toggle="modal"
                          :data-bs-target="'#terms-' + invoice.id"
                          @click="formatNoa(invoice_details)"
                        >
                          {{ $t('Submit') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal modal-top fade" :id="'terms-' + invoice.id" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Request Finance') }}</h5>
                        <button
                          type="button"
                          class="btn-close"
                          id="close-single-terms-and-conditions"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <h6 v-if="terms_text && terms_text.terms_conditions != ''">{{ $t('Terms and Conditions') }}</h6>
                        <div
                          class="border border-primary rounded-top rounded-bottom p-2"
                          v-if="terms_text && terms_text.terms_conditions != ''"
                          style="max-height: 250px; overflow-y: auto"
                        >
                          <span v-html="terms_text.terms_conditions" class="text-wrap"></span>
                        </div>
                        <a
                          :href="'../factoring/cash-planner/invoices/terms/vendor_financing/download'"
                          v-if="terms_text && terms_text.terms_conditions != ''"
                          target="_blank"
                          >{{ $t('Download Terms and Conditions') }}</a
                        >
                        <h6 v-if="noa_text && noa_text.body != ''">{{ $t('NOA') }}</h6>
                        <div
                          class="border border-primary rounded-top rounded-bottom p-2"
                          v-if="noa_text && noa_text.body != ''"
                          style="max-height: 250px; overflow-y: auto"
                        >
                          <span v-html="noa_text.body" class="text-wrap"></span>
                        </div>
                        <a
                          :href="'../factoring/cash-planner/invoices/' + invoice.id + '/noa/download'"
                          target="_blank"
                          v-if="noa_text && noa_text.body != ''"
                          >{{ $t('Download NOA') }}</a
                        >
                      </div>
                      <div class="modal-footer d-flex justify-content-between">
                        <small class="mt-4"
                          >{{ $t('By Submitting, you agree to the') }}
                          <a
                            :href="'' + terms_and_conditions_link + ''"
                            target="_blank"
                            class="text-primary"
                            style="cursor: pointer"
                            >{{ $t('Terms and Conditions') }}</a
                          ></small
                        >
                        <div>
                          <button type="button" class="btn btn-label-secondary mx-2" data-bs-dismiss="modal">
                            {{ $t('Cancel') }}
                          </button>
                          <button type="button" class="btn btn-primary" @click="requestFinance" :disabled="!can_submit">
                            {{ $t('Submit') }}
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'cash-planner-invoice-' + invoice.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex gap-1">
                        <div>
                          <button
                            class="btn btn-primary"
                            type="button"
                            data-bs-toggle="modal"
                            :data-bs-target="'#add-attachment-' + invoice_details.id"
                          >
                            {{ $t('Attachment') }}
                          </button>
                        </div>
                        <a :href="'invoices/' + invoice_details.id + '/pdf/download'" class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex justify-content-between mb-4">
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{
                                invoice_details.buyer ? invoice_details.buyer.name : invoice_details.program.anchor.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.delivery_address }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Debit From') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.bank_details[0].account_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{
                                invoice_details.buyer
                                  ? invoice_details.program.anchor.name
                                  : invoice_details.company.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.remarks }}</h6>
                          </span>
                          <span class="d-flex justify-content-between" v-if="invoice_details.credit_to">
                            <h5 class="fw-light my-auto">{{ $t('Credit To') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.credit_to }}</h6>
                          </span>
                          <span class="d-flex justify-content-between" v-if="invoice_details.rejected_reason">
                            <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.rejected_reason }}</h6>
                          </span>
                        </div>
                        <div class="mb-3">
                          <span v-if="invoice_details.pi_number" class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PI No') }}:</h5>
                            <h6
                              class="fw-bold mx-2 my-auto text-decoration-underline text-primary pointer"
                              data-bs-toggle="modal"
                              :data-bs-target="'#cash-planner-pi-' + invoice_details.id"
                            >
                              {{ invoice_details.pi_number }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice No.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.invoice_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO No.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{
                                invoice_details.purchase_order
                                  ? invoice_details.purchase_order.purchase_order_number
                                  : ''
                              }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Payment / OD Account No.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ invoice_details.vendor_configurations.payment_account_number }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Date.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(invoice_details.invoice_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Status.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.approval_stage }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Due Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(invoice_details.due_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table">
                          <thead class="bg-label-primary">
                            <tr>
                              <th>{{ $t('Item') }}</th>
                              <th>{{ $t('Quantity') }}</th>
                              <th>{{ $t('Price Per Quantity') }}</th>
                              <th>{{ $t('Total') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in invoice_details.invoice_items" :key="item.id">
                              <td>{{ item.item }}</td>
                              <td>{{ item.quantity }}</td>
                              <td>{{ item.price_per_quantity }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Discount') }}:</h6>
                          <h5 class="text-success my-auto">
                            {{ new Intl.NumberFormat().format(invoice_details.total_invoice_discount) }}
                          </h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_taxes.length" class="px-2">
                        <span
                          v-for="tax in invoice_details.invoice_taxes"
                          :key="tax.id"
                          class="d-flex justify-content-end"
                        >
                          <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
                          <h5 class="text-success my-auto">{{ new Intl.NumberFormat().format(tax.value) }}</h5>
                        </span>
                      </div>
                      <div v-else class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Tax') }}</h6>
                          <h5 class="text-success my-auto">0.0</h5>
                        </span>
                      </div>
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                          <h5 class="text-success my-auto py-1">
                            {{ invoice_details.currency }}
                            {{
                              new Intl.NumberFormat().format(
                                invoice_details.total +
                                  invoice_details.total_invoice_taxes -
                                  invoice_details.total_invoice_discount
                              )
                            }}
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
                :id="'cash-planner-pi-' + invoice.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex gap-1">
                        <div>
                          <button
                            class="btn btn-primary"
                            type="button"
                            data-bs-toggle="modal"
                            :data-bs-target="'#add-attachment-' + invoice_details.id"
                          >
                            {{ $t('Attachment') }}
                          </button>
                        </div>
                        <a
                          :href="'invoices/payment-instruction/' + invoice_details.id + '/pdf/download'"
                          class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex justify-content-between mb-4">
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{
                                invoice_details.buyer ? invoice_details.buyer.name : invoice_details.program.anchor.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.delivery_address }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Debit From') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.bank_details[0].account_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{
                                invoice_details.buyer
                                  ? invoice_details.program.anchor.name
                                  : invoice_details.company.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.remarks }}</h6>
                          </span>
                          <span class="d-flex justify-content-between" v-if="invoice_details.credit_to">
                            <h5 class="fw-light my-auto">{{ $t('Credit To') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.credit_to }}</h6>
                          </span>
                          <span class="d-flex justify-content-between" v-if="invoice_details.rejected_reason">
                            <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.rejected_reason }}</h6>
                          </span>
                        </div>
                        <div class="mb-3">
                          <span v-if="invoice_details.pi_number" class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice No.') }}:</h5>
                            <h6
                              class="fw-bold mx-2 my-auto text-decoration-underline text-primary pointer"
                              data-bs-toggle="modal"
                              :data-bs-target="'#cash-planner-invoice-' + invoice_details.id"
                            >
                              {{ invoice_details.invoice_number }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PI No.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.pi_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO No.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{
                                invoice_details.purchase_order
                                  ? invoice_details.purchase_order.purchase_order_number
                                  : ''
                              }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Payment / OD Account No.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ invoice_details.vendor_configurations.payment_account_number }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Date.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(invoice_details.invoice_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Status.') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.approval_stage }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Due Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(invoice_details.due_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table">
                          <thead class="bg-label-primary">
                            <tr>
                              <th>{{ $t('Item') }}</th>
                              <th>{{ $t('Quantity') }}</th>
                              <th>{{ $t('Price Per Quantity') }}</th>
                              <th>{{ $t('Total') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in invoice_details.invoice_items" :key="item.id">
                              <td>{{ item.item }}</td>
                              <td>{{ item.quantity }}</td>
                              <td>{{ item.price_per_quantity }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                            </tr>
                          </tbody>
                        </table>
                        <div class="px-2">
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto">{{ $t('Discount') }}:</h6>
                            <h5 class="text-success my-auto">
                              {{ new Intl.NumberFormat().format(invoice_details.total_invoice_discount) }}
                            </h5>
                          </span>
                        </div>
                        <div v-if="invoice_details.invoice_taxes.length" class="px-2">
                          <span
                            v-for="tax in invoice_details.invoice_taxes"
                            :key="tax.id"
                            class="d-flex justify-content-end"
                          >
                            <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
                            <h5 class="text-success my-auto">{{ new Intl.NumberFormat().format(tax.value) }}</h5>
                          </span>
                        </div>
                        <div v-else class="px-2">
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto">{{ $t('Tax') }}</h6>
                            <h5 class="text-success my-auto">0.0</h5>
                          </span>
                        </div>
                        <div v-if="invoice_details.invoice_fees.length > 0" class="px-2">
                          <span
                            v-for="fee in invoice_details.invoice_fees"
                            :key="fee.id"
                            class="d-flex justify-content-end"
                          >
                            <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                            <h5
                              class="text-success my-auto py-1"
                              v-if="
                                fee.name != 'Credit Note Amount' &&
                                fee.name != 'Credit Note' &&
                                fee.name != 'Credit Amount'
                              "
                            >
                              ({{
                                getTaxPercentage(
                                  invoice_details.total +
                                    invoice_details.total_invoice_taxes -
                                    invoice_details.total_invoice_discount,
                                  fee.amount
                                )
                              }}%) {{ new Intl.NumberFormat().format(fee.amount.toFixed(2)) }}
                            </h5>
                            <h5 class="text-success my-auto py-1" v-else>{{ fee.amount }}</h5>
                          </span>
                        </div>
                        <div v-else class="px-2">
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto py-1">{{ $t('WHT Tax') }}:</h6>
                            <h5 class="text-success my-auto py-1">(0.0%) 0</h5>
                          </span>
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto py-1">{{ $t('WHT VAT') }}:</h6>
                            <h5 class="text-success my-auto py-1">(0.0%) 0</h5>
                          </span>
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto py-1">{{ $t('Credit Note Amount') }}:</h6>
                            <h5 class="text-success my-auto py-1">0</h5>
                          </span>
                        </div>
                        <div class="bg-label-secondary px-2">
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                            <h5 class="text-success my-auto py-1">
                              {{ new Intl.NumberFormat().format(invoice_details.invoice_total_amount) }}
                            </h5>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <upload-attachment v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
            </tr>
          </tbody>
          <tfoot class="text-nowrap" v-if="!is_dashboard">
            <tr>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td>
                {{ $t('Total') }}
                <span class="text-success">{{ new Intl.NumberFormat().format(total_invoice_amount.toFixed(2)) }}</span>
              </td>
              <td></td>
              <td>
                {{ $t('Total') }}
                <span class="text-success">{{ new Intl.NumberFormat().format(total_pi_amount.toFixed(2)) }}</span>
              </td>
              <td>
                {{ $t('Total') }}
                <span class="text-success">{{
                  new Intl.NumberFormat().format(total_actual_remittance.toFixed(2))
                }}</span>
              </td>
              <td></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <button
        class="d-none"
        id="show-multiple-request-modal"
        ref="show_multiple_request_modal"
        data-bs-toggle="modal"
        data-bs-target="#multipleRequestFinancing"
      ></button>
      <div class="modal fade" id="multipleRequestFinancing" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalCenterTitle">{{ $t('Request Finance') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="d-flex justify-content-between">
                <span>{{ $t('Total Amount') }}</span>
                <span v-if="!bulk_data_loading">{{
                  new Intl.NumberFormat().format(selected_invoices_total_amount)
                }}</span>
                <img v-else src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
              </div>
              <div class="d-flex justify-content-between my-1">
                <span>{{ $t('Actual Remittance') }}</span>
                <span v-if="!bulk_data_loading">{{
                  new Intl.NumberFormat().format(bulk_invoice_remittance.toFixed(2))
                }}</span>
                <img v-else src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
              </div>
              <label for="Request Date" class="form-label">{{ $t('I want payment on') }}:</label>
              <flat-pickr
                v-model="bulk_request_payment_date"
                class="form-control"
                @on-change="updateBulkDiscount"
                :config="config"
              />
              <!-- <div class="form-group">
                <DatePicker v-once v-model="bulk_request_payment_date" inline showWeek class="w-full" @date-select="updateBulkDiscount" :minDate="prime_min_date" :maxDate="prime_max_date" :disabledDates="prime_disabled_dates" :disabledDays="prime_disabled_days" />
              </div> -->
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Cancel') }}</button>
              <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#terms"
                @click="can_submit = true"
              >
                {{ $t('Submit') }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal modal-top fade" id="terms" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalCenterTitle">{{ $t('Request Finance') }}</h5>
              <button
                type="button"
                class="btn-close"
                id="close-bulk-terms-and-conditions"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body">
              <h6 v-if="terms_text && terms_text.terms_conditions != ''">{{ $t('Terms and Conditions') }}</h6>
              <div
                class="border border-primary rounded-top rounded-bottom p-2"
                v-if="terms_text && terms_text.terms_conditions != ''"
                style="max-height: 250px; overflow-y: auto"
              >
                <span v-html="terms_text.terms_conditions" class="text-wrap"></span>
              </div>
              <a
                :href="'../factoring/cash-planner/invoices/terms/vendor_financing/download'"
                v-if="terms_text && terms_text.terms_conditions != ''"
                target="_blank"
                >{{ $t('Download Terms and Conditions') }}</a
              >
            </div>
            <div class="modal-footer d-flex justify-content-between">
              <small class="mt-4"
                >{{ $t('By Submitting, you agree to the') }}
                <a
                  :href="terms_text ? 'factoring/cash-planner/invoices/terms/vendor_financing/download' : '#'"
                  target="_blank"
                  class="text-primary"
                  style="cursor: pointer"
                  >{{ $t('Terms and Conditions') }}</a
                ></small
              >
              <div>
                <button type="button" class="btn btn-label-secondary mx-2" data-bs-dismiss="modal">Cancel</button>
                <button
                  type="button"
                  class="btn btn-primary"
                  id="accept-bulk-terms"
                  @click="requestFinanceForMultiple"
                  :disabled="!can_submit"
                >
                  {{ $t('Accept') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <button
        class="d-none"
        id="successful-request-for-finance"
        data-bs-toggle="modal"
        data-bs-target="#payment-request-successful"
      ></button>
      <div class="modal fade" id="payment-request-successful" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalCenterTitle">{{ $t('Request for Finance successful') }}</h5>
            </div>
            <div class="modal-body">
              <img
                src="../../../../../public/assets/img/successful-request.png"
                style="width: 32rem"
                alt="Successfully Requested"
              />
              <h4 class="fw-bold">{{ $t('Your Payment Request(s) have been submitted successfully') }}!</h4>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close') }}</button>
            </div>
          </div>
        </div>
      </div>
      <pagination
        v-if="!is_dashboard && invoices.meta"
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
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from '../partials/Pagination.vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import DatePicker from 'primevue/datepicker';
import UploadAttachment from '../../../UploadAttachment.vue';

export default {
  name: 'EligibleInvoices',
  components: {
    Pagination,
    flatPickr,
    DatePicker,
    UploadAttachment
  },
  props: ['can_request', 'is_dashboard'],
  setup(props) {
    const can_request = props.can_request;
    const is_dashboard = props.is_dashboard ?? false;
    const toast = useToast();
    const invoices = ref([]);
    const base_url = inject('baseURL');
    const invoice_details = ref(null);
    const eligibility_amount = ref(0);
    const discount_amount = ref(0);
    // const min_date = ref(new Date().toLocaleDateString('en-CA'));
    const min_date = ref('');
    const max_date = ref('');
    const bulk_max_date = ref('');
    const payment_date = ref(moment().format('DD MMM YYYY'));
    const bulk_payment_date = ref(moment().format('DD MMM YYYY'));
    const request_payment_date = ref(new Date().toLocaleDateString('en-CA'));
    const bulk_request_payment_date = ref(new Date().toLocaleDateString('en-CA'));
    const processing_fees = ref(1);
    const processing_fee_amount = ref(0);
    const days_to_payment = ref(1);
    const total_remittance = ref(0);
    const invoice_total_amount = ref(0);
    const invoice_taxes_amount = ref(0);
    const invoice_fees_amount = ref(0);
    const invoice_discounts_amount = ref(0);
    const invoice_pi_amount = ref(0);
    const invoice_eligibility_percentage = ref(0);
    const invoice_eligibility_amount = ref(0);
    const invoice_eligibility_for_finance = ref(0);
    const invoice_discount_amount = ref(0);
    const invoice_days_to_payment = ref(0);
    const invoice_business_spread = ref(0);
    const invoice_processing_fee = ref(0);
    const invoice_total_remmitance = ref(0);
    const invoice_min_financing_days = ref(0);
    const invoice_max_financing_days = ref(0);
    const invoice_due_date = ref(0);
    const payment_accounts = ref([]);
    const eligibility = ref(0);
    const pi_amount = ref(0);
    const taxes_amount = ref(0);
    const business_spread = ref(0);
    const credit_to = ref('');
    const payment_account = ref(false);
    const viewRequestDetails = ref(false);

    const total_invoice_amount = ref(0);
    const total_pi_amount = ref(0);
    const total_eligible_amount = ref(0);
    const total_actual_remittance = ref(0);

    const can_submit = ref(false);

    const selected_invoices = ref([]);

    const show_multiple_request_modal = ref(false);

    const show_terms_button = ref('');

    const terms_and_conditions_link = ref('');

    const noa_link = ref('');

    const noa_text = ref('');

    const terms_text = ref('');

    const tax_on_discount = ref(0);

    const tax_on_discount_amount = ref(0);

    const selected_invoices_total_amount = ref(0);

    const bulk_invoice_remittance = ref(0);

    const config = ref({
      inline: true
    });

    const config2 = ref({
      inline: true
    });

    const vendor_fee_details = ref([]);

    const total_fees = ref(0);

    const total_tax_on_fees = ref(0);

    // Search Fields
    const invoice_number_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const date_search = ref('');
    const buyer_search = ref('');

    const bank_holidays = ref([]);
    const offdays = ref([]);

    const per_page = ref(is_dashboard ? 10 : 50);

    const bulk_data_loading = ref(false);

    const prime_disabled_dates = ref([]);
    const prime_disabled_days = ref([]);
    const prime_max_date = ref(null);
    const prime_min_date = ref(new Date());

    const invoice_details_key = ref(0);

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'factoring/cash-planner/invoices/eligible/data', {
          params: {
            per_page: per_page
          }
        })
        .then(response => {
          total_invoice_amount.value = 0;
          total_pi_amount.value = 0;
          total_eligible_amount.value = 0;
          total_actual_remittance.value = 0;
          invoices.value = response.data.invoices;
          invoices.value.data.forEach(invoice => {
            total_invoice_amount.value += invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount;
            total_pi_amount.value +=
              invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_fees - invoice.total_invoice_discount;
            total_eligible_amount.value += invoice.eligible_for_finance;
            total_actual_remittance.value += invoice.actual_remittance_amount;
          });
        });
    });

    const filter = async () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'factoring/cash-planner/invoices/eligible/data', {
          params: {
            buyer: buyer_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          total_invoice_amount.value = 0;
          total_pi_amount.value = 0;
          total_eligible_amount.value = 0;
          total_actual_remittance.value = 0;
          invoices.value.data.forEach(invoice => {
            total_invoice_amount.value += invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount;
            total_pi_amount.value +=
              invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_fees - invoice.total_invoice_discount;
            total_eligible_amount.value += invoice.eligible_for_finance;
            total_actual_remittance.value += invoice.actual_remittance_amount;
          });
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      buyer_search.value = '';
      invoice_number_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      date_search.value = '';
      $('#date_search').val('');
      await axios
        .get(base_url + 'factoring/cash-planner/invoices/eligible/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          total_invoice_amount.value = 0;
          total_pi_amount.value = 0;
          total_eligible_amount.value = 0;
          total_actual_remittance.value = 0;
          invoices.value.data.forEach(invoice => {
            total_invoice_amount.value += invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount;
            total_pi_amount.value +=
              invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_fees - invoice.total_invoice_discount;
            total_eligible_amount.value += invoice.eligible_for_finance;
            total_actual_remittance.value += invoice.actual_remittance_amount;
          });
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
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

    const showModal = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
        .then(response => {
          vendor_fee_details.value = [];

          total_fees.value = 0;

          total_tax_on_fees.value = 0;
          total_tax_on_fees.value = 0;
          total_tax_on_fees.value = 0;

          invoice_details.value = response.data;

          invoice_due_date.value = invoice_details.value.due_date;

          invoice_total_amount.value = 0;
          invoice_details.value.invoice_items.forEach(item => {
            invoice_total_amount.value += item.quantity * item.price_per_quantity;
          });

          invoice_taxes_amount.value = 0;
          invoice_details.value.invoice_taxes.forEach(item => {
            invoice_taxes_amount.value += item.value;
          });

          invoice_fees_amount.value = 0;
          invoice_details.value.invoice_fees.forEach(item => {
            invoice_fees_amount.value += item.amount;
          });

          invoice_discounts_amount.value = 0;
          invoice_details.value.invoice_discounts.forEach(item => {
            if (item.type == 'percentage') {
              discount_amount.value += (item.value / 100) * (invoice_total_amount.value + invoice_taxes_amount.value);
            } else {
              discount_amount.value += item.value;
            }
          });

          invoice_total_amount.value =
            invoice_details.value.total +
            invoice_details.value.total_invoice_taxes -
            invoice_details.value.total_invoice_discount;

          invoice_pi_amount.value =
            invoice_details.value.total +
            invoice_details.value.total_invoice_taxes -
            invoice_details.value.total_invoice_fees -
            invoice_details.value.total_invoice_discount;

          invoice_days_to_payment.value = moment(invoice_details.value.due_date).diff(moment(), 'days') + 1;

          invoice_eligibility_percentage.value = invoice_details.value.vendor_configurations.eligibility;

          invoice_eligibility_amount.value =
            invoice_details.value.total +
            invoice_details.value.total_invoice_taxes -
            invoice_details.value.total_invoice_fees -
            invoice_details.value.total_invoice_discount;

          invoice_eligibility_for_finance.value =
            (invoice_eligibility_percentage.value / 100) *
            (invoice_details.value.total +
              invoice_details.value.total_invoice_taxes -
              invoice_details.value.total_invoice_fees -
              invoice_details.value.total_invoice_discount);

          invoice_business_spread.value = invoice_details.value.vendor_discount_details.total_roi;

          invoice_discount_amount.value =
            invoice_eligibility_for_finance.value *
            (invoice_business_spread.value / 100) *
            ((moment(invoice_details.value.due_date).diff(moment(), 'days') + 1) / 365);

          tax_on_discount.value = invoice_details.value.program.discount_details[0].tax_on_discount;

          tax_on_discount_amount.value = (tax_on_discount.value / 100) * invoice_discount_amount.value;

          if (
            invoice_business_spread.value > 0 &&
            invoice_details.value.vendor_discount_details.vendor_discount_bearing > 0
          ) {
            invoice_discount_amount.value =
              (invoice_details.value.vendor_discount_details.vendor_discount_bearing / invoice_business_spread.value) *
              invoice_discount_amount.value;
          }

          let anchor_fees = 0;
          let vendor_bearing_fees = 0;
          let vendor_tax_on_fees = 0;
          if (invoice_details.value.program.discount_details[0].fee_type == 'Front Ended') {
            invoice_details.value.vendor_fee_details.forEach((fees, index) => {
              if (fees.type == 'percentage') {
                if (fees.charge_type === 'daily') {
                  // Vendor Bearing Fees
                  vendor_bearing_fees +=
                    (fees.vendor_bearing_discount / 100) *
                    ((fees.value / 100) * invoice_eligibility_for_finance.value * invoice_days_to_payment.value);

                  // Used for Display
                  let fee_amount =
                    (fees.vendor_bearing_discount / 100) *
                    ((fees.value / 100) * invoice_eligibility_for_finance.value * invoice_days_to_payment.value);

                  // Anchor Bearing Fees
                  anchor_fees +=
                    (fees.anchor_bearing_discount / 100) *
                    ((fees.value / 100) * invoice_eligibility_for_finance.value * invoice_days_to_payment.value);

                  if (fees.vendor_bearing_discount > 0) {
                    vendor_fee_details.value.push([
                      fees.fee_name,
                      new Intl.NumberFormat().format(fee_amount.toFixed(2)) + '(' + fees.value + '%)'
                    ]);
                  }

                  if (fees.taxes) {
                    vendor_tax_on_fees +=
                      (fees.taxes / 100) *
                      ((fees.value / 100) * invoice_eligibility_for_finance.value) *
                      invoice_days_to_payment.value;
                  }
                } else {
                  // Vendor Bearing Fees
                  vendor_bearing_fees +=
                    (fees.vendor_bearing_discount / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);

                  // Used for Display
                  let fee_amount =
                    (fees.vendor_bearing_discount / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);

                  // Anchor Bearing Fees
                  anchor_fees +=
                    (fees.anchor_bearing_discount / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);

                  if (fees.vendor_bearing_discount > 0) {
                    vendor_fee_details.value.push([
                      fees.fee_name,
                      new Intl.NumberFormat().format(fee_amount.toFixed(2)) + '(' + fees.value + '%)'
                    ]);
                  }

                  if (fees.taxes) {
                    vendor_tax_on_fees +=
                      (fees.taxes / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);
                  }
                }
              }
              if (fees.type === 'amount') {
                if (fees.charge_type === 'daily') {
                  // Vendor Bearing Fees
                  vendor_bearing_fees +=
                    (fees.vendor_bearing_discount / 100) * fees.value * invoice_days_to_payment.value;
                  // Used for Display
                  let fee_amount = (fees.vendor_bearing_discount / 100) * fees.value * invoice_days_to_payment.value;

                  // Anchor Bearing Fees
                  anchor_fees += (fees.anchor_bearing_discount / 100) * fees.value * invoice_days_to_payment.value;

                  if (fees.vendor_bearing_discount > 0) {
                    vendor_fee_details.value.push([
                      fees.fee_name,
                      invoice_details.value.currency +
                        ' ' +
                        new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                        ' (' +
                        fees.value +
                        ')'
                    ]);
                  }

                  if (fees.taxes) {
                    vendor_tax_on_fees += (fees.taxes / 100) * fees.value;
                  }
                } else {
                  // Vendor Bearing Fees
                  vendor_bearing_fees += (fees.vendor_bearing_discount / 100) * fees.value;
                  // Used for Display
                  let fee_amount = (fees.vendor_bearing_discount / 100) * fees.value;

                  // Anchor Bearing Fees
                  anchor_fees += (fees.anchor_bearing_discount / 100) * fees.value;

                  if (fees.vendor_bearing_discount > 0) {
                    vendor_fee_details.value.push([
                      fees.fee_name,
                      invoice_details.value.currency +
                        ' ' +
                        new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                        ' (' +
                        fees.value +
                        ')'
                    ]);
                  }

                  if (fees.taxes) {
                    vendor_tax_on_fees += (fees.taxes / 100) * fees.value;
                  }
                }
              }
              if (fees.type == 'per amount') {
                if (fees.charge_type === 'daily') {
                  // Vendor Bearing Fees
                  vendor_bearing_fees +=
                    (fees.vendor_bearing_discount / 100) *
                    (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                      fees.value *
                      invoice_days_to_payment.value);
                  // Used for Display
                  let fee_amount =
                    (fees.vendor_bearing_discount / 100) *
                    (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                      fees.value *
                      invoice_days_to_payment.value);

                  // Anchor Bearing Fees
                  anchor_fees +=
                    (fees.anchor_bearing_discount / 100) *
                    (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                      fees.value *
                      invoice_days_to_payment.value);

                  if (fees.vendor_bearing_discount > 0) {
                    vendor_fee_details.value.push([
                      fees.fee_name,
                      invoice_details.value.currency +
                        ' ' +
                        new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                        ' (' +
                        fees.value +
                        ' Per ' +
                        fees.per_amount +
                        ')'
                    ]);
                  }

                  if (fees.taxes) {
                    vendor_tax_on_fees +=
                      (fees.taxes / 100) *
                      (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                        fees.value *
                        invoice_days_to_payment.value);
                  }
                } else {
                  // Vendor Bearing Fees
                  vendor_bearing_fees +=
                    (fees.vendor_bearing_discount / 100) *
                    (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);

                  // Used for Display
                  let fee_amount =
                    (fees.vendor_bearing_discount / 100) *
                    (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);

                  // Anchor Bearing Fees
                  anchor_fees +=
                    (fees.anchor_bearing_discount / 100) *
                    (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);

                  if (fees.vendor_bearing_discount > 0) {
                    vendor_fee_details.value.push([
                      fees.fee_name,
                      invoice_details.value.currency +
                        ' ' +
                        new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                        ' (' +
                        fees.value +
                        ' Per ' +
                        fees.per_amount +
                        ')'
                    ]);
                  }

                  if (fees.taxes) {
                    vendor_tax_on_fees +=
                      (fees.taxes / 100) *
                      (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);
                  }
                }
              }
            });
          }

          total_fees.value += vendor_bearing_fees;

          total_tax_on_fees.value += vendor_tax_on_fees;

          if (invoice_details.value.discount_type == 'Front Ended') {
            invoice_total_remmitance.value =
              invoice_eligibility_for_finance.value -
              total_fees.value -
              total_tax_on_fees.value -
              invoice_discount_amount.value -
              tax_on_discount_amount.value;
          } else {
            invoice_total_remmitance.value =
              invoice_eligibility_for_finance.value - total_fees.value - total_tax_on_fees.value;
          }

          // Fee Charging is rear ended
          if (invoice_details.value.program.discount_details[0].fee_type == 'Rear Ended') {
            invoice_total_remmitance.value =
              invoice_total_remmitance.value + total_fees.value + total_tax_on_fees.value;
          }

          invoice_min_financing_days.value = invoice_details.value.min_financing_days;
          invoice_max_financing_days.value = invoice_details.value.max_financing_days;

          min_date.value = moment(invoice_details.value.due_date)
            .subtract(invoice_max_financing_days.value, 'days')
            .format('DD MMM YYYY');

          // Ensure min_date is not in the past
          if (moment(min_date.value) < moment()) {
            min_date.value = moment().format('DD MMM YYYY');
          }

          payment_date.value = min_date.value;

          invoice_days_to_payment.value = moment(invoice_details.value.due_date).diff(moment(min_date.value), 'days');

          max_date.value = moment(invoice_details.value.due_date)
            .subtract(invoice_min_financing_days.value, 'days')
            .format('DD MMM YYYY');

          let disabled_dates = [
            function (date) {
              return offdays.value.includes(date.getDay());
            }
          ];

          prime_max_date.value = new Date(max_date.value);

          offdays.value.forEach(off_day => {
            prime_disabled_days.value.push(off_day);
          });

          bank_holidays.value.forEach(holiday => {
            disabled_dates.push('"' + holiday + '"');
            prime_disabled_dates.value.push(new Date(holiday));
          });

          config.value = {
            inline: true,
            wrap: false, // set wrap to true only when using 'input-group'
            altInput: true,
            dateFormat: 'd M Y',
            locale: {
              firstDayOfWeek: 1
            },
            minDate: min_date.value,
            maxDate: max_date.value,
            disable: disabled_dates
          };

          invoice_details.value.bank_details.forEach(bank_details => {
            if (bank_details.account_number == invoice_details.value.credit_to) {
              credit_to.value = bank_details.id;
              can_submit.value = true;
            } else {
              credit_to.value = '';
              can_submit.value = false;
            }
          });

          request_payment_date.value = moment().format('DD MMM YYYY');

          viewRequestDetails.value = true;

          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateBulkDiscount = (selectedDates, dateStr, instance) => {
      if (dateStr != '') {
        bulk_invoice_remittance.value = 0;
        selected_invoices_total_amount.value = 0;
        total_fees.value = 0;
        total_tax_on_fees.value = 0;
        tax_on_discount_amount.value = 0;

        bulk_data_loading.value = true;

        // Get invoice details
        axios
          .post(base_url + 'factoring/invoices/remittance/amount', {
            invoices: selected_invoices.value,
            date: bulk_request_payment_date.value
          })
          .then(response => {
            selected_invoices_total_amount.value = response.data.total_amount;
            bulk_invoice_remittance.value = response.data.total_remittance_amount;
            bulk_data_loading.value = false;
          });

        bulk_payment_date.value = moment(dateStr).format('DD MMM YYYY');
        bulk_request_payment_date.value = moment(dateStr).format('DD MMM YYYY');
      }
    };

    const showMultipleRequestModal = () => {
      if (selected_invoices.value.length <= 0) {
        toast.error('Select Payment Instructions');
        return false;
      }

      bulk_request_payment_date.value = new Date().toLocaleDateString('en-CA');

      bulk_payment_date.value = new Date().toLocaleDateString('en-CA');

      let disabled_dates = [
        function (date) {
          return offdays.value.includes(date.getDay());
        }
      ];

      prime_max_date.value = new Date(bulk_max_date.value);

      offdays.value.forEach(off_day => {
        prime_disabled_days.value.push(off_day);
      });

      bank_holidays.value.forEach(holiday => {
        disabled_dates.push('"' + holiday + '"');
        prime_disabled_dates.value.push(new Date(holiday));
      });

      config.value = {
        inline: true,
        wrap: false, // set wrap to true only when using 'input-group'
        altInput: true,
        dateFormat: 'd M Y',
        locale: {
          firstDayOfWeek: 1
        },
        minDate: 'today',
        maxDate: bulk_max_date.value,
        disable: disabled_dates
      };

      bulk_invoice_remittance.value = 0;
      selected_invoices_total_amount.value = 0;
      total_fees.value = 0;
      total_tax_on_fees.value = 0;
      tax_on_discount_amount.value = 0;

      axios
        .post(base_url + 'factoring/invoices/remittance/amount', {
          invoices: selected_invoices.value,
          date: bulk_request_payment_date.value
        })
        .then(response => {
          selected_invoices_total_amount.value = response.data.total_amount;
          bulk_invoice_remittance.value = response.data.total_remittance_amount;
          bulk_data_loading.value = false;
        });

      show_multiple_request_modal.value.click();
    };

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'created':
          style = 'bg-label-primary';
          break;
        case 'paid':
          style = 'bg-label-success';
          break;
        case 'failed':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }

      return style;
    };

    const getInvoices = async () => {
      await axios
        .get(base_url + 'factoring/cash-planner/invoices/eligible/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          terms_and_conditions_link.value = response.data.terms_and_conditions;
          noa_link.value = response.data.noa;
          terms_text.value = response.data.terms_text;
          noa_text.value = response.data.noa_text;
          invoices.value = response.data.invoices;
          total_invoice_amount.value = 0;
          total_pi_amount.value = 0;
          total_eligible_amount.value = 0;
          total_actual_remittance.value = 0;
          invoices.value.data.forEach(invoice => {
            total_invoice_amount.value += invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount;
            total_pi_amount.value +=
              invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_fees - invoice.total_invoice_discount;
            total_eligible_amount.value += invoice.eligible_for_finance;
            total_actual_remittance.value += invoice.actual_remittance_amount;
          });

          bulk_max_date.value = moment(response.data.highest_due_date)
            .subtract(response.data.min_financing_days, 'days')
            .format('DD MMM YYYY');

          response.data.holidays.forEach(holiday => {
            bank_holidays.value.push(holiday.date_formatted);
          });

          if (response.data.off_days) {
            response.data.off_days.forEach(off => {
              offdays.value.push(off);
            });
          }
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateDiscount = (selectedDates, dateStr, instance) => {
      invoice_total_amount.value = invoice_details.value.total;
      invoice_taxes_amount.value = invoice_details.value.total_invoice_taxes;
      invoice_fees_amount.value = invoice_details.value.total_invoice_fees;

      total_fees.value = 0;

      total_tax_on_fees.value = 0;

      vendor_fee_details.value = [];

      let invoice_discounts = invoice_details.value.total_invoice_discount;

      invoice_total_amount.value = invoice_total_amount.value + invoice_taxes_amount.value - invoice_discounts;

      let invoice_amount = invoice_total_amount.value - invoice_fees_amount.value;

      invoice_eligibility_amount.value = (invoice_eligibility_percentage.value / 100) * invoice_amount;

      invoice_processing_fee.value = (processing_fees.value / 100) * invoice_eligibility_amount.value;

      invoice_days_to_payment.value = moment(invoice_details.value.due_date).diff(moment(dateStr), 'days');

      invoice_discount_amount.value =
        (invoice_eligibility_percentage.value / 100) *
        invoice_amount *
        (invoice_business_spread.value / 100) *
        (moment(invoice_details.value.due_date).diff(moment(dateStr), 'days') / 365);

      tax_on_discount_amount.value = (tax_on_discount.value / 100) * invoice_discount_amount.value;

      if (invoice_details.value.vendor_discount_details.vendor_discount_bearing > 0) {
        invoice_discount_amount.value =
          (invoice_details.value.vendor_discount_details.vendor_discount_bearing / invoice_business_spread.value) *
          invoice_discount_amount.value;
      }

      let vendor_bearing_fees = 0;
      let anchor_fees = 0;
      let vendor_tax_on_fees = 0;
      if (invoice_details.value.program.discount_details[0].fee_type == 'Front Ended') {
        invoice_details.value.vendor_fee_details.forEach((fees, index) => {
          if (fees.type == 'percentage') {
            if (fees.charge_type === 'daily') {
              // Vendor Bearing Fees
              vendor_bearing_fees +=
                (fees.vendor_bearing_discount / 100) *
                ((fees.value / 100) * invoice_eligibility_for_finance.value * invoice_days_to_payment.value);

              // Used for Display
              let fee_amount =
                (fees.vendor_bearing_discount / 100) *
                ((fees.value / 100) * invoice_eligibility_for_finance.value * invoice_days_to_payment.value);

              // Anchor Bearing Fees
              anchor_fees +=
                (fees.anchor_bearing_discount / 100) *
                ((fees.value / 100) * invoice_eligibility_for_finance.value * invoice_days_to_payment.value);

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  new Intl.NumberFormat().format(fee_amount.toFixed(2)) + '(' + fees.value + '%)'
                ]);
              }

              if (fees.taxes) {
                vendor_tax_on_fees +=
                  (fees.taxes / 100) *
                  ((fees.value / 100) * invoice_eligibility_for_finance.value) *
                  invoice_days_to_payment.value;
              }
            } else {
              // Vendor Bearing Fees
              vendor_bearing_fees +=
                (fees.vendor_bearing_discount / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);

              // Used for Display
              let fee_amount =
                (fees.vendor_bearing_discount / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);

              // Anchor Bearing Fees
              anchor_fees +=
                (fees.anchor_bearing_discount / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  new Intl.NumberFormat().format(fee_amount.toFixed(2)) + '(' + fees.value + '%)'
                ]);
              }

              if (fees.taxes) {
                vendor_tax_on_fees += (fees.taxes / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);
              }
            }
          }
          if (fees.type === 'amount') {
            if (fees.charge_type === 'daily') {
              // Vendor Bearing Fees
              vendor_bearing_fees += (fees.vendor_bearing_discount / 100) * fees.value * invoice_days_to_payment.value;
              // Used for Display
              let fee_amount = (fees.vendor_bearing_discount / 100) * fees.value * invoice_days_to_payment.value;

              // Anchor Bearing Fees
              anchor_fees += (fees.anchor_bearing_discount / 100) * fees.value * invoice_days_to_payment.value;

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  invoice_details.value.currency +
                    ' ' +
                    new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                    ' (' +
                    fees.value +
                    ')'
                ]);
              }

              if (fees.taxes) {
                vendor_tax_on_fees += (fees.taxes / 100) * fees.value;
              }
            } else {
              // Vendor Bearing Fees
              vendor_bearing_fees += (fees.vendor_bearing_discount / 100) * fees.value;
              // Used for Display
              let fee_amount = (fees.vendor_bearing_discount / 100) * fees.value;

              // Anchor Bearing Fees
              anchor_fees += (fees.anchor_bearing_discount / 100) * fees.value;

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  invoice_details.value.currency +
                    ' ' +
                    new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                    ' (' +
                    fees.value +
                    ')'
                ]);
              }

              if (fees.taxes) {
                vendor_tax_on_fees += (fees.taxes / 100) * fees.value;
              }
            }
          }
          if (fees.type == 'per amount') {
            if (fees.charge_type === 'daily') {
              // Vendor Bearing Fees
              vendor_bearing_fees +=
                (fees.vendor_bearing_discount / 100) *
                (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                  fees.value *
                  invoice_days_to_payment.value);
              // Used for Display
              let fee_amount =
                (fees.vendor_bearing_discount / 100) *
                (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                  fees.value *
                  invoice_days_to_payment.value);

              // Anchor Bearing Fees
              anchor_fees +=
                (fees.anchor_bearing_discount / 100) *
                (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                  fees.value *
                  invoice_days_to_payment.value);

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  invoice_details.value.currency +
                    ' ' +
                    new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                    ' (' +
                    fees.value +
                    ' Per ' +
                    fees.per_amount +
                    ')'
                ]);
              }

              if (fees.taxes) {
                vendor_tax_on_fees +=
                  (fees.taxes / 100) *
                  (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) *
                    fees.value *
                    invoice_days_to_payment.value);
              }
            } else {
              // Vendor Bearing Fees
              vendor_bearing_fees +=
                (fees.vendor_bearing_discount / 100) *
                (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);

              // Used for Display
              let fee_amount =
                (fees.vendor_bearing_discount / 100) *
                (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);

              // Anchor Bearing Fees
              anchor_fees +=
                (fees.anchor_bearing_discount / 100) *
                (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  invoice_details.value.currency +
                    ' ' +
                    new Intl.NumberFormat().format(fee_amount.toFixed(2)) +
                    ' (' +
                    fees.value +
                    ' Per ' +
                    fees.per_amount +
                    ')'
                ]);
              }

              if (fees.taxes) {
                vendor_tax_on_fees +=
                  (fees.taxes / 100) *
                  (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);
              }
            }
          }
        });
      }

      total_fees.value += vendor_bearing_fees;

      total_tax_on_fees.value += vendor_tax_on_fees;

      if (invoice_details.value.program.discount_details[0].discount_type == 'Front Ended') {
        invoice_total_remmitance.value =
          (invoice_eligibility_percentage.value / 100) * invoice_amount -
          total_fees.value -
          total_tax_on_fees.value -
          invoice_discount_amount.value -
          tax_on_discount_amount.value;
      } else {
        invoice_total_remmitance.value =
          (invoice_eligibility_percentage.value / 100) * invoice_amount - total_fees.value - total_tax_on_fees.value;
      }

      // Fees are charged rear ended
      if (invoice_details.value.program.discount_details[0].fee_type == 'Rear Ended') {
        invoice_total_remmitance.value = invoice_total_remmitance.value + total_fees.value + total_tax_on_fees.value;
      }

      payment_date.value = moment(dateStr).format('DD MMM YYYY');
    };

    const requestFinance = async () => {
      if (credit_to.value == '') {
        toast.error('Select Payment Account');
        payment_account.value = true;
        return;
      }
      can_submit.value = false;
      await axios
        .post(base_url + 'factoring/cash-planner/invoices/request/send', {
          invoice_id: invoice_details.value.id,
          payment_request_date: payment_date.value,
          credit_to: credit_to.value
        })
        .then(response => {
          toast.success('Payment request sent successfully');
          document.getElementById('close-single-terms-and-conditions').click();
          document.getElementById('successful-request-for-finance').click();
          getInvoices();
        })
        .catch(err => {
          console.log(err);
          can_submit.value = true;
          if (err.response.data && err.response.data.message) {
            toast.error(err.response.data.message);
          } else {
            toast.error('An error. occurred. Refresh and try again.');
          }
        });
    };

    const requestFinanceForMultiple = async () => {
      can_submit.value = false;
      document.getElementById('accept-bulk-terms').innerText = 'Processing...';
      const formData = new FormData();
      selected_invoices.value.forEach(selected => {
        formData.append('invoices[]', selected.id);
      });
      formData.append('payment_request_date', bulk_payment_date.value);
      await axios
        .post(base_url + 'factoring/cash-planner/invoices/request/multiple/send', formData)
        .then(response => {
          toast.success('Payment requests sent successfully');
          document.getElementById('close-bulk-terms-and-conditions').click();
          document.getElementById('successful-request-for-finance').click();
          selected_invoices.value = [];
          selected_invoices_total_amount.value = 0;
          bulk_invoice_remittance.value = 0;
          getInvoices();
        })
        .catch(err => {
          console.log(err);
          can_submit.value = true;
          if (err.response.data && err.response.data.message) {
            toast.error(err.response.data.message);
          } else {
            toast.error('An error. occurred. Refresh and try again.');
          }
        })
        .finally(() => {
          document.getElementById('accept-bulk-terms').innerText = 'Accept';
        });
    };

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    onMounted(() => {
      getInvoices();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            anchor: buyer_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          total_invoice_amount.value = 0;
          total_pi_amount.value = 0;
          total_eligible_amount.value = 0;
          total_actual_remittance.value = 0;
          invoices.value = response.data.invoices;
          invoices.value.data.forEach(invoice => {
            total_invoice_amount.value += invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount;
            total_pi_amount.value +=
              invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_fees - invoice.total_invoice_discount;
            total_eligible_amount.value += invoice.eligible_for_finance;
            total_actual_remittance.value += invoice.actual_remittance_amount;
            let is_selected = selected_invoices.value.filter(selected => {
              return selected.id == invoice.id;
            });

            if (is_selected.length > 0) {
              nextTick(() => {
                document.getElementById('invoice-' + invoice.id).checked = true;
              });
            }
          });
        });
    };

    const updateSelected = invoice => {
      let index = selected_invoices.value.findIndex(selected => selected.id == invoice.id);
      if (index == -1) {
        selected_invoices.value.push(invoice);
      } else {
        selected_invoices.value.splice(index, 1);
      }

      if (selected_invoices.value.length == invoices.value.data.length) {
        document.getElementById('select-all').checked = true;
      } else {
        document.getElementById('select-all').checked = false;
      }

      if (selected_invoices.value.length > 0) {
        // Get max date that can be selected.(most minumum value from selected invoices)
        let earliest = selected_invoices.value[0].due_date;
        let least_minimum_financing_days = selected_invoices.value[0].min_financing_days;
        selected_invoices.value.forEach(select => {
          // Get invoice with earliest date
          if (moment(select.due_date).isBefore(earliest)) {
            earliest = moment(select.due_date);
          }

          // Get least minimum financing days
          if (select.min_financing_days < least_minimum_financing_days) {
            least_minimum_financing_days = select.min_financing_days;
          }
        });

        max_date.value = moment(earliest).subtract(least_minimum_financing_days, 'days').format('DD MMM YYYY');

        config.value = {
          inline: false,
          wrap: false, // set wrap to true only when using 'input-group'
          altInput: true,
          dateFormat: 'd M Y',
          locale: {
            firstDayOfWeek: 1
          },
          minDate: 'today',
          maxDate: max_date.value,
          disable: [
            function (date) {
              return date.getDay() === 0 || date.getDay() === 6; // disable weekends
            }
          ]
        };
      }
    };

    const selectAll = () => {
      if (!document.getElementById('select-all').checked) {
        invoices.value.data.forEach(invoice => {
          if (document.getElementById('invoice-' + invoice.id).checked == true) {
            document.getElementById('invoice-' + invoice.id).checked = false;
            let f;
            let index = selected_invoices.value.filter(function (selected, index) {
              f = index;
              selected.id == invoice.id;
            });
            if (!index) {
              return false;
            }
            selected_invoices.value.splice(f, 1);
          }
        });
      } else {
        invoices.value.data.forEach(invoice => {
          document.getElementById('invoice-' + invoice.id).checked = true;
          selected_invoices.value.push(invoice);
        });
      }
    };

    const acceptTerms = () => {
      can_submit.value = !can_submit.value;
    };

    const formatNoa = invoice => {
      let new_text = '';
      if (noa_text.value) {
        new_text = noa_text.value.body;
        new_text = new_text.replace('{date}', moment(invoice.invoice_date).format('Do MMM YYYY'));
        new_text = new_text.replace('{buyerName}', invoice.company.name);
        new_text = new_text.replace('{anchorName}', invoice.buyer.name);
        new_text = new_text.replace('{company}', invoice.company.name);
        new_text = new_text.replace('{anchorCompanyUniqueID}', invoice.buyer.unique_identification_number);
        new_text = new_text.replace('{time}', moment().format('Do MMM YYYY'));
        new_text = new_text.replace('{agreementDate}', moment().format('Do MMM YYYY'));
        new_text = new_text.replace('{contract}', '');
        new_text = new_text.replace('{anchorCustomerId}', '');
        new_text = new_text.replace(
          '{anchorAccountName}',
          invoice.bank_details.length > 0 ? invoice.bank_details[0].name_as_per_bank : ''
        );
        new_text = new_text.replace(
          '{anchorAccountNumber}',
          invoice.bank_details.length > 0 ? invoice.bank_details[0].account_number : ''
        );
        new_text = new_text.replace('{anchorCustomerId}', '');
        new_text = new_text.replace(
          '{anchorBranch}',
          invoice.bank_details.length > 0 ? invoice.bank_details[0].branch : ''
        );
        new_text = new_text.replace('{anchorIFSCCode}', '');
        new_text = new_text.replace(
          '{anchorAddress}',
          invoice.buyer.address + ' ' + invoice.buyer.postal_code + ' ' + invoice.buyer.city
        );
        new_text = new_text.replace(
          '{penalnterestRate}',
          invoice.vendor_discount_details ? invoice.vendor_discount_details.penal_discount_on_principle : ''
        );
        new_text = new_text.replace('{sellerName}', invoice.company.name);

        noa_text.value.body = new_text;
      }
    };

    return {
      can_request,
      is_dashboard,
      config,
      config2,
      moment,
      max_date,
      min_date,
      eligibility_amount,
      pi_amount,
      discount_amount,
      days_to_payment,
      total_remittance,
      processing_fee_amount,
      invoices,
      payment_date,
      request_payment_date,
      bulk_request_payment_date,
      credit_to,
      taxes_amount,

      payment_account,

      vendor_fee_details,

      total_fees,
      total_tax_on_fees,

      tax_on_discount,
      tax_on_discount_amount,

      invoice_total_amount,
      invoice_taxes_amount,
      invoice_fees_amount,
      invoice_eligibility_percentage,
      invoice_eligibility_amount,
      invoice_eligibility_for_finance,
      invoice_discount_amount,
      invoice_days_to_payment,
      invoice_business_spread,
      invoice_processing_fee,
      invoice_min_financing_days,
      invoice_due_date,
      invoice_total_remmitance,

      bulk_invoice_remittance,

      resolvePaymentRequestStatus,

      total_invoice_amount,
      total_pi_amount,
      total_eligible_amount,
      total_actual_remittance,

      can_submit,

      selected_invoices,

      show_multiple_request_modal,

      show_terms_button,

      terms_and_conditions_link,

      noa_text,

      noa_link,

      terms_text,

      selected_invoices_total_amount,

      updateBulkDiscount,

      bulk_data_loading,

      // Search fields
      buyer_search,
      invoice_number_search,
      from_date_search,
      to_date_search,
      per_page,

      formatNoa,

      prime_disabled_dates,
      prime_disabled_days,
      prime_max_date,
      prime_min_date,

      changePage,
      updateDiscount,
      showModal,
      showMultipleRequestModal,
      requestFinanceForMultiple,

      requestFinance,
      invoice_details,
      viewRequestDetails,
      getTaxPercentage,
      updateSelected,
      selectAll,
      acceptTerms,
      filter,
      refresh,
      showInvoice,
      invoice_details_key
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
/* .flatpickr-calendar {
  width: 100% !important;
}
.flatpickr-innerContainer {
  width: 100% !important;
  padding: 0 !important;
  margin-top: 5px !important;
}
.flatpickr-rContainer {
  width: 90% !important;
  padding: 0 !important;
  margin-left: 15px !important;
} */
.flatpickr-months {
  min-height: 50px !important;
}
.flatpickr-month {
  min-height: 50px !important;
}
.flatpickr-current-month {
  padding: 0 !important;
}
.flatpickr-days {
  width: 100% !important;
  padding: 0 !important;
  margin-left: 10px !important;
}
.flatpickr-months .flatpickr-next-month {
  top: 10px !important;
}
.flatpickr-months .flatpickr-prev-month {
  top: 10px !important;
}
.invoices-table-content {
  height: 40px !important;
  overflow: auto !important;
}
</style>
