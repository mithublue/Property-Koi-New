<?php

namespace App\Http\Controllers;

use App\Models\BuyerDemand;
use App\Models\Favorite;
use App\Models\Lead;
use App\Models\Property;
use App\Models\VisitBooking;
use App\Services\MagicLoginService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    public function mapView(Request $request)
    {
        $properties = Property::with(['media', 'land', 'flat', 'commercial', 'share'])
            ->where('status', 'live')
            ->get()
            ->map(function ($p) {
                $lat = $p->latitude;
                $lng = $p->longitude;

                if (!$lat || !$lng) {
                    $baseLat = 23.7650;
                    $baseLng = 90.4350;
                    $areaLower = strtolower($p->area ?? '');

                    if (str_contains($areaLower, 'banasree')) {
                        $baseLat = 23.7580;
                        $baseLng = 90.4390;
                    } elseif (str_contains($areaLower, 'purbachal')) {
                        $baseLat = 23.8250;
                        $baseLng = 90.4850;
                    } elseif (str_contains($areaLower, 'gulshan')) {
                        $baseLat = 23.7925;
                        $baseLng = 90.4078;
                    } elseif (str_contains($areaLower, 'uttara')) {
                        $baseLat = 23.8720;
                        $baseLng = 90.3840;
                    }

                    $blockOffset = (ord(strtoupper(substr($p->block ?? 'A', 0, 1))) - 65) * 0.0025;
                    $seed = abs(crc32(($p->slug ?? '') . $p->id));
                    $jitterLat = (($seed % 100) - 50) * 0.00004;
                    $jitterLng = ((intdiv($seed, 100) % 100) - 50) * 0.00004;

                    $lat = $baseLat + $blockOffset + $jitterLat;
                    $lng = $baseLng + $blockOffset + $jitterLng;
                }

                return [
                    'id'            => $p->id,
                    'title'         => $p->title,
                    'slug'          => $p->slug,
                    'type'          => $p->type,
                    'area'          => $p->area ?? 'আফতাবনগর',
                    'block'         => $p->block,
                    'road'          => $p->road_number,
                    'price'         => $p->formatted_price,
                    'size'          => $p->size ? $p->size . ' কাঠা' : ($p->flat_size_sqft ? $p->flat_size_sqft . ' sqft' : ''),
                    'image'         => $p->cover_url,
                    'url'           => route('property.show', $p->slug ?: $p->id),
                    'lat'           => (float) $lat,
                    'lng'           => (float) $lng,
                ];
            });

        $seo = SeoService::getOgMeta('map-view');

        return view('pages.map-view', compact('properties', 'seo'));
    }

    public function shortlist()
    {
        $seo = SeoService::getOgMeta('shortlist');

        return view('pages.shortlist', compact('seo'));
    }

    public function shortlistData(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', (array) $ids));

        // If authenticated, automatically import guest IDs to cloud favorites and merge
        if (Auth::check()) {
            $userId = Auth::id();
            foreach ($ids as $guestPropId) {
                if ($guestPropId > 0 && Property::where('id', $guestPropId)->exists()) {
                    Favorite::firstOrCreate([
                        'user_id'     => $userId,
                        'property_id' => $guestPropId,
                    ]);
                }
            }
            $favIds = Favorite::where('user_id', $userId)->pluck('property_id')->toArray();
            $ids = array_values(array_unique(array_merge($ids, $favIds)));
        }

        if (empty($ids)) {
            return response()->json([
                'success'    => true,
                'properties' => [],
                'data'       => ['properties' => []],
                'ids'        => [],
            ]);
        }

        $properties = Property::with(['media', 'land', 'flat', 'share', 'commercial'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($p) {
                $sizeLabel = match($p->type) {
                    'land'       => ($p->land?->katha_size ?? $p->size ?? 0) . ' কাঠা',
                    'flat'       => ($p->flat?->flat_size_sqft ?? 0) . ' বর্গফুট',
                    'land_share' => ($p->share?->flat_size_sqft ?? 0) . ' বর্গফুট',
                    'commercial' => ($p->commercial?->size_sqft ?? 0) . ' বর্গফুট',
                    default      => $p->size ? $p->size . ' কাঠা' : '',
                };

                return [
                    'id'              => $p->id,
                    'title'           => $p->title,
                    'slug'            => $p->slug,
                    'code'            => $p->property_code ?: ('AF-' . $p->id),
                    'type'            => $p->type,
                    'type_label'      => $p->type_label,
                    'block'           => $p->block ?: 'A',
                    'road'            => $p->road_number,
                    'price'           => $p->formatted_price,
                    'raw_price'       => $p->raw_price,
                    'size'            => $sizeLabel,
                    'image'           => $p->cover_url,
                    'cover_url'       => $p->cover_url,
                    'url'             => route('property.show', $p->slug ?: $p->id),
                ];
            });

        return response()->json([
            'success'    => true,
            'properties' => $properties,
            'data'       => ['properties' => $properties],
            'ids'        => $properties->pluck('id')->values()->all(),
        ]);
    }

    public function agentPortal(Request $request)
    {
        return redirect()->route('buyer.dashboard');
    }

    public function buyerDashboard(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('info', 'ক্রেতা ড্যাশবোর্ড দেখতে অনুগ্রহ করে লগইন করুন।');
        }

        $demands = BuyerDemand::where('user_id', $user->id)
            ->orWhere('phone', $user->phone)
            ->latest()
            ->get();

        $bookings = VisitBooking::where('user_id', $user->id)
            ->orWhere('buyer_phone', $user->phone)
            ->with('property')
            ->latest()
            ->get();

        $magicUrl = MagicLoginService::getMagicLoginUrl($user);

        return view('pages.buyer-dashboard', compact('user', 'demands', 'bookings', 'magicUrl'));
    }
}
