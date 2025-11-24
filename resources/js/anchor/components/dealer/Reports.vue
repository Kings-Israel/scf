<template>
<div class="nav-align-top nav-tabs-shadow mb-4">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
      <button type="button" class="nav-link active font-weight-bolder text-uppercase" role="tab" @click="switchTabs('invoices_report')" data-bs-toggle="tab" data-bs-target="#navs-all-invoices-report" aria-controls="navs-all-invoices-report" aria-selected="false">All Invoices Report</button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('programs_report')" data-bs-toggle="tab" data-bs-target="#navs-program-report" aria-controls="navs-program-report" aria-selected="false"> Program Report </button>
    </li>
    <li class="nav-item">
      <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('payments_report')" data-bs-toggle="tab" data-bs-target="#navs-maturing-invoices-report" aria-controls="navs-maturing-invoices-report" aria-selected="false">Payments Report</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="navs-all-invoices-report" role="tabpanel">
      <all-invoices-report ref="invoices_report"></all-invoices-report>
    </div>
    <div class="tab-pane fade" id="navs-program-report" role="tabpanel">
      <program-report ref="programs_report"></program-report>
    </div>
    <div class="tab-pane fade" id="navs-maturing-invoices-report" role="tabpanel">
      <payments-report ref="payments_report"></payments-report>
    </div>
  </div>
</div>
</template>
<script>
import { ref, watch, onMounted, inject } from 'vue'
import AllInvoicesReport from './AllInvoicesReport.vue'
import ProgramReport from './ProgramReport.vue'
import PaymentsReport from './PaymentsReport.vue'
export default {
  name: 'FactoringReports',
  components: {
    AllInvoicesReport,
    ProgramReport,
    PaymentsReport
  },
  setup() {
    const base_url = inject('baseURL')
    const invoices_report = ref(null)
    const programs_report = ref(null)
    const payments_report = ref(null)

    onMounted(() => {
      invoices_report.value.getReport()
    })

    const switchTabs = (tab) => {
      switch (tab) {
        case 'invoices_report':
          invoices_report.value.getReport()
          break;
        case 'programs_report':
          programs_report.value.getPrograms()
          break;
        case 'payments_report':
          payments_report.value.getRequests()
          break;
        default:
          break;
      }
    }

    return {
      invoices_report,
      programs_report,
      payments_report,
      switchTabs,
    }
  }
}
</script>
