<?php

namespace App\Listeners;

use Bavix\Wallet\Internal\Events\TransactionCreatedEventInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Transaction;
use Spatie\ModelStatus\Exceptions\InvalidStatus;

class TransactionCreatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param TransactionCreatedEventInterface $event
     * @return void
     * @throws InvalidStatus
     */
    public function handle(TransactionCreatedEventInterface $event): void
    {
        tap(Transaction::find($event->getId()), function (Transaction $transaction) {
            $transaction->setStatus(
                name: $transaction->confirmed ? 'CONFIRMED': 'PENDING'
            );
        });
    }
}
