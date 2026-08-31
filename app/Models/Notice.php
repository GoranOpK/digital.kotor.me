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
        'publicly_available',
        'source_type',
        'source_id',
        'source_object_id',
        'content_delivery',
        'published_at',
        'superseded_notice_id',
    ];

    protected $casts = [
        'visible_in_active_panel' => 'boolean',
        'publicly_available' => 'boolean',
        'published_at' => 'datetime',
        'source_id' => 'integer',
        'source_object_id' => 'integer',
        'superseded_notice_id' => 'integer',
    ];

    public function supersededNotice()
    {
        return $this->belongsTo(self::class, 'superseded_notice_id');
    }

    public function sourceObject()
    {
        return $this->belongsTo(CompetitionOfficialDecisionCopy::class, 'source_object_id');
    }
}
