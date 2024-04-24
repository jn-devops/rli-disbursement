<?php

namespace App\Providers;

use App\Actions\SendDepositFeedbackAction;
use App\Events\DepositConfirmed;
use App\Events\DisbursementRejected;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Bavix\Wallet\Internal\Events\TransactionCreatedEventInterface;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Observers\{TransactionObserver, UserObserver};
use App\Actions\SendDisbursementFeedbackAction;
use App\Listeners\CreateDisbursementReference;
use App\Listeners\TransactionCreatedListener;
use App\Listeners\UpdateReferenceStatus;
use Illuminate\Auth\Events\Registered;
use App\Events\DisbursementRequested;
use App\Events\DisbursementConfirmed;
use Illuminate\Support\Facades\Event;
use App\Models\{Transaction, User};

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        DisbursementConfirmed::class => [
            SendDisbursementFeedbackAction::class,
            UpdateReferenceStatus::class//TODO: Test this
        ],
        DisbursementRejected::class => [
            UpdateReferenceStatus::class//TODO: Test this
        ],
        TransactionCreatedEventInterface::class => [
            TransactionCreatedListener::class
        ],
        DisbursementRequested::class => [
            CreateDisbursementReference::class
        ],
        DepositConfirmed::class => [
            SendDepositFeedbackAction::class
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Transaction::observe(TransactionObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
