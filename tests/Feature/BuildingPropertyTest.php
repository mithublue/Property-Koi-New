<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyBuildingDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_search_page_renders_successfully()
    {
        $response = $this->get('/building-search');
        $response->assertStatus(200);
        $response->assertSee('বাড়ি / ভবন');
        $response->assertSee('বাড়ি ও সম্পূর্ণ ভবন');
    }

    public function test_house_search_redirects_to_building_search_with_301()
    {
        $response = $this->get('/house-search');
        $response->assertStatus(301);
        $response->assertRedirect('/building-search');
    }

    public function test_user_can_submit_building_property()
    {
        $user = User::factory()->create();

        $payload = [
            'property_type'           => 'building',
            'title'                   => 'আফতাবনগর ডি ব্লকে ৫ কাঠা জমিতে নির্মিত চমৎকার ৭ তলা আবাসিক ভবন',
            'area'                    => 'Aftabnagar',
            'block'                   => 'D',
            'road_number'             => '5',
            'road_width_ft'           => 30,
            'plot_number'             => '18',
            'facing'                  => 'south',
            'building_sub_type'       => 'residential_building',
            'land_size_katha'         => 5.0,
            'total_floors'            => 7,
            'foundation_floors'       => 9,
            'units_per_floor'         => 2,
            'total_units'             => 12,
            'monthly_rental_income'   => 320000,
            'total_built_area_sqft'   => 15500,
            'parking_capacity'        => 8,
            'approved_plan'           => 1,
            'plan_approval_authority' => 'RAJUK',
            'gas_connection_type'     => 'titas_pipeline',
            'electricity_type'        => 'individual_meters',
            'occupancy_status'        => 'fully_rented',
            'has_lift'                => 1,
            'has_generator'           => 1,
            'has_substation'          => 1,
            'has_cctv'                => 1,
            'has_guard_room'          => 1,
            'has_rooftop_garden'      => 1,
            'is_corner'               => 1,
            'asking_price'            => 145000000,
            'is_negotiable'           => 1,
            'owner_name'              => 'হাজী নুরুল ইসলাম',
            'owner_phone'             => '01715889900',
            'description'             => 'তিতাস গ্যাস পাইপলাইন ও নিজস্ব সাব-স্টেশনসহ সম্পূর্ণ রেন্টেড ৭ তলা ভবন।',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('property.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $property = Property::where('type', 'building')->first();
        $this->assertNotNull($property);
        $this->assertEquals('building', $property->type);
        $this->assertEquals('বাড়ি / ভবন', $property->type_label);
        $this->assertEquals(145000000, $property->raw_price);
        $this->assertEquals(5.0, $property->size);

        $detail = PropertyBuildingDetail::where('property_id', $property->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals('residential_building', $detail->building_sub_type);
        $this->assertEquals(5.0, $detail->land_size_katha);
        $this->assertEquals(7, $detail->total_floors);
        $this->assertEquals(9, $detail->foundation_floors);
        $this->assertEquals(12, $detail->total_units);
        $this->assertEquals(320000, $detail->monthly_rental_income);
        $this->assertEquals('titas_pipeline', $detail->gas_connection_type);
        $this->assertTrue((bool)$detail->has_lift);
        $this->assertTrue((bool)$detail->has_substation);
        $this->assertTrue((bool)$detail->is_corner);
    }

    public function test_building_detail_page_renders_specifics()
    {
        $user = User::factory()->create();

        $property = Property::create([
            'property_code'      => 'PK-BLD-001',
            'title'              => 'মিরপুর ডিওএইচএস-এ বিলাসবহুল ট্রিপ্লেক্স বাড়ি বিক্রয়',
            'slug'               => 'mirpur-dohs-triplex-house-for-sale',
            'type'               => 'building',
            'area'               => 'Mirpur DOHS',
            'status'             => 'live',
            'is_approved'        => true,
            'created_by_user_id' => $user->id,
        ]);

        PropertyBuildingDetail::create([
            'property_id'             => $property->id,
            'building_sub_type'       => 'duplex',
            'land_size_katha'         => 4.5,
            'total_floors'            => 3,
            'foundation_floors'       => 5,
            'units_per_floor'         => 1,
            'total_units'             => 1,
            'monthly_rental_income'   => 180000,
            'total_built_area_sqft'   => 6200,
            'price_total'             => 85000000,
            'parking_capacity'        => 3,
            'approved_plan'           => true,
            'plan_approval_authority' => 'Cantonment Board',
            'gas_connection_type'     => 'titas_pipeline',
            'electricity_type'        => 'three_phase',
            'occupancy_status'        => 'owner_occupied',
            'has_lift'                => true,
            'has_generator'           => true,
            'has_substation'          => false,
            'has_cctv'                => true,
            'has_guard_room'          => true,
            'has_rooftop_garden'      => true,
            'is_corner'               => true,
        ]);

        $response = $this->get(route('property.show', $property->slug));
        $response->assertStatus(200);
        $response->assertSee('বাড়ি / সম্পূর্ণ ভবন');
        $response->assertSee('ডুপ্লেক্স / ট্রিপ্লেক্স বাড়ি');
        $response->assertSee('৪.৫');
        $response->assertSee('কাঠা');
        $response->assertSee('৩ তলা বর্তমান');
        $response->assertSee('৫ তলা ফাউন্ডেশন');
        $response->assertSee('১৮০,০০০');
        $response->assertSee('তিতাস সরকারি গ্যাস পাইপলাইন');
        $response->assertSee('৩টি গাড়ির স্পেস');
    }

    public function test_building_search_filtering_works()
    {
        $user = User::factory()->create();

        $prop1 = Property::create([
            'property_code'      => 'PK-BLD-002',
            'title'              => 'ছোট ৪ তলা বাড়ি',
            'slug'               => 'choto-4-tola-bari',
            'type'               => 'building',
            'area'               => 'Aftabnagar',
            'status'             => 'live',
            'is_approved'        => true,
            'created_by_user_id' => $user->id,
        ]);
        PropertyBuildingDetail::create([
            'property_id'           => $prop1->id,
            'building_sub_type'     => 'residential_building',
            'land_size_katha'       => 3.0,
            'total_floors'          => 4,
            'price_total'           => 40000000,
            'monthly_rental_income' => 90000,
            'has_lift'              => false,
        ]);

        $prop2 = Property::create([
            'property_code'      => 'PK-BLD-003',
            'title'              => 'বড় ৮ তলা হাই-ইল্ড আবাসিক ভবন',
            'slug'               => 'boro-8-tola-high-yield-building',
            'type'               => 'building',
            'area'               => 'Aftabnagar',
            'status'             => 'live',
            'is_approved'        => true,
            'created_by_user_id' => $user->id,
        ]);
        PropertyBuildingDetail::create([
            'property_id'           => $prop2->id,
            'building_sub_type'     => 'residential_building',
            'land_size_katha'       => 6.0,
            'total_floors'          => 8,
            'price_total'           => 180000000,
            'monthly_rental_income' => 450000,
            'has_lift'              => true,
        ]);

        // Filter by floors_min = 6
        $resFloors = $this->get('/building-search?floors_min=6');
        $resFloors->assertStatus(200);
        $resFloors->assertSee('বড় ৮ তলা হাই-ইল্ড আবাসিক ভবন');
        $resFloors->assertDontSee('ছোট ৪ তলা বাড়ি');

        // Filter by monthly_rent_min = 200000
        $resRent = $this->get('/building-search?monthly_rent_min=200000');
        $resRent->assertStatus(200);
        $resRent->assertSee('বড় ৮ তলা হাই-ইল্ড আবাসিক ভবন');
        $resRent->assertDontSee('ছোট ৪ তলা বাড়ি');
    }
}
