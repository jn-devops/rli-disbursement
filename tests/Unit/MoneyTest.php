<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_money(): void
    {
        $money = Money::ofMinor(100, 'PHP');
        $this->assertEquals(1, $money->getAmount()->toInt());
        $money = Money::of(1, 'PHP');
        $this->assertEquals(1, $money->getAmount()->toInt());
        $this->assertEquals(100, $money->getMinorAmount()->toInt());
        $this->assertEquals(100, Money::of(1, 'PHP')->getMinorAmount()->toInt());
    }
}
