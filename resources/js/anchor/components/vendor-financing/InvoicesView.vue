<template>
<div class="nav-align-top nav-tabs-shadow mb-4">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
      <button type="button" class="nav-link active text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-all-invoices" aria-controls="navs-all-invoices" aria-selected="true" @click="switchTabs('invoices')">{{ $t('All Invoices') }}</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pending-invoices" aria-controls="navs-pending-invoices" aria-selected="false" @click="switchTabs('pending_invoices')">{{ $t('Pending') }}</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="navs-all-invoices" role="tabpanel">
      <invoices ref="invoices"></invoices>
    </div>
    <div class="tab-pane fade show" id="navs-pending-invoices" role="tabpanel">
      <pending-invoices ref="pending_invoices"></pending-invoices>
    </div>
  </div>
</div>
</template>
<script>
import { ref, onMounted } from 'vue'
import PendingInvoices from './PendingInvoices.vue'
import Invoices from './Invoices.vue'

export default {
  name: 'InvoicesView',
  components: {
    Invoices,
    PendingInvoices
  },
  setup() {
    const invoices = ref(null)
    const pending_invoices = ref(null)

    onMounted(() => {
      invoices.value.getInvoices()
    })

    const switchTabs = (tab) => {
      switch (tab) {
        case 'invoices':
          invoices.value.getInvoices()
          break;
        case 'pending_invoices':
          pending_invoices.value.getInvoices()
          break;
        default:
          break;
      }
    }

    return {
      invoices,
      pending_invoices,
      switchTabs,
    }
  }
}
</script>
