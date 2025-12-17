<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or get Correlation ID
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        // Store in request attributes
        $request->attributes->set('correlation_id', $correlationId);

        // Set log context
        Log::withContext(['correlation_id' => $correlationId]);

        // Process request
        $response = $next($request);

        // Add to response headers
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
