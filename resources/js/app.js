import { createApp } from 'vue/dist/vue.esm-bundler';
import Toast from 'vue-toastification';
import axios from 'axios';
import { i18nVue } from 'laravel-vue-i18n';
// Import the CSS or use your own!
import 'vue-toastification/dist/index.css';
import './anchor';
import './vendor';
import './buyer';

import DashboardCards from './components/DashboardCards.vue';
import Notifications from './components/Notifications.vue';
import DashboardVendorFinancing from './components/DashboardVendorFinancing.vue';
import DashboardFactoringRequests from './components/DashboardFactoringRequests.vue';
import DashboardDealerFinancing from './components/DashboardDealerFinancing.vue';
import VendorFinancing from './components/VendorFinancing.vue';
import FactoringRequests from './components/FactoringRequests.vue';
import DealerFinancing from './components/DealerFinancing.vue';
import Companies from './components/Companies.vue';
import CompanyAuthorizationGroup from './components/CompanyAuthorizationGroup.vue';
import CompanyAuthorizationMatrix from './components/CompanyAuthorizationMatrix.vue';
import CompanyUsers from './components/CompanyUsers.vue';
import UploadedCompanies from './components/UploadedCompanies.vue';
import UploadCompanies from './components/UploadCompanies.vue';
import PendingApproval from './components/PendingApproval.vue';
import PaymentRequests from './components/PaymentRequests.vue';
import PaymentReports from './components/PaymentReports.vue';
import CbsTransactions from './components/CbsTransactions.vue';
import Programs from './components/programs/Programs.vue';
import PendingPrograms from './components/programs/PendingPrograms.vue';
import ManageVendors from './components/programs/ManageVendors.vue';
import Opportunities from './components/Opportunities.vue';
import OdAccounts from './components/OdAccounts.vue';
import ExhaustedPrograms from './components/programs/Exhausted.vue';
import ExpiredPrograms from './components/programs/Expired.vue';
import ActivityLogs from './components/Logs.vue';
import Payments from './components/Payments.vue';
import BaseRates from './components/settings/BaseRates.vue';
import BankUsers from './components/settings/BankUsers.vue';
import TaxRates from './components/settings/Taxes.vue';
import Holidays from './components/settings/Holidays.vue';
import Branches from './components/settings/Branches.vue';
import FeesMaster from './components/settings/FeesMaster.vue';
import ConvertionRates from './components/settings/ConvertionRates.vue';
import NoaTemplates from './components/settings/NoaTemplates.vue';
import TermsAndConditions from './components/settings/TermsAndConditions.vue';
import PendingConfigurations from './components/PendingConfigurations.vue';
import UploadedPaymentRequests from './components/UploadedPaymentRequests.vue';

const dashboard_cards = createApp({});
const notifications = createApp({});
const companies = createApp({});
const uploaded_companies = createApp({});
const upload_companies = createApp({});
const company_users = createApp({});
const programs = createApp({});
const pending_programs = createApp({});
const manage_vendors = createApp({});
const exhausted_programs = createApp({});
const expired_programs = createApp({});
const pending_approval = createApp({});
const payment_requests = createApp({});
const payment_reports = createApp({});
const cbs_transactions = createApp({});
const opportunities = createApp({});
const vendor_financing = createApp({});
const factoring_requests = createApp({});
const dealer_financing = createApp({});
const dashboard_vendor_financing = createApp({});
const dashboard_factoring_requests = createApp({});
const dashboard_dealer_financing = createApp({});
const od_accounts = createApp({});
const activity_logs = createApp({});
const base_rates = createApp({});
const bank_users = createApp({});
const tax_rates = createApp({});
const holidays = createApp({});
const branches = createApp({});
const fees_master = createApp({});
const convertion_rates = createApp({});
const noa_templates = createApp({});
const terms_and_conditions = createApp({});
const authorization_group = createApp({});
const authorization_matrix = createApp({});
const pending_configurations = createApp({});
const uploaded_payment_requests = createApp({});

const payments = createApp({});

const baseURL = '/';

var _dashboard_cards = document.getElementById('dashboard-cards');
if (_dashboard_cards) {
  dashboard_cards.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dashboard_cards.use(Toast);
  dashboard_cards.provide('baseURL', baseURL);
  dashboard_cards.component('DashboardCards', DashboardCards);
  dashboard_cards.mount('#dashboard-cards');
}

var _notifications = document.getElementById('notifications');
if (_notifications) {
  notifications.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  notifications.use(Toast);
  notifications.provide('baseURL', baseURL);
  notifications.component('Notifications', Notifications);
  notifications.mount('#notifications');
}

var _payments = document.getElementById('payments');
if (_payments) {
  payments.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  payments.use(Toast);
  payments.provide('baseURL', baseURL);
  payments.component('Payments', Payments);
  payments.mount('#payments');
}

var _authorization_group = document.getElementById('authorization-groups');
if (_authorization_group) {
  authorization_group.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  authorization_group.use(Toast);
  authorization_group.provide('baseURL', baseURL);
  authorization_group.component('AuthorizationGroups', CompanyAuthorizationGroup);
  authorization_group.mount('#authorization-groups');
}

var _authorization_matrix = document.getElementById('authorization-matrix');
if (_authorization_matrix) {
  authorization_matrix.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  authorization_matrix.use(Toast);
  authorization_matrix.provide('baseURL', baseURL);
  authorization_matrix.component('AuthorizationMatrix', CompanyAuthorizationMatrix);
  authorization_matrix.mount('#authorization-matrix');
}

