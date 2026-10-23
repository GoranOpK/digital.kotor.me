<fieldset style="margin: 12px 0; border: 0; padding: 0;">
    <legend style="font-weight: 600; margin-bottom: 8px;">Prisustvo trenutnih članova</legend>

    @foreach($seats as $seatNo => $member)
        @if($member)
            @php
                $selected = $current->get($member->id);
                $selectedMode = $selected?->present ? $selected->attendance_mode : 'absent';
            @endphp

            <div style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                <div style="font-weight: 600; margin-bottom: 6px;">
                    Mjesto {{ $seatNo }} — {{ $member->name }}
                </div>

                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="radio"
                               name="attendance[{{ $member->id }}]"
                               value="absent"
                               @checked($selectedMode === 'absent')>
                        Odsutan
                    </label>

                    @foreach($attendanceModes as $mode => $label)
                        <label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="radio"
                                   name="attendance[{{ $member->id }}]"
                                   value="{{ $mode }}"
                                   @checked($selectedMode === $mode)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        @else
            <p>Mjesto {{ $seatNo }}: upražnjeno — prisustvo se ne može evidentirati.</p>
        @endif
    @endforeach
</fieldset>