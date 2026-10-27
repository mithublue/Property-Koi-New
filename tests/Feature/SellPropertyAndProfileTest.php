<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyCommercialDetail;
use App\Models\PropertyFlatDetail;
use App\Models\PropertyLandDetail;
use App\Models\PropertyShareDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellPropertyAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_sell_property_page_renders_successfully()
    {
        $this->get('/sell-property?role=owner')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/sell-property?role=owner');
        $response->assertStatus(200);
        $response->assertSee('প্রপার্টি মালিক মোড');
        $response->assertSee('Smart Auto-Fill');
        $response->assertSee('JSON Engine');
        $response->assertSee('প্রপার্টি সাবমিট করুন');
    }

    public function test_land_property_submission_via_json_endpoint()
    {
        $user = User::factory()->create();

        $payload = [
            'property_type'      => 'land',
            'title'              => 'টেস্ট আফতাবনগর ৩ কাঠার দক্ষিণমুখী প্লট',
            'area'               => 'Aftabnagar',
            'block'              => 'F',
            'sector'             => 'সেক্টর-৩',
            'road_number'        => '7/A',
            'road_width_ft'      => 30,
            'plot_number'        => '450',
            'facing'             => 'south',
            'size'               => 3.5,
            'price_per_katha'    => 12000000,
            'asking_price'       => 42000000,
            'is_negotiable'      => 1,
            'is_corner'          => 1,
            'boundary_wall_done' => 1,
            'owner_name'         => 'টেস্ট ওনার',
            'owner_phone'        => '01700000000',
            'description'        => 'চমৎকার লোকেশনে ৩.৫ কাঠার প্লট।',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('property.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('properties', [
            'type'  => 'land',
            'title' => 'টেস্ট আফতাবনগর ৩ কাঠার দক্ষিণমুখী প্লট',
            'block' => 'F',
        ]);

        $property = Property::where('title', 'টেস্ট আফতাবনগর ৩ কাঠার দক্ষিণমুখী প্লট')->first();
        $this->assertNotNull($property);

        $this->assertDatabaseHas('property_land_details', [
            'property_id' => $property->id,
            'katha_size'  => 3.5,
            'price_total' => 42000000,
            'is_corner'   => 1,
        ]);

        $this->assertDatabaseHas('property_direct_sources', [
            'property_id' => $property->id,
            'owner_name'  => 'টেস্ট ওনার',
        ]);
    }

    public function test_flat_property_submission()
    {
        $user = User::factory()->create();

        $payload = [
            'property_type'     => 'flat',
            'title'             => 'আফতাবনগর ই ব্লকের ১৭৫০ বর্গফুটের রেডি ফ্ল্যাট',
            'area'              => 'Aftabnagar',
            'block'             => 'E',
            'road_width_ft'     => 25,
            'facing'            => 'south',
            'flat_size_sqft'    => 1750,
            'price_per_sqft'    => 6000,
            'asking_price'      => 10500000,
            'bedrooms'          => 3,
            'bathrooms'         => 3,
            'floor_number'      => 4,
            'parking_available' => 1,
            'has_lift'          => 1,
            'owner_name'        => 'ফ্ল্যাট মালিক',
            'owner_phone'       => '01800000000',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('property.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $property = Property::where('title', 'আফতাবনগর ই ব্লকের ১৭৫০ বর্গফুটের রেডি ফ্ল্যাট')->first();
        $this->assertNotNull($property);

        $this->assertDatabaseHas('property_flat_details', [
            'property_id'    => $property->id,
            'flat_size_sqft' => 1750,
            'bedrooms'       => 3,
        ]);
    }

    public function test_buyer_dashboard_profile_avatar_upload_and_persistence()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name'  => 'Mithu Buyer',
            'email' => 'mithu_buyer_test@example.com',
            'phone' => '01711223344',
        ]);

        $avatarFile = UploadedFile::fake()->image('my_avatar.png', 200, 200);

        $response = $this->actingAs($user)
            ->post(route('buyer.dashboard.profile'), [
                'displayName'     => 'Mithu Buyer Updated',
                'email'           => 'mithu_buyer_test@example.com',
                'phone'           => '01711223344',
                'whatsappNumber'  => '01711223344',
                'preferredType'   => 'land',
                'preferredBlocks' => 'C, F',
                'budgetRange'     => '1-2 crore',
                'avatar'          => $avatarFile,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $user->refresh();
        $this->assertEquals('Mithu Buyer Updated', $user->name);
        $this->assertNotNull($user->avatar_url);
        $this->assertStringContainsString('avatars/avatar_' . $user->id, $user->avatar_url);
    }

    public function test_admin_settings_page_renders_cleanly()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('adm_developer_mode');
    }

    public function test_sell_property_flat_with_other_features_json_and_address_visibility_stores_successfully()
    {
        $payload = [
            'property_type'      => 'flat',
            'title'              => 'দক্ষিণ বনশ্রী এফ ব্লক ১০ নম্বর রোডে রেডি ১২৮০ স্কয়ারফিটের ফ্ল্যাট বিক্রয়',
            'area'               => 'South Banasree',
            'block'              => 'F',
            'road_number'        => '10',
            'road_width_ft'      => 20,
            'flat_size_sqft'     => 1280,
            'price_per_sqft'     => 5800,
            'asking_price'       => 7500000,
            'bedrooms'           => 3,
            'bathrooms'          => 3,
            'balconies'          => 2,
            'address_visibility' => json_encode(['block' => '', 'sector' => '', 'road_number' => '']),
            'other_features'     => json_encode([['label' => 'লিফট সুবিধা', 'checked' => true], ['label' => 'জেনারেটর', 'checked' => true]]),
            'description'        => 'টেস্ট ডেসক্রিপশন',
            'owner_phone'        => '01711223344',
            'post_to_facebook'   => '0',
        ];

        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('property.store'), $payload);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('properties', [
            'title' => 'দক্ষিণ বনশ্রী এফ ব্লক ১০ নম্বর রোডে রেডি ১২৮০ স্কয়ারফিটের ফ্ল্যাট বিক্রয়',
            'type'  => 'flat',
            'area'  => 'South Banasree',
            'block' => 'F',
        ]);
    }

    public function test_property_detail_shows_four_action_buttons_mobile_drawer_and_hides_owner_info()
    {
        $property = Property::create([
            'property_code' => 'PK-TEST01',
            'title'         => 'টেস্ট সিঙ্গেল প্রপার্টি',
            'slug'          => 'test-single-property-unique-123',
            'type'          => 'flat',
            'area'          => 'Aftabnagar',
            'block'         => 'F',
            'is_negotiable' => true,
            'status'        => 'live',
        ]);

        \App\Models\PropertyDirectSource::create([
            'property_id'              => $property->id,
            'source_type'              => 'direct_owner',
            'relationship_declaration' => 'সরাসরি মালিক',
            'owner_name'               => 'গোপন মালিকের নাম',
            'owner_phone'              => '01999888777',
            'asking_price'             => 5000000,
            'is_primary_source'        => true,
            'verification_status'      => 'verified',
        ]);

        $response = $this->get('/property/' . $property->slug);
        $response->assertStatus(200);

        // 1. MUST NOT show owner's private phone or direct call to owner
        $response->assertDontSee('01999888777');
        $response->assertDontSee('গোপন মালিকের নাম');
        $response->assertDontSee('সরাসরি কল করুন');

        // 2. MUST show the 4 action buttons
        $response->assertSee('সাইট ভিজিট শিডিউল করুন');
        $response->assertSee('আরও তথ্য জানতে চান?');
        $response->assertSee('দর প্রস্তাব বা অফার দিন');
        $response->assertSee('হোয়াটসঅ্যাপে যোগাযোগ করুন');

        // 3. MUST show the WP-matching top header card badges & price
        $response->assertSee('PK-TEST01');
        $response->assertSee('Aftabnagar');
        $response->assertSee('ব্লক F');
        $response->assertSee('আলোচনা সাপেক্ষ');
        $response->assertSee('টেস্ট সিঙ্গেল প্রপার্টি');

        // 4. MUST show the mobile right-edge floating drawer trigger
        $response->assertSee('contactDrawerOpen');
        $response->assertSee('যোগাযোগ করুন');

        // 5. Test slots endpoint
        $slotsRes = $this->getJson(route('property.slots.available', ['month' => now()->month, 'year' => now()->year]));
        $slotsRes->assertStatus(200);
        $slotsRes->assertJsonStructure(['success', 'data' => ['slots_by_date']]);

        // 5. Test lead enquiry endpoint
        $leadRes = $this->postJson(route('property.lead', $property->slug), [
            'buyer_name'   => 'আকরাম খান',
            'buyer_phone'  => '01711002233',
            'enquiry_type' => 'more_info',
            'message'      => 'ফ্লোর প্ল্যান দেখতে চাই',
        ]);
        $leadRes->assertStatus(200);
        $leadRes->assertJson(['success' => true]);
        $this->assertDatabaseHas('leads', [
            'property_id' => $property->id,
            'buyer_name'  => 'আকরাম খান',
            'buyer_phone' => '01711002233',
        ]);
    }

    public function test_sell_property_form_and_admin_forms_have_address_visibility_show_by_default()
    {
        $user = User::factory()->create();

        // 1. Frontend form page displays '👁️ শো' as default state for visibility toggles
        $sellFormRes = $this->actingAs($user)->get(route('property.create'));
        $sellFormRes->assertStatus(200);
        $sellFormRes->assertSee('👁️ শো');

        // 2. Submitting property without overriding visibility defaults to all true
        $payload = [
            'title'         => 'টেস্ট ডিফল্ট শো ভিজিবিলিটি প্রপার্টি',
            'property_type' => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'B',
            'road_number'   => '4',
            'plot_number'   => '21',
            'size'          => 5.0,
            'price_total'   => 12000000,
            'owner_phone'   => '01711223344',
        ];
        $res = $this->actingAs($user)->postJson(route('property.store'), $payload);
        $res->assertStatus(200);

        $saved = Property::where('title', 'টেস্ট ডিফল্ট শো ভিজিবিলিটি প্রপার্টি')->first();
        $this->assertNotNull($saved);
        $this->assertTrue($saved->isAddressVisible('block'));
        $this->assertTrue($saved->isAddressVisible('road_number'));
        $this->assertTrue($saved->isAddressVisible('plot_number'));
        $this->assertTrue($saved->isAddressVisible('road_width_ft'));
    }

    public function test_property_creation_fails_without_owner_phone()
    {
        $user = User::factory()->create();

        $payload = [
            'property_type' => 'land',
            'title'         => 'ফোন নম্বর ছাড়া প্রপার্টি',
            'area'          => 'Aftabnagar',
            'block'         => 'A',
            'size'          => 3.0,
            'asking_price'  => 5000000,
            // owner_phone is deliberately omitted
        ];

        $res = $this->actingAs($user)->postJson(route('property.store'), $payload);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['owner_phone']);
    }

    public function test_logged_out_users_do_not_see_post_ad_buttons()
    {
        // Guest visiting homepage
        $guestHome = $this->get(route('home'));
        $guestHome->assertStatus(200);
        $guestHome->assertDontSeeText('বিজ্ঞাপন দিন');
        $guestHome->assertDontSeeText('ফ্রি প্রপার্টি বিজ্ঞাপন দিন');
        $guestHome->assertDontSeeText('প্রপার্টি পোস্ট করুন (Post Ad)');
        $guestHome->assertDontSee(route('property.create'));

        // Logged-in user visiting homepage
        $user = User::factory()->create();
        $authHome = $this->actingAs($user)->get(route('home'));
        $authHome->assertStatus(200);
        $authHome->assertSeeText('বিজ্ঞাপন দিন');
        $authHome->assertSee(route('property.create'));
    }
}
