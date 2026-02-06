<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    /**
     * Provjerava da li je rang lista formirana - rok za prijave je istekao
     * i svi članovi komisije su ocjenili sve prijave
     */
    public function isRankingFormed(): bool
    {
        // Rang lista se ne formira prije isteka roka za prijave (osim ako je konkurs već zatvoren)
        if ($this->status !== 'closed' && !$this->isApplicationDeadlinePassed()) {
            Log::channel('single')->info('[RANG LISTA DEBUG] isRankingFormed = false (rok nije istekao)', [
                'competition_id' => $this->id,
                'status' => $this->status,
                'isApplicationDeadlinePassed' => $this->isApplicationDeadlinePassed(),
            ]);
            return false;
        }

        $commission = $this->commission;
        if (!$commission) {
            Log::channel('single')->info('[RANG LISTA DEBUG] isRankingFormed = false (nema komisije)', [
                'competition_id' => $this->id,
                'commission_id' => $this->commission_id,
            ]);
            return false;
        }

        $activeMemberIds = $commission->activeMembers()->pluck('id');
        if ($activeMemberIds->isEmpty()) {
            Log::channel('single')->info('[RANG LISTA DEBUG] isRankingFormed = false (nema aktivnih članova)', [
                'competition_id' => $this->id,
                'commission_id' => $commission->id,
            ]);
            return false;
        }

        $applications = $this->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'rejected'])
            ->get();

        if ($applications->isEmpty()) {
            Log::channel('single')->info('[RANG LISTA DEBUG] isRankingFormed = false (nema prijava)', [
                'competition_id' => $this->id,
            ]);
            return false;
        }

        $activeMembersCount = $activeMemberIds->count();
        foreach ($applications as $application) {
            $evaluatedCount = \App\Models\EvaluationScore::where('application_id', $application->id)
                ->whereIn('commission_member_id', $activeMemberIds)
                ->pluck('commission_member_id')
                ->unique()
                ->count();

            if ($evaluatedCount < $activeMembersCount) {
                Log::channel('single')->info('[RANG LISTA DEBUG] isRankingFormed - prijava nije sva ocijenjena', [
                    'competition_id' => $this->id,
                    'application_id' => $application->id,
                    'evaluated_count' => $evaluatedCount,
                    'active_members_count' => $activeMembersCount,
                ]);
                return false;
            }
        }

        Log::channel('single')->info('[RANG LISTA DEBUG] isRankingFormed = true', [
            'competition_id' => $this->id,
            'applications_count' => $applications->count(),
            'active_members_count' => $activeMembersCount,
        ]);
        return true;
    }

    /**
     * Provjerava da li je predsjednik komisije donio odluku za sve prijave u rang listi
     * (zaključak komisije za svaku prijavu)
     */
    public function hasChairmanCompletedDecisions(): bool
    {
        if (!$this->isRankingFormed()) {
            return false;
        }

        $commission = $this->commission;
        $chairmanMember = $commission ? $commission->activeMembers()->where('position', 'predsjednik')->first() : null;
        if (!$chairmanMember) {
            return false;
        }

        $allApplications = $this->applications()
            ->whereIn('status', ['submitted', 'evaluated', 'rejected'])
            ->with('evaluationScores')
            ->get();

        $rankingApplications = $allApplications->filter(function ($application) use ($chairmanMember) {
            if ($application->evaluationScores->isEmpty() || !$application->meetsMinimumScore()) {
                return false;
            }
            $chairmanScore = $application->evaluationScores->firstWhere('commission_member_id', $chairmanMember->id);
            if ($chairmanScore && $chairmanScore->documents_complete === false) {
                return false;
            }
            return true;
        });

        // Ako nema prijava u rang listi (sve odbijene), predsjednik je završio
        if ($rankingApplications->isEmpty()) {
            return true;
        }
        return $rankingApplications->every(fn ($app) => $app->commission_decision !== null);
    }
}