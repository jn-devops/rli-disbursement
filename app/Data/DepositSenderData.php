<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class DepositSenderData extends Data
{
    public function __construct(
        public string $accountNumber,
        public string $institutionCode,
        public string $name,
    ) {}
}