var _noa_templates = document.getElementById('noa-templates');
if (_noa_templates) {
  noa_templates.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  noa_templates.use(Toast);
  noa_templates.provide('baseURL', baseURL);
  noa_templates.component('NoaTemplates', NoaTemplates);
  noa_templates.mount('#noa-templates');
}

var _terms_and_conditions = document.getElementById('terms-and-conditions');
if (_terms_and_conditions) {
  terms_and_conditions.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  terms_and_conditions.use(Toast);
  terms_and_conditions.provide('baseURL', baseURL);
  terms_and_conditions.component('TermsAndConditions', TermsAndConditions);
  terms_and_conditions.mount('#terms-and-conditions');
}

var _fees_master = document.getElementById('fees-master');
if (_fees_master) {
  fees_master.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  fees_master.use(Toast);
  fees_master.provide('baseURL', baseURL);
  fees_master.component('FeesMaster', FeesMaster);
  fees_master.mount('#fees-master');
}

var _branches = document.getElementById('branches');
if (_branches) {
  branches.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  branches.use(Toast);
  branches.provide('baseURL', baseURL);
  branches.component('Branches', Branches);
  branches.mount('#branches');
}

var _convertion_rates = document.getElementById('convertion_rates');
if (_convertion_rates) {
  convertion_rates.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  convertion_rates.use(Toast);
  convertion_rates.provide('baseURL', baseURL);
  convertion_rates.component('ConvertionRates', ConvertionRates);
  convertion_rates.mount('#convertion_rates');
}

var _bank_users = document.getElementById('bank-users');
if (_bank_users) {
  bank_users.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  bank_users.use(Toast);
  bank_users.provide('baseURL', baseURL);
  bank_users.component('BankUsers', BankUsers);
  bank_users.mount('#bank-users');
}

var _base_rates = document.getElementById('base_rates');
if (_base_rates) {
  base_rates.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  base_rates.use(Toast);
  base_rates.provide('baseURL', baseURL);
  base_rates.component('BaseRates', BaseRates);
  base_rates.mount('#base_rates');
}

var _tax_rates = document.getElementById('tax_rates');
if (_tax_rates) {
  tax_rates.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  tax_rates.use(Toast);
  tax_rates.provide('baseURL', baseURL);
  tax_rates.component('TaxRates', TaxRates);
  tax_rates.mount('#tax_rates');
}

var _holidays = document.getElementById('holidays');
if (_holidays) {
  holidays.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  holidays.use(Toast);
  holidays.provide('baseURL', baseURL);
  holidays.component('Holidays', Holidays);
  holidays.mount('#holidays');
}

var _companies = document.getElementById('companies');
if (_companies) {
  companies.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  companies.use(Toast);
  companies.provide('baseURL', baseURL);
  companies.component('CompaniesComponent', Companies);
  companies.mount('#companies');
}

var _uploaded_companies = document.getElementById('uploaded-companies');
if (_uploaded_companies) {
  uploaded_companies.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  uploaded_companies.use(Toast);
  uploaded_companies.provide('baseURL', baseURL);
  uploaded_companies.component('UploadedCompanies', UploadedCompanies);
  uploaded_companies.mount('#uploaded-companies');
}

var _upload_companies = document.getElementById('upload-companies');
if (_upload_companies) {
  upload_companies.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  upload_companies.use(Toast);
  upload_companies.provide('baseURL', baseURL);
  upload_companies.component('UploadCompanies', UploadCompanies);
  upload_companies.mount('#upload-companies');
}

var _companies_users = document.getElementById('company-users');
if (_companies_users) {
  company_users.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  company_users.use(Toast);
  company_users.provide('baseURL', baseURL);
  company_users.component('CompanyUsersComponent', CompanyUsers);
  company_users.mount('#company-users');
}

var _pending_approval = document.getElementById('pending-approval');
if (_pending_approval) {
  pending_approval.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  pending_approval.use(Toast);
  pending_approval.provide('baseURL', baseURL);
  pending_approval.component('PendingApprovalComponent', PendingApproval);
  pending_approval.mount('#pending-approval');
}

var _opportunities = document.getElementById('opportunities');
if (_opportunities) {
  opportunities.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  opportunities.use(Toast);
  opportunities.provide('baseURL', baseURL);
  opportunities.component('OpportunitiesComponent', Opportunities);
  opportunities.mount('#opportunities');
}

var _payment_requests = document.getElementById('payment-requests');
if (_payment_requests) {
  payment_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  payment_requests.use(Toast);
  payment_requests.provide('baseURL', baseURL);
  payment_requests.component('PaymentRequests', PaymentRequests);
  payment_requests.mount('#payment-requests');
}

var _uploaded_payment_requests = document.getElementById('uploaded-payment-requests');
if (_uploaded_payment_requests) {
  uploaded_payment_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  uploaded_payment_requests.use(Toast);
  uploaded_payment_requests.provide('baseURL', baseURL);
  uploaded_payment_requests.component('UploadedPaymentRequests', UploadedPaymentRequests);
  uploaded_payment_requests.mount('#uploaded-payment-requests');
}

var _payment_reports = document.getElementById('payment-reports');
if (_payment_reports) {
  payment_reports.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  payment_reports.use(Toast);
  payment_reports.provide('baseURL', baseURL);
  payment_reports.component('PaymentReports', PaymentReports);
  payment_reports.mount('#payment-reports');
}

var _cbs_transactions = document.getElementById('cbs-transactions');
if (_cbs_transactions) {
  cbs_transactions.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  cbs_transactions.use(Toast);
  cbs_transactions.provide('baseURL', baseURL);
  cbs_transactions.component('CbsTransactions', CbsTransactions);
  cbs_transactions.mount('#cbs-transactions');
}

