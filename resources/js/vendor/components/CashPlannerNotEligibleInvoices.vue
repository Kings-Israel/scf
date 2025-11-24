<template>
  <div class="card">
    <div class="card-header">
      <h6>Payments Instructions Not Eligible for Financing</h6>
    </div>
    <div class="card-body">
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
        <table class="table">
          <thead>
            <tr class="">
              <th>{{$t('Invoice No')}}.</th>
              <th>{{$t('Anchor')}}</th>
              <th>{{$t('Due Date')}}</th>
              <th>{{$t('Invoice Amount')}}</th>
              <th>{{$t('PI Amount')}}</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
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
              <td class="text-decoration-underline text-primary" @click="showInvoice(invoice.id, 'show-planner-pi-' + invoice.id)">
                <a href="javascript:;" class="dropdown-item">{{ invoice.invoice_number }}</a>
              </td>
              <td class="">{{ invoice.anchor }}</td>
              <td class="">{{ moment(invoice.due_date).format('D MMM YYYY') }}</td>
              <td class="text-success text-nowrap">{{ invoice.currency }} {{ new Intl.NumberFormat().format((invoice.invoice_total_amount).toFixed(2)) }}</td>
              <td class="text-success text-nowrap">{{ invoice.currency }} {{ new Intl.NumberFormat().format((invoice.eligible_for_finance).toFixed(2)) }}</td>
              <td>
                <button
                  class="d-none"
                  :id="'show-planner-pi-' + invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#cash-planner-pi-' + invoice.id">
                </button>
                <button
                  class="d-none"
                  :id="'show-request-financing-btn-'+invoice.id"
                  data-bs-toggle="modal"
                  :data-bs-target="'#requestFinancing-' + invoice.id">
                </button>
                <button
                  class="d-none"
                  id="loading-modal-btn"
                  data-bs-toggle="modal"
                  data-bs-target="#loading-modal">
                </button>
                <div class="modal fade" id="loading-modal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                      <div class="modal-body">
                        <div class="d-flex justify-content-center">
                          <span>{{ $t('Loading') }}...</span>
                          <img src="../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
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
                :id="'cash-planner-pi-' + invoice.id"
                tabindex="-1"
                aria-hidden="true"
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
                          <span
                            class="d-flex justify-content-end"
                          >
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
                          <span
                            class="d-flex justify-content-end"
                          >
                            <h6 class="mx-2 my-auto py-1">{{ $t('WHT Tax') }}:</h6>
                            <h5
                              class="text-success my-auto py-1">
                              (0.0%) 0
                            </h5>
                          </span>
                          <span
                            class="d-flex justify-content-end"
                          >
                            <h6 class="mx-2 my-auto py-1">{{ $t('WHT VAT') }}:</h6>
                            <h5
                              class="text-success my-auto py-1">
                              (0.0%) 0
                            </h5>
                          </span>
                          <span
                            class="d-flex justify-content-end"
                          >
                            <h6 class="mx-2 my-auto py-1">{{ $t('Credit Note Amount') }}:</h6>
                            <h5
                              class="text-success my-auto py-1">
                              0
                            </h5>
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
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject, nextTick } from 'vue'
import axios from 'axios'
// Notification
import { useToast } from 'vue-toastification'
import moment from 'moment'
import Pagination from './partials/Pagination.vue'
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

