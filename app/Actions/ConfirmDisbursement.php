<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Events\DisbursementConfirmed;
use Bavix\Wallet\Models\Transaction;

class ConfirmDisbursement
{
    use AsAction;

    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
        ];
    }

    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $validated = $request->validated();
        $transaction = Transaction::where('uuid', $validated['uuid'])->first();
        $user = $transaction->payable;
        $user->confirm($transaction);
        DisbursementConfirmed::dispatch($transaction);

        return response('Lester was here!', 200);
    }
}
