<?php

use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocsController as AdminDocsController;
use App\Http\Controllers\Admin\DemandController as AdminDemandController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\RevenueController as AdminRevenueController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Admin\VisitController as AdminVisitController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerDashboardController;
use App\Http\Controllers\ChahidaController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\UploadController;
use App\Services\BuyerDemandMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// 0. WORDPRESS-LIKE WEB GUI INSTALLER
// ==========================================
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::post('/test-db', [InstallController::class, 'testDb'])->name('test-db');
    Route::post('/run', [InstallController::class, 'run'])->name('run');
    Route::get('/success', [InstallController::class, 'success'])->name('success');
});


// ==========================================
// 1. HOME & MARKETPLACE CATALOGS
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/land-search', [PropertyController::class, 'landSearch'])->name('properties.land');
Route::get('/flat-search', [PropertyController::class, 'flatSearch'])->name('properties.flat');
Route::get('/commercial-search', [PropertyController::class, 'commercialSearch'])->name('properties.commercial');
Route::get('/land-share-search', [PropertyController::class, 'shareSearch'])->name('properties.share');

// ==========================================
// 2. SINGLE PROPERTY DETAIL & LEADS
// ==========================================
Route::get('/property/{slug}', [PropertyController::class, 'show'])->name('property.show');
Route::post('/property/{slug}/book-visit', [InteractionController::class, 'bookVisit'])->name('property.book-visit');
Route::post('/property/{slug}/lead', [InteractionController::class, 'submitLead'])->name('property.lead');
Route::get('/property-slots/available', [InteractionController::class, 'getAvailableSlots'])->name('property.slots.available');
Route::post('/property/{slug}/track-whatsapp', [InteractionController::class, 'trackWhatsApp'])->name('property.track-whatsapp');
Route::post('/api/live-chat/track', [InteractionController::class, 'trackLiveChat'])->name('live-chat.track');
Route::post('/property/{slug}/review', [InteractionController::class, 'submitReview'])->name('property.review');

// ==========================================
// 3. SELL PROPERTY (CLIPBOARD / WEBP)
// ==========================================
Route::get('/sell-property', [PropertyController::class, 'create'])->name('property.create');
Route::post('/sell-property', [PropertyController::class, 'store'])->name('property.store');
Route::post('/upload/clipboard-image', [UploadController::class, 'uploadImage'])->name('upload.clipboard');

// ==========================================
// 4. BUYER DEMANDS / CHAHIDA & UNSUBSCRIBE
// ==========================================
Route::get('/chahida', [ChahidaController::class, 'index'])->name('chahida.index');
Route::post('/chahida', [ChahidaController::class, 'store'])->name('chahida.store');
Route::get('/chahida/{code}', [ChahidaController::class, 'show'])->name('chahida.show');
Route::get('/chahida/unsubscribe/{token}', [ChahidaController::class, 'unsubscribe'])->name('demand.unsubscribe');

// Telegram 1-Click Connect & Deep Linking
Route::get('/telegram/connect/{code}', [TelegramController::class, 'connect'])->name('telegram.connect');
Route::get('/chahida/{code}/telegram-status', [TelegramController::class, 'checkDemandStatus'])->name('telegram.status');
Route::match(['get', 'post'], '/api/telegram/webhook', [TelegramController::class, 'handleWebhook'])->name('telegram.webhook');
Route::post('/api/telegram/set-webhook', [TelegramController::class, 'setWebhook'])->name('telegram.set-webhook');

// ==========================================
// 5. COMPARE, MAP VIEW, SHORTLIST
// ==========================================
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::match(['get', 'post'], '/compare/data', [CompareController::class, 'data'])->name('compare.data');
Route::get('/map-view', [PortalController::class, 'mapView'])->name('map.view');
Route::get('/shortlist', [PortalController::class, 'shortlist'])->name('shortlist');
Route::match(['get', 'post'], '/shortlist/data', [PortalController::class, 'shortlistData'])->name('shortlist.data');

