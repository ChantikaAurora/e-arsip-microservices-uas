<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes - Document Service
|--------------------------------------------------------------------------
|
| Routes untuk Document Service yang terpisah dari User Service.
| Autentikasi dilakukan via HTTP request ke User Service.
|
*/

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'document-service',
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'database' => config('database.connections.mysql.database'),
    ]);
});

// Service info endpoint (public)
Route::get('/info', function () {
    return response()->json([
        'service' => 'Document Service',
        'version' => '1.0.0',
        'description' => 'Microservice untuk pengelolaan dokumen e-Arsip P3M',
        'endpoints' => [
            'GET /api/health' => 'Health check',
            'GET /api/info' => 'Service information',
            'GET /api/documents' => 'List all documents (protected)',
            'POST /api/documents' => 'Create new document (protected)',
            'GET /api/documents/{id}' => 'Get document detail (protected)',
            'PUT /api/documents/{id}' => 'Update document (protected)',
            'DELETE /api/documents/{id}' => 'Delete document (protected)',
        ],
        'authentication' => 'Bearer Token from User Service required',
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
|
| Routes yang memerlukan autentikasi via User Service
| Middleware: auth.user-service
|
*/

Route::middleware(['auth.user-service'])->group(function () {

    // Document CRUD endpoints
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])
            ->name('documents.index');

        Route::post('/', [DocumentController::class, 'store'])
            ->name('documents.store');

        Route::get('/{id}', [DocumentController::class, 'show'])
            ->name('documents.show');

        Route::put('/{id}', [DocumentController::class, 'update'])
            ->name('documents.update');

        Route::delete('/{id}', [DocumentController::class, 'destroy'])
            ->name('documents.destroy');
    });

    // Endpoint untuk mendapatkan info user yang sedang login
    Route::get('/me', function (Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'User information from User Service',
            'data' => $request->get('authenticated_user'),
        ]);
    });
});
