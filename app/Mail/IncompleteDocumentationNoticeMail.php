<?php

namespace App\Mail;

use App\Models\Application;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Operational notice only. Not the legal act. Named facts come from frozen evidence.
 */
class IncompleteDocumentationNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly string $recipientName,
        public readonly string $frozenApplicantName,
        public readonly string $competitionTitle,
        public readonly string $applicationNumber,
        public readonly string $subsidyType,
        public readonly int $complaintWindowDays,
        public readonly string $commissionName,
        public readonly ?string $complaintUrl = null,
        public readonly ?CarbonImmutable $complaintDeadline = null,
        public readonly ?string $reason = null,
    ) {
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Obavještenje o rezultatu provjere potpunosti – '.$this->competitionTitle)
            ->view('emails.applications.incomplete_documentation_notice');
    }
}
