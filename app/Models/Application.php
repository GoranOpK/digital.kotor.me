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
        'pib',
        'crps_number',
        'registration_form',
        'de_minimis_declaration',
        'previous_support_declaration',
        'is_registered',
        'accuracy_declaration',
        'status',
        'final_score',
        'ranking_position',
        'rejection_reason',
        'commission_decision',
        'commission_justification',
        'commission_notes',
        'commission_decision_date',
        'signed_by_chairman',
        'signed_by_members',
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
        'commission_decision_date' => 'date',
        'signed_by_chairman' => 'boolean',
        'signed_by_members' => 'array',
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
     * Proverava da li je Obrazac 1a/1b kompletno popunjen
     * (sva obavezna polja + svi obavezni checkbox-ovi)
     */
    public function isObrazacComplete(): bool
    {
        // Proveri osnovna obavezna polja
        if (!$this->business_plan_name || 
            !$this->applicant_type || 
            !$this->business_stage || 
            !$this->business_area || 
            !$this->requested_amount || 
            !$this->total_budget_needed) {
            return false;
        }

        // Proveri polja specifična za tip podnosioca
        if ($this->applicant_type === 'fizicko_lice') {
            if (!$this->physical_person_name || 
                !$this->physical_person_jmbg || 
                !$this->physical_person_phone || 
                !$this->physical_person_email) {
                return false;
            }
        } elseif ($this->applicant_type === 'doo' || $this->applicant_type === 'ostalo') {
            if (!$this->founder_name || 
                !$this->director_name || 
                !$this->company_seat) {
                return false;
            }
        }

        // Proveri oblik registracije (obavezan za sve osim fizičkog lica)
        if ($this->applicant_type !== 'fizicko_lice' && !$this->registration_form) {
            return false;
        }

        // Proveri obavezne checkbox-ove
        // de_minimis_declaration je obavezan za sve
        if (!$this->de_minimis_declaration) {
            return false;
        }

        // accuracy_declaration je obavezan samo za fizičko lice
        if ($this->applicant_type === 'fizicko_lice' && !$this->accuracy_declaration) {
            return false;
        }

        return true;
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
     * Prema Odluci, član 12
     * 
     * VAŽNO: 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti
     *        'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (preduzetnik)
     */
    public function getRequiredDocuments(): array
    {
        $documents = [];
        $isRegistered = $this->is_registered ?? false;
        
        // Preduzetnice koje započinju biznis (započinjanje)
        if ($this->applicant_type === 'preduzetnica' && $this->business_stage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'biznis_plan_usb',
            ];
            
            // Dokumenti vezani za registraciju samo ako ima registrovanu djelatnost
            if ($isRegistered) {
                $documents[] = 'crps_resenje';
                $documents[] = 'pib_resenje';
                // PDV samo ako je obveznik PDV-a (provjerava se kroz vat_number ili pdv_resenje)
                // Za sada dodajemo pdv_resenje ako ima registrovanu djelatnost
                $documents[] = 'pdv_resenje';
            }
        }
        // Preduzetnice koje planiraju razvoj poslovanja (razvoj)
        elseif ($this->applicant_type === 'preduzetnica' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                // PDV samo ako je obveznik PDV-a - za sada dodajemo kao obavezno
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'biznis_plan_usb',
            ];
        }
        // Društva (DOO) koja započinju biznis (započinjanje)
        elseif ($this->applicant_type === 'doo' && $this->business_stage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'biznis_plan_usb',
            ];
            
            // Dokumenti vezani za registraciju samo ako ima registrovanu djelatnost
            if ($isRegistered) {
                $documents[] = 'crps_resenje';
                $documents[] = 'pib_resenje';
                // PDV samo ako je obveznik PDV-a
                $documents[] = 'pdv_resenje';
                $documents[] = 'statut';
                $documents[] = 'karton_potpisa';
            }
        }
        // Društva (DOO) koja planiraju razvoj poslovanja (razvoj)
        elseif ($this->applicant_type === 'doo' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                // PDV samo ako je obveznik PDV-a - za sada dodajemo kao obavezno
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
        // Ostalo (druga društva) - isti dokumenti kao DOO
        elseif ($this->applicant_type === 'ostalo' && $this->business_stage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'biznis_plan_usb',
            ];
            
            // Dokumenti vezani za registraciju samo ako ima registrovanu djelatnost
            if ($isRegistered) {
                $documents[] = 'crps_resenje';
                $documents[] = 'pib_resenje';
                $documents[] = 'pdv_resenje';
                $documents[] = 'statut';
                $documents[] = 'karton_potpisa';
            }
        } elseif ($this->applicant_type === 'ostalo' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                // PDV samo ako je obveznik PDV-a - za sada dodajemo kao obavezno
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
        // Fizičko lice BEZ registrovane djelatnosti
        elseif ($this->applicant_type === 'fizicko_lice') {
            // Fizičko lice nema registrovanu djelatnost, samo lična karta i biznis plan
            $documents = [
                'licna_karta',
                'biznis_plan_usb',
            ];
        }

        // Ako je prethodno dobijala podršku, dodaj izvještaj
        if ($this->previous_support_declaration) {
            $documents[] = 'izvjestaj_realizacija';
            $documents[] = 'finansijski_izvjestaj';
        }

        return array_values($documents); // Reindex array
    }
}