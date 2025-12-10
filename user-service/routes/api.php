<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; // Panggil Controller Login
use App\Http\Controllers\UserController; // Panggil Controller List User

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. Jalur Auth (Register & Login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 2. Jalur Lihat Daftar User (Sesuai request Aurora buat JSON)
Route::get('/users', [UserController::class, 'index']);

// 3. Jalur Khusus (Harus Login / Punya Token)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});