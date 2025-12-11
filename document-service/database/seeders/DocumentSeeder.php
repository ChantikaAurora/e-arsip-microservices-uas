<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\JenisArsip;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan folder documents ada
        if (!Storage::disk('public')->exists('documents')) {
            Storage::disk('public')->makeDirectory('documents');
        }

        // Pastikan JenisArsip sudah ada
        if (JenisArsip::count() === 0) {
            $this->command->warn('JenisArsip belum ada. Jalankan JenisArsipSeeder terlebih dahulu.');
            return;
        }

        // Create sample documents untuk surat masuk
        Document::factory()->count(10)->masuk()->create();

        // Create sample documents untuk surat keluar
        Document::factory()->count(8)->keluar()->create();

        $this->command->info('Document seeder completed: 18 documents created');
        $this->command->info('10 Surat Masuk');
        $this->command->info('   - 8 Surat Keluar');
    }
}
