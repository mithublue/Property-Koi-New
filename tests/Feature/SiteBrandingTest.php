<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiteBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_displays_branding_fields()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_branding@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000000', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $response = $this->actingAs($admin)->get(route('admin.settings.index', ['tab' => 'general']));
        $response->assertStatus(200);
        $response->assertSee('সাইট পরিচিতি, লোগো ও ফেভিকন');
        $response->assertSee('adm_site_name');
        $response->assertSee('adm_site_tagline');
        $response->assertSee('adm_site_logo_file');
        $response->assertSee('adm_site_favicon_file');
        $response->assertSee('adm_site_email');
        $response->assertSee('adm_site_phone');
    }

    public function test_updating_site_branding_and_uploading_logo()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_branding2@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000000', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $fakeLogo = UploadedFile::fake()->image('custom-logo.png', 200, 60);

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'tab' => 'general',
            'adm_site_name' => 'MyRealEstateBD',
            'adm_site_tagline' => 'Best Properties in Dhaka',
            'adm_site_email' => 'hello@myrealestatebd.com',
            'adm_site_phone' => '01888-999000',
            'adm_site_logo_file' => $fakeLogo,
            'adm_site_copyright' => '© 2026 MyRealEstateBD. All Rights Reserved.',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'general']));

        $this->assertEquals('MyRealEstateBD', Setting::get('adm_site_name'));
        $this->assertEquals('Best Properties in Dhaka', Setting::get('adm_site_tagline'));
        $this->assertEquals('hello@myrealestatebd.com', Setting::get('adm_site_email'));
        $this->assertEquals('01888-999000', Setting::get('adm_site_phone'));
        $this->assertStringContainsString('uploads/branding/logo_', Setting::get('adm_site_logo'));

        // Frontend home verification
        auth()->logout();
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('MyRealEstateBD');
        $homeResponse->assertSee('hello@myrealestatebd.com');
        $homeResponse->assertSee('01888-999000');
        $homeResponse->assertSee('© 2026 MyRealEstateBD. All Rights Reserved.');
    }
}
