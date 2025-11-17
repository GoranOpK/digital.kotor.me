<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Competition (Konkurs)
 * 
 * Predstavlja konkurs za žensko ili omladinsko preduzetništvo.
 * Definiše naziv, opis, trajanje i tip konkursa.
 * 
 * @property int $id - Jedinstveni identifikator konkursa
 * @property string $title - Naslov konkursa
 * @property string $description - Detaljan opis konkursa i uslova
 * @property \Illuminate\Support\Carbon $start_date - Datum početka konkursa
 * @property \Illuminate\Support\Carbon $end_date - Datum završetka konkursa
 * @property string $type - Tip konkursa ('žensko', 'omladinsko')
 * @property string $status - Status konkursa ('draft', 'active', 'closed')
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class Competition extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni.
     * Omogućava jednostavno kreiranje konkursa kroz forme.
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
     * Veza: jedan konkurs ima više prijava (One-to-Many)
     * 
     * Omogućava pristup svim prijavama podnesenim na ovaj konkurs.
     * 
     * Primjer: $competition->applications - sve prijave na konkurs
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Veza: konkurs ima više kriterijuma za evaluaciju (One-to-Many)
     * 
     * Svaki konkurs može imati različite kriterijume po kojima se
     * ocjenjuju prijave (npr. inovativnost, izvodljivost, itd.)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluationCriteria()
    {
        return $this->hasMany(EvaluationCriteria::class);
    }

    /**
     * Veza: konkurs ima više prioriteta (One-to-Many)
     * 
     * Definiše prioritetne oblasti ili kategorije za ovaj konkurs.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priorities()
    {
        return $this->hasMany(Priority::class);
    }
}