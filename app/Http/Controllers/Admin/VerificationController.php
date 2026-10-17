<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyVerification;
use App\Models\Setting;
use App\Services\FacebookAutoPostService;
use App\Services\SeoService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');
        $status = $request->query('status', 'pending');

        $query = Property::with(['verification', 'media', 'creator', 'directSources']);

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($status === 'pending') {
            $query->whereIn('status', ['pending', 'draft']);
        } elseif ($status === 'verified') {
            $query->whereIn('status', ['verified', 'live']);
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }

        $properties = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'pending'  => Property::whereIn('status', ['pending', 'draft'])->count(),
            'verified' => Property::whereIn('status', ['verified', 'live'])->count(),
            'rejected' => Property::where('status', 'rejected')->count(),
            'all'      => Property::count(),
        ];

        return view('admin.verifications', compact('properties', 'stats', 'type', 'status'));
    }

    public function updateChecklist(Request $request, Property $property)
    {
        $verification = $property->verification ?: new PropertyVerification(['property_id' => $property->id]);

        $c1 = $request->boolean('checklist_owner_identity');
        $c2 = $request->boolean('checklist_location');
        $c3 = $request->boolean('checklist_basic_info');
        $c4 = $request->boolean('checklist_documents');

        $verification->checklist_owner_identity = $c1;
        $verification->checklist_location       = $c2;
        $verification->checklist_basic_info     = $c3;
        $verification->checklist_documents      = $c4;
        $verification->verification_notes       = $request->input('verification_notes');

        $allChecked = ($c1 && $c2 && $c3 && $c4);

        if ($allChecked) {
            $verification->verified_at = now();
            $property->update(['status' => 'live']);

            // Automatic IndexNow ping
            SeoService::pingIndexNow($property);

            // Auto FB post if enabled
            if (Setting::isTrue('adm_fb_autopost_enabled') || Setting::isTrue('fb_autopost_enabled')) {
                FacebookAutoPostService::postProperty($property);
            }
        } else {
            if ($property->status === 'live') {
                $property->update(['status' => 'pending']);
            }
        }

        $verification->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'all_verified' => $allChecked,
                'status' => $property->status,
                'message' => $allChecked ? 'সবগুলো চেকলিস্ট সম্পন্ন! প্রপার্টি এখন লাইভ।' : 'চেকলিস্ট আপডেট করা হয়েছে।'
            ]);
        }

        return back()->with('success', $allChecked ? "প্রপার্টি #{$property->property_code} ৪-দফা ভেরিফিকেশন সম্পন্ন এবং লাইভ হয়েছে!" : "চেকলিস্ট আপডেট হয়েছে।");
    }

    public function reject(Request $request, Property $property)
    {
        $property->update(['status' => 'rejected']);
        $verification = $property->verification ?: new PropertyVerification(['property_id' => $property->id]);
        $verification->verification_notes = $request->input('reason', 'প্রপার্টি তথ্য সঠিক নয়।');
        $verification->save();

        return back()->with('success', "প্রপার্টি #{$property->property_code} বাতিল (Rejected) করা হয়েছে।");
    }
}
