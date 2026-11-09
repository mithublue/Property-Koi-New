<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuidePdfExportController extends Controller
{
    /**
     * Display the Guide PDF Exporter Configuration page.
     */
    public function index(Request $request): View
    {
        $query = Guide::query();

        // 1. Date Range Filtering
        if ($request->filled('published_from')) {
            $query->whereDate('created_at', '>=', $request->published_from);
        }
        if ($request->filled('published_to')) {
            $query->whereDate('created_at', '<=', $request->published_to);
        }

        // 2. Category Filtering
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // 3. Status Filtering
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        // 4. Search query
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        // Order by
        $sortBy = $request->get('sort', 'latest');
        if ($sortBy === 'oldest') {
            $query->oldest('created_at');
        } elseif ($sortBy === 'title') {
            $query->orderBy('title', 'asc');
        } else {
            $query->latest('created_at');
        }

        $guides = $query->get();
        $categories = Guide::getCategoryOptions();

        // Pre-selected IDs from bulk actions or query string
        $preselectedIds = [];
        if ($request->has('ids')) {
            $rawIds = is_array($request->ids) ? $request->ids : explode(',', (string) $request->ids);
            $preselectedIds = array_map('intval', array_filter($rawIds));
        }

        $totalAllCount = Guide::count();
        $publishedCount = Guide::where('is_published', true)->count();

        return view('admin.guides.export', compact(
            'guides',
            'categories',
            'preselectedIds',
            'totalAllCount',
            'publishedCount'
        ));
    }

    /**
     * Generate and display the printable PDF Document / Booklet.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'export_scope' => 'nullable|string|in:all,selected,filtered',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'published_from' => 'nullable|date',
            'published_to' => 'nullable|date',
            'include_cover' => 'nullable|boolean',
            'include_toc' => 'nullable|boolean',
            'include_faqs' => 'nullable|boolean',
            'include_images' => 'nullable|boolean',
            'order' => 'nullable|string|in:asc,desc,title',
        ]);

        $query = Guide::query();

        // Determine guides selection
        $exportScope = $request->input('export_scope', 'selected');

        if ($exportScope === 'selected' && !empty($request->ids)) {
            $query->whereIn('id', $request->ids);
        } else {
            // Apply date filters if provided
            if ($request->filled('published_from')) {
                $query->whereDate('created_at', '>=', $request->published_from);
            }
            if ($request->filled('published_to')) {
                $query->whereDate('created_at', '<=', $request->published_to);
            }
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
            if ($request->filled('status')) {
                if ($request->status === 'published') {
                    $query->where('is_published', true);
                } elseif ($request->status === 'draft') {
                    $query->where('is_published', false);
                }
            }
        }

        // Sorting
        $order = $request->input('order', 'asc');
        if ($order === 'desc') {
            $query->latest('created_at');
        } elseif ($order === 'title') {
            $query->orderBy('title', 'asc');
        } else {
            $query->oldest('created_at');
        }

        $guides = $query->get();

        if ($guides->isEmpty()) {
            return redirect()->route('admin.guides.export')
                ->with('error', 'এক্সপোর্ট করার জন্য কোনো গাইড পাওয়া যায়নি। দয়া করে তারিখ বা গাইড সিলেকশন পরীক্ষা করুন।');
        }

        $siteName = Setting::get('adm_site_name', config('app.name', 'PropertyKoi'));
        $siteLogo = Setting::get('adm_site_logo');
        $siteLogoUrl = $siteLogo ? (str_starts_with($siteLogo, 'http') ? $siteLogo : asset($siteLogo)) : null;

        $options = [
            'include_cover' => $request->boolean('include_cover', true),
            'include_toc' => $request->boolean('include_toc', true),
            'include_faqs' => $request->boolean('include_faqs', true),
            'include_images' => $request->boolean('include_images', true),
            'auto_print' => $request->boolean('auto_print', true),
            'doc_title' => $request->input('doc_title') ?: 'প্রপার্টি নলেজ হাব ও আইনি গাইডলাইন সংকলন',
            'subtitle' => $request->input('subtitle') ?: 'বাংলাদেশে জমি, ফ্ল্যাট ও আবাসন খাতের হালনাগাদ সরকারি নিয়ম, দলিল যাচাই ও ইনভেস্টমেন্ট চেকলিস্ট',
        ];

        return view('admin.guides.pdf-book', compact('guides', 'siteName', 'siteLogoUrl', 'options'));
    }
}
