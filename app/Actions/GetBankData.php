<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Resources\BankDataResource;
use App\Data\BankData;

class GetBankData
{
    use AsAction;

    /**
     * @return array
     */
    public function handle(): array
    {
        $json_file = 'banks_list.json';

        return BankData::collectFromJsonFile($json_file);
    }

    public function asController(): BankDataResource
    {
        return new BankDataResource($this->handle());
    }
}
