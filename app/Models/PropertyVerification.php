<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyVerification extends Model
{
    protected $fillable = [
        'property_id',
        'owner_identity_checked',
        'location_checked',
        'basic_info_checked',
        'basic_documents_checked',
        'documents_available',
        'disclaimer_acknowledged',
        'verified_by_user_id',
        'verified_at',
        'audit_notes',
    ];

    protected $casts = [
        'owner_identity_checked' => 'boolean',
        'location_checked' => 'boolean',
        'basic_info_checked' => 'boolean',
        'basic_documents_checked' => 'boolean',
        'documents_available' => 'array',
        'disclaimer_acknowledged' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getChecklistOwnerIdentityAttribute(): bool
    {
        return (bool) $this->owner_identity_checked;
    }

    public function setChecklistOwnerIdentityAttribute($value): void
    {
        $this->attributes['owner_identity_checked'] = (bool) $value;
    }

    public function getChecklistLocationAttribute(): bool
    {
        return (bool) $this->location_checked;
    }

    public function setChecklistLocationAttribute($value): void
    {
        $this->attributes['location_checked'] = (bool) $value;
    }

    public function getChecklistBasicInfoAttribute(): bool
    {
        return (bool) $this->basic_info_checked;
    }

    public function setChecklistBasicInfoAttribute($value): void
    {
        $this->attributes['basic_info_checked'] = (bool) $value;
    }

    public function getChecklistDocumentsAttribute(): bool
    {
        return (bool) $this->basic_documents_checked;
    }

    public function setChecklistDocumentsAttribute($value): void
    {
        $this->attributes['basic_documents_checked'] = (bool) $value;
    }

    public function getVerificationNotesAttribute(): ?string
    {
        return $this->audit_notes;
    }

    public function setVerificationNotesAttribute($value): void
    {
        $this->attributes['audit_notes'] = $value;
    }
}
