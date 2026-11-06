<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyGuideApiKey;
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
            'name'        => 'মো: সাইফুল ইসলাম',
            'phone'       => '01711223344',
            'agency_name' => 'বাটারফ্লাই রিয়েল এস্টেট',
            'role'        => 'agent',
            'rating'      => 4.8,
            'notes'       => 'বসুন্ধরা ও আফতাবনগর অঞ্চলের অভিজ্ঞ এজেন্ট।',
        ];

        $response = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->postJson('/api/v1/agents', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'is_new'  => true,
            'data'    => [
                'name'        => 'মো: সাইফুল ইসলাম',
                'phone'       => '01711223344',
                'agency_name' => 'বাটারফ্লাই রিয়েল এস্টেট',
                'role'        => 'agent',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name'  => 'মো: সাইফুল ইসলাম',
            'phone' => '01711223344',
            'role'  => 'agent',
        ]);

        $this->assertDatabaseHas('agent_profiles', [
            'agency_name' => 'বাটারফ্লাই রিয়েল এস্টেট',
        ]);

        // List agents
        $listResponse = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->getJson('/api/v1/agents?q=সাইফুল');

        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['phone' => '01711223344']);
    }

    public function test_can_create_property_matching_gemini_sheet_row_and_auto_links_agent(): void
    {
        // Payload identical to what Gemini AI extracted in the user's screenshot
        $payload = [
            'place'       => 'bashundhara',
            'block'       => 'F',
            'road'        => '25',
            'road_width'  => '25ft',
            'plot_number' => '4500',
            'katha'       => '4',
            'agent'       => 'Saiful Islam',
            'contact'     => '+880 1924-400307',
            'price'       => '1.55cr',
            'total_price' => '6.2 cr',
            'extra_notes' => 'Just close to Viqarunnisa, asking',
            'status'      => 'directory',
        ];

        $response = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->postJson('/api/v1/properties', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'area'   => 'bashundhara',
                'block'  => 'F',
                'status' => 'draft',
            ],
        ]);

        // Check property created
        $property = Property::where('area', 'bashundhara')->where('block', 'F')->first();
        $this->assertNotNull($property);
        $this->assertEquals('land', $property->type);
        $this->assertStringContainsString('Viqarunnisa', $property->description);

        // Check land detail
        $this->assertNotNull($property->landDetail);
        $this->assertEquals(4.0, (float) $property->landDetail->katha_size);
        $this->assertEquals(15500000, (float) $property->landDetail->price_per_katha);
        $this->assertEquals(62000000, (float) $property->landDetail->price_total);

        // Check agent was automatically created and linked
        $agentUser = User::where('phone', '+880 1924-400307')->first();
        $this->assertNotNull($agentUser);
        $this->assertEquals('Saiful Islam', $agentUser->name);
        $this->assertNotNull($agentUser->agentProfile);

        // Check Direct Source
        $directSource = $property->directSources()->first();
        $this->assertNotNull($directSource);
        $this->assertEquals($agentUser->id, $directSource->source_user_id);
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
