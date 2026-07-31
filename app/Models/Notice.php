<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_description',
        'visible_in_active_panel',
        'source_type',
        'source_id',
        'content_delivery',
        'published_at',
    ];

    protected $casts = [
        'visible_in_active_panel' => 'boolean',
        'published_at' => 'datetime',
        'source_id' => 'integer',
    ];
}
