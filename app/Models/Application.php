<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'user_id',
        'type',
        'status',
        'business_plan'
    ];

    // Veza: aplikacija pripada konkursu
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    // Veza: aplikacija ima više dokumenata
    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    // Veza: aplikacija ima više ocjena (score)
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class);
    }

    // Veza: aplikacija ima izvještaje realizacije
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // Veza: aplikacija ima jedan ugovor
    public function contract()
    {
        return $this->hasOne(Contract::class);
    }
}