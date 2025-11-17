<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za izvještaje o realizaciji projekta.
 * 
 * Predstavlja periodični izvještaj koji korisnik podnosi o napretku realizacije
 * projekta nakon što mu je odobren ugovor. Sadrži opis aktivnosti, dokumente
 * kao dokaz i može biti ocijenjen od strane administratora.
 */
class Report extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * 
     * @var array<string>
     */
    protected $fillable = [
        'application_id',
        'description',
        'document_file',
        'status'
    ];

    /**
     * Veza mnogo-na-jedan: izvještaj pripada jednoj prijavi.
     * 
     * Omogućava dohvatanje prijave za koju se podnosi ovaj izvještaj realizacije.
     * Korisnik može podnositi više izvještaja kroz tok realizacije projekta
     * (npr. kvartalni ili finalni izvještaj).
     * Koristi se: $report->application
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}