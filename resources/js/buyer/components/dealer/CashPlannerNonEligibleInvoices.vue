<template>
  <div class="card">
    <div class="card-header">
      <h6>{{ $t('Payment Instructions not eligible for Financing') }}</h6>
    </div>
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap">
          <div class="mb-1">
            <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              v-model="anchor_search"
              placeholder="Anchor"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="mx-1">
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
          <div class="mx-1">
            <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Due Date') }})</label>
            <input
              type="text"
              id="non_eligible_date_search"
              class="form-control form-search"
              name="non_eligible_daterange"
              placeholder="Select Dates"
              autocomplete="off"
            />
          </div>
          <!-- <div class="mx-1">
            <label for="From Date" class="form-label">{{ $t('From Date') }}</label>
            <input class="form-control form-search" type="date" id="html5-date-input" v-model="from_date_search" v-on:keyup.enter="filter" />
          </div>
          <div class="mx-1">
            <label for="To Date" class="form-label">{{ $t('To Date') }}</label>
            <input class="form-control form-search" type="date" id="html5-date-input" v-model="to_date_search" v-on:keyup.enter="filter" />
          </div> -->
          <div class="mx-1 my-auto">
            <button class="btn btn-primary btn-sm" @click="filter">
              <i class="ti ti-search non-eligible-filter"></i>
            </button>
          </div>
          <div class="mx-1 my-auto">
            <button class="btn btn-primary btn-sm" @click="refresh">
              <i class="ti ti-refresh non-eligible-refresh"></i>
            </button>
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
      <div class="table-responsive">
        <table class="table invoices-table">
          <thead>
            <tr class="">
              <th>{{ $t('Invoice No') }}.</th>
              <th>{{ $t('Anchor') }}.</th>
              <th>{{ $t('Invoice Date') }}</th>
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('Due Date') }}</th>
              <th></th>
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
              <td
                class="text-decoration-underline text-primary"
                @click="showInvoice(invoice.id, 'pending-show-planner-pi-' + invoice.id)"
              >
                <a href="javascript:;" class="">{{ invoice.invoice_number }}</a>
              </td>
              <td class="">{{ invoice.anchor }}</td>
              <td class="">{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
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
              <td class="">{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
              <td>
                <button
                  class="d-none"
                  :id="'pending-show-planner-pi-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pending-cash-planner-pi-' + invoice.id"
                ></button>
                <button
                  class="d-none"
                  :id="'pending-loading-modal-btn-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pending-loading-modal-' + invoice.id"
                ></button>
                <div class="modal fade" :id="'pending-loading-modal-' + invoice.id" tabindex="-1">
                  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          :id="'pending-close-loading-modal-btn-' + invoice.id"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="d-flex justify-content-center">
                          <span>{{ $t('Loading') }}...</span>
                          <img src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'pending-cash-planner-invoice-' + invoice.id"
                tabindex="-1"
              >
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex">
                        <div class="d-flex" v-if="invoice_details.media.length > 0">
                          <a
                            v-for="(attachment, key) in invoice_details.media"
                            :key="key"
                            :href="attachment.original_url"
                            target="_blank"
                            class="btn btn-secondary btn-sm mx-1"
                          >
                            <i class="ti ti-paperclip"></i>{{ $t('View Attachment') }} {{ key + 1 }}</a
                          >
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
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.buyer ? invoice_details.buyer.name : invoice_details.program.anchor.name }}</h6>
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
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.buyer ? invoice_details.program.anchor.name : invoice_details.company.name }}</h6>
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
                              :data-bs-target="'#pi-' + invoice_details.id"
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
                        <span
                          class="d-flex justify-content-end"
                        >
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
                :id="'pending-cash-planner-pi-' + invoice.id"
                tabindex="-1"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('PI Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex">
                        <a :href="'invoices/' + invoice_details.id + '/edit'" v-if="invoice_details.can_edit">
                          <button class="btn btn-info btn-sm mx-1">
                            <i class="ti ti-pencil"></i> {{ $t('Edit') }}
                          </button>
                        </a>
                        <a :href="'invoices/create/' + invoice_details.id" v-if="invoice_details.can_edit">
                          <button class="btn btn-warning btn-sm mx-1">
                            <i class="ti ti-pencil"></i> {{ $t('Replicate') }}
                          </button>
                        </a>
                        <a
                          :href="'invoices/payment-instruction/' + invoice_details.id + '/pdf/download'"
                          class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                        <a
                          v-if="invoice_details.attachment"
                          :href="invoice_details.attachment"
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
                            <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.program.anchor.name }}</h6>
                          </span>
                          <span v-if="invoice_details.purchase_order" class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.purchase_order.delivery_address }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.remarks }}</h6>
                          </span>
                        </div>
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice/Unique Ref No') }}:</h5>
                            <h6
                              class="text-decoration-underline text-primary mx-2 my-auto"
                              data-bs-toggle="modal"
                              :data-bs-target="'#pending-cash-planner-invoice-' + invoice_details.id"
                            >
                              <a href="javascript:;" class="">{{ invoice_details.invoice_number }}</a>
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Due Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(invoice_details.due_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table">
                          <thead style="background: #f0f0f0">
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
                      <div v-if="invoice_details.invoice_discounts.length" class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Total Invoice Discount') }}</h6>
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
                      <div v-if="invoice_details.invoice_fees.length > 0" class="px-2">
                        <span
                          v-for="fee in invoice_details.invoice_fees"
                          :key="fee.id"
                          class="d-flex justify-content-end"
                        >
                          <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                          <h5 class="text-success my-auto py-1" v-if="fee.name != 'Credit Note Amount'">
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
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                          <h5 class="text-success my-auto py-1">
                            {{
                              new Intl.NumberFormat().format(
                                invoice_details.total +
                                  invoice_details.total_invoice_taxes -
                                  invoice_details.total_invoice_fees -
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

export default {
  name: 'EligibleInvoices',
  components: {
    Pagination,
    flatPickr,
    DatePicker
  },
  setup(props) {
    const toast = useToast();
    const invoices = ref([]);
    const base_url = inject('baseURL');
    const invoice_details = ref(null);
    const eligibility_amount = ref(0);
    const discount_amount = ref(0);
    const min_date = ref(new Date().toLocaleDateString('en-CA'));
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
    const invoice_due_date = ref(0);
    const payment_accounts = ref([]);
    const eligibility = ref(0);
    const pi_amount = ref(0);
    const taxes_amount = ref(0);
    const business_spread = ref(0);
    const credit_to = ref('');
    const payment_account = ref(false);
    const viewRequestDetails = ref(false);
    const drawdown_amount = ref(0);
    const invoice_amount_in_words = ref('');
    const drawdown_amount_in_words = ref('');

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
    const anchor_search = ref('');
    const date_search = ref('');

    const bank_holidays = ref([]);
    const offdays = ref([]);

    const per_page = ref(50);

    const bulk_data_loading = ref(false);

    const prime_disabled_dates = ref([]);
    const prime_disabled_days = ref([]);
    const prime_max_date = ref(null);
    const prime_bulk_max_date = ref(null);
    const prime_min_date = ref(new Date());

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'cash-planner/invoices/non-eligible/data', {
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
        });
    });

    const filter = async () => {
      date_search.value = $('#non_eligible_date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.non-eligible-filter').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + 'cash-planner/invoices/non-eligible/data', {
          params: {
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-search non-eligible-filter"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.non-eligible-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      anchor_search.value = '';
      invoice_number_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      date_search.value = '';
      $('#non_eligible_date_search').val('');
      await axios
        .get(base_url + 'cash-planner/invoices/non-eligible/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-refresh non-eligible-refresh"></i>');
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('pending-loading-modal-btn-' + invoice).click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          nextTick(() => {
            document.getElementById('pending-close-loading-modal-btn-' + invoice).click();
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showModal = async (invoice, modal) => {
      document.getElementById('pending-loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
        .then(response => {
          vendor_fee_details.value = [];

          total_fees.value = 0;

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

          invoice_eligibility_amount.value = invoice_details.value.invoice_total_amount;

          invoice_eligibility_for_finance.value =
            (invoice_eligibility_percentage.value / 100) * invoice_details.value.invoice_total_amount;

          invoice_amount_in_words.value = toWords(invoice_eligibility_for_finance.value.toFixed(2));

          invoice_business_spread.value = invoice_details.value.vendor_discount_details.total_roi;

          let vendor_fees = 0;
          let vendor_tax_on_fees = 0;
          invoice_details.value.vendor_fee_details.forEach((fees, index) => {
            if (fees.type == 'percentage') {
              // Vendor Bearing Fees
              vendor_fees += (fees.value / 100) * invoice_eligibility_for_finance.value;

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([fees.fee_name, fees.value + '%']);
              }
              if (fees.taxes) {
                vendor_tax_on_fees += (fees.taxes / 100) * ((fees.value / 100) * invoice_eligibility_for_finance.value);
              }
            }
            if (fees.type == 'amount') {
              // Vendor Bearing Fees
              vendor_fees += fees.value;

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([fees.fee_name, invoice_details.value.currency + ' ' + fees.value]);
              }
              if (fees.taxes) {
                vendor_tax_on_fees += (fees.taxes / 100) * ((fees.vendor_bearing_discount / 100) * fees.value);
              }
            }
            if (fees.type == 'per amount') {
              // Vendor Bearing Fees
              vendor_fees += Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value;

              if (fees.vendor_bearing_discount > 0) {
                vendor_fee_details.value.push([
                  fees.fee_name,
                  fees.value + ' P.Am ' + invoice_details.value.currency + ' ' + fees.per_amount
                ]);
              }
              if (fees.taxes) {
                vendor_tax_on_fees +=
                  (fees.taxes / 100) *
                  (Math.round(invoice_eligibility_for_finance.value / fees.per_amount) * fees.value);
              }
            }
          });

          total_fees.value += vendor_fees;

          total_tax_on_fees.value += vendor_tax_on_fees;

          invoice_total_remmitance.value =
            invoice_eligibility_for_finance.value - total_fees.value - total_tax_on_fees.value;

          invoice_min_financing_days.value = invoice_details.value.program.min_financing_days;

          // drawdown_amount.value = invoice_eligibility_amount.value;
          drawdown_amount_in_words.value = toWords(0);

          let highest_to_day_value = 0;
          invoice_details.value.vendor_discount_details.forEach(discount_details => {
            if (discount_details.to_day > highest_to_day_value) {
              highest_to_day_value = discount_details.to_day;
            }
          });

          max_date.value = moment().add(highest_to_day_value, 'days').format('DD MMM YYYY');

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
            minDate: 'today',
            maxDate: max_date.value,
            disable: disabled_dates
          };

          request_payment_date.value = moment().add(highest_to_day_value, 'days').format('DD MMM YYYY');
          payment_date.value = moment().add(highest_to_day_value, 'days').format('DD MMM YYYY');

          viewRequestDetails.value = true;

          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch(drawdown_amount, drawdown_amount => {
      drawdown_amount_in_words.value = toWords(drawdown_amount.replaceAll(',', ''));
      if (
        drawdown_amount.replaceAll(',', '') > 0 &&
        drawdown_amount.replaceAll(',', '') <= invoice_eligibility_for_finance.value
      ) {
        can_submit.value = true;
      } else {
        can_submit.value = false;
      }
    });

    const formatAmount = () => {
      if (drawdown_amount.value) {
        drawdown_amount.value = new Intl.NumberFormat().format(drawdown_amount.value.replaceAll(',', ''));
      }
    };

    const updateBulkDiscount = (selectedDates, dateStr, instance) => {
      if (dateStr != '') {
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

      let disabled_dates = [
        function (date) {
          return offdays.value.includes(date.getDay());
        }
      ];

      prime_bulk_max_date.value = new Date(bulk_max_date.value);

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
        .post(base_url + 'invoices/remittance/amount', {
          invoices: selected_invoices.value,
          date: bulk_request_payment_date.value
        })
        .then(response => {
          selected_invoices_total_amount.value = response.data.total_amount;
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
        .get(base_url + 'cash-planner/invoices/non-eligible/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateDiscount = (selectedDates, dateStr, instance) => {
      payment_date.value = moment(dateStr).format('DD MMM YYYY');
    };

    const requestFinance = async () => {
      can_submit.value = false;
      await axios
        .post(base_url + 'cash-planner/invoices/request/send', {
          invoice_id: invoice_details.value.id,
          payment_date: payment_date.value,
          drawdown_amount: drawdown_amount.value
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
        .post(base_url + 'cash-planner/invoices/request/multiple/send', formData)
        .then(response => {
          toast.success('Payment request(s) sent successfully');
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
            anchor: anchor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
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

        prime_bulk_max_date.value = new Date(max_date.value);

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
        new_text = new_text.replace('{buyerName}', invoice.program.anchor.name);
        new_text = new_text.replace('{anchorName}', invoice.program.anchor.name);
        new_text = new_text.replace('{company}', invoice.company.name);
        new_text = new_text.replace('{anchorCompanyUniqueID}', invoice.program.anchor.unique_identification_number);
        new_text = new_text.replace('{time}', moment().format('Do MMM YYYY'));
        new_text = new_text.replace('{agreementDate}', moment().format('Do MMM YYYY'));
        new_text = new_text.replace('{contract},', '');
        new_text = new_text.replace('{anchorAccountName},', '');
        new_text = new_text.replace('{anchorAccountNumber},', '');
        new_text = new_text.replace('{anchorCustomerId},', '');
        new_text = new_text.replace('{anchorBranch},', '');
        new_text = new_text.replace('{anchorIFSCCode},', '');
        new_text = new_text.replace('{anchorAddress},', '');
        new_text = new_text.replace('{penalnterestRate},', '');
        new_text = new_text.replace('{sellerName}', invoice.company.name);

        noa_text.value.body = new_text;
      }
    };

    var th = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

    var dg = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    var tn = [
      'Ten',
      'Eleven',
      'Twelve',
      'Thirteen',
      'Fourteen',
      'Fifteen',
      'Sixteen',
      'Seventeen',
      'Eighteen',
      'Nineteen'
    ];
    var tw = ['Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    function toWords(s) {
      s = s.toString();
      s = s.replace(/[\, ]/g, '');
      if (s != parseFloat(s)) return 'Not a Number';
      var x = s.indexOf('.');
      if (x == -1) x = s.length;
      if (x > 15) return 'Too Big';
      var n = s.split('');
      var str = '';
      var sk = 0;
      for (var i = 0; i < x; i++) {
        if ((x - i) % 3 == 2) {
          if (n[i] == '1') {
            str += tn[Number(n[i + 1])] + ' ';
            i++;
            sk = 1;
          } else if (n[i] != 0) {
            str += tw[n[i] - 2] + ' ';
            sk = 1;
          }
        } else if (n[i] != 0) {
          str += dg[n[i]] + ' ';
          if ((x - i) % 3 == 0) str += 'Hundred ';
          sk = 1;
        }
        if ((x - i) % 3 == 1) {
          if (sk) str += th[(x - i - 1) / 3] + ' ';
          sk = 0;
        }
      }
      if (x != s.length) {
        var y = s.length;
        str += 'point ';
        for (var i = x + 1; i < y; i++) str += dg[n[i]] + ' ';
      }
      return str.replace(/\s+/g, ' ');
    }

    return {
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
      drawdown_amount,
      invoice_amount_in_words,
      drawdown_amount_in_words,

      bulk_invoice_remittance,

      resolvePaymentRequestStatus,

      formatAmount,

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
      anchor_search,
      invoice_number_search,
      from_date_search,
      to_date_search,
      date_search,
      per_page,

      formatNoa,

      prime_disabled_dates,
      prime_disabled_days,
      prime_max_date,
      prime_min_date,
      prime_bulk_max_date,

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
      showInvoice
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
