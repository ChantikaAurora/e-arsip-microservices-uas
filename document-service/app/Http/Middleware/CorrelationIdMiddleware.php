<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Cek apakah sudah ada ID dari pengirim? Kalau belum, bikin baru (UUID).
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        // 2. Simpan ID ini biar bisa dipakai di controller
        $request->attributes->set('correlation_id', $correlationId);

        // 3. Catat di Log server (biar kelihatan di terminal)
        // Note: Log::withContext butuh Laravel 8.49+, kalau error nanti kita sesuaikan
        Log::withContext(['correlation_id' => $correlationId]);

        // 4. Lanjutkan proses ke Controller (Login/Register/dll)
        $response = $next($request);

        // 5. Tempelkan ID ini di balasan (Response) biar pengirim tahu
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
