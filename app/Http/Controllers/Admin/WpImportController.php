<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpLiveImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WpImportController extends Controller
{
    public function __construct(
        protected WpLiveImportService $importService
    ) {}

    /**
     * Test connection to live WordPress site
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url'      => 'required|url',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->importService->testConnection(
            $validated['url'],
            $validated['username'],
            $validated['password']
        );

        return response()->json($result);
    }

    /**
     * Execute live import from WordPress
     */
    public function startImport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url'                   => 'required|url',
            'username'              => 'required|string',
            'password'              => 'required|string',
            'limit'                 => 'required|integer|min:1|max:500',
            'duplicate_action'      => 'required|in:skip,overwrite',
            'import_properties'     => 'nullable|boolean',
            'import_leads'          => 'nullable|boolean',
            'import_demands'        => 'nullable|boolean',
            'import_users'          => 'nullable|boolean',
            'import_reviews'        => 'nullable|boolean',
            'sync_cloudinary'       => 'nullable|boolean',
        ]);

        $result = $this->importService->runImport($validated);

        return response()->json($result);
    }

    /**
     * Download the standalone wp-sync-bridge.php file
     */
    public function downloadBridge(): BinaryFileResponse
    {
        $filePath = public_path('wp-sync-bridge.php');
        if (!file_exists($filePath)) {
            abort(404, 'Bridge file not found.');
        }

        return response()->download($filePath, 'wp-sync-bridge.php', [
            'Content-Type' => 'application/x-php',
        ]);
    }
}
