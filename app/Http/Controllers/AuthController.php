<?php

namespace App\Http\Controllers;

use App\Events\LoginAttempt;
use App\Events\UserRegistered;
use App\Jobs\SendVerificationEmail;
use App\Models\SessionToken;
use App\Models\User;
use App\Notifications\SuspiciousLogin;
use App\Services\TwoFactorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->middleware('auth:api')->except(['register', 'login', 'refreshToken', 'verifyEmail', 'forgotPassword', 'resetPassword', 'verify2FA']);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_token' => Str::random(64),
            'email_verified_at' => null,
            'is_active' => false,
        ]);

        dispatch(new SendVerificationEmail($user));
        event(new UserRegistered($user));

        return response()->json(['message' => 'User created successfully. Please verify your email.', 'user' => $user->only(['name', 'email', 'created_at'])], 201);
    }

    final function verifyEmail(Request $request): JsonResponse
    {
        $user = User::where('verification_token', $request->token)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid verification token'], 400);
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->is_active = true;
        $user->save();

        return response()->json(['message' => 'Email verified successfully']);
    }

    final function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->is_active) {
            return response()->json(['error' => 'User account is inactive or does not exist'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json(['error' => 'Email not verified'], 401);
        }

        event(new LoginAttempt($user, $request->ip(), $request->header('User-Agent')));

        if (!$token = JWTAuth::attempt($credentials)) {
            $user->increment('failed_login_attempts');

            if ($user->failed_login_attempts >= 5) {
                $user->is_locked = true;
                $user->locked_until = now()->addMinutes(30);
                $user->save();
                return response()->json(['error' => 'Account locked due to too many failed attempts'], 401);
            }

            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if ($user->is_locked && $user->locked_until > now()) {
            return response()->json(['error' => 'Account is locked. Try again later.'], 401);
        }

        $user->failed_login_attempts = 0;
        $user->last_login_at = now();
        $user->save();

        if ($user->two_factor_enabled) {
            $code = $this->twoFactorService->generateCode($user);
            return response()->json([
                'message' => '2FA code has been sent. Please verify.',
                'requires_2fa' => true,
                'temp_token' => encrypt(['user_id' => $user->id, 'exp' => now()->addMinutes(10)->timestamp])
            ]);
        }

        $this->checkSuspiciousLogin($user, $request);
        return $this->respondWithToken($token, $user, $request);
    }

    public function verify2FA(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'temp_token' => 'required',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = decrypt($request->temp_token);
            if ($data['exp'] < now()->timestamp) {
                return response()->json(['error' => '2FA verification expired'], 401);
            }

            $user = User::findOrFail($data['user_id']);
            if (!$this->twoFactorService->verifyCode($user, $request->code)) {
                return response()->json(['error' => 'Invalid 2FA code'], 401);
            }

            $token = JWTAuth::fromUser($user);
            $this->checkSuspiciousLogin($user, $request);
            return $this->respondWithToken($token, $user, $request);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }

    public function toggleTwoFactor(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->two_factor_enabled = !$user->two_factor_enabled;
        $user->save();

        if ($user->two_factor_enabled) {
            $secret = $this->twoFactorService->generateSecret($user);
            return response()->json([
                'message' => '2FA has been enabled',
                'secret' => $secret,
                'qr_code' => $this->twoFactorService->getQrCodeUrl($user, $secret)
            ]);
        }

        return response()->json(['message' => '2FA has been disabled']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'If that email exists in our system, a password reset link has been sent.']);
        }

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Send email with $token

        return response()->json(['message' => 'If that email exists in our system, a password reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();
        return response()->json([
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->getRoleNames(),
            'active_sessions' => $user->sessions()->count(),
        ]);
    }

    public function refreshToken(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token' => $newToken]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }

    public function logout(): JsonResponse
    {
        $token = JWTAuth::getToken();
        $sessionToken = SessionToken::where('token', (string)$token)->first();

        if ($sessionToken) {
            $sessionToken->delete();
        }

        JWTAuth::invalidate($token);
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function logoutAll(): JsonResponse
    {
        auth()->user()->sessions()->delete();
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out from all devices']);
    }

    public function activeSessions(): JsonResponse
    {
        $sessions = auth()->user()->sessions()->orderBy('created_at', 'desc')->get();
        return response()->json(['sessions' => $sessions]);
    }

    public function terminateSession(Request $request): JsonResponse
    {
        $session = SessionToken::findOrFail($request->session_id);

        if ($session->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $session->delete();
        return response()->json(['message' => 'Session terminated successfully']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string',
            'password' => 'sometimes|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 422);
            }
            $user->password = Hash::make($request->password);
        }

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            $user->verification_token = Str::random(64);
            dispatch(new SendVerificationEmail($user));
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }

    final function respondWithToken($token, $user, Request $request): JsonResponse
    {
        $session = SessionToken::create([
            'user_id' => $user->id,
            'device' => $request->header('User-Agent'),
            'ip' => $request->ip(),
            'token' => $token,
            'last_activity' => now(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->getRoleNames(),
        ]);
    }

    protected function checkSuspiciousLogin(User $user, Request $request): void
    {
        $lastLogin = $user->sessions()->orderBy('created_at', 'desc')->first();

        if (!$lastLogin) {
            return;
        }

        $suspicious = false;
        $reasons = [];

        if ($lastLogin->ip != $request->ip()) {
            $suspicious = true;
            $reasons[] = 'IP address change';
        }

        $lastUserAgent = $lastLogin->device;
        $currentUserAgent = $request->header('User-Agent');

        if ($lastUserAgent !== $currentUserAgent) {
            $suspicious = true;
            $reasons[] = 'Browser/device change';
        }

        $lastLoginTime = new Carbon($lastLogin->created_at);
        $timeDifference = $lastLoginTime->diffInHours(now());

        if ($suspicious) {
            $user->notify(new SuspiciousLogin($request->ip(), $reasons, now()));
        }
    }
}
