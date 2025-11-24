import { createApp } from 'vue/dist/vue.esm-bundler';
import Toast from 'vue-toastification';
// Import the CSS or use your own!
import 'vue-toastification/dist/index.css';
import { i18nVue } from 'laravel-vue-i18n';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

import FinanceRequests from './anchor/components/vendor-financing/FinanceRequests.vue';
import AnchorNotifications from './anchor/components/vendor-financing/Notifications.vue';
import FactoringNotifications from './anchor/components/factoring/Notifications.vue';
// import VendorFinancingInvoices from './anchor/components/vendor-financing/Invoices.vue';
// import VendorFinancingPendingInvoices from './anchor/components/vendor-financing/PendingInvoices.vue';
import VendorFinancingInvoicesView from './anchor/components/vendor-financing/InvoicesView.vue';
import Vendors from './anchor/components/vendor-financing/Vendors.vue';
import PurchaseOrders from './anchor/components/vendor-financing/PurchaseOrders.vue';
import AnchorReports from './anchor/components/vendor-financing/Reports.vue';
import InvoicesUpload from './anchor/components/vendor-financing/InvoicesUpload.vue';
import FactoringInvoicesUpload from './anchor/components/factoring/InvoicesUpload.vue';
import UploadedInvoices from './anchor/components/vendor-financing/UploadedInvoices.vue';
import FactoringInvoices from './anchor/components/factoring/Invoices.vue';
import FactoringCashPlannerPrograms from './anchor/components/factoring/CashPlannerPrograms.vue';
import CashPlannerEligibleInvoices from './anchor/components/factoring/CashPlannerEligibleInvoices.vue';
import DashboardCashPlannerEligibleInvoices from './anchor/components/factoring/DashboardCashPlannerEligibleInvoices.vue';
import CashPlannerNonEligibleInvoices from './anchor/components/factoring/CashPlannerNonEligibleInvoices.vue';
import FinancingRequests from './anchor/components/factoring/FinancingRequests.vue';
import Settings from './anchor/components/vendor-financing/settings/Index.vue';
import FactoringPurchaseOrders from './anchor/components/factoring/PurchaseOrders.vue';
import FactoringPendingPurchaseOrders from './anchor/components/factoring/PendingPurchaseOrders.vue';
import FactoringReports from './anchor/components/factoring/Reports.vue';
import FactoringAllInvoicesReport from './anchor/components/factoring/AllInvoicesReport.vue';
import FactoringProgramsReport from './anchor/components/factoring/ProgramReport.vue';
import DealerProgramsReport from './anchor/components/factoring/DealerProgramReport.vue';
import FactoringPaymentsReport from './anchor/components/factoring/PaymentsReport.vue';
import FactoringSettings from './anchor/components/factoring/settings/Index.vue';
import FactoringPayments from './anchor/components/factoring/Payments.vue';
import FactoringExpiredInvoices from './anchor/components/factoring/ExpiredInvoices.vue';
import FactoringUploadedInvoices from './anchor/components/factoring/UploadedInvoices.vue';
import DiscountSlab from './anchor/components/factoring/settings/DiscountSlab.vue';
import Buyers from './anchor/components/factoring/Dealers.vue';
import InvoiceAnalyisReport from './anchor/components/vendor-financing/InvoiceAnalysisReport.vue';
import MaturingInvoicesReport from './anchor/components/vendor-financing/MaturingInvoicesReport.vue';
import AllInvoicesReport from './anchor/components/vendor-financing/AllInvoicesReport.vue';
import PaidInvoicesReport from './anchor/components/vendor-financing/PaidInvoicesReport.vue';
import OverdueInvoicesReport from './anchor/components/vendor-financing/OverdueInvoicesReport.vue';
import VendorAnalysisReport from './anchor/components/vendor-financing/VendorAnalysisReport.vue';
import ClosedInvoicesReport from './anchor/components/vendor-financing/ClosedInvoices.vue';
import Discountings from './anchor/components/vendor-financing/Discountings.vue';
import DealerAccounts from './anchor/components/factoring/DealerAccounts.vue';
import FactoringCbsTransactions from './anchor/components/factoring/CbsTransactions.vue';
import DealerPaymentRequests from './anchor/components/factoring/DealerPaymentRequests.vue';
import DealerDpdInvoices from './anchor/components/factoring/DpdInvoices.vue';
import DealerRejectedInvoices from './anchor/components/factoring/DealerRejectedInvoices.vue';