// ==========================================
// 6. AUTHENTICATION, REGISTRATION & GOOGLE OAUTH (SLUG: /appcenter)
// ==========================================
Route::get('/appcenter', [AuthController::class, 'showLogin'])->name('login');
Route::post('/appcenter', [AuthController::class, 'login']);
Route::get('/appcenter/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/appcenter/register', [AuthController::class, 'register']);
Route::post('/appcenter/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/appcenter/login-register', [AuthController::class, 'showLogin'])->name('login-register');

// Email Activation Link & OTP verification
Route::get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');
Route::post('/email/resend', [AuthController::class, 'resendVerification'])->name('auth.resend-verification');
Route::post('/email/verify-code', [AuthController::class, 'verifyEmailCode'])->name('auth.verify-code');

// Google / Gmail OAuth
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/google/mock', [AuthController::class, 'handleGoogleMockLogin'])->name('auth.google.mock');

// Magic Links
Route::get('/auth/magic/{token}', [AuthController::class, 'magicLogin'])->name('auth.magic');

// ==========================================
// 7. USER PORTALS & BUYER DASHBOARD
// ==========================================
Route::get('/agent-portal', fn () => redirect()->route('buyer.dashboard'))->name('agent.portal');

Route::middleware('auth')->prefix('buyer-dashboard')->group(function () {
    Route::get('/', [BuyerDashboardController::class, 'index'])->name('buyer.dashboard');
    Route::post('/profile', [BuyerDashboardController::class, 'updateProfile'])->name('buyer.dashboard.profile');
    Route::post('/favorite/toggle', [BuyerDashboardController::class, 'toggleFavorite'])->name('buyer.dashboard.fav.toggle');
    Route::post('/favorite/remove', [BuyerDashboardController::class, 'removeFavorite'])->name('buyer.dashboard.fav.remove');
    Route::post('/demand', [BuyerDashboardController::class, 'storeDemand'])->name('buyer.dashboard.demand.store');
    Route::post('/demand/toggle', [BuyerDashboardController::class, 'toggleDemandStatus'])->name('buyer.dashboard.demand.toggle');
    Route::post('/demand/delete', [BuyerDashboardController::class, 'deleteDemand'])->name('buyer.dashboard.demand.delete');
    Route::post('/demand/cron', [BuyerDashboardController::class, 'runDemandMatchCron'])->name('buyer.dashboard.demand.cron');
    Route::get('/property/{property}', [BuyerDashboardController::class, 'getPropertyForEdit'])->name('buyer.dashboard.property.get');
    Route::post('/property/{property}', [BuyerDashboardController::class, 'updateProperty'])->name('buyer.dashboard.property.update');
    Route::post('/property/{property}/media', [BuyerDashboardController::class, 'uploadPropertyMedia'])->name('buyer.dashboard.property.media.upload');
    Route::post('/property/{property}/media/{mediaId}/cover', [BuyerDashboardController::class, 'setCoverMedia'])->name('buyer.dashboard.property.media.cover');
    Route::delete('/property/{property}/media/{mediaId}', [BuyerDashboardController::class, 'deletePropertyMedia'])->name('buyer.dashboard.property.media.delete');
    Route::post('/property/{property}/media/{mediaId}/delete', [BuyerDashboardController::class, 'deletePropertyMedia'])->name('buyer.dashboard.property.media.delete.post');
});

// ==========================================
// 8. PUSH NOTIFICATION & SEO
// ==========================================
Route::post('/api/fcm/register-token', [InteractionController::class, 'registerFcmToken'])->name('fcm.register');
Route::get('/firebase-messaging-sw.js', function () {
    $path = public_path('firebase-messaging-sw.js');
    if (!file_exists($path)) {
        $path = public_path('assets/js/firebase-messaging-sw.js');
    }
    return response()->file($path, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Service-Worker-Allowed' => '/',
    ]);
});
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/property-sitemap.xml', [SitemapController::class, 'sitemap']);
Route::get('/{key}.txt', [SitemapController::class, 'indexnow'])->where('key', '[a-zA-Z0-9_-]{16,64}');

