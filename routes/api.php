<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/disburse', \App\Actions\RequestDisbursementAction::class)
//    ->middleware(['auth:sanctum', 'ability:disburse:account'])
    ->middleware(['auth:sanctum'])
    ->name('disbursement-payment');

Route::post('/confirm-disbursement', \App\Actions\ConfirmDisbursement::class)
//    ->middleware(['auth:sanctum', 'ability:disburse:account'])
    ->middleware(['auth:sanctum'])
    ->name('confirm-disbursement');

Route::post('/confirm-deposit', \App\Actions\ConfirmDepositAction::class)
//    ->middleware(['auth:sanctum', 'ability:disburse:account'])
    ->name('confirm-deposit');

Route::get('/banks', \App\Actions\GetBankData::class)
    ->name('banks');
