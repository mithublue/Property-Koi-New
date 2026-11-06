<?php

namespace Tests\Feature;

use App\Models\FcmDeviceToken;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuideCommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $buyer;
    protected Guide $guide;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'  => 'Super Admin',
            'email' => 'admin@propertykoi.com',
            'phone' => '01700000001',
            'role'  => 'admin',
        ]);

        $this->buyer = User::factory()->create([
            'name'  => 'Regular Buyer',
            'email' => 'buyer@example.com',
            'phone' => '01700000002',
            'role'  => 'buyer',
        ]);

        $this->guide = Guide::create([
            'title'        => 'জমির মূল দলিল ও বায়া দলিল যাচাই গাইড ২০২৬',
            'slug'         => 'jomir-dolil-jachai-guide-2026',
            'category'     => 'legal_docs',
            'content'      => '<p>দলিল যাচাইয়ের বিস্তারিত নিয়মাবলী...</p>',
            'excerpt'      => 'দলিল যাচাই করার সহজ ও আইনি পদ্ধতি।',
            'is_published' => true,
        ]);

        // Configure Telegram Admin Chat ID
        Setting::set('adm_telegram_bot_token', '123456789:ABCDEF_mock_token');
        Setting::set('adm_telegram_admin_chat_id', '987654321');
    }

    public function test_visitor_can_submit_comment_and_admin_is_notified(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
            'fcm.googleapis.com/*' => Http::response(['success' => 1], 200),
        ]);

        // Register FCM tokens: 1 for admin, 1 for regular buyer
        FcmDeviceToken::create([
            'user_id' => $this->admin->id,
            'token'   => 'admin_fcm_token_xyz',
        ]);
        FcmDeviceToken::create([
            'user_id' => $this->buyer->id,
            'token'   => 'buyer_fcm_token_abc',
        ]);

        $response = $this->post(route('guides.comments.store', $this->guide->slug), [
            'author_name'  => 'মোহাম্মদ করিম',
            'author_phone' => '01811223344',
            'comment'      => 'বায়া দলিলের ফটোকপি দিয়ে কি সাব-রেজিস্ট্রি অফিসে নামজারি করা সম্ভব?',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('comment_success');

        // Assert record created in database with pending status (false)
        $this->assertDatabaseHas('guide_comments', [
            'guide_id'     => $this->guide->id,
            'author_name'  => 'মোহাম্মদ করিম',
            'author_phone' => '01811223344',
            'is_approved'  => false,
        ]);

        // Verify Telegram message was dispatched to admin chat id
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), 'api.telegram.org')) {
                $body = $request->data();
                return ($body['chat_id'] ?? null) == '987654321'
                    && str_contains($body['text'] ?? '', 'মোহাম্মদ করিম')
                    && str_contains($body['text'] ?? '', 'jomir-dolil-jachai-guide-2026');
            }
            return true;
        });
    }

    public function test_honeypot_spam_is_trapped_and_rejected(): void
    {
        $response = $this->post(route('guides.comments.store', $this->guide->slug), [
            'author_name' => 'Spam Bot',
            'comment'     => 'Visit cheap shoes website',
            'website_hp'  => 'http://spamurl.com', // honeypot filled
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('guide_comments', [
            'author_name' => 'Spam Bot',
        ]);
    }

    public function test_pending_comments_are_not_shown_to_public_until_approved(): void
    {
        $comment = GuideComment::create([
            'guide_id'     => $this->guide->id,
            'author_name'  => 'অপ্রকাশিত পাঠক',
            'author_phone' => '01900000000',
            'comment'      => 'এই প্রশ্নটি এখনো অনুমোদিত হয়নি।',
            'is_approved'  => false,
        ]);

        $response = $this->get(route('guides.show', $this->guide->slug));
        $response->assertStatus(200);
        $response->assertDontSee('এই প্রশ্নটি এখনো অনুমোদিত হয়নি।');

        // Now admin approves it
        $comment->update(['is_approved' => true]);

        $responseApproved = $this->get(route('guides.show', $this->guide->slug));
        $responseApproved->assertStatus(200);
        $responseApproved->assertSee('এই প্রশ্নটি এখনো অনুমোদিত হয়নি।');
        $responseApproved->assertSee('অপ্রকাশিত পাঠক');
    }

    public function test_admin_can_access_comments_moderation_page(): void
    {
        GuideComment::create([
            'guide_id'     => $this->guide->id,
            'author_name'  => 'রহিম সাহেব',
            'author_phone' => '01711223344',
            'comment'      => 'খতিয়ান ও সিএস পর্চার মধ্যে পার্থক্য কী?',
            'is_approved'  => false,
        ]);

        // Regular buyer cannot access moderation (gets redirected away)
        $this->actingAs($this->buyer)->get(route('admin.guides.comments.index'))->assertRedirect();
        $this->actingAs($this->buyer)->getJson(route('admin.guides.comments.index'))->assertStatus(403);

        // Admin can access
        $response = $this->actingAs($this->admin)->get(route('admin.guides.comments.index'));
        $response->assertStatus(200);
        $response->assertSee('গাইড প্রশ্নোত্তর ও কমেন্ট মডারেশন');
        $response->assertSee('রহিম সাহেব');
        $response->assertSee('01711223344');
        $response->assertSee('খতিয়ান ও সিএস পর্চার মধ্যে পার্থক্য কী?');
    }

    public function test_admin_can_approve_reject_and_delete_comment(): void
    {
        $comment = GuideComment::create([
            'guide_id'     => $this->guide->id,
            'author_name'  => 'তানভীর',
            'comment'      => 'টেস্ট কমেন্ট',
            'is_approved'  => false,
        ]);

        // 1. Approve
        $this->actingAs($this->admin)
            ->post(route('admin.guides.comments.approve', $comment))
            ->assertRedirect();

        $this->assertTrue($comment->fresh()->is_approved);

        // 2. Reject / Unapprove
        $this->actingAs($this->admin)
            ->post(route('admin.guides.comments.reject', $comment))
            ->assertRedirect();

        $this->assertFalse($comment->fresh()->is_approved);

        // 3. Delete
        $this->actingAs($this->admin)
            ->delete(route('admin.guides.comments.destroy', $comment))
            ->assertRedirect();

        $this->assertModelMissing($comment);
    }

    public function test_admin_can_reply_to_comment_and_it_shows_as_verified_desk(): void
    {
        $comment = GuideComment::create([
            'guide_id'     => $this->guide->id,
            'author_name'  => 'সুমন',
            'comment'      => 'ফ্ল্যাট বুকিং দেয়ার আগে কি কি লিগ্যাল পেপার চেক করতে হবে?',
            'is_approved'  => false,
        ]);

        $replyText = 'রাজউক অনুমোদন পত্র, অনুমোদিত নকশা এবং ডেভেলপার ও জমির মালিকের ৩৯ ধারার আমমোক্তারনামা চেক করা বাধ্যতামূলক।';

        $this->actingAs($this->admin)
            ->post(route('admin.guides.comments.reply', $comment), [
                'reply' => $replyText,
            ])
            ->assertRedirect();

        // Check parent comment was auto-approved
        $this->assertTrue($comment->fresh()->is_approved);

        // Check admin reply created
        $this->assertDatabaseHas('guide_comments', [
            'guide_id'       => $this->guide->id,
            'parent_id'      => $comment->id,
            'author_name'    => 'PropertyKoi Legal & Property Desk',
            'is_approved'    => true,
            'is_admin_reply' => true,
        ]);

        // Public page should now display both question and verified admin reply
        $response = $this->get(route('guides.show', $this->guide->slug));
        $response->assertStatus(200);
        $response->assertSee('সুমন');
        $response->assertSee('PropertyKoi Legal & Property Desk');
        $response->assertSee('ভেরিফাইড অ্যাডমিন উত্তর');
        $response->assertSee($replyText);
    }
}
