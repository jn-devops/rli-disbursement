<?php

namespace Tests\Feature\Actions;

use App\Models\Product;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Database\Seeders\{ProductSeeder, UserSeeder};
use App\Actions\RequestDisbursementAction;
use Bavix\Wallet\Models\Transaction;
use App\Data\GatewayResponseData;
use Illuminate\Support\Arr;
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
        $this->seed(ProductSeeder::class);
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

        //since it is not confirmed, the use balance should still be the same until it is confirmed by the bank (old)
        //since it is not confirmed, the use balance should be deducted of the service fee only
        $this->assertFalse($transaction->confirmed);
        $expectedServiceFee = (new ServiceFee($user))->amount($credits);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);

//        $actualServiceFee = Price::ofMinor($transaction->amount * -1, 'PHP');
//        $expectedServiceFee = (new ServiceFee($user))->compute($credits);
//        $this->assertEquals(0, $expectedServiceFee->compareTo($actualServiceFee));
//        $this->assertEquals($expectedServiceFee->inclusive()->getAmount()->toFloat(), $actualServiceFee->inclusive()->getAmount()->toFloat());
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
        $payload = [
            'reference' => $this->faker->uuid(),
            'bank' => 'CUOBPHM2XXX',
            'account_number' => '039000000052',
            'via' => 'INSTAPAY',
            'amount' => $credits->getAmount()->toInt()
        ];
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('disbursement-payment'), $payload);
        $response->assertStatus(200);
        $response->assertJsonStructure(['uuid', 'transaction_id', 'status']);
        $transaction = with($operationId = $response->json('transaction_id'), function ($operationId) {
            return Transaction::whereJsonContains('meta->operationId', $operationId)->first();
        });
        $this->assertTrue($transaction->payable->is($user));
        $this->assertFalse($transaction->confirmed);

        $details = Arr::get($transaction->meta, 'details');
        $this->assertEquals($payload['reference'], $details['reference_id']);
        $this->assertEquals($payload['bank'], $details['destination_account']['bank_code']);
        $this->assertEquals($payload['account_number'], $details['destination_account']['account_number']);
        $this->assertEquals($payload['via'], $details['settlement_rail']);
        $this->assertEquals($payload['amount'], Money::ofMinor($details['amount']['num'], $details['amount']['cur'])->getAmount()->toInt());

        $expectedServiceFee = (new ServiceFee($user))->amount($credits);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);
        $actualServiceFee = Price::ofMinor($transaction->amount * -1, 'PHP');
//        $expectedServiceFee = (new ServiceFee($user))->compute($credits);
//        $this->assertEquals(0, $expectedServiceFee->compareTo($actualServiceFee));
//        $this->assertEquals($actualServiceFee->getAmount()->toFloat(), $actualServiceFee->inclusive()->getAmount()->toFloat());

        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('confirm-disbursement'), ['operationId' => $operationId]);
        $response->assertStatus(200);
        $this->assertTrue($transaction->fresh()->confirmed);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat() - $credits->getAmount()->toFloat(), $user->fresh()->balanceFloat);
    }
}
