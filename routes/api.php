<?php

use App\Http\Controllers\Api\GuideApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PropertyKoi API Routes (v1)
|--------------------------------------------------------------------------
|
| Secure API endpoints for external tools, AI publishing workflows,
| and automated content orchestration.
|
*/

Route::prefix('v1')->group(function () {

    // Public / Read-Only Knowledge Hub API Endpoints
    Route::get('/guides', [GuideApiController::class, 'index'])->name('api.v1.guides.index');
    Route::get('/guides/topics', [GuideApiController::class, 'topics'])->name('api.v1.guides.topics');
    Route::get('/guides/{identifier}', [GuideApiController::class, 'show'])->name('api.v1.guides.show');

    // Protected Publishing & Management Endpoints (API Key / Admin Token Required)
    Route::middleware('api.guide.auth')->group(function () {
        Route::post('/guides/check-uniqueness', [GuideApiController::class, 'checkUniqueness'])->name('api.v1.guides.check-uniqueness');
        Route::post('/guides', [GuideApiController::class, 'store'])->name('api.v1.guides.store');
        Route::match(['put', 'patch'], '/guides/{identifier}', [GuideApiController::class, 'update'])->name('api.v1.guides.update');
        Route::delete('/guides/{identifier}', [GuideApiController::class, 'destroy'])->name('api.v1.guides.destroy');
    });

});
