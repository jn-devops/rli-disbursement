<?php

namespace Tests\Feature\Actions;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Actions\RequestDisbursementAction;
use Bavix\Wallet\Models\Transaction;
use App\Data\GatewayResponseData;
use Database\Seeders\UserSeeder;
use App\Classes\ServiceFee;
use Whitecube\Price\Price;
use Brick\Money\Money;
use App\Models\User;
use Tests\TestCase;

class RequestDisbursementActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    /** @test */
    public function request_disbursement_action_works_with_service_fee(): void
    {
        $initialAmountFloat = 1000;
        $user = tap(User::factory()->create(), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $credits = Money::of(100, 'PHP');
        $response = app(RequestDisbursementAction::class)->run($user, [
            'reference' => $this->faker->uuid(),
            'bank' => 'CUOBPHM2XXX',
            'account_number' => '039000000052',
            'via' => 'INSTAPAY',
            'amount' => $credits->getAmount()->toInt()
        ]);
        $this->assertInstanceOf(GatewayResponseData::class, $response);
        $this->assertGreaterThan(1000000, $response->transaction_id);
        $this->assertEquals('Pending', $response->status);

        //extract the transaction record from the operationId
        $transaction = Transaction::whereJsonContains('meta->operationId', $response->transaction_id)->first();
        $this->assertTrue($transaction->payable->is($user));

        //since it is not confirmed, the use balance should still be the same until it is confirmed by the bank
        $this->assertFalse($transaction->confirmed);
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);

        $actualServiceFee = Price::ofMinor($transaction->amount * -1, 'PHP');
        $expectedServiceFee = (new ServiceFee($user))->compute($credits);
        $this->assertEquals(0, $expectedServiceFee->compareTo($actualServiceFee));
        $this->assertEquals($expectedServiceFee->inclusive()->getAmount()->toFloat(), $actualServiceFee->inclusive()->getAmount()->toFloat());
    }

    /** @test */
    public function request_disbursement_action_has_end_point_with_service_fee(): void
    {
        $initialAmountFloat = 1000;
        $user = tap(User::factory()->create(), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $credits = Money::of(100, 'PHP');
        $token = $user->createToken('pipe-dream')->plainTextToken;
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('disbursement-payment'), [
            'reference' => $this->faker->uuid(),
            'bank' => 'CUOBPHM2XXX',
            'account_number' => '039000000052',
            'via' => 'INSTAPAY',
            'amount' => $credits->getAmount()->toInt()
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['uuid', 'transaction_id', 'status']);
        $transaction = with($operationId = $response->json('transaction_id'), function ($operationId) {
            return Transaction::where('meta', json_encode(compact('operationId')))->first();
        });
        $this->assertTrue($transaction->payable->is($user));
        $this->assertFalse($transaction->confirmed);
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $actualServiceFee = Price::ofMinor($transaction->amount * -1, 'PHP');
        $expectedServiceFee = (new ServiceFee($user))->compute($credits);
        $this->assertEquals(0, $expectedServiceFee->compareTo($actualServiceFee));
        $this->assertEquals($actualServiceFee->getAmount()->toFloat(), $actualServiceFee->inclusive()->getAmount()->toFloat());

        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('confirm-disbursement'), ['operationId' => $operationId]);
        $response->assertStatus(200);
        $this->assertTrue($transaction->fresh()->confirmed);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->inclusive()->getAmount()->toFloat(), $user->fresh()->balanceFloat);
    }
}
