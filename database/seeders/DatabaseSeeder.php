<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ensure we are not running on a production environment
        if (app()->environment() == 'production') {
            return;
        }

        $this->call([
            UsersSeeder::class,
            ProductWithIngredientsSeeder::class,
        ]);
    }
}
