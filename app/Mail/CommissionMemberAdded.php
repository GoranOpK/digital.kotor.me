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
    public ?CommissionMember $replacedMember;

    /**
     * Create a new message instance.
     */
    public function __construct(CommissionMember $member, ?CommissionMember $replacedMember = null)
    {
        $this->member = $member;
        $this->commission = $member->commission;
        $this->replacedMember = $replacedMember;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $subject = $this->member->is_substitute
            ? 'Imenovanje za zamjenskog člana Komisije za podršku ženskom preduzetništvu'
            : 'Imenovanje u Komisiju za podršku ženskom preduzetništvu';

        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject($subject)
            ->view('emails.commissions.member_added');
    }
}

