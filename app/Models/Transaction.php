<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Support\Arr;
use App\Data\BankData;

/**
 * Class Transaction
 *
 * @property string $via
 * @property string $institution
 * @property string $account
 * @property string $status
 *
 */
class Transaction extends \Bavix\Wallet\Models\Transaction
{
    use HasStatuses;

    /**
     * @return Attribute
     */
    protected function via(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => Arr::get($this->meta, 'request.payload.settlement_rail', Arr::get($this->meta, 'title', 'System')),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'channel', '-')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => '-',
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'title', 'System')
                },
            },
        );
    }

    /**
     * @return Attribute
     */
    protected function institution(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => $this->getBankName(Arr::get($this->meta, 'request.payload.destination_account.bank_code',  $this->payable instanceof User ? $this->payable->merchant_name : 'XXX')),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'sender.name',  $this->payable instanceof User ? $this->payable->merchant_name : 'YYY')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => 'System',
                    self::TYPE_DEPOSIT => $this->payable instanceof User ? $this->payable->merchant_name : 'ZZZ'
                },
            },
        );
    }

    /**
     * @return Attribute
     */
    protected function account(): Attribute
    {
        return Attribute::make(
            get: fn () => match(get_class($this->payable)) {
                User::class => match($this->type) {
                    self::TYPE_WITHDRAW => Arr::get($this->meta, 'request.payload.destination_account.account_number',  $this->payable instanceof User ? $this->payable->merchant_code : 'XXX'),
                    self::TYPE_DEPOSIT => Arr::get($this->meta, 'sender.name',  $this->payable instanceof User ? $this->payable->merchant_code : 'YYY')
                },
                Product::class => match($this->type) {
                    self::TYPE_WITHDRAW => 'System',
                    self::TYPE_DEPOSIT => $this->payable instanceof User ? $this->payable->merchant_code : 'ZZZ'
                },
            },
        );
    }

//    public function getStatusAttribute(): string
//    {
//        return match($this->confirmed){
//            true => Arr::get($this->meta, 'status', 'CONFIRMED') ?: 'CONFIRMED',
//            false => Arr::get($this->meta, 'status', 'PENDING')  ?: 'PENDING'
//        };
//    }
//
//    public function setStatusAttribute(?string $value): self
//    {
//        $this->meta = array_merge($this->meta ?? [], ['status' => $value]);
//
//        return $this;
//    }

    /**
     * @param string $value
     * @return string
     */
    protected function getBankName(string $value): string
    {
        $bank_data = BankData::collectFromJsonFile('banks_list.json');
        if ($bank = Arr::get($bank_data, $value))
            return $bank->name;

        return $value;
    }
}
