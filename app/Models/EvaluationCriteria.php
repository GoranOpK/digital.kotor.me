<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model EvaluationCriteria (Kriterijum evaluacije)
 * 
 * Predstavlja kriterijum po kojem se ocjenjuju prijave na konkurs.
 * Svaki konkurs može imati više kriterijuma sa maksimalnim brojem bodova.
 * 
 * @property int $id - Jedinstveni identifikator kriterijuma
 * @property int $competition_id - ID konkursa kojem kriterijum pripada
 * @property string $name - Naziv kriterijuma (npr. 'Inovativnost', 'Izvodljivost')
 * @property string $description - Detaljan opis šta se ocjenjuje
 * @property float $max_score - Maksimalan broj bodova za ovaj kriterijum
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class EvaluationCriteria extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni.
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
     * Veza: kriterijum pripada jednom konkursu (Many-to-One)
     * 
     * Omogućava pristup konkursu kojem ovaj kriterijum pripada.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Veza: kriterijum ima više ocjena (One-to-Many)
     * 
     * Povezuje sve ocjene date po ovom kriterijumu.
     * Korisno za izvještavanje i analize.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class, 'criteria_id');
    }
}