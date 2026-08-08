<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TS-010.3b — predložena operacija nad Održavanjem (add/update podataka).
 * baseline_* = stanje termina kanonskog Održavanja pri prvom kreiranju update op-a.
 */
class CulturalEventChangeProposalOccurrence extends Model
{
    public const OPERATION_ADD = 'add';

    public const OPERATION_UPDATE = 'update';

    public const OPERATIONS = [
        self::OPERATION_ADD,
        self::OPERATION_UPDATE,
    ];

    protected $fillable = [
        'proposal_id',
        'operation',
        'source_occurrence_id',
        'baseline_datum',
        'baseline_vrijeme_od',
        'baseline_vrijeme_do',
        'baseline_cjelodnevno',
        'proposed_datum',
        'proposed_vrijeme_od',
        'proposed_vrijeme_do',
        'proposed_cjelodnevno',
        'proposed_location_id',
        'proposed_location_manual_name',
    ];

    protected function casts(): array
    {
        return [
            'proposed_datum' => 'date',
            'proposed_cjelodnevno' => 'boolean',
            'baseline_datum' => 'date',
            'baseline_cjelodnevno' => 'boolean',
            'proposal_id' => 'integer',
            'source_occurrence_id' => 'integer',
            'proposed_location_id' => 'integer',
        ];
    }

    public function isAdd(): bool
    {
        return $this->operation === self::OPERATION_ADD;
    }

    public function isUpdate(): bool
    {
        return $this->operation === self::OPERATION_UPDATE;
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(CulturalEventChangeProposal::class, 'proposal_id');
    }

    public function sourceOccurrence(): BelongsTo
    {
        return $this->belongsTo(CulturalOccurrence::class, 'source_occurrence_id');
    }

    public function proposedLocation(): BelongsTo
    {
        return $this->belongsTo(CulturalLocation::class, 'proposed_location_id');
    }

    public function hasTerminBaseline(): bool
    {
        return $this->baseline_datum !== null;
    }

    /**
     * True kada se kanonski termin razlikuje od baseline-a na kojem je update op zasnovan.
     */
    public function terminConflictsWithCanonical(CulturalOccurrence $occurrence): bool
    {
        if (! $this->isUpdate() || ! $this->hasTerminBaseline()) {
            return false;
        }

        return ! self::terminFieldsEqual(
            $this->baseline_datum?->toDateString(),
            $this->baseline_vrijeme_od,
            $this->baseline_vrijeme_do,
            (bool) $this->baseline_cjelodnevno,
            $occurrence->datum?->toDateString(),
            $occurrence->vrijeme_od,
            $occurrence->vrijeme_do,
            (bool) $occurrence->cjelodnevno,
        );
    }

    /**
     * @return array{
     *     datum: string|\DateTimeInterface,
     *     vrijeme_od: ?string,
     *     vrijeme_do: ?string,
     *     cjelodnevno: bool,
     *     location_id: ?int,
     *     location_manual_name: ?string
     * }
     */
    public function toOccurrencePayload(): array
    {
        return [
            'datum' => $this->proposed_datum,
            'vrijeme_od' => $this->proposed_vrijeme_od,
            'vrijeme_do' => $this->proposed_vrijeme_do,
            'cjelodnevno' => (bool) $this->proposed_cjelodnevno,
            'location_id' => $this->proposed_location_id,
            'location_manual_name' => $this->proposed_location_manual_name,
        ];
    }

    /**
     * Snapshot termina sa kanonskog Održavanja (samo pri kreiranju update op-a).
     *
     * @return array{
     *     baseline_datum: string,
     *     baseline_vrijeme_od: ?string,
     *     baseline_vrijeme_do: ?string,
     *     baseline_cjelodnevno: bool
     * }
     */
    public static function baselineFromOccurrence(CulturalOccurrence $occurrence): array
    {
        return [
            'baseline_datum' => $occurrence->datum?->toDateString() ?? (string) $occurrence->datum,
            'baseline_vrijeme_od' => self::normalizeTimeForCompare($occurrence->vrijeme_od),
            'baseline_vrijeme_do' => self::normalizeTimeForCompare($occurrence->vrijeme_do),
            'baseline_cjelodnevno' => (bool) $occurrence->cjelodnevno,
        ];
    }

    public static function terminFieldsEqual(
        ?string $aDatum,
        mixed $aOd,
        mixed $aDo,
        bool $aCjelodnevno,
        ?string $bDatum,
        mixed $bOd,
        mixed $bDo,
        bool $bCjelodnevno,
    ): bool {
        return $aDatum === $bDatum
            && self::normalizeTimeForCompare($aOd) === self::normalizeTimeForCompare($bOd)
            && self::normalizeTimeForCompare($aDo) === self::normalizeTimeForCompare($bDo)
            && $aCjelodnevno === $bCjelodnevno;
    }

    public static function normalizeTimeForCompare(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
            return $raw;
        }

        return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
    }
}
