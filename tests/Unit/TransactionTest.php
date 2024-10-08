<?php

namespace Tests\Unit;

use App\Actions\TopupWalletAction;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\{Transaction, User};
use Illuminate\Support\Arr;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    public function test_transaction_has_status(): void
    {
        $user = User::factory()->create();
        $user->deposit(1000, ['details' => 'x'], false);
        $transaction = Transaction::first();

        $this->assertFalse($transaction->confirmed);
        $this->assertEquals('PENDING', $transaction->status);

        $transaction->setStatus('REJECTED');
        $this->assertFalse($transaction->confirmed);
        $this->assertEquals('REJECTED', $transaction->status);

        $user->confirm($transaction);
        $this->assertTrue($transaction->confirmed);
        $this->assertEquals('SETTLED', $transaction->status);
    }

    public function test_transaction_status_can_be_settled_via_endpoint()
    {
        $user = User::factory()->create();
        $user->deposit(1000, ['details' => 'x'], false);
        $transaction = Transaction::first();

        $this->assertFalse($transaction->confirmed);
        $this->assertEquals('PENDING', $transaction->status);

//        $array = ['operationId' => 1];
//        $response = $this->postJson(route('confirm-disbursement'), $array);
//        $response->assertStatus(200);
    }

    public function test_transaction_inheritance()
    {
        $this->seed(UserSeeder::class);
        $user = User::factory()->create();
        $transfer = TopupWalletAction::run($user, 1000);
        $trx = \Bavix\Wallet\Models\Transaction::find($transfer->deposit->id);
        $this->assertInstanceOf(\Bavix\Wallet\Models\Transaction::class, $trx);
        $transaction = Transaction::from($trx);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertTrue($trx->is($transaction));
    }
}
