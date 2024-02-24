<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class GatewayResponseData extends Data
{
    public function __construct(
        public string $transaction_id,
        public string $status,
    ) {}
}
