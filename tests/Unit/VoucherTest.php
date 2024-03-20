<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use FrittenKeeZ\Vouchers\Facades\Vouchers;
use FrittenKeeZ\Vouchers\Models\Voucher;
use App\Models\User;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_voucher(): void
    {
        $user = User::factory()->create();
        $meta = [
            'tf' => 15,
            'mdr' => 1
        ];
        $code = tap(Vouchers::withMetadata($meta)->create(), function ($voucher) {

        })->code;

        if (Vouchers::redeem($code, $user)) {
            $voucher = Voucher::where('code', $code)->first();
            $this->assertTrue($voucher->isRedeemed());
            $this->assertEquals($meta, $voucher->metadata);
        }
    }
}
