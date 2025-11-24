import { createApp } from 'vue/dist/vue.esm-bundler';
import Toast from 'vue-toastification';
// Import the CSS or use your own!
import 'vue-toastification/dist/index.css';
import { i18nVue } from 'laravel-vue-i18n';

import AllInvoices from './buyer/components/AllInvoices.vue';
import PendingInvoices from './buyer/components/PendingInvoices.vue';
import Anchors from './buyer/components/Anchors.vue';
import Invoices from './buyer/components/dealer/Invoices.vue';
import PurchaseOrders from './buyer/components/PurchaseOrders.vue';
import Reports from './buyer/components/Reports.vue';
import BuyerInvoiceAnalyisReport from './buyer/components/InvoiceAnalysisReport.vue';
import BuyerMaturingInvoicesReport from './buyer/components/MaturingInvoicesReport.vue';
import BuyerAllInvoicesReport from './buyer/components/AllInvoicesReport.vue';
import BuyerPaidInvoicesReport from './buyer/components/PaidInvoicesReport.vue';
import BuyerOverdueInvoicesReport from './buyer/components/OverdueInvoicesReport.vue';
import BuyerVendorAnalysisReport from './buyer/components/VendorAnalysisReport.vue';
import BuyerClosedInvoicesReport from './buyer/components/ClosedInvoices.vue';
import BuyerSettings from './buyer/components/settings/Index.vue';
import Notifications from './buyer/components/Notifications.vue';
import BuyerInvoicesUpload from './buyer/components/InvoicesUpload.vue';
import BuyerUploadedInvoices from './buyer/components/UploadedInvoices.vue';
import BuyerFinanceRequests from './buyer/components/FinanceRequests.vue';

import FinanceRequests from './buyer/components/dealer/FinanceRequests.vue';
import CashPlannerPrograms from './buyer/components/dealer/CashPlannerPrograms.vue';
import CashPlannerEligibleInvoices from './buyer/components/dealer/CashPlannerEligibleInvoices.vue';
import CashPlannerNonEligibleInvoices from './buyer/components/dealer/CashPlannerNonEligibleInvoices.vue';
import DealerReports from './buyer/components/dealer/Reports.vue';
import DealerAllInvoicesReport from './buyer/components/dealer/AllInvoicesReport.vue';
import DealerProgramReport from './buyer/components/dealer/ProgramReport.vue';
import DealerPaymentsReport from './buyer/components/dealer/PaymentsReport.vue';
import OdRepayments from './buyer/components/dealer/OdRepayments.vue';
import OdDetails from './buyer/components/dealer/OdDetails.vue';
import DealerSettings from './buyer/components/dealer/settings/Index.vue';
import DealerNotifications from './buyer/components/dealer/Notifications.vue';
import OdAccountSummary from './buyer/components/dealer/OdAccountSummary.vue';
import DpdInvoices from './buyer/components/dealer/DpdInvoices.vue';
import RejectedInvoices from './buyer/components/dealer/RejectedInvoices.vue';
import DealerInvoicesUpload from './buyer/components/dealer/InvoicesUpload.vue';
import DealerAnchors from './buyer/components/dealer/Anchors.vue';
import DealerUploadedInvoices from './buyer/components/dealer/UploadedInvoices.vue';

const buyer_notifications = createApp({});
const buyer_all_invoices = createApp({});
const anchors = createApp({});
const buyer_invoices_upload = createApp({});
const buyer_uploaded_invoices = createApp({});
const buyer_pending_invoices = createApp({});
const buyer_purchase_orders = createApp({});
const buyer_reports = createApp({});
const buyer_invoice_analysis_report = createApp({});
const buyer_maturing_invoices_report = createApp({});
const buyer_all_invoices_report = createApp({});
const buyer_paid_invoices_report = createApp({});
const buyer_overdue_invoices_report = createApp({});
const buyer_vendor_analysis_report = createApp({});
const buyer_closed_invoices_report = createApp({});
const od_repayments = createApp({});
const od_details = createApp({});
const buyer_settings = createApp({});
const buyer_payment_instructions = createApp({});

