<template>
  <div class="card p-0">
    <div class="card-body p-2">
      <div class="d-flex flex-column flex-md-row justify-content-between">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="Vendor" class="form-label">{{ $t('Vendor') }}</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              placeholder="Vendor"
              v-model="vendor_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Invoice No" class="form-label">{{ $t('Invoice No') }}.</label>
            <input
              v-on:keyup.enter="filter"
              type="text"
              class="form-control form-search"
              placeholder="Invoice No"
              v-model="invoice_number_search"
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
            />
          </div>
          <div class="">
            <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Disbursement Date') }})</label>
            <input
              type="text"
              id="disbursement_date_search"
              class="form-control form-search"
              name="disbursement_daterange"
              placeholder="Select Dates"
            />
          </div>
          <div class="">
            <label for="Financing Status" class="form-label">{{ $t('Sort By') }}</label>
            <select class="form-select form-search" v-model="sort_by">
              <option value="">{{ $t('Sort By') }}</option>
              <option value="po_asc">{{ $t('PO No. (ASC)') }}</option>
              <option value="po_desc">{{ $t('PO No. (DESC)') }}</option>
              <option value="invoice_no_asc">{{ $t('Invoice No. (ASC)') }}</option>
              <option value="invoice_no_desc">{{ $t('Invoice No. (DESC)') }}</option>
              <option value="due_date_asc">{{ $t('Due Date. (ASC)') }}</option>
              <option value="due_date_desc">{{ $t('Due Date. (DESC)') }}</option>
              <option value="invoice_amount_asc">{{ $t('Invoice Amount. (ASC)') }}</option>
              <option value="invoice_amount_desc">{{ $t('Invoice Amount. (DESC)') }}</option>
              <option value="vendor_asc">{{ $t('Vendor. (ASC)') }}</option>
              <option value="vendor_desc">{{ $t('Vendor. (DESC)') }}</option>
            </select>
          </div>
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" @click="clearSearch"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
        <div class="d-flex justify-content-md-end gap-1 mt-2 mt-md-auto">
          <div class="">
            <select class="form-select" v-model="per_page" style="width: 5rem">
              <option value="10">10</option>
              <option value="20">20</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <button type="button" @click="exportInvoices" style="height: fit-content" class="btn btn-primary">
            <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }}
          </button>
          <button type="button" @click="exportPdfReport" class="btn btn-primary" style="height: fit-content">
            <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }}
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
      <div class="table-responsive">
        <table class="table dt-invoices">
          <thead>
            <tr>
              <th>{{ $t('Vendor') }}</th>
              <th>{{ $t('Invoice No.') }}</th>
              <th>{{ $t('Invoice Amount') }}</th>
              <th>{{ $t('Invoice Date') }}</th>
              <th>{{ $t('Due Date') }}</th>
              <th>{{ $t('Disb. Date') }}</th>
              <th>{{ $t('Closure Date') }}</th>
              <th>{{ $t('Transaction Ref.') }}</th>
              <th>{{ $t('Actions') }}</th>
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
              <td
                class="text-primary text-decoration-underline"
                @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
              >
                <a href="javascript:;">{{ invoice.invoice_number }}</a>
              </td>
              <td class="text-success">
                {{ invoice.currency }}
                <span>
                  {{ new Intl.NumberFormat().format(invoice.invoice_total_amount) }}
                </span>
              </td>
              <td class="">{{ moment(invoice.invoice_date).format('DD MMM YYYY') }}</td>
              <td class="">{{ moment(invoice.due_date).format('DD MMM YYYY') }}</td>
              <td class="">
                {{ invoice.disbursement_date ? moment(invoice.disbursement_date).format('DD MMM YYYY') : '-' }}
              </td>
              <td class="">
                {{ invoice.closure_date ? moment(invoice.closure_date).format('DD MMM YYYY') : '-' }}
              </td>
              <td>{{ invoice.closure_transaction_reference ? invoice.closure_transaction_reference : '-' }}</td>
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
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a
                        href="javascript:;"
                        class="dropdown-item badge bg-label-primary mt-1"
                        @click="showInvoice(invoice.id, 'show-details-btn-' + invoice.id)"
                        >{{ $t('Details') }}</a
                      >
                    </li>
                  </ul>
                </div>
              </td>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
              <payment-instruction
                v-if="invoice_details"
                :invoice-details="invoice_details"
                :key="invoice_details_key"
              />
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
import Pagination from '../partials/Pagination.vue';
import moment from 'moment';
import InvoiceDetails from '../../../InvoiceDetails.vue';
import PaymentInstruction from '../../../PaymentInstruction.vue';

