<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Database\Seeders\ProductSeeder;
use App\Models\{Product, User};
use Bavix\Wallet\Objects\Cart;
use Whitecube\Price\Price;
use Brick\Money\Money;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
    }

    /** @test */
    public function product_attributes(): void
    {
        $product = Product::factory()->create();
        $this->assertIsString($product->code);
        $this->assertIsString($product->name);
        $this->assertInstanceOf(Price::class, $product->price);
    }

    /** @test */
    public function product_seeds(): void
    {
        $codes = ['transaction_fee', 'merchant_discount_rate'];
        foreach ($codes as $code) {
            $product = Product::where('code', $code)->first();
            match ($product->code) {
                'transaction_fee' => $this->assertEquals(15 * 100, $product->price->base()->getMinorAmount()->toFloat()),
                'merchant_discount_rate' => $this->assertEquals(1, $product->price->base()->getMinorAmount()->toFloat()),
            };
        }
        $user = User::factory()->create();
        foreach ($codes as $code) {
            $product = Product::where('code', $code)->firstOrFail();
            match ($product->code) {
                'transaction_fee' => $this->assertEquals(15 * 100, $product->getAmountProduct($user)),
                'merchant_discount_rate' => $this->assertEquals(1, $product->getAmountProduct($user)),
            };
        }
    }

    /** @test */
    public function product_price_can_be_overridden_by_user(): void
    {
        $user = User::factory()->create([
            'tf' => 20 * 100,
            'mdr' => 11
        ]);
        $this->assertEquals(20 * 100, $user->tf);
        $this->assertEquals(11, $user->mdr);

        $codes = ['transaction_fee', 'merchant_discount_rate'];
        foreach ($codes as $code) {
            $product = Product::where('code', $code)->first();
            match ($product->code) {
                'transaction_fee' => $this->assertEquals(20 * 100, $product->getAmountProduct($user)),
                'merchant_discount_rate' => $this->assertEquals(11, $product->getAmountProduct($user)),
            };
        }
    }

    /** @test */
    public function product_can_be_bought(): void
    {
        $user = User::factory()->create();
        $user->deposit($initial_deposit = 1000 * 100);
        $this->assertEquals(1000, $user->balanceFloat);
        $code = 'transaction_fee';
        $product = Product::where('code', $code)->first();
        $this->assertEquals(15 * 100, $product->getAmountProduct($user));
        $this->assertEquals(0, $product->wallet->balance);
        $transaction = $user->pay($product);
        $this->assertEquals(0, $transaction->fee);
        $this->assertEquals($initial_deposit - $product->getAmountProduct($user), $user->balance);
        $this->assertEquals($product->getAmountProduct($user), $product->wallet->balance);
    }

    /** @test */
    public function products_can_be_bought_in_a_cart(): void
    {
        $deposit = Money::of(1000, 'PHP'); // ₱1,000 for initial deposit
        $credits = Money::of(100, 'PHP'); //   ₱  100 for disbursement

        $user = tap(User::factory()->create(), function ($user) use ($deposit) {
            $user->deposit($deposit->getMinorAmount()->toInt());
            $this->assertEquals($deposit->getAmount()->toFloat(), $user->balanceFloat);
        });

        /*************************** disburse ₱100 ****************************/
        $user->withdraw($credits->getMinorAmount()->toInt());

        $product_qty_list = [
            'transaction_fee' => 1, //qty per transaction
            'merchant_discount_rate' => $credits->getAmount()->toInt(), //qty per peso
        ];

        $service_fees = [];
        $sf = Money::of(0, 'PHP');
        $cart = with(app(Cart::class), function ($cart) use ($user, $product_qty_list, &$service_fees, &$sf) {
            $collection = tap(Product::query()->whereIn('code', array_keys($product_qty_list))->get(), function ($products) use (&$cart, $user, $product_qty_list, &$service_fees, &$sf) {
                foreach ($products as $product) {
                    $this->assertEquals($product->price->base()->getMinorAmount()->toFloat(), $product->getAmountProduct($user));
                    $this->assertEquals(0, $product->wallet->balance);
                    $qty = $product_qty_list[$product->code];
                    $cart = $cart->withItem($product, quantity: $qty);
                    $sf = $product->price->base()->multipliedBy($qty)->plus($sf);
                    $service_fees[$product->code] = $product->price->base()->multipliedBy($qty);
                }
            });

            return $cart;
        });

        /******************************** pay service fees ********************************/
        /****************************** ₱15 transaction fee *******************************/
        /*************************** ₱1 merchant discount rate ****************************/
        $user->payCart($cart);

        tap(Product::where('code', 'transaction_fee')->first(), function ($product) use ($user, $product_qty_list) {
            $qty = $product_qty_list[$product->code];
            $this->assertEquals($product->getAmountProduct($user) * $qty, $product->wallet->balance);
        });
        tap(Product::where('code', 'merchant_discount_rate')->first(), function ($product) use ($user, $product_qty_list) {
            $qty = $product_qty_list[$product->code];
            $this->assertEquals($product->getAmountProduct($user) * $qty, $product->wallet->balance);
        });
        $this->assertEquals($deposit->minus($credits)->minus($sf), Money::ofMinor($user->balance, 'PHP'));
        $this->assertEquals(88400, $user->balance);
    }
}
