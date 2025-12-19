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
        'business_plan_name',
        'applicant_type',
        'business_stage',
        'founder_name',
        'director_name',
        'company_seat',
        'physical_person_name',
        'physical_person_jmbg',
        'physical_person_phone',
        'physical_person_email',
        'requested_amount',
        'total_budget_needed',
        'approved_amount',
        'business_area',
        'website',
        'bank_account',
        'vat_number',
        'de_minimis_declaration',
        'previous_support_declaration',
        'is_registered',
        'accuracy_declaration',
        'status',
        'final_score',
        'ranking_position',
        'rejection_reason',
        'submitted_at',
        'evaluated_at',
        'interview_scheduled_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'total_budget_needed' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'final_score' => 'decimal:2',
        'de_minimis_declaration' => 'boolean',
        'previous_support_declaration' => 'boolean',
        'is_registered' => 'boolean',
        'accuracy_declaration' => 'boolean',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'interview_scheduled_at' => 'datetime',
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

    // Veza: aplikacija ima ocjene od komisije (evaluation scores)
    public function evaluationScores()
    {
        return $this->hasMany(EvaluationScore::class);
    }

    // Veza: aplikacija ima biznis plan
    public function businessPlan()
    {
        return $this->hasOne(BusinessPlan::class);
    }

    // Veza: aplikacija pripada korisniku
    public function user()
    {
        return $this->belongsTo(User::class);
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

    /**
     * Izračunava prosječnu ocjenu po kriterijumu (zbir ocjena svih članova / broj članova)
     */
    public function calculateAverageScorePerCriterion(int $criterionNumber): float
    {
        $scores = $this->evaluationScores()
            ->whereNotNull("criterion_{$criterionNumber}")
            ->pluck("criterion_{$criterionNumber}")
            ->toArray();

        if (empty($scores)) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Izračunava konačnu ocjenu (zbir prosječnih ocjena po svim kriterijumima)
     */
    public function calculateFinalScore(): float
    {
        $totalScore = 0;
        
        for ($i = 1; $i <= 10; $i++) {
            $totalScore += $this->calculateAverageScorePerCriterion($i);
        }

        return round($totalScore, 2);
    }

    /**
     * Proverava da li prijava zadovoljava minimum (30 bodova)
     */
    public function meetsMinimumScore(): bool
    {
        $finalScore = $this->final_score ?? $this->calculateFinalScore();
        return $finalScore >= 30;
    }

    /**
     * Proverava da li su svi članovi komisije ocjenili prijavu
     */
    public function isFullyEvaluated(): bool
    {
        $competition = $this->competition;
        if (!$competition) {
            return false;
        }

        // Pronađi aktivnu komisiju za godinu konkursa
        $commission = \App\Models\Commission::where('year', $competition->year)
            ->where('status', 'active')
            ->first();

        if (!$commission) {
            return false;
        }

        $activeMembersCount = $commission->activeMembers()->count();
        $evaluationCount = $this->evaluationScores()->count();

        return $evaluationCount >= $activeMembersCount;
    }

    /**
     * Proverava da li prijava ima sve obavezne dokumente
     */
    public function hasAllRequiredDocuments(): bool
    {
        $requiredDocs = $this->getRequiredDocuments();
        $uploadedDocs = $this->documents()
            ->where('is_required', true)
            ->pluck('document_type')
            ->toArray();

        foreach ($requiredDocs as $docType) {
            if (!in_array($docType, $uploadedDocs)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vraća listu obaveznih dokumenata prema tipu prijave
     * 
     * VAŽNO: 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti
     *        'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (preduzetnik)
     */
    public function getRequiredDocuments(): array
    {
        $documents = [];
        
        // Dokumenti vezani za registraciju (ne obavezni ako nema registrovanu djelatnost)
        $registrationDocs = ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'potvrda_upc_porezi', 'ioppd_obrazac', 'godisnji_racuni'];

        // Fizičko lice BEZ registrovane djelatnosti (nema registrovanu djelatnost u skladu sa Zakonom o privrednim društvima)
        if ($this->applicant_type === 'fizicko_lice') {
            // Fizičko lice nema registrovanu djelatnost, samo lična karta i biznis plan
            $documents = [
                'licna_karta',
                'biznis_plan_usb',
            ];
        } elseif ($this->applicant_type === 'preduzetnica' && $this->business_stage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'biznis_plan_usb',
            ];
        } elseif ($this->applicant_type === 'preduzetnica' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'biznis_plan_usb',
            ];
        } elseif ($this->applicant_type === 'doo' && $this->business_stage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'biznis_plan_usb',
            ];
        } elseif ($this->applicant_type === 'doo' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'godisnji_racuni',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'biznis_plan_usb',
            ];
        }

        // Ako nema registrovanu djelatnost, ukloni dokumente vezane za registraciju
        if ($this->is_registered === false) {
            $documents = array_filter($documents, function($doc) use ($registrationDocs) {
                return !in_array($doc, $registrationDocs);
            });
        }

        // Ako je prethodno dobijala podršku, dodaj izvještaj
        if ($this->previous_support_declaration) {
            $documents[] = 'izvjestaj_realizacija';
            $documents[] = 'finansijski_izvjestaj';
        }

        return array_values($documents); // Reindex array
    }
}