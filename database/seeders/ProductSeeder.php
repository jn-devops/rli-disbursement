<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Brick\Money\Money;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'code' => 'transaction_fee',
                'name' => 'Transaction Fee',
                'price' => Money::of(15, 'PHP')->getMinorAmount()->toInt() // 15 x 100¢ = ₱15
            ],
            [
                'code' => 'merchant_discount_rate',
                'name' => 'Merchant Discount Rate',
                'price' => Money::ofMinor(1, 'PHP')->getMinorAmount()->toInt() // 1¢
            ],
        ]);
    }
}
