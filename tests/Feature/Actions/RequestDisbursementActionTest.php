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
    public function request_disbursement_action_requires_array_returns_transaction(): void
    {
        $user = User::factory()->create();
        $user->depositFloat($initial_amount = 1000);
        $this->assertEquals($initial_amount, $user->balanceFloat);
        $reference = $this->faker->uuid();
        $bank_code = 'CUOBPHM2XXX';
        $bank_account_number = '039000000052';
        $via = 'INSTAPAY';
        $credits = Money::of(100, 'PHP');
        $amountFloat = $credits->getAmount()->toInt();
        $action = app(RequestDisbursementAction::class);
        $attribs = [
            'reference' => $reference,
            'bank' => $bank_code,
            'account_number' => $bank_account_number,
            'via' => $via,
            'amount' => $credits->getAmount()->toInt()
        ];

        $response = $action->run($user, $attribs);
        $this->assertInstanceOf(GatewayResponseData::class, $response);
        $this->assertGreaterThan(1000000, $response->transaction_id);
        $this->assertEquals('Pending', $response->status);

        $transaction = Transaction::whereJsonContains('meta->operationId', $response->transaction_id)->first();
        $this->assertFalse($transaction->confirmed);
        $this->assertTrue($transaction->payable->is($user));

        $serviceFee = Price::ofMinor($transaction->amount * -1,'PHP');
        $service_fee = (new ServiceFee($user))->compute($credits);
        $this->assertEquals(0, $service_fee->compareTo($serviceFee));
        $this->assertEquals($serviceFee->getAmount()->toFloat(), $serviceFee->inclusive()->getAmount()->toFloat());

        $this->assertEquals($initial_amount, $user->balanceFloat);
    }

    /** @test */
    public function request_disbursement_action_has_end_point(): void
    {
        $reference = $this->faker->uuid();
        $bank_code = 'CUOBPHM2XXX';
        $bank_account_number = '039000000052';
        $via = 'INSTAPAY';
        $amount = 1;
        $action = app(RequestDisbursementAction::class);
        $attribs = [
            'reference' => $reference,
            'bank' => $bank_code,
            'account_number' => $bank_account_number,
            'via' => $via,
            'amount' => $amount
        ];
        $user = User::factory()->create();
        $user->depositFloat($initial_amount = 1000);
        $token = $user->createToken('pipe-dream')->plainTextToken;
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('disbursement-payment'), $attribs);
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'Pending']);
        $this->assertEquals($initial_amount, $user->fresh()->balanceFloat);

        $operationId = $response->json('transaction_id');
        $meta = json_encode(compact('operationId'));
        $transaction = Transaction::where('meta', $meta)->first();
        $this->assertFalse($transaction->confirmed);
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('confirm-disbursement'), ['operationId' => $operationId]);
        $response->assertStatus(200);
        $this->assertTrue($transaction->fresh()->confirmed);
        $this->assertEquals($initial_amount - $amount, $user->fresh()->balanceFloat);
    }
}
