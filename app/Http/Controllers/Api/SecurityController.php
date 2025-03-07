<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\LoginHistory;
use Carbon\Carbon;

class SecurityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getLoginHistory(): JsonResponse
    {
        $history = auth()->user()->loginHistory()
            ->orderBy('login_at', 'desc')
            ->paginate(10);

        return response()->json($history);
    }

    public function getSecuritySettings(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'two_factor_enabled' => $user->two_factor_enabled,
            'active_sessions' => $user->activeSessions()->count(),
            'last_password_changed' => 'Not available',
            'account_locked' => $user->is_locked,
            'locked_until' => $user->locked_until,
        ]);
    }
}
