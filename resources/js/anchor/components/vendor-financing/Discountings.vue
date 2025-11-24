<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control mb-1 form-search"
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
          <input type="text" id="date_search" class="form-control form-search" name="daterange" placeholder="Select Dates" />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Invoice Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="submitted">{{ $t('Pending') }}</option>
            <option value="approved">{{ $t('Approved') }}</option>
            <option value="denied">{{ $t('Denied') }}</option>
          </select>
        </div>
        <div class=" table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class=" table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex align-items-end">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" @click="exportReport" class="btn btn-primary mx-1">
          <i class="ti ti-download ti-xs px-1"></i> Excel
        </button>
        <button type="button" @click="exportPdfReport" class="btn btn-primary">
          <i class="ti ti-download ti-xs px-1"></i> PDF
        </button>
      </div>
    </div>
    <pagination
      v-if="report.meta"
      :from="report.meta.from"
      :to="report.meta.to"
      :links="report.meta.links"
      :next_page="report.links.next"
      :prev_page="report.links.prev"
      :total_items="report.meta.total"
      :first_page_url="report.links.first"
      :last_page_url="report.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Invoice No') }}.</th>
            <th>{{ $t('Amount') }}</th>
            <th>{{ $t('Disbursement Date') }}</th>
            <th>{{ $t('Transaction Ref') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!report.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="report.data && report.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="invoice in report.data" :key="invoice.id">
            <td class="">{{ invoice.company }}</td>
            <td
              class="text-primary text-decoration-underline"
              @click="showInvoice(invoice.id, 'show-details-btn-'+invoice.id)"
              style="cursor: pointer"
            >
              {{ invoice.invoice_number }}
            </td>
            <td class="text-success">
              {{ invoice.currency }}
              <span>
              {{
                new Intl.NumberFormat().format(
                  invoice.invoice_total_amount
                )
              }}
              </span>
            </td>
            <td v-if="invoice.disbursement_date">{{ moment(invoice.disbursement_date).format('DD MMM YYYY') }}</td>
            <td v-else>-</td>
            <td v-if="invoice.transaction_ref">
              {{
                invoice.transaction_ref
              }}
            </td>
            <td v-else>-</td>
            <td>
              <button
                class="d-none"
                :id="'show-details-btn-'+invoice.id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + invoice.id">
              </button>
              <button
                class="d-none"
                :id="'show-pi-btn-'+invoice.id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + invoice.id">
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
                        <img src="../../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal fade" v-if="invoice_details" :id="'invoice-' + invoice.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
                        <a :href="'../invoices/' + invoice_details.id + '/pdf/download'" class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex justify-content-between">
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto" v-if="invoice_details.buyer">{{ $t('Buyer') }}:</h5>
                            <h5 class="fw-light my-auto" v-else>{{ $t('Dealer') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto text-end" v-if="invoice_details.buyer">
                              {{ invoice_details.buyer.name }}
                            </h6>
                            <h6 class="fw-bold mx-2 my-auto text-end" v-else>
                              {{ invoice_details.company.name }}
                            </h6>
                          </span>
                          <span class="d-flex">
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
                          <span class="d-flex justify-content-between" v-if="invoice_details.pi_number">
                            <h5 class="my-auto fw-light">{{ $t('PI No') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto text-primary pointer" data-bs-toggle="modal" :data-bs-target="'#pi-'+invoice_details.id">{{ invoice_details.pi_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice/Unique Ref No') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.invoice_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto" v-if="!invoice_details.total_amount">
                              {{ invoice_details.currency }}
                              {{ new Intl.NumberFormat().format(invoice_details.total + invoice_details.total_invoice_taxes) }}
                            </h6>
                            <h6 class="fw-bold text-success mx-2 my-auto" v-else>
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total_amount) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Due Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ moment(invoice_details.due_date).format('DD MMM YYYY') }}</h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive" v-if="invoice_details.invoice_items.length > 0">
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
                            <tr v-for="item in invoice_details.invoice_items" :key="item.id">
                              <td>{{ item.item }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity) }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.price_per_quantity) }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div v-if="invoice_details.invoice_discounts.length > 0" class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Total Invoice Discount') }}</h6>
                          <h5 class="text-success my-auto">
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total_invoice_discount) }}
                          </h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_taxes.length > 0" class="px-2">
                        <span v-for="tax in invoice_details.invoice_taxes" :key="tax.id" class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
                          <h5 class="text-success my-auto">
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(tax.value) }}
                          </h5>
                        </span>
                      </div>
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1" v-if="!invoice_details.drawdown_amount">{{ $t('Total') }}</h6>
                          <h6 class="mx-2 my-auto py-1" v-else>{{ $t('Total Drawdown Amount') }}</h6>
                          <h5 class="text-success my-auto py-1" v-if="!invoice_details.drawdown_amount">
                            {{
                              new Intl.NumberFormat().format(
                                invoice_details.total +
                                  invoice_details.total_invoice_taxes -
                                  invoice_details.total_invoice_discount
                              )
                            }}
                          </h5>
                          <h5 class="text-success my-auto py-1" v-else>
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.drawdown_amount) }}
                          </h5>
                        </span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <a
                        v-if="
                          !invoice_details.user_has_approved && (invoice_details.status == 'pending' || invoice_details.status == 'created')
                        "
                        href="javascript:;"
                        @click="requestApproval(invoice)"
                        class="btn btn-primary"
                        >{{ $t('Approve') }}</a
                      >
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal fade" v-if="invoice_details" :id="'pi-' + invoice.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
                        <a :href="'../invoices/payment-instruction/' + invoice_details.id + '/pdf/download'" class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex justify-content-between">
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto" v-if="invoice_details.buyer">{{ $t('Buyer') }}:</h5>
                            <h5 class="fw-light my-auto" v-else>{{ $t('Dealer') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto text-end" v-if="invoice_details.buyer">
                              {{ invoice_details.buyer.name }}
                            </h6>
                            <h6 class="fw-bold mx-2 my-auto text-end" v-else>
                              {{ invoice_details.company.name }}
                            </h6>
                          </span>
                          <span class="d-flex">
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
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice/Unique Ref No') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto text-primary pointer" data-bs-toggle="modal" :data-bs-target="'#invoice-'+invoice_details.id">{{ invoice_details.invoice_number }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto" v-if="!invoice_details.total_amount">
                              {{ invoice_details.currency }}
                              {{ new Intl.NumberFormat().format(invoice_details.total + invoice_details.total_invoice_taxes) }}
                            </h6>
                            <h6 class="fw-bold text-success mx-2 my-auto" v-else>
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total_amount) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Due Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ moment(invoice_details.due_date).format('DD MMM YYYY') }}</h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive" v-if="invoice_details.invoice_items.length > 0">
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
                            <tr v-for="item in invoice_details.invoice_items" :key="item.id">
                              <td>{{ item.item }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity) }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.price_per_quantity) }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div v-if="invoice_details.invoice_discounts.length > 0" class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Total Invoice Discount') }}</h6>
                          <h5 class="text-success my-auto">
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total_invoice_discount) }}
                          </h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_taxes.length > 0" class="px-2">
                        <span v-for="tax in invoice_details.invoice_taxes" :key="tax.id" class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
                          <h5 class="text-success my-auto">
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(tax.value) }}
                          </h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_fees.length > 0" class="px-2">
                        <span v-for="fee in invoice_details.invoice_fees" :key="fee.id" class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                          <h5 class="text-success my-auto py-1" v-if="fee.name != 'Credit Note Amount'">
                            ({{
                              getTaxPercentage(
                                invoice_details.total + invoice_details.total_invoice_taxes - invoice_details.total_invoice_discount,
                                fee.amount
                              )
                            }}%) {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(fee.amount.toFixed(2)) }}
                          </h5>
                          <h5 class="text-success my-auto py-1" v-else>{{ fee.amount }}</h5>
                        </span>
                      </div>
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1" v-if="!invoice_details.drawdown_amount">{{ $t('Total') }}</h6>
                          <h6 class="mx-2 my-auto py-1" v-else>{{ $t('Total Drawdown Amount') }}</h6>
                          <h5 class="text-success my-auto py-1" v-if="!invoice_details.drawdown_amount">
                            {{
                              new Intl.NumberFormat().format(
                                invoice_details.total +
                                  invoice_details.total_invoice_taxes -
                                  invoice_details.total_invoice_fees -
                                  invoice_details.total_invoice_discount
                              )
                            }}
                          </h5>
                          <h5 class="text-success my-auto py-1" v-else>
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.drawdown_amount) }}
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
      v-if="report.meta"
      :from="report.meta.from"
      :to="report.meta.to"
      :links="report.meta.links"
      :next_page="report.links.next"
      :prev_page="report.links.prev"
      :total_items="report.meta.total"
      :first_page_url="report.links.first"
      :last_page_url="report.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, onMounted, inject, nextTick } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'DiscountsReport',
  components: {
    Pagination
  },
  setup(props, context) {
    const base_url = inject('baseURL')
    const per_page = ref(50);
    const vendor_search = ref('');
    const invoice_number_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const status_search = ref('');
    const financing_status_search = ref('');
    const date_search = ref('')
    const type = ref('discountings');
    const report = ref([]);

    const invoice_details = ref(null)

    const getReport = () => {
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click()
      await axios.get('../invoices/'+invoice+'/details')
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2)
    }

    onMounted(() => {
      getReport()
    })

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
          style = 'bg-label-warning';
          break;
        case 'Pending Checker':
          style = 'bg-label-warning';
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
        case 'Closed':
          style = 'bg-label-secondary';
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
        case 'submitted':
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

    const filter = () => {
      date_search.value = $('#date_search').val()
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD')
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD')
      }
      let parent = $('.ti-search').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value,
            invoice_number: invoice_number_search.value,
            vendor_search: vendor_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>')
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent()
      parent.html('<img src="../../../../../assets/img/spinner.svg" />')
      invoice_number_search.value = '';
      vendor_search.value = 0;
      from_date_search.value = '';
      to_date_search.value = '';
      status_search.value = '';
      financing_status_search.value = '';
      date_search.value = ''
      $('#date_search').val('')
      axios
        .get('report', {
          params: {
            per_page: per_page.value,
            type: type.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>')
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: type.value,
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            vendor_search: vendor_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value
          }
        })
        .then(response => {
          report.value = response.data;
        })
        .catch(error => {
          console.log(error);
        });
    };

    const exportReport = () => {
      axios
        .get(base_url + 'reports/report/discountings/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            vendor_search: vendor_search.value,
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
          fileLink.setAttribute('download', `Discountings_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportPdfReport = () => {
      axios
        .get(base_url + 'reports/discountings/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            invoice_number: invoice_number_search.value,
            vendor_search: vendor_search.value,
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
          fileLink.setAttribute('download', `Discountings_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      report,
      per_page,
      // Search Params
      vendor_search,
      invoice_number_search,
      status_search,
      financing_status_search,
      from_date_search,
      to_date_search,
      invoice_details,
      resolveStatus,
      resolveFinancingStatus,
      changePage,
      filter,
      refresh,
      exportReport,
      exportPdfReport,
      showInvoice,
      getTaxPercentage,
    };
  }
};
</script>
<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
