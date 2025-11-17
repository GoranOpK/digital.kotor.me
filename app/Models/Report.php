<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Report (Izvještaj o realizaciji)
 * 
 * Predstavlja izvještaj koji korisnik podnosi o napretku ili završetku projekta.
 * Sadrži opis aktivnosti i dokaze realizacije (dokumenta, fotografije).
 * 
 * @property int $id - Jedinstveni identifikator izvještaja
 * @property int $application_id - ID prijave za koju se podnosi izvještaj
 * @property string $description - Opis realizovanih aktivnosti
 * @property string $document_file - Putanja do dokumenta sa dokazima
 * @property string $status - Status izvještaja ('submitted', 'approved', 'rejected')
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class Report extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni.
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
     * Veza: izvještaj pripada jednoj prijavi (Many-to-One)
     * 
     * Svaki izvještaj je vezan za konkretnu prijavu na konkurs.
     * Jedna prijava može imati više izvještaja (međuizvještaji, završni izvještaj).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}