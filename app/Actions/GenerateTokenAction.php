<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class GenerateTokenAction
{
    use AsAction;

    /**
     * @param User $user
     * @param string $password
     * @param string $device
     * @return string|null
     */
    public function handle(User $user, string $password, string $device = 'mobile'): ?string
    {
        if (Hash::check($password, $user->getAttribute('password'))) {
           return $user->createToken($device)->plainTextToken;
        }

        return null;
    }
}
