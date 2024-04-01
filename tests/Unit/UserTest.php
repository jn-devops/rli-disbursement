<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Illuminate\Validation\ValidationException;
use App\Actions\Fortify\CreateNewUser;
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

    public function test_user_attributes(): void
    {
        $user = User::factory()->create();
        $this->assertIsString($user->webhook);
        $this->assertIsString($user->merchant_code);
        $this->assertIsString($user->merchant_name);
        $this->assertIsString($user->merchant_city);
    }

    public function test_user_default_attributes(): void
    {
        $user = User::factory()->create();
        $this->assertIsString((string) $user->id, $user->merchant_code);
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

    public function test_default_transaction_fee(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(15 * 100, config('disbursement.user.tf'));
//        $this->assertEquals(config('disbursement.user.transaction_fee'), $user->tf);
        $this->assertEquals(null, $user->tf);
        $this->assertEquals(1, config('disbursement.user.mdr'));
//        $this->assertEquals(config('disbursement.user.mdr'), $user->mdr);
        $this->assertEquals(null, $user->mdr);
    }

    public function test_user_has_maximum_entries(): void
    {
        $max_users = 9;
        $count = User::all()->count();
        $action = app(CreateNewUser::class);
        for ($i = $count; $i < $max_users; $i++) {
            $user = $action->create([
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'mobile' => $this->faker->phoneNumber(),
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
            $this->assertEquals((string) ($i+1), $user->merchant_code);
        }
        $this->assertEquals($max_users, User::all()->count());
        $this->expectException(ValidationException::class);
        $action->create([
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'mobile' => $this->faker->phoneNumber(),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }
}
