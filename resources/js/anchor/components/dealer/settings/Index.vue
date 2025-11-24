<template>
  <div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
      <li class="nav-item">
        <button type="button" @click="switchTabs('bank-accounts')" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bank-details" aria-controls="navs-pills-bank-details" aria-selected="true"><i class="tf-icons ti ti-file-text ti-xs me-1"></i> {{ $t('Bank Details') }}</button>
      </li>
      <li class="nav-item" v-if="can_view == 1">
        <button type="button" @click="switchTabs('purchase-order-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-maker-checker" aria-controls="navs-pills-maker-checker" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> {{ $t('Purchase Order Settings') }} <i v-if="has_proposed_updates == 1" class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i> </button>
      </li>
      <li class="nav-item" v-if="can_view == 1">
        <button type="button" @click="switchTabs('invoice-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-invoice-settings" aria-controls="navs-pills-invoice-settings" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> {{ $t('Invoice Settings') }} <i v-if="has_invoice_proposed_updates == 1" class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i> </button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-pills-bank-details" role="tabpanel">
        <bank-accounts ref="bank_accounts"></bank-accounts>
      </div>
      <div class="tab-pane fade" id="navs-pills-maker-checker" role="tabpanel" v-if="can_view == 1">
        <purchase-order-settings ref="purchase_order_settings" :can_edit="can_edit"></purchase-order-settings>
      </div>
      <div class="tab-pane fade" id="navs-pills-invoice-settings" role="tabpanel" v-if="can_view == 1">
        <invoice-settings ref="invoice_settings" :can_edit="can_edit"></invoice-settings>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, watch, onMounted } from 'vue'
import BankAccounts from './BankAccounts.vue';
import PurchaseOrderSettings from './PurchaseOrderSettings.vue';
import InvoiceSettings from './InvoiceSettings.vue';
export default {
  name: 'Settings',
  props: ['can_edit', 'can_view', 'can_edit_vendor', 'has_proposed_updates', 'has_invoice_proposed_updates'],
  components: {
    BankAccounts,
    PurchaseOrderSettings,
    InvoiceSettings,
  },
  setup(props) {
    const can_edit = props.can_edit
    const can_view = props.can_view
    const can_edit_vendor = props.can_edit_vendor
    const has_proposed_updates = props.has_proposed_updates
    const has_invoice_proposed_updates = props.has_invoice_proposed_updates
    const bank_accounts = ref(null)
    const purchase_order_settings = ref(null)
    const invoice_settings = ref(null)

    onMounted(() => {
      bank_accounts.value.getBankAccounts()
    })

    const switchTabs = (tab) => {
      switch (tab) {
        case 'bank-accounts':
          bank_accounts.value.getBankAccounts()
          break;
        case 'purchase-order-settings':
          purchase_order_settings.value.getPurchaseOrderSettings()
          break;
        case 'invoice-settings':
          invoice_settings.value.getInvoiceSettings()
          break;
        default:
          break;
      }
    }

    return {
      can_edit,
      can_view,
      can_edit_vendor,
      has_proposed_updates,
      has_invoice_proposed_updates,
      bank_accounts,
      purchase_order_settings,
      invoice_settings,
      switchTabs,
    }
  }
}
</script>
