<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info(' Starting database seeding...');
        $this->command->newLine();

        $this->call([
            JenisArsipSeeder::class,
            DocumentSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info(' Database seeding completed successfully!');
    }
}