export default {
  name: 'AllInvoices',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  setup() {
    const toast = useToast();
    const base_url = inject('baseURL');
    const type = ref('closed-invoices');
    const invoices = ref([]);
    const users = ref([]);
    const eligibility = ref(0);
    const eligibility_amount = ref(0);
    const invoice_details = ref(null);
    const pi_amount = ref(0);
    const business_spread = ref(0);
    const discount_amount = ref(0);
    const anchor_taxes = ref([]);
    const invoice_taxes = ref([]);
    const invoice_subtotal = ref(0);
    const invoice_total = ref(0);

    const per_page = ref(50);

    const vendor_search = ref('');
    const invoice_number_search = ref('');
    const from_date_search = ref('');
    const to_date_search = ref('');
    const from_disbursement_date_search = ref('');
    const to_disbursement_date_search = ref('');
    const status_search = ref([]);
    const financing_status_search = ref([]);
    const date_search = ref('');
    const disbursement_date_search = ref('');
    const sort_by = ref('');

    const selected_invoices = ref([]);

    const bulk_action = ref('');

    const reject_reason = ref('');

    const selected_invoices_taxes = ref([]);

    const invoice_fees_processing = ref(false);

    const can_bulk_approve = ref(true);

    const invoice_details_key = ref(0);

    const getInvoices = async () => {
      await axios
        .get('report', {
          params: {
            type: type.value,
            per_page: per_page.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data;
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
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

    const showApprovalModal = invoice => {
      invoice_subtotal.value = new Intl.NumberFormat().format(
        invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount
      );
      invoice_total.value = new Intl.NumberFormat().format(
        invoice.total + invoice.total_invoice_taxes - invoice.total_invoice_discount
      );
    };

    const editBuyerFees = invoice => {
      $('#view-fees-' + invoice.id).addClass('d-none');
      $('#edit-fees-' + invoice.id).removeClass('d-none');
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
          document.getElementById('edit-fees-close-btn-' + invoice.id).click();
          // for (const item in invoice_taxes.value) {
          // }
          invoice_taxes.value = [];
        })
        .catch(err => {
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
          document.getElementById('close-modal-' + invoice.id).click();
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

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    onMounted(() => {
      getInvoices();
      $('#invoice-status-select').on('change', function () {
        var ids = $('#invoice-status-select').val();
        status_search.value = ids;
      });
      $('#financing-status-select').on('change', function () {
        var ids = $('#financing-status-select').val();
        financing_status_search.value = ids;
      });
    });

    const updateSelected = id => {
      bulk_action.value = '';
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

    const selectAll = () => {
      bulk_action.value = '';
      selected_invoices.value = [];
      invoice_fees_processing.value = true;
      if (!document.getElementById('select-all').checked) {
        invoices.value.data.forEach(data => {
          if (data.status == 'submitted' && data.user_can_approve && !data.user_has_approved) {
            document.getElementById('data-select-' + data.id).checked = false;
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
            document.getElementById('data-select-' + data.id).checked = true;
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
            document.getElementById('show-bulk-reject-modal').click();
          }
          if (newVal == 'approve') {
            document.getElementById('show-bulk-approval-modal').click();
          }
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
          document.getElementById('close-modal').click();
          for (const item in invoice_taxes.value) {
            invoice_taxes.value[item] = '';
          }
          selected_invoices.value = [];
          selected_invoices_taxes.value = [];
          bulk_action.value = '';
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
          can_bulk_approve.value = true;
        });
    };

    const submitBulkRejection = () => {
      const formData = new FormData();
      formData.append('rejected_reason', reject_reason.value);
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

    watch([per_page], async ([per_page]) => {
      await axios
        .get('report', {
          params: {
            per_page: per_page,
            type: type.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
    });

    const submitRejection = id => {
      const formData = new FormData();
      formData.append('rejected_reason', reject_reason.value);

      formData.append('action', bulk_action.value);

      axios
        .post(base_url + 'invoices/' + id + '/reject', formData)
        .then(() => {
          getInvoices();
          toast.success('Invoices updated successfully');
          document.getElementById('close-reject-modal-' + id).click();
          bulk_action.value = '';
          reject_reason.value = '';
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const exportInvoices = () => {
      axios
        .get(base_url + 'reports/report/' + type.value + '/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Closed_Invoices_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const exportPdfReport = () => {
      axios
        .get(base_url + 'reports/' + type.value + '/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Closed_Invoices_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const clearSearch = () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      vendor_search.value = '';
      invoice_number_search.value = '';
      from_date_search.value = '';
      to_date_search.value = '';
      status_search.value = ['pending_maker', 'pending_checker'];
      financing_status_search.value = [];
      date_search.value = '';
      sort_by.value = '';
      $('#date_search').val('');
      from_disbursement_date_search.value = '';
      to_disbursement_date_search.value = '';
      $('#disbursement_date_search').val('');
      axios
        .get('report', {
          params: {
            type: type.value,
            per_page: per_page.value
          }
        })
        .then(response => {
          invoices.value = response.data;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const filter = async () => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date_search.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date_search.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      disbursement_date_search.value = $('#disbursement_date_search').val();
      if (disbursement_date_search.value) {
        from_disbursement_date_search.value = moment(disbursement_date_search.value.split(' - ')[0]).format(
          'YYYY-MM-DD'
        );
        to_disbursement_date_search.value = moment(disbursement_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get('report', {
          params: {
            type: type.value,
            per_page: per_page.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: type.value,
            per_page: per_page.value,
            vendor: vendor_search.value,
            invoice_number: invoice_number_search.value,
            from_date: from_date_search.value,
            to_date: to_date_search.value,
            from_disbursement_date: from_disbursement_date_search.value,
            to_disbursement_date: to_disbursement_date_search.value,
            status: status_search.value,
            financing_status: financing_status_search.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          invoices.value = response.data.invoices;
        });
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

    return {
      moment,
      per_page,
      invoices,
      users,
      eligibility,
      eligibility_amount,
      business_spread,
      discount_amount,
      invoice_details,
      pi_amount,
      anchor_taxes,
      invoice_taxes,
      invoice_subtotal,
      invoice_total,

      // Search Params
      vendor_search,
      invoice_number_search,
      status_search,
      financing_status_search,
      from_date_search,
      to_date_search,
      sort_by,

      bulk_action,

      reject_reason,

      selected_invoices,
      selected_invoices_taxes,

      invoice_fees_processing,

      can_bulk_approve,

      selectAll,
      calculatePayable,
      updateSelected,
      bulkAction,
      resolveStatus,
      resolveFinancingStatus,
      changePage,
      submitApproval,
      showApprovalModal,
      updateInvoiceTotal,
      getTaxPercentage,
      clearSearch,
      filter,
      exportInvoices,
      exportPdfReport,
      submitBulkApproval,
      submitBulkRejection,
      submitRejection,
      approveFees,
      editBuyerFees,
      showInvoice,
      userHasApproved,
      approvalDetails,
      invoice_details_key
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
