<template>
  <div class="nav-align-top mb-4">
    <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
      <li class="nav-item">
        <button type="button" @click="switchTabs('bank-accounts')" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-bank-details" aria-controls="navs-pills-bank-details" aria-selected="true"><i class="tf-icons ti ti-file-text ti-xs me-1"></i> Bank Details(CASA)</button>
      </li>
      <li class="nav-item" v-if="can_view">
        <button type="button" @click="switchTabs('purchase-order-settings')" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-maker-checker" aria-controls="navs-pills-maker-checker" aria-selected="false"><i class="tf-icons ti ti-folders ti-xs me-1"></i> Purchase Order Settings <i v-if="has_proposed_updates == 1" class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i> </button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-pills-bank-details" role="tabpanel">
        <buyer-bank-accounts ref="bank_accounts"></buyer-bank-accounts>
      </div>
      <div class="tab-pane fade" id="navs-pills-maker-checker" role="tabpanel" v-if="can_view == 1">
        <buyer-purchase-order-settings ref="purchase_order_settings" :can_edit="can_edit"></buyer-purchase-order-settings>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, watch, onMounted } from 'vue'
import BuyerBankAccounts from './BankAccounts.vue';
import BuyerPurchaseOrderSettings from './PurchaseOrderSettings.vue';
export default {
  name: 'Settings',
  components: {
    BuyerBankAccounts,
    BuyerPurchaseOrderSettings,
  },
  props: ['can_edit', 'can_edit_anchor', 'can_view', 'has_proposed_updates'],
  setup(props) {
    const can_edit = props.can_edit
    const can_edit_anchor = props.can_edit_anchor
    const has_proposed_updates = props.has_proposed_updates
    const can_view = props.can_view
    const bank_accounts = ref(null)
    const purchase_order_settings = ref(null)

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
        default:
          break;
      }
    }

    return {
      can_edit,
      can_edit_anchor,
      can_view,
      bank_accounts,
      purchase_order_settings,
      has_proposed_updates,
      switchTabs,
    }
  }
}
</script>
