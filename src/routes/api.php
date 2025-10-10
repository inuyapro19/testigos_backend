<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth routes
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\AuthController::class, 'me']);
        Route::put('/profile', [\App\Http\Controllers\AuthController::class, 'updateProfile']);

        // Permissions & Roles endpoints
        Route::get('/auth/permissions', [\App\Http\Controllers\AuthController::class, 'permissions']);
        Route::get('/auth/roles', [\App\Http\Controllers\AuthController::class, 'roles']);
        Route::get('/auth/abilities', [\App\Http\Controllers\AuthController::class, 'abilities']);

        // Case routes
        Route::apiResource('cases', App\Http\Controllers\Api\V1\CaseController::class);
        Route::get('/cases/pending/review', [App\Http\Controllers\Api\V1\CaseController::class, 'pendingReview']);
        Route::get('/cases/published/list', [App\Http\Controllers\Api\V1\CaseController::class, 'published']);
        Route::post('/cases/{case}/documents', [App\Http\Controllers\Api\V1\CaseController::class, 'addDocument']);
        Route::get('/cases/{case}/documents/{document}', [App\Http\Controllers\Api\V1\CaseController::class, 'downloadDocument']);
        Route::post('/cases/{case}/assign-lawyer', [App\Http\Controllers\Api\V1\CaseController::class, 'assignLawyer']);
        Route::post('/cases/{case}/evaluate', [App\Http\Controllers\Api\V1\CaseController::class, 'evaluate']);
        Route::post('/cases/{case}/publish', [App\Http\Controllers\Api\V1\CaseController::class, 'publish']);
        Route::post('/cases/{case}/start', [App\Http\Controllers\Api\V1\CaseController::class, 'start']);
        Route::post('/cases/{case}/close', [App\Http\Controllers\Api\V1\CaseController::class, 'close']);
        Route::post('/cases/{case}/distribute-returns', [App\Http\Controllers\Api\V1\CaseController::class, 'distributeReturns']);

        // Investment routes
        Route::apiResource('investments', App\Http\Controllers\Api\V1\InvestmentController::class);
        Route::get('/investment/statistics', [App\Http\Controllers\Api\V1\InvestmentController::class, 'statistics']);
        Route::get('/investment/opportunities', [App\Http\Controllers\Api\V1\InvestmentController::class, 'opportunities']);

        // Transaction routes
        Route::get('/transactions', [App\Http\Controllers\Api\V1\TransactionController::class, 'index']);
        Route::get('/transactions/{id}', [App\Http\Controllers\Api\V1\TransactionController::class, 'show']);
        Route::get('/transactions/case/{caseId}', [App\Http\Controllers\Api\V1\TransactionController::class, 'caseTransactions']);
        Route::get('/transactions/statistics/all', [App\Http\Controllers\Api\V1\TransactionController::class, 'statistics']);

        // Withdrawal routes
        Route::get('/withdrawals', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'index']);
        Route::post('/withdrawals', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'store']);
        Route::get('/withdrawals/{id}', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'show']);
        Route::post('/withdrawals/{id}/approve', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'approve']);
        Route::post('/withdrawals/{id}/reject', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'reject']);
        Route::post('/withdrawals/{id}/process', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'process']);
        Route::post('/withdrawals/{id}/complete', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'complete']);
        Route::delete('/withdrawals/{id}', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'destroy']);
        Route::get('/withdrawals/balance/available', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'availableBalance']);
        Route::get('/withdrawals/statistics/all', [App\Http\Controllers\Api\V1\WithdrawalController::class, 'statistics']);

        // Notification routes
      /*  Route::get('/notifications', [App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::put('/notifications/{notification}/read', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);*/

        // Roles and Permissions routes
        Route::get('/roles', [App\Http\Controllers\Api\V1\RoleController::class, 'index']);
        Route::get('/roles/{id}', [App\Http\Controllers\Api\V1\RoleController::class, 'show']);
        Route::post('/users/{userId}/roles', [App\Http\Controllers\Api\V1\RoleController::class, 'assignRole']);
        Route::delete('/users/{userId}/roles/{role}', [App\Http\Controllers\Api\V1\RoleController::class, 'removeRole']);
        Route::get('/users/{userId}/roles', [App\Http\Controllers\Api\V1\RoleController::class, 'getUserRoles']);

        Route::get('/permissions', [App\Http\Controllers\Api\V1\PermissionController::class, 'index']);
        Route::post('/users/{userId}/permissions', [App\Http\Controllers\Api\V1\PermissionController::class, 'assignPermission']);
        Route::delete('/users/{userId}/permissions/{permission}', [App\Http\Controllers\Api\V1\PermissionController::class, 'removePermission']);
        Route::get('/users/{userId}/permissions', [App\Http\Controllers\Api\V1\PermissionController::class, 'getUserPermissions']);
        Route::post('/roles/{roleId}/permissions', [App\Http\Controllers\Api\V1\PermissionController::class, 'assignPermissionToRole']);
        Route::delete('/roles/{roleId}/permissions/{permission}', [App\Http\Controllers\Api\V1\PermissionController::class, 'removePermissionFromRole']);

        // Admin routes
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard']);
            Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users']);
            Route::get('/cases', [\App\Http\Controllers\AdminController::class, 'cases']);
            Route::get('/investments', [\App\Http\Controllers\AdminController::class, 'investments']);
            Route::get('/analytics', [\App\Http\Controllers\AdminController::class, 'analytics']);
            Route::put('/users/{user}/status', [\App\Http\Controllers\AdminController::class, 'updateUserStatus']);
            Route::put('/lawyers/{lawyer}/verify', [\App\Http\Controllers\AdminController::class, 'verifyLawyer']);
            Route::put('/investors/{investor}/accredit', [\App\Http\Controllers\AdminController::class, 'accreditInvestor']);
        });
    });
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('testigo.platform.version'),
    ]);
});

