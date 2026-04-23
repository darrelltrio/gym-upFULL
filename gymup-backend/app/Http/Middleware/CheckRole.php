<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Mengapa ini penting? Middleware ini akan mencegat request sebelum sampai ke Controller.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Cek apakah user sudah login (seharusnya sudah dihandle auth:sanctum)
        // 2. Cek apakah role user sesuai dengan role yang diminta di route
        if ($request->user() && $request->user()->role !== $role) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required role (' . $role . ') to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}