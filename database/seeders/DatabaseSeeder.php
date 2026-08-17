<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * This app has no Laravel Auth / users table usage — access is entirely
     * through the shared access-token system (see DemoSeeder).
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
