<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ValidateUserServiceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil token dari header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided. Please login first.',
                'error' => 'Unauthorized'
            ], 401);
        }

        try {
            // Cache key berdasarkan token untuk mengurangi request ke user-service
            $cacheKey = 'user_token_' . md5($token);

            // Cek cache dulu (optional, untuk performa)
            $userData = Cache::remember($cacheKey, 300, function () use ($token) {
                // Validasi token ke user-service
                $response = Http::timeout(5)
                    ->withToken($token)
                    ->acceptJson()
                    ->get(config('services.user_service.url') . '/api/user');

                if ($response->failed()) {
                    return null;
                }

                return $response->json();
            });

            if (!$userData) {
                // Hapus cache jika token invalid
                Cache::forget($cacheKey);

                Log::warning('Invalid token attempt', [
                    'ip' => $request->ip(),
                    'token_preview' => substr($token, 0, 10) . '...'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token. Please login again.',
                    'error' => 'Unauthorized'
                ], 401);
            }

            // Attach user data ke request untuk digunakan di controller
            $request->merge(['authenticated_user' => $userData]);
            $request->attributes->set('user_id', $userData['id'] ?? null);
            $request->attributes->set('user_email', $userData['email'] ?? null);
            $request->attributes->set('user_name', $userData['name'] ?? null);

            // Log successful authentication (optional)
            Log::info('User authenticated via user-service', [
                'user_id' => $userData['id'] ?? null,
                'email' => $userData['email'] ?? null,
                'endpoint' => $request->path()
            ]);

            return $next($request);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User service connection failed', [
                'error' => $e->getMessage(),
                'user_service_url' => config('services.user_service.url')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication service is currently unavailable. Please try again later.',
                'error' => 'Service Unavailable'
            ], 503);

        } catch (\Exception $e) {
            Log::error('Authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during authentication.',
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
