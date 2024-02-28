<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Actions\RequestDepositAction;
use Bavix\Wallet\Models\Transfer;
use Database\Seeders\UserSeeder;
use App\Models\User;
use Tests\TestCase;

class RequestDepositActionTest extends TestCase
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
    public function request_deposit_action_works(): void
    {
        $user = User::factory()->create();
        $amountFloat = 1000;
        $transaction = RequestDepositAction::run($user, $amountFloat);
        $this->assertInstanceOf(Transfer::class, $transaction);
        $this->assertEquals(1000, $user->balanceFloat);
        $this->assertEquals((1000 * 1000 * 1000) - 1000, $this->system->balanceFloat);
    }
}
