<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyEditRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_update_redirects_to_edit_page_not_index()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_prop_test@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000000', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $property = Property::create([
            'property_code' => 'PK-TEST99',
            'title' => 'Original Title',
            'type' => 'flat',
            'area' => 'South Banasree',
            'block' => 'F',
            'status' => 'live',
            'asking_price' => 7500000,
            'size' => 1280,
            'slug' => 'original-title-pk-test99',
        ]);

        $updateData = [
            'title' => 'Updated Property Title',
            'type' => 'flat',
            'area' => 'South Banasree',
            'block' => 'F',
            'status' => 'live',
            'size' => 1350,
            'asking_price' => 8000000,
            'active_tab' => 'specs',
        ];

        $response = $this->actingAs($admin)->put(route('admin.properties.update', $property), $updateData);

        // Must redirect to edit page, NOT properties index
        $response->assertRedirect(route('admin.properties.edit', ['property' => $property, 'tab' => 'specs']));
        $response->assertSessionHas('success');

        // Check property was actually updated in database
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => 'Updated Property Title',
        ]);

        // Follow redirect and ensure the edit page loads with success message
        $followResponse = $this->actingAs($admin)->get(route('admin.properties.edit', ['property' => $property, 'tab' => 'specs']));
        $followResponse->assertStatus(200);
        $followResponse->assertSee('Updated Property Title');
        $followResponse->assertSee("প্রপার্টি এডিট #{$property->property_code}");
    }

    public function test_property_owner_phone_and_whatsapp_displayed_and_updated_in_edit_mode()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_prop_test2@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000000', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $property = Property::create([
            'property_code' => 'PK-TGL6HA',
            'title' => 'আফতাবনগর ৩ কাঠার প্লট',
            'type' => 'land',
            'area' => 'Aftabnagar',
            'block' => 'F',
            'status' => 'live',
            'asking_price' => 79750000,
            'slug' => 'aftabnagar-plot-pk-tgl6ha',
        ]);

        // 1. Visit edit page before direct source exists - edit page should render WhatsApp field
        $response = $this->actingAs($admin)->get(route('admin.properties.edit', $property));
        $response->assertStatus(200);
        $response->assertSee('মালিকের ফোন নম্বর / WhatsApp');

        // 2. Update property with owner_name and owner_phone
        $updateData = [
            'title' => 'আফতাবনগর ৩ কাঠার প্লট (আপডেটেড)',
            'type' => 'land',
            'area' => 'Aftabnagar',
            'block' => 'F',
            'status' => 'live',
            'asking_price' => 79750000,
            'owner_name' => 'আলহাজ্ব গোলাম রসূল',
            'owner_phone' => '01711223344',
            'source_type' => 'direct_owner',
            'active_tab' => 'basic',
        ];

        $updateResponse = $this->actingAs($admin)->put(route('admin.properties.update', $property), $updateData);
        $updateResponse->assertRedirect(route('admin.properties.edit', ['property' => $property, 'tab' => 'basic']));

        // Direct source should now exist with owner_phone = 01711223344
        $this->assertDatabaseHas('property_direct_sources', [
            'property_id' => $property->id,
            'owner_name' => 'আলহাজ্ব গোলাম রসূল',
            'owner_phone' => '01711223344',
            'is_primary_source' => true,
        ]);

        // 3. Edit page now reflects owner_name and owner_phone
        $editResponse = $this->actingAs($admin)->get(route('admin.properties.edit', $property));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('01711223344');
        $editResponse->assertSee('আলহাজ্ব গোলাম রসূল');
    }
}
