<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Owner',
            'email'    => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'is_owner' => true,
        ]);
    }
}
