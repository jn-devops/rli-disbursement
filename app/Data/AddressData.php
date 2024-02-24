<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public string $address1,
        public string $city,
        public string $country,
        public string $postal_code,
    ) {}
}
