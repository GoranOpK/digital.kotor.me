<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model ApplicationScore (Ocjena prijave)
 * 
 * Predstavlja ocjenu koju evaluator daje prijavi po određenom kriterijumu.
 * Svaka prijava može dobiti više ocjena od više evaluatora.
 * 
 * @property int $id - Jedinstveni identifikator ocjene
 * @property int $application_id - ID prijave koja se ocjenjuje
 * @property int $evaluator_id - ID korisnika (evaluatora) koji daje ocjenu
 * @property int $criteria_id - ID kriterijuma po kojem se ocjenjuje
 * @property float $score - Broj bodova (ocjena)
 * @property string $comment - Komentar uz ocjenu (opcionalno)
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme davanja ocjene
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class ApplicationScore extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni.
     * 
     * @var array<string>
     */
    protected $fillable = [
        'application_id',
        'evaluator_id',
        'criteria_id',
        'score',
        'comment'
    ];

    /**
     * Veza: ocjena pripada jednoj prijavi (Many-to-One)
     * 
     * Omogućava pristup prijavi koja se ocjenjuje.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Veza: ocjenu daje jedan evaluator (Many-to-One)
     * 
     * Povezuje ocjenu sa korisnikom koji ima ulogu evaluatora.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Veza: ocjena se odnosi na jedan kriterijum (Many-to-One)
     * 
     * Povezuje ocjenu sa kriterijumom evaluacije
     * (npr. inovativnost, izvodljivost, održivost).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function criteria()
    {
        return $this->belongsTo(EvaluationCriteria::class, 'criteria_id');
    }
}