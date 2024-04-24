<?php

namespace App\Actions;

use App\Notifications\DisbursementNotification;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\DisbursementConfirmed;
use Spatie\WebhookServer\WebhookCall;
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
        logger('SendDisbursementFeedbackAction@handle');
        $user = $transaction->payable;
        if ($user instanceof User)
            if (filter_var($user->webhook, FILTER_VALIDATE_URL)) {
                logger('filter_var($user->webhook, FILTER_VALIDATE_URL)');
//                $user->notify(new DisbursementNotification($transaction));
                WebhookCall::create()
                    ->url($user->webhook)
                    ->payload([
                        'payload' => [
                            'webhook' => $transaction->meta
                        ]
                    ])
                    ->doNotSign()
                    ->dispatch();
            }
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
