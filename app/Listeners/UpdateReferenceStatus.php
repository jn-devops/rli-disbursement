<?php

namespace App\Listeners;

use App\Actions\GetDisbursementStatusAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\DisbursementEvent;
use Illuminate\Support\Arr;

class UpdateReferenceStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DisbursementEvent $event): void
    {
        $transaction = $event->transaction;
        $user = $transaction->payable;
        $operationId = Arr::get($transaction->meta, 'operationId');

        GetDisbursementStatusAction::dispatch($user, $operationId);
    }
}
