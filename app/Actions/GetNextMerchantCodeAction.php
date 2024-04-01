<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Models\User;

class GetNextMerchantCodeAction
{
    use AsAction;

    public function handle(array $used_merchant_codes = null): ?int
    {
        $used_merchant_codes = null != $used_merchant_codes ? $used_merchant_codes : $this->getMerchantCodesArray();
        $limit = 9;
        for ($merchant_code = 1; $merchant_code <= $limit; $merchant_code++) {
            if (!in_array($merchant_code, $used_merchant_codes))
                return $merchant_code;
        }

        return null;
    }

    /**
     * @return int[]|string[]
     */
    public function getMerchantCodesArray(): array
    {
        return array_values(User::all()->pluck('merchant_code')->sort()->toArray());
    }
}
