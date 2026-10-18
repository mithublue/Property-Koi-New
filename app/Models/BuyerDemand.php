<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BuyerDemand extends Model
{
    protected $fillable = [
        'requirement_code',
        'user_id',
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'property_type',
        'preferred_areas',
        'preferred_blocks',
        'min_road_width_ft',
        'preferred_facings',
        'min_size',
        'max_size',
        'min_budget',
        'max_budget',
        'purpose',
        'purchase_timeline',
        'status',
        'notify_channels',
        'telegram_chat_id',
        'telegram_username',
        'unsubscribe_token',
        'last_notified_at',
        'match_count',
        'notes',
        'criteria_details',
    ];

    protected $casts = [
        'preferred_areas' => 'array',
        'preferred_blocks' => 'array',
        'preferred_facings' => 'array',
        'notify_channels' => 'array',
        'criteria_details' => 'array',
        'min_size' => 'decimal:2',
        'max_size' => 'decimal:2',
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'last_notified_at' => 'datetime',
        'match_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($demand) {
            if (empty($demand->requirement_code)) {
                $demand->requirement_code = 'REQ-' . strtoupper(Str::random(6));
            }
            if (empty($demand->unsubscribe_token)) {
                $demand->unsubscribe_token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DemandNotification::class);
    }

    public function hasChannel(string $channel): bool
    {
        $channels = explode(',', (string) $this->notify_channels);
        return in_array(trim($channel), array_map('trim', $channels), true);
    }

    /**
     * Query existing demands belonging to a submitter based on user_id (if logged in)
     * OR phone / WhatsApp, email, or Telegram identifiers (if guest or cross-referenced).
     */
    public static function queryForSubmitter(?int $userId, ?string $phone, ?string $email, ?string $teleChatId = null, ?string $teleUsername = null)
    {
        $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;
        $last10 = ($cleanPhone && strlen($cleanPhone) >= 10) ? substr($cleanPhone, -10) : $cleanPhone;
        $cleanEmail = $email ? strtolower(trim($email)) : null;
        $cleanTeleUser = $teleUsername ? ltrim(trim($teleUsername), '@') : null;
        $cleanTeleChat = $teleChatId ? trim($teleChatId) : null;

        return self::where(function ($q) use ($userId, $phone, $cleanPhone, $last10, $cleanEmail, $cleanTeleChat, $cleanTeleUser) {
            $hasCondition = false;

            if (!empty($userId)) {
                $q->orWhere('user_id', $userId);
                $hasCondition = true;
            }

            if (!empty($phone) && strlen(trim($phone)) >= 6) {
                $rawPhone = trim($phone);
                $q->orWhere(function ($sq) use ($rawPhone, $cleanPhone, $last10) {
                    $sq->where('buyer_phone', $rawPhone);
                    if ($cleanPhone) {
                        $sq->orWhere('buyer_phone', $cleanPhone);
                    }
                    if ($last10 && strlen($last10) >= 8) {
                        $sq->orWhere('buyer_phone', 'LIKE', '%' . $last10);
                    }
                });
                $hasCondition = true;
            }

            if (!empty($cleanEmail) && filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                $q->orWhere('buyer_email', $cleanEmail);
                $hasCondition = true;
            }

            if (!empty($cleanTeleChat)) {
                $q->orWhere('telegram_chat_id', $cleanTeleChat);
                $hasCondition = true;
            }

            if (!empty($cleanTeleUser) && strlen($cleanTeleUser) >= 3) {
                $q->orWhere('telegram_username', $cleanTeleUser);
                $hasCondition = true;
            }

            if (!$hasCondition) {
                $q->whereRaw('0 = 1');
            }
        });
    }

    /**
     * Count active/existing demands for this submitter.
     */
    public static function countDemandsForSubmitter(?int $userId, ?string $phone, ?string $email, ?string $teleChatId = null, ?string $teleUsername = null): int
    {
        return self::queryForSubmitter($userId, $phone, $email, $teleChatId, $teleUsername)->count();
    }
}
