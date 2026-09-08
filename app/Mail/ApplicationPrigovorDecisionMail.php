<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\ApplicationPrigovor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationPrigovorDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public Application $application;

    public ApplicationPrigovor $prigovor;

    public string $recipientName;

    public string $decisionLabel;

    public string $applicationUrl;

    public function __construct(Application $application, ApplicationPrigovor $prigovor)
    {
        $application->loadMissing('user');

        $this->application = $application;
        $this->prigovor = $prigovor;
        $this->recipientName = $application->user?->name ?? 'podnositeljko';
        $this->decisionLabel = $prigovor->statusLabel();
        $this->applicationUrl = route('applications.show', $application);
    }

    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Obavještenje o odluci Komisije po Prigovoru')
            ->view('emails.applications.prigovor_decision');
    }
}
