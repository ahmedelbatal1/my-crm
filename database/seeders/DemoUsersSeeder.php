<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'System Admin', 'email' => 'admin@test.com', 'role' => UserRole::Admin],
            ['name' => 'Mostafa',      'email' => 'rep1@test.com',  'role' => UserRole::SalesRep],
            ['name' => 'Heba',         'email' => 'rep2@test.com',  'role' => UserRole::SalesRep],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'role' => $u['role'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}