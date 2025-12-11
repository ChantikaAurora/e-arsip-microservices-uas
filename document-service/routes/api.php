<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

// Route::middleware('auth:sanctum')->group(function () {

// List + filter + search
Route::get('/documents', [DocumentController::class, 'index']);

// Create
Route::post('/documents', [DocumentController::class, 'store']);

// Stats
Route::get('/documents/stats', [DocumentController::class, 'stats']);

// Detail
Route::get('/documents/{id}', [DocumentController::class, 'show']);

// Update
Route::put('/documents/{id}', [DocumentController::class, 'update']);

// Delete
Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

// Download file
Route::get('/documents/{id}/download', [DocumentController::class, 'download']);
    
// });
