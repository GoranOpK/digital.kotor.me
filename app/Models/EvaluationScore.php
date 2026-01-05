<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'commission_member_id',
        'documents_complete',
        'criterion_1',
        'criterion_2',
        'criterion_3',
        'criterion_4',
        'criterion_5',
        'criterion_6',
        'criterion_7',
        'criterion_8',
        'criterion_9',
        'criterion_10',
        'average_score',
        'final_score',
        'notes',
        'justification',
    ];

    protected $casts = [
        'documents_complete' => 'boolean',
        'criterion_1' => 'integer',
        'criterion_2' => 'integer',
        'criterion_3' => 'integer',
        'criterion_4' => 'integer',
        'criterion_5' => 'integer',
        'criterion_6' => 'integer',
        'criterion_7' => 'integer',
        'criterion_8' => 'integer',
        'criterion_9' => 'integer',
        'criterion_10' => 'integer',
        'average_score' => 'decimal:2',
        'final_score' => 'decimal:2',
    ];

    /**
     * Veza sa prijavom
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Veza sa članom komisije
     */
    public function commissionMember(): BelongsTo
    {
        return $this->belongsTo(CommissionMember::class);
    }

    /**
     * Izračunava zbir svih kriterijuma
     */
    public function calculateTotalScore(): int
    {
        return ($this->criterion_1 ?? 0) +
               ($this->criterion_2 ?? 0) +
               ($this->criterion_3 ?? 0) +
               ($this->criterion_4 ?? 0) +
               ($this->criterion_5 ?? 0) +
               ($this->criterion_6 ?? 0) +
               ($this->criterion_7 ?? 0) +
               ($this->criterion_8 ?? 0) +
               ($this->criterion_9 ?? 0) +
               ($this->criterion_10 ?? 0);
    }
}

