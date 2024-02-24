<?php

namespace App\Collections;

use Illuminate\Support\Collection;

class SettlementRailCollection extends Collection
{
    public function __construct(
        $items = [],
        public array $settlement_rails = [],
    )
    {
        parent::__construct($items);
    }
}
