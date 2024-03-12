<?php

namespace App\Actions;

use App\Notifications\DisbursementNotification;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\DisbursementConfirmed;
use Bavix\Wallet\Models\Transaction;
use App\Models\User;

class SendDisbursementFeedbackAction
{
    use AsAction;

    public function handle(Transaction $transaction)
    {
        $user = $transaction->payable;
        if ($user instanceof User)
            $user->notify(new DisbursementNotification($transaction));
    }

    public function asListener(DisbursementConfirmed $event): void
    {
        $this->handle($event->transaction);
    }
}