const dealer_invoices = createApp({});
const cash_planner_programs = createApp({});
const dealer_cash_planner_eligible_invoices = createApp({});
const dealer_cash_planner_non_eligible_invoices = createApp({});
const finance_requests = createApp({});
const dealer_settings = createApp({});
const dealer_notifications = createApp({});
const od_account_summary = createApp({});
const dpd_invoices = createApp({});
const rejected_invoices = createApp({});
const dealer_reports = createApp({});
const dealer_all_invoices_report = createApp({});
const dealer_programs_report = createApp({});
const dealer_payments_report = createApp({});
const dealer_invoices_upload = createApp({});
const dealer_anchors = createApp({});
const dealer_uploaded_invoices = createApp({});

const baseURL = '/buyer/';

buyer_notifications.component('BuyerNotifications', Notifications);
buyer_all_invoices.component('BuyerAllInvoicesComponent', AllInvoices);
anchors.component('Anchors', Anchors);
buyer_pending_invoices.component('BuyerPendingInvoices', PendingInvoices);
buyer_purchase_orders.component('BuyerPurchaseOrdersComponent', PurchaseOrders);
buyer_reports.component('BuyerReports', Reports);
buyer_invoice_analysis_report.component('BuyerInvoiceAnalysisReport', BuyerInvoiceAnalyisReport);
buyer_maturing_invoices_report.component('BuyerMaturingInvoicesReport', BuyerMaturingInvoicesReport);
buyer_all_invoices_report.component('BuyerAllInvoicesReport', BuyerAllInvoicesReport);
buyer_paid_invoices_report.component('BuyerPaidInvoicesReport', BuyerPaidInvoicesReport);
buyer_overdue_invoices_report.component('BuyerOverdueInvoicesReport', BuyerOverdueInvoicesReport);
buyer_vendor_analysis_report.component('BuyerVendorAnalysisReport', BuyerVendorAnalysisReport);
buyer_closed_invoices_report.component('BuyerClosedInvoicesReport', BuyerClosedInvoicesReport);
buyer_settings.component('BuyerSettings', BuyerSettings);
buyer_invoices_upload.component('BuyerInvoicesUpload', BuyerInvoicesUpload);
buyer_uploaded_invoices.component('BuyerUploadedInvoices', BuyerUploadedInvoices);
buyer_payment_instructions.component('BuyerPaymentInstructions', BuyerFinanceRequests);

const dealerBaseURL = '/dealer/';

dealer_invoices.component('DealerInvoices', Invoices);
dealer_reports.component('DealerReports', DealerReports);
od_details.component('OdDetails', OdDetails);
od_repayments.component('OdRepayments', OdRepayments);
dealer_settings.component('DealerSettings', DealerSettings);
dealer_notifications.component('DealerNotifications', DealerNotifications);
finance_requests.component('FinanceRequests', FinanceRequests);
cash_planner_programs.component('CashPlannerPrograms', CashPlannerPrograms);
od_account_summary.component('OdAccountSummary', OdAccountSummary);
dpd_invoices.component('DpdInvoices', DpdInvoices);
rejected_invoices.component('RejectedInvoices', RejectedInvoices);
dealer_cash_planner_eligible_invoices.component('DealerCashPlannerEligibleInvoices', CashPlannerEligibleInvoices);
dealer_cash_planner_non_eligible_invoices.component(
  'DealerCashPlannerNonEligibleInvoices',
  CashPlannerNonEligibleInvoices
);
dealer_all_invoices_report.component('DealerAllInvoicesReport', DealerAllInvoicesReport);
dealer_programs_report.component('DealerProgramsReport', DealerProgramReport);
dealer_payments_report.component('DealerPaymentsReport', DealerPaymentsReport);
dealer_invoices_upload.component('DealerInvoicesUpload', DealerInvoicesUpload);
dealer_anchors.component('DealerAnchors', DealerAnchors);
dealer_uploaded_invoices.component('DealerUploadedInvoices', DealerUploadedInvoices);

