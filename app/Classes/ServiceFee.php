<?php

namespace App\Classes;


use Whitecube\Price\Price;
use Brick\Money\Money;
use App\Models\User;

class ServiceFee
{

    /**
     * @param User $user
     */
    public function __construct(protected User $user){}

    /**
     * @param Money $amount
     * @return Price
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function compute(Money $amount): Price
    {
        return (new Price($amount))
            ->addModifier('merchant_discount_rate', function ($modifier) {
                $modifier->multiply(1 + 1.5/100);
            })
            ->addModifier('transaction_fee', Money::of(15, 'PHP'));
    }
}
