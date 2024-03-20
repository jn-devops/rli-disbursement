<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\GenerateServiceFeesCodeAction;
use Illuminate\Foundation\Testing\WithFaker;
use FrittenKeeZ\Vouchers\Models\Voucher;
use Brick\Money\Money;
use Tests\TestCase;

class GenerateServiceFeesCodeActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_generate_service_fees_action_works(): void
    {
        $tf = $this->faker->numberBetween(12, 18) * 100;
        $mdr = $this->faker->numberBetween(1, 5);
        $code = GenerateServiceFeesCodeAction::run($tf, $mdr);
        $voucher = Voucher::where('code', $code)->first();
        $this->assertEquals(['tf' => $tf, 'mdr' => $mdr], $voucher->metadata);
    }

    public function test_generate_service_fees_command_works(): void
    {
        $transaction_fee = $this->faker->numberBetween(12, 18);
        $merchant_discount_rate = $this->faker->numberBetween(10, 20)/10;
        $this->artisan('outgoing:service-fees', ['transaction_fee' => $transaction_fee, 'merchant_discount_rate' => $merchant_discount_rate])
            ->assertExitCode(0);
    }
}
