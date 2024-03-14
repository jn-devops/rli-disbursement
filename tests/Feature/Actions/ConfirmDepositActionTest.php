<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Actions\ConfirmDepositAction;
use Illuminate\Support\Facades\Event;
use App\Data\DepositResponseData;
use Database\Seeders\UserSeeder;
use App\Events\DepositConfirmed;
use App\Models\User;
use Tests\TestCase;

class ConfirmDepositActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $system;

    protected string $json_template = <<<JSON
            {"alias":"91500","amount"::amount,"channel":"INSTAPAY","commandId":25462208,"externalTransferStatus":"SETTLED","operationId":23868611,"productBranchCode":"113","recipientAccountNumber":"9150009173011987","recipientAccountNumberBankFormat":"113-001-00001-9","referenceCode":":mobile","referenceNumber":"20240301PAPHPHM1XXXG000000000490635","registrationTime":"2024-03-01T20:50:57.942","remarks":"InstaPay transfer #20240301PAPHPHM1XXXG000000000490635","sender":{"accountNumber":"639173011987","institutionCode":"PAPHPHM1XXX","name":"Lester Hurtado"},"transferType":"P2P","merchant_details": {"merchant_code": "1","merchant_account": "09171234567"}}
JSON;
    public function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->system = User::getSystem();
    }

    public function test_apply_deposit_action_works(): void
    {
        Event::fake();
        $mobile = '09171234567';
        $amount = $this->faker->numberBetween(1,10);
        $user = User::factory()->create(['mobile' => $mobile]);
        $this->assertEquals(0, $user->fresh()->balanceFloat);
        $json_response = trans($this->json_template, ['mobile' => $mobile, 'amount' => $amount]);
        $array = json_decode($json_response, true);

        $response = ConfirmDepositAction::run($array);
        $ar1 = $response->toArray();
        $ar2 = DepositResponseData::from($array)->toArray();
        $this->assertEquals($ar1, $ar2);
        $this->assertEquals($amount, $user->fresh()->balanceFloat);
        Event::assertDispatched(DepositConfirmed::class);
    }

    public function test_apply_deposit_action_has_end_points(): void
    {
        $mobile = '09171234567';
        $amount = $this->faker->numberBetween(1,10);
        $user = User::factory()->create(['mobile' => $mobile]);
        $this->assertEquals(0, $user->fresh()->balanceFloat);
        $json_response = trans($this->json_template, ['mobile' => $mobile, 'amount' => $amount]);
        $array = json_decode($json_response, true);

        $response = $this->postJson(route('confirm-deposit'), $array);
        $response->assertStatus(200);
    }
}