var _programs = document.getElementById('programs');
if (_programs) {
  programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  programs.use(Toast);
  programs.provide('baseURL', baseURL);
  programs.component('Programs', Programs);
  programs.mount('#programs');
}

var _pending_programs = document.getElementById('pending-programs');
if (_pending_programs) {
  pending_programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  pending_programs.use(Toast);
  pending_programs.provide('baseURL', baseURL);
  pending_programs.component('PendingPrograms', PendingPrograms);
  pending_programs.mount('#pending-programs');
}

var _manage_vendors = document.getElementById('manage-vendors');
if (_manage_vendors) {
  manage_vendors.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  manage_vendors.use(Toast);
  manage_vendors.provide('baseURL', baseURL);
  manage_vendors.component('ManageVendors', ManageVendors);
  manage_vendors.mount('#manage-vendors');
}

var _exhausted_programs = document.getElementById('exhausted-programs');
if (_exhausted_programs) {
  exhausted_programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  exhausted_programs.use(Toast);
  exhausted_programs.provide('baseURL', baseURL);
  exhausted_programs.component('ExhaustedPrograms', ExhaustedPrograms);
  exhausted_programs.mount('#exhausted-programs');
}

var _expired_programs = document.getElementById('expired-programs');
if (_expired_programs) {
  expired_programs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  expired_programs.use(Toast);
  expired_programs.provide('baseURL', baseURL);
  expired_programs.component('ExpiredPrograms', ExpiredPrograms);
  expired_programs.mount('#expired-programs');
}

var _vendor_financing = document.getElementById('vendor-financing');
if (_vendor_financing) {
  vendor_financing.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_financing.use(Toast);
  vendor_financing.provide('baseURL', baseURL);
  vendor_financing.component('VendorFinancing', VendorFinancing);
  vendor_financing.mount('#vendor-financing');
}

var _factoring_requests = document.getElementById('factoring-requests');
if (_factoring_requests) {
  factoring_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_requests.use(Toast);
  factoring_requests.provide('baseURL', baseURL);
  factoring_requests.component('FactoringRequests', FactoringRequests);
  factoring_requests.mount('#factoring-requests');
}

var _dealer_financing = document.getElementById('dealer-financing');
if (_dealer_financing) {
  dealer_financing.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_financing.use(Toast);
  dealer_financing.provide('baseURL', baseURL);
  dealer_financing.component('DealerFinancing', DealerFinancing);
  dealer_financing.mount('#dealer-financing');
}

var _dashboard_vendor_financing = document.getElementById('dashboard-vendor-financing');
if (_dashboard_vendor_financing) {
  dashboard_vendor_financing.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dashboard_vendor_financing.use(Toast);
  dashboard_vendor_financing.provide('baseURL', baseURL);
  dashboard_vendor_financing.component('DashboardVendorFinancing', DashboardVendorFinancing);
  dashboard_vendor_financing.mount('#dashboard-vendor-financing');
}

var _dashboard_factoring_requests = document.getElementById('dashboard-factoring-requests');
if (_dashboard_factoring_requests) {
  dashboard_factoring_requests.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dashboard_factoring_requests.use(Toast);
  dashboard_factoring_requests.provide('baseURL', baseURL);
  dashboard_factoring_requests.component('DashboardFactoringRequests', DashboardFactoringRequests);
  dashboard_factoring_requests.mount('#dashboard-factoring-requests');
}

var _dashboard_dealer_financing = document.getElementById('dashboard-dealer-financing');
if (_dashboard_dealer_financing) {
  dashboard_dealer_financing.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dashboard_dealer_financing.use(Toast);
  dashboard_dealer_financing.provide('baseURL', baseURL);
  dashboard_dealer_financing.component('DashboardDealerFinancing', DashboardDealerFinancing);
  dashboard_dealer_financing.mount('#dashboard-dealer-financing');
}

var _pending_configurations = document.getElementById('pending-configurations');
if (_pending_configurations) {
  pending_configurations.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  pending_configurations.use(Toast);
  pending_configurations.provide('baseURL', baseURL);
  pending_configurations.component('PendingConfigurations', PendingConfigurations);
  pending_configurations.mount('#pending-configurations');
}

var _od_accounts = document.getElementById('od-accounts');
if (_od_accounts) {
  od_accounts.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  od_accounts.use(Toast);
  od_accounts.provide('baseURL', baseURL);
  od_accounts.component('OdAccounts', OdAccounts);
  od_accounts.mount('#od-accounts');
}

var _activity_logs = document.getElementById('activity-logs');
if (_activity_logs) {
  activity_logs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  activity_logs.use(Toast);
  activity_logs.provide('baseURL', baseURL);
  activity_logs.component('ActivityLogs', ActivityLogs);
  activity_logs.mount('#activity-logs');
}

// Reports
import AllPayments from './components/reports/AllPayments.vue';
import InactiveUsers from './components/reports/InactiveUsers.vue';
import PaymentsReport from './components/reports/PaymentsReport.vue';
import DrawdownDetailsReport from './components/reports/DrawdownDetailsReport.vue';
import RejectedPaymentsReport from './components/reports/RejectedPayments.vue';
import PaymentsPendingApproval from './components/reports/PaymentsPendingApproval.vue';
import PaymentsPendingDisbursal from './components/reports/PaymentsPendingDisbursal.vue';
import UserMaintenanceHistoryReport from './components/reports/UserMaintenanceHistoryReport.vue';
import FinalRtrReport from './components/reports/FinalRtrReport.vue';
import MaturingPaymentsReport from './components/reports/MaturingPaymentsReport.vue';
import MaturityExtendedReport from './components/reports/MaturityExtendedReport.vue';
import PfReport from './components/reports/PfReport.vue';
import PfAndCcReport from './components/reports/PfAndCcReport.vue';
import ReconciliationReport from './components/reports/ReconciliationReport.vue';
import UserAndRolesReport from './components/reports/UserAndRolesReport.vue';
import CronLogs from './components/reports/CronLogs.vue';
import VendorsReport from './components/reports/Vendors-report.vue';

