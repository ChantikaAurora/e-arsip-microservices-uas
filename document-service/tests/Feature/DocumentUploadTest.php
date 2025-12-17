<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    public function test_document_upload_works(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('surat.pdf', 100);

        $response = $this->postJson('/api/documents', [
            'type' => 'masuk',
            'nomor_surat' => '123',
            'kode_klasifikasi' => 'ABC',
            'tanggal_surat' => '2024-10-10',
            'jenis' => 1,
            'file' => $file,
        ]);

        $response->assertStatus(201);

        $this->assertTrue(
        Storage::disk('public')->exists(
            'documents/' . $file->hashName()
        )
    );

    }
}
