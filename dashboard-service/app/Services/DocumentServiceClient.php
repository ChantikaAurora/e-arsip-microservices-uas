<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('DOCUMENT_SERVICE_URL', 'http://localhost:8002');
    }

    /**
     * Get documents list from Document Service
     */
    public function getDocumentsList(string $token, string $correlationId, array $params = []): array
    {
        Log::info('Calling Document Service - Get Documents List', [
            'correlation_id' => $correlationId,
            'url' => $this->baseUrl . '/api/documents',
            'params' => $params,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->baseUrl . '/api/documents', $params);

            if ($response->successful()) {
                Log::info('Document Service responded successfully', [
                    'correlation_id' => $correlationId,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::warning('Document Service returned error', [
                'correlation_id' => $correlationId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Document Service error: ' . $response->status(),
                'data' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to call Document Service', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Document Service unavailable: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get document statistics from Document Service
     */
    public function getDocumentStats(string $token, string $correlationId): array
    {
        Log::info('Calling Document Service - Get Stats', [
            'correlation_id' => $correlationId,
        ]);

        try {
            // Get all documents
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->baseUrl . '/api/documents', ['per_page' => 1000]);

            if ($response->successful()) {
                $data = $response->json();
                $documents = $data['data']['data'] ?? [];

                // Calculate stats
                $stats = [
                    'total_documents' => count($documents),
                    'by_jenis' => [],
                ];

                // Group by jenis_arsip
                foreach ($documents as $doc) {
                    $jenis = $doc['jenis_arsip']['nama'] ?? 'Unknown';
                    if (!isset($stats['by_jenis'][$jenis])) {
                        $stats['by_jenis'][$jenis] = 0;
                    }
                    $stats['by_jenis'][$jenis]++;
                }

                Log::info('Document stats calculated', [
                    'correlation_id' => $correlationId,
                    'total' => $stats['total_documents'],
                ]);

                return [
                    'success' => true,
                    'data' => $stats,
                ];
            }

            return [
                'success' => false,
                'error' => 'Document Service error',
                'data' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get document stats', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Document Service unavailable',
                'data' => null,
            ];
        }
    }
}
