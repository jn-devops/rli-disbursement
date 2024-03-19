<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

/**
 * Class Transaction
 *
 * @property string $via
 *
 */
class Transaction extends \Bavix\Wallet\Models\Transaction
{
    protected function via(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => Arr::get($this->meta, 'details.settlement_rail', '-'),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'channel', '-')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => 'system',
                    self::TYPE_DEPOSIT => '-'
                },
            },
        );
    }
}
