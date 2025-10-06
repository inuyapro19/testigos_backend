<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $roles = Role::with('permissions')->get();

        return response()->json([
            'data' => $roles,
        ]);
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            'data' => $role,
        ]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, $userId): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,victim,lawyer,investor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);

            // Remove all current roles
            $user->syncRoles([]);

            // Assign new role
            $user->assignRole($request->role);

            // Update role field in users table
            $user->update(['role' => $request->role]);

            return response()->json([
                'message' => 'Role assigned successfully',
                'data' => $user->load(['roles', 'permissions']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove role from user.
     */
    public function removeRole(Request $request, $userId, $roleName): JsonResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $user = User::findOrFail($userId);
            $user->removeRole($roleName);

            return response()->json([
                'message' => 'Role removed successfully',
                'data' => $user->load(['roles', 'permissions']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get roles of a user.
     */
    public function getUserRoles(Request $request, $userId): JsonResponse
    {
        $user = User::with('roles')->findOrFail($userId);

        return response()->json([
            'data' => $user->roles,
        ]);
    }
}
