<template>
<div class="nav-align-top nav-tabs-shadow mb-4">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" v-if="can_view_all_invoices == 1">
      <button type="button" class="nav-link active font-weight-bolder text-uppercase" role="tab" @click="switchTabs('invoices_report')" data-bs-toggle="tab" data-bs-target="#navs-all-invoices-report" aria-controls="navs-all-invoices-report" aria-selected="false">All Invoices Report</button>
    </li>
    <li class="nav-item" v-if="can_view_programs == 1">
      <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('programs_report')" data-bs-toggle="tab" data-bs-target="#navs-program-report" aria-controls="navs-program-report" aria-selected="false"> Program Report </button>
    </li>
    <li class="nav-item" v-if="can_view_payments == 1">
      <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('payments_report')" data-bs-toggle="tab" data-bs-target="#navs-maturing-invoices-report" aria-controls="navs-maturing-invoices-report" aria-selected="false">Payments Report</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="navs-all-invoices-report" role="tabpanel" v-if="can_view_all_invoices == 1">
      <all-invoices-report ref="invoices_report"></all-invoices-report>
    </div>
    <div class="tab-pane fade" id="navs-program-report" role="tabpanel" v-if="can_view_programs == 1">
      <program-report ref="programs_report"></program-report>
    </div>
    <div class="tab-pane fade" id="navs-maturing-invoices-report" role="tabpanel" v-if="can_view_payments == 1">
      <payments-report ref="payments_report"></payments-report>
    </div>
  </div>
</div>
</template>
<script>
import { ref, watch, onMounted } from 'vue'
import AllInvoicesReport from './AllInvoicesReport.vue'
import ProgramReport from './ProgramReport.vue'
import PaymentsReport from './PaymentsReport.vue'
export default {
  name: 'Reports',
  components: {
    AllInvoicesReport,
    ProgramReport,
    PaymentsReport
  },
  props: ['can_view_all_invoices', 'can_view_programs', 'can_view_payments'],
  setup(props) {
    const can_view_all_invoices = props.can_view_all_invoices
    const can_view_programs = props.can_view_programs
    const can_view_payments = props.can_view_payments
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
      can_view_all_invoices,
      can_view_programs,
      can_view_payments,
      invoices_report,
      programs_report,
      payments_report,
      switchTabs,
    }
  }
}
</script>
