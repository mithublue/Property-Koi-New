<?php

namespace App\Http\Controllers;

use App\Mail\AccountActivationMail;
use App\Models\BuyerDemand;
use App\Models\BuyerInteraction;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\User;
use App\Models\VisitBooking;
use App\Services\BuyerDemandMatchService;
use App\Services\WatermarkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BuyerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('info', 'ক্রেতা ড্যাশবোর্ড ব্যবহার করতে অনুগ্রহ করে লগইন বা রেজিস্ট্রেশন করুন।');
        }

        // 1. Profile Data
        $profile = [
            'user_id'           => $user->id,
            'display_name'      => $user->name,
            'username'          => $user->username ?: '',
            'email'             => $user->email ?: '',
            'is_email_verified' => $user->isEmailVerified(),
            'phone'             => $user->phone ?: '',
            'whatsapp_number'   => $user->whatsapp_number ?: ($user->phone ?: ''),
            'avatar_url'        => $user->avatar_url ?: ('https://ui-avatars.com/api/?name=' . urlencode($user->name ?: 'Buyer') . '&background=059669&color=fff&size=160&bold=true'),
            'preferred_type'    => $user->preferred_type ?: 'any',
            'preferred_blocks'  => $user->preferred_blocks ?: '',
            'budget_range'      => $user->budget_range ?: '',
            'member_since'      => $user->created_at ? $user->created_at->format('F Y') : date('F Y'),
        ];

        // 2. Favorites
        $favoritesQuery = Favorite::where('user_id', $user->id)
            ->with(['property.media', 'property.land', 'property.flat', 'property.share', 'property.commercial'])
            ->latest()
            ->get();

        $favorites = [];
        foreach ($favoritesQuery as $fav) {
            $p = $fav->property;
            if (!$p) continue;

            $cover = $p->cover_url;
            $sizeLabel = match($p->type) {
                'land'       => ($p->land?->katha_size ?? $p->size ?? 0) . ' কাঠা',
                'flat'       => ($p->flat?->flat_size_sqft ?? 0) . ' বর্গফুট',
                'land_share' => ($p->share?->flat_size_sqft ?? 0) . ' বর্গফুট',
                'commercial' => ($p->commercial?->size_sqft ?? 0) . ' বর্গফুট',
                default      => $p->size ? $p->size . ' কাঠা' : '',
            };

            $favorites[] = [
                'id'              => $p->id,
                'property_code'   => $p->property_code ?: ('AF-' . $p->id),
                'title'           => $p->title,
                'type'            => $p->type,
                'type_label'      => match($p->type) {
                    'land'       => 'জমি',
                    'flat'       => 'ফ্ল্যাট',
                    'land_share' => 'ল্যান্ড শেয়ার',
                    'commercial' => 'দোকান/কমার্শিয়াল',
                    default      => $p->type,
                },
                'block'           => $p->block ?: '',
                'road_number'     => $p->road_number ?: '',
                'plot_number'     => $p->plot_number ?: '',
                'size_label'      => $sizeLabel,
                'price'           => (float) ($p->raw_price ?: $p->asking_price),
                'formatted_price' => $p->formatted_price,
                'cover_url'       => $cover,
                'detail_url'      => route('property.show', $p->slug ?: $p->id),
            ];
        }

        // 3. Buyer Demands
        // Auto-claim any unassigned/historical demands matching this user's email, phone, or telegram
        BuyerDemand::queryForUser($user)
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id);
            })
            ->update(['user_id' => $user->id]);

        $demandsRaw = BuyerDemand::queryForUser($user)
            ->latest()
            ->get();

        $demands = [];
        foreach ($demandsRaw as $d) {
            $matchedProps = BuyerDemandMatchService::findMatchingPropertiesForRequirement($d);
            $matches = [];
            foreach ($matchedProps as $m) {
                $mp = $m['property'] ?? null;
                if (!$mp) continue;
                $matches[] = [
                    'id'            => $mp->id,
                    'property_code' => $mp->property_code ?: ('AF-' . $mp->id),
                    'title'         => $mp->title,
                    'area'          => $mp->area ?: 'Aftabnagar',
                    'block'         => $mp->block ?: '',
                    'match_score'   => round($m['score'] ?? 95),
                    'detail_url'    => route('property.show', $mp->slug ?: $mp->id),
                    'channels'      => (array) ($d->notify_channels ?: ['push', 'email']),
                ];
            }

            $demands[] = [
                'id'                  => $d->id,
                'requirement_code'    => $d->requirement_code ?: ($d->demand_code ?: ('REQ-' . $d->id)),
                'status'              => $d->status ?: 'active',
                'property_type'       => $d->property_type,
                'property_type_label' => match($d->property_type) {
                    'land'       => 'জমি',
                    'flat'       => 'ফ্ল্যাট',
                    'land_share' => 'ল্যান্ড শেয়ার',
                    'commercial' => 'দোকান/কমার্শিয়াল',
                    default      => 'যেকোনো ধরনের',
                },
                'preferred_areas'     => (array) ($d->preferred_areas ?: [$d->preferred_blocks ?: 'Aftabnagar']),
                'min_budget'          => (float) ($d->min_budget ?: 0),
                'max_budget'          => (float) ($d->max_budget ?: ($d->budget_max ?: 0)),
                'min_size'            => (float) ($d->min_size ?: 0),
                'max_size'            => (float) ($d->max_size ?: 0),
                'notify_channels'     => (array) ($d->notify_channels ?: ['push', 'email', 'phone']),
                'telegram_chat_id'    => $d->telegram_chat_id,
                'telegram_deep_link'  => \App\Services\TelegramService::getDeepLinkForDemand($d),
                'matches'             => $matches,
                'last_notified_at'    => $d->last_notified_at ? $d->last_notified_at->format('d M, Y (h:i A)') : null,
                'created_at'          => $d->created_at ? $d->created_at->format('d M, Y') : '',
            ];
        }

        // 4. Interactions (WhatsApp, Offers, Site Visits)
        $interactionsRaw = BuyerInteraction::where('user_id', $user->id)
            ->with(['property.media'])
            ->latest()
            ->get();

        // Also merge VisitBookings
        $visitBookings = VisitBooking::where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->phone) {
                    $q->orWhere('buyer_phone', $user->phone);
                }
            })
            ->with(['property.media'])
            ->latest()
            ->get();

        $interactions = [];

        foreach ($interactionsRaw as $ir) {
            $p = $ir->property;
            $cover = $p?->media->first()?->file_path ?: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&auto=format&fit=crop&q=80';

            $typeLabel = match($ir->interaction_type) {
                'whatsapp_contact' => 'হোয়াটসঅ্যাপ যোগাযোগ',
                'make_offer'       => 'দর প্রস্তাব (অফার)',
                'site_visit'       => 'সাইট ভিজিট শিডিউল',
                default            => 'কার্যক্রম',
            };

            $badgeColor = match($ir->interaction_type) {
                'whatsapp_contact' => 'bg-emerald-500 text-white',
                'make_offer'       => 'bg-teal-600 text-white',
                'site_visit'       => 'bg-indigo-600 text-white',
                default            => 'bg-slate-700 text-white',
            };

            $interactions[] = [
                'id'               => $ir->id,
                'property_id'      => $ir->property_id,
                'property_code'    => $p?->property_code ?: ('AF-' . $ir->property_id),
                'property_title'   => $p?->title ?: ('প্রপার্টি #' . $ir->property_id),
                'property_type'    => $p?->type ?: 'land',
                'block'            => $p?->block ?: '',
                'cover_url'        => $cover,
                'detail_url'       => $p ? route('property.show', $p->slug ?: $p->id) : '#',
                'interaction_type' => $ir->interaction_type,
                'type_label'       => $typeLabel,
                'badge_color'      => $badgeColor,
                'offered_price'    => $ir->offered_price ? (float) $ir->offered_price : null,
                'formatted_offer'  => $ir->offered_price ? ('৳ ' . number_format($ir->offered_price)) : null,
                'slot_date'        => $ir->slot_date ? $ir->slot_date->format('d M, Y') : '',
                'slot_time'        => $ir->slot_time ?: '',
                'status'           => $ir->status ?: 'submitted',
                'notes'            => $ir->notes ?: '',
                'created_at'       => $ir->created_at ? $ir->created_at->format('Y-m-d H:i') : '',
                'formatted_date'   => $ir->created_at ? $ir->created_at->format('d M, Y (h:i A)') : '',
            ];
        }

        // Merge visit bookings if not already present
        foreach ($visitBookings as $vb) {
            $alreadyHas = collect($interactions)->contains(function ($item) use ($vb) {
                return $item['interaction_type'] === 'site_visit' && $item['property_id'] === $vb->property_id;
            });

            if (!$alreadyHas) {
                $p = $vb->property;
                $cover = $p?->media->first()?->file_path ?: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&auto=format&fit=crop&q=80';

                $interactions[] = [
                    'id'               => 'vb_' . $vb->id,
                    'property_id'      => $vb->property_id,
                    'property_code'    => $p?->property_code ?: ('AF-' . $vb->property_id),
                    'property_title'   => $p?->title ?: 'আফতাবনগর প্রপার্টি ভিজিট',
                    'property_type'    => $p?->type ?: 'land',
                    'block'            => $p?->block ?: '',
                    'cover_url'        => $cover,
                    'detail_url'       => $p ? route('property.show', $p->slug ?: $p->id) : '#',
                    'interaction_type' => 'site_visit',
                    'type_label'       => 'সাইট ভিজিট শিডিউল',
                    'badge_color'      => 'bg-indigo-600 text-white',
                    'offered_price'    => null,
                    'formatted_offer'  => null,
                    'slot_date'        => $vb->slot_date ? \Carbon\Carbon::parse($vb->slot_date)->format('d M, Y') : '',
                    'slot_time'        => $vb->slot_time ?: '',
                    'status'           => $vb->status ?: 'pending',
                    'notes'            => $vb->notes ?: '',
                    'created_at'       => $vb->created_at ? $vb->created_at->format('Y-m-d H:i') : '',
                    'formatted_date'   => $vb->created_at ? $vb->created_at->format('d M, Y (h:i A)') : '',
                ];
            }
        }

        // 5. My Properties (Posted as Owner / Buyer)
        $myPropsRaw = Property::where('created_by_user_id', $user->id)
            ->with(['media', 'land', 'flat', 'share', 'commercial'])
            ->latest()
            ->get();

        $myProperties = [];
        foreach ($myPropsRaw as $mp) {
            $cover = $mp->cover_url;

            $statusLabel = match($mp->status) {
                'live'                 => 'অনুমোদিত ও লাইভ',
                'pending_verification' => 'ভেরিফিকেশন পেন্ডিং',
                'draft'                => 'ড্রাফট (খসড়া)',
                'booked'               => 'বুকড',
                'sold'                 => 'বিক্রিত',
                default                => $mp->status,
            };

            $statusColor = match($mp->status) {
                'live'                 => 'bg-emerald-600 text-white',
                'pending_verification' => 'bg-amber-500 text-white',
                'draft'                => 'bg-slate-500 text-white',
                'booked'               => 'bg-blue-600 text-white',
                'sold'                 => 'bg-slate-700 text-white',
                default                => 'bg-slate-600 text-white',
            };

            $myProperties[] = [
                'id'                 => $mp->id,
                'property_code'      => $mp->property_code ?: ('AF-' . $mp->id),
                'title'              => $mp->title,
                'type'               => $mp->type,
                'type_label'         => match($mp->type) {
                    'land'       => 'জমি',
                    'flat'       => 'ফ্ল্যাট',
                    'land_share' => 'ল্যান্ড শেয়ার',
                    'commercial' => 'দোকান/কমার্শিয়াল',
                    default      => $mp->type,
                },
                'area'               => $mp->area ?: 'Aftabnagar',
                'block'              => $mp->block ?: 'A',
                'sector'             => $mp->sector ?: '',
                'road_number'        => $mp->road_number ?: '',
                'road_width_ft'      => (int) ($mp->road_width_ft ?: 20),
                'plot_number'        => $mp->plot_number ?: '',
                'holding_number'     => $mp->holding_number ?: '',
                'price'              => (float) ($mp->raw_price ?: $mp->asking_price),
                'formatted_price'    => $mp->formatted_price,
                'status'             => $mp->status,
                'status_label'       => $statusLabel,
                'status_color'       => $statusColor,
                'views_count'        => (int) ($mp->views_count ?: 0),
                'views_count_bn'     => self::toBnNumber((int) ($mp->views_count ?: 0)),
                'cover_url'          => $cover,
                'created_at'         => $mp->created_at ? $mp->created_at->format('d M, Y') : '',
                'detail_url'         => route('property.show', $mp->slug ?: $mp->id),
                'url'                => route('property.show', $mp->slug ?: $mp->id),
                'is_negotiable'      => (int) ($mp->is_negotiable ?? 1),
                'owner_name'         => $mp->owner_name ?: $user->name,
                'owner_phone'        => $mp->owner_phone ?: $user->whatsapp_number,
                'address_visibility' => $mp->address_visibility ?: [
                    'block' => true, 'sector' => true, 'road_number' => true,
                    'road_width_ft' => true, 'plot_number' => true, 'holding_number' => true,
                ],
                // Specs
                'katha_size'         => $mp->land?->katha_size ?? $mp->size ?? '',
                'soil_filling'       => $mp->land?->soil_filling ?? 'ready',
                'boundary_wall'      => (int) ($mp->land?->boundary_wall ?? 0),
                'corner_plot'        => (int) ($mp->land?->corner_plot ?? 0),
                'flat_size_sqft'     => $mp->flat?->flat_size_sqft ?? '',
                'bedrooms'           => $mp->flat?->bedrooms ?? 3,
                'bathrooms'          => $mp->flat?->bathrooms ?? 3,
                'balconies'          => $mp->flat?->balconies ?? 2,
                'floor_number'       => $mp->flat?->floor_number ?? 1,
                'total_floors'       => $mp->flat?->total_floors ?? 6,
                'has_parking'        => (int) ($mp->flat?->has_parking ?? 1),
                'handover_status'    => $mp->flat?->handover_status ?? 'ready',
            ];
        }

        // Stats summary
        $stats = [
            'favorites'     => count($favorites),
            'demands'       => count($demands),
            'whatsapp'      => count(array_filter($interactions, fn($i) => $i['interaction_type'] === 'whatsapp_contact')),
            'offers'        => count(array_filter($interactions, fn($i) => $i['interaction_type'] === 'make_offer')),
            'visits'        => count(array_filter($interactions, fn($i) => $i['interaction_type'] === 'site_visit')),
            'my_properties' => count($myProperties),
        ];

        $max_demands_limit = ($user->isAdmin() || $user->role === 'agent')
            ? null
            : (int) \App\Models\Setting::get('adm_max_demands_per_user', 3);

        return view('pages.buyer-dashboard', compact('profile', 'favorites', 'demands', 'interactions', 'myProperties', 'stats', 'user', 'max_demands_limit'));
    }

    public function toggleFavorite(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুগ্রহ করে প্রথমে লগইন করুন।'], 401);
        }

        $propertyId = (int) $request->input('property_id');
        if (!$propertyId) {
            return response()->json(['success' => false, 'message' => 'অবৈধ প্রপার্টি আইডি।'], 422);
        }

        $fav = Favorite::where('user_id', $user->id)->where('property_id', $propertyId)->first();

        if ($fav) {
            $fav->delete();
            return response()->json([
                'success'     => true,
                'is_favorite' => false,
                'message'     => 'পছন্দের তালিকা থেকে মুছে ফেলা হয়েছে।',
            ]);
        } else {
            Favorite::create([
                'user_id'     => $user->id,
                'property_id' => $propertyId,
            ]);
            return response()->json([
                'success'     => true,
                'is_favorite' => true,
                'message'     => 'পছন্দের তালিকায় সফলভাবে যোগ করা হয়েছে!',
            ]);
        }
    }

    public function removeFavorite(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 401);
        }

        if ($request->input('clear_all')) {
            Favorite::where('user_id', $user->id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'পুরো পছন্দ তালিকা খালি করা হয়েছে!',
            ]);
        }

        $propertyId = (int) $request->input('property_id');
        Favorite::where('user_id', $user->id)->where('property_id', $propertyId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'পছন্দ তালিকা থেকে সফলভাবে মুছে ফেলা হয়েছে!',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 401);
        }

        $validated = $request->validate([
            'displayName'     => 'required|string|max:100',
            'email'           => 'required|email|max:100',
            'whatsappNumber'  => 'nullable|string|max:30',
            'phone'           => 'nullable|string|max:30',
            'preferredType'   => 'nullable|string|max:30',
            'preferredBlocks' => 'nullable|string|max:100',
            'budgetRange'     => 'nullable|string|max:100',
            'password'        => 'nullable|string|min:6',
            'avatar'          => 'nullable|image|max:10240',
        ]);

        $emailChanged = false;
        $newEmail = strtolower(trim($validated['email']));

        if ($newEmail !== strtolower($user->email)) {
            // Check if email belongs to someone else
            $exists = User::where('email', $newEmail)->where('id', '!=', $user->id)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'এই ইমেইল ঠিকানাটি অন্য একটি অ্যাকাউন্টে ইতিমধ্যে ব্যবহৃত হচ্ছে।',
                ], 422);
            }
            $user->email = $newEmail;
            $user->email_verified_at = null;
            $emailChanged = true;
        }

        $user->name             = $validated['displayName'];
        $user->whatsapp_number  = $validated['whatsappNumber'] ?? $user->whatsapp_number;
        $user->phone            = $validated['phone'] ?? $user->phone;
        $user->preferred_type   = $validated['preferredType'] ?? 'any';
        $user->preferred_blocks = $validated['preferredBlocks'] ?? null;
        $user->budget_range     = $validated['budgetRange'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Avatar Upload
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');
            $user->avatar_url = 'storage/' . $path;
        }

        $user->save();

        // If email changed, dispatch new activation link and code
        if ($emailChanged) {
            $token = Str::random(64);
            $code  = (string) random_int(100000, 999999);
            $user->update([
                'email_verification_token'   => $token,
                'email_verification_code'    => $code,
                'email_verification_sent_at' => now(),
            ]);

            $activationUrl = route('auth.verify-email', ['token' => $token]);
            try {
                Mail::to($user->email)->send(new AccountActivationMail($user, $activationUrl, $code));
            } catch (\Throwable $e) {}

            return response()->json([
                'success'           => true,
                'message'           => 'প্রোফাইল আপডেট হয়েছে! নতুন ইমেইলে ভেরিফিকেশন কোড ও লিঙ্ক পাঠানো হয়েছে।',
                'is_email_verified' => false,
                'avatar_url'        => $user->avatar_url,
            ]);
        }

        return response()->json([
            'success'           => true,
            'message'           => 'প্রোফাইল তথ্য সফলভাবে সংরক্ষণ ও আপডেট করা হয়েছে!',
            'is_email_verified' => $user->isEmailVerified(),
            'avatar_url'        => $user->avatar_url,
        ]);
    }

    public function storeDemand(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 401);
        }

        $code = 'REQ-' . strtoupper(Str::random(6));

        $teleInput = trim($request->telegram_chat_id ?: ($user->telegram_chat_id ?? ''));
        $teleChatId = null;
        $teleUsername = null;
        if (!empty($teleInput)) {
            if (is_numeric($teleInput) || str_starts_with($teleInput, '-')) {
                $teleChatId = $teleInput;
            } else {
                $teleUsername = ltrim($teleInput, '@');
                $resolved = \App\Services\TelegramService::resolveChatIdFromUsername($teleUsername);
                if ($resolved) {
                    $teleChatId = (string) $resolved;
                }
            }
        }

        $submitPhone = $request->buyer_phone ?: ($user->whatsapp_number ?: $user->phone);
        $submitEmail = $request->buyer_email ?: $user->email;

        // Enforce maximum demand submissions limit for regular buyers
        $isStaff = $user->isAdmin() || $user->role === 'agent';
        if (!$isStaff) {
            $maxDemands = (int) \App\Models\Setting::get('adm_max_demands_per_user', 3);
            $existingCount = BuyerDemand::countDemandsForSubmitter($user->id, $submitPhone, $submitEmail, $teleChatId, $teleUsername, onlyActive: true);

            if ($existingCount >= $maxDemands) {
                return response()->json([
                    'success'       => false,
                    'message'       => "আপনি ইতিমধ্যে সর্বোচ্চ {$maxDemands}টি সক্রিয় চাহিদা সাবমিট করেছেন। নতুন চাহিদা যোগ করতে হলে আপনার তালিকা থেকে যেকোনো একটি পুরনো চাহিদা মুছে ফেলুন বা বন্ধ করুন।",
                    'limit_reached' => true,
                    'max_limit'     => $maxDemands,
                ], 422);
            }
        }

        $demand = BuyerDemand::create([
            'user_id'           => $user->id,
            'buyer_name'        => $request->buyer_name ?: $user->name,
            'buyer_phone'       => $submitPhone,
            'buyer_email'       => $submitEmail,
            'property_type'     => $request->property_type ?: 'land',
            'preferred_areas'   => $request->preferred_areas ?: ['Aftabnagar'],
            'min_budget'        => $request->min_budget ?: null,
            'max_budget'        => $request->max_budget ?: null,
            'min_size'          => $request->min_size ?: null,
            'max_size'          => $request->max_size ?: null,
            'notify_channels'   => $request->notify_channels ?: ['push', 'email', 'phone'],
            'telegram_chat_id'  => $teleChatId,
            'telegram_username' => $teleUsername,
            'status'            => 'active',
            'requirement_code'  => $code,
            'unsubscribe_token' => Str::random(32),
        ]);

        // Run instant match & send confirmation
        $matches = BuyerDemandMatchService::findMatchingPropertiesForRequirement($demand);
        if (!empty($matches)) {
            BuyerDemandMatchService::dispatchDemandNotifications($demand, $matches);
        } else {
            BuyerDemandMatchService::sendDemandReceivedConfirmation($demand);
        }

        return response()->json([
            'success'            => true,
            'message'            => 'আপনার চাহিদা সফলভাবে রেকর্ড করা হয়েছে! AI ইঞ্জিন স্বয়ংক্রিয়ভাবে মিল খুঁজবে।',
            'demand'             => $demand,
            'telegram_deep_link' => \App\Services\TelegramService::getDeepLinkForDemand($demand),
        ]);
    }

    public function toggleDemandStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 401);
        }

        $demandId = (int) $request->demand_id;
        $status = $request->status === 'closed' ? 'closed' : 'active';

        $demand = BuyerDemand::find($demandId);
        if (!$demand) {
            return response()->json(['success' => false, 'message' => 'চাহিদা খুঁজে পাওয়া যায়নি।'], 404);
        }

        $cleanUserPhone = $user->phone ? preg_replace('/[^0-9]/', '', $user->phone) : '';
        $last10UserPhone = strlen($cleanUserPhone) >= 10 ? substr($cleanUserPhone, -10) : $cleanUserPhone;
        $cleanDemandPhone = $demand->buyer_phone ? preg_replace('/[^0-9]/', '', $demand->buyer_phone) : '';
        $last10DemandPhone = strlen($cleanDemandPhone) >= 10 ? substr($cleanDemandPhone, -10) : $cleanDemandPhone;

        $isOwner = ($demand->user_id === $user->id)
            || ($user->email && strtolower(trim((string)$demand->buyer_email)) === strtolower(trim((string)$user->email)))
            || (!empty($last10UserPhone) && !empty($last10DemandPhone) && $last10UserPhone === $last10DemandPhone);

        if (!$isOwner && !$user->isAdmin() && $user->role !== 'agent') {
            return response()->json(['success' => false, 'message' => 'অনুমতি নেই।'], 403);
        }

        $demand->update(['status' => $status]);
        return response()->json(['success' => true, 'status' => $status]);
    }

    public function deleteDemand(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 401);
        }

        $demandId = (int) $request->demand_id;
        $demand = BuyerDemand::find($demandId);

        if (!$demand) {
            return response()->json(['success' => true, 'message' => 'চাহিদা ইতিমধ্যে মুছে ফেলা হয়েছে।']);
        }

        $cleanUserPhone = $user->phone ? preg_replace('/[^0-9]/', '', $user->phone) : '';
        $last10UserPhone = strlen($cleanUserPhone) >= 10 ? substr($cleanUserPhone, -10) : $cleanUserPhone;
        $cleanDemandPhone = $demand->buyer_phone ? preg_replace('/[^0-9]/', '', $demand->buyer_phone) : '';
        $last10DemandPhone = strlen($cleanDemandPhone) >= 10 ? substr($cleanDemandPhone, -10) : $cleanDemandPhone;

        $isOwner = ($demand->user_id === $user->id)
            || ($user->email && strtolower(trim((string)$demand->buyer_email)) === strtolower(trim((string)$user->email)))
            || (!empty($last10UserPhone) && !empty($last10DemandPhone) && $last10UserPhone === $last10DemandPhone);

        if (!$isOwner && !$user->isAdmin() && $user->role !== 'agent') {
            return response()->json(['success' => false, 'message' => 'এই চাহিদা মুছে ফেলার অনুমতি নেই।'], 403);
        }

        $demand->notifications()->delete();
        $demand->delete();

        return response()->json(['success' => true, 'message' => 'চাহিদা সফলভাবে মুছে ফেলা হয়েছে!']);
    }

    public function runDemandMatchCron(Request $request)
    {
        try {
            $result = BuyerDemandMatchService::runDailyDemandMatching(true);
            return response()->json([
                'success' => true,
                'message' => 'স্বয়ংক্রিয় এআই ম্যাচিং যাচাই সম্পন্ন হয়েছে! নতুন মিল থাকলে নোটিফিকেশন পাঠানো হয়েছে।',
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ম্যাচিং প্রসেসে সমস্যা হয়েছে: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPropertyForEdit(Property $property)
    {
        $user = Auth::user();
        if (!$user || ((int) $property->created_by_user_id !== (int) $user->id && !$user->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 403);
        }

        $property->load(['land', 'flat', 'share', 'commercial', 'media']);

        $media = $this->formatPropertyMediaList($property);

        $vis = $property->address_visibility;
        if (is_string($vis)) {
            $vis = json_decode($vis, true) ?: [];
        }
        if (!is_array($vis) || empty($vis)) {
            $vis = [
                'block'          => true,
                'sector'         => true,
                'road_number'    => true,
                'road_width_ft'  => true,
                'plot_number'    => true,
                'holding_number' => true,
            ];
        }

        $feats = $property->other_features;
        if (is_string($feats)) {
            $feats = json_decode($feats, true) ?: [];
        }
        if (!is_array($feats)) {
            $feats = [];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'property' => [
                    'id'                 => $property->id,
                    'property_code'      => $property->property_code ?: ('AF-' . $property->id),
                    'type'               => $property->type,
                    'title'              => $property->title,
                    'description'        => $property->description ?: '',
                    'fb_caption'         => $property->fb_caption ?: '',
                    'price'              => $property->asking_price,
                    'is_negotiable'      => (int) ($property->is_negotiable ?? 1),
                    'block'              => $property->block ?: 'A',
                    'sector'             => $property->sector ?: '',
                    'road_number'        => $property->road_number ?: '',
                    'road_width_ft'      => $property->road_width_ft ?: 20,
                    'plot_number'        => $property->plot_number ?: '',
                    'holding_number'     => $property->holding_number ?: '',
                    'facing'             => $property->facing ?: 'south',
                    'google_map_pin_url' => $property->google_map_pin_url ?: '',
                    'latitude'           => $property->latitude ?: '',
                    'longitude'          => $property->longitude ?: '',
                    'owner_name'         => $property->owner_name ?: $user->name,
                    'owner_phone'        => $property->owner_phone ?: ($user->whatsapp_number ?: $user->phone),
                    'address_visibility' => $vis,
                    'other_features'     => $feats,
                    'land'               => $property->land,
                    'flat'               => $property->flat,
                    'share'              => $property->share,
                    'commercial'         => $property->commercial,
                ],
                'media'    => $media,
            ],
        ]);
    }

    public function updateProperty(Request $request, Property $property)
    {
        $user = Auth::user();
        if (!$user || ((int) $property->created_by_user_id !== (int) $user->id && !$user->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 403);
        }

        $price = $request->input('price', $property->asking_price);
        $vis = $request->input('address_visibility', $property->address_visibility);
        if (is_string($vis)) {
            $vis = json_decode($vis, true) ?: [];
        }
        $feats = $request->input('other_features', $property->other_features);
        if (is_string($feats)) {
            $feats = json_decode($feats, true) ?: [];
        }

        $property->update([
            'title'              => $request->input('title', $property->title),
            'description'        => $request->input('description', $property->description),
            'fb_caption'         => $request->input('fb_caption', $property->fb_caption),
            'asking_price'       => $price,
            'is_negotiable'      => (bool) $request->input('is_negotiable', $property->is_negotiable),
            'owner_name'         => $request->input('owner_name', $property->owner_name),
            'owner_phone'        => $request->input('owner_phone', $property->owner_phone),
            'block'              => $request->input('block', $property->block),
            'sector'             => $request->input('sector', $property->sector),
            'road_number'        => $request->input('road_number', $property->road_number),
            'road_width_ft'      => $request->input('road_width_ft', $property->road_width_ft),
            'plot_number'        => $request->input('plot_number', $property->plot_number),
            'holding_number'     => $request->input('holding_number', $property->holding_number),
            'facing'             => $request->input('facing', $property->facing),
            'google_map_pin_url' => $request->input('google_map_pin_url', $property->google_map_pin_url),
            'latitude'           => $request->input('latitude', $property->latitude),
            'longitude'          => $request->input('longitude', $property->longitude),
            'address_visibility' => $vis,
            'other_features'     => $feats,
        ]);

        // Category-specific specs
        if ($property->type === 'land') {
            PropertyLandDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'katha_size'          => $request->input('katha_size', 0),
                    'land_type'           => $request->input('land_type', 'residential'),
                    'boundary_wall_done'  => (bool) $request->input('boundary_wall', 0),
                    'soil_filling_status' => $request->input('soil_filling', 'ready'),
                    'is_corner'           => (bool) $request->input('corner_plot', 0),
                    'is_lake_facing'      => (bool) $request->input('lake_facing', 0),
                    'front_road_width_ft' => $request->input('front_road_width_ft', $request->input('road_width_ft', 20)),
                    'price_total'         => $price,
                ]
            );
        } elseif ($property->type === 'flat') {
            PropertyFlatDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'flat_size_sqft'    => $request->input('flat_size_sqft', 0),
                    'floor_number'      => $request->input('floor_number', 1),
                    'total_floors'      => $request->input('total_floors', 6),
                    'bedrooms'          => $request->input('bedrooms', 3),
                    'bathrooms'         => $request->input('bathrooms', 3),
                    'balconies'         => $request->input('balconies', 2),
                    'parking_available' => (bool) $request->input('has_parking', 1),
                    'completion_status' => $request->input('handover_status', 'ready'),
                    'handover_year'     => $request->input('handover_year', date('Y')),
                    'down_payment'      => $request->input('down_payment', 0),
                    'price_total'       => $price,
                ]
            );
        } elseif ($property->type === 'land_share') {
            PropertyShareDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'project_name'            => $request->input('project_name', ''),
                    'developer_name'          => $request->input('developer_name', ''),
                    'land_size_katha'         => $request->input('land_size_katha', 0),
                    'building_stories'        => $request->input('building_stories', ''),
                    'flat_size_sqft'          => $request->input('flat_size_sqft', 0),
                    'bedrooms'                => $request->input('bedrooms', 3),
                    'bathrooms'               => $request->input('bathrooms', 3),
                    'balconies'               => $request->input('balconies', 2),
                    'units_per_floor'         => $request->input('units_per_floor', 1),
                    'total_shares'            => $request->input('total_shares', 0),
                    'available_shares'        => $request->input('available_shares', 0),
                    'has_parking'             => (bool) $request->input('has_parking', 1),
                    'has_drawing_dining'      => (bool) $request->input('has_drawing_dining', 0),
                    'has_family_living'       => (bool) $request->input('has_family_living', 0),
                    'has_lift'                => (bool) $request->input('has_lift', 0),
                    'has_generator'           => (bool) $request->input('has_generator', 0),
                    'has_substation'          => (bool) $request->input('has_substation', 0),
                    'construction_start_date' => $request->input('construction_start_date', ''),
                    'construction_duration'   => $request->input('construction_duration', ''),
                    'current_progress'        => $request->input('current_progress', ''),
                    'location_highlights'     => $request->input('location_highlights', ''),
                    'share_price'             => $price,
                ]
            );
        } elseif ($property->type === 'commercial') {
            PropertyCommercialDetail::updateOrCreate(
                ['property_id' => $property->id],
                [
                    'commercial_type'     => $request->input('commercial_type', 'shop'),
                    'market_name'         => $request->input('market_name', ''),
                    'shop_number'         => $request->input('shop_number', ''),
                    'size_sqft'           => $request->input('size_sqft', 0),
                    'price_per_sqft'      => $request->input('price_per_sqft', 0),
                    'floor_number'        => $request->input('floor_number', 1),
                    'total_floors'        => $request->input('total_floors', 5),
                    'frontage_ft'         => $request->input('frontage_ft', 0),
                    'ceiling_height_ft'   => $request->input('ceiling_height_ft', 0),
                    'completion_status'   => $request->input('completion_status', 'ready'),
                    'monthly_rent_yield'  => $request->input('monthly_rent_yield', 0),
                    'washroom_type'       => $request->input('washroom_type', 'common'),
                    'parking_available'   => (bool) $request->input('has_parking', 0),
                    'has_lift'            => (bool) $request->input('has_lift', 0),
                    'has_escalator'       => (bool) $request->input('has_escalator', 0),
                    'has_generator'       => (bool) $request->input('has_generator', 0),
                    'has_central_ac'      => (bool) $request->input('has_central_ac', 0),
                    'has_cctv'            => (bool) $request->input('has_cctv', 0),
                    'has_fire_safety'     => (bool) $request->input('has_fire_safety', 0),
                    'is_corner'           => (bool) $request->input('is_corner', 0),
                    'is_main_road_facing' => (bool) $request->input('is_main_road_facing', 0),
                    'price_total'         => $price,
                ]
            );
        }

        // YouTube Video Embed
        $yt = $request->input('youtube_video_url');
        if (!empty($yt)) {
            PropertyMedia::updateOrCreate(
                ['property_id' => $property->id, 'media_type' => 'video_embed'],
                ['file_path' => $yt, 'is_cover' => false]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'প্রপার্টি তথ্য ও স্পেসিফিকেশন সফলভাবে সংরক্ষণ করা হয়েছে!',
            'data'    => [
                'property' => $property->fresh(),
            ],
        ]);
    }

    public function uploadPropertyMedia(Request $request, Property $property)
    {
        $user = Auth::user();
        if (!$user || ((int) $property->created_by_user_id !== (int) $user->id && !$user->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 403);
        }

        $files = $request->file('new_images') ?: ($request->file('images') ?: []);
        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'কোনো ছবি পাওয়া যায়নি।'], 422);
        }

        $uploadDir = public_path('uploads/properties');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $hasCover = PropertyMedia::where('property_id', $property->id)->where('is_cover', true)->exists();

        foreach ((array) $files as $file) {
            if (!$file->isValid()) continue;

            $filename = 'prop_' . $property->id . '_' . date('Ymd_His') . '_' . Str::random(6) . '.webp';
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            $relativeUrl = 'uploads/properties/' . $filename;

            $converted = WatermarkService::convertToWebp($file->getRealPath(), $destPath, true);
            if (!$converted) {
                $file->move($uploadDir, $filename);
            }

            $cloudUrl = \App\Services\CloudinaryService::upload($destPath);
            $finalPath = $cloudUrl ?: $relativeUrl;

            PropertyMedia::create([
                'property_id' => $property->id,
                'file_path'   => $finalPath,
                'media_type'  => 'photo',
                'is_cover'    => !$hasCover,
                'sort_order'  => (PropertyMedia::where('property_id', $property->id)->max('sort_order') ?? 0) + 1,
            ]);

            $hasCover = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'ছবি সফলভাবে আপলোড ও অপ্টিমাইজ হয়েছে!',
            'data'    => [
                'media' => $this->formatPropertyMediaList($property),
            ],
        ]);
    }

    public function deletePropertyMedia(Request $request, Property $property, $mediaId)
    {
        $user = Auth::user();
        if (!$user || ((int) $property->created_by_user_id !== (int) $user->id && !$user->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 403);
        }

        $media = PropertyMedia::where('property_id', $property->id)->where('id', $mediaId)->first();
        if ($media) {
            $wasCover = $media->is_cover;
            if ($media->file_path && !Str::startsWith($media->file_path, ['http://', 'https://'])) {
                @unlink(public_path($media->file_path));
            }
            $media->delete();

            if ($wasCover) {
                $next = PropertyMedia::where('property_id', $property->id)->where('media_type', 'photo')->first();
                if ($next) {
                    $next->update(['is_cover' => true]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'ছবি মুছে ফেলা হয়েছে।',
            'data'    => [
                'media' => $this->formatPropertyMediaList($property),
            ],
        ]);
    }

    public function setCoverMedia(Request $request, Property $property, $mediaId)
    {
        $user = Auth::user();
        if (!$user || ((int) $property->created_by_user_id !== (int) $user->id && !$user->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়।'], 403);
        }

        PropertyMedia::where('property_id', $property->id)->update(['is_cover' => false]);
        PropertyMedia::where('property_id', $property->id)->where('id', $mediaId)->update(['is_cover' => true]);

        return response()->json([
            'success' => true,
            'message' => 'কভার ইমেজ সফলভাবে সেট করা হয়েছে!',
            'data'    => [
                'media' => $this->formatPropertyMediaList($property),
            ],
        ]);
    }

    protected function formatPropertyMediaList(Property $property): array
    {
        return $property->fresh()->media->map(function ($m) {
            return [
                'id'         => $m->id,
                'file_path'  => Str::startsWith($m->file_path, ['http://', 'https://']) ? $m->file_path : asset($m->file_path),
                'raw_path'   => $m->file_path,
                'media_type' => $m->media_type,
                'is_cover'   => $m->is_cover ? 1 : 0,
                'caption'    => $m->caption ?: '',
            ];
        })->toArray();
    }

    public static function toBnNumber(int|string $number): string
    {
        $bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $enDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($enDigits, $bnDigits, (string) $number);
    }
}
