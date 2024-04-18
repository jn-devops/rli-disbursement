<?php

namespace App\Providers;

use App\Nova\Reference;
use Laravel\Nova\Menu\{MenuItem, MenuSection};
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Fields\DateTime;
use Illuminate\Support\Carbon;
use App\Classes\NovaWhitelist;
use Laravel\Nova\Nova;
use App\Nova\User;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        DateTime::macro('withFriendlyDate', function () {
            return $this->tap(function ($field) {
                $field->displayUsing(function ($d) use ($field) {
                    if ($field->isValidNullValue($d)) {
                        return null;
                    }

                    return Carbon::parse($d)->diffForHumans();
                });
            });
        });

        Nova::serving(function (ServingNova $event) {
            /** @var \App\Models\User|null $user */
            $user = $event->request->user();

            if (is_null($user)) {
                return;
            }

            Nova::initialPath("/resources/users/{$user->getKey()}");
        });

        Nova::mainMenu(function ($request) {
            return [
                MenuSection::make(config('app.name'), [
                    MenuItem::resource(User::class)->name('Accounts'),
//                    MenuItem::resource(Reference::class)->name('References'),
                    MenuItem::make('Profile')->path('../user/profile'),
                    MenuItem::make('API Tokens')->path('../user/api-tokens'),
                    MenuItem::make('Outgoing')->path('../outgoing')
                ])->icon('user')->collapsable(),
            ];
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            $whitelist = config('disbursement.nova.whitelist');
            $object = new NovaWhitelist($whitelist);

            return $object->allow($user->email);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
