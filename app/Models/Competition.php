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
        'commission_id',
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

    // Veza: konkurs pripada komisiji
    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    /**
     * Izračunava datum i vreme isteka konkursa
     */
    public function getDeadlineAttribute()
    {
        $days = 20; // Fiksno po zakonu/odluci

        // 1. Ako je postavljen datum početka, on je baza za 20 dana
        if ($this->start_date) {
            return $this->start_date->copy()->addDays($days)->endOfDay();
        }

        // 2. Fallback na datum objavljivanja ako nema početka
        if ($this->published_at) {
            return $this->published_at->copy()->addDays($days)->endOfDay();
        }

        return null;
    }

    /**
     * Proverava da li je konkurs trenutno otvoren za prijave
     */
    public function getIsOpenAttribute()
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = now();
        $start = $this->start_date ? $this->start_date->startOfDay() : ($this->published_at ? $this->published_at : null);
        $deadline = $this->deadline;

        if (!$start || !$deadline) {
            return false;
        }

        return $now >= $start && $now <= $deadline;
    }

    /**
     * Proverava da li konkurs tek treba da počne
     */
    public function getIsUpcomingAttribute()
    {
        if ($this->status !== 'published' || !$this->start_date) {
            return false;
        }

        return now() < $this->start_date->startOfDay();
    }
}