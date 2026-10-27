<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidesAndCalculatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_guides_index_page_loads_and_displays_guides()
    {
        $guide = Guide::create([
            'title'            => 'জমির দলিল ও নামজারি খতিয়ান যাচাই গাইড ২০২৬',
            'slug'             => 'jomir-dolil-namjari-jachai-2026',
            'category'         => 'legal_docs',
            'excerpt'          => 'জমি ক্রয়ের পূর্বে সিএস, এসএ, আরএস ও বিএস খতিয়ান যাচাই করার পূর্ণাঙ্গ নির্দেশিকা।',
            'content'          => '<h2>দলিল যাচাই</h2><p>দলিল যাচাইয়ের বিস্তারিত তথ্য।</p>',
            'reading_time_min' => 6,
            'is_published'     => true,
            'is_featured'      => true,
            'views_count'      => 10,
        ]);

        $response = $this->get(route('guides.index'));

        $response->assertStatus(200);
        $response->assertSee('জমির দলিল ও নামজারি খতিয়ান যাচাই গাইড ২০২৬');
        $response->assertSee('নলেজ হাব ও আইনি গাইডলাইন');
    }

    public function test_guide_show_page_loads_and_increments_views()
    {
        $guide = Guide::create([
            'title'            => 'ফ্ল্যাট ক্রয়ের পূর্বে রাজউক অনুমোদন যাচাই',
            'slug'             => 'flat-kenar-age-rajuk-anumodon-jachai',
            'category'         => 'buying_guides',
            'excerpt'          => 'রাজউকের নকশা ও অকুপেন্সি সার্টিফিকেট ছাড়া ফ্ল্যাট কেনা অত্যন্ত ঝুঁকিপূর্ণ।',
            'content'          => '<h2>রাজউক প্ল্যান</h2><p>অনুমোদিত নকশা পরীক্ষা করুন।</p>',
            'faqs'             => [
                ['question' => 'অনুমোদিত নকশা ছাড়া কি ফ্ল্যাটে গ্যাস-বিদ্যুৎ সংযোগ পাওয়া যায়?', 'answer' => 'না, অকুপেন্সি সার্টিফিকেট ছাড়া সংযোগ দেওয়া হয় না।']
            ],
            'reading_time_min' => 5,
            'is_published'     => true,
            'views_count'      => 5,
        ]);

        $response = $this->get(route('guides.show', $guide->slug));

        $response->assertStatus(200);
        $response->assertSee('ফ্ল্যাট ক্রয়ের পূর্বে রাজউক অনুমোদন যাচাই');
        $response->assertSee('অনুমোদিত নকশা পরীক্ষা করুন');
        $response->assertSee('অনুমোদিত নকশা ছাড়া কি ফ্ল্যাটে গ্যাস-বিদ্যুৎ সংযোগ পাওয়া যায়?');

        $guide->refresh();
        $this->assertEquals(6, $guide->views_count);
    }

    public function test_calculators_hub_and_sub_pages_load()
    {
        $hubResponse = $this->get(route('calculators.index'));
        $hubResponse->assertStatus(200);
        $hubResponse->assertSee('রেজিস্ট্রেশন ফি ও ট্যাক্স ক্যালকুলেটর');
        $hubResponse->assertSee('ল্যান্ড শেয়ার ROI ও সেভিংস ক্যালকুলেটর');

        $regResponse = $this->get(route('calculators.registration'));
        $regResponse->assertStatus(200);
        $regResponse->assertSee('স্ট্যাম্প শুল্ক (Stamp Duty)');
        $regResponse->assertSee('নিবন্ধন ফি (Registration Fee)');
        $regResponse->assertSee('উৎসে আয়কর (AIT - ধারা ৫৩এইচ)');

        $shareResponse = $this->get(route('calculators.land-share'));
        $shareResponse->assertStatus(200);
        $shareResponse->assertSee('ল্যান্ড শেয়ার প্রকল্পে');
        $shareResponse->assertSee('কত টাকা সাশ্রয় হবে?');
        $shareResponse->assertSee('কাঙ্ক্ষিত ফ্ল্যাটের সাইজ');
    }

    public function test_valuation_page_and_api_estimate_work()
    {
        $valResponse = $this->get(route('valuation.index'));
        $valResponse->assertStatus(200);
        $valResponse->assertSee('স্মার্ট প্রপার্টি ভ্যালুয়েশন এস্টিমেটর');
        $valResponse->assertSee('আফতাবনগর (Aftabnagar)');

        $apiResponse = $this->postJson(route('valuation.estimate'), [
            'area'       => 'aftabnagar',
            'type'       => 'flat',
            'size'       => 1350,
            'road_width' => 40,
            'is_corner'  => true,
            'has_parking'=> true,
        ]);

        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure([
            'success',
            'estimate' => [
                'area_name',
                'property_type',
                'min_estimate',
                'max_estimate',
                'fair_market_value',
                'confidence_score',
            ]
        ]);
        $this->assertTrue($apiResponse->json('estimate.fair_market_value') > 0);
    }

    public function test_admin_guides_management_crud()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_guide@propertykoi.com'],
            ['name' => 'Admin Guide User', 'phone' => '01711223344', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        // 1. List
        $res = $this->actingAs($admin)->get(route('admin.guides.index'));
        $res->assertStatus(200);

        // 2. Create
        $createRes = $this->actingAs($admin)->post(route('admin.guides.store'), [
            'title'            => 'টেস্ট গাইড ২০২৬',
            'slug'             => 'test-guide-2026',
            'category'         => 'investment',
            'excerpt'          => 'টেস্ট সারসংক্ষেপ',
            'content'          => '<p>টেস্ট কনটেন্ট বডি</p>',
            'reading_time_min' => 4,
            'is_published'     => 1,
            'is_featured'      => 1,
            'faq_questions'    => ['প্রশ্ন ১?'],
            'faq_answers'      => ['উত্তর ১।'],
        ]);

        $createRes->assertRedirect(route('admin.guides.index'));
        $this->assertDatabaseHas('guides', ['slug' => 'test-guide-2026']);

        $createdGuide = Guide::where('slug', 'test-guide-2026')->first();

        // 3. Toggle Published
        $toggleRes = $this->actingAs($admin)->post(route('admin.guides.toggle-published', $createdGuide));
        $toggleRes->assertStatus(302);
        $this->assertFalse((bool) $createdGuide->fresh()->is_published);

        // 4. Delete
        $deleteRes = $this->actingAs($admin)->delete(route('admin.guides.destroy', $createdGuide));
        $deleteRes->assertRedirect(route('admin.guides.index'));
        $this->assertDatabaseMissing('guides', ['id' => $createdGuide->id]);
    }

    public function test_sitemap_includes_guides_and_calculators()
    {
        $guide = Guide::create([
            'title'        => 'সাইটম্যাপ টেস্ট গাইড',
            'slug'         => 'sitemap-test-guide',
            'category'     => 'legal_docs',
            'content'      => '<p>টেস্ট</p>',
            'is_published' => true,
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('/guides', $content);
        $this->assertStringContainsString('/calculators', $content);
        $this->assertStringContainsString('/calculators/registration', $content);
        $this->assertStringContainsString('/calculators/land-share-roi', $content);
        $this->assertStringContainsString('/valuation', $content);
        $this->assertStringContainsString('/guides/sitemap-test-guide', $content);
        $this->assertStringContainsString('/aftabnagar-property-guide', $content);
    }

    public function test_aftabnagar_buyer_guide_page_loads_and_has_schema_and_price_index()
    {
        // 1. Direct page loads
        $response = $this->get(route('guides.aftabnagar'));
        $response->assertStatus(200);
        $response->assertSee('আফতাবনগর প্রপার্টি বায়ার গাইড ও');
        $response->assertSee('কাঠাপ্রতি বাজারদর ২০২৬');
        $response->assertSee('ব্লক এ (Block A)');
        $response->assertSee('ব্লক বি (Block B)');
        $response->assertSee('ব্লকভিত্তিক জমির কাঠাপ্রতি মূল্য তালিকা');
        $response->assertSee('ইএইচএল বরাদ্দ ও মূল দলিল');
        $response->assertSee('Dataset');
        $response->assertSee('Aftabnagar Real Estate Price Index 2026');
        $response->assertSee('FAQPage');

        // Verify broker/dalal claims are absent
        $content = $response->getContent();
        $this->assertStringNotContainsString('০% ব্রোকার ফি', $content);
        $this->assertStringNotContainsString('দালাল ছাড়া', $content);
        $this->assertStringNotContainsString('ব্রোকার ছাড়া', $content);

        // 2. SEO Aliases redirect 301
        $this->get('/aftabnagar')->assertRedirect('/aftabnagar-property-guide');
        $this->get('/aftabnagar-plot-price')->assertRedirect('/aftabnagar-property-guide');
        $this->get('/aftabnagar-flat-price')->assertRedirect('/aftabnagar-property-guide');
    }

    public function test_aftabnagar_search_seo_is_dynamically_generated()
    {
        // 1. Aftabnagar with Block
        $response = $this->get('/land-search?area=Aftabnagar&block=A');
        $response->assertStatus(200);
        $response->assertSee('Aftabnagar Block A Plots for Sale');

        $content = $response->getContent();
        $this->assertStringNotContainsString('০% ব্রোকার ফি', $content);
        $this->assertStringNotContainsString('দালাল ছাড়া', $content);

        // 2. Another prime area (e.g. Bashundhara)
        $bashundharaRes = $this->get('/land-search?area=Bashundhara');
        $bashundharaRes->assertStatus(200);
        $bashundharaRes->assertSee('Plots &amp; Land for Sale in Bashundhara', false);

        // 3. General search covers all of Dhaka & Bangladesh
        $generalRes = $this->get('/land-search');
        $generalRes->assertStatus(200);
        $generalRes->assertSee('Plots &amp; Land for Sale in Dhaka &amp; Bangladesh', false);
    }

    public function test_guide_seeder_runs_cleanly_and_renders_aeo_and_schema_tags()
    {
        $this->seed(\Database\Seeders\GuideSeeder::class);

        // Verify all 10 guides exist in database
        $this->assertEquals(10, Guide::count());

        $firstGuide = Guide::where('slug', 'jomir-dolil-o-namjari-khotian-jachai-guide-2026')->first();
        $this->assertNotNull($firstGuide);

        // Request page and test AEO elements
        $response = $this->get(route('guides.show', $firstGuide->slug));
        $response->assertStatus(200);
        $response->assertSee('aeo-quick-summary');
        $response->assertSee('সারসংক্ষেপ');
        $response->assertSee('Property Koi Desk');
        $response->assertDontSeeText('এআই সামারি');
        $response->assertSee('FAQPage');
        $response->assertSee('SpeakableSpecification');
        $response->assertSee('eporcha.gov.bd');
    }

    public function test_admin_one_click_seeder_endpoint_and_ui()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $regularUser = User::factory()->create([
            'role' => 'buyer',
        ]);

        // 1. Guest is redirected to login
        $this->post(route('admin.guides.seed'))->assertRedirect(route('login'));

        // 2. Regular non-admin user is forbidden
        $this->actingAs($regularUser)->postJson(route('admin.guides.seed'))->assertStatus(403);

        // 3. Admin can view guides index with 1-click seeder button
        $indexRes = $this->actingAs($admin)->get(route('admin.guides.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('১-ক্লিক ডেটাবেজ সিড');
        $indexRes->assertSee('১০টি এআই গাইড সিড / সিঙ্ক');

        // 4. Admin triggers 1-click seeder via Web
        $this->assertEquals(0, Guide::count());

        $seedRes = $this->actingAs($admin)->post(route('admin.guides.seed'), [
            'target' => 'guides',
        ]);

        $seedRes->assertRedirect(route('admin.guides.index'));
        $seedRes->assertSessionHas('success');

        // Verify that GuideSeeder executed and populated the database
        $this->assertEquals(10, Guide::count());
    }
}