var _anchors = document.getElementById('anchors');
if (_anchors) {
  anchors.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  anchors.use(Toast);
  anchors.provide('baseURL', baseURL);
  anchors.mount('#anchors');
}

var _buyer_reports = document.getElementById('buyer-reports');
if (_buyer_reports) {
  buyer_reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_reports.use(Toast);
  buyer_reports.provide('baseURL', baseURL);
  buyer_reports.mount('#buyer-reports');
}

var _buyer_payment_instructions = document.getElementById('buyer-payment-instructions');
if (_buyer_payment_instructions) {
  buyer_payment_instructions.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_payment_instructions.use(Toast);
  buyer_payment_instructions.provide('baseURL', baseURL);
  buyer_payment_instructions.mount('#buyer-payment-instructions');
}

var _buyer_invoice_analysis_report = document.getElementById('buyer-invoice-analysis-report');
if (_buyer_invoice_analysis_report) {
  buyer_invoice_analysis_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_invoice_analysis_report.use(Toast);
  buyer_invoice_analysis_report.provide('baseURL', baseURL);
  buyer_invoice_analysis_report.mount('#buyer-invoice-analysis-report');
}

var _buyer_maturing_invoices_report = document.getElementById('buyer-maturing-invoices-report');
if (_buyer_maturing_invoices_report) {
  buyer_maturing_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_maturing_invoices_report.use(Toast);
  buyer_maturing_invoices_report.provide('baseURL', baseURL);
  buyer_maturing_invoices_report.mount('#buyer-maturing-invoices-report');
}

var _buyer_paid_invoices_report = document.getElementById('buyer-paid-invoices-report');
if (_buyer_paid_invoices_report) {
  buyer_paid_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_paid_invoices_report.use(Toast);
  buyer_paid_invoices_report.provide('baseURL', baseURL);
  buyer_paid_invoices_report.mount('#buyer-paid-invoices-report');
}

var _buyer_overdue_invoices_report = document.getElementById('buyer-overdue-invoices-report');
if (_buyer_overdue_invoices_report) {
  buyer_overdue_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_overdue_invoices_report.use(Toast);
  buyer_overdue_invoices_report.provide('baseURL', baseURL);
  buyer_overdue_invoices_report.mount('#buyer-overdue-invoices-report');
}

var _buyer_closed_invoices_report = document.getElementById('buyer-closed-invoices-report');
if (_buyer_closed_invoices_report) {
  buyer_closed_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_closed_invoices_report.use(Toast);
  buyer_closed_invoices_report.provide('baseURL', baseURL);
  buyer_closed_invoices_report.mount('#buyer-closed-invoices-report');
}

var _buyer_all_invoices_report = document.getElementById('buyer-all-invoices-report');
if (_buyer_all_invoices_report) {
  buyer_all_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_all_invoices_report.use(Toast);
  buyer_all_invoices_report.provide('baseURL', baseURL);
  buyer_all_invoices_report.mount('#buyer-all-invoices-report');
}

var _buyer_vendor_analysis_report = document.getElementById('buyer-vendor-analysis-report');
if (_buyer_vendor_analysis_report) {
  buyer_vendor_analysis_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_vendor_analysis_report.use(Toast);
  buyer_vendor_analysis_report.provide('baseURL', baseURL);
  buyer_vendor_analysis_report.mount('#buyer-vendor-analysis-report');
}

var _dealer_reports = document.getElementById('dealer_reports');
if (_dealer_reports) {
  dealer_reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_reports.use(Toast);
  dealer_reports.provide('baseURL', dealerBaseURL);
  dealer_reports.mount('#dealer_reports');
}

var _od_repayments = document.getElementById('od_repayments');
if (_od_repayments) {
  od_repayments.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  od_repayments.use(Toast);
  od_repayments.provide('baseURL', dealerBaseURL);
  od_repayments.mount('#od_repayments');
}

var _od_details = document.getElementById('od_details');
if (_od_details) {
  od_details.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  od_details.use(Toast);
  od_details.provide('baseURL', dealerBaseURL);
  od_details.mount('#od_details');
}