// Dealer Financing
import FactoringDealerInvoicesUpload from './anchor/components/dealer/InvoicesUpload.vue';
import FactoringDealerInvoices from './anchor/components/dealer/Invoices.vue';
import FactoringDealerCashPlannerPrograms from './anchor/components/dealer/CashPlannerPrograms.vue';
import FactoringDealerFinancingRequests from './anchor/components/dealer/FinancingRequests.vue';
import FactoringDealerPurchaseOrders from './anchor/components/dealer/PurchaseOrders.vue';
import FactoringDealerPendingPurchaseOrders from './anchor/components/dealer/PendingPurchaseOrders.vue';
import FactoringDealerReports from './anchor/components/dealer/Reports.vue';
import FactoringDealerAllInvoicesReport from './anchor/components/dealer/AllInvoicesReport.vue';
import FactoringDealerProgramsReport from './anchor/components/dealer/DealerProgramReport.vue';
import FactoringDealerPaymentsReport from './anchor/components/dealer/PaymentsReport.vue';
import FactoringDealerSettings from './anchor/components/dealer/settings/Index.vue';
import FactoringDealerPayments from './anchor/components/dealer/Payments.vue';
import FactoringDealerExpiredInvoices from './anchor/components/dealer/ExpiredInvoices.vue';
import FactoringDealerUploadedInvoices from './anchor/components/dealer/UploadedInvoices.vue';
import FactoringDealerCbsTransactions from './anchor/components/dealer/CbsTransactions.vue';
import Dealers from './anchor/components/dealer/Dealers.vue';
import FactoringDealerPaymentRequests from './anchor/components/dealer/DealerPaymentRequests.vue';
import FactoringDealerDpdInvoices from './anchor/components/dealer/DpdInvoices.vue';
import FactoringDealerRejectedInvoices from './anchor/components/dealer/DealerRejectedInvoices.vue';

const baseURL = '/anchor/';

const anchor_notifications = createApp({});
const factoring_notifications = createApp({});

// Vendor Financing
// const vendor_financing_invoices = createApp({});
// const vendor_financing_pending_invoices = createApp({});
const vendor_financing_invoices_view = createApp({});
const vendors = createApp({});
const purchase_orders = createApp({});
const anchor_reports = createApp({});
const anchor_settings = createApp({});
const invoices_upload = createApp({});
const uploaded_invoices = createApp({});
const invoice_analysis_report = createApp({});
const maturing_invoices_report = createApp({});
const all_invoices_report = createApp({});
const paid_invoices_report = createApp({});
const overdue_invoices_report = createApp({});
const vendor_analysis_report = createApp({});
const closed_invoices_report = createApp({});
const discountings = createApp({});
const payment_instructions = createApp({});

anchor_notifications.component('AnchorNotifications', AnchorNotifications);
factoring_notifications.component('FactoringNotifications', FactoringNotifications);
// vendor_financing_invoices.component('InvoicesComponent', VendorFinancingInvoices);
// vendor_financing_pending_invoices.component('PendingInvoicesComponent', VendorFinancingPendingInvoices);
invoices_upload.component('InvoicesUpload', InvoicesUpload);
uploaded_invoices.component('UploadedInvoicesView', UploadedInvoices);
vendor_financing_invoices_view.component('InvoicesView', VendorFinancingInvoicesView);
vendors.component('Vendors', Vendors);
purchase_orders.component('PurchaseOrdersComponent', PurchaseOrders);
anchor_reports.component('AnchorReports', AnchorReports);
anchor_settings.component('AnchorSettings', Settings);
invoice_analysis_report.component('InvoiceAnalysisReport', InvoiceAnalyisReport);
maturing_invoices_report.component('MaturingInvoicesReport', MaturingInvoicesReport);
all_invoices_report.component('AllInvoicesReport', AllInvoicesReport);
paid_invoices_report.component('PaidInvoicesReport', PaidInvoicesReport);
overdue_invoices_report.component('OverdueInvoicesReport', OverdueInvoicesReport);
vendor_analysis_report.component('VendorAnalysisReport', VendorAnalysisReport);
closed_invoices_report.component('ClosedInvoicesReport', ClosedInvoicesReport);
discountings.component('Discountings', Discountings);
payment_instructions.component('PaymentInstructions', FinanceRequests);

