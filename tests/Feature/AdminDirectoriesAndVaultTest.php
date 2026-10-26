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

    public function test_admin_properties_create_redirects_to_sell_property(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.properties.create', ['destination' => 'vault']));
        $response->assertRedirect(route('property.create', ['destination' => 'vault']));
    }

    public function test_sell_property_with_vault_destination_saves_as_draft_with_custom_fields(): void
    {
        $customFields = [
            ['key' => 'মৌজা', 'value' => 'গজারিয়া'],
            ['key' => 'RS দাগ নম্বর', 'value' => '৩৮১১, ৩৯৬৮'],
            ['key' => 'কাগজের অবস্থা', 'value' => '১০০% ফ্রেশ ও নিষ্কণ্টক'],
        ];

        $postData = [
            'title'         => 'উত্তরখান ৩ কাঠা নিষ্কণ্টক জমি',
            'area'          => 'উত্তরখান',
            'property_type' => 'land',
            'owner_name'    => 'আফতাব মোল্লা',
            'owner_phone'   => '01719998877',
            'size'          => 3,
            'asking_price'  => 4500000,
            'destination'   => 'vault',
            'custom_fields' => json_encode($customFields),
        ];

        $response = $this->actingAs($this->admin)->post(route('property.store'), $postData);
        $response->assertRedirect(route('admin.properties.directory'));

        $saved = Property::where('title', 'উত্তরখান ৩ কাঠা নিষ্কণ্টক জমি')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('draft', $saved->status);
        $this->assertEquals('উত্তরখান', $saved->area);
        $this->assertIsArray($saved->custom_fields);
        $this->assertCount(3, $saved->custom_fields);
        $this->assertEquals('মৌজা', $saved->custom_fields[0]['key']);
        $this->assertEquals('গজারিয়া', $saved->custom_fields[0]['value']);

        // Check search by custom field value in admin directory
        $searchDir = $this->actingAs($this->admin)->get(route('admin.properties.directory', ['search' => 'গজারিয়া']));
        $searchDir->assertStatus(200);
        $searchDir->assertSee('উত্তরখান ৩ কাঠা নিষ্কণ্টক জমি');

        // Check search by RS dag number
        $searchDag = $this->actingAs($this->admin)->get(route('admin.properties.directory', ['search' => '৩৮১১']));
        $searchDag->assertStatus(200);
        $searchDag->assertSee('উত্তরখান ৩ কাঠা নিষ্কণ্টক জমি');

        // Verify dynamic areas and custom keys include newly added values
        $areas = Property::getDistinctAreas();
        $this->assertContains('উত্তরখান', $areas);

        $keys = Property::getDistinctCustomKeys();
        $this->assertContains('মৌজা', $keys);
        $this->assertContains('RS দাগ নম্বর', $keys);
        $this->assertContains('কাগজের অবস্থা', $keys);
    }

    public function test_property_edit_opens_frontend_mode_and_updates_successfully(): void
    {
        $property = Property::create([
            'property_code' => 'PK-EDT01',
            'title'         => 'আফতাবনগর সি ব্লক ৩ কাঠা প্লট',
            'slug'          => 'aftabnagar-c-block-3-katha-edt01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'C',
            'status'        => 'draft',
            'asking_price'  => 8500000,
            'custom_fields' => [['key' => 'মৌজা', 'value' => 'মেরাদিয়া']],
            'created_by_user_id' => $this->admin->id,
        ]);

        // 1. Frontend edit page loads with 200 OK and preloaded data
        $editPage = $this->actingAs($this->admin)->get(route('property.edit', $property));
        $editPage->assertStatus(200);
        $editPage->assertSee('PK-EDT01');
        $editPage->assertSee('প্রপার্টি এডিট মোড');
        $editPage->assertSee('মেরাদিয়া');

        // 3. Update property via frontend update route
        $updateResponse = $this->actingAs($this->admin)->post(route('property.update', $property), [
            'title'         => 'আফতাবনগর সি ব্লক ৩ কাঠা কর্নার প্লট (আপডেটেড)',
            'area'          => 'Aftabnagar',
            'block'         => 'C',
            'property_type' => 'land',
            'size'          => 3,
            'asking_price'  => 9000000,
            'owner_phone'   => '01711223344',
            'destination'   => 'vault',
            'custom_fields' => json_encode([
                ['key' => 'মৌজা', 'value' => 'মেরাদিয়া দক্ষিণ'],
                ['key' => 'RS দাগ নম্বর', 'value' => '৪৯২০'],
            ]),
        ]);

        $updateResponse->assertRedirect(route('admin.properties.directory'));

        $property->refresh();
        $this->assertEquals('আফতাবনগর সি ব্লক ৩ কাঠা কর্নার প্লট (আপডেটেড)', $property->title);
        $this->assertEquals('draft', $property->status);
        $this->assertEquals('মেরাদিয়া দক্ষিণ', $property->custom_fields[0]['value']);
        $this->assertEquals('৪৯২০', $property->custom_fields[1]['value']);
    }

    public function test_bulk_delete_and_bulk_status_in_property_directory(): void
    {
        $p1 = Property::create([
            'property_code' => 'PK-BLK01',
            'title'         => 'প্লট ১',
            'slug'          => 'plot-1-blk01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $p2 = Property::create([
            'property_code' => 'PK-BLK02',
            'title'         => 'প্লট ২',
            'slug'          => 'plot-2-blk02',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        // 1. Bulk Status Change to 'sold'
        $statusRes = $this->actingAs($this->admin)->postJson(route('admin.properties.bulk-status'), [
            'ids'    => [$p1->id, $p2->id],
            'status' => 'sold',
        ]);
        $statusRes->assertStatus(200);
        $statusRes->assertJson(['success' => true, 'count' => 2, 'status' => 'sold']);

        $p1->refresh();
        $p2->refresh();
        $this->assertEquals('sold', $p1->status);
        $this->assertEquals('sold', $p2->status);

        // 2. Filter directory by status = 'sold'
        $dirRes = $this->actingAs($this->admin)->get(route('admin.properties.directory', ['status' => 'sold']));
        $dirRes->assertStatus(200);
        $dirRes->assertSee('PK-BLK01');
        $dirRes->assertSee('PK-BLK02');

        // 3. Bulk Delete
        $delRes = $this->actingAs($this->admin)->postJson(route('admin.properties.bulk-delete'), [
            'ids' => [$p1->id, $p2->id],
        ]);
        $delRes->assertStatus(200);
        $delRes->assertJson(['success' => true, 'count' => 2]);

        $this->assertDatabaseMissing('properties', ['id' => $p1->id]);
        $this->assertDatabaseMissing('properties', ['id' => $p2->id]);
    }
}
