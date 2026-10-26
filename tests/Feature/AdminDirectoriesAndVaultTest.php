<?php

namespace Tests\Feature;

use App\Models\BuyerDemand;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDirectoriesAndVaultTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@propertykoi.com',
        ]);
    }

    public function test_admin_can_view_and_create_agent_in_agent_directory(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.agents.index'));
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($this->admin)->post(route('admin.agents.store'), [
            'name'                     => 'রফিক তালুকদার',
            'phone'                    => '01711223344',
            'whatsapp_number'          => '01711223344',
            'email'                    => 'rafiq.agent@example.com',
            'role'                     => 'agent',
            'admin_fixed_rating'       => '4.9',
            'is_verified_direct_agent' => '1',
            'notes'                    => 'আফতাবনগর সি ও ডি ব্লকের অভিজ্ঞ এজেন্ট।',
        ]);

        $storeResponse->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('users', [
            'name'  => 'রফিক তালুকদার',
            'phone' => '01711223344',
            'role'  => 'agent',
        ]);
    }

    public function test_admin_can_view_and_create_buyer_in_buyer_directory(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.buyers.index'));
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($this->admin)->post(route('admin.buyers.store'), [
            'name'             => 'তানভীর আহমেদ',
            'phone'            => '01899887766',
            'whatsapp_number'  => '01899887766',
            'email'            => 'tanveer.buyer@example.com',
            'preferred_type'   => 'land',
            'preferred_areas'  => 'Aftabnagar',
            'preferred_blocks' => 'C, D',
            'budget_range'     => '১ কোটি ২০ লাখ',
            'min_budget'       => 10000000,
            'max_budget'       => 13000000,
            'notes'            => 'দক্ষিণমুখী প্লট খুঁজছেন',
        ]);

        $storeResponse->assertRedirect(route('admin.buyers.index'));

        $this->assertDatabaseHas('users', [
            'name'  => 'তানভীর আহমেদ',
            'phone' => '01899887766',
            'role'  => 'buyer',
        ]);

        $this->assertDatabaseHas('buyer_demands', [
            'buyer_name'    => 'তানভীর আহমেদ',
            'buyer_phone'   => '01899887766',
            'property_type' => 'land',
            'status'        => 'active',
        ]);
    }

    public function test_private_property_directory_and_1_click_publishing(): void
    {
        // 1. Create a private/draft property
        $privateProp = Property::create([
            'property_code' => 'AP-PVT01',
            'title'         => 'আফতাবনগর ব্লক বি ৫ কাঠা প্রাইভেট প্লট',
            'slug'          => 'aftabnagar-block-b-private-plot-pvt01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'B',
            'status'        => 'draft',
            'asking_price'  => 12000000,
            'created_by_user_id' => $this->admin->id,
        ]);

        // 2. Must appear in Admin Private Directory
        $dirResponse = $this->actingAs($this->admin)->get(route('admin.properties.directory'));
        $dirResponse->assertStatus(200);
        $dirResponse->assertSee('AP-PVT01');

        // 3. Must NOT appear on public search /properties
        $this->app['auth']->logout();
        $searchResponse = $this->get(route('properties.index'));
        $searchResponse->assertStatus(200);
        $searchResponse->assertDontSee('AP-PVT01');

        // 4. Must NOT be accessible to public visitors on /property/{slug} (Returns 404 for guests)
        $publicDetailResponse = $this->get(route('property.show', $privateProp->slug));
        $publicDetailResponse->assertStatus(404);

        // 5. Admin executes 1-Click Publish to Live
        $publishResponse = $this->actingAs($this->admin)->post(route('admin.properties.publish-live', $privateProp));
        $publishResponse->assertSessionHas('success');

        $privateProp->refresh();
        $this->assertEquals('live', $privateProp->status);
        $this->assertNotNull($privateProp->last_verified_at);

        // 6. Now publicly accessible on /property/{slug} with 200 OK
        $this->app['auth']->logout();
        $liveDetailResponse = $this->get(route('property.show', $privateProp->slug));
        $liveDetailResponse->assertStatus(200);
        $liveDetailResponse->assertSee('AP-PVT01');

        // 7. Admin unpublishes back to private directory
        $unpublishResponse = $this->actingAs($this->admin)->post(route('admin.properties.unpublish', $privateProp));
        $unpublishResponse->assertSessionHas('success');

        $privateProp->refresh();
        $this->assertEquals('draft', $privateProp->status);

        // 8. Again hidden from public visitors (Returns 404)
        $this->app['auth']->logout();
        $hiddenAgainResponse = $this->get(route('property.show', $privateProp->slug));
        $hiddenAgainResponse->assertStatus(404);
    }
}
