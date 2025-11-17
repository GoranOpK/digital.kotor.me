<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za ocjene prijava od strane evaluatora.
 * 
 * Predstavlja pojedinačnu ocjenu koju je evaluator dodijelio prijavi
 * za određeni kriterijum ocjenjivanja. Sadrži numerički score, opcioni
 * komentar i reference na prijavu, evaluatora i kriterijum.
 */
class ApplicationScore extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
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
     * Veza mnogo-na-jedan: ocjena pripada jednoj prijavi.
     * 
     * Omogućava dohvatanje prijave koja je ocjenjena.
     * Koristi se: $score->application
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Veza mnogo-na-jedan: ocjenu je dao određeni evaluator (korisnik).
     * 
     * Omogućava dohvatanje korisnika koji je dao ovu ocjenu.
     * Evaluator je User sa ulogom 'evaluator'.
     * Koristi se: $score->evaluator
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Veza mnogo-na-jedan: ocjena je data za određeni kriterijum.
     * 
     * Omogućava dohvatanje kriterijuma po kojem je prijava ocjenjena
     * (npr. inovativnost, izvodljivost).
     * Koristi se: $score->criteria
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function criteria()
    {
        return $this->belongsTo(EvaluationCriteria::class, 'criteria_id');
    }
}