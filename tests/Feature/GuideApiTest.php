<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyGuideApiKey;
use App\Models\Guide;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'test_secret_guide_api_key_2026';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('adm_api_secret_key', $this->apiKey);
    }

    public function test_public_can_list_guides_and_topics_via_api(): void
    {
        Guide::create([
            'title'        => 'পাবলিক এপিআই টেস্ট গাইড',
            'slug'         => 'public-api-test-guide',
            'category'     => 'legal_docs',
            'content'      => '<p>কনটেন্ট বডি</p>',
            'excerpt'      => 'সারসংক্ষেপ',
            'is_published' => true,
        ]);

        $listRes = $this->getJson(route('api.v1.guides.index'));
        $listRes->assertStatus(200);
        $listRes->assertJsonFragment(['slug' => 'public-api-test-guide']);

        $topicsRes = $this->getJson(route('api.v1.guides.topics'));
        $topicsRes->assertStatus(200);
        $topicsRes->assertJsonStructure([
            'success',
            'total_topics',
            'topics' => [
                '*' => ['id', 'title', 'slug', 'category', 'category_label', 'url']
            ]
        ]);
    }

    public function test_api_store_fails_without_authentication_key(): void
    {
        $response = $this->postJson(route('api.v1.guides.store'), [
            'title'   => 'অননুমোদিত এপিআই টেস্ট',
            'content' => '<p>কনটেন্ট</p>',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_check_uniqueness_detects_duplicate_or_similar_topics(): void
    {
        Guide::create([
            'title'        => 'আফতাবনগরে প্লট কেনার নিয়ম ও দাম',
            'slug'         => 'aftabnagore-plot-kenar-niyam',
            'category'     => 'buying_guides',
            'content'      => '<p>কনটেন্ট</p>',
            'is_published' => true,
        ]);

        // Exact slug collision
        $slugCollisionRes = $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson(route('api.v1.guides.check-uniqueness'), [
                'title' => 'অন্য টাইটেল কিন্তু একই স্ল্যাগ',
                'slug'  => 'aftabnagore-plot-kenar-niyam',
            ]);

        $slugCollisionRes->assertStatus(200);
        $slugCollisionRes->assertJson([
            'success'   => true,
            'is_unique' => false,
        ]);

        // Completely new unique topic
        $uniqueRes = $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson(route('api.v1.guides.check-uniqueness'), [
                'title' => 'নতুন সম্পূর্ণ অনন্য বিষয় ২০২৬',
                'slug'  => 'notun-shompurno-ononno-bishoy-2026',
            ]);

        $uniqueRes->assertStatus(200);
        $uniqueRes->assertJson([
            'success'   => true,
            'is_unique' => true,
        ]);
    }

    public function test_authenticated_client_can_publish_guide_via_api(): void
    {
        $payload = [
            'title'            => 'এপিআই পাবলিশ টেস্ট গাইড ২০২৬',
            'slug'             => 'api-publish-test-guide-2026',
            'category'         => 'investment',
            'excerpt'          => 'এপিআই এর মাধ্যমে প্রকাশিত টেস্ট গাইডের সারসংক্ষেপ।',
            'content'          => '<h2>প্রধান শিরোনাম</h2><p>এটি একটি বিস্তারিত অনুচ্ছেদ।</p>',
            'reading_time_min' => 6,
            'seo_title'        => 'এপিআই পাবলিশ টেস্ট গাইড | PropertyKoi',
            'seo_description'  => 'মেটা ডেসক্রিপশন টেস্ট।',
            'seo_keywords'     => 'api test, guide test, propertykoi',
            'faqs'             => [
                ['question' => 'এটি কি কাজ করে?', 'answer' => 'হ্যাঁ, এটি শতভাগ কাজ করে।']
            ],
            'en_profile'       => [
                'headline'    => 'API Publish Test Guide 2026',
                'description' => 'English meta description test',
                'keywords'    => 'api test, guide test english',
                'abstract'    => 'Abstract test for AI answer engines',
                'faqs'        => [
                    ['question' => 'Does this work in English?', 'answer' => 'Yes, absolutely.']
                ]
            ],
            'is_featured'      => true,
            'is_published'     => true,
        ];

        $response = $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson(route('api.v1.guides.store'), $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('guides', [
            'slug'        => 'api-publish-test-guide-2026',
            'category'    => 'investment',
            'author_name' => 'Property Koi Desk',
        ]);

        $guide = Guide::where('slug', 'api-publish-test-guide-2026')->first();
        $this->assertNotNull($guide->en_profile);
        $this->assertEquals('API Publish Test Guide 2026', $guide->en_profile['headline']);

        // Check show page loads with the new guide
        $showResponse = $this->get(route('guides.show', $guide->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('এপিআই পাবলিশ টেস্ট গাইড ২০২৬');
        $showResponse->assertSee('এটি কি কাজ করে?');
        $showResponse->assertSee('Property Koi Desk');
    }

    public function test_admin_user_can_authenticate_without_api_key_header(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.guides.store'), [
                'title'   => 'অ্যাডমিন সেশন এপিআই পোস্ট',
                'slug'    => 'admin-session-api-post',
                'content' => '<p>অ্যাডমিন সেশন টেস্ট কনটেন্ট</p>',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('guides', ['slug' => 'admin-session-api-post']);
    }

    public function test_published_ready_vs_under_construction_guide_has_rich_bilingual_seo_and_aeo(): void
    {
        $slug = 'ready-flat-vs-under-construction-flat-dhaka-buying-guide-2026';
        $this->artisan('guide:publish', [
            '--file' => 'content/guides/ready-flat-vs-under-construction-flat-dhaka-buying-guide-2026.json',
            '--force' => true,
        ])->assertExitCode(0);

        // 1. API show endpoint returns rich SEO metadata
        $apiRes = $this->getJson(route('api.v1.guides.show', $slug));
        $apiRes->assertStatus(200);
        $apiRes->assertJsonPath('data.slug', $slug);
        $apiRes->assertJsonPath('data.en_profile.headline', 'Ready Flat vs Under-Construction Flat in Dhaka: Cost, Risks and Decision Matrix (2026 Guide)');

        // 2. Public Web Show Page loads with 200 and renders full AEO and SEO elements
        $webRes = $this->get(route('guides.show', $slug));
        $webRes->assertStatus(200);
        $webRes->assertSee('রেডি ফ্ল্যাট বনাম নির্মাণাধীন ফ্ল্যাট');
        $webRes->assertSee('এক নজরে রেডি বনাম নির্মাণাধীন ফ্ল্যাটের মূল পার্থক্য');
        $webRes->assertSee('রিয়েল এস্টেট উন্নয়ন ও ব্যবস্থাপনা আইন ২০১০');
        $webRes->assertSee('ধারা ১৫');
        $webRes->assertSee('ধারা ২০');
        $webRes->assertSee('Property Koi Desk');
        $webRes->assertSee('রেডি ফ্ল্যাট ও নির্মাণাধীন ফ্ল্যাটের মূল্যের মধ্যে পার্থক্যের হার কত?');

        // Verify Schema JSON-LD contains bilingual Article and FAQPage
        $content = $webRes->getContent();
        $this->assertStringContainsString('application/ld+json', $content);
        $this->assertStringContainsString('Ready Flat vs Under-Construction Flat in Dhaka', $content);
        $this->assertStringContainsString('How much cheaper is an under-construction flat compared to a ready flat in Dhaka?', $content);
        $this->assertStringContainsString('Under Section 15 and 20 of the Real Estate Development and Management Act 2010', $content);
    }
}
