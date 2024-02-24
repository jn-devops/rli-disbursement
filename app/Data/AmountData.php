<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AmountData extends Data
{
    public function __construct(
        public string $cur,
        public string $num
    ) {}
}
