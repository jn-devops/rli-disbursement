<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Actions\{GenerateTokenAction, TopupWalletAction};
use Database\Seeders\{ProductSeeder, UserSeeder};
use App\Models\User;
use Tests\TestCase;

class RequestGenerateTokenActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $system;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->seed(UserSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->system = User::getSystem();
    }

    /** @test */
    public function generate_token_action_works(): void
    {
        $user = User::factory()->create(['password' => bcrypt($password = $this->faker->word())]);
        TopupWalletAction::run($user, $amount = 1000);
        $this->assertEquals($amount, $user->balanceFloat);
        $token = GenerateTokenAction::run($user, $password, $device = 'tech1');
        $reference = $this->faker->uuid();
        $bank_code = 'CUOBPHM2XXX';
        $bank_account_number = '039000000052';
        $via = 'INSTAPAY';
        $amount = 100;
        $attribs = [
            'reference' => $reference,
            'bank' => $bank_code,
            'account_number' => $bank_account_number,
            'via' => $via,
            'amount' => $amount
        ];
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('disbursement-payment'), $attribs);
        $response->assertStatus(200);
        $response->assertJsonStructure(['uuid', 'transaction_id', 'status']);
    }
}
