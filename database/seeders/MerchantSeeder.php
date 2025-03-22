<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Merchant::create([
            'merchant_id'  => Str::uuid(),
            'merchant_key' => Str::random(32), // Generate random key
            'secret_key'   => Str::random(32), // Generate random secret
        ]);
    }
}
