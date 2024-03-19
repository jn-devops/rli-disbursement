<?php

namespace App\Actions;

use App\Notifications\DisbursementNotification;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\DisbursementConfirmed;
use App\Models\Transaction;
use App\Models\User;

class SendDisbursementFeedbackAction
{
    use AsAction;

    /**
     * @param Transaction $transaction
     * @return void
     */
    public function handle(Transaction $transaction): void
    {
        $user = $transaction->payable;
        if ($user instanceof User)
            $user->notify(new DisbursementNotification($transaction));
    }

    /**
     * @param DisbursementConfirmed $event
     * @return void
     */
    public function asListener(DisbursementConfirmed $event): void
    {
        $this->handle($event->transaction);
    }
}
