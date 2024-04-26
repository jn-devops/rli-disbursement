<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\WebhookServer\WebhookCall;
use App\Events\DepositConfirmed;
use App\Models\Transaction;
use App\Models\User;

class SendDepositFeedbackAction
{
    use AsAction;

    /**
     * @param Transaction $transaction
     * @return void
     */
    public function handle(Transaction $transaction): void
    {
        logger('SendDepositFeedbackAction@handle');
        $user = $transaction->payable;
        logger('$user = ');
        logger($user->toArray());
        $user_deposit_endpoint = 'https://members-fibi.qrx.com.ph/api/v1/webhook/qrph';
        if ($user instanceof User)
            if (filter_var($user_deposit_endpoint, FILTER_VALIDATE_URL)) {
                logger('filter_var($user_deposit_endpoint, FILTER_VALIDATE_URL)');
                WebhookCall::create()
                    ->url($user_deposit_endpoint)
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
     * @param DepositConfirmed $event
     * @return void
     */
    public function asListener(DepositConfirmed $event): void
    {
        $this->handle($event->transaction);
    }
}
