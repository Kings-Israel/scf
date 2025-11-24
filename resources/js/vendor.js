import { createApp } from 'vue/dist/vue.esm-bundler';
import Toast from 'vue-toastification';
// Import the CSS or use your own!
import 'vue-toastification/dist/index.css';
import { i18nVue } from 'laravel-vue-i18n';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

import Notifications from './vendor/components/Notifications.vue';
import AllInvoices from './vendor/components/AllInvoices.vue';
import Payments from './vendor/components/Payments.vue';
import PendingInvoices from './vendor/components/PendingInvoices.vue';
import CashPlannerPrograms from './vendor/components/CashPlannerPrograms.vue';
import CashPlannerEligibleInvoices from './vendor/components/CashPlannerEligibleInvoices.vue';
import DashboardCashPlannerEligibleInvoices from './vendor/components/DashboardCashPlannerEligibleInvoices.vue';
import CashPlannerNotEligibleInvoices from './vendor/components/CashPlannerNotEligibleInvoices.vue';
import FinanceRequests from './vendor/components/FinanceRequests.vue';
import PurchaseOrders from './vendor/components/PurchaseOrders.vue';
import PendingPurchaseOrders from './vendor/components/PendingPurchaseOrders.vue';
import Reports from './vendor/components/Reports.vue';
import VendorAllInvoicesReport from './vendor/components/AllInvoicesReport.vue'
import VendorPaymentsReport from './vendor/components/PaymentsReport.vue'
import VendorProgramsReport from './vendor/components/ProgramReport.vue'
import Settings from './vendor/components/settings/Index.vue';
import LoanAccounts from './vendor/components/LoanAccounts.vue';
import VendorInvoicesUpload from './vendor/components/InvoicesUpload.vue';
import VendorUploadedInvoices from './vendor/components/UploadedInvoices.vue';

const notifications = createApp({});
const all_invoices = createApp({});
const payments = createApp({});
const vendor_pending_invoices = createApp({});
const cash_planner_programs = createApp({});
const cash_planner_eligible_invoices = createApp({});
const dashboard_cash_planner_eligible_invoices = createApp({});
const cash_planner_non_eligible_invoices = createApp({});
const finance_requests = createApp({});
const purchase_orders = createApp({});
const pending_purchase_orders = createApp({});
const reports = createApp({});
const vendor_all_invoices_report = createApp({})
const vendor_payments_report = createApp({})
const vendor_programs_report = createApp({})
const settings = createApp({});
const loan_accounts = createApp({});
const vendor_invoices_upload = createApp({});
const vendor_uploaded_invoices = createApp({});

const baseURL = '/vendor/';

notifications.component('VendorNotifications', Notifications);
all_invoices.component('VendorAllInvoicesComponent', AllInvoices);
payments.component('VendorPaymentsComponent', Payments);
vendor_pending_invoices.component('VendorPendingInvoicesComponent', PendingInvoices);
cash_planner_programs.component('VendorCashPlannerProgramsComponent', CashPlannerPrograms);
cash_planner_eligible_invoices.component('VendorCashPlannerEligibleInvoicesComponent', CashPlannerEligibleInvoices);
dashboard_cash_planner_eligible_invoices.component('VendorDashboardCashPlannerEligibleInvoicesComponent', DashboardCashPlannerEligibleInvoices);
cash_planner_non_eligible_invoices.component(
  'VendorCashPlannerNonEligibleInvoicesComponent',
  CashPlannerNotEligibleInvoices
);
finance_requests.component('VendorFinanceRequestsComponent', FinanceRequests);
purchase_orders.component('VendorPurchaseOrdersComponent', PurchaseOrders);
pending_purchase_orders.component('VendorPendingPurchaseOrdersComponent', PendingPurchaseOrders);
reports.component('VendorReports', Reports);
vendor_all_invoices_report.component('VendorAllInvoicesReport', VendorAllInvoicesReport)
vendor_programs_report.component('VendorProgramsReport', VendorProgramsReport)
vendor_payments_report.component('VendorPaymentsReport', VendorPaymentsReport)
settings.component('VendorSettings', Settings);
loan_accounts.component('VendorLoanAccounts', LoanAccounts);
vendor_invoices_upload.component('VendorInvoicesUpload', VendorInvoicesUpload);
vendor_uploaded_invoices.component('VendorUploadedInvoices', VendorUploadedInvoices);

var _notifications = document.getElementById('vendor-notifications');
if (_notifications) {
  notifications.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  notifications.use(Toast);
  notifications.provide('baseURL', baseURL);
  notifications.mount('#vendor-notifications');
}

var _vendor_all_invoices = document.getElementById('vendor-all-invoices');
if (_vendor_all_invoices) {
  all_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  all_invoices.use(Toast);
  all_invoices.provide('baseURL', baseURL);
  all_invoices.mount('#vendor-all-invoices');
}

var _loan_accounts = document.getElementById('vendor-loan-accounts');
if (_loan_accounts) {
  loan_accounts.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  loan_accounts.use(Toast);
  loan_accounts.provide('baseURL', baseURL);
  loan_accounts.mount('#vendor-loan-accounts');
}

var _vendor_invoices_upload = document.getElementById('vendor-invoices-upload');
if (_vendor_invoices_upload) {
  vendor_invoices_upload.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_invoices_upload.use(Toast);
  vendor_invoices_upload.provide('baseURL', baseURL);
  vendor_invoices_upload.mount('#vendor-invoices-upload');
}

