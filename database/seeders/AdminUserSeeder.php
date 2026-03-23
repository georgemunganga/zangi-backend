<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::query()->updateOrCreate(
            ['email' => 'admin@zangisworld.com'],
            [
                'name' => 'Zangi Admin',
                'password' => 'Zangi@12121212',
                'role' => 'admin',
            ],
        );
    }
}
