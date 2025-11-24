<template>
  <div class="card p-3">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
          <input
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Invoice Number"
            v-model="invoice_number_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
          <input
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Dealer"
            v-model="vendor_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
          <input
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Anchor"
            v-model="anchor_search"
            aria-describedby="defaultFormControlHelp"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="From" class="form-label">{{ $t('From') }} ({{ $t('Rejection Date') }})</label>
          <input type="date" class="form-control form-search" v-model="from_date" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="To" class="form-label">{{ $t('From') }} ({{ $t('Rejection Date') }})</label>
          <input type="date" class="form-control form-search" v-model="to_date" v-on:keyup.enter="filter" />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Sort By') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="sort_by">
            <option value="">{{ $t('Sort By') }}</option>
            <option value="ASC">{{ $t('Ascending') }}</option>
            <option value="DESC">{{ $t('Descending') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex gap-1 justify-content-md-end mt-2 mt-md-auto">
        <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <div class="">
          <download-buttons
            @download-report="downloadReport"
            @download-pdf="downloadPdf"
            :downloading-excel="downloadingExcel"
            :downloading-pdf="downloadingPdf"
          ></download-buttons>
        </div>
      </div>
    </div>
    <pagination
      v-if="requests.meta"
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
          <tr class="">
            <th>{{ $t('Anchor') }}</th>
            <th>{{ $t('Vendor') }}</th>
            <th>{{ $t('Request Date') }}</th>
            <th>{{ $t('Rejection Date') }}</th>
            <th>{{ $t('PI No') }}.</th>
            <th>{{ $t('Invoice Amount') }}</th>
            <th>{{ $t('Payment Amount') }}</th>
            <th>{{ $t('Rejected By') }}</th>
            <th>{{ $t('Rejection Reason') }}</th>
            <th></th>
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
          <tr v-for="payment_request in requests.data" :key="payment_request.id" class="text-nowrap">
            <td class="text-primary text-decoration-underline px-2">
              <a :href="'../companies/' + payment_request.buyer_id + '/details'" v-if="payment_request.buyer_id">
                {{ payment_request.buyer_name }}
              </a>
              <a :href="'../companies/' + payment_request.anchor_id + '/details'" v-else>
                {{ payment_request.anchor_name }}
              </a>
            </td>
            <td class="text-primary text-decoration-underline px-2">
              <a :href="'../companies/' + payment_request.company_id + '/details'">
                {{ payment_request.company_name }}
              </a>
            </td>
            <td class="px-1">{{ moment(payment_request.payment_request_date).format(date_format) }}</td>
            <td class="px-1">{{ moment(payment_request.updated_at).format(date_format) }}</td>
            <td
              class="text-primary text-decoration-underline px-1"
              @click="showInvoice(payment_request.invoice_id, 'show-pi-btn-' + payment_request.invoice_id)"
              style="cursor: pointer; max-width: 50px; overflow-x: clip"
            >
              {{ payment_request.pi_number }}
            </td>
            <td class="text-success">
              {{ payment_request.invoice_currency }}
              {{ new Intl.NumberFormat().format(payment_request.invoice_amount) }}
            </td>
            <td class="text-success">
              {{ payment_request.invoice_currency }}
              {{ new Intl.NumberFormat().format(payment_request.amount.toFixed(2)) }}
            </td>
            <td>{{ payment_request.rejected_by ? payment_request.rejected_by.name : '' }}</td>
            <td>{{ payment_request.rejected_reason }}</td>
            <td>
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
                        <img src="../../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="invoice_details"
                :id="'invoice-' + payment_request.invoice_id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex">
                        <div class="d-flex" v-if="invoice_details.invoice_media.length > 0">
                          <a
                            v-for="(attachment, key) in invoice_details.invoice_media"
                            :key="key"
                            :href="attachment.original_url"
                            target="_blank"
                            class="btn btn-secondary btn-sm mx-1"
                          >
                            <i class="ti ti-paperclip"></i>{{ $t('View Attachment') }} {{ key + 1 }}</a
                          >
                        </div>
                        <a
                          :href="'../requests/invoices/' + invoice_details.id + '/pdf/download'"
                          class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex justify-content-between">
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.company.name }}</h6>
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
                            <h5 class="my-auto fw-light">{{ $t('Invoice/Unique Ref No') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.invoice_number }}</h6>
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
                              {{ moment(invoice_details.due_date).format(date_format) }}
                            </h6>
                          </span>
                        </div>
                      </div>
                      <div v-if="invoice_details.invoice_items.length > 0" class="table-responsive">
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
                            {{ invoice_details.currency }}
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
                          <h5 class="text-success my-auto">
                            {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(tax.value) }}
                          </h5>
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
                :id="'pi-' + payment_request.invoice_id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Payment Instruction') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex">
                        <div class="d-flex" v-if="invoice_details.invoice_media.length > 0">
                          <a
                            v-for="(attachment, key) in invoice_details.invoice_media"
                            :key="key"
                            :href="attachment.original_url"
                            target="_blank"
                            class="btn btn-secondary btn-sm mx-1"
                          >
                            <i class="ti ti-paperclip"></i>{{ $t('View Attachment') }} {{ key + 1 }}</a
                          >
                        </div>
                        <a
                          :href="'../requests/invoices/payment-instruction/' + invoice_details.id + '/pdf/download'"
                          class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
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
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.remarks }}</h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Status') }}:</h5>
                            <span class="badge me-1 m_title" :class="resolveStatus(invoice_details.status)">{{
                              invoice_details.status
                            }}</span>
                          </span>
                          <span v-if="invoice_details.rejection_reason" class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.rejected_reason }}</h6>
                          </span>
                        </div>
                        <div class="mb-3">
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice/Unique Ref No') }}:</h5>
                            <h6
                              class="fw-bold mx-2 my-auto text-primary pointer"
                              data-bs-toggle="modal"
                              :data-bs-target="'#invoice-' + invoice_details.id"
                            >
                              {{ invoice_details.invoice_number }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-2 my-auto">
                              {{ invoice_details.currency }}
                              {{ new Intl.NumberFormat().format(invoice_details.total) }}
                            </h6>
                          </span>
                          <span class="d-flex justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Due Date') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">
                              {{ moment(invoice_details.due_date).format(date_format) }}
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
                                  invoice_details.invoice_number,
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
      v-if="requests.meta"
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
import Pagination from '../partials/Pagination.vue';
import DownloadButtons from './partials/DownloadButtons.vue';

export default {
  name: 'RejectedPayments',
  components: {
    Pagination,
    DownloadButtons
  },
  props: ['bank', 'date_format'],
  setup(props) {
    const date_format = props.date_format;
    const base_url = inject('baseURL');
    const requests = ref([]);
    const per_page = ref(50);

    const anchor_search = ref('');
    const vendor_search = ref('');
    const payment_reference_number_search = ref('');
    const invoice_number_search = ref('');
    const sort_by = ref('');
    const from_date = ref('');
    const to_date = ref('');

    const invoice_details = ref(null);

    const getRequests = async () => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'rejected-loans-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          requests.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/requests/invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch(per_page, async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'rejected-loans-report',
            per_page: per_page,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          requests.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'rejected-loans-report',
            per_page: per_page.value,
            anchor: anchor_search.value,
            vendor: vendor_search.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          requests.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      anchor_search.value = '';
      vendor_search.value = '';
      payment_reference_number_search.value = '';
      invoice_number_search.value = '';
      sort_by.value = '';
      from_date.value = '';
      to_date.value = '';
      await axios
        .get(base_url + props.bank + '/reports/data', {
          params: {
            type: 'rejected-loans-report',
            per_page: per_page.value
          }
        })
        .then(response => {
          requests.value = response.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const downloadingExcel = ref(false);
    const downloadingPdf = ref(false);

    const downloadReport = () => {
      downloadingExcel.value = true;
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'rejected-loans-report',
            anchor: anchor_search.value,
            vendor: vendor_search.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Rejected_loans_report_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          downloadingExcel.value = false;
        });
    };

    const downloadPdf = () => {
      downloadingPdf.value = true;
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'rejected-loans-report',
            anchor: anchor_search.value,
            vendor: vendor_search.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Rejected_loans_Report_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          downloadingPdf.value = false;
        });
    };

    onMounted(() => {
      getRequests();
    });

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'created':
          style = 'bg-label-primary';
          break;
        case 'approved':
          style = 'bg-label-success';
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

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            type: 'rejected-loans-report',
            anchor: anchor_search.value,
            vendor: vendor_search.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            sort_by: sort_by.value,
            from_date: from_date.value,
            to_date: to_date.value
          }
        })
        .then(response => {
          requests.value = response.data;
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
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    return {
      moment,
      requests,
      per_page,
      anchor_search,
      vendor_search,
      payment_reference_number_search,
      invoice_number_search,
      sort_by,
      from_date,
      to_date,
      invoice_details,
      filter,
      refresh,
      resolvePaymentRequestStatus,
      changePage,
      downloadReport,
      downloadPdf,
      getTaxPercentage,
      resolveStatus,
      showInvoice,

      date_format,
      downloadingExcel,
      downloadingPdf
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
</style>
