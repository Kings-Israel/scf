<template>
  <div class="card">
    <div class="card-header">
      <h6>Payment Instructions eligible for Financing (from YoFinvoice)</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr class="">
              <th>Invoice No.</th>
              <th>Invoice Date</th>
              <th>P.O Number</th>
              <th>Invoice Amount</th>
              <th>Due Date</th>
              <th>PI Amount</th>
              <th>Eligible Amount</th>
              <th>Progress Status</th>
              <th>Pay Me Early</th>
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
              <td class="text-decoration-underline text-primary">{{ invoice.invoice_number }}</td>
              <td class="">{{ moment(invoice.invoice_date).format('D MMM YYYY') }}</td>
              <td class="">-</td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>{{
                  new Intl.NumberFormat().format(getTotalAmount(invoice.invoice_items) + calculateTaxesAmount(invoice))
                }}</span>
              </td>
              <td class="">{{ moment(invoice.due_date).format('D MMM YYYY') }}</td>
              <td class="text-success">
                {{ invoice.currency }}
                {{ getTotalAmount(invoice.invoice_items) + calculateTaxesAmount(invoice) - calculatePiAmount(invoice) }}
              </td>
              <td class="text-success">
                {{ invoice.currency }} {{ new Intl.NumberFormat().format(getLegibleAmount(invoice)) }}
              </td>
              <td class="">
                <span
                  v-if="invoice.payment_requests"
                  class="badge me-1 m_title"
                  :class="resolvePaymentRequestStatus(invoice.payment_requests[0].status)"
                  >{{ invoice.payment_requests[0].status }}</span
                >
                <span v-else>-</span>
              </td>
              <td class="text-success">
                <i
                  v-if="!invoice.payment_requests"
                  class="ti ti-coin ti-sm"
                  data-bs-toggle="modal"
                  :data-bs-target="'#requestFinancing-' + invoice.id"
                  style="cursor: pointer"
                  @click="showModal(invoice)"
                ></i>
                <!-- <i v-if="!invoice.payment_request" class='ti ti-coin ti-sm' style="cursor: pointer" @click="showModal(invoice)"></i> -->
                <div class="modal fade" :id="'requestFinancing-' + invoice.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">Request Finance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <h5>Step 1: Review Invoice Details</h5>
                        <div class="row">
                          <div class="col-6">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Invoice Amount</label>
                              <h6 class="text-success">
                                {{ invoice.currency }} {{ new Intl.NumberFormat().format(invoice_total_amount) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">PI Amount(A)</label>
                              <h6 class="text-success">
                                {{ invoice.currency }} {{ new Intl.NumberFormat().format(invoice_eligibility_amount) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Eligible Amount</label>
                              <h6 class="text-success">
                                {{ invoice.currency }} {{ new Intl.NumberFormat().format(invoice_eligibility_amount) }}
                              </h6>
                            </div>
                          </div>
                          <div class="col-6">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Due Date</label>
                              <h6 class="" v-if="invoice_details">
                                {{ moment(invoice_due_date).format('DD MMM YYYY') }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Eligibility</label>
                              <h6 class="" v-if="invoice_details">{{ invoice_eligibility_percentage }}%</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Eligible For Finance</label>
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(invoice_eligibility_for_finance) }}
                              </h6>
                            </div>
                          </div>
                        </div>
                        <h5 class="mt-1">Step 2: Select Early Payment Date</h5>
                        <div class="row">
                          <div class="col-6">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Early Payment Date</label>
                              <h6 class="">{{ payment_date }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Days to Payment</label>
                              <h6 class="">{{ invoice_days_to_payment }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Annual Discount Rate(%)</label>
                              <h6 class="" v-if="invoice_details">{{ invoice_business_spread }}%</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Processing Fees <strong>(C)</strong></label>
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(invoice_processing_fee.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Discount Amount <strong>(D)</strong></label>
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(Math.abs(invoice_discount_amount).toFixed(2)) }}
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
                          </div>
                        </div>
                        <h5 class="mt-1">Step 3: Review Offer and Submit Request</h5>
                        <div class="row">
                          <div class="col-12">
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Early Payment Date</label>
                              <h6 class="" id="payment_date">{{ payment_date }}</h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Eligible Amount</label>
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(invoice_eligibility_amount.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Request Amount <strong>(B)</strong></label>
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(invoice_eligibility_amount.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label"
                                >Actual Remittance Amount <strong>(B-C-D)</strong></label
                              >
                              <h6 class="text-success">
                                {{ invoice.currency }}
                                {{ new Intl.NumberFormat().format(invoice_total_remmitance.toFixed(2)) }}
                              </h6>
                            </div>
                            <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Credit To</label>
                              <select
                                v-if="invoice_details"
                                class="form-select w-50 my-2"
                                :class="payment_account ? 'border-danger' : ''"
                                v-model="credit_to"
                                id="account_number"
                                name="account_number"
                              >
                                <option value="">Select Payment Account</option>
                                <option
                                  v-for="account in invoice_details.vendor_bank_details"
                                  :key="account.id"
                                  :value="account.account_number"
                                >
                                  {{ account.account_number }}
                                </option>
                              </select>
                            </div>
                            <!-- <div class="border-bottom d-flex justify-content-between">
                              <label for="nameWithTitle" class="form-label">Balance Invoice Amount Paid on Maturity</label>
                              <h6 class="text-success">Ksh 0</h6>
                            </div> -->
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="requestFinance">Submit</button>
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
        :from="invoices.from"
        :to="invoices.to"
        :links="invoices.links"
        :next_page="invoices.next_page_url"
        :prev_page="invoices.prev_page_url"
        :total_items="invoices.total"
        :first_page_url="invoices.first_page_url"
        :last_page_url="invoices.last_page_url"
        @change-page="changePage"
      ></pagination>
      <!-- Modal -->
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref, watch, inject } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from '../partials/Pagination.vue';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

export default {
  name: 'EligibleInvoices',
  components: {
    Pagination,
    flatPickr
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);
    const invoice_details = ref({});
    const eligibility_amount = ref(0);
    const discount_amount = ref(0);
    const min_date = ref(new Date().toLocaleDateString('en-CA'));
    const max_date = ref('');
    const payment_date = ref(moment().format('DD MMM YYYY'));
    const request_payment_date = new Date().toLocaleDateString('en-CA');
    const processing_fees = ref(1);
    const processing_fee_amount = ref(0);
    const days_to_payment = ref(1);
    const total_remittance = ref(0);
    const invoice_total_amount = ref(0);
    const invoice_taxes_amount = ref(0);
    const invoice_fees_amount = ref(0);
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

    const config = ref({});

    const showModal = invoice => {
      invoice_details.value = invoice;

      invoice_due_date.value = invoice.due_date;

      invoice_total_amount.value = 0;
      invoice.invoice_items.forEach(item => {
        invoice_total_amount.value += item.quantity * item.price_per_quantity;
      });

      invoice_taxes_amount.value = 0;
      invoice.invoice_taxes.forEach(item => {
        invoice_taxes_amount.value += item.value;
      });

      invoice_fees_amount.value = 0;
      invoice.invoice_fees.forEach(item => {
        invoice_fees_amount.value += item.amount;
      });

      invoice_pi_amount.value = invoice_total_amount.value + invoice_taxes_amount.value - invoice_fees_amount.value;

      invoice_days_to_payment.value = moment(invoice.due_date).diff(moment(), 'days') + 1;

      invoice_eligibility_percentage.value = invoice.vendor_configurations.eligibility;

      invoice_eligibility_amount.value =
        invoice_total_amount.value + invoice_taxes_amount.value - invoice_fees_amount.value;

      invoice_eligibility_for_finance.value =
        (invoice_eligibility_percentage.value / 100) * invoice_total_amount.value +
        invoice_taxes_amount.value -
        invoice_fees_amount.value;

      invoice_business_spread.value = invoice.vendor_discount_details.total_spread;

      invoice_discount_amount.value =
        (invoice_eligibility_percentage.value / 100) *
        invoice_eligibility_amount.value *
        (invoice_business_spread.value / 100) *
        ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365);

      invoice_processing_fee.value = (processing_fees.value / 100) * invoice_eligibility_amount.value;

      invoice_total_remmitance.value =
        invoice_eligibility_amount.value - invoice_processing_fee.value - invoice_discount_amount.value;

      invoice_min_financing_days.value = invoice.program.min_financing_days;

      max_date.value = moment(invoice.due_date)
        .subtract(invoice_min_financing_days.value, 'days')
        .format('DD MMM YYYY');

      config.value = {
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

      viewRequestDetails.value = true;
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
        .get(base_url + 'cash-planner/invoices/eligible/data')
        .then(response => {
          invoices.value = response.data.invoices;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateDiscount = (selectedDates, dateStr, instance) => {
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

      let invoice_amount = invoice_total_amount.value + invoice_taxes_amount.value - invoice_fees_amount.value;

      invoice_eligibility_amount.value = (invoice_eligibility_percentage.value / 100) * invoice_amount;

      invoice_processing_fee.value = (processing_fees.value / 100) * invoice_eligibility_amount.value;

      invoice_discount_amount.value =
        (invoice_eligibility_percentage.value / 100) *
        invoice_amount *
        (invoice_business_spread.value / 100) *
        (moment(dateStr).diff(moment(invoice_details.value.due_date), 'days') / 365);

      invoice_total_remmitance.value =
        invoice_eligibility_amount.value - invoice_processing_fee.value - invoice_discount_amount.value;

      payment_date.value = moment(dateStr).format('DD MMM YYYY');
    };

    const getLegibleAmount = invoice => {
      let eligibility = invoice.vendor_configurations.eligibility;
      let total_amount = getTotalAmount(invoice.invoice_items);
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });
      return (eligibility / 100) * (total_amount + taxes - deducts);
    };

    const calculateEligibility = invoice => {
      let eligibility = invoice.vendor_configurations.eligibility;
      let total_amount = getTotalAmount(invoice.invoice_items);
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });
      eligibility_amount.value = (eligibility / 100) * (total_amount + taxes - deducts);
      return eligibility_amount.value;
    };

    const calculatePiAmount = invoice => {
      let deducts = 0;
      if (invoice.invoice_fees.length > 0) {
        invoice.invoice_fees.forEach(item => {
          deducts += item.amount;
        });
      }
      return deducts;
    };

    const calculateTaxesAmount = invoice => {
      let taxes = 0;
      if (invoice.invoice_taxes.length > 0) {
        invoice.invoice_taxes.forEach(item => {
          taxes += item.value;
        });
      }
      return taxes;
    };

    const getDaysToPayment = invoice => {
      days_to_payment.value = moment(invoice.due_date).diff(moment(), 'days') + 1;

      return days_to_payment.value;
    };

    const calculateDiscount = invoice => {
      invoice_details.value = invoice;
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });
      eligibility.value = invoice.vendor_configurations.eligibility;
      let total_amount = getTotalAmount(invoice.invoice_items);
      let legible_amount = total_amount + taxes - deducts;
      business_spread.value = invoice.vendor_discount_details.total_spread;
      discount_amount.value =
        (eligibility.value / 100) *
        legible_amount *
        (business_spread.value / 100) *
        ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365);
      return discount_amount.value.toFixed(2);
    };

    const calculateProcessingFee = invoice => {
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });
      let total_amount = getTotalAmount(invoice_details.value.invoice_items);
      let legible_amount = (eligibility.value / 100) * (total_amount + taxes - deducts);
      processing_fee_amount.value = (processing_fees.value / 100) * legible_amount;
      return processing_fee_amount.value;
    };

    const calculateRemittance = invoice => {
      let eligibility = invoice.vendor_configurations.eligibility;
      let total_amount = getTotalAmount(invoice.invoice_items);
      let deducts = 0;
      invoice.invoice_fees.forEach(item => {
        deducts += item.amount;
      });
      let taxes = 0;
      invoice.invoice_taxes.forEach(item => {
        taxes += item.value;
      });
      let legible_amount = (eligibility / 100) * (total_amount + taxes - deducts);
      let processing_fee = (processing_fees.value / 100) * legible_amount;
      let discount =
        legible_amount *
        (invoice.vendor_discount_details.total_spread / 100) *
        ((moment(invoice.due_date).diff(moment(), 'days') + 1) / 365);
      total_remittance.value = legible_amount - processing_fee - discount;
      return total_remittance.value.toFixed(2);
    };

    const getTotalAmount = invoice_items => {
      let amount = 0;
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity;
      });
      return amount;
    };

    const requestFinance = async () => {
      if (credit_to.value == '') {
        toast.error('Select Payment Account');
        payment_account.value = true;
        return;
      }

      await axios
        .post(base_url + 'cash-planner/invoices/request/send', {
          invoice_id: invoice_details.value.id,
          payment_request_date: payment_date.value,
          credit_to: credit_to.value
        })
        .then(response => {
          toast.success('Payment request sent successfully');
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error. occurred. Refresh and try again.');
        });
    };

    onMounted(() => {
      getInvoices();
    });

    const changePage = async page => {
      await axios.get(page).then(response => {
        invoices.value = response.data.invoices;
      });
    };

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

      getTotalAmount,
      getLegibleAmount,
      calculateEligibility,
      calculateRemittance,
      calculateDiscount,
      calculateProcessingFee,
      calculateTaxesAmount,
      calculatePiAmount,
      getDaysToPayment,
      changePage,
      updateDiscount,
      showModal,

      requestFinance,
      invoice_details,
      viewRequestDetails
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
