<?php

namespace App\Console\Commands;

use App\Models\PropertyMedia;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrateMediaToCloudinaryCommand extends Command
{
    protected $signature = 'media:migrate-cloudinary {--delete-local=1 : Automatically delete local files once uploaded}';

    protected $description = 'Migrate all local property media files to Cloudinary (auto-converted to WebP), update database records, and remove local copies';

    public function handle(): int
    {
        $this->info('Starting Cloudinary media migration...');

        if (!CloudinaryService::isConfigured()) {
            $this->error('Cloudinary is not configured! Please check adm_cloudinary_* settings in the database.');
            return Command::FAILURE;
        }

        $allMedia = PropertyMedia::all();
        $this->info("Found {$allMedia->count()} media records in database.");

        $migratedCount = 0;
        $failedCount   = 0;
        $skippedCount  = 0;
        $freedBytes    = 0;

        foreach ($allMedia as $media) {
            $path = $media->file_path;

            // Check if already a remote Cloudinary URL
            if (Str::startsWith($path, 'https://res.cloudinary.com/')) {
                $this->line("  [SKIPPED] Media #{$media->id} is already hosted on Cloudinary.");
                $skippedCount++;
                continue;
            }

            // Skip YouTube/video embeds
            if ($media->media_type === 'video_embed' || Str::contains($path, ['youtube.com', 'youtu.be', 'facebook.com', 'vimeo.com'])) {
                $this->line("  [SKIPPED] Media #{$media->id} is an external video embed link.");
                $skippedCount++;
                continue;
            }

            // Local file resolution
            $localFullPath = public_path($path);
            if (!file_exists($localFullPath)) {
                $this->warn("  [NOT FOUND] Local file does not exist: {$localFullPath}");
                $failedCount++;
                continue;
            }

            $size = filesize($localFullPath);
            $this->comment("  [UPLOADING] Media #{$media->id} ({$path}) [" . round($size / 1024, 1) . " KB] -> Cloudinary...");

            $cloudUrl = CloudinaryService::upload($localFullPath);

            if ($cloudUrl) {
                $oldPath = $media->file_path;
                $media->file_path = $cloudUrl;
                $media->save();

                $this->info("    ✓ Uploaded to: {$cloudUrl}");

                // Delete local file if it is in uploads directory
                if ($this->option('delete-local') && Str::contains($localFullPath, 'public' . DIRECTORY_SEPARATOR . 'uploads')) {
                    @unlink($localFullPath);
                    $freedBytes += $size;
                    $this->info("    🗑️ Deleted local file: {$oldPath}");
                }

                $migratedCount++;
            } else {
                $this->error("    ✕ Failed to upload {$path} to Cloudinary!");
                $failedCount++;
            }
        }

        // Clean up any stray orphaned files in public/uploads/properties
        $propertiesUploadDir = public_path('uploads/properties');
        if (File::isDirectory($propertiesUploadDir)) {
            $files = File::files($propertiesUploadDir);
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $filename = $file->getFilename();
                $size = $file->getSize();

                // Check if any media still uses this filename
                $inUse = PropertyMedia::where('file_path', 'like', "%{$filename}%")->exists();
                if (!$inUse) {
                    $this->comment("  [ORPHANED] Found unlinked file: {$filename}. Uploading to Cloudinary...");
                    $cloudUrl = CloudinaryService::upload($filePath);
                    if ($cloudUrl) {
                        // Create unassigned PropertyMedia record
                        PropertyMedia::create([
                            'property_id' => null,
                            'file_path'   => $cloudUrl,
                            'media_type'  => 'photo',
                            'is_cover'    => false,
                            'caption'     => 'Imported from local upload',
                        ]);
                    }
                    @unlink($filePath);
                    $freedBytes += $size;
                    $this->info("  🗑️ Deleted orphaned local file: {$filename}");
                }
            }
        }

        $this->newLine();
        $this->info("=== Migration Summary ===");
        $this->info("Successfully Migrated: {$migratedCount}");
        $this->info("Already on Cloudinary: {$skippedCount}");
        $this->info("Failed:                {$failedCount}");
        $this->info("Local Disk Space Freed: " . round($freedBytes / 1024, 1) . " KB");

        return Command::SUCCESS;
    }
}