// Dealer Financing Reports
import DfProgramReport from './components/reports/DfProgramReport.vue';
import DfAnchorwiseDealerReport from './components/reports/DfAnchorwiseDealerReport.vue';
import DfCashCollateralReport from './components/reports/DfCashCollateralReport.vue';
import DfDealerClassificationReport from './components/reports/DfDealerClassificationReport.vue';
import DfProgramMappingReport from './components/reports/DfProgramMappingReport.vue';
import DfOdLedgerReport from './components/reports/DfOdLedgerReport.vue';
import DfFeesAndInterestSharingReport from './components/reports/DfFeesAndInterestSharingReport.vue';
import DfIncomeReport from './components/reports/DfIncomeReport.vue';
import DfMonthlyUtilizationAndOutstandingReport from './components/reports/DfMonthlyUtilizationAndOutstandingReport.vue';
import DfOverdueReport from './components/reports/DfOverdueReport.vue';
import DfPotentialFinancingReport from './components/reports/DfPotentialFinancingReport.vue';
import DfOverdueInvoicesReport from './components/reports/DfOverdueInvoices.vue';
import DistributorLimitUtilizationReport from './components/reports/DistributorLimitUtilizationReport.vue';
import DfFundingLimitUtilizationReport from './components/reports/DfFundingLimitUtilizationReport.vue';
import DfCollectionReport from './components/reports/DfCollectionReport.vue';
import DfRepaymentDetailsReport from './components/reports/DfRepaymentDetailsReport.vue';
import DealersDailyOutstandingBalance from './components/reports/DealersDailyOutstandingBalance.vue';

// Vendor Financing Reports
import VfProgramReport from './components/reports/VfProgramReport.vue';
import VfAnchorwiseVendorReport from './components/reports/VfAnchorwiseVendorReport.vue';
import VfVendorClassificationReport from './components/reports/VfVendorClassificationReport.vue';
import VfProgramMappingReport from './components/reports/VfProgramMappingReport.vue';
import VendorDailyOutstandingBalance from './components/reports/VendorDailyOutstandingBalance.vue';
import IfPaymentDetailsReport from './components/reports/IfPaymentDetailsReport.vue';
import VfFeesAndInterestSharingReport from './components/reports/VfFeesAndInterestSharingReport.vue';
import VfIncomeReport from './components/reports/VfIncomeReport.vue';
import FactoringIncomeReport from './components/reports/FactoringIncomeReport.vue';
import VfPotentialFinancingReport from './components/reports/VfPotentialFinancingReport.vue';
import VfRepaymentDetailsReport from './components/reports/VfRepaymentDetailsReport.vue';
import VfOverdueInvoicesReport from './components/reports/VfOverdueInvoices.vue';
import VfFundingLimitUtilizationReport from './components/reports/VfFundingLimitUtilizationReport.vue';

import BankGlsReport from './components/reports/BankGlsReport.vue';

const all_payments = createApp({});
const cron_logs = createApp({});
const inactive_users = createApp({});
const payments_report = createApp({});
const drawdown_details_report = createApp({});
const rejected_loans_report = createApp({});
const loans_pending_approval_report = createApp({});
const loans_pending_disbursal_report = createApp({});
const user_maintenance_history_report = createApp({});
const final_rtr_report = createApp({});
const maturing_payments_report = createApp({});
const maturity_extended_report = createApp({});
const pf_report = createApp({});
const pf_and_cc_report = createApp({});
const if_payment_details_report = createApp({});
const reconciliation_report = createApp({});
const users_and_roles_report = createApp({});
const vendors_report = createApp({});
// Dealer Financing
const dealer_financing_programs_report = createApp({});
const df_anchorwise_dealer_report = createApp({});
const df_cash_collateral_report = createApp({});
const df_dealer_classification_report = createApp({});
const df_program_mapping_report = createApp({});
const df_od_ledger_report = createApp({});
const df_fees_and_interest_sharing_report = createApp({});
const df_income_report = createApp({});
const df_monthly_utilization_and_outstanding_report = createApp({});
const df_overdue_report = createApp({});
const df_potential_financing_report = createApp({});
const df_overdue_invoices_report = createApp({});
const distributor_limit_utilization_report = createApp({});
const df_funding_limit_utilization_report = createApp({});
const df_collection_report = createApp({});
const df_repayment_details_report = createApp({});
const dealers_daily_outstanding_balance = createApp({});

// Vendor Financing
const vendor_financing_programs_report = createApp({});
const vf_anchorwise_vendor_report = createApp({});
const vf_vendor_classification_report = createApp({});
const vf_program_mapping_report = createApp({});
const vendor_daily_outstanding_balance = createApp({});
const vf_fees_and_interest_sharing_report = createApp({});
const vf_income_report = createApp({});
const factoring_income_report = createApp({});
const vf_potential_financing_report = createApp({});
const vf_repayment_details_report = createApp({});
const vf_overdue_invoices_report = createApp({});
const vf_funding_limit_utilization_report = createApp({});

