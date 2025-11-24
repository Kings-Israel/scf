<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\laravel_example\UserManagement;

$controller_path = 'App\Http\Controllers\Buyer';

Route::group(['prefix' => '/buyer', 'as' => 'buyer.', 'middleware' => ['auth', 'current_company']], function () use (
  $controller_path
) {
  Route::post('/company/switch', $controller_path . '\ConfigurationsController@switchCompany')->name('company.switch');

  // Main Page Route
  Route::get('/dashboard', $controller_path . '\DashboardController@index')->name('dashboard');
  Route::get('/dashboard/invoices/data', $controller_path . '\DashboardController@invoicesData');

  Route::get('/notifications/view', $controller_path . '\DashboardController@notifications')->name('notifications');
  Route::get('/notifications/view/data', $controller_path . '\DashboardController@notificationsData');
  Route::get('/notifications/{notification}/read', $controller_path . '\DashboardController@notificationRead');

  // Invoices
  Route::get('/invoices', $controller_path . '\InvoicesController@index')->name('invoices.index');
  Route::get('/invoices/data', $controller_path . '\InvoicesController@invoices');
  Route::get('/invoices/pending', $controller_path . '\InvoicesController@pending')->name('invoices.pending');
  Route::get('/invoices/pending/data', $controller_path . '\InvoicesController@pendingInvoices');
  Route::get('/invoices/expired', $controller_path . '\InvoicesController@expired')->name('invoices.expired');
  Route::get('/invoices/create', $controller_path . '\InvoicesController@create')->name('invoices.create');
  Route::get('/invoices/{invoice}/edit', $controller_path . '\InvoicesController@edit')->name('invoices.edit');
  Route::post('/invoices/{invoice}/update', $controller_path . '\InvoicesController@update')->name('invoices.update');
  Route::get('/invoices/payments', $controller_path . '\InvoicesController@payments')->name('invoices.payments');
  Route::post('invoices/import', $controller_path . '\InvoicesController@import')->name('invoices.import');
  Route::get('invoices/export', $controller_path . '\InvoicesController@export');
  Route::get('/invoices/sample/download', $controller_path . '\InvoicesController@downloadSample')->name(
    'invoices.sample.download'
  );
  Route::get('/invoices/uploaded', $controller_path . '\InvoicesController@uploaded')->name('invoices.uploaded');
  Route::get('/invoices/uploaded/data', $controller_path . '\InvoicesController@uploadedInvoices');
  Route::get('/invoices/uploaded/export', $controller_path . '\InvoicesController@exportUploadedInvoices');
  Route::get('/invoices/upload/error-report/download', $controller_path . '\InvoicesController@downloadErrorReport');
  Route::get('/invoices/payment-instructions', $controller_path . '\InvoicesController@buyerPaymentInstructions')->name(
    'invoices.payment-instructions'
  );
  Route::get('/payment-instructions/data', $controller_path . '\InvoicesController@paymentInstructionsData');

  Route::get('/invoices/{invoice}/details', $controller_path . '\InvoicesController@show');

  Route::get('/invoices/{invoice}/pdf/download', $controller_path . '\InvoicesController@download')->name(
    'invoice.download'
  );
  Route::get(
    '/invoices/payment-instruction/{invoice}/pdf/download',
    $controller_path . '\InvoicesController@downloadPaymentInstruction'
  )->name('invoice.payment-instruction.download');

  Route::get('/invoices/anchors', $controller_path . '\InvoicesController@anchors')->name('invoices.anchors');
  Route::get('/invoices/anchors/data', $controller_path . '\InvoicesController@anchorsData');

  Route::post('/invoices/{invoice}/approve', $controller_path . '\InvoicesController@approve')->name(
    'invoices.approve'
  );
  Route::get('/invoices/{invoice}/fees/approve', $controller_path . '\InvoicesController@approveFees')->name(
    'invoices.fees.approve'
  );
  Route::post('/invoices/{invoice}/reject', $controller_path . '\InvoicesController@reject')->name('invoices.reject');
  Route::post('/invoices/bulk/update/approve', $controller_path . '\InvoicesController@bulkApprove');
  Route::post('/invoices/bulk/update/reject', $controller_path . '\InvoicesController@bulkReject');
  Route::post('/invoices/bulk/details', $controller_path . '\InvoicesController@bulkDetails');

  // Financing Requests
  Route::get('/financing-requests', $controller_path . '\InvoicesController@requests')->name(
    'planner.financing-requests'
  );
  Route::get('/repayments', $controller_path . '\InvoicesController@repayments')->name('planner.repayments');

  // Programs
  Route::get('/programs', $controller_path . '\ProgramsController@programs')->name('programs');

  // Purchase Orders
  Route::get('/purchase-orders', $controller_path . '\PurchaseOrdersController@index')->name('purchase-orders.index');
  Route::get('/purchase-orders/data', $controller_path . '\PurchaseOrdersController@purchaseOrdersData');
  Route::get('/purchase-orders/create', $controller_path . '\PurchaseOrdersController@create')->name(
    'purchase-orders.create'
  );
  Route::post('/purchase-orders/store', $controller_path . '\PurchaseOrdersController@store')->name(
    'purchase-orders.store'
  );
  Route::post('/purchase-orders/approve', $controller_path . '\PurchaseOrdersController@approve')->name(
    'purchase-orders.approve'
  );
  Route::post('/purchase-orders/bulk/approve', $controller_path . '\PurchaseOrdersController@bulkApprove');
  Route::get('/purchase-orders/{purchaseOrder}/show', $controller_path . '\PurchaseOrdersController@show')->name(
    'purchase-orders.show'
  );
  Route::get(
    '/purchase-orders/{purchase_order}/pdf/download',
    $controller_path . '\PurchaseOrdersController@downloadPurchaseOrder'
  )->name('purchase-orders.download');

  // Reports
  Route::get('/reports/dashboard', $controller_path . '\ReportsController@index')->name('reports');
  Route::get('/reports/dashboard/data', $controller_path . '\ReportsController@data');
  Route::get('/reports/report', $controller_path . '\ReportsController@reports');
  Route::get('/reports/invoice-analysis-report', $controller_path . '\ReportsController@invoiceAnalysisView')->name(
    'reports.invoice-analysis'
  );
  Route::get('/reports/maturing-invoices-report', $controller_path . '\ReportsController@maturingInvoicesView')->name(
    'reports.maturing-invoices'
  );
  Route::get('/reports/paid-invoices-report', $controller_path . '\ReportsController@paidInvoicesView')->name(
    'reports.paid-invoices'
  );
  Route::get('/reports/overdue-invoices-report', $controller_path . '\ReportsController@overdueInvoicesView')->name(
    'reports.overdue-invoices'
  );
  Route::get('/reports/closed-invoices-report', $controller_path . '\ReportsController@closedInvoicesView')->name(
    'reports.closed-invoices'
  );
  Route::get('/reports/all-invoices-report', $controller_path . '\ReportsController@allInvoicesView')->name(
    'reports.all-invoices'
  );
  Route::get('/reports/report/{type}/export', $controller_path . '\ReportsController@export');
  Route::get('/report/{type}/pdf/export', $controller_path . '\ReportsController@exportPdf');

  // Configurations
  Route::get('/configurations/view', $controller_path . '\ConfigurationsController@general')->name(
    'configurations.index'
  );
  Route::get('/configurations/bank-accounts', $controller_path . '\ConfigurationsController@bankAccounts');
  Route::get(
    '/configurations/purchase-order-settings',
    $controller_path . '\ConfigurationsController@purchaseOrderSettings'
  );
  Route::post(
    '/configurations/purchase-order-settings/update',
    $controller_path . '\ConfigurationsController@updatePurchaseOrderSettings'
  );
  Route::get(
    '/configurations/purchase-order-settings/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateStatus'
  );
  Route::get('/configurations/company', $controller_path . '\ConfigurationsController@company')->name(
    'configurations.company'
  );
  Route::get('/configurations/vendors', $controller_path . '\ConfigurationsController@vendors')->name(
    'configurations.vendors'
  );
  Route::post(
    '/configurations/vendor-settings/{id}/update',
    $controller_path . '\ConfigurationsController@updateVendorSettings'
  );
  Route::get(
    '/configurations/vendor-settings/{program_vendor_configuration}/approve/{status}',
    $controller_path . '\ConfigurationsController@approveVendorSettings'
  )->name('program_vendor_configuration.change.approve');

  // Manuals and Help
  Route::get('/help/view', $controller_path . '\HelpController@index')->name('help.index');

  // Company Profile
  Route::get('/profile/company', $controller_path . '\ConfigurationsController@company')->name('company.profile');
});

