<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Batuhan Yegin',
                'email' => 'batuhanyegin23@gmail.com',
                'password' => Hash::make(12345678),
                'user_type' => 1,
                'is_active'=>1,
            ],
            [
                'id' => 2,
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make(12345678),
                'user_type' => 0,
                'is_active'=>1,
            ]
        ]);
    }
}
