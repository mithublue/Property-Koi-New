<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerDemand;
use App\Models\Lead;
use App\Models\User;
use App\Models\VisitBooking;
use App\Services\BuyerDemandMatchService;
use App\Services\MagicLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BuyerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['buyerDemands', 'leads', 'visitBookings', 'favoriteProperties'])
            ->where('role', 'buyer');

        $search = trim((string) $request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('preferred_blocks', 'like', "%{$search}%")
                  ->orWhere('preferred_type', 'like', "%{$search}%");
            });
        }

        $filterType = trim((string) $request->input('type', ''));
        if (!empty($filterType) && $filterType !== 'all') {
            $query->where(function ($q) use ($filterType) {
                $q->where('preferred_type', $filterType)
                  ->orWhereHas('buyerDemands', fn($dq) => $dq->where('property_type', $filterType));
            });
        }

        $buyers = $query->latest()->paginate(15)->withQueryString();

        // Ensure every buyer has a magic token for 1-click access
        foreach ($buyers as $buyer) {
            if (empty($buyer->magic_token)) {
                MagicLoginService::getOrCreateToken($buyer);
            }
        }

        $stats = [
            'total_buyers'    => User::where('role', 'buyer')->count(),
            'active_demands'  => BuyerDemand::where('status', 'active')->count(),
            'total_leads'     => Lead::count(),
            'visit_bookings'  => VisitBooking::count(),
            'land_seekers'    => User::where('role', 'buyer')->where('preferred_type', 'land')->count(),
            'flat_seekers'    => User::where('role', 'buyer')->where('preferred_type', 'flat')->count(),
        ];

        return view('admin.buyers', compact('buyers', 'stats', 'search', 'filterType'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'required|string|max:50|unique:users,phone',
            'whatsapp_number'  => 'nullable|string|max:50',
            'email'            => 'nullable|email|max:255|unique:users,email',
            'preferred_type'   => 'nullable|string|in:land,flat,commercial,land_share,any',
            'preferred_areas'  => 'nullable|string|max:255',
            'preferred_blocks' => 'nullable|string|max:255',
            'budget_range'     => 'nullable|string|max:100',
            'min_budget'       => 'nullable|numeric',
            'max_budget'       => 'nullable|numeric',
            'purchase_timeline'=> 'nullable|string|max:100',
            'notes'            => 'nullable|string',
        ], [
            'phone.required' => 'বায়ারের মোবাইল নম্বর দেওয়া আবশ্যক।',
            'phone.unique'   => 'এই মোবাইল নম্বরের বায়ার ইতিমধ্যে ডাটাবেসে রয়েছে।',
            'email.unique'   => 'এই ইমেইলটি অন্য অ্যাকাউন্টে ব্যবহৃত হচ্ছে।',
        ]);

        $rawPhone = preg_replace('/[^0-9]/', '', $validated['phone']);
        $whatsapp = $validated['whatsapp_number'] ? preg_replace('/[^0-9]/', '', $validated['whatsapp_number']) : $rawPhone;

        // 1. Create or get User with buyer role
        $buyer = User::create([
            'name'             => $validated['name'],
            'phone'            => $validated['phone'],
            'whatsapp_number'  => $whatsapp,
            'email'            => $validated['email'] ?: ('buyer_' . Str::random(6) . '@propertykoi.local'),
            'password'         => Hash::make(Str::random(16)),
            'role'             => 'buyer',
            'is_verified'      => true,
            'preferred_type'   => $validated['preferred_type'] ?? 'land',
            'preferred_blocks' => $validated['preferred_blocks'] ?? null,
            'budget_range'     => $validated['budget_range'] ?? null,
        ]);

        MagicLoginService::getOrCreateToken($buyer);

        // 2. Automatically register an active BuyerDemand for automated matching
        $areas = !empty($validated['preferred_areas'])
            ? array_filter(array_map('trim', explode(',', $validated['preferred_areas'])))
            : ['Aftabnagar', 'Aftab Nagar'];

        $blocks = !empty($validated['preferred_blocks'])
            ? array_filter(array_map('trim', explode(',', $validated['preferred_blocks'])))
            : [];

        $demand = BuyerDemand::create([
            'user_id'           => $buyer->id,
            'buyer_name'        => $buyer->name,
            'buyer_phone'       => $buyer->phone,
            'buyer_email'       => $buyer->email,
            'property_type'     => $validated['preferred_type'] ?? 'any',
            'preferred_areas'   => $areas,
            'preferred_blocks'  => $blocks,
            'min_budget'        => $validated['min_budget'] ?? 0,
            'max_budget'        => $validated['max_budget'] ?? 0,
            'purchase_timeline' => $validated['purchase_timeline'] ?? 'immediate',
            'status'            => 'active',
            'notify_channels'   => ['phone', 'email', 'telegram'],
            'notes'             => $validated['notes'] ?? null,
        ]);

        // 3. Check immediate matches
        $matches = BuyerDemandMatchService::findMatchingPropertiesForRequirement($demand);
        $matchCount = count($matches);

        $msg = "বায়ার {$buyer->name} সফলভাবে যুক্ত হয়েছেন!";
        if ($matchCount > 0) {
            $msg .= " ডাটাবেসে তার চাহিদার সাথে মিলে এমন {$matchCount}টি প্রপার্টি পাওয়া গেছে।";
        }

        return redirect()->route('admin.buyers.index')->with('success', $msg);
    }

    public function destroy(User $user)
    {
        if ($user->role !== 'buyer') {
            return redirect()->route('admin.buyers.index')->with('error', 'শুধুমাত্র বায়ার মুছে ফেলা যাবে।');
        }

        // Remove related demands & inquiries
        $user->buyerDemands()->delete();
        $user->leads()->delete();
        $user->visitBookings()->delete();
        $user->delete();

        return redirect()->route('admin.buyers.index')->with('success', 'বায়ার রেকর্ড সফলভাবে মুছে ফেলা হয়েছে।');
    }
}
