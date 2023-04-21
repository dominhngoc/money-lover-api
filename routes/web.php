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
    Route::post('api/transaction', 'store');
    Route::post('api/transactions', 'storeMulti');
    Route::put('api/transaction-update', 'update');
    Route::delete('api/transaction/{id}', 'destroy');
    Route::get('api/balance', 'getBalance');
    Route::get('api/balance-specific', 'getBalanceSpecific');
});

// get income,expense,loan,lend

require __DIR__.'/auth.php';
