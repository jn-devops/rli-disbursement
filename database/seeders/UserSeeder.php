<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        tap(app(CreateNewUser::class)->create(config('disbursement.user.system')), function ($system) {
            $system->depositFloat(config('disbursement.wallet.initial_deposit'));
        });
    }
}
