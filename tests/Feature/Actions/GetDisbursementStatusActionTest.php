<?php

namespace Tests\Feature\Actions;

use App\Classes\Gateway;
use App\Models\Reference;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Actions\GetDisbursementStatusAction;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Spatie\Fractalistic\Fractal;
use Tests\TestCase;

class GetDisbursementStatusActionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;
    protected GetDisbursementStatusAction $action;
    protected Gateway $gateway;
    protected array $response = [
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

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->deposit(1000, ['details' => 'x'], false);
        $this->transaction = Transaction::first();
        $this->transaction->meta = $this->response;
        $this->transaction->save();
        $this->action = app(GetDisbursementStatusAction::class);
        $this->gateway = app(Gateway::class);
    }

    public function test_action_gets_disbursement_status()
    {
        $operationId = '29187603';
        $url = $this->gateway->getStatusEndPoint($operationId);
        $header = $this->gateway->getHeaders();
        Http::fake([$url => Http::response($this->response, 200, $header)]);
        $fractal = $this->action->run($this->user, $operationId);

        $this->assertInstanceOf(Fractal::class, $fractal);
        $this->assertEquals($operationId, $fractal->toArray()['data']['transaction_id']);
    }

    public function test_action_gets_disbursement_status_from_end_point()
    {
        $operationId = '29187603';
        $url = $this->gateway->getStatusEndPoint($operationId);
        $header = $this->gateway->getHeaders();
        Http::fake([$url => Http::response($this->response, 200, $header)]);
        $reference = tap(new Reference(['code' => 'goldpayw4389306', 'operation_id' => $operationId]), function ($reference) {
            $reference->user()->associate($this->user);
            $reference->transaction()->associate($this->transaction);
            $reference->save();
        });

        $token = $this->user->createToken('test-user')->plainTextToken;
        $response = $this->withHeaders(['Authorization'=>'Bearer '.$token])
            ->getJson(route('disbursement-status', ['code' => $reference->code]));
        $this->assertEquals($operationId, $response->json('data.transaction_id'));
        $this->assertEquals($this->user->id, $response->json('data.source_account.user_id'));
        $this->assertEquals($this->user->merchant_code, $response->json('data.source_account.merchant_code'));
        $this->assertEquals($this->user->merchant_name, $response->json('data.source_account.merchant_name'));
    }
}
