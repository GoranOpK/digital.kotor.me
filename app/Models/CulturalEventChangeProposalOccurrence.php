<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TS-010.3b — predložena operacija nad Održavanjem (add/update podataka).
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
}
