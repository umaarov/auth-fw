<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SessionToken;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check()) {
            try {
                $token = JWTAuth::getToken();
                if ($token) {
                    SessionToken::where('token', (string)$token)
                        ->update(['last_activity' => now()]);
                }
            } catch (\Exception $e) {
                // fail
            }
        }

        return $response;
    }
}
