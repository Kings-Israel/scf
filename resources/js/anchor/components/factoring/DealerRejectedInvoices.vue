<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Search" class="form-label">{{ $t('Invoice Number') }}</label>
            <input
              type="text"
              class="form-control form-search"
              placeholder="Invoice No"
              v-model="invoice_number"
              v-on:keyup.enter="filter"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Search" class="form-label">{{ $t('Dealer') }}</label>
            <input
              type="text"
              class="form-control form-search"
              placeholder="Dealer"
              v-model="dealer"
              v-on:keyup.enter="filter"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="DPD Rage" class="form-label">{{ $t('DPD Range') }} ({{ $t('Due Date') }})</label>
            <select class="form-select form-search" v-model="range">
              <option value="">{{ $t('Select Range') }}</option>
              <option value="1-30">1 - 30 {{ $t('Days') }}</option>
              <option value="31-60">31 - 60 {{ $t('Days') }}</option>
              <option value="61-90">61 - 90 {{ $t('Days') }}</option>
              <option value="more than 90">> 90 {{ $t('Days') }}</option>
            </select>
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
        <table class="table">
          <thead>
            <tr>
              <th>{{ $t('Dealer') }}</th>
              <th>{{ $t('Invoice No') }}.</th>
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('Status') }}</th>
              <th>{{ $t('Due Date') }}</th>
              <th>{{ $t('Finance Status') }}</th>
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
              <td>{{ invoice.company }}</td>
              <td class="text-primary text-decoration-underline">
                <a href="javascript:;" @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)">{{
                  invoice.invoice_number
                }}</a>
              </td>
              <td class="text-success">
                {{ invoice.currency }} <span>{{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}</span>
              </td>
              <td>
                <span class="badge me-1 m_title" :class="resolveStatus(invoice.approval_stage)">
                  {{ invoice.approval_stage }}
                </span>
              </td>
              <td>
                <span class="">
                  {{ moment(invoice.due_date).format('DD MMM YYYY') }}
                </span>
              </td>
              <td>
                <span class="badge me-1 m_title" :class="resolveFinancingStatus(invoice.financing_status)">
                  {{ invoice.financing_status }}
                </span>
              </td>
              <td>
                <a :href="'../drawdown/create/' + invoice.id" class="btn btn-sm btn-primary">{{ $t('Re-Initiate') }}</a>
              </td>
              <td>
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
                <div
                  class="modal fade"
                  v-if="invoice_details"
                  :id="'invoice-' + invoice.id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="d-flex">
                          <a
                            :href="'../invoices/payment-instruction/' + invoice_details.id + '/pdf/download'"
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
                            <!-- <span class="d-flex justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Rejected Reason') }}:</h5>
                            <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.payment_requests[0].rejected_reason }}</h6>
                          </span> -->
                          </div>
                          <div class="mb-3">
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('Invoice/Unique Ref No') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">{{ invoice_details.invoice_number }}</h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('Invoice Amount') }}:</h5>
                              <h6 class="fw-bold text-success mx-2 my-auto">
                                {{ invoice_details.currency }}
                                {{ new Intl.NumberFormat().format(invoice_details.total_amount) }}
                              </h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('Invoice Date') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">
                                {{ moment(invoice_details.invoice_date).format('DD MMM YYYY') }}
                              </h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('Invoice Due Date') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">
                                {{ moment(invoice_details.due_date).format('DD MMM YYYY') }}
                              </h6>
                            </span>
                            <span class="d-flex justify-content-between">
                              <h5 class="my-auto fw-light">{{ $t('Payment Date') }}:</h5>
                              <h6 class="fw-bold mx-2 my-auto">
                                {{ moment(invoice_details.payment_date).format('DD MMM YYYY') }}
                              </h6>
                            </span>
                          </div>
                        </div>
                        <div class="table-responsive">
                          <div v-if="invoice_details.invoice_fees.length > 0" class="px-2">
                            <span
                              v-for="fee in invoice_details.invoice_fees"
                              :key="fee.id"
                              class="d-flex justify-content-end"
                            >
                              <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                              <h5 class="text-success my-auto py-1" v-if="fee.name != 'Credit Note Amount'">
                                ({{
                                  new Intl.NumberFormat().format(
                                    getTaxPercentage(invoice_details.invoice_total_amount, fee.amount).toFixed(2)
                                  )
                                }}%) {{ new Intl.NumberFormat().format(fee.amount.toFixed(2)) }}
                              </h5>
                              <h5 class="text-success my-auto py-1" v-else>{{ fee.amount }}</h5>
                            </span>
                          </div>
                        </div>
                        <div class="bg-label-secondary px-2">
                          <span class="d-flex justify-content-end">
                            <h6 class="mx-2 my-auto py-1">{{ $t('Total Drawdown Amount') }}</h6>
                            <h5 class="text-success my-auto py-1">
                              {{ new Intl.NumberFormat().format(invoice_details.invoice_total_amount) }}
                            </h5>
                          </span>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <a
                          v-if="invoice_details.status == 'pending'"
                          href="javascript:;"
                          @click="requestApproval(invoice)"
                          class="btn btn-primary"
                          >{{ $t('Submit for Approval') }}</a
                        >
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
import { computed, onMounted, ref, inject, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';

export default {
  name: 'RejectedInvoices',
  components: {
    Pagination
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const invoices = ref([]);
    const per_page = ref(50);
    const invoice_number = ref('');
    const dealer = ref('');
    const range = ref('');

    const invoice_details = ref(null);

    const getInvoices = async () => {
      await axios
        .get(base_url + 'dealer/invoices/dealer/rejected-invoices/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + 'invoices/' + invoice + '/details')
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

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'Overdue':
          style = 'bg-label-danger';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending Maker':
          style = 'bg-label-primary';
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return (tax_amount / invoice_amount) * 100;
    };

    onMounted(() => {
      getInvoices();
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + 'dealer/invoices/dealer/rejected-invoices/data', {
          params: {
            per_page: per_page,
            invoice_number: invoice_number.value,
            dealer: dealer.value,
            range: range.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + 'dealer/invoices/dealer/rejected-invoices/data', {
          params: {
            invoice_number: invoice_number.value,
            dealer: dealer.value,
            per_page: per_page.value,
            range: range.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      invoice_number.value = '';
      dealer.value = '';
      range.value = '';
      axios
        .get(base_url + 'dealer/invoices/dealer/rejected-invoices/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            invoice_number: invoice_number.value,
            dealer: dealer.value,
            range: range.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    };

    return {
      moment,
      per_page,
      invoice_number,
      dealer,
      range,
      invoices,
      invoice_details,
      resolveStatus,
      resolveFinancingStatus,
      changePage,
      getTaxPercentage,
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
</style>
