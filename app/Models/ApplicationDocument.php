<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model ApplicationDocument (Dokument uz prijavu)
 * 
 * Predstavlja dokumente koje korisnik prilaže uz prijavu na konkurs
 * (CV, sertifikati, dozvole, biznis plan u PDF formatu, itd.).
 * 
 * @property int $id - Jedinstveni identifikator dokumenta
 * @property int $application_id - ID prijave kojoj dokument pripada
 * @property string $name - Naziv dokumenta
 * @property string $file_path - Putanja do fajla na serveru
 * @property string $type - Tip dokumenta ('cv', 'certificate', 'business_plan', itd.)
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme upload-a
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class ApplicationDocument extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni.
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
     * Veza: dokument pripada jednoj prijavi (Many-to-One)
     * 
     * Omogućava pristup prijavi kojoj ovaj dokument pripada.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}