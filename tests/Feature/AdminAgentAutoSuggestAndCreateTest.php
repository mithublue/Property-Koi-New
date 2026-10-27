<?php

namespace Tests\Feature;

use App\Models\AgentProfile;
use App\Models\Property;
use App\Models\PropertyDirectSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAgentAutoSuggestAndCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected User $existingAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '01711999999',
            'email' => 'admin@propertykoi.test',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'buyer',
            'phone' => '01711888888',
            'email' => 'buyer@propertykoi.test',
        ]);

        $this->existingAgent = User::factory()->create([
            'name' => 'করিম আহমেদ',
            'role' => 'agent',
            'phone' => '01711122233',
            'whatsapp_number' => '01711122233',
            'email' => 'karim@propertykoi.test',
        ]);

        AgentProfile::create([
            'user_id' => $this->existingAgent->id,
            'agency_name' => 'ঢাকা রিয়েল এস্টেট',
            'admin_fixed_rating' => 4.8,
            'is_verified_direct_agent' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_regular_user_cannot_see_agent_assignment_fields()
    {
        $response = $this->actingAs($this->regularUser)->get(route('property.create'));
        $response->assertStatus(200);
        $response->assertDontSeeText('দায়িত্বপ্রাপ্ত এজেন্ট');
        $response->assertDontSeeText('এজেন্টের হোয়াটসঅ্যাপ নম্বর');
    }

    public function test_guest_cannot_access_sell_property_page_and_is_redirected_to_login()
    {
        $response = $this->get(route('property.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_sees_agent_assignment_fields()
    {
        $response = $this->actingAs($this->admin)->get(route('property.create'));
        $response->assertStatus(200);
        $response->assertSeeText('দায়িত্বপ্রাপ্ত এজেন্ট');
        $response->assertSeeText('এজেন্টের হোয়াটসঅ্যাপ নম্বর');
    }

    public function test_agent_search_endpoint_requires_admin_access()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('admin.agents.search', ['q' => 'করিম']));
        $response->assertStatus(403);
    }

    public function test_admin_can_search_agents_via_ajax()
    {
        // Search by name
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.agents.search', ['q' => 'করিম']));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $this->existingAgent->id,
            'name' => 'করিম আহমেদ',
            'whatsapp_number' => '01711122233',
            'agency_name' => 'ঢাকা রিয়েল এস্টেট',
        ]);

        // Search by phone
        $phoneResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.agents.search', ['q' => '01711122233']));
        $phoneResponse->assertStatus(200);
        $phoneResponse->assertJsonFragment([
            'id' => $this->existingAgent->id,
            'name' => 'করিম আহমেদ',
        ]);

        // Search empty
        $emptyResponse = $this->actingAs($this->admin)
            ->getJson(route('admin.agents.search', ['q' => '']));
        $emptyResponse->assertStatus(200);
        $this->assertEmpty($emptyResponse->json());
    }

    public function test_admin_creating_property_with_existing_agent_links_to_agent()
    {
        $payload = [
            'property_type'   => 'land',
            'title'           => 'প্লট টেস্ট এক্সিস্টিং এজেন্ট',
            'area'            => 'Aftabnagar',
            'block'           => 'B',
            'owner_name'      => 'প্লট মালিক আব্দুল্লাহ',
            'owner_phone'     => '01799887766',
            'size'            => 5.0,
            'price_per_katha' => 8000000,
            'asking_price'    => 40000000,
            'agent_id'        => $this->existingAgent->id,
            'agent_name'      => $this->existingAgent->name,
            'agent_whatsapp'  => $this->existingAgent->whatsapp_number,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('property.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $property = Property::latest('id')->first();
        $this->assertNotNull($property);
        $this->assertEquals($this->existingAgent->id, $property->created_by_user_id);

        $directSource = $property->directSources()->first();
        $this->assertNotNull($directSource);
        $this->assertEquals($this->existingAgent->id, $directSource->source_user_id);
        $this->assertEquals('direct_agent', $directSource->source_type);
        $this->assertEquals('প্লট মালিক আব্দুল্লাহ', $directSource->owner_name);
        $this->assertEquals('01799887766', $directSource->owner_phone);
    }

    public function test_admin_creating_property_with_new_agent_auto_creates_agent()
    {
        $newAgentName = 'রফিকুল ইসলাম';
        $newAgentWhatsapp = '01855667788';

        $this->assertFalse(User::where('phone', $newAgentWhatsapp)->exists());

        $payload = [
            'property_type'   => 'land',
            'title'           => 'প্লট টেস্ট নতুন এজেন্ট অটো ক্রিয়েট',
            'area'            => 'Banasree',
            'block'           => 'C',
            'owner_phone'     => '01700112233',
            'size'            => 4.0,
            'asking_price'    => 25000000,
            'agent_name'      => $newAgentName,
            'agent_whatsapp'  => $newAgentWhatsapp,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('property.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify new user was created with role agent
        $createdUser = User::where('phone', $newAgentWhatsapp)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($newAgentName, $createdUser->name);
        $this->assertEquals('agent', $createdUser->role);
        $this->assertTrue((bool) $createdUser->is_verified);
        $this->assertNotEmpty($createdUser->magic_token);

        // Verify AgentProfile was created
        $this->assertNotNull($createdUser->agentProfile);
        $this->assertTrue((bool) $createdUser->agentProfile->is_verified_direct_agent);
        $this->assertEquals('5.00', (string) $createdUser->agentProfile->admin_fixed_rating);

        // Verify property is linked to this newly created agent
        $property = Property::latest('id')->first();
        $this->assertEquals($createdUser->id, $property->created_by_user_id);

        $directSource = $property->directSources()->first();
        $this->assertEquals($createdUser->id, $directSource->source_user_id);
        $this->assertEquals('direct_agent', $directSource->source_type);
    }

    public function test_admin_updating_property_can_assign_or_change_agent()
    {
        $property = Property::create([
            'property_code'      => 'PK-TEST99',
            'created_by_user_id' => $this->admin->id,
            'title'              => 'পূর্বের প্রপার্টি',
            'slug'               => 'prev-prop-1234',
            'type'               => 'land',
            'area'               => 'Aftabnagar',
            'status'             => 'draft',
        ]);

        PropertyDirectSource::create([
            'property_id' => $property->id,
            'source_user_id' => $this->admin->id,
            'source_type' => 'direct_owner',
            'owner_name' => 'মালিক',
            'owner_phone' => '01711223344',
            'asking_price' => 1000000,
            'is_primary_source' => true,
        ]);

        $payload = [
            'property_type'  => 'land',
            'title'          => 'আপডেটেড প্রপার্টি টাইটেল',
            'area'           => 'Aftabnagar',
            'owner_phone'    => '01711223344',
            'asking_price'   => 1500000,
            'agent_id'       => $this->existingAgent->id,
            'agent_name'     => $this->existingAgent->name,
            'agent_whatsapp' => $this->existingAgent->whatsapp_number,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('property.update', $property), $payload);

        $response->assertStatus(200);

        $property->refresh();
        $this->assertEquals($this->existingAgent->id, $property->created_by_user_id);

        $source = $property->directSources()->first();
        $this->assertEquals($this->existingAgent->id, $source->source_user_id);
        $this->assertEquals('direct_agent', $source->source_type);
    }

    public function test_regular_user_cannot_spoof_agent_assignment()
    {
        $payload = [
            'property_type'  => 'land',
            'title'          => 'সাধারণ ইউজারের লিস্টিং',
            'area'           => 'Aftabnagar',
            'owner_phone'    => '01711888888',
            'asking_price'   => 10000000,
            'agent_id'       => $this->existingAgent->id,
            'agent_name'     => $this->existingAgent->name,
            'agent_whatsapp' => $this->existingAgent->whatsapp_number,
        ];

        $response = $this->actingAs($this->regularUser)
            ->postJson(route('property.store'), $payload);

        $response->assertStatus(200);

        $property = Property::latest('id')->first();
        // Must belong to regular user, not the agent!
        $this->assertEquals($this->regularUser->id, $property->created_by_user_id);
    }

    public function test_admin_can_update_agent_details_via_endpoint()
    {
        $payload = [
            'name'                     => 'করিম আহমেদ আপডেটেড',
            'phone'                    => '01711122299',
            'whatsapp_number'          => '01711122288',
            'email'                    => 'karim_updated@propertykoi.test',
            'role'                     => 'agent',
            'agency_name'              => 'উত্তরখান রিয়েলটরস',
            'admin_fixed_rating'       => 4.9,
            'is_verified_direct_agent' => 1,
            'penalty_status'           => 'bypassed_warning',
            'penalty_reason'           => 'নিয়ম লঙ্ঘনের প্রাথমিক সতর্কতা',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.agents.update', $this->existingAgent), $payload);

        $response->assertRedirect(route('admin.agents.index'));

        $this->existingAgent->refresh();
        $this->assertEquals('করিম আহমেদ আপডেটেড', $this->existingAgent->name);
        $this->assertEquals('01711122299', $this->existingAgent->phone);
        $this->assertEquals('01711122288', $this->existingAgent->whatsapp_number);
        $this->assertEquals('karim_updated@propertykoi.test', $this->existingAgent->email);

        $profile = $this->existingAgent->agentProfile;
        $this->assertEquals('উত্তরখান রিয়েলটরস', $profile->agency_name);
        $this->assertEquals('4.90', (string) $profile->admin_fixed_rating);
        $this->assertEquals('bypassed_warning', $profile->penalty_status);
        $this->assertEquals('নিয়ম লঙ্ঘনের প্রাথমিক সতর্কতা', $profile->penalty_reason);
    }

    public function test_agent_directory_page_renders_with_drawers()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agents.index'));
        $response->assertStatus(200);
        $response->assertSee('openAddDrawer()', false);
        $response->assertSee('openEditDrawer(', false);
        $response->assertSee('নতুন এজেন্ট বা মালিক যুক্ত করুন');
        $response->assertSee('সদস্যের তথ্য এডিট করুন');
    }
}
