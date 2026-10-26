<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SheetImportPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sample_sheet_url',
        'field_mappings',
        'options',
        'created_by_user_id',
    ];

    protected $casts = [
        'field_mappings' => 'array',
        'options'        => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
