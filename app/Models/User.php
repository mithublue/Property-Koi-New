<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'whatsapp_number',
        'role',
        'is_verified',
        'avatar_url',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'magic_token',
        'last_magic_login_at',
        'google_id',
        'email_verification_token',
        'email_verification_code',
        'email_verification_sent_at',
        'preferred_type',
        'preferred_blocks',
        'budget_range',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'magic_token',
        'email_verification_token',
        'email_verification_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'last_magic_login_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function getAvatarUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (\Illuminate\Support\Str::startsWith($value, ['http://', 'https://'])) {
            if (preg_match('#/(?:public/)?storage/(.+)$#', $value, $matches)) {
                return asset('storage/' . $matches[1]);
            }
            return $value;
        }
        return asset($value);
    }

    public function agentProfile(): HasOne
    {
        return $this->hasOne(AgentProfile::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'created_by_user_id');
    }

    public function buyerDemands(): HasMany
    {
        return $this->hasMany(BuyerDemand::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'favorites')->withTimestamps();
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(BuyerInteraction::class);
    }

    public function visitBookings(): HasMany
    {
        return $this->hasMany(VisitBooking::class);
    }
}
