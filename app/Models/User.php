<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Bavix\Wallet\Traits\{CanConfirm, HasWallet};
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Notifications\Notifiable;
use Bavix\Wallet\Interfaces\WalletFloat;
use Bavix\Wallet\Interfaces\Confirmable;
use Bavix\Wallet\Traits\HasWalletFloat;
use Laravel\Jetstream\HasProfilePhoto;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Models\Transaction;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $mobile
 * @property float  $transaction_fee
 * @property float  $merchant_discount_rate
 *
 * @method   int    getKey()
 */
class User extends Authenticatable implements Wallet, WalletFloat, Confirmable
{
    use TwoFactorAuthenticatable;
    use HasProfilePhoto;
    use HasWalletFloat;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use CanConfirm;
    use HasWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'transaction_fee',
        'merchant_discount_rate'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    static public function getSystem(): static
    {
        return User::where('email', config('disbursement.user.system.email'))->firstOrFail();
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }
}
