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

Route::group(['middleware' => 'firewall.middleware.blacklist'], function ()
{
    Route::get('coming/soon', function()
    {
        return "We are about to launch, please come back in a few days.";
    });

    Route::group(['middleware' => 'firewall.middleware.whitelist'], function ()
    {
        Route::post('/disburse', \App\Actions\RequestDisbursementAction::class)
            ->middleware(['auth:sanctum'])
            ->name('disbursement-payment');
        Route::get('/status/{code}',  \App\Actions\GetDisbursementStatusAction::class)
            ->middleware('auth:sanctum')
            ->name('disbursement-status');
    });
});


Route::post('/confirm-disbursement', \App\Actions\ConfirmDisbursement::class)
    ->middleware(['auth:sanctum'])
    ->name('confirm-disbursement');

Route::post('/reject-disbursement', \App\Actions\RejectDisbursement::class)
    ->middleware(['auth:sanctum'])
    ->name('reject-disbursement');

Route::post('/confirm-deposit', \App\Actions\ConfirmDepositAction::class)
    ->name('confirm-deposit');

Route::get('/banks', \App\Actions\GetBankData::class)
    ->name('api-banks');

Route::middleware('auth:sanctum')->get('/generate-qr',  \App\Actions\GenerateDepositQRCodeAction::class);


