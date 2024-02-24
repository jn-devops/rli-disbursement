<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class DestinationAccountData extends Data
{
    public function __construct(
        public string $bank_code,
        public string $account_number
    ) {}
}