var _vendor_uploaded_invoices = document.getElementById('vendor-uploaded-invoices');
if (_vendor_uploaded_invoices) {
  vendor_uploaded_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_uploaded_invoices.use(Toast);
  vendor_uploaded_invoices.provide('baseURL', baseURL);
  vendor_uploaded_invoices.mount('#vendor-uploaded-invoices');
}

var _payments = document.getElementById('vendor-payments');
if (_payments) {
  payments.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  payments.use(Toast);
  payments.provide('baseURL', baseURL);
  payments.mount('#vendor-payments');
}

var _vendor_pending_invoices = document.getElementById('vendor-pending-invoices');
if (_vendor_pending_invoices) {
  vendor_pending_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_pending_invoices.use(Toast);
  vendor_pending_invoices.provide('baseURL', baseURL);
  vendor_pending_invoices.mount('#vendor-pending-invoices');
}

var _vendor_cash_planner_programs = document.getElementById('vendor-cash-planner-programs');
if (_vendor_cash_planner_programs) {
  cash_planner_programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  cash_planner_programs.use(Toast);
  cash_planner_programs.provide('baseURL', baseURL);
  cash_planner_programs.mount('#vendor-cash-planner-programs');
}

var dashboardCashPlannerEligibleInvoices = document.getElementById('vendor-dashboard-cash-planner-eligible-invoices');
if (dashboardCashPlannerEligibleInvoices) {
  dashboard_cash_planner_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dashboard_cash_planner_eligible_invoices.use(PrimeVue, {theme: {
    preset: Aura,
    options: {
      prefix: 'p',
      darkModeSelector: 'light',
      cssLayer: false
    }
  }})
  dashboard_cash_planner_eligible_invoices.use(Toast);
  dashboard_cash_planner_eligible_invoices.provide('baseURL', baseURL);
  dashboard_cash_planner_eligible_invoices.mount('#vendor-dashboard-cash-planner-eligible-invoices');
}

var cashPlannerEligibleInvoices = document.getElementById('vendor-cash-planner-eligible-invoices');
if (cashPlannerEligibleInvoices) {
  cash_planner_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  cash_planner_eligible_invoices.use(PrimeVue, {theme: {
    preset: Aura,
    options: {
      prefix: 'p',
      darkModeSelector: 'light',
      cssLayer: false
    }
  }})
  cash_planner_eligible_invoices.use(Toast);
  cash_planner_eligible_invoices.provide('baseURL', baseURL);
  cash_planner_eligible_invoices.mount('#vendor-cash-planner-eligible-invoices');
}

var cashPlannerNonEligibleInvoices = document.getElementById('vendor-cash-planner-non-eligible-invoices');
if (cashPlannerNonEligibleInvoices) {
  cash_planner_non_eligible_invoices.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  cash_planner_non_eligible_invoices.use(Toast);
  cash_planner_non_eligible_invoices.provide('baseURL', baseURL);
  cash_planner_non_eligible_invoices.mount('#vendor-cash-planner-non-eligible-invoices');
}

var financeRequests = document.getElementById('vendor-finance-requests');
if (financeRequests) {
  finance_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  finance_requests.use(Toast);
  finance_requests.provide('baseURL', baseURL);
  finance_requests.mount('#vendor-finance-requests');
}

var purchaseOrders = document.getElementById('vendor-purchase-orders');
if (purchaseOrders) {
  purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  purchase_orders.use(Toast);
  purchase_orders.provide('baseURL', baseURL);
  purchase_orders.mount('#vendor-purchase-orders');
}

var pendingPurchaseOrders = document.getElementById('vendor-pending-purchase-orders');
if (pendingPurchaseOrders) {
  pending_purchase_orders.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  pending_purchase_orders.use(Toast);
  pending_purchase_orders.provide('baseURL', baseURL);
  pending_purchase_orders.mount('#vendor-pending-purchase-orders');
}

var _reports = document.getElementById('vendor-reports');
if (_reports) {
  reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  reports.use(Toast);
  reports.provide('baseURL', baseURL);
  reports.mount('#vendor-reports');
}

var _vendor_all_invoices_report = document.getElementById('vendor_all_invoices_report');
if (_vendor_all_invoices_report) {
  vendor_all_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_all_invoices_report.use(Toast);
  vendor_all_invoices_report.provide('baseURL', baseURL);
  vendor_all_invoices_report.mount('#vendor_all_invoices_report');
}

var _vendor_programs_report = document.getElementById('vendor_programs_report');
if (_vendor_programs_report) {
  vendor_programs_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_programs_report.use(Toast);
  vendor_programs_report.provide('baseURL', baseURL);
  vendor_programs_report.mount('#vendor_programs_report');
}

var _vendor_payments_report = document.getElementById('vendor_payments_report');
if (_vendor_payments_report) {
  vendor_payments_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_payments_report.use(Toast);
  vendor_payments_report.provide('baseURL', baseURL);
  vendor_payments_report.mount('#vendor_payments_report');
}


var _settings = document.getElementById('vendor-settings');
if (_settings) {
  settings.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  settings.use(Toast);
  settings.provide('baseURL', baseURL);
  settings.mount('#vendor-settings');
}
