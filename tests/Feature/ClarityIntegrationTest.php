<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\ClarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClarityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('adm_clarity_project_id', 'clarity_test_proj_99');
        Setting::set('adm_clarity_enabled', 'yes');
        Setting::set('adm_clarity_exclude_admin', 'yes');
        Setting::set('adm_clarity_track_roles', 'yes');
    }

    public function test_clarity_service_configuration_and_rendering()
    {
        $this->assertEquals('clarity_test_proj_99', ClarityService::getProjectId());
        $this->assertTrue(ClarityService::isEnabled());
        $this->assertTrue(ClarityService::isConfigured());

        // Guest user should be tracked
        auth()->logout();
        $this->assertTrue(ClarityService::shouldRender());

        // Normal buyer should be tracked
        $buyer = new User(['role' => 'buyer', 'name' => 'Test Buyer']);
        $buyer->id = 999;
        $this->actingAs($buyer);
        $this->assertTrue(ClarityService::shouldRender());

        // Admin should be excluded
        $admin = new User(['role' => 'admin', 'name' => 'Test Admin']);
        $admin->id = 1;
        $this->actingAs($admin);
        $this->assertFalse(ClarityService::shouldRender());
    }

    public function test_admin_clarity_dashboard_view_loads_for_admin()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_test_clarity@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000000', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $response = $this->actingAs($admin)->get(route('admin.clarity'));
        $response->assertStatus(200);
        $response->assertSee('Microsoft Clarity কন্ট্রোল ড্যাশবোর্ড');
        $response->assertSee('clarity_test_proj_99');
    }

    public function test_admin_settings_page_contains_clarity_card()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_test_clarity@propertykoi.com'],
            ['name' => 'Admin User', 'phone' => '01700000000', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        $response = $this->actingAs($admin)->get(route('admin.settings.index', ['tab' => 'notifications']));
        $response->assertStatus(200);
        $response->assertSee('Microsoft Clarity সেশন রেকর্ডিং ও হিটম্যাপ');
        $response->assertSee('adm_clarity_project_id');
        $response->assertSee('clarity_test_proj_99');
    }

    public function test_frontend_renders_clarity_script_for_guests()
    {
        auth()->logout();
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('https://www.clarity.ms/tag/');
        $response->assertSee('clarity_test_proj_99');
        $response->assertSee('turbo:load');
    }
}
