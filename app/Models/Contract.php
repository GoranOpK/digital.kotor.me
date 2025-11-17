<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Contract (Ugovor)
 * 
 * Predstavlja ugovor između Opštine i dobitnika konkursa.
 * Generiše se nakon odobravanja prijave i sadrži sve uslove saradnje.
 * 
 * @property int $id - Jedinstveni identifikator ugovora
 * @property int $application_id - ID prijave za koju se generiše ugovor
 * @property string $status - Status ugovora ('draft', 'sent', 'signed')
 * @property string $contract_file - Putanja do PDF fajla sa ugovorom
 * @property \Illuminate\Support\Carbon $signed_at - Datum potpisivanja ugovora
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class Contract extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni.
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
     * Veza: ugovor pripada jednoj prijavi (Many-to-One)
     * 
     * Svaki ugovor je vezan za jednu odobrenu prijavu na konkurs.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}