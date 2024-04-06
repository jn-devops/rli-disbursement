<?php

namespace App\Observers;

use Spatie\ModelStatus\Exceptions\InvalidStatus;
use App\Models\Transaction;

class TransactionObserver
{
    /**
     * @param Transaction $transaction
     * @return void
     * @throws InvalidStatus
     */
    public function updated(Transaction $transaction): void
    {
        if (true === $transaction->confirmed) {
            $transaction->setStatus('SETTLED');
        }
    }
}
