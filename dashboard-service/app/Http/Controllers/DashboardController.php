<?php

namespace App\Http\Controllers;

use App\Services\UserServiceClient;
use App\Services\DocumentServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private UserServiceClient $userService;
    private DocumentServiceClient $documentService;

    public function __construct(
        UserServiceClient $userService,
        DocumentServiceClient $documentService
    ) {
        $this->userService = $userService;
        $this->documentService = $documentService;
    }

    /**
     * Get complete dashboard data
     * Combines data from User Service and Document Service
     */
    public function index(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        Log::info('Dashboard request received', [
            'correlation_id' => $correlationId,
            'has_token' => !empty($token),
        ]);

        // Validate token
        if (!$token) {
            Log::warning('Dashboard request without token', [
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'Token not provided',
            ], 401);
        }

        try {
            // Call User Service - Get Profile
            $userResult = $this->userService->getUserProfile($token, $correlationId);

            // Call Document Service - Get Stats
            $docStatsResult = $this->documentService->getDocumentStats($token, $correlationId);

            // Check if services are available
            if (!$userResult['success']) {
                Log::error('User Service unavailable', [
                    'correlation_id' => $correlationId,
                    'error' => $userResult['error'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'User Service unavailable',
                    'error' => $userResult['error'],
                ], 503);
            }

            if (!$docStatsResult['success']) {
                Log::warning('Document Service unavailable', [
                    'correlation_id' => $correlationId,
                    'error' => $docStatsResult['error'],
                ]);

                // Continue with user data only
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard data retrieved (Document Service unavailable)',
                    'data' => [
                        'user' => $userResult['data']['data'] ?? null,
                        'documents' => null,
                        'stats' => null,
                    ],
                    'warnings' => [
                        'document_service' => 'unavailable',
                    ],
                ], 200);
            }

            // Success - combine data
            Log::info('Dashboard data retrieved successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $userResult['data']['data']['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'user' => $userResult['data']['data'] ?? null,
                    'document_stats' => $docStatsResult['data'] ?? null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Dashboard error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dashboard error',
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user info only
     */
    public function userInfo(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        Log::info('User info request', [
            'correlation_id' => $correlationId,
        ]);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        try {
            $result = $this->userService->getUserProfile($token, $correlationId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'User Service unavailable',
                    'error' => $result['error'],
                ], 503);
            }

            return response()->json([
                'success' => true,
                'message' => 'User info retrieved',
                'data' => $result['data']['data'] ?? null,
            ], 200);

        } catch (\Exception $e) {
            Log::error('User info error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user info',
            ], 500);
        }
    }

    /**
     * Get documents data
     */
    public function documents(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $token = $request->bearerToken();

        Log::info('Documents request via Dashboard', [
            'correlation_id' => $correlationId,
        ]);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        try {
            $params = [
                'per_page' => $request->input('per_page', 10),
                'page' => $request->input('page', 1),
            ];

            $result = $this->documentService->getDocumentsList($token, $correlationId, $params);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document Service unavailable',
                    'error' => $result['error'],
                ], 503);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documents retrieved',
                'data' => $result['data']['data'] ?? null,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Documents retrieval error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving documents',
            ], 500);
        }
    }
}
