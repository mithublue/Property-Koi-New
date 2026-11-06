<?php

use App\Http\Controllers\Api\AgentApiController;
use App\Http\Controllers\Api\GuideApiController;
use App\Http\Controllers\Api\PropertyApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PropertyKoi API Routes (v1)
|--------------------------------------------------------------------------
|
| Secure API endpoints for external tools, AI publishing workflows,
| Google Sheets sync, Agent directory, and automated property orchestration.
|
*/

Route::prefix('v1')->group(function () {

    // Public / Read-Only Knowledge Hub API Endpoints
    Route::get('/guides', [GuideApiController::class, 'index'])->name('api.v1.guides.index');
    Route::get('/guides/topics', [GuideApiController::class, 'topics'])->name('api.v1.guides.topics');
    Route::get('/guides/{identifier}', [GuideApiController::class, 'show'])->name('api.v1.guides.show');

    // Protected API Endpoints (API Key / Admin Token Required)
    Route::middleware('api.auth')->group(function () {
        // 1. Guides Management
        Route::post('/guides/check-uniqueness', [GuideApiController::class, 'checkUniqueness'])->name('api.v1.guides.check-uniqueness');
        Route::post('/guides', [GuideApiController::class, 'store'])->name('api.v1.guides.store');
        Route::match(['put', 'patch'], '/guides/{identifier}', [GuideApiController::class, 'update'])->name('api.v1.guides.update');
        Route::delete('/guides/{identifier}', [GuideApiController::class, 'destroy'])->name('api.v1.guides.destroy');

        // 2. Properties Management (Google Sheets & Gemini AI Ingestion)
        Route::get('/properties', [PropertyApiController::class, 'index'])->name('api.v1.properties.index');
        Route::post('/properties', [PropertyApiController::class, 'store'])->name('api.v1.properties.store');
        Route::post('/properties/bulk', [PropertyApiController::class, 'bulk'])->name('api.v1.properties.bulk');
        Route::match(['put', 'patch', 'post'], '/properties/{identifier}', [PropertyApiController::class, 'update'])->name('api.v1.properties.update');

        // 3. Agents & Owners Directory Management
        Route::get('/agents', [AgentApiController::class, 'index'])->name('api.v1.agents.index');
        Route::post('/agents', [AgentApiController::class, 'store'])->name('api.v1.agents.store');
        Route::get('/agents/{id}', [AgentApiController::class, 'show'])->name('api.v1.agents.show');
    });

});
