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
}
