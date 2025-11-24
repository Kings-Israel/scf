<template>
  <div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
      <li class="nav-item">
        <button type="button" @click="switchTabs('bank-accounts')" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bank-details" aria-controls="navs-pills-bank-details" aria-selected="true"><i class="tf-icons ti ti-file-text ti-xs me-1"></i> Bank Details(CASA)</button>
      </li>
      <li class="nav-item" v-if="can_edit_anchor == 1">
        <button type="button" @click="switchTabs('approval-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-configurations" aria-controls="navs-pills-configurations" aria-selected="false"><i class="tf-icons ti ti-clipboard-check ti-xs me-1"></i> Approval/Auto Financing Settings </button>
      </li>
      <li class="nav-item" v-if="can_edit == 1">
        <button type="button" @click="switchTabs('invoice-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-maker-checker" aria-controls="navs-pills-maker-checker" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> Invoice Settings <i v-if="has_proposed_updates == 1" class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i> </button>
      </li>
      <li class="nav-item" v-if="can_edit == 1">
        <button type="button" @click="switchTabs('tax-details')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-po-settings" aria-controls="navs-pills-po-settings" aria-selected="false"><i class="tf-icons ti ti-settings ti-xs me-1"></i> Tax Details</button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-pills-bank-details" role="tabpanel">
        <bank-accounts ref="bank_accounts"></bank-accounts>
      </div>
      <div class="tab-pane fade" id="navs-pills-configurations" role="tabpanel">
        <approval-settings ref="approval_settings" v-if="can_edit_anchor == 1"></approval-settings>
      </div>
      <div class="tab-pane fade" id="navs-pills-maker-checker" role="tabpanel">
        <invoice-settings ref="invoice_settings" v-if="can_edit == 1" :can_edit="can_edit"></invoice-settings>
      </div>
      <div class="tab-pane fade" id="navs-pills-po-settings" role="tabpanel">
        <tax-details ref="tax_details" v-if="can_edit == 1" :can_edit="can_edit"></tax-details>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, watch, onMounted } from 'vue'
import BankAccounts from './BankAccounts.vue';
import ApprovalSettings from './ApprovalSettings.vue';
import InvoiceSettings from './InvoiceSettings.vue';
import TaxDetails from './TaxDetails.vue'
export default {
  name: 'Settings',
  props: ['can_edit', 'can_edit_anchor', 'checker', 'has_proposed_updates'],
  components: {
    BankAccounts,
    ApprovalSettings,
    InvoiceSettings,
    TaxDetails,
  },
  setup(props) {
    const can_edit = props.can_edit
    const can_edit_anchor = props.can_edit_anchor
    const has_proposed_updates = props.has_proposed_updates
    const bank_accounts = ref(null)
    const approval_settings = ref(null)
    const invoice_settings = ref(null)
    const tax_details = ref(null)

    onMounted(() => {
      bank_accounts.value.getBankAccounts()
    })

    const switchTabs = (tab) => {
      switch (tab) {
        case 'bank-accounts':
          bank_accounts.value.getBankAccounts()
          break;
        case 'approval-settings':
          approval_settings.value.getAnchors()
          break;
        case 'invoice-settings':
          invoice_settings.value.getInvoiceSettings()
          break;
        case 'tax-details':
          tax_details.value.getTaxes()
          break;
        default:
          break;
      }
    }

    return {
      can_edit,
      can_edit_anchor,
      bank_accounts,
      approval_settings,
      invoice_settings,
      tax_details,
      switchTabs,
      has_proposed_updates,
    }
  }
}
</script>
