<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Reference, Transaction, User};
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReferenceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;
    protected Transaction $transaction;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->deposit(1000, ['details' => 'x'], false);
        $this->transaction = Transaction::first();
        $json = <<<META
{"details": {"amount": {"cur": "PHP", "num": "70000"}, "sender": {"address": {"city": "Makati City", "country": "PH", "address1": "Salcedo Village", "postal_code": "1227"}, "customer_id": "90627"}, "recipient": {"name": "09269431547", "address": {"city": "Nabunturan", "country": "PH", "address1": "Mawab", "postal_code": "8802"}}, "reference_id": "goldpayw4411093", "settlement_rail": "INSTAPAY", "destination_account": {"bank_code": "GXCHPHM2XXX", "account_number": "09269431547"}, "source_account_number": "113-001-00001-9"}, "operationId": "29315337"}
META;
        $this->transaction->meta = json_decode($json, true);
        $this->transaction->save();
    }

    public function test_reference_can_be_persisted(): void
    {
        tap(new Reference(['code' => 'goldpayw4411093', 'operation_id' => '29315337']), function ($reference) {
            $reference->user()->associate($this->user);
            $reference->transaction()->associate($this->transaction);
        })->save();

        $this->assertDatabaseHas('references', [
            'code' => 'goldpayw4411093',
            'operation_id' => '29315337',
            'user_id' => $this->user->id,
            'transaction_id' => $this->transaction->id
        ]);

        $reference = Reference::where('code', 'goldpayw4411093')->first();
        $this->assertEquals('29315337', $reference->operation_id);
    }
}
