<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Services\MessageTemplateService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $stage = $request->query('stage', 'all');
        $query = Lead::with(['property', 'user', 'notes.author', 'assignedAgent']);

        if ($stage !== 'all') {
            $query->where('stage', $stage);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('lead_code', 'like', "%{$s}%")
                  ->orWhere('buyer_name', 'like', "%{$s}%")
                  ->orWhere('buyer_phone', 'like', "%{$s}%");
            });
        }

        $leads = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'           => Lead::count(),
            'new'             => Lead::where('stage', 'new')->count(),
            'contacted'       => Lead::where('stage', 'contacted')->count(),
            'visit_scheduled' => Lead::where('stage', 'visit_scheduled')->count(),
            'closed'          => Lead::where('stage', 'closed')->count(),
        ];

        return view('admin.leads', compact('leads', 'stats', 'stage'));
    }

    public function updateStage(Request $request, Lead $lead)
    {
        $stage = $request->validate([
            'stage' => 'required|in:new,contacted,qualified,visit_scheduled,visited,negotiation,booked,sold,dropped'
        ])['stage'];

        $lead->update(['stage' => $stage]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'stage' => $stage]);
        }

        return back()->with('success', "লিড স্টেজ '{$stage}' আপডেট হয়েছে।");
    }

    public function addNote(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        LeadNote::create([
            'lead_id'        => $lead->id,
            'author_user_id' => auth()->id(),
            'author_name'    => auth()->user()?->name ?? 'Admin',
            'note_text'      => $validated['note'],
        ]);

        return back()->with('success', 'লিড নোট সংরক্ষণ করা হয়েছে।');
    }

    public function getOwnerWhatsappUrl(Lead $lead)
    {
        $lead->load(['property.directSources', 'property.creator']);
        $ownerPhone = $lead->property?->directSources?->first()?->whatsapp_number 
            ?? $lead->property?->creator?->whatsapp_number 
            ?? $lead->property?->creator?->phone;

        if (empty($ownerPhone)) {
            return response()->json(['success' => false, 'error' => 'মালিকের হোয়াটসঅ্যাপ নম্বর পাওয়া যায়নি।']);
        }

        $msg = MessageTemplateService::render('lead_enquiry_notification', [
            '%name%'             => $lead->property?->creator?->name ?? 'মালিক মহোদয়',
            '%property_code%'    => $lead->property?->property_code ?? '',
            '%property_title%'   => $lead->property?->title ?? '',
            '%enquiry_type%'     => $lead->enquiry_type ?? 'নতুন বায়ার ইনকোয়ারি',
            '%lead_details%'     => "বায়ার নাম: {$lead->buyer_name}\nফোন: {$lead->buyer_phone}\nনোট: \"{$lead->message}\"",
            '%lead_message%'     => (string) ($lead->message ?? ''),
            '%site_name%'        => config('app.name', 'PropertyKoi.com'),
            '%login_magic_link%' => $lead->property?->creator?->magic_token ? url('/auth/magic/' . $lead->property->creator->magic_token) : url('/agent-portal'),
            '%phone%'            => $ownerPhone,
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $ownerPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '88' . $cleanPhone;
        }

        $url = 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($msg);

        return response()->json(['success' => true, 'whatsapp_url' => $url]);
    }
}
