<?php

namespace App\Mail;

use App\Models\ApplicationComplaint;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Operational notice to the applicant about the Commission decision on a canonical complaint,
 * including the decision reasoning. Carries no other Commission-internal data (member names, scores).
 */
class CanonicalComplaintDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $competitionTitle,
        public readonly string $applicationReference,
        public readonly string $decision,
        public readonly CarbonImmutable $decidedAt,
        public readonly string $statusUrl,
        public readonly ?string $decisionNotes = null,
    ) {
    }

    public function accepted(): bool
    {
        return $this->decision === ApplicationComplaint::ACCEPTED;
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Odluka o prigovoru na nalaz o nepotpunosti – '.$this->competitionTitle)
            ->view('emails.applications.canonical_complaint_decision');
    }
}
