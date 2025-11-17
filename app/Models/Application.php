<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Application (Prijava na konkurs)
 * 
 * Predstavlja prijavu korisnika na konkurs za žensko ili omladinsko preduzetništvo.
 * Čuva podatke o aplikaciji, biznis planu, statusu i povezanim dokumentima.
 * 
 * @property int $id - Jedinstveni identifikator prijave
 * @property int $competition_id - ID konkursa na koji se prijavljuje
 * @property int $user_id - ID korisnika koji podnosi prijavu
 * @property string $type - Tip prijave (npr. 'žensko', 'omladinsko')
 * @property string $status - Status prijave ('pending', 'approved', 'rejected')
 * @property string $business_plan - Tekst biznis plana
 * @property \Illuminate\Support\Carbon $created_at - Vrijeme kreiranja prijave
 * @property \Illuminate\Support\Carbon $updated_at - Vrijeme posljednje izmjene
 */
class Application extends Model
{
    use HasFactory;

    /**
     * Atributi koji mogu biti masovno dodijeljeni (mass assignable).
     * Ovi podaci se mogu popunjavati direktno preko forme.
     * 
     * @var array<string>
     */
    protected $fillable = [
        'competition_id',
        'user_id',
        'type',
        'status',
        'business_plan'
    ];

    /**
     * Veza: aplikacija pripada konkursu (Many-to-One)
     * 
     * Svaka prijava je povezana sa jednim konkursom.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Veza: aplikacija ima više dokumenata (One-to-Many)
     * 
     * Uz prijavu korisnik može priložiti različite dokumente
     * (CV, dozvole, sertifikate, itd.)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /**
     * Veza: aplikacija ima više ocjena (One-to-Many)
     * 
     * Svaki evaluator može dati ocjene prijavi po različitim kriterijumima.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class);
    }

    /**
     * Veza: aplikacija ima izvještaje realizacije (One-to-Many)
     * 
     * Nakon odobrenja prijave, korisnik podnosi izvještaje o realizaciji projekta.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Veza: aplikacija ima jedan ugovor (One-to-One)
     * 
     * Za odobrene prijave generiše se ugovor koji korisnik potpisuje.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contract()
    {
        return $this->hasOne(Contract::class);
    }
}