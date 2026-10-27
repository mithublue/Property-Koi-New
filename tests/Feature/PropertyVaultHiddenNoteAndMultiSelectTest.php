<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Services\PropertySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyVaultHiddenNoteAndMultiSelectTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'  => 'admin',
            'email' => 'admin.vault@propertykoi.com',
        ]);
    }

    public function test_smart_keyword_extraction_from_hidden_note(): void
    {
        $note = "মালিক বিদেশে থাকেন, খুব জরুরী বিক্রয়। বায়া দলিল ওকে এবং ব্যাংক লোন সুবিধা আছে। #hotdeal";
        $tags = PropertySearchService::extractKeywordsFromNote($note);

        $this->assertIsArray($tags);
        $this->assertContains('মালিক বিদেশে', $tags);
        $this->assertContains('জরুরী বিক্রয়', $tags);
        $this->assertContains('বায়া দলিল ক্লিয়ার', $tags);
        $this->assertContains('ব্যাংক লোন সুবিধা', $tags);
        $this->assertContains('hotdeal', $tags);
    }

    public function test_hidden_note_is_not_viewable_on_public_frontend(): void
    {
        $prop = Property::create([
            'property_code'      => 'PK-HN-PUBLIC',
            'title'              => 'Prime Residential Plot in Aftabnagar',
            'slug'               => 'prime-residential-plot-in-aftabnagar-public',
            'type'               => 'land',
            'area'               => 'Aftabnagar',
            'block'              => 'D',
            'status'             => 'live',
            'hidden_note'        => 'TOP SECRET: Owner desperate, will sell at 80 lac net.',
            'hidden_tags'        => ['urgent', 'distress_sale'],
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->get(route('property.show', $prop->slug));
        $response->assertStatus(200);
        $response->assertDontSee('TOP SECRET');
        $response->assertDontSee('80 lac net');
        $response->assertDontSee('distress_sale');
    }

    public function test_hidden_note_search_in_vault(): void
    {
        $prop1 = Property::create([
            'property_code'      => 'PK-HN-01',
            'title'              => 'Plot One',
            'slug'               => 'plot-one-hn01',
            'type'               => 'land',
            'status'             => 'draft',
            'hidden_note'        => 'মালিক বিদেশে থাকেন জরুরী বিক্রয়',
            'hidden_tags'        => ['মালিক বিদেশে', 'জরুরী বিক্রয়'],
            'created_by_user_id' => $this->admin->id,
        ]);

        $prop2 = Property::create([
            'property_code'      => 'PK-HN-02',
            'title'              => 'Plot Two',
            'slug'               => 'plot-two-hn02',
            'type'               => 'land',
            'status'             => 'draft',
            'hidden_note'        => 'সাধারণ ড্রাফট কোনো বিশেষ শর্ত নেই',
            'hidden_tags'        => [],
            'created_by_user_id' => $this->admin->id,
        ]);

        // Search by hidden note text
        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['note' => 'মালিক বিদেশে']));

        $response->assertStatus(200);
        $response->assertSee('PK-HN-01');
        $response->assertDontSee('PK-HN-02');
    }

    public function test_keyword_tag_search_in_vault(): void
    {
        $prop1 = Property::create([
            'property_code'      => 'PK-TAG-01',
            'title'              => 'Plot with Loan Facility',
            'slug'               => 'plot-with-loan-tag01',
            'type'               => 'land',
            'status'             => 'draft',
            'hidden_note'        => 'ব্যাংক লোন সুবিধা আছে',
            'hidden_tags'        => ['ব্যাংক লোন সুবিধা'],
            'created_by_user_id' => $this->admin->id,
        ]);

        $prop2 = Property::create([
            'property_code'      => 'PK-TAG-02',
            'title'              => 'Regular Plot',
            'slug'               => 'regular-plot-tag02',
            'type'               => 'land',
            'status'             => 'draft',
            'hidden_note'        => 'সাধারণ প্লট',
            'hidden_tags'        => [],
            'created_by_user_id' => $this->admin->id,
        ]);

        // Search by keyword/tag
        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['keyword' => 'ব্যাংক লোন সুবিধা']));

        $response->assertStatus(200);
        $response->assertSee('PK-TAG-01');
        $response->assertDontSee('PK-TAG-02');
    }

    public function test_multiselect_type_and_status_filters(): void
    {
        $propLand = Property::create([
            'property_code'      => 'PK-MULTI-LAND',
            'title'              => 'Land Property',
            'slug'               => 'land-property-multi',
            'type'               => 'land',
            'status'             => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propFlat = Property::create([
            'property_code'      => 'PK-MULTI-FLAT',
            'title'              => 'Flat Property',
            'slug'               => 'flat-property-multi',
            'type'               => 'flat',
            'status'             => 'pending_verification',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propComm = Property::create([
            'property_code'      => 'PK-MULTI-COMM',
            'title'              => 'Commercial Property',
            'slug'               => 'comm-property-multi',
            'type'               => 'commercial',
            'status'             => 'sold',
            'created_by_user_id' => $this->admin->id,
        ]);

        // 1. Multiselect Type: land and flat only
        $typeResponse = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['types' => ['land', 'flat'], 'status' => 'all']));
        $typeResponse->assertStatus(200);
        $typeResponse->assertSee('PK-MULTI-LAND');
        $typeResponse->assertSee('PK-MULTI-FLAT');
        $typeResponse->assertDontSee('PK-MULTI-COMM');

        // 2. Multiselect Status: draft and pending only
        $statusResponse = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['statuses' => ['draft', 'pending']]));
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee('PK-MULTI-LAND');
        $statusResponse->assertSee('PK-MULTI-FLAT');
        $statusResponse->assertDontSee('PK-MULTI-COMM');
    }

    public function test_admin_can_update_hidden_note_via_ajax_endpoint(): void
    {
        $prop = Property::create([
            'property_code'      => 'PK-AJAX-01',
            'title'              => 'Ajax Test Plot',
            'slug'               => 'ajax-test-plot-01',
            'type'               => 'land',
            'status'             => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.properties.hidden-note', $prop), [
            'hidden_note' => 'মালিক বিদেশে থাকেন, বায়া দলিল ওকে।',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'     => true,
            'hidden_note' => 'মালিক বিদেশে থাকেন, বায়া দলিল ওকে।',
        ]);

        $prop->refresh();
        $this->assertEquals('মালিক বিদেশে থাকেন, বায়া দলিল ওকে।', $prop->hidden_note);
        $this->assertContains('মালিক বিদেশে', $prop->hidden_tags);
        $this->assertContains('বায়া দলিল ক্লিয়ার', $prop->hidden_tags);
    }
}
