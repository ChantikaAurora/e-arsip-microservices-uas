<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('USER_SERVICE_URL', 'http://localhost:8001');
    }

    /**
     * Get user profile from User Service
     */
    public function getUserProfile(string $token, string $correlationId): array
    {
        Log::info('Calling User Service - Get Profile', [
            'correlation_id' => $correlationId,
            'url' => $this->baseUrl . '/api/profile',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->baseUrl . '/api/profile');

            if ($response->successful()) {
                Log::info('User Service responded successfully', [
                    'correlation_id' => $correlationId,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::warning('User Service returned error', [
                'correlation_id' => $correlationId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'User Service error: ' . $response->status(),
                'data' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to call User Service', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'User Service unavailable: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get users list from User Service
     */
    public function getUsersList(string $token, string $correlationId, array $params = []): array
    {
        Log::info('Calling User Service - Get Users List', [
            'correlation_id' => $correlationId,
            'params' => $params,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->baseUrl . '/api/users', $params);

            if ($response->successful()) {
                Log::info('User Service - Users list retrieved', [
                    'correlation_id' => $correlationId,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::warning('User Service - Users list error', [
                'correlation_id' => $correlationId,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'User Service error: ' . $response->status(),
                'data' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to call User Service - Users list', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'User Service unavailable',
                'data' => null,
            ];
        }
    }
}
