<?php

namespace Tests\Feature\Actions;

use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Database\Seeders\{ProductSeeder, UserSeeder};
use App\Notifications\DisbursementNotification;
use Bavix\Wallet\Exceptions\InsufficientFunds;
use Illuminate\Support\Facades\Notification;
use App\Actions\RequestDisbursementAction;
use Bavix\Wallet\Models\Transaction;
use App\Data\GatewayResponseData;
use App\Models\{Product, User};
use Illuminate\Support\Arr;
use Whitecube\Price\Price;
use Brick\Money\Money;

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
    public function request_disbursement_action_works_with_product_service_fee(): void
    {
        $initialAmountFloat = 1000;
        $user = tap(User::factory()->createQuietly(['meta->service->tf' => 0, 'mdr' => 0]), function ($user) use ($initialAmountFloat) {
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

        $tf_product = Product::where('code', 'transaction_fee')->first();
        $tf = $tf_product->getAmountProduct($user);
        $mdr_product = Product::where('code', 'merchant_discount_rate')->first();
        $mdr = $mdr_product->getAmountProduct($user);

        $expectedServiceFee = Money::ofMinor($tf + $mdr * $credits->getAmount()->toInt(), 'PHP');
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);
    }

    /** @test */
    public function request_disbursement_action_exception_on_wallet_balance(): void
    {
        $initialAmountFloat = 100;
        $creditAmountFloat = $initialAmountFloat + $this->faker->numberBetween(1,100);
        $tf = 0;
        $mdr = 0;
        $user = tap(User::factory()->createQuietly(['meta->service->tf' => $tf, 'mdr' => $mdr]), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $this->assertEquals(0, $user->tf);
        $this->assertEquals(0, $user->mdr);
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $credits = Money::of($creditAmountFloat, 'PHP');
        try {
            app(RequestDisbursementAction::class)->run($user, [
                'reference' => $this->faker->uuid(),
                'bank' => 'CUOBPHM2XXX',
                'account_number' => '039000000052',
                'via' => 'INSTAPAY',
                'amount' => $credits->getAmount()->toInt()
            ]);
        }
        catch (InsufficientFunds $e) {
            $this->assertEquals($initialAmountFloat, $user->fresh()->balanceFloat);
        }
    }

    /** @test */
    public function request_disbursement_action_exception_on_tf(): void
    {
        $initialAmountFloat = 100;
        $creditAmountFloat = $initialAmountFloat;
        $tf = 15;
        $mdr = 0;
        $user = tap(User::factory()->createQuietly(['meta->service->tf' => $tf, 'mdr' => $mdr]), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $this->assertEquals(15, $user->tf);
        $this->assertEquals(0, $user->mdr);
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $credits = Money::of($creditAmountFloat, 'PHP');
        try {
            app(RequestDisbursementAction::class)->run($user, [
                'reference' => $this->faker->uuid(),
                'bank' => 'CUOBPHM2XXX',
                'account_number' => '039000000052',
                'via' => 'INSTAPAY',
                'amount' => $credits->getAmount()->toInt()
            ]);
        }
        catch (InsufficientFunds $e) {
            $this->assertEquals($initialAmountFloat - ($tf/100), $user->fresh()->balanceFloat);
        }
    }

    /** @test */
    public function request_disbursement_action_exception_on_mdr(): void
    {
        $initialAmountFloat = 100;
        $creditAmountFloat = $initialAmountFloat;
        $tf = 0;
        $mdr = 1;
        $user = tap(User::factory()->createQuietly(['meta->service->tf' => $tf, 'mdr' => $mdr]), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $this->assertEquals(0, $user->tf);
        $this->assertEquals(1, $user->mdr);
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $credits = Money::of($creditAmountFloat, 'PHP');
        try {
            app(RequestDisbursementAction::class)->run($user, [
                'reference' => $this->faker->uuid(),
                'bank' => 'CUOBPHM2XXX',
                'account_number' => '039000000052',
                'via' => 'INSTAPAY',
                'amount' => $credits->getAmount()->toInt()
            ]);
        }
        catch (InsufficientFunds $e) {
            $this->assertEquals($initialAmountFloat - (($mdr/100)*$creditAmountFloat), $user->fresh()->balanceFloat);
        }
    }

    /** @test */
    public function request_disbursement_action_works_with_user_service_fee(): void
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

//        $tf = config('disbursement.user.tf');
        $tf = Product::where('code', 'transaction_fee')->first()->getAmountProduct($user);
        $this->assertEquals(null, $user->tf);
//        $mdr = config('disbursement.user.mdr');
        $mdr = Product::where('code', 'merchant_discount_rate')->first()->getAmountProduct($user);
        $this->assertEquals(null, $user->mdr);

        $expectedServiceFee = Money::ofMinor($tf + $mdr * $credits->getAmount()->toInt(), 'PHP');
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);
    }

    /** @test */
    public function request_disbursement_action_has_end_point_with_service_fee(): void
    {
        Notification::fake();
        $initialAmountFloat = 1000;
        $user = tap(User::factory()->create([
            'tf' => 20 * 100,
            'mdr' => 0
        ]), function ($user) use ($initialAmountFloat) {
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

//        $expectedServiceFee = (new ServiceFee($user))->amount($credits);
//        $tf = config('disbursement.user.tf');
        $this->assertEquals(2000, $user->tf);
//        $mdr = config('disbursement.user.mdr');
        $this->assertEquals(null, $user->mdr);
        $tf = Product::where('code', 'transaction_fee')->first()->getAmountProduct($user);
        $mdr = Product::where('code', 'merchant_discount_rate')->first()->getAmountProduct($user);

        $this->assertEquals(2000, $tf);
        $this->assertEquals(0, $mdr);

        $expectedServiceFee = Money::ofMinor($tf + $mdr * $credits->getAmount()->toInt(), 'PHP');

        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);
        $actualServiceFee = Price::ofMinor($transaction->amount * -1, 'PHP');
//        $expectedServiceFee = (new ServiceFee($user))->compute($credits);
//        $this->assertEquals(0, $expectedServiceFee->compareTo($actualServiceFee));
//        $this->assertEquals($actualServiceFee->getAmount()->toFloat(), $actualServiceFee->inclusive()->getAmount()->toFloat());

        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('confirm-disbursement'), ['operationId' => $operationId]);
        $response->assertStatus(200);
        $this->assertTrue($transaction->fresh()->confirmed);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat() - $credits->getAmount()->toFloat(), $user->fresh()->balanceFloat);
        Notification::assertSentTo($user, DisbursementNotification::class);
    }
}
