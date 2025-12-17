<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * ✅ Display listing of users with pagination
     */
    public function index(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            Log::info('User list requested', [
                'correlation_id' => $correlationId,
                'requested_by' => $request->user()->id,
            ]);

            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');

            $query = User::query();

            if ($search) {
                // Simple validation
                if (!preg_match("/^[a-zA-Z0-9\s@._-]+$/", $search)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Search input tidak valid',
                    ], 422);
                }

                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->latest()->paginate($perPage);

            Log::info('User list retrieved', [
                'correlation_id' => $correlationId,
                'total' => $users->total(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Daftar pengguna berhasil diambil',
                'data' => $users,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving users', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar pengguna',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ Store new user
     */
    public function store(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        Log::info('Create user attempt', [
            'correlation_id' => $correlationId,
            'created_by' => $request->user()->id,
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,p3m',
            ], [
                'name.required' => 'Nama wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.unique' => 'Email sudah terdaftar',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 6 karakter',
                'role.required' => 'Role wajib dipilih',
                'role.in' => 'Role hanya boleh admin atau p3m',
            ]);

            if ($validator->fails()) {
                Log::warning('User creation validation failed', [
                    'correlation_id' => $correlationId,
                    'errors' => $validator->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            Log::info('User created successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengguna berhasil dibuat',
                'data' => $user,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating user', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengguna',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ Display single user
     */
    public function show(Request $request, $id)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            Log::info('User detail requested', [
                'correlation_id' => $correlationId,
                'user_id' => $id,
            ]);

            $user = User::find($id);

            if (!$user) {
                Log::warning('User not found', [
                    'correlation_id' => $correlationId,
                    'user_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail pengguna berhasil diambil',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving user', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pengguna',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ Update user
     */
    public function update(Request $request, $id)
    {
        $correlationId = $request->attributes->get('correlation_id');

        Log::info('Update user attempt', [
            'correlation_id' => $correlationId,
            'user_id' => $id,
            'updated_by' => $request->user()->id,
        ]);

        try {
            $user = User::find($id);

            if (!$user) {
                Log::warning('User not found', [
                    'correlation_id' => $correlationId,
                    'user_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6',
                'role' => 'sometimes|in:admin,p3m',
            ], [
                'name.string' => 'Nama harus berupa teks',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah digunakan',
                'password.min' => 'Password minimal 6 karakter',
                'role.in' => 'Role hanya boleh admin atau p3m',
            ]);

            if ($validator->fails()) {
                Log::warning('User update validation failed', [
                    'correlation_id' => $correlationId,
                    'errors' => $validator->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->has('role')) {
                $user->role = $request->role;
            }

            $user->save();

            Log::info('User updated successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengguna berhasil diupdate',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating user', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengguna',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ Delete user
     */
    public function destroy(Request $request, $id)
    {
        $correlationId = $request->attributes->get('correlation_id');

        Log::info('Delete user attempt', [
            'correlation_id' => $correlationId,
            'user_id' => $id,
            'deleted_by' => $request->user()->id,
        ]);

        try {
            $user = User::find($id);

            if (!$user) {
                Log::warning('User not found', [
                    'correlation_id' => $correlationId,
                    'user_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan',
                ], 404);
            }

            // Prevent deleting self
            if ($user->id === $request->user()->id) {
                Log::warning('Attempt to delete self', [
                    'correlation_id' => $correlationId,
                    'user_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus akun sendiri',
                ], 403);
            }

            $userEmail = $user->email;
            $user->delete();

            Log::info('User deleted successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $id,
                'email' => $userEmail,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengguna berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting user', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengguna',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }
}
