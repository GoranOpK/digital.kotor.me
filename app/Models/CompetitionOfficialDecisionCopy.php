<?php

namespace App\Models;

use App\Events\OfficialContentPublicAvailabilityRevoked;
use Illuminate\Database\Eloquent\Model;

class CompetitionOfficialDecisionCopy extends Model
{
    protected $fillable = [
        'competition_id',
        'storage_path',
        'uploaded_by',
        'business_title',
        'business_published_on',
        'permanent_delete_pending_at',
        'permanently_deleted_at',
        'permanently_deleted_by',
    ];

    protected $casts = [
        'competition_id' => 'integer',
        'uploaded_by' => 'integer',
        'business_published_on' => 'date',
        'permanent_delete_pending_at' => 'datetime',
        'permanently_deleted_at' => 'datetime',
        'permanently_deleted_by' => 'integer',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function permanentlyDeletedBy()
    {
        return $this->belongsTo(User::class, 'permanently_deleted_by');
    }

    public function lifecycleEvents()
    {
        return $this->hasMany(CompetitionOfficialDecisionLifecycleEvent::class);
    }

    public function hasBeenPublished(): bool
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $this->competition_id)
            ->where('source_object_id', $this->id)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->exists();
    }

    public function isCurrentlyPublished(): bool
    {
        return $this->currentPublicSignedCopyNoticesQuery()->exists();
    }

    public function currentPublicSignedCopyNotices()
    {
        return $this->currentPublicSignedCopyNoticesQuery()->get();
    }

    public function currentPublicSignedCopyNoticesQuery()
    {
        return static::activeSignedCopyNoticesQuery($this->competition_id)
            ->where('source_object_id', $this->id);
    }

    public static function competitionHasPublishedSignedCopy(int $competitionId): bool
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $competitionId)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->exists();
    }

    public static function activeSignedCopyNotices(int $competitionId)
    {
        return static::activeSignedCopyNoticesQuery($competitionId)->get();
    }

    public static function activeSignedCopyNoticesQuery(int $competitionId)
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $competitionId)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->where('publicly_available', true);
    }

    public function previousRevokedSignedCopyNoticesQuery()
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $this->competition_id)
            ->where('source_object_id', $this->id)
            ->where('content_delivery', 'competition_decision_signed_copy')
            ->where('publicly_available', false)
            ->orderByDesc('id');
    }

    public static function leftoverDecisionHtmlNoticesQuery(int $competitionId)
    {
        return Notice::query()
            ->where('source_type', 'competition_decision')
            ->where('source_id', $competitionId)
            ->where('content_delivery', 'competition_decision_html')
            ->where(function ($query) {
                $query->where('visible_in_active_panel', true)
                    ->orWhere('publicly_available', true);
            });
    }

    public static function leftoverDecisionHtmlNotices(int $competitionId)
    {
        return static::leftoverDecisionHtmlNoticesQuery($competitionId)->get();
    }

    public static function revokeLeftoverDecisionHtmlPublications(int $competitionId): void
    {
        foreach (static::leftoverDecisionHtmlNotices($competitionId) as $notice) {
            event(new OfficialContentPublicAvailabilityRevoked($notice->id));
        }
    }
}
