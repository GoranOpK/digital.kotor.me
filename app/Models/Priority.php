<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Priority (Prioritet konkursa)
 * 
 * Definiše prioritetne oblasti ili kategorije za konkurs.
 * Na primjer: IT industrija, turizam, poljoprivreda, itd.
 * 
 * @property int $id - Jedinstveni identifikator prioriteta
 * @property int $competition_id - ID konkursa kojem prioritet pripada
 * @property string $name - Naziv prioritetne oblasti
 * @property string $description - Opis prioriteta i specifičnosti
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class Priority extends Model
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
        'description'
    ];

    /**
     * Veza: prioritet pripada jednom konkursu (Many-to-One)
     * 
     * Povezuje prioritet sa konkursom kojem pripada.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}