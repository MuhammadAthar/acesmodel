<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Use DB::table to bypass Eloquent's hashed cast (prevents double-hashing)
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@acesmodel.com'],
            [
                'name'               => 'Super Admin',
                'password'           => Hash::make('Admin@1234'),
                'role'               => 'superadmin',
                'plan'               => 'enterprise',
                'credits'            => 99999,
                'email_verified_at'  => now(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'test@example.com'],
            [
                'name'               => 'Test User',
                'password'           => Hash::make('password'),
                'role'               => 'user',
                'plan'               => 'free',
                'credits'            => 10,
                'email_verified_at'  => now(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );
    }
}