var _anchor_notifications = document.getElementById('anchor-notifications');
if (_anchor_notifications) {
  anchor_notifications.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  anchor_notifications.use(Toast);
  anchor_notifications.provide('baseURL', baseURL);
  anchor_notifications.mount('#anchor-notifications');
}

var _factoring_notifications = document.getElementById('factoring-notifications');
if (_factoring_notifications) {
  factoring_notifications.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_notifications.use(Toast);
  factoring_notifications.provide('baseURL', baseURL);
  factoring_notifications.mount('#factoring-notifications');
}

var _anchor_reports = document.getElementById('anchor_reports');
if (_anchor_reports) {
  anchor_reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  anchor_reports.use(Toast);
  anchor_reports.provide('baseURL', baseURL);
  anchor_reports.mount('#anchor_reports');
}

var _invoices_upload = document.getElementById('invoices_upload');
if (_invoices_upload) {
  invoices_upload.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  invoices_upload.use(Toast);
  invoices_upload.provide('baseURL', baseURL);
  invoices_upload.mount('#invoices_upload');
}

var _vendor_financing_invoices_view = document.getElementById('invoices-view');
if (_vendor_financing_invoices_view) {
  vendor_financing_invoices_view.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_financing_invoices_view.use(Toast);
  vendor_financing_invoices_view.provide('baseURL', baseURL);
  vendor_financing_invoices_view.mount('#invoices-view');
}

var _uploaded_invoices = document.getElementById('uploaded-invoices-view');
if (_uploaded_invoices) {
  uploaded_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  uploaded_invoices.use(Toast);
  uploaded_invoices.provide('baseURL', baseURL);
  uploaded_invoices.mount('#uploaded-invoices-view');
}

var _payment_instructions = document.getElementById('payment-instructions');
if (_payment_instructions) {
  payment_instructions.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  payment_instructions.use(Toast);
  payment_instructions.provide('baseURL', baseURL);
  payment_instructions.mount('#payment-instructions');
}

// var vendor_invoices = document.getElementById('vendor_financing_invoices');
// if (vendor_invoices) {
//   vendor_financing_invoices.use(i18nVue, {
//     resolve: async lang => {
//       const langs = import.meta.glob('../../lang/*.json');
//       return await langs[`../../lang/${lang}.json`]();
//     }
//   });
//   vendor_financing_invoices.use(Toast);
//   vendor_financing_invoices.provide('baseURL', baseURL);
//   vendor_financing_invoices.mount('#vendor_financing_invoices');
// }

// var vendor_pending_invoices = document.getElementById('vendor_financing_pending_invoices');
// if (vendor_pending_invoices) {
//   vendor_financing_pending_invoices.use(i18nVue, {
//     resolve: async lang => {
//       const langs = import.meta.glob('../../lang/*.json');
//       return await langs[`../../lang/${lang}.json`]();
//     }
//   });
//   vendor_financing_pending_invoices.use(Toast);
//   vendor_financing_pending_invoices.provide('baseURL', baseURL);
//   vendor_financing_pending_invoices.mount('#vendor_financing_pending_invoices');
// }

var _vendors = document.getElementById('vendors');
if (_vendors) {
  vendors.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendors.use(Toast);
  vendors.provide('baseURL', baseURL);
  vendors.mount('#vendors');
}

var _purchase_orders = document.getElementById('purchase_orders');
if (_purchase_orders) {
  purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  purchase_orders.use(Toast);
  purchase_orders.provide('baseURL', baseURL);
  purchase_orders.mount('#purchase_orders');
}

var _anchor_settings = document.getElementById('anchor-settings');
if (_anchor_settings) {
  anchor_settings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  anchor_settings.use(Toast);
  anchor_settings.provide('baseURL', baseURL);
  anchor_settings.mount('#anchor-settings');
}

var _invoice_analysis_report = document.getElementById('invoice-analysis-report');
if (_invoice_analysis_report) {
  invoice_analysis_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  invoice_analysis_report.use(Toast);
  invoice_analysis_report.provide('baseURL', baseURL);
  invoice_analysis_report.mount('#invoice-analysis-report');
}

