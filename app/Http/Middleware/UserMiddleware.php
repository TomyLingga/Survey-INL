<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('sanctum')->user();

        if ($user) {
            return $next($request);
        }

        return response()->json(['code' => 401,'error' => 'Unauthorized'], 401);
    }
}
