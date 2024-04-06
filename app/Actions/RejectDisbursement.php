<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Events\DisbursementRejected;
use App\Models\Transaction;
use Illuminate\Support\Arr;

class RejectDisbursement
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
        $transaction->setStatus('REJECTED');
//        $transaction->status = 'REJECTED';
        $transaction->save();

        DisbursementRejected::dispatch($transaction);

        return response('Disbursement rejected!', 200);
    }
}
