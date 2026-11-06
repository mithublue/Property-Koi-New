<?php

namespace App\Services;

use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuideCommentService
{
    /**
     * Store a new user inquiry/comment and notify admin via Telegram and Mobile Push.
     */
    public static function storeComment(Guide $guide, array $data, ?User $user = null): GuideComment
    {
        $isAdmin = $user && ($user->isAdmin() || $user->role === 'admin' || $user->role === 'administrator');

        $comment = GuideComment::create([
            'guide_id'       => $guide->id,
            'user_id'        => $user?->id,
            'parent_id'      => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'author_name'    => trim((string) ($data['author_name'] ?? ($user?->name ?: 'ভিজিটর'))),
            'author_email'   => !empty($data['author_email']) ? trim((string) $data['author_email']) : ($user?->email ?: null),
            'author_phone'   => !empty($data['author_phone']) ? trim((string) $data['author_phone']) : ($user?->phone ?: null),
            'comment'        => trim((string) $data['comment']),
            'is_approved'    => $isAdmin, // Auto-approve if created by admin
            'is_admin_reply' => $isAdmin && !empty($data['parent_id']),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);

        // If the comment was posted by a regular visitor, notify ADMIN ONLY
        if (!$isAdmin) {
            self::notifyAdmin($guide, $comment);
        }

        return $comment;
    }

    /**
     * Dispatch alert strictly to the site admin via Telegram and Mobile FCM Push.
     */
    public static function notifyAdmin(Guide $guide, GuideComment $comment): void
    {
        $guideUrl = route('guides.show', $guide->slug);
        $moderationUrl = url('/backend/guides/comments');

        $authorName = e($comment->author_name);
        $contact = e($comment->author_phone ?: ($comment->author_email ?: 'প্রদান করেনি'));
        $commentExcerpt = Str::limit(strip_tags($comment->comment), 280);
        $guideTitle = e($guide->title);

        // 1. Telegram Notification (Strictly Admin Chat ID)
        try {
            $adminChatId = TelegramService::getAdminChatId();
            if (!empty($adminChatId)) {
                $text = "💬 <b>নতুন গাইড মন্তব্য / প্রশ্নোত্তর!</b>\n\n"
                      . "📖 <b>গাইড:</b> {$guideTitle}\n"
                      . "👤 <b>প্রেরক:</b> {$authorName}\n"
                      . "📞 <b>যোগাযোগ:</b> {$contact}\n\n"
                      . "📝 <b>প্রশ্ন / মন্তব্য:</b>\n"
                      . "<i>\"{$commentExcerpt}\"</i>\n\n"
                      . "⚡ অনুগ্রহ করে নিচে ক্লিক করে কমেন্টটি অনুমোদন বা উত্তর দিন:";

                $buttons = [
                    [
                        ['text' => '📖 গাইড দেখুন', 'url' => $guideUrl],
                        ['text' => '⚡ কমেন্ট মডারেশন', 'url' => $moderationUrl],
                    ],
                ];

                TelegramService::sendMessage($adminChatId, $text, ['inline_keyboard' => $buttons]);
            }
        } catch (\Throwable $te) {
            Log::warning('[GuideCommentService] Telegram notify admin error: ' . $te->getMessage());
        }

        // 2. Mobile Push Notification via FCM (Strictly Admin Device Tokens)
        try {
            $title = "💬 নতুন গাইড মন্তব্য: " . Str::limit($guide->title, 40);
            $body = "{$authorName}: {$commentExcerpt}";

            FcmService::sendToAdmins($title, $body, $moderationUrl);
        } catch (\Throwable $fe) {
            Log::warning('[GuideCommentService] FCM notify admin error: ' . $fe->getMessage());
        }
    }

    /**
     * Admin reply to an existing comment.
     */
    public static function replyAsAdmin(GuideComment $parentComment, string $replyText, User $adminUser): GuideComment
    {
        return GuideComment::create([
            'guide_id'       => $parentComment->guide_id,
            'user_id'        => $adminUser->id,
            'parent_id'      => $parentComment->id,
            'author_name'    => 'PropertyKoi Legal & Property Desk',
            'author_email'   => $adminUser->email,
            'author_phone'   => $adminUser->phone,
            'comment'        => trim($replyText),
            'is_approved'    => true,
            'is_admin_reply' => true,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
