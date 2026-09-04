<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only EP catalog-admin audit. Not a payment lifecycle event.
 */
class PaymentCatalogAudit extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentCatalogAuditFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const ENTITY_PAYMENT_TYPE = 'payment_type';

    public const ENTITY_PAYMENT_ACCOUNT = 'payment_account';

    public const ENTITY_TYPE_AVAILABILITY = 'payment_type_availability';

    public const ENTITY_ACCOUNT_AVAILABILITY = 'payment_account_availability';

    public const ENTITY_EP_SETTING = 'ep_setting';

    protected $table = 'ep_catalog_audits';

    protected $fillable = [
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('EP catalog audits are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('EP catalog audits are append-only.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
