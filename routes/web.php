<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
Route::controller(\App\Http\Controllers\HomeController::class)->group(function () {
    Route::get('api/transaction/{id}', 'show');
    Route::post('api/transactions-month', 'getTransactionListByMonth');
    Route::post('api/transactions-all', 'getTransactionList');
    Route::post('api/transaction', 'store');
    Route::post('api/transactions', 'storeMulti');
    Route::put('api/transaction-update', 'update');
    Route::post('api/transaction-delete', 'destroy');
    Route::post('api/expense-specific', 'getExpenseSpecific');
    Route::get('api/balance-specific', 'getBalanceSpecific');
    Route::post('api/balance-specific-month', 'getBalanceSpecificByMonth');
    Route::get('api/transactions-schedule', 'getAllOfInstallmentAndComingSoon');
    Route::put('api/transactions-schedule', 'paymentInstallment');
});

// get income,expense,loan,lend

require __DIR__.'/auth.php';
