<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Bavix\Wallet\Interfaces\ProductInterface;
use Illuminate\Database\Eloquent\Model;
use Bavix\Wallet\Interfaces\Customer;
use Bavix\Wallet\Traits\HasWallet;
use Whitecube\Price\Price;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Class Product
 *
 * @property int    $id
 * @property string $code
 * @property string $name
 * @property Price  $price
 *
 * @method   int    getKey()
 */
class Product extends Model implements ProductInterface
{
    use HasFactory;
    use HasWallet;
    protected $fillable = ['code', 'name', 'price'];

    protected int $qty = 1;

    public function getAmountProduct(Customer $customer): int|string
    {
//        $qty = $this->qty ?: 1;

        return
            $customer instanceof User
                ? match ($this->code) {
                    'transaction_fee' => $customer->tf ?: $this->price->base()->getMinorAmount()->toFloat(),
                    'merchant_discount_rate' => ($customer->mdr ?: $this->price->base()->getMinorAmount()->toFloat()) * $this->qty }
                : $this->price->base()->getMinorAmount()->toFloat()
            ;
    }

    public function getMetaProduct(): ?array
    {
        return [
            'title' => $this->name,
            'description' => 'Purchase of Product #' . $this->id,
        ];
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn(int $value) => new Price(Money::ofMinor($value, 'PHP')),
        );
    }
//    public function getPriceAttribute(): ?Price
//    {
//        dd($this->);
//        $base = Money::ofMinor($this->get('price'), 'PHP');
//dd($base);
//        return new Price($base);
//    }

    public function setMDRAttribute(Price $price): self
    {
        $value = $price->base();
        $this->setAttribute('price', $value->getMinorAmount()->toInt());

        return $this;
    }

    public function setQty(int $value): self
    {
        $this->qty = $value;

        return $this;
    }
}
