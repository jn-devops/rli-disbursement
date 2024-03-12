<?php

use Illuminate\Support\Facades\{Response, Route, Storage};
use Illuminate\Foundation\Application;
use Inertia\Inertia;

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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/outgoing', [\App\Http\Controllers\SendController::class, 'outgoing'])
        ->name('outgoing');

    Route::post('/disburse',  [\App\Http\Controllers\SendController::class, 'disburse'])
        ->name('disburse');

    Route::post('/transfer',  [\App\Http\Controllers\SendController::class, 'transfer'])
        ->name('transfer');

    Route::post('/update-fees',  [\App\Http\Controllers\SendController::class, 'updateFees'])
        ->name('update-fees');
});

Route::post('/topup-wallet', \App\Actions\TopupWalletAction::class)
    ->name('topup-wallet');

Route::get('/guide', function () {
    $filename = 'guide.pdf';
    $path = documents_path($filename);

    return Response::make(file_get_contents($path), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$filename.'"'
    ]);
})->name('guide');
