<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\InvoiceController;
use App\Http\Controllers\Vendor\ReportsController;
use App\Http\Controllers\Vendor\FinancialRequestsController;
use App\Http\Controllers\Vendor\PurchaseOrdersController;
use App\Http\Controllers\Vendor\CashPlannerController;
use App\Http\Controllers\Vendor\SettingController;
use App\Http\Controllers\Vendor\HelpController;

Route::group(['prefix' => '/vendor', 'as' => 'vendor.', 'middleware' => ['auth', 'current_company']], function () {
  // Main Page Route
  Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
  Route::get('/dashboard/invoices/data', [DashboardController::class, 'invoicesData']);

  Route::get('/notifications/view', [DashboardController::class, 'notifications'])->name('notifications');
  Route::get('/notifications/view/data', [DashboardController::class, 'notificationsData']);
  Route::get('/notifications/{notification}/read', [DashboardController::class, 'notificationRead']);
  Route::get('/notifications/all/update/read', [DashboardController::class, 'notificationReadAll']);

  // Invoices
  Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoice-index');
  Route::get('/invoices/data', [InvoiceController::class, 'invoices']);
  Route::get('/invoices/pending/data', [InvoiceController::class, 'pendingInvoices']);
  Route::get('/invoices/create/{invoice?}', [InvoiceController::class, 'create'])->name('invoice-create');
  Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoice-edit');
  Route::get('/invoices/invoice-number/{number}/check', [InvoiceController::class, 'checkInvoiceNumber']);
  Route::get('/invoices/delete/{invoice}', [InvoiceController::class, 'deleteDraft'])->name('invoice-delete');
  Route::get('/invoices/create/{company}/programs', [InvoiceController::class, 'programs'])->name('invoice-programs');
  Route::get('/invoices/program/{program}/{company}', [InvoiceController::class, 'program']);
  Route::post('/invoices/store', [InvoiceController::class, 'store'])->name('invoice-store');
  Route::post('/invoices/{invoice}/update', [InvoiceController::class, 'update'])->name('invoice-update');
  Route::post('/invoices/draft/store', [InvoiceController::class, 'storeDraft']);
  Route::get('/invoices/payments', [InvoiceController::class, 'payments'])->name('invoice-payments');
  Route::get('/invoices/payments/data', [InvoiceController::class, 'paymentsData']);
  Route::post('/invoices/{invoice}/request/send', [InvoiceController::class, 'sendInvoiceForApproval'])->name('invoice-approval-send');
  Route::post('invoices/import', [InvoiceController::class, 'import'])->name('invoices-import');
  Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices-export');
  Route::get('/invoices/accounts', [InvoiceController::class, 'accounts'])->name('invoices-accounts');
  Route::get('invoices/accounts/data', [InvoiceController::class, 'loanAccounts']);
  Route::get('invoices/sample/download', [InvoiceController::class, 'downloadSample'])->name('invoices.sample.download');
  Route::get('/invoices/upload/error-report/download', [InvoiceController::class, 'downloadErrorReport']);
  Route::get('/invoices/uploaded', [InvoiceController::class, 'uploaded'])->name('invoices.uploaded');
  Route::get('/invoices/uploaded/data', [InvoiceController::class, 'uploadedInvoices']);
  Route::get('/invoices/uploaded/export', [InvoiceController::class, 'exportUploadedInvoices']);
  Route::delete('invoices/{invoice}/delete', [InvoiceController::class, 'delete']);
  Route::get('invoices/{invoice}/attachment/delete', [InvoiceController::class, 'deleteAttachment'])->name('invoices.attachment.delete');
  Route::post('/invoices/attachments/store', [InvoiceController::class, 'storeAttachments'])->name('invoices.attachment.store');

  Route::get('/invoices/{invoice}/details', [InvoiceController::class, 'show']);
  Route::get('/invoices/{invoice}/{date}/remittance/calculate', [InvoiceController::class, 'calculateRemittance']);
  Route::post('/invoices/remittance/amount', [CashPlannerController::class, 'remittanceAmountDetails']);

  Route::get('/invoices/{invoice}/pdf/download', [InvoiceController::class, 'download'])->name('invoice.download');
  Route::get('/invoices/payment-instruction/{invoice}/pdf/download', [InvoiceController::class, 'downloadPaymentInstruction'])->name('invoice.payment-instruction.download');

  // Reports
  Route::get('/reports/dashboard', [ReportsController::class, 'index'])->name('reports');
  Route::get('/reports/dashboard/data', [ReportsController::class, 'data']);
  Route::get('/reports/all-invoices-report/view', [ReportsController::class, 'allInvoicesReportView'])->name('reports.all-invoices');
  Route::get('/reports/programs-report/view', [ReportsController::class, 'programsReportView'])->name('reports.programs');
  Route::get('/reports/payments-report/view', [ReportsController::class, 'paymentsReportView'])->name('reports.payments');
  Route::get('/reports/all-invoices-report', [ReportsController::class, 'allInvoicesReport']);
  Route::get('/reports/programs-report', [ReportsController::class, 'programsReport']);
  Route::get('/reports/payments-report', [ReportsController::class, 'paymentsReport']);
  Route::get('/report/{type}/export', [ReportsController::class, 'export']);
  Route::get('/report/{type}/pdf/export', [ReportsController::class, 'exportPdf']);
  // Finacing Requests
  Route::get('/finance/requests', [FinancialRequestsController::class, 'index'])->name('financialrequests');
  Route::get('/finance-requests/data', [FinancialRequestsController::class, 'financeRequests']);
  Route::post('/finance-requests/{payment_request}/status/update', [FinancialRequestsController::class, 'updateFinanceRequest']);
  // Purchase Orders
  Route::get('/purchase-orders', [PurchaseOrdersController::class, 'index'])->name('purchase-orders.index');
  Route::get('/purchase-orders/data', [PurchaseOrdersController::class, 'purchaseOrdersData']);
  Route::get('/purchase-orders/pending/data', [PurchaseOrdersController::class, 'pendingPurchaseOrdersData']);
  Route::get('/purchase-orders/{purchase_order}/show', [PurchaseOrdersController::class, 'show']);
  Route::get('/purchase-orders/{purchase_order}/pdf/download', [PurchaseOrdersController::class, 'downloadPurchaseOrder']);
  Route::post('/purchase-orders/status/update', [PurchaseOrdersController::class, 'update']);
  Route::post('/purchase-orders/bulk/approve', [PurchaseOrdersController::class, 'bulkUpdateStatus']);
  Route::get('/purchase-orders/{purchase_order}/convert', [PurchaseOrdersController::class, 'convertToInvoice']);
  Route::get('/purchase-orders/{purchase_order}/edit', [PurchaseOrdersController::class, 'edit']);
  Route::post('/purchase-orders/{purchase_order}/update', [PurchaseOrdersController::class, 'updatePO'])->name('purchase-order.update');
  Route::get('/purchase-orders/program/{program}', [InvoiceController::class, 'program']);
  // Cash planner
  Route::get('/cash/planner', [CashPlannerController::class, 'index'])->name('cashplanner');
  Route::get('/cash-planner/programs', [CashPlannerController::class, 'programs']);
  Route::get('/cash-planner/invoices/eligible/data', [CashPlannerController::class, 'eligibleInvoices']);
  Route::get('/cash-planner/invoices/non-eligible/data', [CashPlannerController::class, 'nonEligibleInvoices']);
  Route::post('/cash-planner/invoices/request/send', [CashPlannerController::class, 'requestFinance']);
  Route::post('/cash-planner/invoices/request/multiple/send', [CashPlannerController::class, 'requestMultipleFinance']);
  Route::get('/cash-planner/program/{program}/financing/eligible', [CashPlannerController::class, 'eligibleForFinancing']);
  Route::get('/cash-planner/program/{program}/{payment_date}/financing/calculate', [CashPlannerController::class, 'eligibleForFinancingCalculate']);
  Route::post('/cash-planner/financing/requests/store', [CashPlannerController::class, 'storeMassFinancingRequest'])->name('cashplanner.financing_requests.store');
  Route::post('/cash-planner/financing/requests/export', [CashPlannerController::class, 'exportInvoices'])->name('cashplanner.financing_requests.export');
  Route::get('/cash-planner/program/planner/calculate', [CashPlannerController::class, 'plannerCalculate']);
  Route::get('/cash-planner/invoices/{invoice}/noa/download', [CashPlannerController::class, 'downloadNoa'])->name('cash-planner.invoices.noa.download');
  Route::get('/cash-planner/invoices/terms/{type}/download', [CashPlannerController::class, 'downloadTerms'])->name('cash-planner.invoices.terms.download');
  // Settings
  Route::get('/settings', [SettingController::class, 'index'])->name('settings');
  Route::get('/settings/bank-accounts-data', [SettingController::class, 'bankAccountsData'])->name('settings.bank-accounts-data');
  Route::get('/settings/anchors', [SettingController::class, 'anchors'])->name('settings.anchors');
  Route::post('/settings/anchors/update', [SettingController::class, 'updateAnchorSettings'])->name('settings.anchors.update');
  Route::get('/settings/invoice-settings', [SettingController::class, 'invoiceSettings'])->name('settings.invoice-settings');
  Route::post('/settings/invoice-settings/update', [SettingController::class, 'updateInvoiceSettings'])->name('settings.invoice-settings.update');
  Route::get('/settings/invoice-settings/status/{status}/update', [SettingController::class, 'updateStatus']);
  Route::get('/settings/taxes', [SettingController::class, 'taxes']);
  Route::post('/settings/taxes', [SettingController::class, 'addTax']);
  Route::delete('/settings/taxes/{id}', [SettingController::class, 'deleteTax']);

  // Company Profile
  Route::get('/profile/company/{company}', [SettingController::class, 'companyProfile'])->name('company.profile');

  Route::post('/company/switch', [SettingController::class, 'switchCompany'])->name('company.switch');

  // Help and Manuals
  Route::get('/help/view', [HelpController::class, 'index'])->name('help.index');
});
