<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\JenisArsip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Seed JenisArsip untuk test
        $this->seed(\Database\Seeders\JenisArsipSeeder::class);

        // Fake storage
        Storage::fake('public');

        // Create and authenticate user
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test: Health check endpoint
     */
    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'OK',
                     'service' => 'Document Service',
                 ]);
    }

    /**
     * Test: Can upload document surat masuk
     */
    public function test_can_upload_document_surat_masuk(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $jenisArsip = JenisArsip::where('kode', 'SM')->first();

        $response = $this->postJson('/api/documents', [
            'type' => 'masuk',
            'nomor_surat' => '001/SM/P3M/I/2024',
            'kode_klasifikasi' => 'SK-001',
            'tanggal_surat' => '2024-01-15',
            'tanggal_terima' => '2024-01-16',
            'asal_surat' => 'Kementerian Pendidikan',
            'pengirim' => 'Direktur Jenderal',
            'perihal' => 'Undangan Rapat Koordinasi',
            'jenis' => $jenisArsip->id,
            'lampiran' => '2 lembar',
            'keterangan' => 'Surat penting',
            'file' => $file,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Document created successfully',
                 ])
                 ->assertJsonStructure([
                     'message',
                     'document' => [
                         'id',
                         'type',
                         'nomor_surat',
                         'perihal',
                         'file',
                         'created_at',
                     ]
                 ]);

        $this->assertDatabaseHas('documents', [
            'type' => 'masuk',
            'nomor_surat' => '001/SM/P3M/I/2024',
            'asal_surat' => 'Kementerian Pendidikan',
        ]);
    }

    /**
     * Test: Can upload document surat keluar
     */
    public function test_can_upload_document_surat_keluar(): void
    {
        $file = UploadedFile::fake()->create('surat-keluar.pdf', 150, 'application/pdf');
        $jenisArsip = JenisArsip::where('kode', 'SK')->first();

        $response = $this->postJson('/api/documents', [
            'type' => 'keluar',
            'nomor_surat' => '002/SK/P3M/I/2024',
            'kode_klasifikasi' => 'SK-002',
            'tanggal_surat' => '2024-01-20',
            'tujuan_surat' => 'Universitas Indonesia',
            'penerima' => 'Rektor',
            'perihal' => 'Permohonan Kerjasama Penelitian',
            'jenis' => $jenisArsip->id,
            'file' => $file,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Document created successfully',
                 ]);

        $this->assertDatabaseHas('documents', [
            'type' => 'keluar',
            'nomor_surat' => '002/SK/P3M/I/2024',
            'tujuan_surat' => 'Universitas Indonesia',
        ]);
    }

    /**
     * Test: Validation fails for invalid document type
     */
    public function test_validation_fails_for_invalid_document_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/documents', [
            'type' => 'invalid_type',
            'nomor_surat' => '001/XX/2024',
            'tanggal_surat' => '2024-01-15',
            'perihal' => 'Test',
            'jenis' => 1,
            'file' => $file,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test: Can get document statistics
     */
    public function test_can_get_document_statistics(): void
    {
        Document::factory()->count(5)->masuk()->create();
        Document::factory()->count(3)->keluar()->create();

        $response = $this->getJson('/api/documents/stats');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total_documents',
                     'total_surat_masuk',
                     'total_surat_keluar',
                     'documents_this_month',
                     'recent_documents',
                 ])
                 ->assertJson([
                     'total_documents' => 8,
                     'total_surat_masuk' => 5,
                     'total_surat_keluar' => 3,
                 ]);
    }

    /**
     * Test: Can list documents with pagination
     */
    public function test_can_list_documents_with_pagination(): void
    {
        Document::factory()->count(25)->create();

        $response = $this->getJson('/api/documents');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta' => [
                         'current_page',
                         'per_page',
                         'total',
                         'last_page',
                     ],
                 ]);

        // Default pagination is 10 items per page
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    /**
     * Test: Can filter documents by type masuk
     */
    public function test_can_filter_documents_by_type_masuk(): void
    {
        Document::factory()->count(5)->masuk()->create();
        Document::factory()->count(3)->keluar()->create();

        $response = $this->getJson('/api/documents?type=masuk');

        $response->assertStatus(200);

        $documents = $response->json('data');
        $this->assertCount(5, $documents);

        foreach ($documents as $doc) {
            $this->assertEquals('masuk', $doc['type']);
        }
    }

    /**
     * Test: Can filter documents by type keluar
     */
    public function test_can_filter_documents_by_type_keluar(): void
    {
        Document::factory()->count(5)->masuk()->create();
        Document::factory()->count(3)->keluar()->create();

        $response = $this->getJson('/api/documents?type=keluar');

        $response->assertStatus(200);

        $documents = $response->json('data');
        $this->assertCount(3, $documents);

        foreach ($documents as $doc) {
            $this->assertEquals('keluar', $doc['type']);
        }
    }

    /**
     * Test: Can show single document
     */
    public function test_can_show_single_document(): void
    {
        $document = Document::factory()->masuk()->create([
            'nomor_surat' => '999/TEST/P3M/2024',
            'perihal' => 'Test Perihal Dokumen',
        ]);

        $response = $this->getJson("/api/documents/{$document->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $document->id,
                     'nomor_surat' => '999/TEST/P3M/2024',
                     'perihal' => 'Test Perihal Dokumen',
                 ])
                 ->assertJsonStructure([
                     'id',
                     'type',
                     'nomor_surat',
                     'kode_klasifikasi',
                     'tanggal_surat',
                     'perihal',
                     'file',
                     'jenis_arsip',
                 ]);
    }

    /**
     * Test: Returns 404 for non-existent document
     */
    public function test_returns_404_for_non_existent_document(): void
    {
        $response = $this->getJson('/api/documents/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Document not found',
                 ]);
    }

    /**
     * Test: Can update document
     */
    public function test_can_update_document(): void
    {
        $document = Document::factory()->masuk()->create([
            'perihal' => 'Original Perihal',
        ]);

        $response = $this->putJson("/api/documents/{$document->id}", [
            'type' => 'masuk',
            'nomor_surat' => $document->nomor_surat,
            'kode_klasifikasi' => $document->kode_klasifikasi,
            'tanggal_surat' => $document->tanggal_surat->format('Y-m-d'),
            'tanggal_terima' => $document->tanggal_terima->format('Y-m-d'),
            'asal_surat' => $document->asal_surat,
            'pengirim' => $document->pengirim,
            'perihal' => 'Updated Perihal Baru',
            'jenis' => $document->jenis,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Document updated successfully',
                 ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'perihal' => 'Updated Perihal Baru',
        ]);
    }

    /**
     * Test: Can delete document
     */
    public function test_can_delete_document(): void
    {
        $document = Document::factory()->create();

        $response = $this->deleteJson("/api/documents/{$document->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Document deleted successfully',
                 ]);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    }

    /**
     * Test: Can search documents by nomor surat
     */
    public function test_can_search_documents_by_nomor_surat(): void
    {
        Document::factory()->create([
            'nomor_surat' => '123/UNIQUE/P3M/2024',
            'perihal' => 'Dokumen Unik',
        ]);

        Document::factory()->create([
            'nomor_surat' => '456/OTHER/P3M/2024',
            'perihal' => 'Dokumen Lain',
        ]);

        $response = $this->getJson('/api/documents?search=UNIQUE');

        $response->assertStatus(200);

        $documents = $response->json('data');
        $this->assertGreaterThan(0, count($documents));
        $this->assertStringContainsString('UNIQUE', $documents[0]['nomor_surat']);

    }

    /**
     * Test: Can search documents by perihal
     */
    public function test_can_search_documents_by_perihal(): void
    {
        Document::factory()->create([
            'perihal' => 'Undangan Rapat Penting',
        ]);

        Document::factory()->create([
            'perihal' => 'Surat Biasa',
        ]);

        $response = $this->getJson('/api/documents?search=Rapat');

        $response->assertStatus(200);

        $documents = $response->json('data');
        $this->assertGreaterThan(0, count($documents));
    }

    /**
     * Test: Can filter documents by jenis arsip
     */
    public function test_can_filter_documents_by_jenis_arsip(): void
    {
        $jenisArsip = JenisArsip::where('kode', 'SM')->first();

        Document::factory()->count(3)->create([
            'jenis' => $jenisArsip->id,
        ]);

        $response = $this->getJson("/api/documents?jenis={$jenisArsip->id}");

        $response->assertStatus(200);

        $documents = $response->json('data');
        foreach ($documents as $doc) {
            $this->assertEquals($jenisArsip->id, $doc['jenis']);
        }
    }

    /**
     * Test: Can filter documents by date range
     */
    public function test_can_filter_documents_by_date_range(): void
    {
        Document::factory()->create([
            'tanggal_surat' => '2024-01-15',
        ]);

        Document::factory()->create([
            'tanggal_surat' => '2024-06-15',
        ]);

        $response = $this->getJson('/api/documents?start_date=2024-01-01&end_date=2024-01-31');

        $response->assertStatus(200);
    }

    /**
     * Test: Correlation ID is present in response header
     */
    public function test_correlation_id_present_in_response(): void
    {
        $correlationId = 'test-correlation-123';

        $response = $this->withHeaders([
            'X-Correlation-ID' => $correlationId,
        ])->getJson('/api/documents');

        $response->assertStatus(200)
                 ->assertHeader('X-Correlation-ID', $correlationId);
    }

    /**
     * Test: File validation rejects invalid file types
     */
    public function test_file_validation_rejects_invalid_file_types(): void
    {
        $file = UploadedFile::fake()->create('document.exe', 100);

        $response = $this->postJson('/api/documents', [
            'type' => 'masuk',
            'nomor_surat' => '001/SM/2024',
            'tanggal_surat' => '2024-01-15',
            'tanggal_terima' => '2024-01-16',
            'asal_surat' => 'Test',
            'pengirim' => 'Test',
            'perihal' => 'Test',
            'jenis' => 1,
            'file' => $file,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['file']);
    }
}