// Dealer
Route::group(['prefix' => '/dealer', 'as' => 'dealer.', 'middleware' => ['auth', 'current_company']], function () use (
  $controller_path
) {
  Route::post('/company/switch', $controller_path . '\ConfigurationsController@switchCompany')->name('company.switch');

  // Dashboard
  Route::get('/dashboard', $controller_path . '\DashboardController@dealer')->name('dashboard');
  Route::get('/dashboard/invoices/data', $controller_path . '\DashboardController@dealerInvoicesData');

  Route::get('/notifications/view', $controller_path . '\DashboardController@notificationsDealer')->name(
    'notifications'
  );
  Route::get('/notifications/view/data', $controller_path . '\DashboardController@notificationsDataDealer');
  Route::get('/notifications/{notification}/read', $controller_path . '\DashboardController@notificationRead');

  // Invoices
  Route::get('/invoices', $controller_path . '\InvoicesController@dealerPendingInvoices')->name('invoices.index');
  Route::get('/invoices/data', $controller_path . '\InvoicesController@dealerInvoices');
  Route::get('/invoices/data/export', $controller_path . '\InvoicesController@dealerExportInvoices');
  Route::get('/invoices/invoice-number/{number}/check', $controller_path . '\InvoicesController@checkInvoiceNumber');
  Route::get('/invoices/payment-instructions', $controller_path . '\InvoicesController@paymentInstructions')->name(
    'invoices.payment-instructions'
  );
  Route::get('/invoices/initiate-drawdown/{company}/programs', $controller_path . '\InvoicesController@programs')->name(
    'invoice-programs'
  );
  Route::get('/invoices/initiate-drawdown/program/{program}/details', $controller_path . '\InvoicesController@program');
  Route::get('/invoices/initiate-drawdown/{invoice?}', $controller_path . '\InvoicesController@initiateDrawdown')->name(
    'invoices.initiate-drawdown'
  );
  Route::post('/invoices/initiate-drawdown', $controller_path . '\InvoicesController@storeDrawdownInvoice')->name(
    'invoices.initiate-drawdown.store'
  );
  Route::get('/invoices/{invoice}/request/send', $controller_path . '\InvoicesController@sendInvoiceForApproval')->name(
    'invoice-approval-send'
  );

  Route::get('/invoices/{invoice}/details', $controller_path . '\InvoicesController@show');

  Route::get('/invoices/{invoice}/pdf/download', $controller_path . '\InvoicesController@download')->name(
    'invoice.download'
  );
  Route::get(
    '/invoices/payment-instruction/{invoice}/pdf/download',
    $controller_path . '\InvoicesController@downloadPaymentInstruction'
  )->name('invoice.payment-instruction.download');

  Route::post('/invoices/attachments/store', $controller_path . '\InvoicesController@storeAttachments')->name(
    'invoices.attachment.store'
  );
  Route::get('/invoices/sample/download', $controller_path . '\InvoicesController@dealerDownloadSample')->name(
    'invoices.sample.download'
  );
  Route::get('/invoices/anchors', $controller_path . '\InvoicesController@dealerAnchors')->name(
    'invoices.dealer.anchors'
  );
  Route::post('invoices/import', $controller_path . '\InvoicesController@dealerImport')->name('invoices.dealer.import');
  Route::get(
    '/invoices/upload/error-report/download',
    $controller_path . '\InvoicesController@dealerDownloadErrorReport'
  );
  Route::get('/invoices/uploaded', $controller_path . '\InvoicesController@dealerUploadedInvoices')->name(
    'invoices.uploaded'
  );
  Route::get('/invoices/uploaded/data', $controller_path . '\InvoicesController@dealerUploadedInvoicesData');
  Route::get('/invoices/uploaded/export', $controller_path . '\InvoicesController@dealerExportUploadedInvoices');

  // Planner
  Route::get('/planner', $controller_path . '\InvoicesController@planner')->name('planner.index');
  Route::get('/planner/calculate', $controller_path . '\InvoicesController@plannerCalculate');
  Route::get('/planner/programs/data', $controller_path . '\InvoicesController@dealerPrograms');
  Route::get('/cash-planner/invoices/eligible/data', $controller_path . '\InvoicesController@eligibleInvoices');
  Route::get('/cash-planner/invoices/non-eligible/data', $controller_path . '\InvoicesController@nonEligibleInvoices');
  Route::post('/cash-planner/invoices/request/send', $controller_path . '\InvoicesController@requestFinance');
  Route::post(
    '/cash-planner/invoices/request/multiple/send',
    $controller_path . '\InvoicesController@requestMultipleFinance'
  );
  Route::get('/planner/financing-requests', $controller_path . '\InvoicesController@financingRequests')->name(
    'planner.financing-requests'
  );
  Route::get('/planner/financing-requests/data', $controller_path . '\InvoicesController@financingRequestsData');
  Route::post(
    '/planner/financing-requests/{payment_request}/status/update',
    $controller_path . '\InvoicesController@updateFinanceRequest'
  );
  Route::get(
    '/planner/program/{program}/financing/eligible',
    $controller_path . '\InvoicesController@eligibleForFinancing'
  );
  Route::get(
    '/planner/program/{program}/{payment_date}/financing/calculate',
    $controller_path . '\InvoicesController@eligibleForFinancingCalculate'
  );
  Route::post(
    '/planner/financing/requests/store',
    $controller_path . '\InvoicesController@storeMassFinancingRequest'
  )->name('planner.financing_requests.store');
  Route::post('/invoices/remittance/amount', $controller_path . '\InvoicesController@remittanceAmountDetails');
  Route::get('/planner/invoices/{invoice}/noa/download', $controller_path . '\InvoicesController@downloadNoa')->name(
    'cash-planner.invoices.noa.download'
  );
  Route::get('/planner/invoices/terms/{type}/download', $controller_path . '\InvoicesController@downloadTerms')->name(
    'cash-planner.invoices.terms.download'
  );

  // Accounts
  Route::get('/accounts/od-repayments', $controller_path . '\InvoicesController@odRepayments')->name(
    'accounts.od-repayments'
  );
  Route::get('/accounts/od-repayments/data', $controller_path . '\InvoicesController@odRepaymentsData');
  Route::get('/accounts/od-details', $controller_path . '\InvoicesController@odDetails')->name('accounts.od-details');
  Route::get(
    '/accounts/od-accounts/{program_vendor_configuration}/od-details',
    $controller_path . '\InvoicesController@odAccountDetails'
  );
  Route::get(
    '/accounts/od-accounts/{program_vendor_configuration}/payments/data',
    $controller_path . '\InvoicesController@odAccountPayments'
  );
  Route::get('/accounts/od-details/data', $controller_path . '\InvoicesController@odAccountsData');
  Route::post('/accounts/od-details/credit-account', $controller_path . '\InvoicesController@creditAccount');

  // Reports
  Route::get('/reports/dashboard', $controller_path . '\ReportsController@dealerIndex')->name('reports');
  Route::get('/reports/dashboard/data', $controller_path . '\ReportsController@dealerFinancingData');
  Route::get('/reports/all-invoices-report', $controller_path . '\ReportsController@dealerFinancingAllInvoicesReport');
  Route::get('/reports/programs-report', $controller_path . '\ReportsController@dealerFinancingProgramsReport');
  Route::get('/reports/payments-report', $controller_path . '\ReportsController@dealerFinancingPaymentsReport');
  Route::get('/reports/od-accounts/report', $controller_path . '\ReportsController@odAccountsReport');
  Route::get('/report/{type}/export', $controller_path . '\ReportsController@export');
  Route::get('/report/{type}/pdf/export', $controller_path . '\ReportsController@exportPdf');
  Route::get(
    '/reports/all-invoices-report/view',
    $controller_path . '\ReportsController@dealerAllInvoicesReportView'
  )->name('reports.all-invoices');
  Route::get('/reports/programs-report/view', $controller_path . '\ReportsController@dealerProgramsReportView')->name(
    'reports.programs'
  );
  Route::get('/reports/payments-report/view', $controller_path . '\ReportsController@dealerPaymentsReportView')->name(
    'reports.payments'
  );
  Route::get('/reports/dpd-invoices', $controller_path . '\InvoicesController@dpdInvoices');
  Route::get('/reports/dpd-invoices/data', $controller_path . '\InvoicesController@dpdInvoicesData');
  Route::get('/reports/rejected-invoices', $controller_path . '\InvoicesController@rejectedInvoices');
  Route::get('/reports/rejected-invoices/data', $controller_path . '\InvoicesController@rejectedInvoicesData');

  // Configurations
  Route::get('/settings/view', $controller_path . '\ConfigurationsController@dealer')->name('configurations');
  Route::get('/settings/bank-accounts', $controller_path . '\ConfigurationsController@dealerBankAccounts');
  Route::get('/settings/invoice-settings', $controller_path . '\ConfigurationsController@dealerInvoiceSettings');
  Route::post(
    '/settings/invoice-settings/update',
    $controller_path . '\ConfigurationsController@updateInvoiceSettings'
  );
  Route::get(
    '/settings/invoice-settings/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateDealerStatus'
  );
  Route::get(
    '/settings/purchase-order-settings',
    $controller_path . '\ConfigurationsController@dealerPurchaseOrderSettings'
  );
  Route::post(
    '/settings/purchase-order-settings/update',
    $controller_path . '\ConfigurationsController@updateDealerPurchaseOrderSettings'
  );
  Route::get(
    '/settings/purchase-order-settings/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateDealerPurchaseOrderStatus'
  );
  Route::get('/settings/taxes', $controller_path . '\ConfigurationsController@taxes');
  Route::post('/settings/taxes', $controller_path . '\ConfigurationsController@addTax');
  Route::delete('/settings/taxes/{id}', $controller_path . '\ConfigurationsController@deleteTax');
  Route::get('/settings/anchor-settings', $controller_path . '\ConfigurationsController@vendor')->name(
    'configurations.anchor-settings'
  );
  Route::post('/settings/anchor-settings/{id}/update', $controller_path . '\ConfigurationsController@updateWht');

  // Manuals and Help
  Route::get('/help/view', $controller_path . '\HelpController@dealerIndex')->name('help.index');

  // Company Profile
  Route::get('/profile/company', $controller_path . '\ConfigurationsController@companyProfile')->name(
    'company.profile'
  );
});
