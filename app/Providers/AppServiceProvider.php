<?php

namespace App\Providers;

use Lorisleiva\Actions\Facades\Actions;
use Illuminate\Support\ServiceProvider;
use App\Classes\Gateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gateway::$client_id = config('disbursement.client.id');
        Gateway::$client_secret = config('disbursement.client.secret');
        if ($this->app->runningInConsole()) {
            Actions::registerCommands();
        }
    }
}
