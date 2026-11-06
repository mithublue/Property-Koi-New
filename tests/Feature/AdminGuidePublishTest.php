<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Setting;
use App\Models\User;
use App\Services\GuidePublisherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminGuidePublishTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'buyer',
        ]);
    }

    public function test_admin_can_view_publish_button_and_modals_on_guides_index(): void
    {
        Guide::create([
            'title'        => 'টেস্ট আনপাবলিশড ব্লগ',
            'slug'         => 'test-unpublished-blog',
            'category'     => 'legal_docs',
            'content'      => '<p>কনটেন্ট বডি</p>',
            'excerpt'      => 'সারসংক্ষেপ',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.guides.index'));
        $response->assertStatus(200);
        $response->assertSee('🚀 লাইভ সাইটে পাবলিশ');
        $response->assertSee('গাইডস লাইভ পাবলিশ সেটিংস');
        $response->assertSee('লেটেস্ট / অপ্রকাশিত ব্লগসমূহ');
        $response->assertSee('ডিফল্ট প্রস্তাবিত');
        $response->assertSee('সরাসরি প্রকাশিত (Published)');
    }

    public function test_non_admin_cannot_access_or_trigger_publishing(): void
    {
        $this->actingAs($this->regularUser)->postJson(route('admin.guides.publish-live'))->assertStatus(403);
        $this->actingAs($this->regularUser)->postJson(route('admin.guides.publish-settings'))->assertStatus(403);
    }

    public function test_admin_can_save_custom_publishing_settings(): void
    {
        $postData = [
            'live_site_url'  => 'https://propertykoi.com',
            'api_key'        => 'my_custom_secret_key_99',
            'publish_scope'  => 'specific',
            'specific_ids'   => [1, 2, 5],
            'default_status' => 'published',
        ];

        $res = $this->actingAs($this->admin)->post(route('admin.guides.publish-settings'), $postData);
        $res->assertRedirect(route('admin.guides.index'));
        $res->assertSessionHas('success');

        $settings = GuidePublisherService::getSettings();
        $this->assertEquals('https://propertykoi.com', $settings['live_site_url']);
        $this->assertEquals('my_custom_secret_key_99', $settings['api_key']);
        $this->assertEquals('specific', $settings['publish_scope']);
        $this->assertEquals([1, 2, 5], $settings['specific_ids']);
        $this->assertEquals('published', $settings['default_status']);
    }

    public function test_admin_can_publish_unpublished_guides_to_live_site(): void
    {
        Http::fake([
            'https://propertykoi.com/api/v1/guides' => Http::response([
                'success' => true,
                'action'  => 'created',
                'message' => 'Published successfully',
            ], 200),
        ]);

        $guide = Guide::create([
            'title'        => 'আফতাবনগর নতুন রেডি ফ্ল্যাট কেনার গাইড ২০২৬',
            'slug'         => 'aftabnagar-new-ready-flat-guide-2026',
            'category'     => 'buying_guides',
            'content'      => '<p>পূর্ণাঙ্গ বিস্তারিত গাইড</p>',
            'excerpt'      => 'আফতাবনগরের ফ্ল্যাট তথ্য',
            'is_published' => false,
        ]);

        $this->assertFalse($guide->is_published);

        $res = $this->actingAs($this->admin)->post(route('admin.guides.publish-live'), [
            'scope'          => 'latest_unpublished',
            'default_status' => 'published',
            'live_site_url'  => 'https://propertykoi.com',
            'api_key'        => 'propertykoi_guides_sec_2026',
        ]);

        $res->assertRedirect(route('admin.guides.index'));
        $res->assertSessionHas('success');

        // Assert HTTP request was dispatched with proper headers & payload
        Http::assertSent(function ($request) use ($guide) {
            return $request->url() === 'https://propertykoi.com/api/v1/guides'
                && $request->header('X-API-KEY')[0] === 'propertykoi_guides_sec_2026'
                && $request['slug'] === $guide->slug
                && $request['is_published'] === true
                && $request['update_existing'] === true;
        });

        // Local guide should now be marked as published
        $this->assertTrue($guide->fresh()->is_published);
    }

    public function test_admin_can_publish_single_guide_to_live_site(): void
    {
        Http::fake([
            'https://propertykoi.com/api/v1/guides' => Http::response([
                'success' => true,
                'action'  => 'updated',
                'message' => 'Single guide synced',
            ], 200),
        ]);

        $guide = Guide::create([
            'title'        => 'জমির নামজারি ও জমাভাগ যাচাই নির্দেশিকা',
            'slug'         => 'land-namjari-jomabhag-guide',
            'category'     => 'legal_docs',
            'content'      => '<p>নামজারি নিয়ম</p>',
            'excerpt'      => 'নামজারি যাচাই',
            'is_published' => false,
        ]);

        $res = $this->actingAs($this->admin)->post(route('admin.guides.publish-single-live', $guide));
        $res->assertRedirect();
        $res->assertSessionHas('success');

        Http::assertSent(function ($request) use ($guide) {
            return $request->url() === 'https://propertykoi.com/api/v1/guides'
                && $request['slug'] === $guide->slug;
        });

        $this->assertTrue($guide->fresh()->is_published);
    }
}
