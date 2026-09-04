<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only KN official-decision lifecycle audit.
 * Not EP catalog audit. Not CulturalActivityRecord. Not the generic logs table.
 */
class CompetitionOfficialDecisionLifecycleEvent extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_METADATA_CORRECTED = 'official_decision_metadata_corrected';

    public const ACTION_UNPUBLISHED = 'official_decision_unpublished';

    public const ACTION_REPUBLISHED = 'official_decision_republished';

    public const ACTION_PERMANENT_DELETE_STARTED = 'official_decision_permanent_delete_started';

    public const ACTION_PERMANENT_DELETE_COMPLETED = 'official_decision_permanent_delete_completed';

    protected $table = 'competition_official_decision_lifecycle_events';

    protected $fillable = [
        'competition_official_decision_copy_id',
        'competition_id',
        'action',
        'actor_user_id',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
            'competition_official_decision_copy_id' => 'integer',
            'competition_id' => 'integer',
            'actor_user_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('KN official decision lifecycle events are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('KN official decision lifecycle events are append-only.');
        });
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(
            CompetitionOfficialDecisionCopy::class,
            'competition_official_decision_copy_id'
        );
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
