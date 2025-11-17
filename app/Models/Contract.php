<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za upravljanje ugovorima.
 * 
 * Predstavlja ugovor između opštine i korisnika čija je prijava odobrena.
 * Ugovor definiše obaveze obje strane, sadrži digitalni fajl i prati status
 * (npr. kreiran, potpisan, arhiviran).
 */
class Contract extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * 
     * @var array<string>
     */
    protected $fillable = [
        'application_id',
        'status',
        'contract_file',
        'signed_at'
    ];

    /**
     * Veza mnogo-na-jedan: ugovor pripada jednoj prijavi.
     * 
     * Omogućava dohvatanje prijave na osnovu koje je ovaj ugovor generisan.
     * Svaki ugovor je vezan za tačno jednu odobrenu prijavu.
     * Koristi se: $contract->application
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}