<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $permissions = Permission::all();

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * Assign permission to user.
     */
    public function assignPermission(Request $request, $userId): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $user->givePermissionTo($request->permission);

            return response()->json([
                'message' => 'Permission assigned successfully',
                'data' => $user->load(['roles', 'permissions']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove permission from user.
     */
    public function removePermission(Request $request, $userId, $permissionName): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $user = User::findOrFail($userId);
            $user->revokePermissionTo($permissionName);

            return response()->json([
                'message' => 'Permission removed successfully',
                'data' => $user->load(['roles', 'permissions']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions of a user (direct + inherited from roles).
     */
    public function getUserPermissions(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'data' => [
                'all_permissions' => $user->getAllPermissions(),
                'direct_permissions' => $user->getDirectPermissions(),
                'role_permissions' => $user->getPermissionsViaRoles(),
            ],
        ]);
    }

    /**
     * Assign permission to role.
     */
    public function assignPermissionToRole(Request $request, $roleId): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findOrFail($roleId);
            $role->givePermissionTo($request->permission);

            return response()->json([
                'message' => 'Permission assigned to role successfully',
                'data' => $role->load('permissions'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign permission to role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove permission from role.
     */
    public function removePermissionFromRole(Request $request, $roleId, $permissionName): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $role = Role::findOrFail($roleId);
            $role->revokePermissionTo($permissionName);

            return response()->json([
                'message' => 'Permission removed from role successfully',
                'data' => $role->load('permissions'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove permission from role',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
