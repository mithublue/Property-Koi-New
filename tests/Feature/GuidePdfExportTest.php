<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidePdfExportTest extends TestCase
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

    public function test_guest_and_regular_user_cannot_access_export(): void
    {
        $guestResponse = $this->get(route('admin.guides.export'));
        $guestResponse->assertRedirect('/appcenter');

        $userResponse = $this->actingAs($this->regularUser)->get(route('admin.guides.export'));
        $userResponse->assertRedirect();
    }

    public function test_admin_can_view_export_configuration_page(): void
    {
        Guide::create([
            'title'        => 'জমির দলিল যাচাই ২০২৬',
            'slug'         => 'jomir-dolil-jachai-2026',
            'category'     => 'legal_docs',
            'content'      => '<p>দলিল যাচাইয়ের বিস্তারিত নিয়ম।</p>',
            'excerpt'      => 'দলিল যাচাই করার নিয়মাবলি',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.guides.export'));

        $response->assertStatus(200);
        $response->assertSee('নলেজ হাব ও প্রপার্টি গাইডস PDF এক্সপোর্টার');
        $response->assertSee('জমির দলিল যাচাই ২০২৬');
        $response->assertSee('ধাপ ১: প্রকাশিত তারিখ রেঞ্জ ও ক্যাটাগরি ফিল্টার');
        $response->assertSee('ধাপ ২: এক্সপোর্টের আওতা ও নির্দিষ্ট গাইড নির্বাচন');
        $response->assertSee('ধাপ ৩: PDF লেআউট ও ব্র্যান্ডিং সেটিংস');
    }

    public function test_admin_can_generate_pdf_booklet_for_all_guides(): void
    {
        Guide::create([
            'title'        => 'প্রথম টেস্ট গাইড',
            'slug'         => 'first-test-guide',
            'category'     => 'legal_docs',
            'content'      => '<p>প্রথম গাইডের মূল বক্তব্য।</p>',
            'excerpt'      => 'প্রথম সারসংক্ষেপ',
            'is_published' => true,
        ]);

        Guide::create([
            'title'        => 'দ্বিতীয় টেস্ট গাইড',
            'slug'         => 'second-test-guide',
            'category'     => 'buying_guides',
            'content'      => '<p>দ্বিতীয় গাইডের মূল বক্তব্য।</p>',
            'excerpt'      => 'দ্বিতীয় সারসংক্ষেপ',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.guides.export.generate'), [
            'export_scope'   => 'all',
            'include_cover'  => '1',
            'include_toc'    => '1',
            'include_faqs'   => '1',
            'include_images' => '1',
            'auto_print'     => '1',
        ]);

        $response->assertStatus(200);
        $response->assertSee('সূচিপত্র (Table of Contents)');
        $response->assertSee('প্রথম টেস্ট গাইড');
        $response->assertSee('দ্বিতীয় টেস্ট গাইড');
        $response->assertSee('প্রিন্ট করুন / Save as PDF');
    }

    public function test_admin_can_generate_pdf_for_selected_guides_only(): void
    {
        $guide1 = Guide::create([
            'title'        => 'নির্বাচিত গাইড এ',
            'slug'         => 'selected-guide-a',
            'category'     => 'legal_docs',
            'content'      => '<p>কনটেন্ট এ</p>',
            'excerpt'      => 'সারসংক্ষেপ এ',
            'is_published' => true,
        ]);

        $guide2 = Guide::create([
            'title'        => 'অনির্বাচিত গাইড বি',
            'slug'         => 'unselected-guide-b',
            'category'     => 'market_insights',
            'content'      => '<p>কনটেন্ট বি</p>',
            'excerpt'      => 'সারসংক্ষেপ বি',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.guides.export.generate'), [
            'export_scope' => 'selected',
            'ids'          => [$guide1->id],
        ]);

        $response->assertStatus(200);
        $response->assertSee('নির্বাচিত গাইড এ');
        $response->assertDontSee('অনির্বাচিত গাইড বি');
    }

    public function test_bulk_action_export_pdf_redirects_to_export_screen(): void
    {
        $guide1 = Guide::create([
            'title'        => 'বাল্ক টেস্ট গাইড ১',
            'slug'         => 'bulk-test-guide-1',
            'category'     => 'legal_docs',
            'content'      => '<p>কনটেন্ট ১</p>',
            'is_published' => true,
        ]);

        $guide2 = Guide::create([
            'title'        => 'বাল্ক টেস্ট গাইড ২',
            'slug'         => 'bulk-test-guide-2',
            'category'     => 'investment',
            'content'      => '<p>কনটেন্ট ২</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.guides.bulk-action'), [
            'action' => 'export_pdf',
            'ids'    => [$guide1->id, $guide2->id],
        ]);

        $response->assertRedirect(route('admin.guides.export', [
            'ids' => "{$guide1->id},{$guide2->id}",
        ]));
    }
}
