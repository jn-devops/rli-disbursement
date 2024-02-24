<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ContactData extends Data
{
    public function __construct(
        public DestinationAccountData $destination,
        public RecipientData          $recipient
    ) {}
}
