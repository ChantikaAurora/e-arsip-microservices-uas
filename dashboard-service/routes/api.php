<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes - Dashboard Service
|--------------------------------------------------------------------------
*/

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'dashboard-service',
        'status' => 'healthy',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Service info endpoint (public)
Route::get('/info', function () {
    return response()->json([
        'service' => 'Dashboard Service',
        'version' => '1.0.0',
        'description' => 'Orchestrator service untuk E-Arsip P3M',
        'endpoints' => [
            'GET /api/health' => 'Health check',
            'GET /api/info' => 'Service information',
            'GET /api/dashboard' => 'Complete dashboard data (protected)',
            'GET /api/dashboard/user' => 'User info only (protected)',
            'GET /api/dashboard/documents' => 'Documents list (protected)',
        ],
        'dependencies' => [
            'user-service' => env('USER_SERVICE_URL'),
            'document-service' => env('DOCUMENT_SERVICE_URL'),
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
| All dashboard endpoints require Bearer token from User Service
*/

Route::prefix('dashboard')->group(function () {
    // Complete dashboard (combines User + Document data)
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard.index');

    // User info only
    Route::get('/user', [DashboardController::class, 'userInfo'])
        ->name('dashboard.user');

    // Documents via dashboard
    Route::get('/documents', [DashboardController::class, 'documents'])
        ->name('dashboard.documents');
});