const bank_gls_report = createApp({});

var _df_funding_limit_utilization_report = document.getElementById('df-funding-limit-utilization-report');
if (_df_funding_limit_utilization_report) {
  df_funding_limit_utilization_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_funding_limit_utilization_report.use(Toast);
  df_funding_limit_utilization_report.provide('baseURL', baseURL);
  df_funding_limit_utilization_report.component('DfFundingLimitUtilizationReport', DfFundingLimitUtilizationReport);
  df_funding_limit_utilization_report.mount('#df-funding-limit-utilization-report');
}

var _vf_funding_limit_utilization_report = document.getElementById('vf-funding-limit-utilization-report');
if (_vf_funding_limit_utilization_report) {
  vf_funding_limit_utilization_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_funding_limit_utilization_report.use(Toast);
  vf_funding_limit_utilization_report.provide('baseURL', baseURL);
  vf_funding_limit_utilization_report.component('VfFundingLimitUtilizationReport', VfFundingLimitUtilizationReport);
  vf_funding_limit_utilization_report.mount('#vf-funding-limit-utilization-report');
}

var _df_overdue_invoices_report = document.getElementById('df-overdue-invoices-report');
if (_df_overdue_invoices_report) {
  df_overdue_invoices_report.use(Toast);
  df_overdue_invoices_report.provide('baseURL', baseURL);
  df_overdue_invoices_report.component('DfOverdueInvoicesReport', DfOverdueInvoicesReport);
  df_overdue_invoices_report.mount('#df-overdue-invoices-report');
}

var _vf_overdue_invoices_report = document.getElementById('vf-overdue-invoices-report');
if (_vf_overdue_invoices_report) {
  vf_overdue_invoices_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_overdue_invoices_report.use(Toast);
  vf_overdue_invoices_report.provide('baseURL', baseURL);
  vf_overdue_invoices_report.component('VfOverdueInvoicesReport', VfOverdueInvoicesReport);
  vf_overdue_invoices_report.mount('#vf-overdue-invoices-report');
}

var _distributor_limit_utilization_report = document.getElementById('distributor-limit-utilization-report');
if (_distributor_limit_utilization_report) {
  distributor_limit_utilization_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  distributor_limit_utilization_report.use(Toast);
  distributor_limit_utilization_report.provide('baseURL', baseURL);
  distributor_limit_utilization_report.component(
    'DistributorLimitUtilizationReport',
    DistributorLimitUtilizationReport
  );
  distributor_limit_utilization_report.mount('#distributor-limit-utilization-report');
}

var _if_payment_details_report = document.getElementById('if-payment-details-report');
if (_if_payment_details_report) {
  if_payment_details_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  if_payment_details_report.use(Toast);
  if_payment_details_report.provide('baseURL', baseURL);
  if_payment_details_report.component('IfPaymentDetailsReport', IfPaymentDetailsReport);
  if_payment_details_report.mount('#if-payment-details-report');
}

var _users_and_roles_report = document.getElementById('users-and-roles-report');
if (_users_and_roles_report) {
  users_and_roles_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  users_and_roles_report.use(Toast);
  users_and_roles_report.provide('baseURL', baseURL);
  users_and_roles_report.component('UsersAndRolesReport', UserAndRolesReport);
  users_and_roles_report.mount('#users-and-roles-report');
}

var _reconciliation_report = document.getElementById('reconciliation-report');
if (_reconciliation_report) {
  reconciliation_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  reconciliation_report.use(Toast);
  reconciliation_report.provide('baseURL', baseURL);
  reconciliation_report.component('ReconciliationReport', ReconciliationReport);
  reconciliation_report.mount('#reconciliation-report');
}

var _cron_logs = document.getElementById('cron-logs');
if (_cron_logs) {
  cron_logs.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  cron_logs.use(Toast);
  cron_logs.provide('baseURL', baseURL);
  cron_logs.component('CronLogs', CronLogs);
  cron_logs.mount('#cron-logs');
}

var _pf_report = document.getElementById('pf-report');
if (_pf_report) {
  pf_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  pf_report.use(Toast);
  pf_report.provide('baseURL', baseURL);
  pf_report.component('PfReport', PfReport);
  pf_report.mount('#pf-report');
}

var _pf_and_cc_report = document.getElementById('pf-and-cc-report');
if (_pf_and_cc_report) {
  pf_and_cc_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  pf_and_cc_report.use(Toast);
  pf_and_cc_report.provide('baseURL', baseURL);
  pf_and_cc_report.component('PfAndCcReport', PfAndCcReport);
  pf_and_cc_report.mount('#pf-and-cc-report');
}

var _maturing_payments_report = document.getElementById('maturing-payments-report');
if (_maturing_payments_report) {
  maturing_payments_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  maturing_payments_report.use(Toast);
  maturing_payments_report.provide('baseURL', baseURL);
  maturing_payments_report.component('MaturingPaymentsReport', MaturingPaymentsReport);
  maturing_payments_report.mount('#maturing-payments-report');
}

var _maturity_extended_report = document.getElementById('maturity-extended-report');
if (_maturity_extended_report) {
  maturity_extended_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  maturity_extended_report.use(Toast);
  maturity_extended_report.provide('baseURL', baseURL);
  maturity_extended_report.component('MaturityExtendedReport', MaturityExtendedReport);
  maturity_extended_report.mount('#maturity-extended-report');
}

