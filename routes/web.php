<?php

use Illuminate\Support\Facades\Route;

$controller_path = 'App\Http\Controllers';

// locale
Route::get('lang/{locale}', $controller_path . '\language\LanguageController@swap');

Route::get(
  '/{bank:url}/company/{company_id}/documents/upload',
  $controller_path . '\RequestedDocumentsController@upload'
)->name('company-documents-upload');

Route::post(
  '/{bank:url}/company/{company}/documents/store',
  $controller_path . '\RequestedDocumentsController@store'
)->name('company-documents-store');

Route::get('/invoices/{invoice}/pdf/download', $controller_path . '\RequestsController@download');
Route::get(
  '/invoices/payment-instruction/{invoice}/pdf/download',
  $controller_path . '\RequestsController@downloadPaymentInstruction'
);
Route::post('invoice/attachment/upload', $controller_path . '\RequestsController@uploadInvoiceAttachment')->middleware(
  'auth'
);

Route::group(['prefix' => '{bank:url}', 'middleware' => ['auth', 'bank_user']], function () use ($controller_path) {
  Route::get('/', $controller_path . '\DashboardController@index')->name('bank.dashboard');
  Route::get('/graph-data', $controller_path . '\DashboardController@graphData');
  Route::get('/dashboard/data', $controller_path . '\DashboardController@dashboardData');

  Route::get('/notifications', $controller_path . '\DashboardController@notifications')->name('notifications');
  Route::get('/notifications/data', $controller_path . '\DashboardController@notificationsData');
  Route::get('/notifications/{notification}/read', $controller_path . '\DashboardController@notificationRead');
  Route::get('/notifications/read/all', $controller_path . '\DashboardController@notificationReadAll');

  // Requests
  Route::get('/requests/reverse-factoring', $controller_path . '\RequestsController@reverseFactoring')->name(
    'requests-reverse-factoring'
  );
  Route::get('/requests/factoring', $controller_path . '\RequestsController@factoring')->name('requests-factoring');
  Route::get('/requests/dealer-financing', $controller_path . '\RequestsController@dealerFinancing')->name(
    'requests-dealer-financing'
  );
  Route::get('/requests/payment-requests', $controller_path . '\RequestsController@requests')->name('payment-requests');
  Route::get('/requests/payment-requests/data', $controller_path . '\RequestsController@paymentRequestsData');
  Route::get(
    '/requests/credit-account-requests/data',
    $controller_path . '\RequestsController@creditAccountRequestsData'
  );
  Route::get('/requests/payment-requests/data/export', $controller_path . '\RequestsController@exportPaymentRequests');
  Route::post(
    '/requests/payment-requests/data/import',
    $controller_path . '\RequestsController@importPaymentRequests'
  )->name('payment-requests.import');
  Route::get(
    '/requests/payment-requests/sample/download',
    $controller_path . '\RequestsController@downloadSample'
  )->name('payment-requests.sample.download');
  // Route::get('/requests/financing-requests/data', $controller_path . '\RequestsController@financingRequestsData');
  Route::get(
    '/requests/vendor-financing-requests/data',
    $controller_path . '\RequestsController@vendorFinancingRequestsData'
  );
  Route::get('/requests/factoring-requests/data', $controller_path . '\RequestsController@factoringRequestsData');
  Route::get(
    '/requests/dealer-financing-requests/data',
    $controller_path . '\RequestsController@dealerFinancingRequestsData'
  );
  Route::post('/requests/financing-requests/update', $controller_path . '\RequestsController@updateRequests');
  Route::post('/requests/payment-requests/update', $controller_path . '\RequestsController@updateRequest')->name(
    'request.update'
  );
  Route::get('/requests/cbs-transactions/data', $controller_path . '\RequestsController@cbsTransactionsData');
  Route::post(
    '/requests/payment-requests/transactions/import',
    $controller_path . '\RequestsController@uploadCbsTransaction'
  );
  Route::get('/requests/uploaded/status', $controller_path . '\RequestsController@uploadedPaymentRequests')->name(
    'requests.uploaded.status'
  );
  Route::get('/requests/uploaded/data', $controller_path . '\RequestsController@uploadedPaymentRequestsData');
  Route::get(
    '/requests/uploaded/data/export',
    $controller_path . '\RequestsController@exportUploadedPaymentRequestsData'
  );
  Route::post('/requests/cbs-transactions/create', $controller_path . '\RequestsController@createCbsTransaction');
  Route::post(
    '/requests/cbs-transactions/{transaction_id}/data/update',
    $controller_path . '\RequestsController@updateCbsTransaction'
  );
  Route::get('/requests/cbs-transactions/export', $controller_path . '\RequestsController@downloadCbsTransaction');
  Route::get(
    '/requests/cbs-transactions/created/export',
    $controller_path . '\RequestsController@downloadCreatedCbsTransaction'
  );
  Route::get(
    '/requests/cbs-transactions/error-report/export',
    $controller_path . '\RequestsController@downloadCbsErrorReport'
  );

  Route::get('/requests/invoices/{invoice}/details', $controller_path . '\RequestsController@invoiceDetails');
  Route::get(
    '/requests/invoices/purchase-order/{purchase_order}/details',
    $controller_path . '\RequestsController@purchaseOrderDetails'
  );
  Route::get(
    '/requests/purchase-orders/{purchase_order}/pdf/download',
    $controller_path . '\RequestsController@downloadPurchaseOrder'
  );
  Route::get('/requests/invoices/{invoice}/pdf/download', $controller_path . '\RequestsController@download');
  Route::get(
    '/requests/invoices/payment-instruction/{invoice}/pdf/download',
    $controller_path . '\RequestsController@downloadPaymentInstruction'
  );
  Route::get(
    '/payment-request/{paymentRequest}/download',
    $controller_path . '\RequestsController@downloadPaymentRequest'
  );

  Route::get('/requests/portfolio/export', $controller_path . '\RequestsController@exportPortfolio');

  // Companies
  Route::get('/companies', $controller_path . '\CompaniesController@index')->name('companies.index');
  Route::get('/opportunities', $controller_path . '\CompaniesController@opportunitiesView')->name(
    'companies.opportunities'
  );
  Route::get('/companies/{company}/edit', $controller_path . '\CompaniesController@edit')->name('companies.edit');
  Route::get('/companies/{company}/edit/{name}/check', $controller_path . '\CompaniesController@checkCompanyName');
  Route::post('/companies/{company}/update', $controller_path . '\CompaniesController@update')
    ->name('companies.update')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/companies/{company}/block-status/update', $controller_path . '\CompaniesController@updateBlockStatus');
  Route::get('/companies/data', $controller_path . '\CompaniesController@companies');
  Route::get('/companies/pending/data', $controller_path . '\CompaniesController@pendingCompanies');
  Route::get('/companies/{company}/users/data', $controller_path . '\CompaniesController@companyUsers');
  Route::get('/companies/{company}/users/{user}/edit', $controller_path . '\CompaniesController@editUser');
  Route::post('/companies/{company}/users/{user}/update', $controller_path . '\CompaniesController@updateUser')->name(
    'companies.users.update'
  );
  Route::get(
    '/companies/{company}/users/{user}/status/update/{status}',
    $controller_path . '\CompaniesController@updateUserStatus'
  );
  Route::get('/companies/opportunities/data', $controller_path . '\CompaniesController@opportunities');
  Route::get('/companies/create', $controller_path . '\CompaniesController@create')
    ->name('companies.create')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/companies/create/{name}/check', $controller_path . '\CompaniesController@checkCompanyName');
  Route::get('/companies/drafts', $controller_path . '\CompaniesController@drafts')
    ->name('companies.drafts')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/companies/{company}/users/{mode}/map', $controller_path . '\CompaniesController@addUser')->name(
    'companies.users.map'
  );
  Route::post('/companies/{company}/users/map', $controller_path . '\CompaniesController@storeUser')->name(
    'companies.users.map.store'
  );
  Route::post('/companies/store', $controller_path . '\CompaniesController@store')
    ->name('companies.store')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::post('/companies/draft/store', $controller_path . '\CompaniesController@storeDraft')
    ->name('companies.draft.store')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/companies/draft/{company}/delete', $controller_path . '\CompaniesController@deleteDraft')
    ->name('companies.draft.delete')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get(
    '/companies/{company}/details/{program_vendor_configuration?}',
    $controller_path . '\CompaniesController@show'
  )->name('companies.show');
  Route::get('/companies/pending/{pipeline}/details', $controller_path . '\CompaniesController@showPending')->name(
    'companies.pending.show'
  );
  Route::post('/companies/{company}/status/update', $controller_path . '\CompaniesController@updateStatus')->name(
    'companies.status.update'
  );
  Route::get(
    '/companies/{company}/activity/status/update/{status}',
    $controller_path . '\CompaniesController@updateActiveStatus'
  )->name('companies.active.status.update');
  Route::post(
    '/companies/{company}/document/status/update',
    $controller_path . '\CompaniesController@updateDocumentStatus'
  )->name('companies.documents.status.update');
  Route::post(
    '/pipelines/pending/{pipeline}/status/update',
    $controller_path . '\CompaniesController@updatePipelineCompanyStatus'
  )->name('pipelines.pending.status.update');
  Route::post(
    '/pipelines/pending/{pipeline}/document/status/update',
    $controller_path . '\CompaniesController@updatePendingDocumentStatus'
  )->name('pipelines.pending.documents.status.update');
  Route::post(
    '/companies/{company}/documents/request',
    $controller_path . '\CompaniesController@requestDocuments'
  )->name('companies.documents.request');
  Route::get('/companies/{company}/updates/{status}/approve', $controller_path . '\CompaniesController@approveChanges')
    ->name('companies.updates.approve')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::post(
    '/companies/bulk/approval-status/status/update',
    $controller_path . '\CompaniesController@bulkUpdateStatus'
  );
  Route::get('/companies/template/download', $controller_path . '\CompaniesController@downloadTemplate');
  Route::get('/companies/upload/error-report/download', $controller_path . '\CompaniesController@downloadErrorReport');
  Route::post('/companies/import', $controller_path . '\CompaniesController@import')->name('companies.import');
  Route::get('/companies/uploaded/view', $controller_path . '\CompaniesController@viewUploaded')->name(
    'companies.uploaded'
  );
  Route::get('/companies/uploaded/data', $controller_path . '\CompaniesController@uploadedData');
  Route::get('/companies/uploaded/export', $controller_path . '\CompaniesController@exportUploadedData');

  // Authorization Matrix
  Route::get(
    '/companies/{company}/authorization-groups',
    $controller_path . '\CompaniesController@authorizationGroups'
  )->name('companies.manage-authorization-groups');
  Route::get(
    '/companies/{company}/authorization-groups/data',
    $controller_path . '\CompaniesController@authorizationGroupsData'
  );
  Route::post(
    '/companies/{company}/authorization-groups/store',
    $controller_path . '\CompaniesController@storeAuthorizationGroup'
  );
  Route::post(
    '/companies/{company}/authorization-groups/update',
    $controller_path . '\CompaniesController@updateAuthorizationGroup'
  );
  Route::get(
    '/companies/{company}/authorization-matrices',
    $controller_path . '\CompaniesController@authorizationMatrices'
  )->name('companies.manage-authorization-matrix');
  Route::get(
    '/companies/{company}/authorization-matrices/data',
    $controller_path . '\CompaniesController@authorizationMatricesData'
  );
  Route::post(
    '/companies/{company}/authorization-matrices/store',
    $controller_path . '\CompaniesController@storeAuthorizationMatrix'
  );
  Route::post(
    '/companies/{company}/authorization-matrices/update',
    $controller_path . '\CompaniesController@updateAuthorizationMatrix'
  );
  Route::get(
    '/companies/{company}/authorization-matrices/program-type/{program_type}',
    $controller_path . '\CompaniesController@authorizationMatrixByProgramType'
  );

  // Company Documents
  Route::get('/companies/{company}/upload-documents', $controller_path . '\CompaniesController@documentsUpload')->name(
    'companies.documents-upload'
  );

  Route::post('/companies/{company}/documents/upload', $controller_path . '\CompaniesController@uploadDocuments')->name(
    'companies.documents.upload'
  );

  Route::get(
    '/companies/{company}/document/{company_document}/delete',
    $controller_path . '\CompaniesController@deleteDocument'
  )->name('companies.document.delete');

  // OD Accounts
  Route::get('/companies/od-accounts', $controller_path . '\CompaniesController@odAccounts')->name(
    'companies.od-accounts'
  );
  Route::get('/companies/od-accounts/data', $controller_path . '\CompaniesController@odAccountsData');
  Route::get(
    '/companies/od-accounts/{program_vendor_configuration}/details',
    $controller_path . '\CompaniesController@odAccountDetails'
  )->name('companies.od-accounts.show');
  Route::post('/companies/od-accounts/credit-account', $controller_path . '\CompaniesController@creditAccount');
  Route::post('/companies/od-accounts/debit-account', $controller_path . '\CompaniesController@debitAccount');
  Route::post('/companies/od-accounts/reversal', $controller_path . '\CompaniesController@reversal');
  Route::get(
    '/companies/od-accounts/{program_vendor_configuration}/{type}/accounts',
    $controller_path . '\CompaniesController@getAccounts'
  );
  Route::get(
    '/companies/od-accounts/{program_vendor_configuration}/discount-account-details',
    $controller_path . '\CompaniesController@discountAccountDetails'
  )->name('companies.od-accounts.discount-account-details');
  Route::get(
    '/companies/od-accounts/{program_vendor_configuration}/total-outstanding',
    $controller_path . '\CompaniesController@totalOutstandingPayments'
  )->name('companies.od-accounts.total-outstanding');
  Route::get(
    '/companies/od-accounts/{program_vendor_configuration}/od-daily-interest',
    $controller_path . '\CompaniesController@odDailyInterest'
  )->name('companies.od-accounts.daily-interest');

  // Programs
  Route::get('/programs/create', $controller_path . '\ProgramsController@create')
    ->name('programs.create')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::post('/programs/name/check', $controller_path . '\ProgramsController@checkProgramName');
  Route::post('/programs/store', $controller_path . '\ProgramsController@store')
    ->name('programs.store')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::post('/programs/draft/store', $controller_path . '\ProgramsController@storeDraft')
    ->name('programs.drafts.store')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/programs', $controller_path . '\ProgramsController@index')->name('programs.index');
  Route::get('/programs/data', $controller_path . '\ProgramsController@programs');
  Route::get('/programs/data/export', $controller_path . '\ProgramsController@exportPrograms');
  Route::get('/programs/drafts', $controller_path . '\ProgramsController@drafts')
    ->name('programs.drafts')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/programs/draft/{program}/delete', $controller_path . '\ProgramsController@deleteDraft')
    ->name('programs.draft.delete')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/programs/exhausted/data', $controller_path . '\ProgramsController@exhaustedProgramsData');
  Route::get('/programs/expired/data', $controller_path . '\ProgramsController@expiredProgramsData');
  Route::get('/programs/pending/data', $controller_path . '\ProgramsController@pendingPrograms');
  Route::get('/programs/{program}/edit', $controller_path . '\ProgramsController@edit')->name('programs.edit');
  Route::post('/programs/{program}/update', $controller_path . '\ProgramsController@update')
    ->name('programs.update')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/programs/{program}/details', $controller_path . '\ProgramsController@show')->name('programs.show');
  Route::delete('/programs/{program}/delete', $controller_path . '\ProgramsController@delete')->name('program.delete');
  Route::get(
    '/programs/{program}/fee/{program_fee_id}/delete',
    $controller_path . '\ProgramsController@deleteProgramFee'
  )->name('program.fee.delete');
  Route::get(
    'programs/{program}/bank_details/{program_bank_details_id}/delete',
    $controller_path . '\ProgramsController@deleteProgramBankDetails'
  )->name('program.bank_details.delete');
  Route::get('/programs/{program}/delete/cancel', $controller_path . '\ProgramsController@cancelDeletion')->name(
    'program.delete.cancel'
  );
  Route::get('/programs/{program}/update/status/{status}', $controller_path . '\ProgramsController@updateStatus')->name(
    'programs.status.update'
  );
  Route::get('/programs/{program}/status/{status}/update', $controller_path . '\ProgramsController@approveProgram');
  Route::post('/programs/update/status', $controller_path . '\ProgramsController@bulkUpdateStatus');
  Route::post('/programs/{program}/updates/{status}/approve', $controller_path . '\ProgramsController@approveChanges')
    ->name('programs.updates.approve')
    ->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
  Route::get('/programs/companies/{company}/details', $controller_path . '\CompaniesController@show');
  Route::get('/programs/{program}/vendors-manage', $controller_path . '\ProgramsController@manageVendors')->name(
    'programs.vendors.manage'
  );
  Route::get('/programs/{program}/vendors-manage/data', $controller_path . '\ProgramsController@vendorsData');
  Route::get(
    '/programs/{program}/vendors-manage/data/export',
    $controller_path . '\ProgramsController@vendorsDataExport'
  );

  // Map Vendors
  Route::get('/programs/{program}/vendors/map', $controller_path . '\ProgramsController@showMapVendor')->name(
    'programs.vendors.map'
  );
  Route::get(
    '/programs/{program}/vendors/{company}/map/edit',
    $controller_path . '\ProgramsController@editMapVendor'
  )->name('programs.vendors.map.edit');
  Route::post('/programs/{program}/vendors/map', $controller_path . '\ProgramsController@mapVendor')->name(
    'programs.vendors.map.store'
  );
  Route::post(
    '/programs/{program}/vendors/{company}/map/update',
    $controller_path . '\ProgramsController@updateMapVendor'
  )->name('programs.vendors.map.update');
  // Map Buyers
  Route::get('/programs/{program}/buyers/map', $controller_path . '\ProgramsController@showMapBuyer')->name(
    'programs.buyers.map'
  );
  Route::get(
    '/programs/{program}/buyers/{company}/map/edit',
    $controller_path . '\ProgramsController@editMapBuyer'
  )->name('programs.buyers.map.edit');
  Route::post('/programs/{program}/buyers/map', $controller_path . '\ProgramsController@mapBuyer')->name(
    'programs.buyers.map.store'
  );
  Route::post(
    '/programs/{program}/buyers/{company}/map/update',
    $controller_path . '\ProgramsController@updateMapBuyer'
  )->name('programs.buyers.map.update');
  // Map Dealers
  Route::get('/programs/{program}/dealers/map', $controller_path . '\ProgramsController@showMapDealer')->name(
    'programs.dealers.map'
  );
  Route::get(
    '/programs/{program}/dealers/{company}/map/edit',
    $controller_path . '\ProgramsController@editMapDealer'
  )->name('programs.dealers.map.edit');
  Route::post('/programs/{program}/dealers/map', $controller_path . '\ProgramsController@mapDealer')->name(
    'programs.dealers.map.store'
  );
  Route::post(
    '/programs/{program}/dealers/{company}/map/update',
    $controller_path . '\ProgramsController@updateMapDealer'
  )->name('programs.dealers.map.update');

  Route::get(
    '/programs/{program}/{company}/mapping/block/status/update',
    $controller_path . '\ProgramsController@blockVendorConfiguration'
  );

  Route::delete('/programs/{program}/mapping/{company}/delete', $controller_path . '\ProgramsController@deleteMapping');
  Route::get(
    '/programs/{program}/mapping/{company}/delete/cancel',
    $controller_path . '\ProgramsController@cancelMappingDeletion'
  );

  Route::get(
    '/programs/{program}/{company}/mapping/update',
    $controller_path . '\ProgramsController@updateMappingStatus'
  );
  Route::post('/programs/{program}/mapping/update', $controller_path . '\ProgramsController@bulkUpdateMappingStatus');
  Route::post(
    '/programs/{program}/mapping/approval/update',
    $controller_path . '\ProgramsController@bulkApproveProgramConfiguration'
  );

  Route::post(
    '/programs/{program}/{company}/mapping/approval/update',
    $controller_path . '\ProgramsController@updateApprovalMappingStatus'
  );

  Route::get(
    '/programs/{program}/mapping/company/{company}/changes/approve/{status}',
    $controller_path . '\ProgramsController@approveProgramConfigurationChanges'
  );
  // Accounts Checker
  Route::get(
    '/programs/accounts-checker/{side}/{program_type}/{program_code?}',
    $controller_path . '\ProgramsController@accountsChecker'
  )->name('programs.accounts-checker');

  // Reports
  Route::get('/reports', $controller_path . '\ReportsController@index')->name('reports.index');
  Route::get('/reports/graph/data', $controller_path . '\ReportsController@graphData');
  Route::get('/reports/graph/revenue/pie/data', $controller_path . '\ReportsController@revenuePieGraphData');
  Route::get('/reports/requests/tracker/data', $controller_path . '\ReportsController@requestsTrackerData');
  Route::get('/reports/invoices/data', $controller_path . '\ReportsController@invoiceStatusData');
  Route::get('/reports/data', $controller_path . '\ReportsController@reportData');
  Route::get('/report/{report}', $controller_path . '\ReportsController@report')->name('reports.report');
  Route::get('/reports/ledger', $controller_path . '\ReportsController@ledger')->name('reports.ledger');
  Route::get('/reports/logs', $controller_path . '\ReportsController@logs')->name('reports.logs');
  Route::get('/reports/logs/data', $controller_path . '\ReportsController@logsData');
  Route::get('/reports/data/export', $controller_path . '\ReportsController@exportReports')->name('reports.export');
  Route::get('/reports/pdf/export', $controller_path . '\ReportsController@exportPdfReports')->name(
    'reports.pdf.export'
  );
  Route::get('/reports/{program}/vendors', $controller_path . '\ReportsController@manageVendors');
  Route::get('/reports/{program}/vendors/data', $controller_path . '\ReportsController@vendorsData');
  Route::get('/reports/{invoice}/payment-details', $controller_path . '\ReportsController@paymentDetails');
  Route::get('/reports/{invoice}/daily-discounts', $controller_path . '\ReportsController@dailyDiscount')->name(
    'reports.payment-details.daily-discount'
  );
  Route::get(
    '/reports/limit-utilization/{program}/{company}',
    $controller_path . '\ReportsController@fundingLimitUtilization'
  );

  // Configrations
  Route::get('/configurations', $controller_path . '\ConfigurationsController@index')->name('configurations.index');
  Route::post(
    '/configurations/compliance/document/add',
    $controller_path . '\ConfigurationsController@addComplianceDocument'
  )->name('configurations.compliance.document.add');
  Route::post(
    '/configurations/compliance/documents/update',
    $controller_path . '\ConfigurationsController@updateComplianceDocuments'
  )->name('configurations.compliance.documents.update');
  Route::get(
    '/configurations/compliance/documents/{bank_document}/delete',
    $controller_path . '\ConfigurationsController@deleteComplianceDocument'
  )->name('configurations.compliance.documents.delete');
  // Base Rates
  Route::get('/configurations/base-rates', $controller_path . '\ConfigurationsController@baseRates')->name(
    'configurations.base-rates'
  );
  Route::get(
    '/configurations/base-rates/{base_rate}/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateActiveStatus'
  )->name('configurations.base-rates.status.update');
  Route::post('/configurations/base-rates/store', $controller_path . '\ConfigurationsController@storeBaseRates');
  Route::post('/configurations/base-rates/update', $controller_path . '\ConfigurationsController@updateBaseRate');
  Route::get('/configurations/base-rates/data', $controller_path . '\ConfigurationsController@baseRatesData');
  // Tax Rates
  Route::get('/configurations/tax-rates', $controller_path . '\ConfigurationsController@taxRates')->name(
    'configurations.tax-rates'
  );
  Route::get('/configurations/tax-rates/data', $controller_path . '\ConfigurationsController@taxRatesData');
  Route::get(
    '/configurations/tax-rates/{tax_rate}/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateTaxRateActiveStatus'
  )->name('configurations.tax-rates.status.update');
  Route::post('/configurations/tax-rates/store', $controller_path . '\ConfigurationsController@storeTaxRates');
  Route::post('/configurations/tax-rates/update', $controller_path . '\ConfigurationsController@updateTaxRate');
  // Holidays
  Route::get('/configurations/holidays', $controller_path . '\ConfigurationsController@holidays')->name(
    'configurations.holidays'
  );
  Route::get('/configurations/holidays/data', $controller_path . '\ConfigurationsController@holidaysData');
  Route::post('/configurations/holidays/store', $controller_path . '\ConfigurationsController@storeHoliday');
  Route::post('/configurations/holidays/update', $controller_path . '\ConfigurationsController@updateHoliday');
  Route::get(
    '/configurations/holiday/{bank_holiday}/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateHolidayActiveStatus'
  )->name('configurations.holiday.status.update');
  Route::post(
    '/configurations/holidays/{bank_holiday}/delete',
    $controller_path . '\ConfigurationsController@deleteHoliday'
  );
  Route::get(
    '/configurations/holidays/sample/download',
    $controller_path . '\ConfigurationsController@downloadHolidaysTemplate'
  );
  Route::post('/configurations/holidays/import', $controller_path . '\ConfigurationsController@importHolidays');
  // Branches
  Route::get('/configurations/branches', $controller_path . '\ConfigurationsController@branches')->name(
    'configurations.branches'
  );
  Route::get('/configurations/branches/data', $controller_path . '\ConfigurationsController@branchesData');
  Route::post('/configurations/branches/store', $controller_path . '\ConfigurationsController@storeBranch');
  Route::post('/configurations/branches/update', $controller_path . '\ConfigurationsController@updateBranch');
  // Fees Master
  Route::get('/configurations/fees-master', $controller_path . '\ConfigurationsController@feesMaster')->name(
    'configurations.fees-master'
  );
  Route::get('/configurations/fees-master/data', $controller_path . '\ConfigurationsController@feesMasterData');
  Route::post('/configurations/fees-master/store', $controller_path . '\ConfigurationsController@storeFeesMaster');
  Route::post('/configurations/fees-master/update', $controller_path . '\ConfigurationsController@updateFeesMaster');
  // NOA Templates
  Route::get('/configurations/noa-templates', $controller_path . '\ConfigurationsController@noaTemplates')->name(
    'configurations.noa-templates'
  );
  Route::get('/configurations/noa-templates/data', $controller_path . '\ConfigurationsController@noaTemplatesData');
  Route::get(
    '/configurations/noa-templates/{noa_template}/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateNoaActiveStatus'
  )->name('configurations.noa-templates.status.update');
  Route::post('/configurations/noa-templates/store', $controller_path . '\ConfigurationsController@storeNoaTemplate');
  Route::post('/configurations/noa-templates/update', $controller_path . '\ConfigurationsController@updateNoaTemplate');

  // Terms and Conditions
  Route::get(
    '/configurations/terms-and-conditions',
    $controller_path . '\ConfigurationsController@termsAndConditions'
  )->name('configurations.terms-and-conditions');
  Route::get(
    '/configurations/terms-and-conditions/data',
    $controller_path . '\ConfigurationsController@termsAndConditionsData'
  );
  Route::get(
    '/configurations/terms-and-conditions/{terms_conditions_config}/status/{status}/update',
    $controller_path . '\ConfigurationsController@updateTermsAndConditionsActiveStatus'
  )->name('configurations.terms-and-conditions.status.update');
  Route::post(
    '/configurations/terms-and-conditions/store',
    $controller_path . '\ConfigurationsController@storeTermsAndConditions'
  );
  Route::post(
    '/configurations/terms-and-conditions/update',
    $controller_path . '\ConfigurationsController@updateTermsAndConditions'
  );

  // Convertion Rates
  Route::get('/configurations/conversion-rates', $controller_path . '\ConfigurationsController@convertionRates')->name(
    'configurations.conversion-rates'
  );
  Route::get(
    '/configurations/conversion-rates/data',
    $controller_path . '\ConfigurationsController@convertionRatesData'
  );
  Route::post(
    '/configurations/conversion-rates/store',
    $controller_path . '\ConfigurationsController@storeConvertionRate'
  );
  Route::post(
    '/configurations/conversion-rates/{bank_convertion_rate}/update',
    $controller_path . '\ConfigurationsController@updateConvertionRate'
  );
  // Configurations
  Route::get('/configurations/pending', $controller_path . '\ConfigurationsController@pending')->name(
    'configurations.pending'
  );
  Route::get('/configurations/pending/data', $controller_path . '\ConfigurationsController@pendingData');
  Route::get(
    '/configurations/config/{proposed_configuration_change}/status/update/{status}',
    $controller_path . '\ConfigurationsController@approveConfiguration'
  )->name('configurations.pending.status.update');
  Route::post(
    '/configurations/withholding/update',
    $controller_path . '\ConfigurationsController@updatetWithholdingAndGls'
  )->name('configurations.withholding.update');
  Route::post(
    '/configurations/specific/update',
    $controller_path . '\ConfigurationsController@updateSpecificConfigurations'
  )->name('configurations.specific.update');
  Route::post(
    '/configurations/platform/update',
    $controller_path . '\ConfigurationsController@updatePlatformConfigurations'
  )->name('configurations.platform.update');
  Route::get('/configurations/users', $controller_path . '\ConfigurationsController@userManagement')->name(
    'configurations.users'
  );
  Route::get('/configurations/users/data', $controller_path . '\ConfigurationsController@users');
  Route::get('/configurations/users/add-user', $controller_path . '\ConfigurationsController@addUser')->name(
    'configurations.users.add'
  );
  Route::post('/configurations/users/store', $controller_path . '\ConfigurationsController@storeUser')->name(
    'configurations.users.store'
  );
  Route::get('/configurations/users/{user}/edit', $controller_path . '\ConfigurationsController@editUser')->name(
    'configurations.users.edit'
  );
  Route::post('/configurations/users/{user}/update', $controller_path . '\ConfigurationsController@updateUser')->name(
    'configurations.users.update'
  );
  Route::post(
    '/configurations/users/{user}/changes/approve',
    $controller_path . '\ConfigurationsController@approveChange'
  )->name('configurations.users.update.approve');
  Route::get(
    '/configurations/users/{user}/status/update/{status}',
    $controller_path . '\ConfigurationsController@updateUserStatus'
  );
  Route::post(
    '/configurations/pending/bulk/update',
    $controller_path . '\ConfigurationsController@bulkApproveConfigurations'
  );

  // Bank Rejection reasons
  Route::get('/configuration/rejection-reasons', $controller_path . '\ConfigurationsController@rejectionReasons');
  Route::post(
    '/configurations/rejection-reasons/store',
    $controller_path . '\ConfigurationsController@storeRejectionReason'
  )->name('configurations.rejection-reason.store');
  Route::post(
    '/configurations/rejection-reasons/{bank_rejection_reason}/update',
    $controller_path . '\ConfigurationsController@updateRejectionReason'
  )->name('configurations.rejection-reason.update');
  Route::get(
    '/configurations/rejection-reasons/{bank_rejection_reason}/delete',
    $controller_path . '\ConfigurationsController@deleteRejectionReason'
  )->name('configurations.rejection-reason.delete');

  Route::post(
    '/configurations/change/status/update',
    $controller_path . '\ConfigurationsController@changeStatusUpdate'
  );

  // Roles
  Route::post('/role/create', $controller_path . '\ConfigurationsController@storeRole')->name('role.store');
  Route::post('/role/{role}/update', $controller_path . '\ConfigurationsController@updateRole')->name('role.update');
  Route::post('/role/{role}/status/update', $controller_path . '\ConfigurationsController@updateRoleStatus')->name(
    'role.status.update'
  );
  // Approve Role Changes
  Route::get(
    '/role/{role}/changes/approve/{status}',
    $controller_path . '\ConfigurationsController@approveRoleChanges'
  )->name('role.changes.approve');

  // Help and Manuals
  Route::get('/help', $controller_path . '\HelpController@index')->name('help.index');

  Route::post('/bank/switch', $controller_path . '\ConfigurationsController@switchBank')->name('bank.switch');
});

Route::get('/verify/{user_id}/view', $controller_path . '\AuthController@verify')
  ->name('verify')
  ->middleware('guest');
Route::get('/verify/{user_id}/resend', $controller_path . '\AuthController@verificationResend')
  ->name('verify.resend')
  ->middleware('guest');
Route::post('/verify/confirm', $controller_path . '\AuthController@confirmVerification')
  ->name('verify.confirm')
  ->middleware('guest');

require __DIR__ . '/anchor.php';
require __DIR__ . '/vendor.php';
require __DIR__ . '/buyer.php';
require __DIR__ . '/auth.php';
