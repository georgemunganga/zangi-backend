<?php

namespace Database\Seeders;

use App\Models\Seller;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        Seller::firstOrCreate(
            ['phone' => '0971000001'],
            [
                'name' => 'James Banda',
                'code' => 'AGT-001',
                'phone' => '0971000001',
                'pin_hash' => Hash::make('1234'),
                'status' => 'active',
            ]
        );

        Seller::firstOrCreate(
            ['phone' => '0971000002'],
            [
                'name' => 'Mary Phiri',
                'code' => 'AGT-002',
                'phone' => '0971000002',
                'pin_hash' => Hash::make('1234'),
                'status' => 'active',
            ]
        );

        Seller::firstOrCreate(
            ['phone' => '0971000003'],
            [
                'name' => 'Peter Mwamba',
                'code' => 'AGT-003',
                'phone' => '0971000003',
                'pin_hash' => Hash::make('1234'),
                'status' => 'active',
            ]
        );
    }
}
