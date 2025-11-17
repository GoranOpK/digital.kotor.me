<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za dokumente priložene uz prijavu.
 * 
 * Predstavlja pojedinačni dokument (PDF, slika, Word fajl) koji je korisnik
 * priložio kao dio prijave na konkurs. Može biti različitih tipova
 * (lična karta, biznis plan, finansijski izvještaji, itd.).
 */
class ApplicationDocument extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * 
     * @var array<string>
     */
    protected $fillable = [
        'application_id',
        'name',
        'file_path',
        'type'
    ];

    /**
     * Veza mnogo-na-jedan: dokument pripada jednoj prijavi.
     * 
     * Omogućava dohvatanje prijave kojoj ovaj dokument pripada.
     * Svaka prijava može imati više dokumenata, ali svaki dokument
     * pripada tačno jednoj prijavi.
     * Koristi se: $document->application
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}