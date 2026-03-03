<?php

namespace App\Mail;

use App\Models\Commission;
use App\Models\CommissionMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommissionMemberAdded extends Mailable
{
    use Queueable, SerializesModels;

    public CommissionMember $member;
    public Commission $commission;

    /**
     * Create a new message instance.
     */
    public function __construct(CommissionMember $member)
    {
        $this->member = $member;
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
            ->view('emails.commissions.member_added');
    }
}