var _maturing_invoices_report = document.getElementById('maturing-invoices-report');
if (_maturing_invoices_report) {
  maturing_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  maturing_invoices_report.use(Toast);
  maturing_invoices_report.provide('baseURL', baseURL);
  maturing_invoices_report.mount('#maturing-invoices-report');
}

var _paid_invoices_report = document.getElementById('paid-invoices-report');
if (_paid_invoices_report) {
  paid_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  paid_invoices_report.use(Toast);
  paid_invoices_report.provide('baseURL', baseURL);
  paid_invoices_report.mount('#paid-invoices-report');
}

var _overdue_invoices_report = document.getElementById('overdue-invoices-report');
if (_overdue_invoices_report) {
  overdue_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  overdue_invoices_report.use(Toast);
  overdue_invoices_report.provide('baseURL', baseURL);
  overdue_invoices_report.mount('#overdue-invoices-report');
}

var _closed_invoices_report = document.getElementById('closed-invoices-report');
if (_closed_invoices_report) {
  closed_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  closed_invoices_report.use(Toast);
  closed_invoices_report.provide('baseURL', baseURL);
  closed_invoices_report.mount('#closed-invoices-report');
}

var _all_invoices_report = document.getElementById('all-invoices-report');
if (_all_invoices_report) {
  all_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  all_invoices_report.use(Toast);
  all_invoices_report.provide('baseURL', baseURL);
  all_invoices_report.mount('#all-invoices-report');
}

var _vendor_analysis_report = document.getElementById('vendor-analysis-report');
if (_vendor_analysis_report) {
  vendor_analysis_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_analysis_report.use(Toast);
  vendor_analysis_report.provide('baseURL', baseURL);
  vendor_analysis_report.mount('#vendor-analysis-report');
}

var _discountings = document.getElementById('discountings');
if (_discountings) {
  discountings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  discountings.use(Toast);
  discountings.provide('baseURL', baseURL);
  discountings.mount('#discountings');
}

// Factoring
const factoring_invoices = createApp({});
const factoring_expired_invoices = createApp({});
const factoring_payments = createApp({});
const eligible_invoices = createApp({});
const dashboard_eligible_invoices = createApp({});
const non_eligible_invoices = createApp({});
const factoring_cash_planner_programs = createApp({});
const financing_requests = createApp({});
const factoring_purchase_orders = createApp({});
const factoring_pending_purchase_orders = createApp({});
const factoring_reports = createApp({});
const factoring_all_invoices_report = createApp({});
const factoring_programs_report = createApp({});
const factoring_dealer_programs_report = createApp({});
const factoring_payments_report = createApp({});
const factoring_settings = createApp({});
const discount_slab = createApp({});
const factoring_invoices_upload = createApp({});
const dealers = createApp({});
const buyers = createApp({});
const factoring_uploaded_invoices = createApp({});
const dealer_accounts = createApp({});
const factoring_cbs_transactions = createApp({});
const dealer_payment_requests = createApp({});
const dealer_dpd_invoices = createApp({});
const dealer_rejected_invoices = createApp({});

discount_slab.component('DiscountSlab', DiscountSlab);
var _discount_slab = document.getElementById('discount_slab');
if (_discount_slab) {
  discount_slab.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  discount_slab.use(Toast);
  discount_slab.provide('baseURL', baseURL);
  discount_slab.mount('#discount_slab');
}

factoring_reports.component('FactoringReports', FactoringReports);
var _factoring_reports = document.getElementById('factoring_reports');
if (_factoring_reports) {
  factoring_reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_reports.use(Toast);
  factoring_reports.provide('baseURL', baseURL);
  factoring_reports.mount('#factoring_reports');
}

factoring_all_invoices_report.component('FactoringAllInvoicesReport', FactoringAllInvoicesReport);
var _factoring_all_invoices_report = document.getElementById('factoring_all_invoices_report');
if (_factoring_all_invoices_report) {
  factoring_all_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_all_invoices_report.use(Toast);
  factoring_all_invoices_report.provide('baseURL', baseURL);
  factoring_all_invoices_report.mount('#factoring_all_invoices_report');
}

factoring_programs_report.component('FactoringProgramsReport', FactoringProgramsReport);
var _factoring_programs_report = document.getElementById('factoring_programs_report');
if (_factoring_programs_report) {
  factoring_programs_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_programs_report.use(Toast);
  factoring_programs_report.provide('baseURL', baseURL);
  factoring_programs_report.mount('#factoring_programs_report');
}

