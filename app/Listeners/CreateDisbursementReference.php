<?php

namespace App\Listeners;

use App\Actions\GetDisbursementStatusAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\DisbursementRequested;
use Illuminate\Support\Arr;
use App\Models\Reference;


class CreateDisbursementReference
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
    public function handle(DisbursementRequested $event): void
    {
        $transaction = $event->transaction;
        $inputs = $event->inputs;
        $request = $event->request;
        $response = $event->response;
        $user = $transaction->payable;
        $operationId = Arr::get($transaction->meta, 'operationId');
        $refCode = Arr::get($transaction->meta, 'request.payload.reference_id');

        tap(new Reference(['code' => $refCode, 'operation_id' => $operationId]), function ($reference) use ($user, $transaction, $inputs, $request, $response) {
            $reference->user()->associate($user);
            $reference->transaction()->associate($transaction);
            $reference->inputs = $inputs;
            $reference->request = $request;
            $reference->response = $response;
        })->save();
        GetDisbursementStatusAction::dispatch($user, $operationId);
    }
}
