<template>
  <div class="nav-align-top nav-tabs-shadow">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <button type="button" class="nav-link active font-weight-bolder text-uppercase" role="tab" @click="switchTabs('invoice_analysis_report')" data-bs-toggle="tab" data-bs-target="#navs-invoice-analysis" aria-controls="navs-invoice-analysis" aria-selected="true">{{ $t('Invoice Analysis') }}</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('all_invoices_report')" data-bs-toggle="tab" data-bs-target="#navs-all-invoices-report" aria-controls="navs-all-invoices-report" aria-selected="false">{{ $t('All Invoices Report') }}</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('vendor_analysis_report')" data-bs-toggle="tab" data-bs-target="#navs-vendor-analysis" aria-controls="navs-vendor-analysis" aria-selected="false">{{ $t('Anchor Analysis') }}</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('maturing_invoices_report')" data-bs-toggle="tab" data-bs-target="#navs-maturing-invoices-report" aria-controls="navs-maturing-invoices-report" aria-selected="false">{{ $t('Maturing Invoices Report') }}</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link text-sm font-weight-bolder text-uppercase" role="tab" @click="switchTabs('paid_invoices_report')" data-bs-toggle="tab" data-bs-target="#navs-paid-invoices-report" aria-controls="navs-paid-invoices-report" aria-selected="false">{{ $t('Paid Invoices Report') }}</button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-invoice-analysis" role="tabpanel">
        <invoice-analysis-report ref="invoice_analysis_report"></invoice-analysis-report>
      </div>
      <div class="tab-pane fade" id="navs-all-invoices-report" role="tabpanel">
        <all-invoices-report ref="all_invoices_report"></all-invoices-report>
      </div>
      <div class="tab-pane fade" id="navs-vendor-analysis" role="tabpanel">
        <vendor-analysis-report ref="vendor_analysis_report"></vendor-analysis-report>
      </div>
      <div class="tab-pane fade" id="navs-maturing-invoices-report" role="tabpanel">
        <maturing-invoices-report ref="maturing_invoices_report"></maturing-invoices-report>
      </div>
      <div class="tab-pane fade" id="navs-paid-invoices-report" role="tabpanel">
        <paid-invoices-report ref="paid_invoices_report"></paid-invoices-report>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, onMounted, inject } from 'vue'
import moment from 'moment'
import InvoiceAnalysisReport from './InvoiceAnalysisReport.vue'
import AllInvoicesReport from './AllInvoicesReport.vue'
import VendorAnalysisReport from './VendorAnalysisReport.vue'
import MaturingInvoicesReport from './MaturingInvoicesReport.vue'
import PaidInvoicesReport from './PaidInvoicesReport.vue'

export default {
  name: 'Reports',
  components: {
    InvoiceAnalysisReport,
    AllInvoicesReport,
    VendorAnalysisReport,
    MaturingInvoicesReport,
    PaidInvoicesReport,
  },
  setup() {
    const base_url = inject('baseURL')
    const invoice_analysis_report = ref(null)
    const vendor_analysis_report = ref(null)
    const all_invoices_report = ref(null)
    const maturing_invoices_report = ref(null)
    const paid_invoices_report = ref(null)

    onMounted(() => {
      invoice_analysis_report.value.getReport()
    })

    const switchTabs = (tab) => {
      switch (tab) {
        case 'invoice_analysis_report':
          invoice_analysis_report.value.getReport()
          break;
        case 'all_invoices_report':
          all_invoices_report.value.getReport()
          break;
        case 'vendor_analysis_report':
          vendor_analysis_report.value.getReport()
          break;
        case 'maturing_invoices_report':
          maturing_invoices_report.value.getReport()
          break;
        case 'paid_invoices_report':
          paid_invoices_report.value.getReport()
          break;
        default:
          break;
      }
    }

    return {
      moment,
      invoice_analysis_report,
      vendor_analysis_report,
      all_invoices_report,
      maturing_invoices_report,
      paid_invoices_report,
      switchTabs,
    }
  }
}
</script>
