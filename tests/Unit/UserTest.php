<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Database\Seeders\UserSeeder;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_user_wallet_int(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(0, $user->balance);
        $user->deposit(10);
        $this->assertEquals(10, $user->balance);
        $user->withdraw(1);
        $this->assertEquals(9, $user->balance);
        $user->forceWithdraw(200, ['description' => 'payment of taxes']);
        $this->assertEquals(-191, $user->balance);
    }

    public function test_user_wallet_float(): void
    {
        $user = User::factory()->create();
        $user->deposit(100);
        $this->assertEquals(100, $user->balance);
        $this->assertEquals(1.00, $user->balanceFloat);
        $user->depositFloat(1.37);
        $this->assertEquals(237, $user->balance);
        $this->assertEquals(2.37, $user->balanceFloat);
    }

    public function test_user_wallet_transfer(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->assertNotEquals($user1->getKey(), $user2->getKey());
        $user1->deposit(100);
        $this->assertEquals(0, $user2->balance);
        $user1->transfer($user2, 5);
        $this->assertEquals(95, $user1->balance);
        $this->assertEquals(5, $user2->balance);
        $user1->forceTransfer($user2, 500);
        $this->assertEquals(-405, $user1->balance);
        $this->assertEquals(505, $user2->balance);
    }

    public function test_system_account(): void
    {
        $user = User::where('email', 'devops@joy-nostalg.com')->first();
        $system = User::getSystem();
        $this->assertTrue($system->is($user));
    }

    public function test_system_initial_deposit(): void
    {
        $system = User::getSystem();
        $this->assertEquals(1000 * 1000 * 1000, $system->balanceFloat);
    }
}
