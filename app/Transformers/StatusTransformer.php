<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\User;

class StatusTransformer extends TransformerAbstract
{
    protected User $user;

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];

    public function __construct(User $user)
    {
        $this->user = $user;
    }


    public function transform(array $resp): array
    {
        unset($resp['source_account']['account_number']);
        unset($resp['source_account']['branch']);
        unset($resp['source_offline_user']);
        unset($resp['customer_id']);
        $resp['source_account']['user_id'] = $this->user->id;
        $resp['source_account']['merchant_code'] = $this->user->merchant_code;
        $resp['source_account']['merchant_name'] = $this->user->merchant_name;

        return $resp;
    }
}
