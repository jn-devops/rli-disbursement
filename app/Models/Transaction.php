<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

/**
 * Class Transaction
 *
 * @property string $via
 * @property string $institution
 * @property string $account
 *
 */
class Transaction extends \Bavix\Wallet\Models\Transaction
{
    protected function via(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => Arr::get($this->meta, 'details.settlement_rail', Arr::get($this->meta, 'title', 'System')),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'channel', '-')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => '-',
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'title', 'System')
                },
            },
        );
    }

    protected function institution(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => Arr::get($this->meta, 'details.destination_account.bank_code',  $this->payable instanceof User ? $this->payable->merchant_name : 'XXX'),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'sender.name',  $this->payable instanceof User ? $this->payable->merchant_name : 'YYY')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => 'System',
                    self::TYPE_DEPOSIT => $this->payable instanceof User ? $this->payable->merchant_name : 'ZZZ'
                },
            },
        );
    }

    protected function account(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => Arr::get($this->meta, 'details.destination_account.account_number',  $this->payable instanceof User ? $this->payable->merchant_name : 'XXX'),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'sender.name',  $this->payable instanceof User ? $this->payable->merchant_code : 'YYY')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => 'System',
                    self::TYPE_DEPOSIT => $this->payable instanceof User ? $this->payable->merchant_code : 'ZZZ'
                },
            },
        );
    }
}
