<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za upravljanje prijavama na konkurs.
 * 
 * Predstavlja prijavu korisnika na konkurs. Svaka prijava sadrži poslovni plan,
 * pripojene dokumente, ocjene evaluatora i može rezultirati ugovorom ukoliko
 * je prihvaćena. Omogućava praćenje statusa prijave kroz cijeli proces.
 */
class Application extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * 
     * @var array<string>
     */
    protected $fillable = [
        'competition_id',
        'user_id',
        'type',
        'status',
        'business_plan'
    ];

    /**
     * Veza mnogo-na-jedan: prijava pripada jednom konkursu.
     * 
     * Omogućava dohvatanje konkursa na koji je ova prijava podnesena.
     * Koristi se: $application->competition
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Veza jedan-na-više: prijava ima više dokumenata.
     * 
     * Omogućava dohvatanje svih dokumenata (PDF, slike, itd.) koji su
     * priloženi kao dio ove prijave.
     * Koristi se: $application->documents
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /**
     * Veza jedan-na-više: prijava ima više ocjena (score).
     * 
     * Omogućava dohvatanje svih ocjena koje su evaluatori dodijelili ovoj prijavi
     * prema različitim kriterijumima.
     * Koristi se: $application->scores
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class);
    }

    /**
     * Veza jedan-na-više: prijava ima više izvještaja realizacije.
     * 
     * Omogućava dohvatanje svih izvještaja o napretku realizacije projekta
     * koje je korisnik podnosio nakon što mu je odobreno finansiranje.
     * Koristi se: $application->reports
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Veza jedan-na-jedan: prijava ima jedan ugovor.
     * 
     * Omogućava dohvatanje ugovora koji je generisan za ovu prijavu
     * nakon što je odobrena i prihvaćena.
     * Koristi se: $application->contract
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contract()
    {
        return $this->hasOne(Contract::class);
    }
}