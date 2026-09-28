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
    public CommissionAppointmentMailCopy $copy;
    public ?CommissionMember $replacedMember;

    /**
     * Create a new message instance.
     */
    public function __construct(
        CommissionMember $member,
        CommissionAppointmentMailCopy $copy,
        ?CommissionMember $replacedMember = null
    ) {
        $this->member = $member;
        $this->commission = $member->commission;
        $this->copy = $copy;
        $this->replacedMember = $replacedMember;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->from('noreply@kotor.me', 'Opština Kotor')
            ->subject($this->copy->memberAddedSubject((bool) $this->member->is_substitute))
            ->view('emails.commissions.member_added', [
                'copy' => $this->copy,
            ]);
    }
}