factoring_dealer_programs_report.component('FactoringDealerProgramsReport', FactoringDealerProgramsReport);
var _factoring_dealer_programs_report = document.getElementById('factoring_dealer_programs_report');
if (_factoring_dealer_programs_report) {
  factoring_dealer_programs_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_programs_report.use(Toast);
  factoring_dealer_programs_report.provide('baseURL', baseURL);
  factoring_dealer_programs_report.mount('#factoring_dealer_programs_report');
}

factoring_payments_report.component('FactoringPaymentsReport', FactoringPaymentsReport);
var _factoring_payments_report = document.getElementById('factoring_payments_report');
if (_factoring_payments_report) {
  factoring_payments_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_payments_report.use(Toast);
  factoring_payments_report.provide('baseURL', baseURL);
  factoring_payments_report.mount('#factoring_payments_report');
}

factoring_invoices.component('FactoringInvoicesComponent', FactoringInvoices);
var _factoring_invoices = document.getElementById('factoring_invoices');
if (_factoring_invoices) {
  factoring_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_invoices.use(Toast);
  factoring_invoices.provide('baseURL', baseURL);
  factoring_invoices.mount('#factoring_invoices');
}

buyers.component('Buyers', Buyers);
var _buyers = document.getElementById('buyers');
if (_buyers) {
  buyers.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyers.use(Toast);
  buyers.provide('baseURL', baseURL);
  buyers.mount('#buyers');
}

dealers.component('Dealers', Dealers);
var _dealers = document.getElementById('dealers');
if (_dealers) {
  dealers.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealers.use(Toast);
  dealers.provide('baseURL', baseURL);
  dealers.mount('#dealers');
}

factoring_expired_invoices.component('FactoringExpiredInvoicesComponent', FactoringExpiredInvoices);
var _factoring_expired_invoices = document.getElementById('factoring_expired_invoices');
if (_factoring_expired_invoices) {
  factoring_expired_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_expired_invoices.use(Toast);
  factoring_expired_invoices.provide('baseURL', baseURL);
  factoring_expired_invoices.mount('#factoring_expired_invoices');
}

factoring_uploaded_invoices.component('FactoringUploadedInvoices', FactoringUploadedInvoices);
var _factoring_uploaded_invoices = document.getElementById('factoring_uploaded_invoices');
if (_factoring_uploaded_invoices) {
  factoring_uploaded_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_uploaded_invoices.use(Toast);
  factoring_uploaded_invoices.provide('baseURL', baseURL);
  factoring_uploaded_invoices.mount('#factoring_uploaded_invoices');
}

factoring_payments.component('FactoringPaymentsComponent', FactoringPayments);
var _factoring_payments = document.getElementById('factoring_payments');
if (_factoring_payments) {
  factoring_payments.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_payments.use(Toast);
  factoring_payments.provide('baseURL', baseURL);
  factoring_payments.mount('#factoring_payments');
}

factoring_cash_planner_programs.component('FactoringCashPlannerPrograms', FactoringCashPlannerPrograms);
var _factoring_cash_planner_programs = document.getElementById('factoring-cash-planner-programs');
if (_factoring_cash_planner_programs) {
  factoring_cash_planner_programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_cash_planner_programs.use(Toast);
  factoring_cash_planner_programs.provide('baseURL', baseURL);
  factoring_cash_planner_programs.mount('#factoring-cash-planner-programs');
}

eligible_invoices.component('CashPlannerEligibleInvoicesComponent', CashPlannerEligibleInvoices);
var _eligible_invoices = document.getElementById('eligible-invoices');
if (_eligible_invoices) {
  eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  eligible_invoices.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        prefix: 'p',
        darkModeSelector: 'light',
        cssLayer: false
      }
    }
  });
  eligible_invoices.use(Toast);
  eligible_invoices.provide('baseURL', baseURL);
  eligible_invoices.mount('#eligible-invoices');
}

