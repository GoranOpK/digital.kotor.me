<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'status'
    ];

    // Veza: jedan konkurs ima više prijava
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // Veza: konkurs ima više kriterijuma
    public function evaluationCriteria()
    {
        return $this->hasMany(EvaluationCriteria::class);
    }

    // Veza: konkurs ima više prioriteta
    public function priorities()
    {
        return $this->hasMany(Priority::class);
    }
}