<?php

namespace App\Classes;

use Whitecube\Price\Price;
use App\Models\Product;
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

    public function amount(Money $credits): Money
    {
        $product_qty_list = ['transaction_fee' => 1, 'merchant_discount_rate' => $credits->getAmount()->toInt()];
        $sf = Money::of(0, 'PHP');
        tap(Product::query()->whereIn('code', array_keys($product_qty_list))->get(), function ($products) use ($product_qty_list, &$sf) {
            foreach ($products as $product) {
                $qty = $product_qty_list[$product->code];
                $sf = Money::ofMinor($product->getAmountProduct($this->user), 'PHP')->multipliedBy($qty)->plus($sf);
            }
        });

        return $sf;
    }
}