dashboard_eligible_invoices.component(
  'DashboardCashPlannerEligibleInvoicesComponent',
  DashboardCashPlannerEligibleInvoices
);
var _dashboard_eligible_invoices = document.getElementById('dashboard-eligible-invoices');
if (_dashboard_eligible_invoices) {
  dashboard_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dashboard_eligible_invoices.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        prefix: 'p',
        darkModeSelector: 'light',
        cssLayer: false
      }
    }
  });
  dashboard_eligible_invoices.use(Toast);
  dashboard_eligible_invoices.provide('baseURL', baseURL);
  dashboard_eligible_invoices.mount('#dashboard-eligible-invoices');
}

non_eligible_invoices.component('CashPlannerNonEligibleInvoicesComponent', CashPlannerNonEligibleInvoices);
var _non_eligible_invoices = document.getElementById('non-eligible-invoices');
if (_non_eligible_invoices) {
  non_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  non_eligible_invoices.use(Toast);
  non_eligible_invoices.provide('baseURL', baseURL);
  non_eligible_invoices.mount('#non-eligible-invoices');
}

financing_requests.component('FinancingRequests', FinancingRequests);
var _financing_requests = document.getElementById('financing_requests');
if (_financing_requests) {
  financing_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  financing_requests.use(Toast);
  financing_requests.provide('baseURL', baseURL);
  financing_requests.mount('#financing_requests');
}

factoring_purchase_orders.component('FactoringPurchaseOrdersComponent', FactoringPurchaseOrders);
var _factoring_purchase_orders = document.getElementById('factoring_purchase_orders');
if (_factoring_purchase_orders) {
  factoring_purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_purchase_orders.use(Toast);
  factoring_purchase_orders.provide('baseURL', baseURL);
  factoring_purchase_orders.mount('#factoring_purchase_orders');
}

factoring_pending_purchase_orders.component('FactoringPendingPurchaseOrdersComponent', FactoringPendingPurchaseOrders);
var _factoring_pending_purchase_orders = document.getElementById('factoring_pending_purchase_orders');
if (_factoring_pending_purchase_orders) {
  factoring_pending_purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_pending_purchase_orders.use(Toast);
  factoring_pending_purchase_orders.provide('baseURL', baseURL);
  factoring_pending_purchase_orders.mount('#factoring_pending_purchase_orders');
}

factoring_settings.component('FactoringSettings', FactoringSettings);
var _factoring_settings = document.getElementById('factoring-settings');
if (_factoring_settings) {
  factoring_settings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_settings.use(Toast);
  factoring_settings.provide('baseURL', baseURL);
  factoring_settings.mount('#factoring-settings');
}

factoring_invoices_upload.component('FactoringInvoicesUpload', FactoringInvoicesUpload);
var _factoring_invoices_upload = document.getElementById('factoring-invoices-upload');
if (_factoring_invoices_upload) {
  factoring_invoices_upload.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_invoices_upload.use(Toast);
  factoring_invoices_upload.provide('baseURL', baseURL);
  factoring_invoices_upload.mount('#factoring-invoices-upload');
}

dealer_accounts.component('DealerAccounts', DealerAccounts);
var _dealer_accounts = document.getElementById('dealer-accounts');
if (_dealer_accounts) {
  dealer_accounts.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_accounts.use(Toast);
  dealer_accounts.provide('baseURL', baseURL);
  dealer_accounts.mount('#dealer-accounts');
}

factoring_cbs_transactions.component('FactoringCbsTransactions', FactoringCbsTransactions);
var _factoring_cbs_transactions = document.getElementById('factoring-cbs-transactions');
if (_factoring_cbs_transactions) {
  factoring_cbs_transactions.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_cbs_transactions.use(Toast);
  factoring_cbs_transactions.provide('baseURL', baseURL);
  factoring_cbs_transactions.mount('#factoring-cbs-transactions');
}

dealer_payment_requests.component('DealerPaymentRequests', DealerPaymentRequests);
var _dealer_payment_requests = document.getElementById('dealer-payment-requests');
if (_dealer_payment_requests) {
  dealer_payment_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_payment_requests.use(Toast);
  dealer_payment_requests.provide('baseURL', baseURL);
  dealer_payment_requests.mount('#dealer-payment-requests');
}

dealer_dpd_invoices.component('DealerDpdInvoices', DealerDpdInvoices);
var _dealer_dpd_invoices = document.getElementById('dealer-dpd-invoices');
if (_dealer_dpd_invoices) {
  dealer_dpd_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_dpd_invoices.use(Toast);
  dealer_dpd_invoices.provide('baseURL', baseURL);
  dealer_dpd_invoices.mount('#dealer-dpd-invoices');
}

