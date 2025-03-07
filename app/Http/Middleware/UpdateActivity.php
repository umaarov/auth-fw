<?php

namespace App\Http\Middleware;

use App\Models\LoginHistory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            LoginHistory::create([
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location' => null,
                'successful' => true,
                'login_at' => now(),
            ]);
        }

        return $next($request);
    }
}
