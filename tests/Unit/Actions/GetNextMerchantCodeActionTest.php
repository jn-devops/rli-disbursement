<?php

namespace Tests\Unit\Actions;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Actions\GetNextMerchantCodeAction;
use Database\Seeders\UserSeeder;
use App\Models\User;
use Tests\TestCase;

class GetNextMerchantCodeActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_get_next_merchant_code_action_works_on_first_record_after_seeding(): void
    {
        $count = User::all()->count();
        $action = app(GetNextMerchantCodeAction::class);
        $this->assertEquals($count+1, $action->run());
    }

    public function test_get_next_merchant_code_action_works_on_succeeding_records(): void
    {
        User::factory($this->faker->numberBetween(1,5))->create(['merchant_code' => null]);
        $count = User::all()->count();
        $action = app(GetNextMerchantCodeAction::class);
        $this->assertEquals($count+1, $action->run());
    }
}
