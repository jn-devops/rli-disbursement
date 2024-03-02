<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Actions\TopupWalletAction;
use Bavix\Wallet\Models\Transfer;
use Database\Seeders\UserSeeder;
use App\Models\User;
use Tests\TestCase;

class TopupWalletActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $system;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->system = User::getSystem();
    }

    /** @test */
    public function test_topup_wallet_action_works(): void
    {
        $user = User::factory()->create();
        $amountFloat = 1000;
        $transaction = TopupWalletAction::run($user, $amountFloat);
        $this->assertInstanceOf(Transfer::class, $transaction);
        $this->assertEquals(1000, $user->balanceFloat);
        $this->assertEquals((1000 * 1000 * 1000) - 1000, $this->system->balanceFloat);
    }
}
