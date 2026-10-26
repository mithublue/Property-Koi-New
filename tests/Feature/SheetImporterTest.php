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
}
