<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['id_number' => 1001],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'password' => Hash::make('chcc@2025'),
            ]
        );
    }
}

