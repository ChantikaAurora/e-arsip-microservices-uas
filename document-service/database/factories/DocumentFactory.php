<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\JenisArsip;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['masuk', 'keluar']);
        return $this->generateDocumentData($type);
    }

    /**
     * Generate document data based on type
     */
    private function generateDocumentData(string $type): array
    {
        // Ambil jenis arsip random
        $jenisArsip = JenisArsip::inRandomOrder()->first();
        $jenisId = $jenisArsip ? $jenisArsip->id : 1;

        $tanggalSurat = $this->faker->dateTimeBetween('-6 months', 'now');

        $baseData = [
            'type' => $type,
            'nomor_surat' => $this->generateNomorSurat($type),
            'kode_klasifikasi' => $this->generateKodeKlasifikasi($jenisArsip?->kode),
            'tanggal_surat' => $tanggalSurat,
            'perihal' => $this->generatePerihal($jenisArsip?->kode),
            'lampiran' => $this->faker->optional()->numberBetween(1, 5) . ' lembar',
            'jenis' => $jenisId,
            'keterangan' => $this->faker->optional()->sentence(10),
            'file' => 'documents/sample-' . $this->faker->uuid() . '.pdf',
            'created_by' => 1,
        ];

        // Field khusus surat masuk / keluar
        if ($type === 'masuk') {
            return array_merge($baseData, [
                'tanggal_terima' => $this->faker->dateTimeBetween($tanggalSurat, 'now'),
                'asal_surat' => $this->generateAsalSurat(),
                'pengirim' => $this->faker->name(),
                'tujuan_surat' => null,
                'penerima' => null,
            ]);
        }

        return array_merge($baseData, [
            'tanggal_terima' => null,
            'asal_surat' => null,
            'pengirim' => null,
            'tujuan_surat' => $this->generateTujuanSurat(),
            'penerima' => $this->faker->name(),
        ]);
    }

    /**
     * Generate nomor surat
     */
    private function generateNomorSurat(string $type): string
    {
        $prefix = $type === 'masuk' ? 'SM' : 'SK';
        $number = $this->faker->unique()->numberBetween(1, 999);
        $month = $this->faker->numberBetween(1, 12);
        $year = date('Y');

        return sprintf('%03d/%s/P3M/%s/%s', $number, $prefix, $this->getRomanMonth($month), $year);
    }

    /**
     * Generate kode klasifikasi berdasarkan jenis arsip
     */
    private function generateKodeKlasifikasi(?string $kode): string
    {
        if (!$kode) {
            return $this->faker->bothify('??-###');
        }

        return strtoupper(substr($kode, 0, 3)) . '-' . $this->faker->numberBetween(100, 999);
    }

    /**
     * Month to Roman numeral
     */
    private function getRomanMonth(int $month): string
    {
        $romans = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return $romans[$month - 1];
    }

    /**
     * Generate perihal berdasarkan jenis arsip baru (14 jenis)
     */
    private function generatePerihal(?string $kodeJenis): string
    {
        $map = [
            'SM'            => 'Surat Masuk – ' . $this->faker->sentence(3),
            'SK'            => 'Surat Keluar – ' . $this->faker->sentence(3),

            'AP'            => 'Pengajuan Anggaran Penelitian',
            'AGP'           => 'Pengajuan Anggaran Pengabdian',

            'LKP'           => 'Laporan Kemajuan Penelitian',
            'LKG'           => 'Laporan Kemajuan Pengabdian',
            'LAP'           => 'Laporan Akhir Penelitian',
            'LAG'           => 'Laporan Akhir Pengabdian',

            'PDP'           => 'Proposal DIPA Penelitian',
            'PDA'           => 'Proposal DIPA Pengabdian',
            'PPP'           => 'Proposal Pusat Penelitian',
            'PPA'           => 'Proposal Pusat Pengabdian',
            'PMP'           => 'Proposal Mandiri Penelitian',
            'PMA'           => 'Proposal Mandiri Pengabdian',
        ];

        // Bersihkan kode jika ada separator (contoh: "PP-DIPA")
        if ($kodeJenis) {
            $simpleKode = strtoupper(str_replace(['-', ' '], '', $kodeJenis));

            foreach ($map as $key => $value) {
                if (str_contains($simpleKode, str_replace('-', '', $key))) {
                    return $value;
                }
            }
        }

        // Default jika tidak ditemukan
        return $this->faker->sentence(4);
    }

    /**
     * Generate asal surat
     */
    private function generateAsalSurat(): string
    {
        $asalSurat = [
            'Kementerian Pendidikan dan Kebudayaan',
            'BRIN (Badan Riset dan Inovasi Nasional)',
            'Pemerintah Daerah Kota',
            'LLDIKTI Wilayah X',
            'Universitas Negeri Padang',
            'Institut Teknologi Bandung',
        ];

        return $this->faker->randomElement($asalSurat);
    }

    /**
     * Generate tujuan surat
     */
    private function generateTujuanSurat(): string
    {
        $tujuan = [
            'Rektor Universitas',
            'Direktur LLDIKTI',
            'Ketua LPPM',
            'Dekan Fakultas',
            'Kepala Laboratorium',
        ];

        return $this->faker->randomElement($tujuan);
    }

    /**
     * Factory state: surat masuk
     */
    public function masuk(): static
    {
        return $this->state(fn () => $this->generateDocumentData('masuk'));
    }

    /**
     * Factory state: surat keluar
     */
    public function keluar(): static
    {
        return $this->state(fn () => $this->generateDocumentData('keluar'));
    }
}
