<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class RecipientData extends Data
{
    public function __construct(
        public string $name,
        public AddressData $address
    ) {}
}
