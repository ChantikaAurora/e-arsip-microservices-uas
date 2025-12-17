<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    /**
     * Test: Health check endpoint works
     */
    public function test_health_check_works(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'service' => 'dashboard-service',
                     'status' => 'healthy',
                 ]);
    }

    /**
     * Test: Info endpoint returns service information
     */
    public function test_info_endpoint_returns_service_info(): void
    {
        $response = $this->getJson('/api/info');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'service',
                     'version',
                     'description',
                     'endpoints',
                     'dependencies',
                 ]);
    }

    /**
     * Test: Dashboard requires authentication
     */
    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Authentication required',
                 ]);
    }

    /**
     * Test: Complete dashboard returns combined data
     */
    public function test_complete_dashboard_returns_combined_data(): void
    {
        // Mock User Service response
        Http::fake([
            '*/api/profile' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'Aurora Admin',
                    'email' => 'aurora@test.com',
                    'role' => 'admin',
                ],
            ], 200),

            // Mock Document Service response
            '*/api/documents*' => Http::response([
                'success' => true,
                'data' => [
                    'data' => [
                        [
                            'id' => 1,
                            'nomor_dokumen' => 'DOC-001',
                            'judul' => 'Dokumen Test',
                            'jenis_arsip' => [
                                'id' => 1,
                                'nama' => 'Surat Masuk',
                            ],
                        ],
                        [
                            'id' => 2,
                            'nomor_dokumen' => 'DOC-002',
                            'judul' => 'Dokumen Test 2',
                            'jenis_arsip' => [
                                'id' => 2,
                                'nama' => 'Surat Keluar',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token-12345',
            'X-Correlation-ID' => 'test-corr-001',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Dashboard data retrieved successfully',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user',
                         'document_stats',
                     ],
                 ])
                 ->assertHeader('X-Correlation-ID', 'test-corr-001');

        // Verify HTTP calls were made
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token-12345') &&
                   $request->hasHeader('X-Correlation-ID', 'test-corr-001');
        });
    }

    /**
     * Test: Dashboard handles User Service unavailable
     */
    public function test_dashboard_handles_user_service_unavailable(): void
    {
        Http::fake([
            '*/api/profile' => Http::response([], 500),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
            'X-Correlation-ID' => 'test-corr-002',
        ])->getJson('/api/dashboard');

        $response->assertStatus(503)
                 ->assertJson([
                     'success' => false,
                     'message' => 'User Service unavailable',
                 ]);
    }

    /**
     * Test: Dashboard handles Document Service unavailable gracefully
     */
    public function test_dashboard_handles_document_service_unavailable_gracefully(): void
    {
        Http::fake([
            '*/api/profile' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ], 200),

            '*/api/documents*' => Http::response([], 500),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
            'X-Correlation-ID' => 'test-corr-003',
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Dashboard data retrieved (Document Service unavailable)',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'user',
                     ],
                     'warnings',
                 ]);
    }

    /**
     * Test: User info endpoint works
     */
    public function test_user_info_endpoint_works(): void
    {
        Http::fake([
            '*/api/profile' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'Aurora',
                    'email' => 'aurora@test.com',
                    'role' => 'admin',
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
            'X-Correlation-ID' => 'test-corr-004',
        ])->getJson('/api/dashboard/user');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User info retrieved',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'role',
                     ],
                 ]);
    }

    /**
     * Test: Documents endpoint forwards request correctly
     */
    public function test_documents_endpoint_forwards_request_correctly(): void
    {
        Http::fake([
            '*/api/documents*' => Http::response([
                'success' => true,
                'data' => [
                    'data' => [
                        ['id' => 1, 'nomor_dokumen' => 'DOC-001'],
                        ['id' => 2, 'nomor_dokumen' => 'DOC-002'],
                        ['id' => 3, 'nomor_dokumen' => 'DOC-003'],
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => 10,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
            'X-Correlation-ID' => 'test-corr-005',
        ])->getJson('/api/dashboard/documents?per_page=10');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Documents retrieved',
                 ]);

        // Verify pagination params were forwarded
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'per_page=10');
        });
    }

    /**
     * Test: Correlation ID is forwarded to services
     */
    public function test_correlation_id_forwarded_to_services(): void
    {
        Http::fake([
            '*/api/profile' => Http::response([
                'success' => true,
                'data' => ['id' => 1, 'name' => 'Test'],
            ], 200),

            '*/api/documents*' => Http::response([
                'success' => true,
                'data' => ['data' => []],
            ], 200),
        ]);

        $correlationId = 'aurora-test-correlation-123';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
            'X-Correlation-ID' => $correlationId,
        ])->getJson('/api/dashboard');

        // Verify correlation ID was forwarded to both services
        Http::assertSent(function ($request) use ($correlationId) {
            return $request->hasHeader('X-Correlation-ID', $correlationId);
        });

        // Verify correlation ID in response header
        $response->assertHeader('X-Correlation-ID', $correlationId);
    }

    /**
     * Test: Token is forwarded to services
     */
    public function test_token_forwarded_to_services(): void
    {
        Http::fake([
            '*/api/profile' => Http::response([
                'success' => true,
                'data' => ['id' => 1],
            ], 200),

            '*/api/documents*' => Http::response([
                'success' => true,
                'data' => ['data' => []],
            ], 200),
        ]);

        $token = 'my-secure-token-xyz-123';

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/dashboard');

        // Verify token was forwarded to both services
        Http::assertSent(function ($request) use ($token) {
            return $request->hasHeader('Authorization', 'Bearer ' . $token);
        });
    }

    /**
     * Test: Generates correlation ID if not provided
     */
    public function test_generates_correlation_id_if_not_provided(): void
    {
        Http::fake([
            '*/api/profile' => Http::response([
                'success' => true,
                'data' => ['id' => 1],
            ], 200),

            '*/api/documents*' => Http::response([
                'success' => true,
                'data' => ['data' => []],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token',
        ])->getJson('/api/dashboard');

        // Should have a correlation ID in response (auto-generated UUID)
        $response->assertHeader('X-Correlation-ID');

        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotEmpty($correlationId);

        // Should be a valid UUID format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $correlationId
        );
    }
}
