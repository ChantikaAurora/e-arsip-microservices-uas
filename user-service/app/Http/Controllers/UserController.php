<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Fungsi untuk melihat daftar semua pengguna
    public function index()
    {
        // Ambil semua data user dari database
        $users = User::all();

        // Kembalikan dalam format JSON
        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil',
            'data' => $users
        ], 200);
    }
}