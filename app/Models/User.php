<?php

namespace App\Models;

use Bavix\Wallet\Interfaces\{Confirmable, Customer, WalletFloat};
use Bavix\Wallet\Traits\{CanConfirm, CanPay, HasWallet};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Notifications\Notifiable;
use Bavix\Wallet\Traits\HasWalletFloat;
use Laravel\Jetstream\HasProfilePhoto;
use Bavix\Wallet\Interfaces\Wallet;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasMeta;

/**
 * Class User
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $mobile
 * @property string $webhook
 * @property float  $transaction_fee
 * @property float  $merchant_discount_rate
 * @property int    $tf
 * @property int    $mdr
 *
 * @method   int    getKey()
 */
class User extends Authenticatable implements Wallet, WalletFloat, Confirmable, Customer
{
    use TwoFactorAuthenticatable;
    use HasProfilePhoto;
    use HasWalletFloat;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use CanConfirm;
    use HasWallet;
    use HasMeta;
    use CanPay;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'webhook',
        'password',
        'transaction_fee',
        'merchant_discount_rate',
        'tf',
        'mdr'
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

    public function getTFAttribute(): ?int
    {
        return $this->getAttribute('meta')->get('service.tf');
    }

    public function setTFAttribute(int $value): self
    {

        $this->getAttribute('meta')->set('service.tf', $value);

        return $this;
    }

    public function getMDRAttribute(): ?int
    {
        return $this->getAttribute('meta')->get('service.mdr');
    }

    public function setMDRAttribute(int $value): self
    {

        $this->getAttribute('meta')->set('service.mdr', $value);

        return $this;
    }
}
