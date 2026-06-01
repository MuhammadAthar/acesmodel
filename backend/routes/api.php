<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\GarmentController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\AiModelController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ModelPersonaController;
use App\Http\Controllers\Admin\AiProviderConfigController as AdminAiProviderConfigController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ModelController as AdminModelController;
use App\Http\Controllers\Admin\ModelPersonaController as AdminModelPersonaController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Public model personas for landing page (active, with avatar, include poses for hover/detail)
Route::get('/public/model-personas', function () {
    $personas = \App\Models\ModelPersona::active()
        ->whereNotNull('avatar_url')
        ->with(['poses' => fn($q) => $q->orderBy('sort_order')->orderBy('id')])
        ->select('id','name','gender','age','nationality','ethnicity','best_for','avatar_url','sort_order')
        ->get();
    return response()->json($personas);
});

// Public model detail page — full info + all poses
Route::get('/public/model-personas/{id}', function ($id) {
    $persona = \App\Models\ModelPersona::with('poses')
        ->where('is_active', true)
        ->findOrFail($id);
    return response()->json($persona);
});

// Payment callbacks (public, verified by gateway signature)
Route::post('/payments/callback/{gateway}', [PaymentController::class, 'callback']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // User profile/settings
    Route::put('/user',            [UserController::class, 'update']);
    Route::put('/user/password',   [UserController::class, 'updatePassword']);
    Route::post('/user/avatar',    [UserController::class, 'uploadAvatar']);

    // Brands
    Route::apiResource('brands', BrandController::class);
    Route::post('/brands/{brand}/analyze-dna', [BrandController::class, 'analyzeDna']);

    // Garments
    Route::apiResource('garments', GarmentController::class)->except(['update']);
    Route::post('garments/{garment}/retry-analysis', [GarmentController::class, 'retryAnalysis']);

    // AI Models (personas)
    Route::apiResource('ai-models', AiModelController::class);

    // Campaigns
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/generate', [CampaignController::class, 'generate']);
    Route::get('/campaigns/{campaign}/assets',    [CampaignController::class, 'assets']);

    // Assets library
    Route::get('/assets',              [AssetController::class, 'index']);
    Route::get('/assets/{asset}',      [AssetController::class, 'show']);
    Route::delete('/assets/{asset}',            [AssetController::class, 'destroy']);
    Route::post('/assets/bulk-delete',          [AssetController::class, 'bulkDestroy']);
    Route::post('/assets/{asset}/regenerate',   [AssetController::class, 'regenerate']);

    // Payments
    Route::get('/plans',                               [PaymentController::class, 'plans']);
    Route::get('/subscription',                        [PaymentController::class, 'activeSubscription']);
    Route::get('/payments',                            [PaymentController::class, 'history']);
    Route::post('/payments/initiate',                  [PaymentController::class, 'initiate']);
    Route::post('/payments/bank-transfer',             [PaymentController::class, 'bankTransferSubmit']);

    // Model Personas (curated by admin, visible to all authenticated users)
    Route::get('/model-personas',             [ModelPersonaController::class, 'index']);
    Route::get('/model-personas/{modelPersona}', [ModelPersonaController::class, 'show']);
});

// ── Super Admin routes ─────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('admin')->group(function () {
    Route::get('/stats',                                   [AdminDashboard::class, 'stats']);

    // Users
    Route::get('/users',                                   [AdminUserController::class, 'index']);
    Route::get('/users/{user}',                            [AdminUserController::class, 'show']);
    Route::put('/users/{user}',                            [AdminUserController::class, 'update']);
    Route::put('/users/{user}/password',                   [AdminUserController::class, 'updatePassword']);
    Route::delete('/users/{user}',                         [AdminUserController::class, 'destroy']);
    Route::post('/users/{user}/grant-credits',             [AdminUserController::class, 'grantCredits']);

    // AI Models
    Route::get('/models',                                  [AdminModelController::class, 'index']);
    Route::get('/models/{aiModel}',                        [AdminModelController::class, 'show']);
    Route::put('/models/{aiModel}',                        [AdminModelController::class, 'update']);
    Route::delete('/models/{aiModel}',                     [AdminModelController::class, 'destroy']);

    // AI Provider Configs
    Route::get('/ai-provider-configs',                         [AdminAiProviderConfigController::class, 'index']);
    Route::post('/ai-provider-configs',                        [AdminAiProviderConfigController::class, 'upsert']);
    Route::post('/ai-provider-configs/test',                   [AdminAiProviderConfigController::class, 'test']);
    Route::delete('/ai-provider-configs/{aiProviderConfig}',   [AdminAiProviderConfigController::class, 'destroy']);

    // Model Personas (admin CRUD)
    Route::get('/model-personas',                                                      [AdminModelPersonaController::class, 'index']);
    Route::post('/model-personas',                                                     [AdminModelPersonaController::class, 'store']);
    Route::post('/model-personas/generate-avatar',                                     [AdminModelPersonaController::class, 'generateAvatar']);
    Route::put('/model-personas/{modelPersona}',                                       [AdminModelPersonaController::class, 'update']);
    Route::delete('/model-personas/{modelPersona}',                                    [AdminModelPersonaController::class, 'destroy']);
    // Poses
    Route::get('/model-personas/{modelPersona}/poses',                                 [AdminModelPersonaController::class, 'poses']);
    Route::post('/model-personas/{modelPersona}/poses',                                [AdminModelPersonaController::class, 'addPose']);
    Route::post('/model-personas/{modelPersona}/poses/generate',                       [AdminModelPersonaController::class, 'generatePose']);
    Route::delete('/model-personas/{modelPersona}/poses/{pose}',                       [AdminModelPersonaController::class, 'deletePose']);

    // Subscriptions
    Route::get('/subscriptions',                           [AdminSubscriptionController::class, 'index']);
    Route::put('/subscriptions/{subscription}',            [AdminSubscriptionController::class, 'update']);
});
