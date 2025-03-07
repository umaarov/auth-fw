<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:admin']);
    }

    public function listRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return response()->json(['roles' => $roles]);
    }

    public function createRole(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json(['message' => 'Role created successfully', 'role' => $role]);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|unique:roles,name,'.$role->id
        ]);

        if ($request->has('name')) {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json(['message' => 'Role updated successfully', 'role' => $role->load('permissions')]);
    }

    public function deleteRole(Role $role): JsonResponse
    {
        if ($role->name === 'admin' || $role->name === 'user') {
            return response()->json(['error' => 'Cannot delete system roles'], 422);
        }

        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function listPermissions(): JsonResponse
    {
        $permissions = Permission::all();
        return response()->json(['permissions' => $permissions]);
    }

    public function createPermission(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name'
        ]);

        $permission = Permission::create(['name' => $request->name]);
        return response()->json(['message' => 'Permission created successfully', 'permission' => $permission]);
    }

    public function updatePermission(Request $request, Permission $permission): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,'.$permission->id
        ]);

        $permission->name = $request->name;
        $permission->save();

        return response()->json(['message' => 'Permission updated successfully', 'permission' => $permission]);
    }

    public function deletePermission(Permission $permission): JsonResponse
    {
        $permission->delete();
        return response()->json(['message' => 'Permission deleted successfully']);
    }

    public function assignRoleToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncRoles($request->roles);

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function assignDirectPermissionsToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Direct permissions assigned successfully',
            'user' => $user->load('permissions')
        ]);
    }
}
