<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use App\Transformers\StatusTransformer;
use Spatie\Fractalistic\Fractal;
use App\Models\User;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_status_transformation(): void
    {
        $response =  [
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

        $user = User::factory()->create();
        $array = Fractal::create()
            ->item($response)->transformWith(new StatusTransformer($user))
            ->toArray()
        ;

        $this->assertFalse(isset($array['data']['source_account']['account_number']));
        $this->assertFalse(isset($array['data']['source_account']['branch']));
        $this->assertEquals($user->id, isset($array['data']['source_account']['user_id']));
        $this->assertEquals($user->merchant_code, isset($array['data']['source_account']['merchant_code']));
        $this->assertEquals($user->merchant_name, isset($array['data']['source_account']['merchant_name']));
    }
}