var _final_rtr_report = document.getElementById('final-rtr-report');
if (_final_rtr_report) {
  final_rtr_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  final_rtr_report.use(Toast);
  final_rtr_report.provide('baseURL', baseURL);
  final_rtr_report.component('FinalRtrReport', FinalRtrReport);
  final_rtr_report.mount('#final-rtr-report');
}

var _all_payments = document.getElementById('all-payments-report');
if (_all_payments) {
  all_payments.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  all_payments.use(Toast);
  all_payments.provide('baseURL', baseURL);
  all_payments.component('AllPaymentsReport', AllPayments);
  all_payments.mount('#all-payments-report');
}

var _inactive_users = document.getElementById('inactive-users-report');
if (_inactive_users) {
  inactive_users.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  inactive_users.use(Toast);
  inactive_users.provide('baseURL', baseURL);
  inactive_users.provide('axios', axios);
  inactive_users.component('InactiveUsersReport', InactiveUsers);
  inactive_users.mount('#inactive-users-report');
}

var _payments_report = document.getElementById('payments-report');
if (_payments_report) {
  payments_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  payments_report.use(Toast);
  payments_report.provide('baseURL', baseURL);
  payments_report.provide('axios', axios);
  payments_report.component('PaymentsReport', PaymentsReport);
  payments_report.mount('#payments-report');
}

var _loans_pending_approval_report = document.getElementById('payments-pending-approval-report');
if (_loans_pending_approval_report) {
  loans_pending_approval_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  loans_pending_approval_report.use(Toast);
  loans_pending_approval_report.provide('baseURL', baseURL);
  loans_pending_approval_report.provide('axios', axios);
  loans_pending_approval_report.component('PaymentsPendingApprovalReport', PaymentsPendingApproval);
  loans_pending_approval_report.mount('#payments-pending-approval-report');
}

var _loans_pending_disbursal_report = document.getElementById('payments-pending-disbursal-report');
if (_loans_pending_disbursal_report) {
  loans_pending_disbursal_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  loans_pending_disbursal_report.use(Toast);
  loans_pending_disbursal_report.provide('baseURL', baseURL);
  loans_pending_disbursal_report.provide('axios', axios);
  loans_pending_disbursal_report.component('PaymentsPendingDisbursalReport', PaymentsPendingDisbursal);
  loans_pending_disbursal_report.mount('#payments-pending-disbursal-report');
}

var _user_maintenance_history_report = document.getElementById('user-maintenance-history-report');
if (_user_maintenance_history_report) {
  user_maintenance_history_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  user_maintenance_history_report.use(Toast);
  user_maintenance_history_report.provide('baseURL', baseURL);
  user_maintenance_history_report.provide('axios', axios);
  user_maintenance_history_report.component('UserMaintenanceHistoryReport', UserMaintenanceHistoryReport);
  user_maintenance_history_report.mount('#user-maintenance-history-report');
}

var _drawdown_details_report = document.getElementById('drawdown-details-report');
if (_drawdown_details_report) {
  drawdown_details_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  drawdown_details_report.use(Toast);
  drawdown_details_report.provide('baseURL', baseURL);
  drawdown_details_report.provide('axios', axios);
  drawdown_details_report.component('DrawdownDetailsReport', DrawdownDetailsReport);
  drawdown_details_report.mount('#drawdown-details-report');
}

var _dealer_financing_programs_report = document.getElementById('dealer-financing-programs-report');
if (_dealer_financing_programs_report) {
  dealer_financing_programs_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealer_financing_programs_report.use(Toast);
  dealer_financing_programs_report.provide('baseURL', baseURL);
  dealer_financing_programs_report.provide('axios', axios);
  dealer_financing_programs_report.component('DealerFinancingProgramsReport', DfProgramReport);
  dealer_financing_programs_report.mount('#dealer-financing-programs-report');
}

var _rejected_loans_report = document.getElementById('rejected-payments-report');
if (_rejected_loans_report) {
  rejected_loans_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  rejected_loans_report.use(Toast);
  rejected_loans_report.provide('baseURL', baseURL);
  rejected_loans_report.provide('axios', axios);
  rejected_loans_report.component('RejectedPaymentsReport', RejectedPaymentsReport);
  rejected_loans_report.mount('#rejected-payments-report');
}

// Dealer Financing Reports
var _df_anchorwise_dealer_report = document.getElementById('df-anchorwise-dealer-report');
if (_df_anchorwise_dealer_report) {
  df_anchorwise_dealer_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_anchorwise_dealer_report.use(Toast);
  df_anchorwise_dealer_report.provide('baseURL', baseURL);
  df_anchorwise_dealer_report.provide('axios', axios);
  df_anchorwise_dealer_report.component('DfAnchorwiseDealerReport', DfAnchorwiseDealerReport);
  df_anchorwise_dealer_report.mount('#df-anchorwise-dealer-report');
}

var _df_monthly_utilization_and_outstanding_report = document.getElementById(
  'df-monthly-utilization-and-outstanding-report'
);
if (_df_monthly_utilization_and_outstanding_report) {
  df_monthly_utilization_and_outstanding_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_monthly_utilization_and_outstanding_report.use(Toast);
  df_monthly_utilization_and_outstanding_report.provide('baseURL', baseURL);
  df_monthly_utilization_and_outstanding_report.provide('axios', axios);
  df_monthly_utilization_and_outstanding_report.component(
    'DfMonthlyUtilizationAndOutstandingReport',
    DfMonthlyUtilizationAndOutstandingReport
  );
  df_monthly_utilization_and_outstanding_report.mount('#df-monthly-utilization-and-outstanding-report');
}

