<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyLandDetail;
use App\Models\PropertyDirectSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPropertyDetailDrawersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'  => 'admin',
            'email' => 'admin@propertykoi.com',
        ]);

        $this->regularUser = User::factory()->create([
            'role'  => 'buyer',
            'email' => 'buyer@example.com',
        ]);

        $this->property = Property::create([
            'title'              => 'আফতাবনগর এল ব্লকে ৫ কাঠার আবাসিক প্লট',
            'slug'               => 'aftabnagar-l-block-5-katha-plot',
            'property_code'      => 'PK-1099',
            'type'               => 'land',
            'purpose'            => 'sale',
            'area'               => 'Aftabnagar',
            'block'              => 'L',
            'road_number'        => '5',
            'plot_number'        => '12',
            'road_width_ft'      => 40,
            'price'              => 45000000,
            'status'             => 'live',
            'hidden_note'        => 'মালিক জরুরি টাকায় বিক্রি করবেন। নেগোসিয়েশন সম্ভব।',
            'hidden_tags'        => ['জরুরি বিক্রি', 'বিনিয়োগ উপযোগী'],
            'address_visibility' => ['block' => true, 'road_number' => false, 'plot_number' => false, 'road_width_ft' => true],
        ]);

        PropertyLandDetail::create([
            'property_id'     => $this->property->id,
            'katha_size'      => 5.00,
            'price_per_katha' => 9000000,
            'price_total'     => 45000000,
        ]);

        PropertyDirectSource::create([
            'property_id'        => $this->property->id,
            'owner_name'         => 'হাজী নুরুল হক',
            'owner_phone'        => '01711998877',
            'asking_price'       => 45000000,
            'source_type'        => 'direct_owner',
            'is_primary_source'  => true,
        ]);
    }

    public function test_guest_cannot_see_admin_drawers_toolbar_on_property_detail(): void
    {
        $response = $this->get(route('property.show', $this->property->slug));
        $response->assertStatus(200);
        $response->assertDontSee('View all data');
        $response->assertDontSee('adminViewDrawerOpen');
        $response->assertDontSee('admin-view-all-content');
        $response->assertDontSee('admin-edit-form-content');
    }

    public function test_regular_user_cannot_see_admin_drawers_toolbar_on_property_detail(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('property.show', $this->property->slug));
        $response->assertStatus(200);
        $response->assertDontSee('View all data');
        $response->assertDontSee('adminViewDrawerOpen');
        $response->assertDontSee('admin-view-all-content');
    }

    public function test_admin_can_see_view_all_data_and_edit_buttons_and_drawers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('property.show', $this->property->slug));
        $response->assertStatus(200);
        $response->assertSee('View all data');
        $response->assertSee('✏️ Edit');
        $response->assertSee('adminViewDrawerOpen');
        $response->assertSee('adminEditDrawerOpen');
        $response->assertSee('id="admin-view-all-content"', false);
        $response->assertSee('id="admin-edit-form-content"', false);
    }

    public function test_admin_can_fetch_quick_view_via_ajax_with_hidden_notes_and_owner_whatsapp(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.properties.quick-view', $this->property),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);
        $response->assertSee('হাজী নুরুল হক');
        $response->assertSee('01711998877');
        $response->assertSee('wa.me/8801711998877');
        $response->assertSee('মালিক জরুরি টাকায় বিক্রি করবেন');
        $response->assertSee('জরুরি বিক্রি');
        $response->assertSee('5.00 কাঠা');
    }

    public function test_admin_can_fetch_drawer_edit_form_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.properties.edit', $this->property),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);
        $response->assertSee('submitDrawerEdit');
        $response->assertSee('আফতাবনগর এল ব্লকে ৫ কাঠার আবাসিক প্লট');
        $response->assertSee('name="hidden_note"', false);
        $response->assertSee('হাজী নুরুল হক');
    }

    public function test_admin_can_update_property_via_ajax_and_receive_json(): void
    {
        $response = $this->actingAs($this->admin)->putJson(
            route('admin.properties.update', $this->property),
            [
                'title'        => 'আফতাবনগর এল ব্লকে ৫ কাঠার প্রাইম প্লট (আপডেটেড)',
                'type'         => 'land',
                'status'       => 'live',
                'asking_price' => 46000000,
                'area'         => 'Aftabnagar',
                'block'        => 'L',
                'road_number'  => '5',
                'plot_number'  => '12',
                'hidden_note'  => 'আপডেটেড গোপন নোট - চূড়ান্ত মূল্য আলোচনা সাপেক্ষ।',
                'owner_name'   => 'হাজী নুরুল হক চৌধুরী',
                'owner_phone'  => '01711998877',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->property->refresh();
        $this->assertEquals('আফতাবনগর এল ব্লকে ৫ কাঠার প্রাইম প্লট (আপডেটেড)', $this->property->title);
        $this->assertEquals('আপডেটেড গোপন নোট - চূড়ান্ত মূল্য আলোচনা সাপেক্ষ।', $this->property->hidden_note);
        $this->assertEquals(46000000, (float)$this->property->asking_price);
    }

    public function test_non_admin_cannot_access_quick_view_or_edit_routes(): void
    {
        // Web requests redirect blocked users
        $response1 = $this->actingAs($this->regularUser)->get(route('admin.properties.quick-view', $this->property));
        $response1->assertRedirect();

        $response2 = $this->actingAs($this->regularUser)->get(route('admin.properties.edit', $this->property));
        $response2->assertRedirect();

        // JSON requests receive 403 Forbidden
        $responseJson = $this->actingAs($this->regularUser)->getJson(route('admin.properties.quick-view', $this->property));
        $responseJson->assertStatus(403);
    }
}
