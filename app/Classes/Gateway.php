<?php

namespace App\Classes;

use App\Data\BankData;
use Illuminate\Support\Facades\Http;

class Gateway
{
    static public string $client_id = 'srHqBkNp57nzHyL1vC6N2o6Q';

    static public string $client_secret = 'w7yrXSGdXtp8s6Rb4Iwg75SlA4zrqx2Hmc2SWs1pCk6fp91i';

    public function getToken(): string
    {
        $credentials = base64_encode(static::$client_id. ':' . static::$client_secret);
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->asForm()->post('https://auth.netbank.ph/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        return $response->json('access_token');
    }

    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken()
        ];
    }

    public function getEndPoint(): string
    {
        return 'https://api-uat.netbank.ph/v1/transactions';
    }

    public function getBanks(): array
    {
        $json_file = 'banks_list.json';
        $json_path = documents_path($json_file);

        return BankData::collectFromJsonFile($json_path);
    }
}