dealer_rejected_invoices.component('DealerRejectedInvoices', DealerRejectedInvoices);
var _dealer_rejected_invoices = document.getElementById('dealer-rejected-invoices');
if (_dealer_rejected_invoices) {
  dealer_rejected_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_rejected_invoices.use(Toast);
  dealer_rejected_invoices.provide('baseURL', baseURL);
  dealer_rejected_invoices.mount('#dealer-rejected-invoices');
}

// Dealer Financing
const factoring_dealer_invoices = createApp({});
const factoring_dealer_expired_invoices = createApp({});
const factoring_dealer_payments = createApp({});
const factoring_dealer_cash_planner_programs = createApp({});
const factoring_dealer_financing_requests = createApp({});
const factoring_dealer_purchase_orders = createApp({});
const factoring_dealer_pending_purchase_orders = createApp({});
const factoring_dealer_reports = createApp({});
const factoring_dealer_all_invoices_report = createApp({});
const factoring_dealer_dealer_programs_report = createApp({});
const factoring_dealer_payments_report = createApp({});
const factoring_dealer_settings = createApp({});
const factoring_dealer_invoices_upload = createApp({});
const factoring_dealers = createApp({});
const factoring_dealer_uploaded_invoices = createApp({});
const factoring_dealer_accounts = createApp({});
const factoring_dealer_cbs_transactions = createApp({});
const factoring_dealer_payment_requests = createApp({});
const factoring_dealer_dpd_invoices = createApp({});
const factoring_dealer_rejected_invoices = createApp({});

factoring_dealer_reports.component('FactoringDealerReports', FactoringDealerReports);
var _factoring_dealer_reports = document.getElementById('factoring_dealer_reports');
if (_factoring_dealer_reports) {
  factoring_dealer_reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_reports.use(Toast);
  factoring_dealer_reports.provide('baseURL', baseURL);
  factoring_dealer_reports.mount('#factoring_dealer_reports');
}

factoring_dealer_all_invoices_report.component('FactoringDealerAllInvoicesReport', FactoringDealerAllInvoicesReport);
var _factoring_dealer_all_invoices_report = document.getElementById('factoring_dealer_all_invoices_report');
if (_factoring_dealer_all_invoices_report) {
  factoring_dealer_all_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_all_invoices_report.use(Toast);
  factoring_dealer_all_invoices_report.provide('baseURL', baseURL);
  factoring_dealer_all_invoices_report.mount('#factoring_dealer_all_invoices_report');
}

factoring_dealer_payments_report.component('FactoringDealerPaymentsReport', FactoringDealerPaymentsReport);
var _factoring_dealer_payments_report = document.getElementById('factoring_dealer_payments_report');
if (_factoring_dealer_payments_report) {
  factoring_dealer_payments_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_payments_report.use(Toast);
  factoring_dealer_payments_report.provide('baseURL', baseURL);
  factoring_dealer_payments_report.mount('#factoring_dealer_payments_report');
}

factoring_dealer_invoices.component('FactoringDealerInvoicesComponent', FactoringDealerInvoices);
var _factoring_dealer_invoices = document.getElementById('factoring_dealer_invoices');
if (_factoring_dealer_invoices) {
  factoring_dealer_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_invoices.use(Toast);
  factoring_dealer_invoices.provide('baseURL', baseURL);
  factoring_dealer_invoices.mount('#factoring_dealer_invoices');
}

factoring_dealer_expired_invoices.component('FactoringDealerExpiredInvoicesComponent', FactoringDealerExpiredInvoices);
var _factoring_dealer_expired_invoices = document.getElementById('factoring_dealer_expired_invoices');
if (_factoring_dealer_expired_invoices) {
  factoring_dealer_expired_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_expired_invoices.use(Toast);
  factoring_dealer_expired_invoices.provide('baseURL', baseURL);
  factoring_dealer_expired_invoices.mount('#factoring_dealer_expired_invoices');
}

factoring_dealer_uploaded_invoices.component('FactoringDealerUploadedInvoices', FactoringDealerUploadedInvoices);
var _factoring_dealer_uploaded_invoices = document.getElementById('factoring_dealer_uploaded_invoices');
if (_factoring_dealer_uploaded_invoices) {
  factoring_dealer_uploaded_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_uploaded_invoices.use(Toast);
  factoring_dealer_uploaded_invoices.provide('baseURL', baseURL);
  factoring_dealer_uploaded_invoices.mount('#factoring_dealer_uploaded_invoices');
}

