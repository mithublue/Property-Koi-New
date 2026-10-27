<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SheetImporterController;
use App\Models\Property;
use App\Models\SheetImportPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SheetImporterTest extends TestCase
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

    public function test_admin_can_view_sheet_importer_page_and_default_preset_is_seeded(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sheet-importer.index'));
        $response->assertStatus(200);
        $response->assertSee('গুগল শিট বাল্ক ইমপোর্টার');
        $response->assertSee('প্লট স্ট্যান্ডার্ড শিট (Plots Standard)');

        $this->assertDatabaseHas('sheet_import_presets', [
            'name' => 'প্লট স্ট্যান্ডার্ড শিট (Plots Standard)',
        ]);
    }

    public function test_admin_can_store_and_delete_mapping_preset(): void
    {
        $storeResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.presets.store'), [
            'name'             => 'উত্তরা ও গুলশান অ্যাপার্টমেন্ট শিট',
            'description'      => 'কলাম: এরিয়া, সেক্টর, সাইজ, বেডরুম, প্রাইস',
            'sample_sheet_url' => 'https://docs.google.com/spreadsheets/d/test-url/edit',
            'field_mappings'   => [
                'area'     => 'area',
                'sector'   => 'sector',
                'size'     => 'size',
                'price'    => 'asking_price',
                'timeline' => 'custom_field:হস্তান্তর সময়',
            ],
            'options'          => [
                'destination'    => 'draft',
                'duplicate_mode' => 'skip',
                'default_type'   => 'flat',
                'default_area'   => 'Uttara',
            ],
        ]);

        $storeResponse->assertStatus(200);
        $storeResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('sheet_import_presets', [
            'name' => 'উত্তরা ও গুলশান অ্যাপার্টমেন্ট শিট',
        ]);

        $preset = SheetImportPreset::where('name', 'উত্তরা ও গুলশান অ্যাপার্টমেন্ট শিট')->firstOrFail();

        // Delete preset
        $deleteResponse = $this->actingAs($this->admin)->deleteJson(route('admin.sheet-importer.presets.destroy', $preset));
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['success' => true]);

        $this->assertDatabaseMissing('sheet_import_presets', [
            'id' => $preset->id,
        ]);
    }

    public function test_smart_math_and_price_parsers(): void
    {
        // 1. Math expressions & size parsing
        $this->assertEquals(5.0, SheetImporterController::parseSize('2.5+2.5'));
        $this->assertEquals(200.0, SheetImporterController::parseSize('10*20'));
        $this->assertEquals(3.5, SheetImporterController::parseSize('৩.৫ কাঠা'));
        $this->assertEquals(10.0, SheetImporterController::parseSize(' 10 katha '));

        // 2. Price with lac / cr / bengali units
        $this->assertEquals(9500000.0, SheetImporterController::parsePrice('95 lac'));
        $this->assertEquals(9500000.0, SheetImporterController::parsePrice('৯৫ লাখ'));
        $this->assertEquals(12500000.0, SheetImporterController::parsePrice('1.25 cr'));
        $this->assertEquals(12500000.0, SheetImporterController::parsePrice('১.২৫ কোটি'));
        $this->assertEquals(8500000.0, SheetImporterController::parsePrice('8500000'));

        // 3. Normalized facing
        $this->assertEquals('south', SheetImporterController::normalizeFacing('দক্ষিণ'));
        $this->assertEquals('south-east', SheetImporterController::normalizeFacing('দক্ষিণ-পূর্ব'));
        $this->assertEquals('north', SheetImporterController::normalizeFacing('North Face'));
        $this->assertEquals('east', SheetImporterController::normalizeFacing('East'));
    }

    public function test_smart_extra_parsing_extracts_mouza_and_dag_numbers(): void
    {
        $extraText = 'মৌজা: মেরাদিয়া, দাগ নং: RS 452, CS 88, হস্তান্তর: ডিসেম্বর ২০২৬, ২০ ফিট রোড';
        $data = ['custom_fields' => []];

        SheetImporterController::parseExtraIntoCustomFields($extraText, $data);

        $keys = array_column($data['custom_fields'], 'key');
        $this->assertContains('মৌজা', $keys);
        $this->assertContains('দাগ নম্বর', $keys);
        $this->assertContains('হস্তান্তর সময়সীমা', $keys);

        // Find mouza value
        foreach ($data['custom_fields'] as $cf) {
            if ($cf['key'] === 'মৌজা') {
                $this->assertEquals('মেরাদিয়া', $cf['value']);
            }
        }
    }

    public function test_bulk_importer_creates_properties_in_vault_and_skips_duplicates(): void
    {
        // Mock Google Sheets CSV download response
        $csvData = "place,block,sector,road,plot number,katha,agent,contact,price,extra,time line\n" .
                   "Aftabnagar,M,Sector 2,5,102,2.5+2.5,Tanvir Ahmed,01711223344,95 lac,\"মৌজা: রামপুরা, দাগ: RS 112\",ডিসেম্বর ২০২৬\n" .
                   "Aftabnagar,C,Sector 1,12,55,3,Shakil Khan,01811223344,1.2 cr,\"মৌজা: মেরাদিয়া, দাগ: CS 99\",জরুরি হস্তান্তর\n";

        Http::fake([
            '*docs.google.com*' => Http::response($csvData, 200, ['Content-Type' => 'text/csv']),
        ]);

        $mappings = [
            'place'       => 'area',
            'block'       => 'block',
            'sector'      => 'sector',
            'road'        => 'road_number',
            'plot number' => 'plot_number',
            'katha'       => 'size',
            'agent'       => 'owner_name',
            'contact'     => 'owner_phone',
            'price'       => 'asking_price',
            'extra'       => 'parse_extra',
            'time line'   => 'custom_field:হস্তান্তর সময়সীমা',
        ];

        // 1. First Import
        $importResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/test-sheet-id/edit',
            'mappings'       => $mappings,
            'destination'    => 'draft',
            'duplicate_mode' => 'skip',
            'default_type'   => 'land',
            'default_area'   => 'Aftabnagar',
        ]);

        $importResponse->assertStatus(200);
        $importResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 2,
            'updated'   => 0,
            'skipped'   => 0,
        ]);

        // Verify property was created in draft status
        $firstProperty = Property::where('block', 'M')
            ->where('plot_number', '102')
            ->first();

        $this->assertNotNull($firstProperty);
        $this->assertEquals('draft', $firstProperty->status);
        $this->assertEquals('Aftabnagar', $firstProperty->area);
        $this->assertEquals(9500000.0, (float)$firstProperty->raw_price);
        $this->assertEquals(5.0, (float)$firstProperty->size);

        // Verify custom fields were saved and searchable
        $customFields = $firstProperty->custom_fields ?? [];
        $keys = array_column($customFields, 'key');
        $this->assertContains('মৌজা', $keys);
        $this->assertContains('দাগ নম্বর', $keys);
        $this->assertContains('হস্তান্তর সময়সীমা', $keys);

        // 2. Second Import with duplicate_mode = 'skip'
        $secondImportResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/test-sheet-id/edit',
            'mappings'       => $mappings,
            'destination'    => 'draft',
            'duplicate_mode' => 'skip',
            'default_type'   => 'land',
            'default_area'   => 'Aftabnagar',
        ]);

        $secondImportResponse->assertStatus(200);
        $secondImportResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 0,
            'updated'   => 0,
            'skipped'   => 2,
        ]);

        // Total properties should still be 2
        $this->assertEquals(2, Property::count());

        // 3. Third Import with duplicate_mode = 'update'
        $thirdImportResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/test-sheet-id/edit',
            'mappings'       => $mappings,
            'destination'    => 'draft',
            'duplicate_mode' => 'update',
            'default_type'   => 'land',
            'default_area'   => 'Aftabnagar',
        ]);

        $thirdImportResponse->assertStatus(200);
        $thirdImportResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 0,
            'updated'   => 2,
            'skipped'   => 0,
        ]);

        $this->assertEquals(2, Property::count());
    }

    public function test_agent_importer_via_google_sheet(): void
    {
        $csvData = "name,phone,whatsapp,role,agency,rating,notes\n" .
                   "Rafiq Talukdar,01711223344,01711223344,agent,Trust Real Estate,4.9,আফতাবনগর অভিজ্ঞ ব্রোকার\n" .
                   "Kamal Uddin,01811223344,01811223344,owner,Direct Owner,5.0,সরাসরি প্লট মালিক\n";

        Http::fake([
            '*docs.google.com*' => Http::response($csvData, 200, ['Content-Type' => 'text/csv']),
        ]);

        $mappings = [
            'name'     => 'name',
            'phone'    => 'phone',
            'whatsapp' => 'whatsapp_number',
            'role'     => 'role',
            'agency'   => 'agency_name',
            'rating'   => 'rating',
            'notes'    => 'notes',
        ];

        // 1. First Import: Create Agents
        $importResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'agent',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/agent-sheet/edit',
            'mappings'       => $mappings,
            'duplicate_mode' => 'skip',
            'default_role'   => 'agent',
            'default_rating' => 5.0,
            'is_verified'    => 1,
        ]);

        $importResponse->assertStatus(200);
        $importResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 2,
            'updated'   => 0,
            'skipped'   => 0,
        ]);

        $agentUser = User::where('phone', '01711223344')->first();
        $this->assertNotNull($agentUser);
        $this->assertEquals('Rafiq Talukdar', $agentUser->name);
        $this->assertEquals('agent', $agentUser->role);
        $this->assertEquals('Trust Real Estate', $agentUser->agentProfile?->agency_name);
        $this->assertEquals(4.9, (float)$agentUser->agentProfile?->admin_fixed_rating);
        $this->assertTrue((bool)$agentUser->agentProfile?->is_verified_direct_agent);

        $ownerUser = User::where('phone', '01811223344')->first();
        $this->assertNotNull($ownerUser);
        $this->assertEquals('owner', $ownerUser->role);

        // 2. Second Import: Skip duplicates
        $skipResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'agent',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/agent-sheet/edit',
            'mappings'       => $mappings,
            'duplicate_mode' => 'skip',
        ]);

        $skipResponse->assertStatus(200);
        $skipResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 0,
            'updated'   => 0,
            'skipped'   => 2,
        ]);

        // 3. Third Import: Update existing
        $updateResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'agent',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/agent-sheet/edit',
            'mappings'       => $mappings,
            'duplicate_mode' => 'update',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 0,
            'updated'   => 2,
            'skipped'   => 0,
        ]);
    }

    public function test_buyer_importer_via_google_sheet(): void
    {
        $csvData = "name,phone,whatsapp,type,area,blocks,budget,timeline,notes\n" .
                   "Tanvir Hasan,01911223344,01911223344,land,Aftabnagar,C/D,1.2 cr,জরুরি,৩ কাঠা দক্ষিণমুখী প্লট চাই\n" .
                   "Nusrat Jahan,01611223344,01611223344,flat,Aftabnagar,B,85 lac,২ মাস,১৫০০ স্কয়ার ফিট ফ্ল্যাট চাই\n";

        Http::fake([
            '*docs.google.com*' => Http::response($csvData, 200, ['Content-Type' => 'text/csv']),
        ]);

        $mappings = [
            'name'     => 'name',
            'phone'    => 'phone',
            'whatsapp' => 'whatsapp_number',
            'type'     => 'preferred_type',
            'area'     => 'preferred_areas',
            'blocks'   => 'preferred_blocks',
            'budget'   => 'budget_range',
            'timeline' => 'purchase_timeline',
            'notes'    => 'notes',
        ];

        // 1. First Import: Create Buyers & Auto Demands
        $importResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'        => 'buyer',
            'sheet_url'          => 'https://docs.google.com/spreadsheets/d/buyer-sheet/edit',
            'mappings'           => $mappings,
            'duplicate_mode'     => 'skip',
            'default_type'       => 'land',
            'default_area'       => 'Aftabnagar',
            'auto_create_demand' => 1,
        ]);

        $importResponse->assertStatus(200);
        $importResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 2,
            'updated'   => 0,
            'skipped'   => 0,
        ]);

        $buyerUser = User::where('phone', '01911223344')->first();
        $this->assertNotNull($buyerUser);
        $this->assertEquals('Tanvir Hasan', $buyerUser->name);
        $this->assertEquals('buyer', $buyerUser->role);
        $this->assertEquals('land', $buyerUser->preferred_type);

        // Verify BuyerDemand was auto created
        $demand = \App\Models\BuyerDemand::where('buyer_phone', '01911223344')->first();
        $this->assertNotNull($demand);
        $this->assertEquals('active', $demand->status);
        $this->assertEquals('land', $demand->property_type);
        $this->assertStringContainsString('৩ কাঠা', $demand->notes);

        // 2. Second Import: Skip duplicates
        $skipResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'buyer',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/buyer-sheet/edit',
            'mappings'       => $mappings,
            'duplicate_mode' => 'skip',
        ]);

        $skipResponse->assertStatus(200);
        $skipResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 0,
            'updated'   => 0,
            'skipped'   => 2,
        ]);
    }

    public function test_property_importer_maps_status_column_and_default_status(): void
    {
        $csvData = "road,plot number,katha,price,status\n" .
                   "5,101,3,80 lac,live\n" .
                   "6,102,5,1.5 cr,booked\n" .
                   "7,103,4,90 lac,\n";

        Http::fake([
            '*docs.google.com*' => Http::response($csvData, 200, ['Content-Type' => 'text/csv']),
        ]);

        $mappings = [
            'road'        => 'road_number',
            'plot number' => 'plot_number',
            'katha'       => 'size',
            'price'       => 'asking_price',
            'status'      => 'status',
        ];

        $importResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'property',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/property-status-sheet/edit',
            'mappings'       => $mappings,
            'duplicate_mode' => 'all',
            'default_type'   => 'land',
            'default_area'   => 'Aftabnagar',
            'default_status' => 'pending',
        ]);

        $importResponse->assertStatus(200);
        $importResponse->assertJson([
            'success'   => true,
            'processed' => 3,
            'created'   => 3,
        ]);

        $p1 = Property::where('plot_number', '101')->first();
        $this->assertNotNull($p1);
        $this->assertEquals('live', $p1->status);

        $p2 = Property::where('plot_number', '102')->first();
        $this->assertNotNull($p2);
        $this->assertEquals('booked', $p2->status);

        $p3 = Property::where('plot_number', '103')->first();
        $this->assertNotNull($p3);
        $this->assertEquals('pending_verification', $p3->status); // uses default_status (normalized)
    }

    public function test_flat_importer_tab_and_preset_is_seeded(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sheet-importer.index', ['tab' => 'flat']));
        $response->assertStatus(200);
        $response->assertSee('ফ্ল্যাট ও অ্যাপার্টমেন্ট স্ট্যান্ডার্ড শিট (Flats Standard)');
        $response->assertSee('ফ্ল্যাট ও অ্যাপার্টমেন্ট ইমপোর্টার');

        $this->assertDatabaseHas('sheet_import_presets', [
            'entity_type' => 'flat',
            'name'        => 'ফ্ল্যাট ও অ্যাপার্টমেন্ট স্ট্যান্ডার্ড শিট (Flats Standard)',
        ]);
    }

    public function test_flat_importer_via_google_sheet_creates_flat_and_details(): void
    {
        $csvData = "project,area,block,road,size_sqft,floor,bed,bath,balcony,facing,parking,price,handover,developer,agent,phone,notes\n" .
                   "Green Valley,Aftabnagar,C,5,1350,4,3,3,2,South,Yes,95 lac,Ready,Sheltech,Rahim Mia,01711998877,সরাসরি ফ্ল্যাট মালিকের সাথে জরুরি বায়া দলিল\n" .
                   "Lake View,Aftabnagar,D,12,1850,7,4,4,3,North-East,Yes,1.65 cr,Under Construction,Navana,Karim Agent,01811998877,ব্যাংক লোন সুবিধা আছে\n";

        Http::fake([
            '*docs.google.com*' => Http::response($csvData, 200, ['Content-Type' => 'text/csv']),
        ]);

        $mappings = [
            'project'   => 'project_name',
            'area'      => 'area',
            'block'     => 'block',
            'road'      => 'road_number',
            'size_sqft' => 'flat_size_sqft',
            'floor'     => 'floor_number',
            'bed'       => 'bedrooms',
            'bath'      => 'bathrooms',
            'balcony'   => 'balconies',
            'facing'    => 'facing',
            'parking'   => 'parking_available',
            'price'     => 'asking_price',
            'developer' => 'developer_name',
            'handover'  => 'completion_status',
            'agent'     => 'owner_name',
            'phone'     => 'owner_phone',
            'notes'     => 'hidden_note',
        ];

        $importResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'flat',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/flat-sheet-id/edit',
            'mappings'       => $mappings,
            'destination'    => 'draft',
            'duplicate_mode' => 'skip',
            'default_area'   => 'Aftabnagar',
        ]);

        $importResponse->assertStatus(200);
        $importResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 2,
            'updated'   => 0,
            'skipped'   => 0,
        ]);

        // Verify property was created in draft status and type flat
        $flatProperty = Property::where('block', 'C')
            ->where('type', 'flat')
            ->first();

        $this->assertNotNull($flatProperty);
        $this->assertEquals('draft', $flatProperty->status);
        $this->assertEquals('Aftabnagar', $flatProperty->area);
        $this->assertStringContainsString('1350 স্কয়ার ফিট', $flatProperty->title);
        $this->assertStringContainsString('3 বেড', $flatProperty->title);
        $this->assertStringContainsString('Green Valley', $flatProperty->title);

        // Verify PropertyFlatDetail
        $detail = $flatProperty->flatDetail;
        $this->assertNotNull($detail);
        $this->assertEquals(1350.0, (float)$detail->flat_size_sqft);
        $this->assertEquals(4, $detail->floor_number);
        $this->assertEquals(3, $detail->bedrooms);
        $this->assertEquals(3, $detail->bathrooms);
        $this->assertEquals(2, $detail->balconies);
        $this->assertEquals('Green Valley', $detail->project_name);
        $this->assertEquals('Sheltech', $detail->developer_name);
        $this->assertEquals('ready', $detail->completion_status);
        $this->assertTrue($detail->parking_available);
        $this->assertEquals(9500000.0, (float)$detail->price_total);

        // Verify hidden note and smart tags
        $this->assertEquals('সরাসরি ফ্ল্যাট মালিকের সাথে জরুরি বায়া দলিল', $flatProperty->hidden_note);
        $this->assertIsArray($flatProperty->hidden_tags);
        $this->assertContains('বায়া দলিল ক্লিয়ার', $flatProperty->hidden_tags);

        // Verify Direct Source
        $source = $flatProperty->directSources()->first();
        $this->assertNotNull($source);
        $this->assertEquals('Rahim Mia', $source->owner_name);
        $this->assertEquals('01711998877', $source->owner_phone);

        // Second import with skip should skip duplicates
        $secondResponse = $this->actingAs($this->admin)->postJson(route('admin.sheet-importer.import'), [
            'entity_type'    => 'flat',
            'sheet_url'      => 'https://docs.google.com/spreadsheets/d/flat-sheet-id/edit',
            'mappings'       => $mappings,
            'destination'    => 'draft',
            'duplicate_mode' => 'skip',
            'default_area'   => 'Aftabnagar',
        ]);

        $secondResponse->assertStatus(200);
        $secondResponse->assertJson([
            'success'   => true,
            'processed' => 2,
            'created'   => 0,
            'skipped'   => 2,
        ]);

        $this->assertEquals(2, Property::where('type', 'flat')->count());
    }
}
