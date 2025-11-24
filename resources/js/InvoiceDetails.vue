<template>
  <div class="modal fade" v-if="invoice_details" :id="'invoice-' + invoice_details.id" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header d-flex flex-column flex-md-row justify-content-between">
          <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="d-flex flex-column flex-md-row gap-1">
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
            <a :href="'/invoices/' + invoice_details.id + '/pdf/download'" class="btn btn-primary"
              ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
            >
          </div>
        </div>
        <div class="modal-body">
          <div class="d-flex flex-column flex-md-row justify-content-md-between mb-4">
            <div class="mb-3">
              <span class="d-flex flex-column flex-md-row justify-content-md-between">
                <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">
                  {{ invoice_details.buyer ? invoice_details.buyer.name : invoice_details.program.anchor.name }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.delivery_address }}</h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="fw-light my-auto">{{ $t('Debit From') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.bank_details[0].account_number }}</h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">
                  {{ invoice_details.buyer ? invoice_details.program.anchor.name : invoice_details.company.name }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.remarks }}</h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between" v-if="invoice_details.credit_to">
                <h5 class="fw-light my-auto">{{ $t('Credit To') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.credit_to }}</h6>
              </span>
              <span
                class="d-flex flex-column flex-md-row justify-content-between"
                v-if="invoice_details.rejected_reason"
              >
                <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.rejected_reason }}</h6>
              </span>
            </div>
            <div class="mb-3">
              <span v-if="invoice_details.pi_number" class="d-flex flex-column flex-md-row justify-content-md-between">
                <h5 class="my-auto fw-light">{{ $t('PI No') }}:</h5>
                <h6
                  class="fw-bold mx-md-2 my-auto text-decoration-underline text-primary pointer"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pi-' + invoice_details.id"
                >
                  {{ invoice_details.pi_number }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('Invoice No.') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.invoice_number }}</h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('PO No.') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">
                  {{ invoice_details.purchase_order ? invoice_details.purchase_order.purchase_order_number : '' }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('Payment / OD Account No.') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">
                  {{ invoice_details.vendor_configurations.payment_account_number }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('Invoice Date.') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">
                  {{ moment(invoice_details.invoice_date).format('DD MMM YYYY') }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('Amount') }}:</h5>
                <h6 class="fw-bold text-success mx-md-2 my-auto">
                  {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total) }}
                </h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('Status.') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.approval_stage }}</h6>
              </span>
              <span class="d-flex flex-column flex-md-row justify-content-between">
                <h5 class="my-auto fw-light">{{ $t('Due Date') }}:</h5>
                <h6 class="fw-bold mx-md-2 my-auto">
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
            <span v-for="tax in invoice_details.invoice_taxes" :key="tax.id" class="d-flex justify-content-end">
              <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
              <h5 class="text-success my-auto">{{ new Intl.NumberFormat().format(tax.value) }}</h5>
            </span>
          </div>
          <div v-else class="px-2">
            <span class="d-flex justify-content-end">
              <h6 class="mx-md-2 my-auto">{{ $t('Tax') }}</h6>
              <h5 class="text-success my-auto">0.0</h5>
            </span>
          </div>
          <div class="bg-label-secondary px-2">
            <span class="d-flex justify-content-end">
              <h6 class="mx-md-2 my-auto py-1">{{ $t('Total') }}</h6>
              <h5 class="text-success my-auto py-1">
                {{ invoice_details.currency }}
                {{
                  new Intl.NumberFormat().format(
                    invoice_details.total + invoice_details.total_invoice_taxes - invoice_details.total_invoice_discount
                  )
                }}
              </h5>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <upload-attachment :invoiceDetails="invoice_details" />
</template>
<script>
import moment from 'moment';
import UploadAttachment from './UploadAttachment.vue';

export default {
  props: ['invoiceDetails'],
  components: {
    UploadAttachment
  },
  setup(props) {
    const invoice_details = props.invoiceDetails;
    return {
      moment,
      invoice_details
    };
  }
};
</script>
