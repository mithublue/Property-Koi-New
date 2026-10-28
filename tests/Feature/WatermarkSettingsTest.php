<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WatermarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WatermarkSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_displays_watermark_configuration_options()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_wm@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000001', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $response = $this->actingAs($admin)->get(route('admin.settings.index', ['tab' => 'media']));
        $response->assertStatus(200);
        $response->assertSee('অটো ইমেজ ওয়াটারমার্ক');
        $response->assertSee('adm_watermark_enabled');
        $response->assertSee('adm_watermark_type');
        $response->assertSee('adm_watermark_logo_file');
        $response->assertSee('adm_watermark_position');
        $response->assertSee('adm_watermark_scale');
    }

    public function test_admin_can_upload_custom_watermark_logo_and_save_position()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_wm2@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000002', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $fakeLogo = UploadedFile::fake()->image('my-brand-logo.png', 180, 60);

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'tab' => 'media',
            'adm_watermark_enabled' => 'yes',
            'adm_watermark_type' => 'image',
            'adm_watermark_logo_file' => $fakeLogo,
            'adm_watermark_position' => 'top-left',
            'adm_watermark_opacity' => 90,
            'adm_watermark_scale' => 25,
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'media']));

        $this->assertEquals('yes', Setting::get('adm_watermark_enabled'));
        $this->assertEquals('image', Setting::get('adm_watermark_type'));
        $this->assertEquals('top-left', Setting::get('adm_watermark_position'));
        $this->assertEquals('90', Setting::get('adm_watermark_opacity'));
        $this->assertEquals('25', Setting::get('adm_watermark_scale'));
        $this->assertStringContainsString('uploads/watermark/watermark_logo_', Setting::get('adm_watermark_logo'));

        // Test Watermark API generation
        $testResponse = $this->actingAs($admin)->postJson(route('admin.settings.test-watermark'));
        $testResponse->assertStatus(200);
        $testResponse->assertJson(['success' => true]);
    }
}