var _od_account_summary = document.getElementById('od_account_summary');
if (_od_account_summary) {
  od_account_summary.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  od_account_summary.use(Toast);
  od_account_summary.provide('baseURL', dealerBaseURL);
  od_account_summary.mount('#od_account_summary');
}

var _buyer_purchase_orders = document.getElementById('buyer-purchase-orders');
if (_buyer_purchase_orders) {
  buyer_purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_purchase_orders.use(Toast);
  buyer_purchase_orders.provide('baseURL', baseURL);
  buyer_purchase_orders.mount('#buyer-purchase-orders');
}

var _buyer_all_invoices = document.getElementById('buyer-all-invoices');
if (_buyer_all_invoices) {
  buyer_all_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_all_invoices.use(Toast);
  buyer_all_invoices.provide('baseURL', baseURL);
  buyer_all_invoices.mount('#buyer-all-invoices');
}

var _buyer_pending_invoices = document.getElementById('buyer-pending-invoices');
if (_buyer_pending_invoices) {
  buyer_pending_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_pending_invoices.use(Toast);
  buyer_pending_invoices.provide('baseURL', baseURL);
  buyer_pending_invoices.mount('#buyer-pending-invoices');
}

var _buyer_invoices_upload = document.getElementById('buyer-invoices-upload');
if (_buyer_invoices_upload) {
  buyer_invoices_upload.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_invoices_upload.use(Toast);
  buyer_invoices_upload.provide('baseURL', baseURL);
  buyer_invoices_upload.mount('#buyer-invoices-upload');
}

var _buyer_uploaded_invoices = document.getElementById('buyer-uploaded-invoices');
if (_buyer_uploaded_invoices) {
  buyer_uploaded_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_uploaded_invoices.use(Toast);
  buyer_uploaded_invoices.provide('baseURL', baseURL);
  buyer_uploaded_invoices.mount('#buyer-uploaded-invoices');
}

var _dealer_invoices = document.getElementById('dealer-invoices');
if (_dealer_invoices) {
  dealer_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_invoices.use(Toast);
  dealer_invoices.provide('baseURL', dealerBaseURL);
  dealer_invoices.mount('#dealer-invoices');
}

var _cash_planner_programs = document.getElementById('cash-planner-programs');
if (_cash_planner_programs) {
  cash_planner_programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  cash_planner_programs.use(Toast);
  cash_planner_programs.provide('baseURL', dealerBaseURL);
  cash_planner_programs.mount('#cash-planner-programs');
}

var _dealer_cash_planner_eligible_invoices = document.getElementById('dealer-cash-planner-eligible-invoices');
if (_dealer_cash_planner_eligible_invoices) {
  dealer_cash_planner_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_cash_planner_eligible_invoices.use(Toast);
  dealer_cash_planner_eligible_invoices.provide('baseURL', dealerBaseURL);
  dealer_cash_planner_eligible_invoices.mount('#dealer-cash-planner-eligible-invoices');
}

var _dealer_cash_planner_non_eligible_invoices = document.getElementById('dealer-cash-planner-non-eligible-invoices');
if (_dealer_cash_planner_non_eligible_invoices) {
  dealer_cash_planner_non_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_cash_planner_non_eligible_invoices.use(Toast);
  dealer_cash_planner_non_eligible_invoices.provide('baseURL', dealerBaseURL);
  dealer_cash_planner_non_eligible_invoices.mount('#dealer-cash-planner-non-eligible-invoices');
}

var _finance_requests = document.getElementById('finance-requests');
if (_finance_requests) {
  finance_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  finance_requests.use(Toast);
  finance_requests.provide('baseURL', dealerBaseURL);
  finance_requests.mount('#finance-requests');
}

var _buyer_settings = document.getElementById('buyer-settings');
if (_buyer_settings) {
  buyer_settings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_settings.use(Toast);
  buyer_settings.provide('baseURL', baseURL);
  buyer_settings.mount('#buyer-settings');
}

