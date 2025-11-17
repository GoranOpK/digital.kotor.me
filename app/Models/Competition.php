<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za upravljanje konkursima.
 * 
 * Predstavlja konkurs za finansijsku podršku projekata. Konkurs može biti različitih tipova
 * (npr. za startupe, sport, kultura) i ima definisan period trajanja, kriterijume ocjenjivanja
 * i prioritete.
 */
class Competition extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * 
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'status'
    ];

    /**
     * Veza jedan-na-više: konkurs ima više prijava.
     * 
     * Omogućava dohvatanje svih prijava koje su podnesene za ovaj konkurs.
     * Koristi se: $competition->applications
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Veza jedan-na-više: konkurs ima više kriterijuma za ocjenjivanje.
     * 
     * Omogućava dohvatanje svih kriterijuma koji se koriste za evaluaciju
     * prijava na ovaj konkurs (npr. inovativnost, izvodljivost, itd.).
     * Koristi se: $competition->evaluationCriteria
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluationCriteria()
    {
        return $this->hasMany(EvaluationCriteria::class);
    }

    /**
     * Veza jedan-na-više: konkurs ima više prioriteta.
     * 
     * Omogućava dohvatanje svih prioritetnih kategorija definisanih za ovaj konkurs
     * (npr. mladi, žene, određene regije).
     * Koristi se: $competition->priorities
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priorities()
    {
        return $this->hasMany(Priority::class);
    }
}