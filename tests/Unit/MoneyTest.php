<?php

namespace Tests\Unit;

use Whitecube\Price\Price;
use Brick\Money\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_money(): void
    {
        $money = Money::ofMinor(100, 'PHP');
        $this->assertEquals(1, $money->getAmount()->toInt());
        $money = Money::of(1, 'PHP');
        $this->assertEquals(1, $money->getAmount()->toInt());
        $this->assertEquals(100, $money->getMinorAmount()->toInt());
        $this->assertEquals(100, Money::of(1, 'PHP')->getMinorAmount()->toInt());
    }

    public function test_price(): void
    {
        $price = Price::of(100, 'PHP')->addModifier('merchant_discount_rate', function ($modifier) {
            $modifier->multiply(1 + 1.5/100);
        })->addModifier('transaction_fee', Money::of(15, 'PHP'));
        $this->assertEquals(100.0, $price->base()->getAmount()->toFloat());
        $this->assertEquals(116.5, $price->inclusive()->getAmount()->toFloat());
    }
}
