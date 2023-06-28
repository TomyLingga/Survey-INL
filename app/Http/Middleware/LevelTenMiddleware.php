<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LevelTenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authorizationHeader = $request->header('Authorization');
        $appId = '7';

            if (strpos($authorizationHeader, 'Bearer ') === 0) {
                $jwt = str_replace('Bearer ', '', $authorizationHeader);
            } else {
                return response()->json(['error' => 'Invalid Authorization header', 'code' => 401], 401);
            }

        if ($jwt) {
            try {

                $decoded = JWT::decode($jwt, new Key(env('JWT_SECRET2'), 'HS256'));

                $urlAkses = "http://36.92.181.10:4763/api/akses/mine/{$appId}/{$decoded->sub}";

                $getakses = Http::withHeaders([
                    'Authorization' => $authorizationHeader,
                ])->get($urlAkses);

                $akses = $getakses->json()['data'];

                if(!$akses){
                    return response()->json(['code' => 401,'error' => 'User has no Access'], 401);
                }

                if ($akses['level_akses'] >= 10 && Carbon::now()->timestamp < $decoded->exp) {
                    return $next($request);
                }else{
                    return response()->json(['code' => 401,'error' => 'Unauthorized'], 401);
                }

            } catch (\Exception $e) {
                // redirect ke login
                return response()->json(['code' => 401,'error' => 'Invalid or expired token'], 401);
            }

        }else{
            // Redirect to login or return an error response
            return response()->json(['code' => 401,'error' => 'Unauthorized'], 401);
        }

    }
}
