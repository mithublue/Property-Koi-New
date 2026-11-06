<?php

namespace Tests\Feature;

use App\Models\AgentProfile;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyAndAgentApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'test_secret_api_key_2026';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('adm_api_secret_key', $this->apiKey);
    }

    public function test_api_rejects_unauthorized_requests(): void
    {
        $this->postJson('/api/v1/properties', [])->assertStatus(401);
        $this->postJson('/api/v1/agents', [])->assertStatus(401);

        $this->withHeaders(['X-API-KEY' => 'wrong_key'])
            ->postJson('/api/v1/properties', [])
            ->assertStatus(401);
    }

    public function test_can_create_and_list_agents_via_api(): void
    {
        $payload = [
            'name'                       => 'মো: সাইফুল ইসলাম',
            'phone'                      => '01711223344',
            'whatsapp_number'            => '01711223344',
            'agency_name'                => 'বাটারফ্লাই রিয়েল এস্টেট',
            'license_no'                 => 'RE-DHK-2026-981',
            'experience_years'           => 8,
            'direct_owner_network_count' => 45,
            'role'                       => 'agent',
            'rating'                     => 4.8,
            'is_verified_direct_agent'   => true,
            'notes'                      => 'বসুন্ধরা ও আফতাবনগর অঞ্চলের অভিজ্ঞ এজেন্ট।',
        ];

        $response = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->postJson('/api/v1/agents', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'is_new'  => true,
            'data'    => [
                'name'                       => 'মো: সাইফুল ইসলাম',
                'phone'                      => '01711223344',
                'agency_name'                => 'বাটারফ্লাই রিয়েল এস্টেট',
                'license_no'                 => 'RE-DHK-2026-981',
                'experience_years'           => 8,
                'direct_owner_network_count' => 45,
                'role'                       => 'agent',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name'  => 'মো: সাইফুল ইসলাম',
            'phone' => '01711223344',
            'role'  => 'agent',
        ]);

        $this->assertDatabaseHas('agent_profiles', [
            'agency_name' => 'বাটারফ্লাই রিয়েল এস্টেট',
            'license_no'  => 'RE-DHK-2026-981',
        ]);

        // List agents
        $listResponse = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->getJson('/api/v1/agents?q=সাইফুল');

        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['phone' => '01711223344']);
    }

    public function test_can_create_property_using_exact_sell_property_json_schema(): void
    {
        // Exact JSON format provided by user from Sell Property Form
        $payload = [
            'owner_name'               => 'ইঞ্জিনিয়ার মো: আরিফ',
            'owner_phone'              => '01811998877',
            'source_type'              => 'owner',
            'relationship_declaration' => 'আমি এই প্লটের মূল মালিক',
            'property_type'            => 'land',
            'area'                     => 'Aftabnagar',
            'custom_area'              => '',
            'sub_area'                 => 'Block C',
            'title'                    => 'আফতাবনগর C ব্লকে ৫ কাঠা আকর্ষণীয় দক্ষিণমুখী প্লট বিক্রয়',
            'block'                    => 'C',
            'sector'                   => 'Sector 2',
            'road_number'              => '12',
            'road_width_ft'            => '25',
            'plot_number'              => '305',
            'holding_number'           => '14/A',
            'hidden_note'              => 'মালিকের সাথে সরাসরি কথা হয়েছে, পেপারস ১০০% ফ্রেশ।',
            'address_visibility'       => [
                'block'          => 'true',
                'sector'         => 'true',
                'road_number'    => 'true',
                'road_width_ft'  => 'true',
                'plot_number'    => 'true',
                'holding_number' => 'false',
            ],
            'facing'                   => 'south',
            'size'                     => '5',
            'price_per_katha'          => '1.4cr',
            'flat_size_sqft'           => '',
            'price_per_sqft'           => '',
            'garage_price'             => '',
            'utility_price'            => '',
            'down_payment'             => '',
            'asking_price'             => '7cr',
            'is_negotiable'            => '1',
            'description'              => 'আফতাবনগর সি ব্লকে ৫ কাঠা চমৎকার দক্ষিণমুখী আবাসিক প্লট।',
            'fb_caption'               => 'আফতাবনগর সি ব্লকে ৫ কাঠা রেডি প্লট!',
            'post_to_facebook'         => '0',
            'youtube_video_url'        => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'google_map_pin_url'       => 'https://maps.google.com/?q=23.766,90.435',
            'latitude'                 => '23.766',
            'longitude'                => '90.435',
            'is_corner'                => '0',
            'is_lake_facing'           => '1',
            'boundary_wall_done'       => '1',
            'soil_filling_status'      => 'filled',
            'other_features'           => [
                'বিদ্যুৎ সংযোগ',
                'ওয়াইড ড্রেনেজ ব্যবস্থা',
                'নিরাপদ আবাসিক পরিবেশ',
            ],
        ];

        $response = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->postJson('/api/v1/properties', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'area'  => 'Aftabnagar',
                'block' => 'C',
                'type'  => 'land',
            ],
        ]);

        $property = Property::where('area', 'Aftabnagar')->where('block', 'C')->first();
        $this->assertNotNull($property);
        $this->assertEquals('land', $property->type);
        $this->assertEquals(25, $property->road_width_ft);
        $this->assertEquals('south', $property->facing);
        $this->assertEquals('মালিকের সাথে সরাসরি কথা হয়েছে, পেপারস ১০০% ফ্রেশ।', $property->hidden_note);
        $this->assertCount(3, $property->other_features);

        // Check Land Detail
        $this->assertNotNull($property->landDetail);
        $this->assertEquals(5.0, (float) $property->landDetail->katha_size);
        $this->assertEquals(14000000, (float) $property->landDetail->price_per_katha);
        $this->assertEquals(70000000, (float) $property->landDetail->price_total);
        $this->assertTrue($property->landDetail->is_lake_facing);
        $this->assertTrue($property->landDetail->boundary_wall_done);

        // Check YouTube Video Media
        $this->assertDatabaseHas('property_media', [
            'property_id' => $property->id,
            'media_type'  => 'video_embed',
            'file_path'   => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        // Check Owner user was auto-created and linked
        $ownerUser = User::where('phone', '01811998877')->first();
        $this->assertNotNull($ownerUser);
        $this->assertEquals('ইঞ্জিনিয়ার মো: আরিফ', $ownerUser->name);
        $this->assertEquals('owner', $ownerUser->role);

        $directSource = $property->directSources()->first();
        $this->assertNotNull($directSource);
        $this->assertEquals($ownerUser->id, $directSource->source_user_id);
        $this->assertEquals('আমি এই প্লটের মূল মালিক', $directSource->relationship_declaration);
    }

    public function test_can_create_flat_property_via_api(): void
    {
        $payload = [
            'owner_name'        => 'তানভীর আহমেদ',
            'owner_phone'       => '01799881122',
            'source_type'       => 'agent',
            'property_type'     => 'flat',
            'area'              => 'Bashundhara',
            'block'             => 'D',
            'flat_size_sqft'    => '1650',
            'price_per_sqft'    => '9500',
            'garage_price'      => '500000',
            'utility_price'     => '300000',
            'bedrooms'          => '3',
            'bathrooms'         => '3',
            'balconies'         => '2',
            'floor_number'      => '4',
            'completion_status' => 'ready',
            'has_lift'          => true,
            'parking_available' => true,
            'has_generator'     => true,
            'has_gas'           => true,
        ];

        $response = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->postJson('/api/v1/properties', $payload);

        $response->assertStatus(201);

        $property = Property::where('area', 'Bashundhara')->where('block', 'D')->first();
        $this->assertNotNull($property);
        $this->assertEquals('flat', $property->type);
        $this->assertNotNull($property->flatDetail);
        $this->assertEquals(1650, (float) $property->flatDetail->flat_size_sqft);
        $this->assertEquals(9500, (float) $property->flatDetail->price_per_sqft);
        // Asking price = (1650 * 9500) + 500000 + 300000 = 15675000 + 800000 = 16475000
        $this->assertEquals(16475000, (float) $property->flatDetail->price_total);
        $this->assertTrue($property->flatDetail->has_lift);
        $this->assertTrue($property->flatDetail->parking_available);
        $this->assertTrue($property->flatDetail->has_gas);
    }

    public function test_can_bulk_create_properties_from_google_sheet(): void
    {
        $payload = [
            'properties' => [
                [
                    'place'       => 'uttor khan',
                    'katha'       => '100*20 katha',
                    'agent'       => 'Saiful Islam',
                    'contact'     => '01700112233',
                    'extra_notes' => 'Bulk row 1',
                ],
                [
                    'place'       => 'rampura',
                    'katha'       => '6 katha',
                    'price'       => '2cr',
                    'total_price' => '12 cr',
                    'extra_notes' => 'Bulk row 2',
                ],
            ],
        ];

        $response = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->postJson('/api/v1/properties/bulk', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success'     => true,
            'total_input' => 2,
            'total_saved' => 2,
        ]);

        $this->assertDatabaseHas('properties', ['area' => 'uttor khan']);
        $this->assertDatabaseHas('properties', ['area' => 'rampura']);
    }
}
