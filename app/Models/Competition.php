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
        'status',
        'competition_number',
        'year',
        'budget',
        'max_support_percentage',
        'deadline_days',
        'published_at',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
        'budget' => 'decimal:2',
        'max_support_percentage' => 'decimal:2',
        'year' => 'integer',
        'deadline_days' => 'integer',
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