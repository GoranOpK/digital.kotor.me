<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Application;
use App\Models\Commission;
use Illuminate\Support\Facades\DB;

class CompetitionService
{
    /**
     * Kreira rang listu prijava za konkurs
     */
    public function generateRankingList(Competition $competition): array
    {
        $applications = Application::where('competition_id', $competition->id)
            ->where('status', 'submitted')
            ->get()
            ->map(function ($application) {
                // Izračunaj konačnu ocjenu ako nije izračunata
                if (!$application->final_score) {
                    $application->final_score = $application->calculateFinalScore();
                    $application->save();
                }

                return $application;
            })
            ->filter(function ($application) {
                // Filtriraj samo one koje zadovoljavaju minimum (30 bodova)
                return $application->meetsMinimumScore();
            })
            ->sortByDesc('final_score')
            ->values();

        // Dodaj poziciju na rang listi
        $position = 1;
        foreach ($applications as $application) {
            $application->ranking_position = $position;
            $application->save();
            $position++;
        }

        return $applications->toArray();
    }

    /**
     * Određuje dobitnike na osnovu budžeta
     */
    public function determineWinners(Competition $competition): array
    {
        $rankingList = $this->generateRankingList($competition);
        $remainingBudget = $competition->budget ?? 0;
        $winners = [];
        $maxSupportPerPlan = ($competition->budget * ($competition->max_support_percentage / 100)) ?? 0;

        foreach ($rankingList as $application) {
            $requestedAmount = $application['requested_amount'] ?? 0;
            
            // Proveri da li je dovoljno budžeta
            if ($remainingBudget <= 0) {
                break;
            }

            // Ako je traženi iznos veći od maksimalnog dozvoljenog, ograniči ga
            $approvedAmount = min($requestedAmount, $maxSupportPerPlan, $remainingBudget);

            if ($approvedAmount > 0) {
                $winners[] = [
                    'application_id' => $application['id'],
                    'approved_amount' => $approvedAmount,
                ];

                $remainingBudget -= $approvedAmount;
            }
        }

        return $winners;
    }

    /**
     * Rešava izjednačenje bodova (prioritet započinjanju biznisa)
     */
    public function resolveTieBreaker(array $applications): array
    {
        // Sortiraj po bodovima, pa po business_stage (započinjanje ima prioritet)
        usort($applications, function ($a, $b) {
            if ($a['final_score'] == $b['final_score']) {
                // Ako su isti bodovi, prioritet započinjanju
                if ($a['business_stage'] === 'započinjanje' && $b['business_stage'] !== 'započinjanje') {
                    return -1;
                }
                if ($a['business_stage'] !== 'započinjanje' && $b['business_stage'] === 'započinjanje') {
                    return 1;
                }
            }
            return $b['final_score'] <=> $a['final_score'];
        });

        return $applications;
    }

    /**
     * Proverava da li konkurs može biti objavljen
     */
    public function canPublish(Competition $competition): bool
    {
        // Proveri da li postoji aktivna komisija
        $commission = Commission::where('year', $competition->year ?? date('Y'))
            ->where('status', 'active')
            ->first();

        if (!$commission || !$commission->hasQuorum()) {
            return false;
        }

        // Proveri da li su svi obavezni podaci popunjeni
        return !empty($competition->title) 
            && !empty($competition->budget)
            && !empty($competition->start_date)
            && !empty($competition->end_date);
    }
}

