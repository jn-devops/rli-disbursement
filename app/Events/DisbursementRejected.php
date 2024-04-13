<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DisbursementRejected extends DisbursementEvent implements ShouldBroadcast
{
    public function broadcastAs(): string
    {
        return 'disbursement.rejected';
    }
}
