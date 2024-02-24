<?php

namespace Tests\Feature\Actions;

use App\Data\GatewayResponseData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Actions\RequestDisbursementAction;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Tests\TestCase;

class RequestDisbursementActionTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function request_disbursement_action_requires_array_returns_boolean(): void
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
        $response = $action->execute($attribs);
        $this->assertInstanceOf(GatewayResponseData::class, $response);
        $this->assertGreaterThan(1000000, $response->transaction_id);
        $this->assertEquals('Pending', $response->status);
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
        $token = $user->createToken('pipe-dream')->plainTextToken;
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])->postJson(route('disbursement-payment'), $attribs);
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'Pending']);
    }
}
