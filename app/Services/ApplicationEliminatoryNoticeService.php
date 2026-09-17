<?php

namespace App\Services;

use App\Mail\ApplicationEliminatoryAppealNoticeMail;
use App\Mail\ApplicationEliminatoryRejectionMail;
use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\ApplicationEliminatoryNotice;
use App\Support\EliminatoryProfileConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ApplicationEliminatoryNoticeService
{
    /**
     * Persist the durable portal notice. Must run inside the fail-confirm transaction.
     * Does not send mail and never resets an existing sent_at.
     */
    public function persistForFailedCheck(Application $application, ApplicationEliminatoryCheck $check): ApplicationEliminatoryNotice
    {
        $notice = ApplicationEliminatoryNotice::query()
            ->where('application_id', $application->id)
            ->lockForUpdate()
            ->first();

        if ($notice !== null) {
            return $notice;
        }

        $profile = $check->profileConfig();

        return ApplicationEliminatoryNotice::create([
            'application_id' => $application->id,
            'eliminatory_check_id' => $check->id,
            'sent_at' => now(),
            'portal_recorded_at' => now(),
            'reasons_snapshot' => $check->failedCriterionLabels(),
            'note_snapshot' => $profile->usesStructuredNotes
                ? EliminatoryProfileConfig::humanYouthActivatedExplanations($check)
                : $check->note,
        ]);
    }

    /**
     * Deliver registered-email notice after the confirm+portal-notice transaction has committed.
     */
    public function deliverRegisteredEmail(Application $application): void
    {
        $application->loadMissing(['user', 'eliminatoryNotice', 'competition', 'eliminatoryCheck']);
        $notice = $application->eliminatoryNotice;

        if ($notice === null || $notice->mail_sent_at !== null) {
            return;
        }

        $recipient = $application->user?->email;
        if (! is_string($recipient) || trim($recipient) === '') {
            $notice->mail_failed_at = now();
            $notice->save();

            return;
        }

        try {
            $mailable = $application->competition?->isOmladinskoProfile()
                ? new ApplicationEliminatoryAppealNoticeMail($application, $notice->fresh())
                : new ApplicationEliminatoryRejectionMail($application, $notice->fresh());

            Mail::to($recipient)->send($mailable);
            $notice->mail_sent_at = now();
            $notice->mail_failed_at = null;
            $notice->save();
        } catch (Throwable $e) {
            Log::warning('Eliminatory rejection mail failed.', [
                'application_id' => $application->id,
                'notice_id' => $notice->id,
                'exception' => $e->getMessage(),
            ]);
            $notice->mail_failed_at = now();
            $notice->save();
        }
    }
}
