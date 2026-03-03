<?php

namespace App\Mail;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommissionAssignedToCompetition extends Mailable
{
    use Queueable, SerializesModels;

    public CommissionMember $member;
    public Competition $competition;
    public Commission $commission;

    /**
     * Create a new message instance.
     */
    public function __construct(CommissionMember $member, Competition $competition)
    {
        $this->member = $member;
        $this->competition = $competition;
        $this->commission = $member->commission;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject('Imenovanje u Komisiju za podršku ženskom preduzetništvu')
            ->view('emails.commissions.assigned');
    }
}

