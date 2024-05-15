<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'id' => 1,
                'title' => 'Çay',
                'is_active' => 1,
            ],
            [
                'id' => 2,
                'title' => 'Kahve',
                'is_active' => 1,
            ],
        ]);
    }
}
