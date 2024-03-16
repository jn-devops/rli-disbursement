<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Arr;
use App\Models\User;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile' => ['nullable', 'string', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ]);
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if (User::count() >= config('disbursement.merchant.max_count')) $validator->errors()->add(
                'name', 'max users exceeded.'
            );
        });
        $validated = $validator->validate();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => Arr::get($validated, 'mobile'),
            'password' => Hash::make($validated['password']),
        ]);
    }
}
