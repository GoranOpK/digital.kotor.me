<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za prioritete u konkursu.
 * 
 * Predstavlja prioritetnu kategoriju ili grupu koja može dobiti dodatne bodove
 * ili prednost u evaluaciji. Primjeri: mladi preduzetnici, žene, određene
 * geografske regije, socijalno ugrožene grupe.
 */
class Priority extends Model
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
        'description'
    ];

    /**
     * Veza mnogo-na-jedan: prioritet pripada jednom konkursu.
     * 
     * Omogućava dohvatanje konkursa za koji je ovaj prioritet definisan.
     * Različiti konkursi mogu imati različite prioritetne kategorije.
     * Koristi se: $priority->competition
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}