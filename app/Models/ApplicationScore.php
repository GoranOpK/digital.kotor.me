<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'evaluator_id',
        'criteria_id',
        'score',
        'comment'
    ];

    // Veza: score pripada aplikaciji
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // Veza: evaluator je User
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // Veza: kriterijum ocjene
    public function criteria()
    {
        return $this->belongsTo(EvaluationCriteria::class, 'criteria_id');
    }
}