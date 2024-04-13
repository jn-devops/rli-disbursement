<?php

namespace Tests\Feature\Actions;


use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Models\{Product, Reference, Transaction, User};
use Database\Seeders\{ProductSeeder, UserSeeder};
use App\Notifications\DisbursementNotification;
use Bavix\Wallet\Exceptions\InsufficientFunds;
use Illuminate\Support\Facades\Notification;
use App\Actions\RequestDisbursementAction;
use App\Events\DisbursementRequested;
use Illuminate\Support\Facades\Event;
use App\Data\GatewayResponseData;
use Illuminate\Support\Arr;
use App\Classes\Gateway;
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
        Event::fake(DisbursementRequested::class);
        $initialAmountFloat = 1000;
        $user = tap(User::factory()->createQuietly(['meta->service->tf' => 0, 'mdr' => 0]), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $credits = Money::of(100, 'PHP');
        $response = app(RequestDisbursementAction::class)->run($user, [
            'reference' => $reference = $this->faker->uuid(),
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

        Event::assertDispatched(DisbursementRequested::class, function ($event) use ($transaction) {
            return $transaction->is($event->transaction);
        });
    }

    /** @test */
    public function request_disbursement_action_persists_reference(): void
    {
        $response = [
            "transaction_id" => "29187603",
            "source_account" => [
                "account_number" => "113-001-00001-9",
                "branch" => "113",
            ],
            "source_offline_user" => [
                "customer_id" => "90627",
            ],
            "destination_account" => [
                "account_number" => "09983940821",
                "bank_code" => "GXCHPHM2XXX",
            ],
            "destination_offline_user" => [
                "name" => "09983940821",
            ],
            "date" => "2024-04-10T11:03:31Z",
            "type" => "Debit",
            "status" => "Rejected",
            "amount" => [
                "cur" => "PHP",
                "num" => "200000",
            ],
            "description" => "EXTERNAL_TRANSFER_OUTGOING",
            "customer_id" => "90627",
            "remarks" => "Transfer",
            "settlement_rail" => "INSTAPAY",
            "reference_id" => "goldpayw4389306",
            "updated" => "2024-04-10T11:03:32Z",
            "operation_id" => "29187603",
            "status_details" => [
                [
                    "status" => "Pending",
                    "updated" => "2024-04-10T11:03:32.087222283Z",
                ],
                [
                    "status" => "Rejected",
                    "message" => "AM14 (Amount exceeds agreed limit) ",
                    "updated" => "2024-04-10T11:06:21.897767468Z",
                ],
            ],
        ];
        $gateway = app(Gateway::class);
        $url = $gateway->getStatusEndPoint($operationId = '29187603');
        $header = $gateway->getHeaders();
        Http::fake([$url => Http::response($response, 200, $header)]);

        $initialAmountFloat = 1000;
        $user = tap(User::factory()->createQuietly(['meta->service->tf' => 0, 'mdr' => 0]), function ($user) use ($initialAmountFloat) {
            $user->depositFloat($initialAmountFloat);
        });
        $this->assertEquals($initialAmountFloat, $user->balanceFloat);
        $credits = Money::of(100, 'PHP');
        $response = app(RequestDisbursementAction::class)->run($user, $inputs = [
            'reference' => $reference = 'goldpayw4389306', //$this->faker->uuid(),
            'bank' => $bank = 'CUOBPHM2XXX',
            'account_number' => $account_number = '039000000052',
            'via' => 'INSTAPAY',
            'amount' => $credits->getAmount()->toInt()
        ]);
        //check reference table
        $refObject = Reference::where('code', $reference)->first();
        $this->assertEquals($reference, $refObject->code);
        $this->assertEquals($response->transaction_id, $refObject->operation_id);
        $this->assertEquals($user->id, $refObject->user_id);
        $this->assertTrue($refObject->user->is($user));
        tap(Transaction::whereJsonContains('meta->operationId', $response->transaction_id)->first(), function ($transaction) use ($refObject) {
            $this->assertTrue($refObject->transaction->is($transaction));
        });
        $this->assertEquals($inputs, $refObject->inputs);
        $this->assertEquals($reference, $refObject->request['reference_id']);//transform this and assert that it is equal to the request
        $this->assertEquals(['transaction_id' => $refObject->operation_id,'status' =>'Pending'], $refObject->response);//transform this and assert that it is equal to the response
        //TODO: test $refObject->status
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
        $user = tap(User::factory()->create(['tf' => 20 * 100, 'mdr' => 0]), function ($user) use ($initialAmountFloat) {
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
        $this->assertEquals('PENDING', $transaction->status);

        $details = Arr::get($transaction->meta, 'request.payload');
        $this->assertEquals($payload['reference'], $details['reference_id']);
        $this->assertEquals($payload['bank'], $details['destination_account']['bank_code']);
        $this->assertEquals($payload['account_number'], $details['destination_account']['account_number']);
        $this->assertEquals($payload['via'], $details['settlement_rail']);
        $this->assertEquals($payload['amount'], Money::ofMinor($details['amount']['num'], $details['amount']['cur'])->getAmount()->toInt());
        $this->assertEquals(2000, $user->tf);
        $this->assertEquals(null, $user->mdr);
        $tf = Product::where('code', 'transaction_fee')->first()->getAmountProduct($user);
        $mdr = Product::where('code', 'merchant_discount_rate')->first()->getAmountProduct($user);
        $this->assertEquals(2000, $tf);
        $this->assertEquals(0, $mdr);

        $expectedServiceFee = Money::ofMinor($tf + $mdr * $credits->getAmount()->toInt(), 'PHP');
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);

        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('confirm-disbursement'), ['operationId' => $operationId]);
        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertTrue($transaction->confirmed);
        $this->assertEquals('SETTLED', $transaction->status);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat() - $credits->getAmount()->toFloat(), $user->fresh()->balanceFloat);
        Notification::assertSentTo($user, DisbursementNotification::class);
    }

    /** @test */
    public function request_disbursement_action_has_end_point_but_rejected(): void
    {
        $initialAmountFloat = 1000;
        $user = tap(User::factory()->create(['tf' => 20 * 100, 'mdr' => 0]), function ($user) use ($initialAmountFloat) {
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
        $tf = Product::where('code', 'transaction_fee')->first()->getAmountProduct($user);
        $mdr = Product::where('code', 'merchant_discount_rate')->first()->getAmountProduct($user);
        $this->assertEquals(2000, $tf);
        $this->assertEquals(0, $mdr);

        $expectedServiceFee = Money::ofMinor($tf + $mdr * $credits->getAmount()->toInt(), 'PHP');
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->balanceFloat);

        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('reject-disbursement'), ['operationId' => $operationId]);
        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertFalse($transaction->confirmed);
        $this->assertEquals('REJECTED', $transaction->status);
        $this->assertEquals($initialAmountFloat - $expectedServiceFee->getAmount()->toFloat(), $user->fresh()->balanceFloat);
    }
}
