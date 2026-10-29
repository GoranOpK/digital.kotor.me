<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only MMSP test-access event. Current permission is the latest row per user.
 */
class MmspTestAccessGrant extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_GRANTED = 'granted';

    public const ACTION_REVOKED = 'revoked';

    public const REASON_MANUAL = 'manual';

    public const REASON_ROLE_CHANGED = 'role_changed';

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'action',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('MMSP test-access grants are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('MMSP test-access grants are append-only.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