var _df_potential_financing_report = document.getElementById('df-potential-financing-report');
if (_df_potential_financing_report) {
  df_potential_financing_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_potential_financing_report.use(Toast);
  df_potential_financing_report.provide('baseURL', baseURL);
  df_potential_financing_report.provide('axios', axios);
  df_potential_financing_report.component('DfPotentialFinancingReport', DfPotentialFinancingReport);
  df_potential_financing_report.mount('#df-potential-financing-report');
}

var _df_overdue_report = document.getElementById('df-overdue-report');
if (_df_overdue_report) {
  df_overdue_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_overdue_report.use(Toast);
  df_overdue_report.provide('baseURL', baseURL);
  df_overdue_report.provide('axios', axios);
  df_overdue_report.component('DfOverdueReport', DfOverdueReport);
  df_overdue_report.mount('#df-overdue-report');
}

var _df_income_report = document.getElementById('df-income-report');
if (_df_income_report) {
  df_income_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_income_report.use(Toast);
  df_income_report.provide('baseURL', baseURL);
  df_income_report.provide('axios', axios);
  df_income_report.component('DfIncomeReport', DfIncomeReport);
  df_income_report.mount('#df-income-report');
}

var _df_od_ledger_report = document.getElementById('df-od-ledger-report');
if (_df_od_ledger_report) {
  df_od_ledger_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_od_ledger_report.use(Toast);
  df_od_ledger_report.provide('baseURL', baseURL);
  df_od_ledger_report.provide('axios', axios);
  df_od_ledger_report.component('DfOdLedgerReport', DfOdLedgerReport);
  df_od_ledger_report.mount('#df-od-ledger-report');
}

var _df_fees_and_interest_sharing_report = document.getElementById('df-fees-and-interest-sharing-report');
if (_df_fees_and_interest_sharing_report) {
  df_fees_and_interest_sharing_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_fees_and_interest_sharing_report.use(Toast);
  df_fees_and_interest_sharing_report.provide('baseURL', baseURL);
  df_fees_and_interest_sharing_report.provide('axios', axios);
  df_fees_and_interest_sharing_report.component('DfFeesAndInterestSharingReport', DfFeesAndInterestSharingReport);
  df_fees_and_interest_sharing_report.mount('#df-fees-and-interest-sharing-report');
}

var _df_cash_collateral_report = document.getElementById('df-cash-collateral-report');
if (_df_cash_collateral_report) {
  df_cash_collateral_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_cash_collateral_report.use(Toast);
  df_cash_collateral_report.provide('baseURL', baseURL);
  df_cash_collateral_report.provide('axios', axios);
  df_cash_collateral_report.component('DfCashCollateralReport', DfCashCollateralReport);
  df_cash_collateral_report.mount('#df-cash-collateral-report');
}

var _df_dealer_classification_report = document.getElementById('df-dealer-classification-report');
if (_df_dealer_classification_report) {
  df_dealer_classification_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_dealer_classification_report.use(Toast);
  df_dealer_classification_report.provide('baseURL', baseURL);
  df_dealer_classification_report.provide('axios', axios);
  df_dealer_classification_report.component('DfDealerClassificationReport', DfDealerClassificationReport);
  df_dealer_classification_report.mount('#df-dealer-classification-report');
}

var _df_program_mapping_report = document.getElementById('df-program-mapping-report');
if (_df_program_mapping_report) {
  df_program_mapping_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_program_mapping_report.use(Toast);
  df_program_mapping_report.provide('baseURL', baseURL);
  df_program_mapping_report.provide('axios', axios);
  df_program_mapping_report.component('DfProgramMappingReport', DfProgramMappingReport);
  df_program_mapping_report.mount('#df-program-mapping-report');
}

var _df_collection_report = document.getElementById('df-collection-report');
if (_df_collection_report) {
  df_collection_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_collection_report.use(Toast);
  df_collection_report.provide('baseURL', baseURL);
  df_collection_report.provide('axios', axios);
  df_collection_report.component('DfCollectionReport', DfCollectionReport);
  df_collection_report.mount('#df-collection-report');
}

var _df_repayment_details_report = document.getElementById('df-repayment-details-report');
if (_df_repayment_details_report) {
  df_repayment_details_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  df_repayment_details_report.use(Toast);
  df_repayment_details_report.provide('baseURL', baseURL);
  df_repayment_details_report.provide('axios', axios);
  df_repayment_details_report.component('DfRepaymentDetailsReport', DfRepaymentDetailsReport);
  df_repayment_details_report.mount('#df-repayment-details-report');
}
// End Dealer Financing Reports

// Vendor Financing Reports
var _vendor_financing_programs_report = document.getElementById('vendor-financing-programs-report');
if (_vendor_financing_programs_report) {
  vendor_financing_programs_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_financing_programs_report.use(Toast);
  vendor_financing_programs_report.provide('baseURL', baseURL);
  vendor_financing_programs_report.provide('axios', axios);
  vendor_financing_programs_report.component('VendorFinancingProgramsReport', VfProgramReport);
  vendor_financing_programs_report.mount('#vendor-financing-programs-report');
}

var _vf_anchorwise_vendor_report = document.getElementById('vf-anchorwise-vendor-report');
if (_vf_anchorwise_vendor_report) {
  vf_anchorwise_vendor_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_anchorwise_vendor_report.use(Toast);
  vf_anchorwise_vendor_report.provide('baseURL', baseURL);
  vf_anchorwise_vendor_report.provide('axios', axios);
  vf_anchorwise_vendor_report.component('VfAnchorwiseVendorReport', VfAnchorwiseVendorReport);
  vf_anchorwise_vendor_report.mount('#vf-anchorwise-vendor-report');
}

