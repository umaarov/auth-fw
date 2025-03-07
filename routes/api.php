<?php

use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('verify-2fa', [AuthController::class, 'verify2FA']);
Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('refresh-token', [AuthController::class, 'refreshToken']);

Route::middleware(['auth:api', 'update.activity'])->group(function () {
    // User profile and sessions
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('logout-all', [AuthController::class, 'logoutAll']);
    Route::get('active-sessions', [AuthController::class, 'activeSessions']);
    Route::post('terminate-session', [AuthController::class, 'terminateSession']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);
    Route::post('toggle-2fa', [AuthController::class, 'toggleTwoFactor']);

    // Security routes
    Route::get('security/login-history', [SecurityController::class, 'getLoginHistory']);
    Route::get('security/settings', [SecurityController::class, 'getSecuritySettings']);

    // Admin routes for role/permission management
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Roles
        Route::get('roles', [RolePermissionController::class, 'listRoles']);
        Route::post('roles', [RolePermissionController::class, 'createRole']);
        Route::put('roles/{role}', [RolePermissionController::class, 'updateRole']);
        Route::delete('roles/{role}', [RolePermissionController::class, 'deleteRole']);

        // Permissions
        Route::get('permissions', [RolePermissionController::class, 'listPermissions']);
        Route::post('permissions', [RolePermissionController::class, 'createPermission']);
        Route::put('permissions/{permission}', [RolePermissionController::class, 'updatePermission']);
        Route::delete('permissions/{permission}', [RolePermissionController::class, 'deletePermission']);

        // User role/permission assignment
        Route::post('users/assign-roles', [RolePermissionController::class, 'assignRoleToUser']);
        Route::post('users/assign-permissions', [RolePermissionController::class, 'assignDirectPermissionsToUser']);
    });
});
