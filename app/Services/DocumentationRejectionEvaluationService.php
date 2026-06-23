<?php

namespace App\Services;

use App\Mail\ApplicationRejectedMissingDocumentsMail;
use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\EvaluationScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DocumentationRejectionEvaluationService
{
    /**
     * Odbij prijavu zbog nedostatka dokumentacije i poništi sve bodove članova komisije.
     * Ostaju zapisi u evaluation_scores (null kriterijumi) po jedan red po članu — trag u bazi i prazan prikaz u tabeli.
     */
    public static function rejectApplicationAndVoidScores(
        Application $application,
        CommissionMember $chairmanMember,
        ?string $chairmanNotes = null,
    ): void {
        DB::transaction(function () use ($application, $chairmanMember, $chairmanNotes) {
            $application->refresh();

            $competition = $application->competition;
            if (!$competition || !$competition->commission) {
                return;
            }

            $members = $competition->commission
                ->activeMembers()
                ->orderByRaw("CASE WHEN position = 'predsjednik' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            foreach ($members as $member) {
                $isChairman = (int) $member->id === (int) $chairmanMember->id;

                EvaluationScore::updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'commission_member_id' => $member->id,
                    ],
                    [
                        'documents_complete' => false,
                        'criterion_1' => null,
                        'criterion_2' => null,
                        'criterion_3' => null,
                        'criterion_4' => null,
                        'criterion_5' => null,
                        'criterion_6' => null,
                        'criterion_7' => null,
                        'criterion_8' => null,
                        'criterion_9' => null,
                        'criterion_10' => null,
                        'average_score' => null,
                        'final_score' => 0,
                        'notes' => $isChairman ? $chairmanNotes : null,
                        'justification' => null,
                    ],
                );
            }

            $application->update([
                'status' => 'rejected',
                'rejection_reason' => 'Nedostaju potrebna dokumenta',
                'final_score' => null,
                'evaluated_at' => null,
            ]);
        });

        self::sendMissingDocumentsRejectionEmail($application->fresh(['documents', 'user', 'competition.upNumber']), $chairmanNotes);
    }

    /**
     * Šalje obavještenje podnositeljki o odbijanju zbog nepotpune dokumentacije (jednom po prijavi).
     */
    protected static function sendMissingDocumentsRejectionEmail(Application $application, ?string $chairmanNotes = null): void
    {
        if ((int) ($application->email ?? 0) === 1) {
            return;
        }

        $recipientEmail = $application->user?->email;
        if (!$recipientEmail) {
            return;
        }

        try {
            $missingLabels = $application->getMissingRequiredDocumentLabels();

            Mail::to($recipientEmail)->send(
                new ApplicationRejectedMissingDocumentsMail($application, $missingLabels, $chairmanNotes)
            );

            $application->update(['email' => 1]);
        } catch (\Throwable $e) {
            Log::error('Greška pri slanju maila o nepotpunoj dokumentaciji prijave ID ' . $application->id . ': ' . $e->getMessage());
        }
    }
}
