<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerDemand;
use App\Services\BuyerDemandMatchService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DemandController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $propertyType = (string) $request->query('property_type', 'all');
        $status = (string) $request->query('status', 'all');
        $telegram = (string) $request->query('telegram', 'all');

        $query = BuyerDemand::with(['notifications.property.media', 'user'])->latest('id');

        // 1. Search Filter (Chahida Code, Name, Phone/WhatsApp, Email)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('requirement_code', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('buyer_phone', 'like', "%{$search}%")
                  ->orWhere('buyer_email', 'like', "%{$search}%")
                  ->orWhere('telegram_username', 'like', "%{$search}%")
                  ->orWhere('telegram_chat_id', 'like', "%{$search}%");
            });
        }

        // 2. Property Type Filter
        if ($propertyType !== 'all') {
            $query->where('property_type', $propertyType);
        }

        // 3. Status Filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // 4. Telegram Filter
        if ($telegram === 'connected') {
            $query->whereNotNull('telegram_chat_id')->where('telegram_chat_id', '!=', '');
        } elseif ($telegram === 'not_connected') {
            $query->where(function ($q) {
                $q->whereNull('telegram_chat_id')->orWhere('telegram_chat_id', '');
            });
        }

        $allDemands = $query->get();

        // Attach pre-calculated live matches to each demand for instant display in accordion
        foreach ($allDemands as $d) {
            $rawMatches = BuyerDemandMatchService::findMatchingPropertiesForRequirement($d);
            $formattedMatches = [];
            foreach ($rawMatches as $m) {
                $p = $m['property'];
                $loc = ($p->area ?: 'আফতাবনগর') . ($p->block ? ' • ব্লক ' . $p->block : ($p->sub_area ? ' • ' . $p->sub_area : ''));
                $waMsg = "আসসালামু আলাইকুম {$d->buyer_name},\nPropertyKoi-তে আপনার চাহিদার সাথে মিলে এমন একটি দারুণ প্রপার্টি পেয়েছি:\n\n🏡 *{$p->title}*\n📍 অবস্থান: {$loc}\n💰 মূল্য: {$p->formatted_price}\n🔗 বিস্তারিত লিংক: " . url('/property/' . $p->slug);

                $formattedMatches[] = [
                    'id'              => $p->id,
                    'code'            => $p->property_code,
                    'title'           => $p->title,
                    'slug'            => $p->slug,
                    'url'             => url('/property/' . $p->slug),
                    'cover_url'       => $p->cover_url,
                    'price'           => $p->formatted_price,
                    'location'        => $loc,
                    'type_label'      => $p->type_label,
                    'score'           => $m['score'] ?? 100,
                    'reasons'         => $m['reasons'] ?? [],
                    'share_wa_url'    => 'https://wa.me/' . self::formatWaNumber($d->buyer_phone) . '?text=' . urlencode($waMsg),
                ];
            }
            $d->matches_data = $formattedMatches;
            $d->matches_count = count($formattedMatches);
        }

        // Group demands by Submitter (primarily by buyer_phone, fallback to buyer_email or demand id)
        $grouped = $allDemands->groupBy(function ($d) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$d->buyer_phone);
            return !empty($cleanPhone) ? $cleanPhone : (strtolower(trim((string)$d->buyer_email)) ?: 'sub_' . $d->id);
        });

        $submitters = $grouped->map(function ($demandsList, $key) {
            /** @var BuyerDemand $latest */
            $latest = $demandsList->first();
            $connectedTele = $demandsList->first(fn($d) => !empty($d->telegram_chat_id));
            $teleChatId = $connectedTele?->telegram_chat_id ?? null;
            $teleUsername = $connectedTele?->telegram_username ?? null;

            $totalMatches = $demandsList->sum('matches_count');
            $cleanPhone = self::formatWaNumber($latest->buyer_phone);

            $defaultWaMsg = "আসসালামু আলাইকুম {$latest->buyer_name}, PropertyKoi থেকে যোগাযোগ করছি আপনার প্রপার্টি চাহিদার বিষয়ে...";

            return [
                'key'               => $key,
                'name'              => $latest->buyer_name,
                'phone'             => $latest->buyer_phone,
                'clean_phone'       => $cleanPhone,
                'whatsapp_url'      => !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}?text=" . urlencode($defaultWaMsg) : null,
                'email'             => $latest->buyer_email,
                'telegram_chat_id'  => $teleChatId,
                'telegram_username' => $teleUsername,
                'telegram_connected'=> !empty($teleChatId),
                'demands_count'     => $demandsList->count(),
                'total_matches'     => $totalMatches,
                'latest_demand'     => $latest,
                'first_seen'        => $demandsList->last()?->created_at,
                'latest_seen'       => $latest->created_at,
                'demands'           => $demandsList->values()->toArray(),
            ];
        })->values();

        // Pagination for submitters
        $perPage = 15;
        $currentPage = (int) $request->query('page', 1);
        $currentItems = $submitters->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedSubmitters = new LengthAwarePaginator(
            $currentItems,
            $submitters->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Stats calculation
        $stats = [
            'total_demands'      => BuyerDemand::count(),
            'unique_submitters'  => BuyerDemand::distinct('buyer_phone')->count('buyer_phone'),
            'telegram_connected' => BuyerDemand::whereNotNull('telegram_chat_id')->where('telegram_chat_id', '!=', '')->distinct('buyer_phone')->count('buyer_phone'),
            'matched_demands'    => BuyerDemand::where('match_count', '>', 0)->orWhere('status', 'matched')->count(),
            'active_demands'     => BuyerDemand::where('status', 'active')->count(),
        ];

        return view('admin.demands', [
            'submitters'   => $paginatedSubmitters,
            'stats'        => $stats,
            'filters'      => [
                'search'        => $search,
                'property_type' => $propertyType,
                'status'        => $status,
                'telegram'      => $telegram,
            ],
        ]);
    }

    public function updateStatus(Request $request, BuyerDemand $demand): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,matched,closed',
        ]);

        $demand->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $demand->status]);
        }

        return back()->with('success', "চাহিদা #{$demand->requirement_code} এর স্ট্যাটাস আপডেট হয়েছে।");
    }

    public function destroy(BuyerDemand $demand): RedirectResponse
    {
        $code = $demand->requirement_code;
        $demand->notifications()->delete();
        $demand->delete();

        return back()->with('success', "চাহিদা #{$code} সফলভাবে মুছে ফেলা হয়েছে।");
    }

    public function sendTelegramAlert(BuyerDemand $demand): JsonResponse|RedirectResponse
    {
        if (empty($demand->telegram_chat_id)) {
            $msg = 'এই বায়ারের টেলিগ্রাম অ্যাকাউন্ট এখনও কানেক্টেড নেই।';
            return request()->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        $matches = BuyerDemandMatchService::findMatchingPropertiesForRequirement($demand);
        TelegramService::sendDemandConfirmation($demand->telegram_chat_id, $demand, $matches);

        if (!empty($matches)) {
            TelegramService::sendPropertyMatch($demand->telegram_chat_id, $demand, $matches);
        }

        $msg = "চাহিদা #{$demand->requirement_code} এর অ্যালার্ট বায়ারের টেলিগ্রামে পাঠানো হয়েছে।";
        return request()->wantsJson() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('success', $msg);
    }

    private static function formatWaNumber(?string $phone): ?string
    {
        if (empty($phone)) return null;
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($clean)) return null;
        if (str_starts_with($clean, '0')) {
            return '88' . $clean;
        }
        if (!str_starts_with($clean, '88') && strlen($clean) === 10) {
            return '880' . $clean;
        }
        return $clean;
    }
}
