<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use App\Models\User;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param array<string, mixed> $input
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => ['required', 'string', 'max:255'],
            'webhook' => ['nullable', 'string', 'url:https'],
            'merchant_code' => ['nullable', 'string', 'min:1', 'max:1', Rule::in(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']), Rule::unique('users', 'meta->merchant->code')->ignore($user->id)],
            'merchant_name' => ['nullable', 'string', 'min:2', Rule::unique('users', 'meta->merchant->name')->ignore($user->id)],
            'merchant_city' => ['nullable', 'string', 'min:2'],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'mobile' => $input['mobile'],
                'webhook' => Arr::get($input, 'webhook'),
                'merchant_code' => Arr::get($input, 'merchant_code'),
                'merchant_name' => Arr::get($input, 'merchant_name'),
                'merchant_city' => Arr::get($input, 'merchant_city'),
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