export default {
  name: "EligibleInvoices",
  components: {
    Pagination,
    flatPickr
  },
  setup() {
    const toast = useToast()
    const invoices = ref([])
    const invoice_details = ref(null)
    const eligibility_amount = ref(0)
    const discount_amount = ref(0)
    const min_date = ref(new Date().toLocaleDateString('en-CA'))
    const max_date = ref('')
    const payment_date = ref(moment().format('DD MMM YYYY'))
    const request_payment_date = new Date().toLocaleDateString('en-CA')
    const processing_fees = ref(1)
    const processing_fee_amount = ref(0)
    const days_to_payment = ref(1)
    const total_remittance = ref(0)
    const invoice_total_amount = ref(0)
    const invoice_taxes_amount = ref(0)
    const invoice_fees_amount = ref(0)
    const invoice_discounts_amount = ref(0)
    const invoice_pi_amount = ref(0)
    const invoice_eligibility_percentage = ref(0)
    const invoice_eligibility_amount = ref(0)
    const invoice_eligibility_for_finance = ref(0)
    const invoice_discount_amount = ref(0)
    const invoice_days_to_payment = ref(0)
    const invoice_business_spread = ref(0)
    const invoice_processing_fee = ref(0)
    const invoice_total_remmitance = ref(0)
    const invoice_min_financing_days = ref(0)
    const invoice_due_date = ref(0)
    const payment_accounts = ref([])
    const eligibility = ref(0)
    const pi_amount = ref(0)
    const taxes_amount = ref(0)
    const business_spread = ref(0)
    const credit_to = ref('')
    const payment_account = ref(false)
    const viewRequestDetails = ref(false)

    const total_invoice_amount = ref(0)
    const total_pi_amount = ref(0)
    const total_eligible_amount = ref(0)

    const can_submit = ref(false)

    const selected_invoices = ref([])

    const show_multiple_request_modal = ref(false)

    const show_terms_button = ref('')

    const base_url = inject('baseURL')

    const config = ref({
      inline: true,
    });

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click()
      await axios.get(base_url + 'invoices/'+invoice+'/details')
        .then(response => {
          invoice_details.value = response.data;
          nextTick(() => {
            document.getElementById(modal).click()
          })
        })
        .catch(err => {
          console.log(err)
        })
    }

    const showModal = (invoice) => {
      let discount_amount = 0

      invoice_details.value = invoice

      invoice_due_date.value = invoice.due_date

      invoice_total_amount.value = 0
      invoice.invoice_items.forEach(item => {
        invoice_total_amount.value += item.quantity * item.price_per_quantity
      });

      invoice_taxes_amount.value = 0
      invoice.invoice_taxes.forEach(item => {
        invoice_taxes_amount.value += item.value
      })

      invoice_fees_amount.value = 0
      invoice.invoice_fees.forEach(item => {
        invoice_fees_amount.value += item.amount
      })

      invoice_discounts_amount.value = 0
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          discount_amount += (item.value / 100) * (invoice_total_amount.value + invoice_taxes_amount.value)
        } else {
          discount_amount += item.value
        }
      })

      // invoice_total_amount.value = invoice_total_amount.value + invoice_taxes_amount.value - discount_amount
      invoice_total_amount.value = invoice.total + invoice.total_taxes - invoice.total_discount

      // invoice_pi_amount.value = invoice_total_amount.value - invoice_fees_amount.value
      invoice_pi_amount.value = invoice.total + invoice.total_taxes - invoice.total_fees - invoice.total_discount

      invoice_days_to_payment.value = moment(invoice.due_date).diff(moment(), 'days') + 1

      invoice_eligibility_percentage.value = invoice.vendor_configurations.eligibility

      invoice_eligibility_amount.value = invoice.total + invoice.total_taxes - invoice.total_fees - invoice.total_discount

      invoice_eligibility_for_finance.value = (invoice_eligibility_percentage.value / 100) * (invoice.total + invoice.total_taxes - invoice.total_fees - invoice.total_discount)

      invoice_business_spread.value = invoice.vendor_discount_details.total_roi

      invoice_discount_amount.value = (((invoice_eligibility_percentage.value / 100) * invoice_eligibility_amount.value) * (invoice_business_spread.value / 100) * ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365))

      invoice_processing_fee.value = (processing_fees.value / 100) * invoice_eligibility_amount.value

      invoice_total_remmitance.value = invoice_eligibility_amount.value - invoice_processing_fee.value - invoice_discount_amount.value

      invoice_min_financing_days.value = invoice.program.min_financing_days

      max_date.value = moment(invoice.due_date).subtract(invoice_min_financing_days.value, 'days').format('DD MMM YYYY')

      config.value = {
        wrap: false, // set wrap to true only when using 'input-group'
        altInput: true,
        dateFormat: 'd M Y',
        locale: {
          "firstDayOfWeek": 1
        },
        minDate: 'today',
        maxDate: max_date.value,
        "disable": [
          function(date) {
            return (date.getDay() === 0 || date.getDay() === 6);  // disable weekends
          }
        ],
      }

      viewRequestDetails.value = true
    }

    const showMultipleRequestModal = () => {
      if (selected_invoices.value.length <= 0) {
        toast.error('Select Payment Instructions')
        return false
      }

      show_multiple_request_modal.value.click()
    }

    const resolvePaymentRequestStatus = (status) => {
      let style = ''
      switch (status) {
        case 'created':
          style = 'bg-label-primary'
          break;
        case 'paid':
          style = 'bg-label-success'
          break;
        case 'failed':
          style = 'bg-label-danger'
          break;
        default:
          style = 'bg-label-primary'
          break;
      }

      return style
    }

    const getInvoices = async () => {
      await axios.get(base_url+'cash-planner/invoices/non-eligible/data')
        .then(response => {
          invoices.value = response.data.invoices
        })
        .catch(err => {
          console.log(err)
        })
    }

    const getLegibleAmount = (invoice) => {
      let eligibility = invoice.vendor_configurations.eligibility
      let deducts = 0
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount
      })
      let taxes = 0
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value
      })
      let invoice_amount = getTotalAmount(invoice.invoice_items) + taxes
      let invoice_discounts = 0
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discounts += (item.value / 100) * invoice_amount
        } else {
          invoice_discounts += item.value
        }
      })

      let total_amount = ((invoice_amount - invoice_discounts) - deducts)
      // let total_spread = invoice.vendor_discount_details.total_roi
      // let discount = (((eligibility / 100) * total_amount) * (total_spread / 100) * ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365))
      return (((eligibility / 100) * total_amount)).toFixed(2)
    }

    const calculateEligibility = (invoice) => {
      let eligibility = invoice.vendor_configurations.eligibility
      let invoice_amount = getTotalAmount(invoice.invoice_items)
      let deducts = 0
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount
      })
      let taxes = 0
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value
      })
      invoice_amount = invoice_amount + taxes
      let invoice_discounts = 0
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discounts += (item.value / 100) * invoice_amount
        } else {
          invoice_discounts += item.value
        }
      })
      invoice_amount = invoice_amount - invoice_discounts
      eligibility_amount.value = (eligibility / 100) * (invoice_amount - deducts)
      return eligibility_amount.value.toFixed(2)
    }

    const calculatePiAmount = (invoice) => {
      let deducts = 0
      if (invoice.invoice_fees.length > 0) {
        invoice.invoice_fees.forEach(item => {
          deducts += item.amount
        })
      }
      return deducts
    }

    const calculateTaxesAmount = (invoice) => {
      let taxes = 0
      if (invoice.invoice_taxes.length > 0) {
        invoice.invoice_taxes.forEach(item => {
          taxes += item.value
        })
      }
      return taxes
    }

    const getDiscountAmount = (invoice) => {
      let discount_amount = 0
      let total_amount = 0
      let taxes_amount = 0
      invoice.invoice_items.forEach(item => {
        total_amount += item.quantity * item.price_per_quantity
      });

      invoice.invoice_taxes.forEach(item => {
        taxes_amount += item.value
      })
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          discount_amount += (item.value / 100) * (total_amount + taxes_amount)
        } else {
          discount_amount += item.value
        }
      });

      return discount_amount
    }

    const getDaysToPayment = (invoice) => {
      days_to_payment.value = moment(invoice.due_date).diff(moment(), 'days') + 1

      return days_to_payment.value
    }

    const calculateDiscount = (invoice) => {
      invoice_details.value = invoice
      let deducts = 0
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount
      })
      let taxes = 0
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value
      })

      eligibility.value = invoice.vendor_configurations.eligibility

      let total_amount = getTotalAmount(invoice.invoice_items)

      let invoice_discount_amount = 0
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discount_amount += (item.value / 100) * (total_amount + taxes)
        } else {
          invoice_discount_amount += item.value
        }
      });

      let legible_amount = total_amount + taxes - invoice_discount_amount - deducts
      business_spread.value = invoice.vendor_discount_details.total_roi
      discount_amount.value = (((eligibility.value / 100) * legible_amount) * (business_spread.value / 100) * ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365))
      return discount_amount.value.toFixed(2)
    }

    const calculateProcessingFee = (invoice) => {
      let deducts = 0
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount
      })
      let taxes = 0
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value
      })

      let total_amount = getTotalAmount(invoice_details.value.invoice_items)

      let invoice_discount_amount = 0
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discount_amount += (item.value / 100) * (total_amount + taxes)
        } else {
          invoice_discount_amount += item.value
        }
      });

      let legible_amount = (eligibility.value / 100) * (total_amount + taxes - invoice_discount_amount - deducts)
      processing_fee_amount.value = (processing_fees.value / 100) * legible_amount
      return processing_fee_amount.value
    }

    const calculateRemittance = (invoice) => {
      let eligibility = invoice.vendor_configurations.eligibility
      let total_amount = getTotalAmount(invoice.invoice_items)
      let deducts = 0
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount
      })
      let taxes = 0
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value
      })

      let invoice_discount_amount = 0
      invoice.invoice_discounts.forEach(item => {
        if (item.type == 'percentage') {
          invoice_discount_amount += (item.value / 100) * (total_amount + taxes)
        } else {
          invoice_discount_amount += item.value
        }
      });

      let legible_amount = (eligibility / 100) * (total_amount + taxes - invoice_discount_amount - deducts)

      let processing_fee =  (processing_fees.value / 100) * legible_amount
      let discount = (legible_amount * (invoice.vendor_discount_details.total_roi / 100) * ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365))
      total_remittance.value = legible_amount - processing_fee - discount
      return total_remittance.value.toFixed(2)
    }

    const getTotalAmount = (invoice_items) => {
      let amount = 0
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity
      });
      return amount
    }

    const requestFinance = async () => {
      if (credit_to.value == "") {
        toast.error('Select Payment Account')
        payment_account.value = true
        return
      }
      can_submit.value = false
      await axios.post('cash-planner/invoices/request/send', {
        'invoice_id': invoice_details.value.id,
        'payment_request_date': payment_date.value,
        'credit_to': credit_to.value
      })
      .then(response => {
        toast.success('Payment request sent successfully')

        setTimeout(() => {
          window.location.reload()
        }, 3000)
      })
      .catch(err => {
        console.log(err)
        can_submit.value = true
        toast.error('An error. occurred. Refresh and try again.')
      })
    }

    const requestFinanceForMultiple = async () => {
      can_submit.value = false
      const formData = new FormData
      selected_invoices.value.forEach(selected => {
        formData.append('invoices[]', selected)
      })
      formData.append('payment_request_date', payment_date.value)
      await axios.post('cash-planner/invoices/request/multiple/send', formData)
      .then(response => {
        toast.success('Payment requests sent successfully')

        setTimeout(() => {
          window.location.reload()
        }, 3000)
      })
      .catch(err => {
        console.log(err)
        can_submit.value = true
        toast.error('An error. occurred. Refresh and try again.')
      })
    }

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2)
    }

    onMounted(() => {
      getInvoices()
    })

    const changePage = async (page) => {
      await axios.get(page)
        .then(response => {
          invoices.value = response.data.invoices
        })
    }

    return {
      config,
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
      credit_to,
      taxes_amount,

      payment_account,

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

      resolvePaymentRequestStatus,

      changePage,

      invoice_details,
      getTaxPercentage,
      invoice_details,
      showInvoice,
    }
  }
}
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
/* .flatpickr-months {
  display: none !important;
} */
.light-style .flatpickr-calendar {
  width: 100% !important;
}
.flatpickr-innerContainer {
  width: 100% !important;
  padding: 0 !important;
}
.light-style .flatpickr-days {
  width: 100% !important;
}
</style>
