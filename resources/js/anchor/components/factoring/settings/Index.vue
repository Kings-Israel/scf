<template>
  <div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
      <li class="nav-item">
        <button type="button" @click="switchTabs('bank-accounts')" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bank-details" aria-controls="navs-pills-bank-details" aria-selected="true"><i class="tf-icons ti ti-file-text ti-xs me-1"></i> {{ $t('Bank Details')}}</button>
      </li>
      <li class="nav-item" v-if="has_factoring_programs == 1 && can_edit_anchor == 1">
        <button type="button" @click="switchTabs('approval-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-configurations" aria-controls="navs-pills-configurations" aria-selected="false"><i class="tf-icons ti ti-clipboard-check ti-xs me-1"></i> Approval/Auto Financing Settings </button>
      </li>
      <li class="nav-item" v-if="can_edit == 1">
        <button type="button" @click="switchTabs('invoice-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-maker-checker" aria-controls="navs-pills-maker-checker" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> {{ $t('Invoice Settings')}} <i v-if="has_proposed_updates == 1" class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i></button>
      </li>
      <li class="nav-item" v-if="can_edit == 1">
        <button type="button" @click="switchTabs('tax-details')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-tax-details" aria-controls="navs-pills-tax-details" aria-selected="false"><i class="tf-icons ti ti-settings ti-xs me-1"></i> {{ $t('Tax Details')}} </button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-pills-bank-details" role="tabpanel">
        <bank-accounts ref="bank_accounts"></bank-accounts>
      </div>
      <div class="tab-pane fade" id="navs-pills-configurations" role="tabpanel" v-if="has_factoring_programs == 1 && can_edit_anchor == 1">
        <approval-settings ref="approval_settings" v-if="can_edit_anchor == 1"></approval-settings>
      </div>
      <div class="tab-pane fade" id="navs-pills-maker-checker" role="tabpanel" v-if="can_edit == 1">
        <invoice-settings ref="invoice_settings" :can_edit="can_edit" :has_dealer_financing_programs="has_dealer_financing_programs"></invoice-settings>
      </div>
      <div class="tab-pane fade" id="navs-pills-tax-details" role="tabpanel" v-if="can_edit == 1">
        <tax-details ref="tax_details" :can_edit="can_edit"></tax-details>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, watch, onMounted } from 'vue'
import BankAccounts from './BankAccounts.vue';
import TaxDetails from './TaxDetails.vue';
import InvoiceSettings from './InvoiceSettings.vue';
import ApprovalSettings from './ApprovalSettings.vue';
export default {
  name: 'Settings',
  props: ['can_edit', 'can_edit_anchor', 'checker', 'has_proposed_updates', 'has_factoring_programs', 'has_dealer_financing_programs'],
  components: {
    BankAccounts,
    TaxDetails,
    InvoiceSettings,
    ApprovalSettings,
  },
  setup(props) {
    const can_edit = props.can_edit
    const can_edit_anchor = props.can_edit_anchor
    const has_proposed_updates = props.has_proposed_updates
    const has_factoring_programs = props.has_factoring_programs
    const has_dealer_financing_programs = props.has_dealer_financing_programs
    const bank_accounts = ref(null)
    const invoice_settings = ref(null)
    const tax_details = ref(null)
    const approval_settings = ref(null)

    onMounted(() => {
      bank_accounts.value.getBankAccounts()
    })

    const switchTabs = (tab) => {
      switch (tab) {
        case 'bank-accounts':
          bank_accounts.value.getBankAccounts()
          break;
        case 'invoice-settings':
          invoice_settings.value.getInvoiceSettings()
          break;
        case 'tax-details':
          tax_details.value.getTaxes()
          break;
        case 'approval-settings':
          approval_settings.value.getAnchors()
          break;
        default:
          break;
      }
    }

    return {
      can_edit,
      can_edit_anchor,
      bank_accounts,
      tax_details,
      invoice_settings,
      tax_details,
      approval_settings,
      has_proposed_updates,
      has_factoring_programs,
      has_dealer_financing_programs,
      switchTabs,
    }
  }
}
</script>