factoring_dealer_payments.component('FactoringDealerPaymentsComponent', FactoringDealerPayments);
var _factoring_dealer_payments = document.getElementById('factoring_dealer_payments');
if (_factoring_dealer_payments) {
  factoring_dealer_payments.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_payments.use(Toast);
  factoring_dealer_payments.provide('baseURL', baseURL);
  factoring_dealer_payments.mount('#factoring_dealer_payments');
}

factoring_dealer_purchase_orders.component('FactoringDealerPurchaseOrdersComponent', FactoringDealerPurchaseOrders);
var _factoring_dealer_purchase_orders = document.getElementById('factoring_dealer_purchase_orders');
if (_factoring_dealer_purchase_orders) {
  factoring_dealer_purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_purchase_orders.use(Toast);
  factoring_dealer_purchase_orders.provide('baseURL', baseURL);
  factoring_dealer_purchase_orders.mount('#factoring_dealer_purchase_orders');
}

factoring_dealer_pending_purchase_orders.component(
  'FactoringDealerPendingPurchaseOrdersComponent',
  FactoringDealerPendingPurchaseOrders
);
var _factoring_dealer_pending_purchase_orders = document.getElementById('factoring_dealer_pending_purchase_orders');
if (_factoring_dealer_pending_purchase_orders) {
  factoring_dealer_pending_purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_pending_purchase_orders.use(Toast);
  factoring_dealer_pending_purchase_orders.provide('baseURL', baseURL);
  factoring_dealer_pending_purchase_orders.mount('#factoring_dealer_pending_purchase_orders');
}

factoring_dealer_settings.component('FactoringDealerSettings', FactoringDealerSettings);
var _factoring_dealer_settings = document.getElementById('factoring-dealer-settings');
if (_factoring_dealer_settings) {
  factoring_dealer_settings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_settings.use(Toast);
  factoring_dealer_settings.provide('baseURL', baseURL);
  factoring_dealer_settings.mount('#factoring-dealer-settings');
}

factoring_dealer_invoices_upload.component('FactoringDealerInvoicesUpload', FactoringDealerInvoicesUpload);
var _factoring_dealer_invoices_upload = document.getElementById('factoring-dealer-invoices-upload');
if (_factoring_dealer_invoices_upload) {
  factoring_dealer_invoices_upload.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_invoices_upload.use(Toast);
  factoring_dealer_invoices_upload.provide('baseURL', baseURL);
  factoring_dealer_invoices_upload.mount('#factoring-dealer-invoices-upload');
}

factoring_dealer_cbs_transactions.component('FactoringDealerCbsTransactions', FactoringDealerCbsTransactions);
var _factoring_dealer_cbs_transactions = document.getElementById('factoring-dealer-cbs-transactions');
if (_factoring_dealer_cbs_transactions) {
  factoring_dealer_cbs_transactions.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_dealer_cbs_transactions.use(Toast);
  factoring_dealer_cbs_transactions.provide('baseURL', baseURL);
  factoring_dealer_cbs_transactions.mount('#factoring-dealer-cbs-transactions');
}

// View Switcher
let viewSwitcher = document.querySelector('.view-switcher');

if (viewSwitcher) {
  viewSwitcher.addEventListener('change', function (e) {
    e.preventDefault();
    let env = process.env.NODE_ENV;
    if (env === 'development') {
      if (e.target.value == 'factoring') {
        window.location.href = 'http://yofinvoice.test/anchor/factoring';
      } else if (e.target.value == 'dealer') {
        window.location.href = 'http://yofinvoice.test/anchor/dealer';
      } else {
        window.location.href = 'http://yofinvoice.test/anchor/dashboard';
      }
    } else {
      if (e.target.value == 'factoring') {
        window.location.href = 'https://scf.amaniaccess.com/anchor/factoring';
      } else if (e.target.value == 'dealer') {
        window.location.href = 'https://scf.amaniaccess.com/anchor/dealer';
      } else {
        window.location.href = 'https://scf.amaniaccess.com/anchor/dashboard';
      }
    }
  });
}
