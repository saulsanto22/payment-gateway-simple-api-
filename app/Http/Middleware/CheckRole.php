<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Roles yang diizinkan (admin, customer, merchant)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Cek apakah user sudah login
        if (! $request->user()) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        // Cek apakah user punya salah satu role yang diizinkan
        $userRole = $request->user()->role->value;

        if (! in_array($userRole, $roles)) {
            return ApiResponse::error(
                'Forbidden. You do not have permission to access this resource.',
                403
            );
        }

        return $next($request);
    }
}
