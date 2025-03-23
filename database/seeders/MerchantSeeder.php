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
            'merchant_id'  => '88d2189a-b6c6-4ac0-84a7-f90e94a89952',
            'merchant_key' => 'Hy13R82KwH3u8YLzwTVCdn4kWOOg5WAj', // Generate random key
            'secret_key'   => 'Y5fN3qC4WdEV0TwkR2RDQqIuzUwGjkrB', // Generate random secret
        ]);
    }
}
