<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'name',
        'description',
        'max_score'
    ];

    // Veza: kriterijum pripada konkursu
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    // Veza: Score-ovi za ovaj kriterijum
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class, 'criteria_id');
    }
}