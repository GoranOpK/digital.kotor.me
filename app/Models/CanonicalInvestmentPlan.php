<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * KN-SYNC-008A — canonical Obrazac P2 (čl.17): I.2 vrsta subvencije and
 * III. finansijski okvir ulaganja (stavke troškova, ukupna vrijednost ulaganja,
 * sopstveno učešće, očekivani iznos subvencije). One per canonical application.
 *
 * Frozen at canonical FINAL; a frozen row cannot be updated or deleted.
 */
class CanonicalInvestmentPlan extends Model
{
    public const FROZEN_MESSAGE = 'Plan ulaganja (Obrazac P2) je zamrznut konačnom predajom i ne može se mijenjati.';

    protected $fillable = [
        'application_id',
        'subsidy_type',
        'total_investment',
        'own_contribution',
        'expected_subsidy',
        'expense_lines',
        'narrative_sections',
    ];

    protected $casts = [
        'total_investment' => 'decimal:2',
        'own_contribution' => 'decimal:2',
        'expected_subsidy' => 'decimal:2',
        'expense_lines' => 'array',
        'narrative_sections' => 'array',
        'frozen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $plan) {
            if ($plan->getOriginal('frozen_at') !== null) {
                throw new LogicException(self::FROZEN_MESSAGE);
            }
        });

        static::deleting(function (self $plan) {
            if ($plan->getOriginal('frozen_at') !== null) {
                throw new LogicException(self::FROZEN_MESSAGE);
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

    /**
     * @return list<array{cost_type: string, description: string, amount: string}>
     */
    public function expenseLines(): array
    {
        $lines = is_array($this->expense_lines) ? array_values($this->expense_lines) : [];

        // JSON columns do not preserve key order; keep the basis fingerprint deterministic.
        return array_map(fn ($line) => is_array($line) ? [
            'cost_type' => (string) ($line['cost_type'] ?? ''),
            'description' => (string) ($line['description'] ?? ''),
            'amount' => (string) ($line['amount'] ?? ''),
        ] : $line, $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function basis(): array
    {
        return [
            'subsidy_type' => $this->subsidy_type,
            'total_investment' => $this->total_investment,
            'own_contribution' => $this->own_contribution,
            'expected_subsidy' => $this->expected_subsidy,
            'expense_lines' => $this->expenseLines(),
            'narrative_sections' => $this->narrative_sections ?? [],
            'frozen_at' => $this->frozen_at?->toIso8601String(),
        ];
    }
}
