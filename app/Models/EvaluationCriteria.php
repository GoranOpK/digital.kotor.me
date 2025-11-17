<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za kriterijume ocjenjivanja prijava.
 * 
 * Predstavlja jedan kriterijum po kojem se evaluiraju prijave na konkurs.
 * Svaki kriterijum ima naziv, opis i maksimalan broj bodova koji se može dodijeliti.
 * Primjeri kriterijuma: inovativnost, izvodljivost, održivost projekta.
 */
class EvaluationCriteria extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * 
     * @var array<string>
     */
    protected $fillable = [
        'competition_id',
        'name',
        'description',
        'max_score'
    ];

    /**
     * Veza mnogo-na-jedan: kriterijum pripada jednom konkursu.
     * 
     * Omogućava dohvatanje konkursa za koji je ovaj kriterijum definisan.
     * Svaki konkurs može imati specifičan set kriterijuma za evaluaciju.
     * Koristi se: $criteria->competition
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Veza jedan-na-više: kriterijum ima više ocjena (score-ova).
     * 
     * Omogućava dohvatanje svih ocjena koje su dodijeljene za ovaj specifični
     * kriterijum kroz različite prijave. Koristi se za analizu i statistiku.
     * Koristi se: $criteria->scores
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class, 'criteria_id');
    }
}