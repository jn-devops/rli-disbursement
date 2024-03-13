<?php

namespace App\Classes;

use App\Data\BankData;
use Illuminate\Support\Facades\Http;

class Gateway
{
    /**
     * @var string
     */
    static public string $client_id;

    /**
     * @var string
     */
    static public string $client_secret;

    /**
     * OAuth2 Basic Authentication
     *
     * @return string
     */
    public function getToken(): string
    {
        $credentials = base64_encode(static::$client_id. ':' . static::$client_secret);
        $gateway_token_endpoint = config('disbursement.server.token-end-point');
        $response = Http::withHeaders(['Authorization' => 'Basic ' . $credentials])
            ->asForm()
            ->post($gateway_token_endpoint, ['grant_type' => 'client_credentials']);

        return $response->json('access_token');
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken()
        ];
    }

    /**
     * @return string
     */
    public function getEndPoint(): string
    {
        return config('disbursement.server.end-point');
    }

    /**
     * @return array
     */
    public function getBanks(): array
    {
        $json_file = 'banks_list.json';
        $json_path = documents_path($json_file);

        return BankData::collectFromJsonFile($json_path);
    }

//    public function getQRHeaders(): array
//    {
//        return [
//            'Content-Type' => 'application/json',
//            'Authorization' => 'Bearer ' . 'EbiHGXVPWLTwzcw0-aEj26TCOJE7syX0xXtRfozfe68.q4yHYcmwUqUWmp4Ovy_G66Mnb4vQHEoJHvxh0iR_v_4'
//        ];
//    }

    public function getQREndPoint(): string
    {
        return config('disbursement.server.qr-end-point');
    }

    public function getDestinationAccount(string $account): string
    {
        return __(':alias:account', [
            'alias' => config('disbursement.client.alias'),
            'account' => $account
        ]);
    }
}