var _dealer_settings = document.getElementById('dealer-settings');
if (_dealer_settings) {
  dealer_settings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_settings.use(Toast);
  dealer_settings.provide('baseURL', dealerBaseURL);
  dealer_settings.mount('#dealer-settings');
}

var _buyer_notifications = document.getElementById('buyer-notifications');
if (_buyer_notifications) {
  buyer_notifications.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  buyer_notifications.use(Toast);
  buyer_notifications.provide('baseURL', baseURL);
  buyer_notifications.mount('#buyer-notifications');
}

var _dealer_notifications = document.getElementById('dealer-notifications');
if (_dealer_notifications) {
  dealer_notifications.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_notifications.use(Toast);
  dealer_notifications.provide('baseURL', dealerBaseURL);
  dealer_notifications.mount('#dealer-notifications');
}

var _dpd_invoices = document.getElementById('dpd-invoices');
if (_dpd_invoices) {
  dpd_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dpd_invoices.use(Toast);
  dpd_invoices.provide('baseURL', dealerBaseURL);
  dpd_invoices.mount('#dpd-invoices');
}

var _rejected_invoices = document.getElementById('rejected-invoices');
if (_rejected_invoices) {
  rejected_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  rejected_invoices.use(Toast);
  rejected_invoices.provide('baseURL', dealerBaseURL);
  rejected_invoices.mount('#rejected-invoices');
}

var _dealer_all_invoices_report = document.getElementById('dealer_all_invoices_report');
if (_dealer_all_invoices_report) {
  dealer_all_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_all_invoices_report.use(Toast);
  dealer_all_invoices_report.provide('baseURL', dealerBaseURL);
  dealer_all_invoices_report.mount('#dealer_all_invoices_report');
}

var _dealer_programs_report = document.getElementById('dealer_programs_report');
if (_dealer_programs_report) {
  dealer_programs_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_programs_report.use(Toast);
  dealer_programs_report.provide('baseURL', dealerBaseURL);
  dealer_programs_report.mount('#dealer_programs_report');
}

var _dealer_payments_report = document.getElementById('dealer_payments_report');
if (_dealer_payments_report) {
  dealer_payments_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_payments_report.use(Toast);
  dealer_payments_report.provide('baseURL', dealerBaseURL);
  dealer_payments_report.mount('#dealer_payments_report');
}

var _dealer_invoices_upload = document.getElementById('dealer-invoices-upload');
if (_dealer_invoices_upload) {
  dealer_invoices_upload.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_invoices_upload.use(Toast);
  dealer_invoices_upload.provide('baseURL', dealerBaseURL);
  dealer_invoices_upload.mount('#dealer-invoices-upload');
}

var _dealer_anchors = document.getElementById('dealer-anchors');
if (_dealer_anchors) {
  dealer_anchors.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_anchors.use(Toast);
  dealer_anchors.provide('baseURL', dealerBaseURL);
  dealer_anchors.mount('#dealer-anchors');
}

var _dealer_uploaded_invoices = document.getElementById('dealer-uploaded-invoices');
if (_dealer_uploaded_invoices) {
  dealer_uploaded_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_uploaded_invoices.use(Toast);
  dealer_uploaded_invoices.provide('baseURL', dealerBaseURL);
  dealer_uploaded_invoices.mount('#dealer-uploaded-invoices');
}

// View Switcher
let viewSwitcher = document.querySelector('.mode-switcher');

if (viewSwitcher) {
  viewSwitcher.addEventListener('change', function (e) {
    e.preventDefault();
    let env = process.env.NODE_ENV;
    if (env === 'development') {
      if (e.target.value == 'dealer') {
        window.location.href = 'http://yofinvoice.test/dealer/dashboard';
      } else {
        window.location.href = 'http://yofinvoice.test/buyer/dashboard';
      }
    } else {
      if (e.target.value == 'dealer') {
        window.location.href = 'https://uat.yofinvoice.com/dealer/dashboard';
      } else {
        window.location.href = 'https://uat.yofinvoice.com/buyer/dashboard';
      }
    }
  });
}
