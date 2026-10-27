<?php

namespace Database\Seeders;

use App\Models\AgentProfile;
use App\Models\BuyerDemand;
use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyDirectSource;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyMedia;
use App\Models\PropertyShareDetail;
use App\Models\PropertyVerification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::firstOrCreate(['email' => 'admin@propertykoi.com'], [
            'name' => 'PropertyKoi Admin',
            'phone' => '01711000001',
            'role' => 'admin',
            'is_verified' => true,
            'password' => Hash::make('password'),
        ]);

        $owner = User::firstOrCreate(['email' => 'owner@propertykoi.com'], [
            'name' => 'এম. এ. রশীদ (মালিক)',
            'phone' => '01711223344',
            'role' => 'owner',
            'is_verified' => true,
            'password' => Hash::make('password'),
        ]);

        $agent = User::firstOrCreate(['email' => 'agent@propertykoi.com'], [
            'name' => 'আফতাবনগর রিয়েল এস্টেট কেয়ার',
            'phone' => '01811556677',
            'role' => 'agent',
            'is_verified' => true,
            'password' => Hash::make('password'),
        ]);

        AgentProfile::firstOrCreate(['user_id' => $agent->id], [
            'agency_name' => 'আফতাবনগর রিয়েলটি পার্টনার্স',
            'license_no' => 'RAJUK-AFT-892',
            'experience_years' => 8,
            'direct_owner_network_count' => 35,
            'rating' => 4.9,
            'is_verified_direct_agent' => true,
            'verified_at' => now(),
        ]);

        // 2. Property 1: Land (Corner Lake Facing Plot)
        $p1 = Property::create([
            'property_code' => 'PK-L101',
            'type' => 'land',
            'title' => 'আফতাবনগর ব্লক সি-তে ৫ কাঠার প্রাইম কর্নার লেকভিউ প্লট',
            'slug' => 'aftabnagar-block-c-5-katha-corner-lake-plot',
            'description' => "আফতাবনগর সি ব্লকের অত্যন্ত আকর্ষণীয় ৫০ ফুট রোডের ওপর ৫ কাঠার দক্ষিণমুখী কর্নার প্লট। প্লটের সামনে সরাসরি লেকভিউ রয়েছে। মাটি শতভাগ ভরাট করা ও সীমানা চিহ্নিত। যেকোনো সময় বাড়ি নির্মাণের উপযোগী।",
            'area' => 'Aftabnagar',
            'sub_area' => 'ব্লক সি',
            'block' => 'C',
            'road_width_ft' => 50,
            'plot_number' => '214',
            'holding_number' => '12',
            'facing' => 'south',
            'status' => 'live',
            'is_featured' => true,
            'is_best_deal' => true,
            'is_negotiable' => true,
            'views_count' => 142,
            'created_by_user_id' => $owner->id,
        ]);

        PropertyLandDetail::create([
            'property_id' => $p1->id,
            'katha_size' => 5.0,
            'price_total' => 37500000.00,
            'price_per_katha' => 7500000.00,
            'is_corner' => true,
            'is_park_facing' => false,
            'is_lake_facing' => true,
            'boundary_wall_done' => true,
            'soil_filling_status' => 'filled',
            'demarcation_ready' => true,
        ]);

        PropertyDirectSource::create([
            'property_id' => $p1->id,
            'source_user_id' => $owner->id,
            'source_type' => 'direct_owner',
            'relationship_declaration' => 'আমি এই প্লটের মূল ও সরাসরি মালিক',
            'owner_name' => $owner->name,
            'owner_phone' => $owner->phone,
            'asking_price' => 37500000.00,
            'is_primary_source' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        PropertyMedia::create([
            'property_id' => $p1->id,
            'file_path' => 'assets/images/og-land.jpg',
            'media_type' => 'photo',
            'is_cover' => true,
        ]);

        // 3. Property 2: Flat (Ready Luxury Apartment)
        $p2 = Property::create([
            'property_code' => 'PK-F202',
            'type' => 'flat',
            'title' => 'ব্লক বি-তে ১৪৫০ বর্গফুটের শতভাগ রেডি দক্ষিণমুখী লাক্সারি ফ্ল্যাট',
            'slug' => 'block-b-1450-sqft-ready-luxury-flat',
            'description' => "সম্পূর্ণ প্রস্তুত ১৪৫০ বর্গফুটের ব্র্যান্ড নিউ অ্যাপার্টমেন্ট। ৩ বেডরুম, ৩ বাথরুম, ৩ বারান্দা। আধুনিক ফিটিংস, ডাবল লিফট, ফুল জেনারেটর ব্যাকআপ ও নিচতলায় নির্ধারিত কার পার্কিং সুবিধা সহ। গ্যাস সংযোগ ও আধুনিক সুরক্ষা ব্যবস্থা রয়েছে।",
            'area' => 'Aftabnagar',
            'sub_area' => 'ব্লক বি',
            'block' => 'B',
            'road_width_ft' => 30,
            'plot_number' => '45',
            'holding_number' => '8B',
            'facing' => 'south',
            'status' => 'live',
            'is_featured' => true,
            'is_best_deal' => false,
            'is_negotiable' => true,
            'views_count' => 98,
            'created_by_user_id' => $owner->id,
        ]);

        PropertyFlatDetail::create([
            'property_id' => $p2->id,
            'flat_size_sqft' => 1450.00,
            'bedrooms' => 3,
            'bathrooms' => 3,
            'balconies' => 3,
            'floor_number' => 4,
            'total_floors' => 8,
            'price_total' => 11600000.00,
            'price_per_sqft' => 8000.00,
            'completion_status' => 'ready',
            'parking_available' => true,
            'has_lift' => true,
            'has_generator' => true,
            'has_gas' => true,
        ]);

        PropertyDirectSource::create([
            'property_id' => $p2->id,
            'source_user_id' => $owner->id,
            'source_type' => 'direct_owner',
            'relationship_declaration' => 'আমি অ্যাপার্টমেন্টটির একক মালিক',
            'owner_name' => 'ডাঃ সাজ্জাদ হোসেন',
            'owner_phone' => '01712998877',
            'asking_price' => 11600000.00,
            'is_primary_source' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        PropertyMedia::create([
            'property_id' => $p2->id,
            'file_path' => 'assets/images/og-flat.jpg',
            'media_type' => 'photo',
            'is_cover' => true,
        ]);

        // 4. Property 3: Commercial Shop (Ground Floor Prime Shop)
        $p3 = Property::create([
            'property_code' => 'PK-C303',
            'type' => 'commercial',
            'title' => 'আফতাবনগর মেইন রোডের শপিং কমপ্লেক্সে গ্রাউন্ড ফ্লোরের রেডি দোকান',
            'slug' => 'aftabnagar-main-road-ground-floor-prime-shop',
            'description' => "আফতাবনগর প্রধান প্রবেশমুখে প্রতিষ্ঠিত আধুনিক শপিং কমপ্লেক্সের নিচতলায় ৩৫০ বর্গফুটের রানিং কমার্শিয়াল শপ। মাসিক নিশ্চিত ভাড়া পাওয়ার দারুণ সুযোগ অথবা যেকোনো ব্র্যান্ড আউটলেটের জন্য শতভাগ আদর্শ।",
            'area' => 'Aftabnagar',
            'sub_area' => 'মেইন রোড',
            'block' => 'A',
            'road_width_ft' => 60,
            'facing' => 'east',
            'status' => 'live',
            'is_featured' => true,
            'is_best_deal' => true,
            'is_negotiable' => true,
            'views_count' => 64,
            'created_by_user_id' => $owner->id,
        ]);

        PropertyCommercialDetail::create([
            'property_id' => $p3->id,
            'size_sqft' => 350.00,
            'commercial_type' => 'shop',
            'market_name' => 'আফতাবনগর সিটি সেন্টার',
            'shop_number' => 'G-14',
            'floor_number' => 'গ্রাউন্ড ফ্লোর',
            'total_floors' => 6,
            'price_total' => 8500000.00,
            'price_per_sqft' => 24285.00,
            'monthly_rent_yield' => 45000.00,
            'completion_status' => 'ready',
            'has_lift' => true,
            'has_generator' => true,
            'has_central_ac' => true,
        ]);

        PropertyDirectSource::create([
            'property_id' => $p3->id,
            'source_user_id' => $owner->id,
            'source_type' => 'direct_owner',
            'relationship_declaration' => 'দোকানটি আমার নিজস্ব মালিকানাধীন',
            'owner_name' => 'কামরুল হাসান',
            'owner_phone' => '01911334455',
            'asking_price' => 8500000.00,
            'is_primary_source' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        PropertyMedia::create([
            'property_id' => $p3->id,
            'file_path' => 'assets/images/og-commercial.jpg',
            'media_type' => 'photo',
            'is_cover' => true,
        ]);

        // 5. Property 4: Land Share Project
        $p4 = Property::create([
            'property_code' => 'PK-S404',
            'type' => 'land_share',
            'title' => 'ব্লক ডি-তে যৌথ ল্যান্ড শেয়ার প্রজেক্টে ১২৫০ sqft ফ্ল্যাটের শেয়ার',
            'slug' => 'block-d-land-share-1250sqft-flat-share',
            'description' => "আফতাবনগর ডি ব্লকে ৫ কাঠা জমির ওপর জি+৯ তলা নান্দনিক ভবনে সীমিত সংখ্যক শেয়ার বরাদ্দ চলছে। যৌথ অর্থায়নে নিজের স্বপ্নের ফ্ল্যাট গড়ার সেরা বিশ্বস্ত প্ল্যাটফর্ম। কোনো হিডেন খরচ ছাড়াই কিস্তিতে ফ্ল্যাটের মালিক হন।",
            'area' => 'Aftabnagar',
            'sub_area' => 'ব্লক ডি',
            'block' => 'D',
            'road_width_ft' => 25,
            'facing' => 'south',
            'status' => 'live',
            'is_featured' => false,
            'is_best_deal' => true,
            'is_negotiable' => false,
            'views_count' => 110,
            'created_by_user_id' => $agent->id,
        ]);

        PropertyShareDetail::create([
            'property_id' => $p4->id,
            'project_name' => 'গ্রিন ভিউ শেয়ার হাইটস',
            'land_size_katha' => 5.0,
            'building_stories' => 'G+9',
            'flat_size_sqft' => 1250.00,
            'share_price' => 3800000.00,
            'total_shares' => 18,
            'available_shares' => 3,
            'has_lift' => true,
            'has_generator' => true,
            'has_parking' => true,
            'construction_start_date' => 'নভেম্বর ২০২৬',
        ]);

        PropertyDirectSource::create([
            'property_id' => $p4->id,
            'source_user_id' => $agent->id,
            'source_type' => 'direct_agent',
            'relationship_declaration' => 'প্রজেক্ট ডেভেলপমেন্ট অথরাইজড পার্টনার',
            'owner_name' => 'ইঞ্জিঃ রফিকুল আলম',
            'owner_phone' => '01811556677',
            'asking_price' => 3800000.00,
            'is_primary_source' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        PropertyMedia::create([
            'property_id' => $p4->id,
            'file_path' => 'assets/images/og-commercial.jpg',
            'media_type' => 'photo',
            'is_cover' => true,
        ]);

        // 6. Sample Buyer Demand for Testing Matcher
        BuyerDemand::create([
            'requirement_code' => 'REQ-DEMO01',
            'buyer_name' => 'তানভীর চৌধুরী',
            'buyer_phone' => '01715001122',
            'buyer_email' => 'tanvir@example.com',
            'property_type' => 'land',
            'preferred_areas' => ['Aftabnagar'],
            'preferred_blocks' => ['C', 'D'],
            'min_road_width_ft' => 20,
            'min_size' => 3.0,
            'max_size' => 6.0,
            'min_budget' => 20000000.00,
            'max_budget' => 45000000.00,
            'notify_channels' => 'push,email,telegram',
            'status' => 'active',
            'unsubscribe_token' => Str::random(32),
        ]);

        // 7. Comprehensive Real Estate Knowledge Hub & Guides (High Authority SEO & AEO)
        $this->call(GuideSeeder::class);
    }
}
