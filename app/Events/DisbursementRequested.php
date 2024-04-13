<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;
use App\Models\Transaction;
use Brick\Money\Money;

class DisbursementRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Transaction $transaction;
    public array $inputs;
    public array $request;
    public array $response;

    public function __construct(Transaction $transaction, array $inputs, array $request, array $response)
    {
        $this->transaction = $transaction;
        $this->inputs = $inputs;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        logger('DisbursementRequested::broadcastOn');
        logger('$this->transaction->payable->id = ' . $this->transaction->payable->id);
        return [
            new PrivateChannel('App.Models.User.' . $this->transaction->payable->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'disbursement.requested';
    }

    public function broadcastWith(): array
    {
        $uuid = $this->transaction->uuid;
        $amount = Money::ofMinor($this->transaction->amount, 'PHP')->getAmount()->toFloat();

        return [
            'uuid' => $uuid,
            'amount' => $amount,
        ];
    }
}
