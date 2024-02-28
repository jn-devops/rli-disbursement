<?php

namespace App\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class RequestDepositAction
{
    use AsAction;

    /**
     * @param User $user
     * @param float $amount
     * @return \Bavix\Wallet\Models\Transfer
     * @throws \Bavix\Wallet\Internal\Exceptions\ExceptionInterface
     */
    public function handle(User $user, float $amount): \Bavix\Wallet\Models\Transfer
    {
        $system = User::getSystem();

        return $system->transferFloat($user, $amount);
    }
}
