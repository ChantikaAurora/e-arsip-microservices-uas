<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * ✅ REQUIREMENT (a): Register with logging
     */
    public function register(Request $request)
    {
        // Get Correlation ID
        $correlationId = $request->attributes->get('correlation_id');

        Log::info('Register attempt', [
            'correlation_id' => $correlationId,
            'email' => $request->email,
        ]);

        try {
            // Laravel 8 style validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'required|in:admin,p3m',
            ], [
                'name.required' => 'Nama wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.unique' => 'Email sudah terdaftar',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 6 karakter',
                'password.confirmed' => 'Konfirmasi password tidak cocok',
                'role.required' => 'Role wajib dipilih',
                'role.in' => 'Role hanya boleh admin atau p3m',
            ]);

            if ($validator->fails()) {
                Log::warning('Registration validation failed', [
                    'correlation_id' => $correlationId,
                    'errors' => $validator->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            // Generate token
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('User registered successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ REQUIREMENT (a): Login with logging
     */
    public function login(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        Log::info('Login attempt', [
            'correlation_id' => $correlationId,
            'email' => $request->email,
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'password.required' => 'Password wajib diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Login failed - invalid credentials', [
                    'correlation_id' => $correlationId,
                    'email' => $request->email,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah',
                ], 401);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('Login successful', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'user' => $user,
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ NEW: Logout endpoint
     */
    public function logout(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $user = $request->user();

            Log::info('Logout attempt', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
            ]);

            // Laravel 8: Delete current token
            $request->user()->currentAccessToken()->delete();

            Log::info('Logout successful', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout gagal',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get profile
     */
    public function profile(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $user = $request->user();

            Log::info('Profile retrieved', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diambil',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Profile error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil profile',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * ✅ NEW: Update profile
     */
    public function updateProfile(Request $request)
    {
        $correlationId = $request->attributes->get('correlation_id');
        $user = $request->user();

        Log::info('Profile update attempt', [
            'correlation_id' => $correlationId,
            'user_id' => $user->id,
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
            ], [
                'name.string' => 'Nama harus berupa teks',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah digunakan',
                'password.min' => 'Password minimal 6 karakter',
                'password.confirmed' => 'Konfirmasi password tidak cocok',
            ]);

            if ($validator->fails()) {
                Log::warning('Profile update validation failed', [
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

            $user->save();

            Log::info('Profile updated successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Update profile gagal',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }
}
