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

    /**
     * Vraća datum zatvaranja prijava (baza za 30-dnevni rok za odluku).
     * Ako je closed_at postavljen (ručno zatvaranje), koristi ga.
     * Inače, ako je rok za prijave istekao, koristi datum roka za prijave.
     */
    public function getApplicationsClosedAt(): ?\Carbon\Carbon
    {
        if ($this->closed_at) {
            return $this->closed_at;
        }
        if ($this->isApplicationDeadlinePassed() && $this->deadline) {
            return $this->deadline;
        }
        return null;
    }

    /**
     * Proverava da li je prošlo 30 dana od zatvaranja prijava
     * Komisija mora donijeti odluku u roku od 30 dana od dana zatvaranja prijava
     */
    public function isEvaluationDeadlinePassed(): bool
    {
        $closedAt = $this->getApplicationsClosedAt();
        if (!$closedAt) {
            return false;
        }

        $deadline = $closedAt->copy()->addDays(30);
        return now()->isAfter($deadline);
    }

    /**
     * Vraća preostalo vrijeme do isteka roka za ocjenjivanje (u danima)
     */
    public function getDaysUntilEvaluationDeadline(): ?int
    {
        $closedAt = $this->getApplicationsClosedAt();
        if (!$closedAt) {
            return null;
        }

        $deadline = $closedAt->copy()->addDays(30);
        $daysRemaining = now()->diffInDays($deadline, false);
        
        return $daysRemaining >= 0 ? (int) $daysRemaining : 0;
    }

    /**
     * Vraća datum isteka roka za donošenje odluke (30 dana od zatvaranja prijava)
     */
    public function getEvaluationDeadlineDate(): ?\Carbon\Carbon
    {
        $closedAt = $this->getApplicationsClosedAt();
        if (!$closedAt) {
            return null;
        }
        return $closedAt->copy()->addDays(30);
    }

    /**
     * Vraća preostalo vrijeme do isteka roka za prijave (u danima)
     * Rok za prijave je 20 dana od početka konkursa
     */
    public function getDaysUntilApplicationDeadline(): ?int
    {
        if ($this->status !== 'published') {
            return null;
        }

        $deadline = $this->deadline;
        if (!$deadline) {
            return null;
        }

        $daysRemaining = now()->diffInDays($deadline, false);
        return $daysRemaining >= 0 ? $daysRemaining : 0;
    }

    /**
     * Provjerava da li je rok za prijave istekao
     */
    public function isApplicationDeadlinePassed(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $deadline = $this->deadline;
        if (!$deadline) {
            return false;
        }

        return now()->isAfter($deadline);
    }
}