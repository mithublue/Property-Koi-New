<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyDirectSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyBilingualSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.search@propertykoi.com',
        ]);
    }

    public function test_searching_bengali_block_h_finds_property_with_english_title_and_block(): void
    {
        $propH = Property::create([
            'property_code' => 'PK-H01',
            'title'         => '3 Katha Plot in Aftabnagar Block H',
            'slug'          => '3-katha-plot-in-aftabnagar-block-h-h01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'H',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propB = Property::create([
            'property_code' => 'PK-B01',
            'title'         => '5 Katha Plot in Aftabnagar Block B',
            'slug'          => '5-katha-plot-in-aftabnagar-block-b-b01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'B',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        // Search "এইচ ব্লক"
        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['search' => 'এইচ ব্লক']));

        $response->assertStatus(200);
        $response->assertSee('PK-H01');
        $response->assertDontSee('PK-B01');
    }

    public function test_searching_english_block_h_finds_property_with_bengali_title(): void
    {
        $propBn = Property::create([
            'property_code' => 'PK-BN-H',
            'title'         => 'আফতাবনগর এইচ ব্লকের চমৎকার প্লট',
            'slug'          => 'aftabnagar-h-block-chomotkar-plot-bnh',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'H',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propOther = Property::create([
            'property_code' => 'PK-BN-C',
            'title'         => 'আফতাবনগর সি ব্লকের প্লট',
            'slug'          => 'aftabnagar-c-block-plot-bnc',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'C',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['search' => 'Block H']));

        $response->assertStatus(200);
        $response->assertSee('PK-BN-H');
        $response->assertDontSee('PK-BN-C');
    }

    public function test_searching_bengali_area_finds_english_area_property(): void
    {
        $propAftab = Property::create([
            'property_code' => 'PK-AFT01',
            'title'         => 'Residential Plot in Aftabnagar Prime Zone',
            'slug'          => 'residential-plot-in-aftabnagar-prime-zone-aft01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'D',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propBash = Property::create([
            'property_code' => 'PK-BSH01',
            'title'         => 'Residential Plot in Bashundhara Prime Zone',
            'slug'          => 'residential-plot-in-bashundhara-prime-zone-bsh01',
            'type'          => 'land',
            'area'          => 'Bashundhara',
            'block'         => 'D',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['search' => 'আফতাবনগর']));

        $response->assertStatus(200);
        $response->assertSee('PK-AFT01');
        $response->assertDontSee('PK-BSH01');
    }

    public function test_bengali_compound_search_area_and_block(): void
    {
        $propAftabH = Property::create([
            'property_code' => 'PK-AFTH',
            'title'         => 'South Facing Plot in Block H',
            'slug'          => 'south-facing-plot-in-block-h-afth',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'H',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propBashH = Property::create([
            'property_code' => 'PK-BSHH',
            'title'         => 'South Facing Plot in Bashundhara Block H',
            'slug'          => 'south-facing-plot-in-bashundhara-block-h-bshh',
            'type'          => 'land',
            'area'          => 'Bashundhara',
            'block'         => 'H',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propAftabB = Property::create([
            'property_code' => 'PK-AFTB',
            'title'         => 'Plot in Aftabnagar Block B',
            'slug'          => 'plot-in-aftabnagar-block-b-aftb',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'B',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        // Search "আফতাবনগর এইচ ব্লক" -> Only Aftabnagar + Block H should match!
        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['search' => 'আফতাবনগর এইচ ব্লক']));

        $response->assertStatus(200);
        $response->assertSee('PK-AFTH');
        $response->assertDontSee('PK-BSHH');
        $response->assertDontSee('PK-AFTB');
    }

    public function test_bengali_road_search_finds_english_digit_road(): void
    {
        $propRoad5 = Property::create([
            'property_code' => 'PK-RD05',
            'title'         => 'Lake View Corner Plot',
            'slug'          => 'lake-view-corner-plot-rd05',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'M',
            'road_number'   => '5',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $propRoad12 = Property::create([
            'property_code' => 'PK-RD12',
            'title'         => 'Park View Plot',
            'slug'          => 'park-view-plot-rd12',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'M',
            'road_number'   => '12',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['search' => 'রোড ৫']));

        $response->assertStatus(200);
        $response->assertSee('PK-RD05');
        $response->assertDontSee('PK-RD12');
    }

    public function test_bengali_numerals_phone_search_finds_owner_phone(): void
    {
        $propPhone = Property::create([
            'property_code' => 'PK-PH01',
            'title'         => 'Direct Owner Exclusive Land',
            'slug'          => 'direct-owner-exclusive-land-ph01',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'status'        => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        PropertyDirectSource::create([
            'property_id'              => $propPhone->id,
            'source_type'              => 'direct_owner',
            'relationship_declaration' => 'মালিক',
            'owner_name'               => 'আব্দুল করিম',
            'owner_phone'              => '01711998877',
            'asking_price'             => 15000000,
            'is_primary_source'        => true,
            'verification_status'      => 'verified',
        ]);

        // Search with Bengali digits: ০১৭১১৯৯৮৮৭৭
        $response = $this->actingAs($this->admin)
            ->get(route('admin.properties.directory', ['search' => '০১৭১১৯৯৮৮৭৭']));

        $response->assertStatus(200);
        $response->assertSee('PK-PH01');
    }

    public function test_frontend_public_search_supports_bilingual_queries(): void
    {
        $liveProp = Property::create([
            'property_code' => 'PK-LIVE-H',
            'title'         => 'Prime Plot in Aftabnagar Block H',
            'slug'          => 'prime-plot-in-aftabnagar-block-h-liveh',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'H',
            'status'        => 'live',
            'created_by_user_id' => $this->admin->id,
        ]);

        $otherLiveProp = Property::create([
            'property_code' => 'PK-LIVE-B',
            'title'         => 'Prime Plot in Aftabnagar Block B',
            'slug'          => 'prime-plot-in-aftabnagar-block-b-liveb',
            'type'          => 'land',
            'area'          => 'Aftabnagar',
            'block'         => 'B',
            'status'        => 'live',
            'created_by_user_id' => $this->admin->id,
        ]);

        // Public listing search for "এইচ ব্লক"
        $response = $this->get(route('properties.land', ['search' => 'এইচ ব্লক']));
        $response->assertStatus(200);
        $response->assertSee('PK-LIVE-H');
        $response->assertDontSee('PK-LIVE-B');
    }
}