var _vf_vendor_classification_report = document.getElementById('vf-vendor-classification-report');
if (_vf_vendor_classification_report) {
  vf_vendor_classification_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_vendor_classification_report.use(Toast);
  vf_vendor_classification_report.provide('baseURL', baseURL);
  vf_vendor_classification_report.provide('axios', axios);
  vf_vendor_classification_report.component('VfVendorClassificationReport', VfVendorClassificationReport);
  vf_vendor_classification_report.mount('#vf-vendor-classification-report');
}

var _vf_program_mapping_report = document.getElementById('vf-program-mapping-report');
if (_vf_program_mapping_report) {
  vf_program_mapping_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_program_mapping_report.use(Toast);
  vf_program_mapping_report.provide('baseURL', baseURL);
  vf_program_mapping_report.provide('axios', axios);
  vf_program_mapping_report.component('VfProgramMappingReport', VfProgramMappingReport);
  vf_program_mapping_report.mount('#vf-program-mapping-report');
}

var _vendor_daily_outstanding_balance = document.getElementById('vendor-daily-outstanding-balance');
if (_vendor_daily_outstanding_balance) {
  vendor_daily_outstanding_balance.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendor_daily_outstanding_balance.use(Toast);
  vendor_daily_outstanding_balance.provide('baseURL', baseURL);
  vendor_daily_outstanding_balance.provide('axios', axios);
  vendor_daily_outstanding_balance.component('VendorDailyOutstandingBalance', VendorDailyOutstandingBalance);
  vendor_daily_outstanding_balance.mount('#vendor-daily-outstanding-balance');
}

var _dealers_daily_outstanding_balance = document.getElementById('dealers-daily-outstanding-balance');
if (_dealers_daily_outstanding_balance) {
  dealers_daily_outstanding_balance.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  dealers_daily_outstanding_balance.use(Toast);
  dealers_daily_outstanding_balance.provide('baseURL', baseURL);
  dealers_daily_outstanding_balance.provide('axios', axios);
  dealers_daily_outstanding_balance.component('DealersDailyOutstandingBalance', DealersDailyOutstandingBalance);
  dealers_daily_outstanding_balance.mount('#dealers-daily-outstanding-balance');
}

var _vf_fees_and_interest_sharing_report = document.getElementById('vf-fees-and-interest-sharing-report');
if (_vf_fees_and_interest_sharing_report) {
  vf_fees_and_interest_sharing_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_fees_and_interest_sharing_report.use(Toast);
  vf_fees_and_interest_sharing_report.provide('baseURL', baseURL);
  vf_fees_and_interest_sharing_report.provide('axios', axios);
  vf_fees_and_interest_sharing_report.component('VfFeesAndInterestSharingReport', VfFeesAndInterestSharingReport);
  vf_fees_and_interest_sharing_report.mount('#vf-fees-and-interest-sharing-report');
}

var _vf_income_report = document.getElementById('vf-income-report');
if (_vf_income_report) {
  vf_income_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_income_report.use(Toast);
  vf_income_report.provide('baseURL', baseURL);
  vf_income_report.provide('axios', axios);
  vf_income_report.component('VfIncomeReport', VfIncomeReport);
  vf_income_report.mount('#vf-income-report');
}

var _factoring_income_report = document.getElementById('factoring-income-report');
if (_factoring_income_report) {
  factoring_income_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  factoring_income_report.use(Toast);
  factoring_income_report.provide('baseURL', baseURL);
  factoring_income_report.provide('axios', axios);
  factoring_income_report.component('FactoringIncomeReport', FactoringIncomeReport);
  factoring_income_report.mount('#factoring-income-report');
}

var _vf_potential_financing_report = document.getElementById('vf-potential-financing-report');
if (_vf_potential_financing_report) {
  vf_potential_financing_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_potential_financing_report.use(Toast);
  vf_potential_financing_report.provide('baseURL', baseURL);
  vf_potential_financing_report.provide('axios', axios);
  vf_potential_financing_report.component('VfPotentialFinancingReport', VfPotentialFinancingReport);
  vf_potential_financing_report.mount('#vf-potential-financing-report');
}

var _vf_repayment_details_report = document.getElementById('vf-repayment-details-report');
if (_vf_repayment_details_report) {
  vf_repayment_details_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vf_repayment_details_report.use(Toast);
  vf_repayment_details_report.provide('baseURL', baseURL);
  vf_repayment_details_report.provide('axios', axios);
  vf_repayment_details_report.component('VfRepaymentDetailsReport', VfRepaymentDetailsReport);
  vf_repayment_details_report.mount('#vf-repayment-details-report');
}
// End Vendor Financing Reports

var _vendors_report = document.getElementById('vendors-report');
if (_vendors_report) {
  vendors_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  vendors_report.use(Toast);
  vendors_report.provide('baseURL', baseURL);
  vendors_report.component('VendorsReport', VendorsReport);
  vendors_report.mount('#vendors-report');
}

var _bank_gls_report = document.getElementById('bank-gls-report');
if (_bank_gls_report) {
  bank_gls_report.use(i18nVue, {
    resolve: async lang => {
      const langs = import.meta.glob('../../lang/*.json');
      return await langs[`../../lang/${lang}.json`]();
    }
  });
  bank_gls_report.use(Toast);
  bank_gls_report.provide('baseURL', baseURL);
  bank_gls_report.provide('axios', axios);
  bank_gls_report.component('BankGlsReport', BankGlsReport);
  bank_gls_report.mount('#bank-gls-report');
}
