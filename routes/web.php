<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AbilityController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CurrentCompanyController;
use App\Http\Controllers\LineItemController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SubsidiaryLedgerController;
use App\Http\Controllers\ReportLineItemController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PostingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SalesReceiptController;
use App\Http\Controllers\ReceivedPaymentController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\SupplierCreditController;
use App\Http\Controllers\InventoryQtyAdjController;
use App\Http\Controllers\CashReceiptController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckCurrentCompany;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/widget', function () {
    return view('widget');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'web'])->group(function () {
    Route::get('/suppliers/import', 'SupplierController@import')->name('suppliers.import')->middleware(CheckCurrentCompany::class);
    Route::post('/suppliers/upload', 'SupplierController@upload')->name('suppliers.upload')->middleware(CheckCurrentCompany::class);
    Route::get('/products/import', 'ProductController@import')->name('products.import')->middleware(CheckCurrentCompany::class);
    Route::post('/products/upload', 'ProductController@upload')->name('products.upload')->middleware(CheckCurrentCompany::class);
    Route::post('/received_payments/ajax-request', 'AjaxController@store')->middleware(CheckCurrentCompany::class);
    Route::post('/creditnote/getinvoice', 'AjaxCNController@getInvoice')->middleware(CheckCurrentCompany::class);
    Route::post('/creditnote/getamounts', 'AjaxCNController@getAmounts')->middleware(CheckCurrentCompany::class);
    Route::post('/suppliercredit/getdocument', 'AjaxSCController@getDocument')->middleware(CheckCurrentCompany::class);
    Route::post('/suppliercredit/getamounts', 'AjaxSCController@getAmounts')->middleware(CheckCurrentCompany::class);
    Route::post('/inventory_qty_adjs/getquantities', 'AjaxInvQtyAdjController@getQuantities')->middleware(CheckCurrentCompany::class);
});

Route::middleware(['auth', 'web'])->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::resource('current_company', CurrentCompanyController::class);
    Route::resource('applications', ApplicationController::class);
    Route::resource('company_users', CompanyUserController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('roles', RoleController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('abilities', AbilityController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('line_items', LineItemController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('accounts', AccountController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('documents', DocumentController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('subsidiary_ledgers', SubsidiaryLedgerController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('report_line_items', ReportLineItemController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('journal_entries', JournalEntryController::class);
    Route::resource('postings', PostingController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('suppliers', SupplierController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('products', ProductController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('bills', BillController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('purchases', PurchaseController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('customers', CustomerController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('invoices', InvoiceController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('sales_receipts', SalesReceiptController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('received_payments', ReceivedPaymentController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('creditnote', CreditNoteController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('suppliercredit', SupplierCreditController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('inventory_qty_adjs', InventoryQtyAdjController::class)->middleware(CheckCurrentCompany::class);
    Route::resource('cash_receipts', CashReceiptController::class)->middleware(CheckCurrentCompany::class);
});

Route::middleware(['auth', 'web'])->group(function () {
    Route::resource('queries', 'QueryController')->middleware(CheckCurrentCompany::class);

    Route::post('/queries/{query}/run', 'QueryController@run')->name('queries.run')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/{query}/screen', 'ReportController@screen')->name('reports.screen')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/{query}/pdf', 'ReportController@pdf')->name('reports.pdf')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/{query}/csv', 'ReportController@csv')->name('reports.csv')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/{query}/run', 'ReportController@run')->name('reports.run')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/trial_balance', 'ReportController@trialBalance')->name('reports.trial_balance')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/comprehensive_income', 'ReportController@comprehensiveIncome')->name('reports.comprehensive_income')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/run_comprehensive_income', 'ReportController@runComprehensiveIncome')->name('reports.run_comprehensive_income')->middleware(CheckCurrentCompany::class);
    Route::get('/reports/financial_position', 'ReportController@financialPosition')->name('reports.financial_position')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/run_financial_position', 'ReportController@runFinancialPosition')->name('reports.run_financial_position')->middleware(CheckCurrentCompany::class);
    Route::get('/reports/changes_in_equity', 'ReportController@changesInEquity')->name('reports.changes_in_equity')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/run_changes_in_equity', 'ReportController@runChangesInEquity')->name('reports.run_changes_in_equity')->middleware(CheckCurrentCompany::class);
    Route::get('/reports/cash_flows', 'ReportController@cashFlows')->name('reports.cash_flows')->middleware(CheckCurrentCompany::class);
    Route::post('/reports/run_cash_flows', 'ReportController@runCashFlows')->name('reports.run_cash_flows')->middleware(CheckCurrentCompany::class);
    Route::get('/reports', 'ReportController@index')->name('reports.index')->middleware(CheckCurrentCompany::class);

    Route::get('/search', 'SearchController@index')->name('search')->middleware(CheckCurrentCompany::class);

    Route::get('/notifications', 'NotificationController@index')->name('notifications.index')->middleware(CheckCurrentCompany::class);
    Route::delete('/notifications/{notification}', 'NotificationController@destroy')->name('notifications.destroy')->middleware(CheckCurrentCompany::class);

    Route::group(['prefix' => 'messages'], function () {
        Route::get('/', ['as' => 'messages', 'uses' => 'MessagesController@index']);
        Route::get('create', ['as' => 'messages.create', 'uses' => 'MessagesController@create']);
        Route::post('/', ['as' => 'messages.store', 'uses' => 'MessagesController@store']);
        Route::get('{id}', ['as' => 'messages.show', 'uses' => 'MessagesController@show']);
        Route::put('{id}', ['as' => 'messages.update', 'uses' => 'MessagesController@update']);
        Route::delete('{id}', ['as' => 'messages.destroy', 'uses' => 'MessagesController@destroy']);
    })->middleware(CheckCurrentCompany::class);
});
