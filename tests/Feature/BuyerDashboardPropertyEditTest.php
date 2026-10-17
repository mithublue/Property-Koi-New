<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerDashboardPropertyEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_fetch_property_for_edit()
    {
        $user = User::create([
            'name' => 'Owner User',
            'phone' => '01711111111',
            'email' => 'owner@propertykoi.com',
            'password' => bcrypt('password'),
            'role' => 'buyer',
        ]);

        $property = Property::create([
            'property_code' => 'PK-HJOHMX',
            'title' => 'Test Property In Aftabnagar',
            'type' => 'flat',
            'area' => 'Aftabnagar',
            'block' => 'F',
            'status' => 'live',
            'asking_price' => 7500000,
            'size' => 1280,
            'created_by_user_id' => $user->id,
            'slug' => 'test-property-in-aftabnagar-pk-hjohmx',
        ]);

        $response = $this->actingAs($user)->getJson(route('buyer.dashboard.property.get', $property));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'property' => [
                    'id' => $property->id,
                    'property_code' => 'PK-HJOHMX',
                    'title' => 'Test Property In Aftabnagar',
                ]
            ]
        ]);
    }

    public function test_non_owner_cannot_fetch_property_for_edit()
    {
        $owner = User::create([
            'name' => 'Owner User',
            'phone' => '01711111111',
            'email' => 'owner2@propertykoi.com',
            'password' => bcrypt('password'),
            'role' => 'buyer',
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'phone' => '01722222222',
            'email' => 'other@propertykoi.com',
            'password' => bcrypt('password'),
            'role' => 'buyer',
        ]);

        $property = Property::create([
            'property_code' => 'PK-OTHER',
            'title' => 'Other Property',
            'type' => 'land',
            'area' => 'Aftabnagar',
            'status' => 'live',
            'created_by_user_id' => $owner->id,
            'slug' => 'other-property-pk-other',
        ]);

        $response = $this->actingAs($otherUser)->getJson(route('buyer.dashboard.property.get', $property));

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }
}
