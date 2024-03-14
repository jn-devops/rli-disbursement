<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class DepositMerchantDetailsData extends Data
{
    public function __construct(
        public string $merchant_code,
        public string $merchant_account
    ) {}
}
