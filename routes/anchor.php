<?php

use Illuminate\Support\Facades\Route;

$controller_path = 'App\Http\Controllers\Anchor';

Route::group(['prefix' => '/anchor', 'as' => 'anchor.', 'middleware' => ['auth', 'current_company']], function () use (
  $controller_path
) {
  // Main Page Route
  Route::get('/dashboard', $controller_path . '\DashboardController@index')->name('dashboard');
  Route::get('/dashboard/invoices/data', $controller_path . '\DashboardController@invoicesData');
  Route::get('/dashboard/invoices/factoring/data', $controller_path . '\DashboardController@factoringInvoicesData');
  Route::get('/dashboard/invoices/dealer/data', $controller_path . '\DashboardController@dealerInvoicesData');

  Route::get('/notifications/view', $controller_path . '\DashboardController@notifications')->name('notifications');
  Route::get('/notifications/view/data', $controller_path . '\DashboardController@notificationsData');
  Route::get('/notifications/{notification}/read', $controller_path . '\DashboardController@notificationRead');
  Route::get('/notifications/all/update/read', $controller_path . '\DashboardController@notificationReadAll');

  // Invoices
  Route::get('/invoices', $controller_path . '\InvoicesController@index')->name('invoices');
  Route::get('/invoices/data', $controller_path . '\InvoicesController@invoices');
  Route::get('/invoices/pending/data', $controller_path . '\InvoicesController@pendingInvoices');
  Route::get('/invoices/uploaded/view', $controller_path . '\InvoicesController@uploaded')->name('invoices.uploaded');
  Route::get('/invoices/uploaded/data', $controller_path . '\InvoicesController@uploadedInvoices');
  Route::get('/invoices/uploaded/export', $controller_path . '\InvoicesController@exportUploadedInvoices');
  Route::get('/invoices/{invoice}/edit', $controller_path . '\InvoicesController@edit')->name('invoices.edit');
  Route::post('/invoices/{invoice}/approve', $controller_path . '\InvoicesController@approve')->name(
    'invoices.approve'
  );
  Route::get('/invoices/{invoice}/fees/approve', $controller_path . '\InvoicesController@approveFees')->name(
    'invoices.fees.approve'
  );
  Route::post('/invoices/{invoice}/update', $controller_path . '\InvoicesController@update')->name('invoices.update');
  Route::post('/invoices/bulk/update/approve', $controller_path . '\InvoicesController@bulkApprove');
  Route::post('/invoices/bulk/update/reject', $controller_path . '\InvoicesController@bulkReject');
  Route::post('/invoices/bulk/details', $controller_path . '\InvoicesController@bulkDetails');
  Route::post('/invoices/{invoice}/reject', $controller_path . '\InvoicesController@reject')->name('invoices.reject');
  Route::post('invoices/import', $controller_path . '\InvoicesController@import')->name('invoices.import');
  Route::get('invoices/export', $controller_path . '\InvoicesController@export');
  Route::get('/invoices/sample/download', $controller_path . '\InvoicesController@downloadSample')->name(
    'invoices.sample.download'
  );
  Route::get('/invoices/upload/error-report/download', $controller_path . '\InvoicesController@downloadErrorReport');
  Route::get('/payment-instructions', $controller_path . '\InvoicesController@paymentInstructions')->name(
    'invoices.payment-instructions'
  );
  Route::get('/payment-instructions/data', $controller_path . '\InvoicesController@paymentInstructionsData');

  Route::get('/invoices/vendors', $controller_path . '\InvoicesController@vendors')->name('invoices.vendors');
  Route::get('/invoices/vendors/data', $controller_path . '\InvoicesController@vendorsData');

  Route::get('/invoices/{invoice}/details', $controller_path . '\InvoicesController@show');
  Route::get(
    '/invoices/{invoice}/{date}/remittance/calculate',
    $controller_path . '\InvoicesController@calculateRemittance'
  );

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

  // Purchase Orders
  Route::get('/purchase-orders', $controller_path . '\PurchaseOrdersController@index')->name('purchase-orders.index');
  Route::get('/purchase-orders/data', $controller_path . '\PurchaseOrdersController@purchaseOrdersData');
  Route::get('/purchase-orders/create', $controller_path . '\PurchaseOrdersController@create')->name(
    'purchase-orders.create'
  );
  Route::get(
    '/purchase-orders/create/purchase-order-number/{number}/check',
    $controller_path . '\PurchaseOrdersController@checkPurchaseOrderNumber'
  );
  Route::post('/purchase-orders/store', $controller_path . '\PurchaseOrdersController@store')->name(
    'purchase-orders.store'
  );
  Route::post('/purchase-orders/status/update', $controller_path . '\PurchaseOrdersController@updateStatus');
  Route::post('/purchase-orders/approve', $controller_path . '\PurchaseOrdersController@approve')->name(
    'purchase-orders.approve'
  );
  Route::post('/purchase-orders/bulk/approve', $controller_path . '\PurchaseOrdersController@bulkApprove')->name(
    'purchase-orders.bulk.approve'
  );
  Route::get('/purchase-orders/{purchaseOrder}/show', $controller_path . '\PurchaseOrdersController@show')->name(
    'purchase-orders.show'
  );
  Route::get(
    '/purchase-orders/{purchase_order}/pdf/download',
    $controller_path . '\PurchaseOrdersController@downloadPurchaseOrder'
  )->name('purchase-orders.download');

  // Reports
  Route::get('/reports/view', $controller_path . '\ReportsController@index')->name('reports');
  Route::get('/reports/reports/data', $controller_path . '\ReportsController@data');
  Route::get('/reports/report', $controller_path . '\ReportsController@reports');
  Route::get('/reports/report/{type}/export', $controller_path . '\ReportsController@export');
  Route::get('/reports/{type}/pdf/export', $controller_path . '\ReportsController@exportPdf');
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
  Route::get('/reports/discountings-report', $controller_path . '\ReportsController@discountingsView')->name(
    'reports.discountings'
  );

  // Configurations
  Route::get('/configurations/general', $controller_path . '\ConfigurationsController@general')->name(
    'configurations-general'
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
    'configurations-company'
  );
  Route::get('/configurations/vendor', $controller_path . '\ConfigurationsController@vendor')->name(
    'configurations-vendor'
  );
  Route::post(
    '/configurations/vendor-settings/{id}/update',
    $controller_path . '\ConfigurationsController@updateVendorSettings'
  );
  Route::get(
    '/configurations/vendor-settings/{program_vendor_configuration}/approve/{status}',
    $controller_path . '\ConfigurationsController@approveVendorSettings'
  )->name('program_vendor_configuration.change.approve');

  Route::get('/configurations/invoice-settings', $controller_path . '\ConfigurationsController@invoiceSettings')->name(
    'settings.invoice-settings'
  );
  Route::post(
    '/configurations/invoice-settings/update',
    $controller_path . '\ConfigurationsController@updateAnchorInvoiceSettings'
  )->name('settings.invoice-settings.update');
  Route::get(
    '/configurations/invoice-settings/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateAnchorStatus'
  );

  Route::get('/help/view', $controller_path . '\HelpController@index')->name('help.index');

  // Factoring
  Route::group(['prefix' => '/factoring', 'as' => 'factoring-'], function () use ($controller_path) {
    // Dashboard
    Route::get('/', $controller_path . '\DashboardController@factoring')->name('dashboard');

    Route::get('/notifications/view', $controller_path . '\DashboardController@notificationsFactoring')->name(
      'notifications'
    );
    Route::get('/notifications/view/data', $controller_path . '\DashboardController@notificationsDataFactoring');
    Route::get('/notifications/{notification}/read', $controller_path . '\DashboardController@notificationRead');
    Route::get(
      '/notifications/all/update/read',
      $controller_path . '\DashboardController@factoringNotificationReadAll'
    );

    // Purchase Orders
    Route::get('/purchase-order', $controller_path . '\PurchaseOrdersController@factoringIndex')->name(
      'purchase-orders.index'
    );
    Route::get('/purchase-orders/data', $controller_path . '\PurchaseOrdersController@factoringPurchaseOrdersData');
    Route::get(
      '/purchase-orders/pending/data',
      $controller_path . '\PurchaseOrdersController@factoringPendingPurchaseOrdersData'
    );
    Route::get('/purchase-orders/{purchase_order}/show', $controller_path . '\PurchaseOrdersController@show');
    Route::get(
      '/purchase-orders/{purchase_order}/pdf/download',
      $controller_path . '\PurchaseOrdersController@downloadPurchaseOrder'
    )->name('purchase-orders.download');
    Route::post('/purchase-orders/status/update', $controller_path . '\PurchaseOrdersController@updateStatus');
    Route::get(
      '/purchase-orders/{purchase_order}/convert',
      $controller_path . '\PurchaseOrdersController@convertToInvoice'
    );
    Route::get('/purchase-orders/program/{program}', $controller_path . '\PurchaseOrdersController@program');
    // Invoices
    Route::get('/invoices', $controller_path . '\InvoicesController@factoringIndex')->name('invoices-index');
    Route::get('/invoices/data', $controller_path . '\InvoicesController@factoringInvoicesData');
    Route::get('/invoices/expired/data', $controller_path . '\InvoicesController@factoringExpiredInvoicesData');
    Route::get('/invoices/create/{invoice?}', $controller_path . '\InvoicesController@factoringCreate')->name(
      'invoices-create'
    );
    Route::get(
      '/invoices/drawdown/create/{invoice?}',
      $controller_path . '\InvoicesController@factoringDrawdownCreate'
    )->name('invoices-drawdown-create');
    Route::get('/invoices/{invoice}/edit', $controller_path . '\InvoicesController@factoringEdit')->name(
      'invoices-edit'
    );
    Route::get(
      '/invoices/{invoice}/fees/approve',
      $controller_path . '\InvoicesController@sendInvoiceForApproval'
    )->name('invoices.fees.approve');
    Route::get(
      '/invoices/create/invoice-number/{number}/check/{dealer?}',
      $controller_path . '\InvoicesController@checkInvoiceNumber'
    );
    Route::get('/invoices/create/{company}/programs', $controller_path . '\InvoicesController@programs')->name(
      'invoice-programs'
    );
    Route::get(
      '/invoices/drawdown/create/{company}/programs',
      $controller_path . '\InvoicesController@drawdownPrograms'
    )->name('invoice-drawdown-programs');
    Route::post('/invoices/store', $controller_path . '\InvoicesController@store')->name('invoices-store');
    Route::post('/invoices/drawdown/store', $controller_path . '\InvoicesController@drawdownStore')->name(
      'invoices-drawdown-store'
    );
    Route::post('/invoices/{invoice}/update', $controller_path . '\InvoicesController@factoringUpdate')->name(
      'invoices-update'
    );
    Route::get('/invoices/expired', $controller_path . '\InvoicesController@factoringExpired')->name(
      'invoices-expired'
    );
    Route::get('/invoices/payments', $controller_path . '\InvoicesController@factoringPayments')->name(
      'invoices-payments'
    );
    Route::get('/invoices/payments/data', $controller_path . '\InvoicesController@factoringPaymentsData');
    Route::post(
      '/invoices/{invoice}/request/send',
      $controller_path . '\InvoicesController@sendInvoiceForApproval'
    )->name('invoice-approval-send');
    Route::get('/invoices/sample/download', $controller_path . '\InvoicesController@downloadFactoringSample')->name(
      'invoices.sample.download'
    );
    Route::get('/invoices/{invoice}/download', $controller_path . '\InvoicesController@download');
    Route::get('/invoices/uploaded', $controller_path . '\InvoicesController@factoringUploaded')->name(
      'invoices.uploaded'
    );
    Route::get('/invoices/uploaded/data', $controller_path . '\InvoicesController@factoringUploadedInvoices');
    Route::get('/invoices/uploaded/export', $controller_path . '\InvoicesController@factoringExportUploadedInvoices');
    Route::post('/invoices/import', $controller_path . '\InvoicesController@factoringImport')->name('invoices.import');
    Route::get(
      '/invoices/upload/error-report/download',
      $controller_path . '\InvoicesController@factoringDownloadErrorReport'
    );

    Route::get('/invoices/{invoice}/details', $controller_path . '\InvoicesController@show');
    Route::post('/invoices/remittance/amount', $controller_path . '\InvoicesController@remittanceAmountDetails');

    Route::get('/invoices/{invoice}/pdf/download', $controller_path . '\InvoicesController@download')->name(
      'invoice.download'
    );
    Route::get(
      '/invoices/payment-instruction/{invoice}/pdf/download',
      $controller_path . '\InvoicesController@downloadPaymentInstruction'
    )->name('invoice.payment-instruction.download');

    // Planner
    Route::get('/planner', $controller_path . '\InvoicesController@factoringPlanner')->name('planner-index');
    Route::get('/cash-planner/programs', $controller_path . '\InvoicesController@factoringPrograms');
    Route::get(
      '/cash-planner/invoices/eligible/data',
      $controller_path . '\InvoicesController@factoringEligibleInvoices'
    );
    Route::get(
      '/cash-planner/invoices/non-eligible/data',
      $controller_path . '\InvoicesController@nonEligibleInvoices'
    );
    Route::post('/cash-planner/invoices/request/send', $controller_path . '\InvoicesController@requestFinance');
    Route::post(
      '/cash-planner/invoices/request/multiple/send',
      $controller_path . '\InvoicesController@requestMultipleFinance'
    );
    Route::get(
      '/cash-planner/program/{program}/financing/eligible',
      $controller_path . '\InvoicesController@eligibleForFinancing'
    );
    Route::get(
      '/cash-planner/program/{program}/{payment_date}/financing/calculate',
      $controller_path . '\InvoicesController@eligibleForFinancingCalculate'
    );
    Route::post(
      '/cash-planner/financing/requests/store',
      $controller_path . '\InvoicesController@storeMassFinancingRequest'
    )->name('cash-planner.financing_requests.store');
    Route::post(
      '/cash-planner/financing/requests/export',
      $controller_path . '\InvoicesController@exportInvoices'
    )->name('cash-planner.financing_requests.export');
    Route::get('/cash-planner/program/planner/calculate', $controller_path . '\InvoicesController@plannerCalculate');
    Route::get(
      '/cash-planner/invoices/{invoice}/noa/download',
      $controller_path . '\InvoicesController@downloadNoa'
    )->name('cash-planner.invoices.noa.download');
    Route::get(
      '/cash-planner/invoices/terms/{type}/download',
      $controller_path . '\InvoicesController@downloadTerms'
    )->name('cash-planner.invoices.terms.download');

    // Financing Requests
    Route::get('/financing-requests', $controller_path . '\InvoicesController@factoringRequests')->name('requests');
    Route::get('/financing-requests/data', $controller_path . '\InvoicesController@factoringRequestsData');
    Route::post(
      '/financing-requests/{payment_request}/status/update',
      $controller_path . '\InvoicesController@updateFinanceRequest'
    );

    // Dealers
    Route::get('/invoices/dealers', $controller_path . '\InvoicesController@dealers')->name('invoices.dealers');
    Route::get('/invoices/dealers/data', $controller_path . '\InvoicesController@dealersData');

    // Programs
    Route::get('/programs', $controller_path . '\ProgramsController@programs')->name('planner-programs');
    Route::get('/programs/{program}/{company}/details', $controller_path . '\ProgramsController@program')->name(
      'planner-program'
    );
    // Purchase Orders
    Route::get('/purchase-orders', $controller_path . '\PurchaseOrdersController@index')->name('purchase-orders');

    // Reports
    Route::get('/reports/view', $controller_path . '\ReportsController@factoringIndex')->name('reports');
    Route::get(
      '/reports/all-invoices-report/view',
      $controller_path . '\ReportsController@factoringAllInvoicesReportView'
    )->name('reports.all-invoices');
    Route::get(
      '/reports/programs-report/view',
      $controller_path . '\ReportsController@factoringProgramsReportView'
    )->name('reports.programs');
    Route::get(
      '/reports/dealer-programs-report/view',
      $controller_path . '\ReportsController@factoringDealerProgramsReportView'
    )->name('reports.dealer.programs');
    Route::get(
      '/reports/payments-report/view',
      $controller_path . '\ReportsController@factoringPaymentsReportView'
    )->name('reports.payments');
    Route::get('/reports/reports/data', $controller_path . '\ReportsController@factoringData');
    Route::get('/reports/all-invoices-report', $controller_path . '\ReportsController@factoringAllInvoicesReport');
    Route::get('/reports/programs-report', $controller_path . '\ReportsController@factoringProgramsReport');
    Route::get(
      '/reports/dealer-programs-report',
      $controller_path . '\ReportsController@factoringDealerProgramsReport'
    );
    Route::get('/reports/payments-report', $controller_path . '\ReportsController@factoringPaymentsReport');
    Route::get('/reports/report/{type}/export', $controller_path . '\ReportsController@export');
    Route::get('/report/{type}/pdf/export', $controller_path . '\ReportsController@exportPdf');
    // Configurations
    Route::get('/settings', $controller_path . '\ConfigurationsController@factoring')->name('configurations');
    Route::get('/settings/bank-accounts', $controller_path . '\ConfigurationsController@factoringBankAccounts');
    Route::get('/settings/invoice-settings', $controller_path . '\ConfigurationsController@factoringInvoiceSettings');
    Route::post(
      '/settings/invoice-settings/update',
      $controller_path . '\ConfigurationsController@updateInvoiceSettings'
    );
    Route::get(
      '/settings/invoice-settings/status/{status}/update',
      $controller_path . '\ConfigurationsController@factoringUpdateStatus'
    );
    Route::get('/settings/taxes', $controller_path . '\ConfigurationsController@taxes');
    Route::post('/settings/taxes', $controller_path . '\ConfigurationsController@addTax');
    Route::delete('/settings/taxes/{id}', $controller_path . '\ConfigurationsController@deleteTax');
    Route::get('/settings/slab-settings', $controller_path . '\ConfigurationsController@slabSettings')->name(
      'configurations.slab-settings'
    );
    Route::get('/settings/slab-settings/data', $controller_path . '\ConfigurationsController@discountSlabs');
    Route::post('/settings/slab-settings/store', $controller_path . '\ConfigurationsController@storeSlabSettings');
    Route::post('/settings/slab-settings/update', $controller_path . '\ConfigurationsController@updateSlabSettings');
    Route::get('/settings/anchors', $controller_path . '\ConfigurationsController@anchors');
    Route::post('/settings/anchors/update', $controller_path . '\ConfigurationsController@updateAnchorSettings');

    Route::get('/help/view', $controller_path . '\HelpController@factoringIndex')->name('help-index');

    // Company Profile
    Route::get(
      '/profile/company/{company}',
      $controller_path . '\ConfigurationsController@factoringCompanyProfile'
    )->name('company.profile');
  });

  Route::group(['prefix' => '/dealer', 'as' => 'dealer-'], function () use ($controller_path) {
    // Dashboard
    Route::get('/', $controller_path . '\DashboardController@dealerFinancing')->name('dashboard');

    Route::get('/notifications/view', $controller_path . '\DashboardController@notificationsDealer')->name(
      'notifications'
    );
    Route::get('/notifications/view/data', $controller_path . '\DashboardController@notificationsDataDealer');
    Route::get('/notifications/{notification}/read', $controller_path . '\DashboardController@dealerNotificationRead');
    Route::get('/notifications/all/update/read', $controller_path . '\DashboardController@dealerNotificationReadAll');

    // Purchase Orders
    Route::get('/purchase-order', $controller_path . '\PurchaseOrdersController@dealerIndex')->name(
      'purchase-orders.index'
    );
    Route::get('/purchase-orders/data', $controller_path . '\PurchaseOrdersController@dealerPurchaseOrdersData');
    Route::get(
      '/purchase-orders/pending/data',
      $controller_path . '\PurchaseOrdersController@dealerPendingPurchaseOrdersData'
    );
    Route::get('/purchase-orders/{purchase_order}/show', $controller_path . '\PurchaseOrdersController@show');
    Route::get(
      '/purchase-orders/{purchase_order}/pdf/download',
      $controller_path . '\PurchaseOrdersController@downloadPurchaseOrder'
    )->name('purchase-orders.download');
    Route::post('/purchase-orders/status/update', $controller_path . '\PurchaseOrdersController@dealerUpdateStatus');
    Route::get(
      '/purchase-orders/{purchase_order}/convert',
      $controller_path . '\PurchaseOrdersController@dealerConvertToInvoice'
    );
    Route::get('/purchase-orders/program/{program}', $controller_path . '\PurchaseOrdersController@dealerProgram');

    // Invoices
    Route::get('/invoices', $controller_path . '\DealerInvoicesController@index')->name('invoices-index');
    Route::get('/invoices/data', $controller_path . '\DealerInvoicesController@invoicesData');
    Route::get('/invoices/expired/data', $controller_path . '\DealerInvoicesController@expiredInvoicesData');
    Route::get('/invoices/create/{invoice?}', $controller_path . '\DealerInvoicesController@create')->name(
      'invoices-create'
    );
    Route::get(
      '/invoices/drawdown/create/{invoice?}',
      $controller_path . '\DealerInvoicesController@drawdownCreate'
    )->name('invoices-drawdown-create');
    Route::get('/invoices/{invoice}/edit', $controller_path . '\DealerInvoicesController@edit')->name('invoices-edit');
    Route::get(
      '/invoices/create/invoice-number/{number}/check/{dealer?}',
      $controller_path . '\DealerInvoicesController@checkInvoiceNumber'
    );
    Route::get('/invoices/create/{company}/programs', $controller_path . '\DealerInvoicesController@programs')->name(
      'invoice-programs'
    );
    Route::get(
      '/invoices/drawdown/create/{company}/programs',
      $controller_path . '\DealerInvoicesController@drawdownPrograms'
    )->name('invoice-drawdown-programs');
    Route::post('/invoices/store', $controller_path . '\DealerInvoicesController@store')->name('invoices-store');
    Route::post('/invoices/drawdown/store', $controller_path . '\DealerInvoicesController@drawdownStore')->name(
      'invoices-drawdown-store'
    );
    Route::get('/invoices/expired', $controller_path . '\DealerInvoicesController@expired')->name('invoices-expired');
    Route::get('/invoices/payments', $controller_path . '\DealerInvoicesController@payments')->name(
      'invoices-payments'
    );
    Route::get('/invoices/payments/data', $controller_path . '\DealerInvoicesController@paymentsData');

    Route::get('/invoices/sample/download', $controller_path . '\DealerInvoicesController@downloadSample')->name(
      'invoices.sample.download'
    );
    Route::get('/invoices/{invoice}/download', $controller_path . '\DealerInvoicesController@download');
    Route::get('/invoices/uploaded', $controller_path . '\DealerInvoicesController@uploaded')->name(
      'invoices.uploaded'
    );
    Route::get('/invoices/uploaded/data', $controller_path . '\DealerInvoicesController@uploadedInvoices');
    Route::get('/invoices/uploaded/export', $controller_path . '\DealerInvoicesController@exportUploadedInvoices');
    Route::post('/invoices/import', $controller_path . '\DealerInvoicesController@import')->name('invoices.import');
    Route::get(
      '/invoices/upload/error-report/download',
      $controller_path . '\DealerInvoicesController@downloadErrorReport'
    );

    Route::get('/invoices/{invoice}/edit', $controller_path . '\DealerInvoicesController@edit')->name('invoices.edit');
    Route::post('/invoices/{invoice}/approve', $controller_path . '\DealerInvoicesController@approve')->name(
      'invoices.approve'
    );
    Route::get('/invoices/{invoice}/fees/approve', $controller_path . '\DealerInvoicesController@approveFees')->name(
      'invoices.fees.approve'
    );
    Route::post('/invoices/{invoice}/update', $controller_path . '\DealerInvoicesController@update')->name(
      'invoices.update'
    );
    Route::post('/invoices/bulk/update/approve', $controller_path . '\DealerInvoicesController@bulkApprove');
    Route::post('/invoices/bulk/update/reject', $controller_path . '\DealerInvoicesController@bulkReject');
    Route::post('/invoices/bulk/details', $controller_path . '\DealerInvoicesController@bulkDetails');
    Route::post('/invoices/{invoice}/reject', $controller_path . '\DealerInvoicesController@reject')->name(
      'invoices.reject'
    );

    Route::get('/invoices/{invoice}/details', $controller_path . '\DealerInvoicesController@show');

    Route::get('/invoices/{invoice}/pdf/download', $controller_path . '\DealerInvoicesController@download')->name(
      'invoice.download'
    );
    Route::get(
      '/invoices/payment-instruction/{invoice}/pdf/download',
      $controller_path . '\DealerInvoicesController@downloadPaymentInstruction'
    )->name('invoice.payment-instruction.download');
    Route::get('/invoices/od-accounts', $controller_path . '\DealerInvoicesController@odAccounts')->name(
      'invoices.od-accounts'
    );
    Route::get('/invoices/od-accounts/data', $controller_path . '\DealerInvoicesController@odAccountsData');
    Route::get(
      '/invoices/od-accounts/{program_vendor_configuration}/details',
      $controller_path . '\DealerInvoicesController@odAccountDetails'
    );
    Route::get(
      '/invoices/od-accounts/{program_vendor_configuration}/cbs-transactions',
      $controller_path . '\DealerInvoicesController@odAccountCbsTransactions'
    );
    Route::get(
      '/invoices/dealer/payment-instructions',
      $controller_path . '\DealerInvoicesController@paymentInstructions'
    )->name('invoices.dealer.payment-instructions');
    Route::get(
      '/invoices/dealer/payment-instructions/data',
      $controller_path . '\DealerInvoicesController@paymentinstructionsData'
    );
    Route::get('/invoices/dealer/dpd-invoices', $controller_path . '\DealerInvoicesController@dealerDpdInvoices')->name(
      'invoices.dealer.dpd-invoices'
    );
    Route::get(
      '/invoices/dealer/dpd-invoices/data',
      $controller_path . '\DealerInvoicesController@dealerDpdInvoicesData'
    );
    Route::get(
      '/invoices/dealer/rejected-invoices',
      $controller_path . '\DealerInvoicesController@rejectedInvoices'
    )->name('invoices.dealer.rejected-invoices');
    Route::get(
      '/invoices/dealer/rejected-invoices/data',
      $controller_path . '\DealerInvoicesController@rejectedInvoicesData'
    );

    // Dealers
    Route::get('/invoices/dealers', $controller_path . '\DealerInvoicesController@dealers')->name('invoices.dealers');
    Route::get('/invoices/dealers/data', $controller_path . '\DealerInvoicesController@dealersData');

    // Programs
    Route::get('/programs', $controller_path . '\ProgramsController@programs')->name('planner-programs');
    Route::get('/programs/{program}/{company}/details', $controller_path . '\ProgramsController@program')->name(
      'planner-program'
    );
    // Purchase Orders
    Route::get('/purchase-orders', $controller_path . '\PurchaseOrdersController@index')->name('purchase-orders');

    // Reports
    Route::get('/reports/view', $controller_path . '\DealerReportsController@index')->name('reports');
    Route::get(
      '/reports/all-invoices-report/view',
      $controller_path . '\DealerReportsController@allInvoicesReportView'
    )->name('reports.all-invoices');
    Route::get('/reports/programs-report/view', $controller_path . '\DealerReportsController@programsReportView')->name(
      'reports.programs'
    );
    Route::get(
      '/reports/dealer-programs-report/view',
      $controller_path . '\DealerReportsController@programsReportView'
    )->name('reports.dealer.programs');
    Route::get('/reports/payments-report/view', $controller_path . '\DealerReportsController@paymentsReportView')->name(
      'reports.payments'
    );
    Route::get('/reports/reports/data', $controller_path . '\DealerReportsController@data');
    Route::get('/reports/all-invoices-report', $controller_path . '\DealerReportsController@allInvoicesReport');
    Route::get('/reports/programs-report', $controller_path . '\DealerReportsController@programsReport');
    Route::get('/reports/dealer-programs-report', $controller_path . '\DealerReportsController@programsReport');
    Route::get('/reports/payments-report', $controller_path . '\DealerReportsController@paymentsReport');
    Route::get('/reports/report/{type}/export', $controller_path . '\DealerReportsController@export');
    Route::get('/report/{type}/pdf/export', $controller_path . '\DealerReportsController@exportPdf');

    // Configurations
    Route::get('/configurations/general', $controller_path . '\DealerConfigurationsController@general')->name(
      'configurations-general'
    );
    Route::get('/configurations/bank-accounts', $controller_path . '\DealerConfigurationsController@bankAccounts');
    Route::get(
      '/configurations/purchase-order-settings',
      $controller_path . '\DealerConfigurationsController@purchaseOrderSettings'
    );
    Route::post(
      '/configurations/purchase-order-settings/update',
      $controller_path . '\DealerConfigurationsController@updatePurchaseOrderSettings'
    );
    Route::get(
      '/configurations/purchase-order-settings/status/{status}/update',
      $controller_path . '\DealerConfigurationsController@updateStatus'
    );
    Route::get('/configurations/company', $controller_path . '\DealerConfigurationsController@company')->name(
      'configurations-company'
    );
    Route::get('/configurations/vendor', $controller_path . '\DealerConfigurationsController@vendor')->name(
      'configurations-vendor'
    );
    Route::post(
      '/configurations/vendor-settings/{id}/update',
      $controller_path . '\DealerConfigurationsController@updateVendorSettings'
    );
    Route::get(
      '/configurations/vendor-settings/{program_vendor_configuration}/approve/{status}',
      $controller_path . '\DealerConfigurationsController@approveVendorSettings'
    )->name('program_vendor_configuration.change.approve');

    Route::get(
      '/configurations/invoice-settings',
      $controller_path . '\DealerConfigurationsController@invoiceSettings'
    )->name('settings.invoice-settings');
    Route::post(
      '/configurations/invoice-settings/update',
      $controller_path . '\DealerConfigurationsController@updateAnchorInvoiceSettings'
    )->name('settings.invoice-settings.update');
    Route::get(
      '/configurations/invoice-settings/status/{status}/update',
      $controller_path . '\DealerConfigurationsController@updateAnchorStatus'
    );

    Route::get('/help/view', $controller_path . '\HelpController@dealerIndex')->name('help-index');

    // Company Profile
    Route::get(
      '/profile/company/{company}',
      $controller_path . '\ConfigurationsController@factoringCompanyProfile'
    )->name('company.profile');
  });

  Route::post('/company/switch', $controller_path . '\ConfigurationsController@switchCompany')->name('company.switch');
  Route::post('/factoring/company/switch', $controller_path . '\ConfigurationsController@switchCompany')->name(
    'factoring.company.switch'
  );
  Route::post('/dealer/company/switch', $controller_path . '\ConfigurationsController@switchCompany')->name(
    'dealer.company.switch'
  );

  // Company Profile
  Route::get('/profile/company/{company}', $controller_path . '\ConfigurationsController@companyProfile')->name(
    'company.profile'
  );
});
