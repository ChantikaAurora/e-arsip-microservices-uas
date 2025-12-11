<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Arsip\JenisArsip;

class JenisArsipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisArsip = [
            [
                'kode' => 'SM',
                'nama' => 'SuratMasuk',
                'deskripsi' => 'Dokumen surat yang diterima oleh institusi',
                'is_active' => true,
            ],
            [
                'kode' => 'SK',
                'nama' => 'SuratKeluar',
                'deskripsi' => 'Dokumen surat yang dikirim oleh institusi',
                'is_active' => true,
            ],
            [
                'kode' => 'AP',
                'nama' => 'AnggaranPenelitian',
                'deskripsi' => 'Dokumen anggaran untuk kegiatan penelitian',
                'is_active' => true,
            ],
            [
                'kode' => 'AG',
                'nama' => 'AnggaranPengabdian',
                'deskripsi' => 'Dokumen anggaran untuk kegiatan pengabdian kepada masyarakat',
                'is_active' => true,
            ],
            [
                'kode' => 'LKP',
                'nama' => 'LaporanKemajuanPenelitian',
                'deskripsi' => 'Dokumen laporan kemajuan pelaksanaan penelitian',
                'is_active' => true,
            ],
            [
                'kode' => 'LKA',
                'nama' => 'LaporanKemajuanPengabdian',
                'deskripsi' => 'Dokumen laporan kemajuan pelaksanaan pengabdian',
                'is_active' => true,
            ],
            [
                'kode' => 'LAP',
                'nama' => 'LaporanAkhirPenelitian',
                'deskripsi' => 'Dokumen laporan akhir hasil penelitian',
                'is_active' => true,
            ],
            [
                'kode' => 'LAA',
                'nama' => 'LaporanAkhirPengabdian',
                'deskripsi' => 'Dokumen laporan akhir hasil pengabdian',
                'is_active' => true,
            ],
            [
                'kode' => 'PDP',
                'nama' => 'ProposalDipaPenelitian',
                'deskripsi' => 'Proposal penelitian dengan skema pendanaan DIPA',
                'is_active' => true,
            ],
            [
                'kode' => 'PDA',
                'nama' => 'ProposalDipaPengabdian',
                'deskripsi' => 'Proposal pengabdian dengan skema pendanaan DIPA',
                'is_active' => true,
            ],
            [
                'kode' => 'PPP',
                'nama' => 'ProposalPusatPenelitian',
                'deskripsi' => 'Proposal penelitian dengan pendanaan dari pusat',
                'is_active' => true,
            ],
            [
                'kode' => 'PPA',
                'nama' => 'ProposalPusatPengabdian',
                'deskripsi' => 'Proposal pengabdian dengan pendanaan dari pusat',
                'is_active' => true,
            ],
            [
                'kode' => 'PMP',
                'nama' => 'ProposalMandiriPenelitian',
                'deskripsi' => 'Proposal penelitian yang bersifat mandiri',
                'is_active' => true,
            ],
            [
                'kode' => 'PMA',
                'nama' => 'ProposalMandiriPengabdian',
                'deskripsi' => 'Proposal pengabdian yang bersifat mandiri',
                'is_active' => true,
            ],
        ];

        foreach ($jenisArsip as $jenis) {
            JenisArsip::updateOrCreate(
                ['kode' => $jenis['kode']],
                $jenis
            );
        }

        $this->command->info('✅ JenisArsip seeder completed: ' . count($jenisArsip) . ' types created/updated');
    }
}
