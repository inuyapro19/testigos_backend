<?php

namespace App\Http\Controllers;

use App\Models\InvestorProfile;
use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'rut' => 'required|string|unique:users',
            'birth_date' => 'nullable|date',
            'address' => 'required|string',
            'phone' => 'required|string',
            'role' => 'required|in:victim,lawyer,investor',

            // Lawyer specific fields
            'license_number' => 'required_if:role,lawyer|string|unique:lawyer_profiles',
            'law_firm' => 'nullable|string',
            'specializations' => 'required_if:role,lawyer|array',
            'years_experience' => 'required_if:role,lawyer|integer|min:0',
            'bio' => 'nullable|string',

            // Investor specific fields
            'investor_type' => 'required_if:role,investor|in:individual,institutional',
            'minimum_investment' => 'nullable|numeric|min:0',
            'maximum_investment' => 'nullable|numeric|min:0',
            'investment_preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rut' => $request->rut,
                'birth_date' => $request->birth_date,
                'address' => $request->address,
                'phone' => $request->phone,
                'role' => $request->role,
            ]);

            // Assign Spatie role
            $user->assignRole($request->role);

            // Create role-specific profiles
            if ($request->role === 'lawyer') {
                LawyerProfile::create([
                    'user_id' => $user->id,
                    'license_number' => $request->license_number,
                    'law_firm' => $request->law_firm,
                    'specializations' => $request->specializations,
                    'years_experience' => $request->years_experience,
                    'bio' => $request->bio,
                ]);
            } elseif ($request->role === 'investor') {
                InvestorProfile::create([
                    'user_id' => $user->id,
                    'investor_type' => $request->investor_type,
                    'minimum_investment' => $request->minimum_investment ?? 1000000,
                    'maximum_investment' => $request->maximum_investment,
                    'investment_preferences' => $request->investment_preferences ?? [],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Load profiles
            $user->load(['lawyerProfile', 'investorProfile']);

            // Get abilities before hiding relations
            $abilities = [
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'primary_role' => $user->getRoleNames()->first()
            ];

            // Hide roles and permissions relations from JSON output
            $user->makeHidden(['roles', 'permissions']);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
                'abilities' => $abilities
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)
                   ->where('is_active', true)
                   ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Load only profiles (not roles/permissions to avoid duplication)
        $user->load(['lawyerProfile', 'investorProfile']);

        // Get abilities before hiding relations
        $abilities = [
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'primary_role' => $user->getRoleNames()->first()
        ];

        // Hide roles and permissions relations from JSON output
        $user->makeHidden(['roles', 'permissions']);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'abilities' => $abilities
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['lawyerProfile', 'investorProfile']);

        // Get abilities before hiding relations
        $abilities = [
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'primary_role' => $user->getRoleNames()->first()
        ];

        // Hide roles and permissions relations from JSON output
        $user->makeHidden(['roles', 'permissions']);

        return response()->json([
            'user' => $user,
            'abilities' => $abilities
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['name', 'email', 'phone', 'address']);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $updateData['avatar'] = $avatarPath;
            }

            $user->update($updateData);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()->load(['lawyerProfile', 'investorProfile'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        $permissions = $request->user()->getAllPermissions()->pluck('name');

        return response()->json([
            'permissions' => $permissions
        ]);
    }

    /**
     * Get user roles.
     */
    public function roles(Request $request): JsonResponse
    {
        $roles = $request->user()->getRoleNames();

        return response()->json([
            'roles' => $roles
        ]);
    }

    /**
     * Get user abilities (roles + permissions).
     */
    public function abilities(Request $request): JsonResponse
    {
        $user = $request->user();
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');
        $primaryRole = $roles->first();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
            'primary_role' => $primaryRole
        ]);
    }
}