// ==========================================
// 9. CRON WEBHOOK (HOSTTIER CPANEL)
// ==========================================
Route::get('/api/cron/match-demands', function (Request $request) {
    $secret = env('CRON_SECRET', 'propertykoi_cron_secret_2026');
    if ($request->query('key') !== $secret) {
        abort(403, 'Unauthorized cron key.');
    }
    $force = $request->boolean('force', false);
    $result = BuyerDemandMatchService::runDailyDemandMatching($force);
    return response()->json([
        'status' => 'success',
        'result' => $result,
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/api/cron/sync-sheets', function (Request $request) {
    $secret = env('CRON_SECRET', 'propertykoi_cron_secret_2026');
    if ($request->query('key') !== $secret) {
        abort(403, 'Unauthorized cron key.');
    }
    $result = \App\Services\GoogleSheetSyncService::syncNow();
    return response()->json([
        'status' => ($result['success'] ?? false) ? 'success' : 'error',
        'result' => $result,
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ==========================================
// 10. ADMIN DASHBOARD & MANAGEMENT (PREFIX: backend)
// ==========================================
Route::prefix('backend')->name('admin.')->middleware('admin.access')->group(function () {
    // 1. Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // 2. Properties Master Catalog
    Route::get('/properties', [AdminPropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/create', [AdminPropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [AdminPropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}/edit', [AdminPropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{property}', [AdminPropertyController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{property}', [AdminPropertyController::class, 'destroy'])->name('properties.destroy');
    Route::post('/properties/bulk-delete', [AdminPropertyController::class, 'bulkDelete'])->name('properties.bulk-delete');
    Route::match(['get', 'post'], '/properties/{property}/status', [AdminPropertyController::class, 'updateStatus'])->name('properties.status');
    Route::post('/properties/auto-assign', [AdminPropertyController::class, 'autoAssignInserters'])->name('properties.auto-assign');
    Route::post('/properties/{property}/fb-post', [AdminPropertyController::class, 'postToFacebook'])->name('properties.fb-post');

    // 3. Media Manager (Cloudinary)
    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::post('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
    Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/media/bulk-delete', [AdminMediaController::class, 'bulkDestroy'])->name('media.bulk-delete');
    Route::post('/media/{media}/update', [AdminMediaController::class, 'update'])->name('media.update');
    Route::post('/media/sync-cloudinary', [AdminMediaController::class, 'syncCloudinary'])->name('media.sync-cloudinary');

    // 4. 4-Point Verifications
    Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::post('/verifications/{property}/checklist', [AdminVerificationController::class, 'updateChecklist'])->name('verifications.checklist');
    Route::post('/verifications/{property}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');

    // 4. Agents & Owners Directory
    Route::get('/agents', [AdminAgentController::class, 'index'])->name('agents.index');
    Route::post('/agents/{user}/profile', [AdminAgentController::class, 'updateProfile'])->name('agents.profile');
    Route::post('/agents/{user}/note', [AdminAgentController::class, 'addNote'])->name('agents.note');
    Route::post('/agents/{user}/magic-token', [AdminAgentController::class, 'regenerateMagicToken'])->name('agents.magic-token');

    // 5. CRM Leads & Pipeline
    Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::post('/leads/{lead}/stage', [AdminLeadController::class, 'updateStage'])->name('leads.stage');
    Route::post('/leads/{lead}/note', [AdminLeadController::class, 'addNote'])->name('leads.note');
    Route::get('/leads/{lead}/owner-wa', [AdminLeadController::class, 'getOwnerWhatsappUrl'])->name('leads.owner-wa');

    // 5.1 Buyer Demands (Chahida) & Submitter Management
    Route::get('/demands', [AdminDemandController::class, 'index'])->name('demands.index');
    Route::post('/demands/{demand}/status', [AdminDemandController::class, 'updateStatus'])->name('demands.status');
    Route::delete('/demands/{demand}', [AdminDemandController::class, 'destroy'])->name('demands.destroy');
    Route::post('/demands/{demand}/telegram-alert', [AdminDemandController::class, 'sendTelegramAlert'])->name('demands.telegram-alert');

    // 6. Site Visits & Slot Scheduler
    Route::get('/visits', [AdminVisitController::class, 'index'])->name('visits.index');
    Route::post('/visits/toggle-slot', [AdminVisitController::class, 'toggleSlot'])->name('visits.toggle-slot');
    Route::post('/visits/batch-slots', [AdminVisitController::class, 'batchUpdateSlots'])->name('visits.batch-slots');
    Route::post('/visits/save-slots', [AdminVisitController::class, 'saveSlots'])->name('visits.save-slots');
    Route::post('/visits/{booking}/status', [AdminVisitController::class, 'updateBookingStatus'])->name('visits.booking-status');

    // 7. Revenue & 0.5% Commission Engine
    Route::get('/revenue', [AdminRevenueController::class, 'index'])->name('revenue.index');
    Route::post('/revenue', [AdminRevenueController::class, 'store'])->name('revenue.store');
    Route::post('/revenue/{transaction}/fee-status', [AdminRevenueController::class, 'updateFeeStatus'])->name('revenue.fee-status');

    // 8. Documentation
    Route::get('/documentation', [AdminDocsController::class, 'index'])->name('documentation');

    // 9. Master Settings (9 Tabs)
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/sync-sheet', [AdminSettingsController::class, 'syncSheetNow'])->name('settings.sync-sheet');
    Route::post('/settings/sheet/preview', [AdminSettingsController::class, 'previewSheet'])->name('settings.sheet.preview');
    Route::post('/settings/sheet/sync', [AdminSettingsController::class, 'syncSheetNow'])->name('settings.sheet.sync');
    Route::post('/settings/sheet/link-whatsapp', [AdminSettingsController::class, 'linkParentlessProperties'])->name('settings.sheet.link-whatsapp');
    Route::post('/settings/test-watermark', [AdminSettingsController::class, 'testWatermark'])->name('settings.test-watermark');
    Route::post('/settings/test-cloudinary', [AdminSettingsController::class, 'testCloudinary'])->name('settings.test-cloudinary');
    Route::post('/settings/test-push', [AdminSettingsController::class, 'testPushNotification'])->name('settings.test-push');
    Route::post('/settings/test-telegram', [AdminSettingsController::class, 'testTelegram'])->name('settings.test-telegram');
    Route::post('/settings/test-email', [AdminSettingsController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/settings/trigger-demand-cron', [AdminSettingsController::class, 'triggerDemandCron'])->name('settings.trigger-demand-cron');
    Route::post('/settings/ping-indexnow', [AdminSettingsController::class, 'pingIndexNow'])->name('settings.ping-indexnow');
    Route::post('/settings/test-facebook', [AdminSettingsController::class, 'testFacebook'])->name('settings.test-facebook');
    Route::post('/settings/penalty', [AdminSettingsController::class, 'updateAgentPenalty'])->name('settings.penalty');

    // 10. Microsoft Clarity Session & Heatmaps View (1:1 with WP Plugin)
    Route::get('/clarity', [AdminSettingsController::class, 'clarityDashboard'])->name('clarity');
    Route::post('/clarity/project', [AdminSettingsController::class, 'saveClarityProject'])->name('clarity.save-project');

    // 11. Live WP Import (Application Password / Bridge)
    Route::post('/settings/wp-import/test', [\App\Http\Controllers\Admin\WpImportController::class, 'testConnection'])->name('settings.wp-import.test');
    Route::post('/settings/wp-import/start', [\App\Http\Controllers\Admin\WpImportController::class, 'startImport'])->name('settings.wp-import.start');
    Route::get('/settings/wp-import/download-bridge', [\App\Http\Controllers\Admin\WpImportController::class, 'downloadBridge'])->name('settings.wp-import.download-bridge');
});
