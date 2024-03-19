<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Events\DisbursementConfirmed;
use App\Models\Transaction;
use Illuminate\Support\Arr;

class ConfirmDisbursement
{
    use AsAction;

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'operationId' => ['required', 'string'],
        ];
    }

    /**
     * @param ActionRequest $request
     * @return \Illuminate\Http\Response
     */
    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $validated = $request->validated();
        $operationId = Arr::get($validated, 'operationId');
        logger('$operationId = ' . $operationId);
        $meta = json_encode(compact('operationId'));
        logger('$meta = ' . $meta);
        $transaction = Transaction::whereJsonContains('meta->operationId', $operationId)->firstOrFail();
        logger('uuid = '. $transaction->uuid);
        $user = $transaction->payable;
        $user->confirm($transaction);
        DisbursementConfirmed::dispatch($transaction);

        return response('Lester was here!', 200);
    }
}
